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
function ciniki_sapos_web_paypalExpressCheckoutGet(&$ciniki, $business_id, $args) {

    //
    // Load paypal settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'business_id', $business_id, 'ciniki.sapos', 'settings', 'paypal');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3192', 'msg'=>'Paypal processing not configured'));
    }
    $paypal_settings = $rc['settings'];

    if( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'live' ) {
        $paypal_endpoint = "https://api-3t.paypal.com/nvp";
        $paypal_redirect_url = "https://www.paypal.com/webscr?cmd=_express-checkout";
    } elseif( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'sandbox' ) {
        $paypal_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
        $paypal_redirect_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout";
    } else {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3185', 'msg'=>'Paypal processing not configured'));
    }

    if( !isset($paypal_settings['paypal-ec-clientid']) || $paypal_settings['paypal-ec-clientid'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3186', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-password']) || $paypal_settings['paypal-ec-password'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3195', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-signature']) || $paypal_settings['paypal-ec-signature'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3196', 'msg'=>'Paypal processing not configured'));
    }

    $paypal_clientid = $paypal_settings['paypal-ec-clientid'];
    $paypal_password = $paypal_settings['paypal-ec-password'];
    $paypal_signature = $paypal_settings['paypal-ec-signature'];

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
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3193', 'msg'=>'Error processing request: ' . curl_error($ch)));
    } else {
        curl_close($ch);
    }
    if( strtolower($nvpResArray['ACK']) == 'success' || strtolower($nvpResArray['ACK']) == 'successwithwarning' ) {
        $paypal_payer_id = urldecode($nvpResArray['PAYERID']);
        $_SESSION['paypal_payer_id'] = $paypal_payer_id;
        return array('stat'=>'ok');
    } 

    error_log("PAYPAL-ERR: " . urldecode($nvpResArray['L_ERROCODE0']) . '-' . urldecode($nvpResArray['L_SHORTMESSAGE0']));

    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3194', 'msg'=>'Oops, we seem to have an error. Please try again or contact us for help. '));
}
?>
