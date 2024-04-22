<?php
//
// Description
// -----------
//
// Returns
// -------
//
function ciniki_sapos_wng_apiInvoiceStripeIntentCreate(&$ciniki, $tnid, &$request) {

    if( isset($request['site']['settings']['stripe-pk']) && $request['site']['settings']['stripe-pk'] != '' 
        && isset($request['site']['settings']['stripe-sk']) && $request['site']['settings']['stripe-sk'] != '' 
        && isset($request['site']['settings']['stripe-version']) && $request['site']['settings']['stripe-version'] == 'elements' 
        ) {

        //
        // Load the invoice
        //
        if( !isset($request['args']['invoice_id']) || $request['args']['invoice_id'] == '' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.437', 'msg'=>'No invoice specified'));
        }

        //
        // FIXME: Load the invoice
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
        $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $request['args']['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.438', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
        }
        $invoice = $rc['invoice'];

        $billing_name = isset($invoice['billing_name']) ? $invoice['billing_name'] : '';
        $receipt_email = '';
        if( isset($invoice['customer']['emails'][0]['email']['address']) ) {
            $receipt_email = $invoice['customer']['emails'][0]['email']['address'];
        }
        
        //
        // Receipt email is required for improved fraud prevention
        //
        if( $receipt_email == '' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.440', 'msg'=>'You must add an email address to your account.'));
        }

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
            'api_key' => $request['site']['settings']['stripe-sk'],
            'stripe_version' => '2024-04-10',
            ]);

        $button_text = "Pay {$currency} $" . number_format($invoice['balance_amount'], 2);

        //
        // Setup the stripe customer 
        //
        if( !isset($invoice['customer']['stripe_customer_id']) || $invoice['customer']['stripe_customer_id'] == '' ) {
            try {
                $customer = $stripe->customers->create([
                    'name' => $invoice['customer']['display_name'],
                    'email' => $receipt_email,
                    'metadata' => [
                        'customer_uuid' => $invoice['customer']['uuid'],
                        ],
                    ]);
            } catch(Exception $e) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.441', 'msg'=>$e->getMessage()));
            }
            
            //
            // Save the stripe customer id for future use
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $invoice['customer']['id'], array(
                'stripe_customer_id' => $customer->id,
                ), 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.455', 'msg'=>'Unable to update customer account', 'err'=>$rc['err']));
            }
            $invoice['customer']['stripe_customer_id'] = $customer->id;
        }

        //
        // Create the payment intent
        //
        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => intval($invoice['balance_amount'] * 100),
                'currency' => $currency,
                'customer' => $invoice['customer']['stripe_customer_id'],
                'receipt_email' => $receipt_email,
                'automatic_payment_methods' => ['enabled' => true],
                'description' => "Invoice #{$invoice['invoice_number']} Payment",
                // FIXME: Only use when setting up recurring payments (donations or memberships)
                //'setup_future_usage' => 'off_session', 
                'metadata' => [
                    'invoice_number' => $invoice['invoice_number'],
                    ],
                ]);
        } catch(Exception $e) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.439', 'msg'=>$e->getMessage()));
        }

        //
        // Add the transaction to the invoice
        //
        $dt = new DateTime('now', new DateTimezone('UTC'));
        $transaction = array(
            'invoice_id' => $invoice['id'],
            'status' => 20, // Processing
            'transaction_type' => 20,
            'transaction_date' => $dt->format('Y-m-d H:i:s'),
            'source' => 30,
            'customer_amount' => $invoice['balance_amount'],
            'transaction_fees' => 0,
            'tenant_amount' => $invoice['balance_amount'],
            'user_id' => -2, // Web User
            'notes' => '',
            'gateway' => 30,
            'gateway_token' => $paymentIntent->id,
            'gateway_status' => 'pending',
            'gateway_response' => '', // Leave response blank, not needed
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.transaction', $transaction, 0x07);
        if( $rc['stat'] != 'ok' ) {
            try {
                $paymentIntent = $stripe->paymentIntents->cancel($request['args']['intent_id'], []);
            } catch(Exception $e) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.457', 'msg'=>'Error occured setting up payment, please try again or contact us for help'));
            }
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.458', 'msg'=>'Error occured setting up payment, please try again or contact us for help'));
        }
            
        return array('stat'=>'ok', 'payment_secret'=>$paymentIntent->client_secret, 'intent_id'=>$paymentIntent->id, 'button_text'=>$button_text);
    }
    
    return array('stat'=>'ok');
}
?>
