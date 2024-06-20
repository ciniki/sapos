<?php
//
// Description
// -----------
// This function will create a customer in Stripe and link to customer in Ciniki
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_stripeInvoiceAutoCharge(&$ciniki, $tnid, $invoice_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLogAdd');

    //
    // Load the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.480', 'msg'=>'Unable to load invoice for auto charge', 'err'=>$rc['err']));
    }
    $invoice = $rc['invoice'];

    //
    // Load stripe settings
    //
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.483', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $currency = 'CAD';
    if( isset($rc['settings']['intl-default-currency']) && $rc['settings']['intl-default-currency'] != '' ) {
        $currency = $rc['settings']['intl-default-currency'];
    }

    //
    // Initialize Stripe Library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev14/init.php');

    $stripe = new \Stripe\StripeClient([
        'api_key' => $settings['stripe-sk'],
        'stripe_version' => '2024-04-10',
        ]);

    //
    // Check stripe_customer_id setup
    //
    if( isset($invoice['customer']['stripe_customer_id']) && $invoice['customer']['stripe_customer_id'] != '' 
        && isset($invoice['stripe_pm_id']) && $invoice['stripe_pm_id'] != '' 
        && $invoice['balance_amount'] > 0 
        && ($invoice['flags']&0x08) == 0x08   // Auto bill is on
        ) {

        //
        // Setup the intent
        //
        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => intval($invoice['balance_amount']*100),
                'currency' => $currency,
                'description' => "Invoice #{$invoice['invoice_number']} Payment",
                'customer' => $invoice['customer']['stripe_customer_id'],
                'payment_method' => $invoice['stripe_pm_id'],
//                'off_session' => true,
                'metadata' => [
                    'invoice_number' => $invoice['invoice_number'],
                    ],
                ]);
        } catch(Exception $e) {
            ciniki_sapos_invoiceLogAdd($ciniki, $tnid, [
                'invoice_id' => $invoice['id'],
                'customer_id' => $invoice['customer']['id'],
                'status' => 50,
                'action' => 'Auto Bill - Start',
                'code' => '',
                'msg' => $e->getMessage(),
                ]);
            error_log("STRIPE-AUTOBILL [{$invoice['id']}]: " . $e->getMessage());
            return array('stat'=>'ok');
        }

        //
        // Create the transaction
        //
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $transaction = array(
            'invoice_id' => $invoice['id'],
            'status' => 20, // Processing
            'transaction_type' => 20,
            'transaction_date' => $dt->format('Y-m-d H:i:s'),
            'source' => 30,
            'customer_amount' => $invoice['balance_amount'],
            'transaction_fees' => 0,
            'tenant_amount' => $invoice['balance_amount'],
            'user_id' => -3, // Ciniki Robot User
            'notes' => '',
            'gateway' => 30,
            'gateway_token' => $paymentIntent->id,
            'gateway_status' => 'pending',
            'gateway_response' => '', // Leave response blank, not needed
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.transaction', $transaction, 0x07);
        if( $rc['stat'] != 'ok' ) {
            ciniki_sapos_invoiceLogAdd($ciniki, $tnid, [
                'invoice_id' => $invoice['id'],
                'customer_id' => $invoice['customer']['id'],
                'status' => 50,
                'action' => 'Auto Bill - Record Transaction',
                'code' => 'ciniki.sapos.485',
                'msg' => 'Unable to record transaction',
                ]);
            try {
                $paymentIntent = $stripe->paymentIntents->cancel($request['args']['intent_id'], []);
            } catch(Exception $e) {
                ciniki_sapos_invoiceLogAdd($ciniki, $tnid, [
                    'invoice_id' => $invoice['id'],
                    'customer_id' => $invoice['customer']['id'],
                    'status' => 50,
                    'action' => 'Auto Bill - Record Transaction',
                    'code' => '',
                    'msg' => $e->getMessage(),
                    ]);
                return array('stat'=>'ok');
            }
            return array('stat'=>'ok');
        }

        //
        // Confirm the intent
        //
        try {
            $stripe->paymentIntents->confirm($paymentIntent->id, [
                'off_session' => true,
                ]);
        } catch(Exception $e) {
            ciniki_sapos_invoiceLogAdd($ciniki, $tnid, [
                'invoice_id' => $invoice['id'],
                'customer_id' => $invoice['customer']['id'],
                'status' => 50,
                'action' => 'Auto Bill - Finish',
                'code' => '',
                'msg' => $e->getMessage(),
                ]);
            error_log("STRIPE-AUTOBILL [{$invoice['id']}]: " . $e->getMessage());
            return array('stat'=>'ok');
        }

        ciniki_sapos_invoiceLogAdd($ciniki, $tnid, [
            'invoice_id' => $invoice['id'],
            'customer_id' => $invoice['customer']['id'],
            'status' => 10,
            'action' => 'Auto Bill - Completed',
            'code' => '',
            'msg' => 'Payment confirmed',
            ]);
    }

    return array('stat'=>'ok');
}
?>
