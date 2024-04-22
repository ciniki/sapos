<?php
//
// Description
// -----------
//
// Returns
// -------
//
function ciniki_sapos_wng_apiInvoiceStripeIntentCancel(&$ciniki, $tnid, &$request) {

    if( isset($request['site']['settings']['stripe-pk']) && $request['site']['settings']['stripe-pk'] != '' 
        && isset($request['site']['settings']['stripe-sk']) && $request['site']['settings']['stripe-sk'] != '' 
        && isset($request['site']['settings']['stripe-version']) && $request['site']['settings']['stripe-version'] == 'elements' 
        ) {

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

        //
        // Cancel the payment intent
        //
        try {
            $paymentIntent = $stripe->paymentIntents->cancel($request['args']['intent_id'], []);
        } catch(Exception $e) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.456', 'msg'=>$e->getMessage()));
        }

        //
        // Cancel the transaction
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'stripeCheckoutCancel');
        $rc = ciniki_sapos_wng_stripeCheckoutCancel($ciniki, $tnid, $request, array(
            'gateway_token' => $request['args']['intent_id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.459', 'msg'=>'Unable to remove transaction', 'err'=>$rc['err']));
        }

        return array('stat'=>'ok');
    }
    
    return array('stat'=>'ok');
}
?>
