<?php
//
// Description
// ===========
// This function should be called when the user is redirected back after paypalExpressCheckoutSet
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_wng_paypalExpressCheckoutGet(&$ciniki, $tnid, $request, $args) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    if( isset($settings['paypal-ec-site']) && $settings['paypal-ec-site'] == 'live' ) {
        $paypal_endpoint = "https://api-3t.paypal.com/nvp";
        $paypal_redirect_url = "https://www.paypal.com/webscr?cmd=_express-checkout";
    } elseif( isset($settings['paypal-ec-site']) && $settings['paypal-ec-site'] == 'sandbox' ) {
        $paypal_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
        $paypal_redirect_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.344', 'msg'=>'Paypal processing not configured'));
    }

    if( !isset($settings['paypal-ec-clientid']) || $settings['paypal-ec-clientid'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.345', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($settings['paypal-ec-password']) || $settings['paypal-ec-password'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.346', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($settings['paypal-ec-signature']) || $settings['paypal-ec-signature'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.347', 'msg'=>'Paypal processing not configured'));
    }

    $paypal_clientid = $settings['paypal-ec-clientid'];
    $paypal_password = $settings['paypal-ec-password'];
    $paypal_signature = $settings['paypal-ec-signature'];

    //
    // Get the paypal token to start the express checkout process
    //
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $paypal_endpoint);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POST, 1);

    $nvpreq="METHOD=GetExpressCheckoutDetails"
        . "&VERSION=93"
        . "&PWD=" . $paypal_password 
        . "&USER=" . $paypal_clientid
        . "&SIGNATURE=" . $paypal_signature
        . "&TOKEN=" . urlencode($args['token'])
        . "";
   
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    // Execute
    $response = curl_exec($ch);

    // Parse response
    $nvpResArray = array();
    $kvs = explode('&', $response);
    foreach($kvs as $kv) {
        list($key, $value) = explode('=', $kv);
        $nvpResArray[urldecode($key)] = urldecode($value);
    }

    if( curl_errno($ch)) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.352', 'msg'=>'Error processing request: ' . curl_error($ch)));
    } else {
        curl_close($ch);
    }
    if( strtolower($nvpResArray['ACK']) == 'success' || strtolower($nvpResArray['ACK']) == 'successwithwarning' ) {
        $paypal_payer_id = urldecode($nvpResArray['PAYERID']);
        $_SESSION['paypal_payer_id'] = $paypal_payer_id;
        return array('stat'=>'ok');
    } 

    error_log("PAYPAL-ERR: " . urldecode($nvpResArray['L_ERROCODE0']) . '-' . urldecode($nvpResArray['L_SHORTMESSAGE0']));

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.353', 'msg'=>'Oops, we seem to have an error. Please try again or contact us for help. '));
}
?>
