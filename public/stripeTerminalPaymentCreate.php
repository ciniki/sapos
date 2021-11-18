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
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev7/init.php');
   
    \Stripe\Stripe::setApiKey($settings['stripe-sk']);

    $connection_token = \Stripe\Terminal\ConnectionToken::create();

    if( $currency == 'cad' ) {
        $intent = \Stripe\PaymentIntent::create([
            'amount' => intval($args['customer_amount']*100),
            'currency' => 'cad',
            'payment_method_types' => ['card_present', 'interac_present'],
            'capture_method' => 'manual',
            'receipt_email' => $email,
            'metadata' => [
                'invoice_number' => $invoice['invoice_number'],
                ],
            ]);
    } else {
        $intent = \Stripe\PaymentIntent::create([
            'amount' => intval($args['customer_amount']*100),
            'currency' => $currency,
            'payment_method_types' => ['card_present'],
            'capture_method' => 'manual',
            'receipt_email' => $email,
            'metadata' => [
                'invoice_number' => $invoice['invoice_number'],
                ],
            ]);
    }

    return array('stat'=>'ok', 'connection_token'=>$connection_token->secret, 'payment_secret'=>$intent->client_secret);
}
?>
