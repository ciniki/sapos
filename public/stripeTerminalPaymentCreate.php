<?php
//
// Description
// -----------
// This method will start a new payment intent for stripe terminal.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_stripeTerminalPaymentCreate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'),
        'customer_amount'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'number', 'name'=>'Amount'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    if( $args['customer_amount'] < 0.5 ) {
        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.sapos.419', 'msg'=>'The amount must be at least $0.50'));
    }

    //  
    // Check permissions
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.stripeTerminalPaymentCreate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.391', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Setup defaults
    //
    $currency = 'cad';

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']['intl-default-currency']) && strtolower($rc['settings']['intl-default-currency']) == 'usd' ) {
        $currency = 'usd';
    }
    
    //
    // Verify stripe terminal is setup
    //
    if( !isset($settings['stripe-terminal']) || $settings['stripe-terminal'] != 'wisepose'
        || !isset($settings['stripe-pk']) || $settings['stripe-pk'] == '' 
        || !isset($settings['stripe-sk']) || $settings['stripe-sk'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.392', 'msg'=>'Stripe Terminal not setup'));
    }

    //
    // Load the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    //
    // Get the customer email to send receipt to
    //
    $email = null;
    if( isset($invoice['customer']['emails'][0]['email']['address']) ) {
        $email = $invoice['customer']['emails'][0]['email']['address'];
    }

    //
    // Load stripe
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev14/init.php');

    try {
        $stripe = new \Stripe\StripeClient([
            'api_key' => $settings['stripe-sk'],
            'stripe_version' => '2024-04-10',
            ]);
    } catch(Exception $e) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.464', 'msg'=>$e->getMessage(), 'err'=>$rc['err']));
    }

    //
    // Check if a customer has been specified and if they need to be created in stripe
    //
    if( isset($invoice['customer']['stripe_customer_id']) && $invoice['customer']['stripe_customer_id'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'stripeCustomerCreate');
        $rc = ciniki_sapos_stripeCustomerCreate($ciniki, $args['tnid'], [
            'customer_id' => $invoice['customer']['id'],
            'stripe' => $stripe,
            ]);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.475', 'msg'=>'Unable to initialize customer', 'err'=>$rc['err']));
        }

        $invoice['customer']['stripe_customer_id'] = $rc['stripe_customer_id'];
    }

    //
    // Build the arguments for the payment intent
    //
    $intent_args = [
        'amount' => intval($args['customer_amount']*100),
        'currency' => $currency,
        'capture_method' => 'manual',
//        'automatic_payment_methods' => ['enabled' => true],
//        'setup_future_usage' => 'off_session',
        'description' => "Invoice #{$invoice['invoice_number']} Payment",
        'metadata' => [
            'invoice_number' => $invoice['invoice_number'],
            ],
        ];

    //
    // Only interac supported in canada
    //
    if( $currency == 'cad' ) {
        $intent_args['payment_method_types'] = ['card_present', 'interac_present'];
    } else {
        $intent_args['payment_method_types'] = ['card_present'];
    }

    if( $email != null && $email != '' ) {
        $intent_args['receipt_email'] = $email;
    } 
    if( isset($invoice['customer']['stripe_customer_id']) && $invoice['customer']['stripe_customer_id'] != '' ) {
        $intent_args['customer'] = $invoice['customer']['stripe_customer_id'];
    }

    //
    // Create the intent
    //
    try {
        $intent = $stripe->paymentIntents->create($intent_args);
    } catch(Exception $e) {
        error_log("API-STRIPE [{$invoice['id']}]: " . $e->getMessage());
        //
        // Check if customer does not exist in stripe (should not happen!)
        //
        if( isset($e->getError()->code) && isset($e->getError()->param) && $e->getError()->code == 'resource_missing' && $e->getError()->param == 'customer' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'stripeCustomerCreate');
            $rc = ciniki_sapos_stripeCustomerCreate($ciniki, $args['tnid'], [
                'customer_id' => $invoice['customer']['id'],
                'stripe' => $stripe,
                ]);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.476', 'msg'=>'Unable to reset customer record', 'err'=>$rc['err']));
            }
            $invoice['customer']['stripe_customer_id'] = $rc['stripe_customer_id'];
            
            //
            // Try the intent again
            //
            $intent_args['customer'] = $invoice['customer']['stripe_customer_id'];
            try {
                $intent = $stripe->paymentIntents->create($intent_args);
            } catch(Exception $e) {
                error_log("API-STRIPE [{$invoice['id']}]: " . $e->getMessage());
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.477', 'msg'=>$e->getMessage()));
            }
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.467', 'msg'=>$e->getMessage()));
        }
    }

    //
    // Create the connection token for stripe terminal
    //
    try {
        $connection_token = $stripe->terminal->connectionTokens->create([]);
    } catch(Exception $e) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.468', 'msg'=>$e->getMessage()));
    } 

    return array('stat'=>'ok', 'connection_token'=>$connection_token->secret, 'payment_secret'=>$intent->client_secret);
}
?>
