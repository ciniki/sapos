<?php
//
// Description
// ===========
// This function is the final call in express checkout to collect the payment.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_web_paypalExpressCheckoutDo(&$ciniki, $business_id, $args) {

    if( !isset($_SESSION['paypal_token']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3199', 'msg'=>'Internal Error'));
    }
    if( !isset($_SESSION['paypal_payer_id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3198', 'msg'=>'Internal Error'));
    }

	//
	// Load paypal settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'business_id', $business_id, 'ciniki.sapos', 'settings', 'paypal');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['settings']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3200', 'msg'=>'Paypal processing not configured'));
	}
	$paypal_settings = $rc['settings'];

    if( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'live' ) {
		$paypal_endpoint = "https://api-3t.paypal.com/nvp";
        $paypal_redirect_url = "https://www.paypal.com/webscr?cmd=_express-checkout";
    } elseif( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'sandbox' ) {
		$paypal_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
        $paypal_redirect_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout";
    } else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3201', 'msg'=>'Paypal processing not configured'));
	}

    if( !isset($paypal_settings['paypal-ec-clientid']) || $paypal_settings['paypal-ec-clientid'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3202', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-password']) || $paypal_settings['paypal-ec-password'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3203', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-signature']) || $paypal_settings['paypal-ec-signature'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3204', 'msg'=>'Paypal processing not configured'));
    }

    $paypal_clientid = $paypal_settings['paypal-ec-clientid'];
    $paypal_password = $paypal_settings['paypal-ec-password'];
    $paypal_signature = $paypal_settings['paypal-ec-signature'];

    //
    // Submit the Express checkout start
    //
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $paypal_endpoint);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POST, 1);

    $nvpreq="METHOD=DoExpressCheckoutPayment"
        . "&VERSION=93"
        . "&PWD=" . $paypal_password 
        . "&USER=" . $paypal_clientid
        . "&SIGNATURE=" . $paypal_signature
        . "&TOKEN=" . urlencode($_SESSION['paypal_token'])
        . "&PAYERID=" . urlencode($_SESSION['paypal_payer_id'])
        . "&PAYMENTREQUEST_0_PAYMENTACTION=" . urlencode($args['type'])
        . "&PAYMENTREQUEST_0_AMT=" . urlencode(sprintf("%.02f", $args['amount']))
        . "&PAYMENTREQUEST_0_CURRENCYCODE=" . urlencode($args['currency'])
        . "&IPADDRESS=" . urlencode($_SERVER['SERVER_NAME'])
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3171', 'msg'=>'Error processing request: ' . curl_error($ch)));
    } else {
        curl_close($ch);
    }
    if( strtolower($nvpResArray['ACK']) == 'success' || strtolower($nvpResArray['ACK']) == 'successwithwarning' ) {
        return array('stat'=>'ok');
    } 

    error_log("PAYPAL-ACK: " . urldecode($nvpResArray['ACK']));
    
    error_log("PAYPAL-ERR: " . urldecode($nvpResArray['L_ERRORCODE0']) . '-' . urldecode($nvpResArray['L_LONGMESSAGE0']));
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3181', 'msg'=>'Oops, we seem to have an error. Please try again or contact us for help. '));
}
?>