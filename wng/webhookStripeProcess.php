<?php
//
// Description
// -----------
// This function will process webhook for a stripe
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sapos request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_sapos_wng_webhookStripeProcess(&$ciniki, $tnid, &$request) {

    //
    // Check to make sure stripe is enable for 
    //
    if( isset($request['site']['settings']['stripe-pk']) && $request['site']['settings']['stripe-pk'] != '' 
        && isset($request['site']['settings']['stripe-sk']) && $request['site']['settings']['stripe-sk'] != '' 
        && isset($request['site']['settings']['stripe-version']) && $request['site']['settings']['stripe-version'] == 'elements' 
        && isset($request['site']['settings']['stripe-whsec']) && $request['site']['settings']['stripe-whsec'] != '' 
        ) {
        
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev14/init.php');
        $stripe = new \Stripe\StripeClient([
            'api_key' => $request['site']['settings']['stripe-sk'],
            'stripe_version' => '2024-04-10',
            ]); 

        //
        // Output the args passed
        //
        $payload = file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $request['site']['settings']['stripe-whsec']
            );
        } catch(\UnexpectedValueException $e) {
            error_log('STRIPE-WEBHOOK: Invalid payload');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.435', 'msg'=>'Invalid stripe payload'));
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            error_log('STRIPE-WEBHOOK: Invalid signature');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.436', 'msg'=>'Invalid stripe signature'));
        }

        if( isset($ciniki['config']['ciniki.wng']['webhook.stripe.log']) 
            && $ciniki['config']['ciniki.wng']['webhook.stripe.log'] == 1
            ) {
            $dt = new DateTime('now', new DateTimezone('UTC'));
            file_put_contents($ciniki['config']['ciniki.core']['log_dir'] . '/stripe-' . $dt->format('Y-m') . '.log', $dt->format('Y-m-d H:i:s') . " [{$tnid}-{$event->type}] " . json_encode($event) . "\n", FILE_APPEND);
        }

        if( $event->type == 'charge.succeeded' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'stripeCheckoutSucceeded');
            $rc = ciniki_sapos_wng_stripeCheckoutSucceeded($ciniki, $tnid, $request, array(
                'gateway_token' => $event->data->object->payment_intent,
                'balance_transaction' => $event->data->object->balance_transaction,
                'event' => $event,
                'stripe' => $stripe,
                ));
            if( $rc['stat'] != 'ok' ) {
                error_log('STRIPE-WEBHOOK: Unable to succeed transaction');
            }
        }
        elseif( $event->type == 'payment_intent.canceled' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'stripeCheckoutCancel');
            $rc = ciniki_sapos_wng_stripeCheckoutCancel($ciniki, $tnid, $request, array('gateway_token'=>$event->data->object->id));
            if( $rc['stat'] != 'ok' ) {
                error_log('STRIPE-WEBHOOK: Unable to cancel transaction');
            }
        } 
        elseif( $event->type == 'charge.updated' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'stripeChargeUpdated');
            $rc = ciniki_sapos_wng_stripeChargeUpdated($ciniki, $tnid, $request, array(
                'gateway_token'=>$event->data->object->payment_intent,
                'balance_transaction'=>$event->data->object->balance_transaction,
                'stripe' => $stripe,
                ));
            if( $rc['stat'] != 'ok' ) {
                error_log('STRIPE-WEBHOOK: Unable to cancel transaction');
            }
        } 
        else {

        }

        // FIXME: Add handling for delayed processed payments


        return array('stat'=>'ok');
    }



    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sapos.434', 'msg'=>"I'm sorry, the page you requested does not exist."));
}
?>
