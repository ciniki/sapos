<?php
//
// Description
// -----------
// This method will complete (capture) the payment intent started by stripeTerminalPaymentIntent
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_stripeTerminalPaymentCapture(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
//        'payment_intent'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'json', 'name'=>'PaymentIntent'), 
        'payment_intent_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Payment Intent ID'), 
        'payment_amount'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Payment Amount'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Check permissions
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.stripeTerminalPaymentIntent'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.444', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.445', 'msg'=>'Stripe Terminal not setup'));
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
    // Load stripe
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev14/init.php');
   
    $stripe = new \Stripe\StripeClient([
        'api_key' => $settings['stripe-sk'],
        'stripe_version' => '2024-04-10',
        ]);

    try {
        $intent = $stripe->paymentIntents->retrieve($args['payment_intent_id']);
        //
        // If the intent requires capture (interac charges don't require capture)
        //
        if( !isset($intent['status']) || $intent['status'] == 'requires_capture' ) {
            $intent = $intent->capture();
        }
    } catch( Error $e ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.393', 'msg'=>'Unable to complete transaction: ' . $e->getMessage()));
    }

    //
    // Setup the fields needed for a transaction
    //
    $args['status'] = 40;   // Completed
    $args['transaction_type'] = 20;
    $args['source'] = 30;
    if( !isset($args['transaction_date']) || $args['transaction_date'] == '' ) {
        $dt = new DateTime('now', new DateTimezone('UTC'));
        $args['transaction_date'] = $dt->format('Y-m-d H:i:s');
    }

    //
    // Set the user id who created the invoice
    //
    $args['user_id'] = $ciniki['session']['user']['id'];

    //
    // Calculate transaction fees
    //
    $args['customer_amount'] = ($args['payment_amount']/100);
    $args['transaction_fees'] = 0;
    //
    // Transaction Fees are sent via webhook 'charge.captured'
    //
    $args['tenant_amount'] = ($args['customer_amount'] - $args['transaction_fees']);

    $args['gateway'] = 30;
    $args['gateway_token'] = $intent->id;
//    $args['gateway_token'] = $intent['balance_transaction'];
    $args['gateway_status'] = isset($intent->status) ? $intent->status : '';
    $args['gateway_response'] = json_encode($intent);

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.transaction', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    $transaction_id = $rc['id'];

    //
    // Update the invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // FIXME: Check if callback hooks to item modules
    //


    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok', 'id'=>$transaction_id);


    return array('stat'=>'ok');
}
?>
