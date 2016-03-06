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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3180', 'msg'=>'Paypal processing not configured'));
	}
	$paypal_settings = $rc['settings'];

	if( $args['sandbox'] == 'yes' ) {
		if( !isset($paypal_settings['paypal-test-endpoint']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3179', 'msg'=>'Paypal processing not configured'));
		}
		if( !isset($paypal_settings['paypal-test-clientid']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3178', 'msg'=>'Paypal processing not configured'));
		}
		if( !isset($paypal_settings['paypal-test-secret']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3177', 'msg'=>'Paypal processing not configured'));
		}
		$paypal_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
        $paypal_redirect_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout";
		$paypal_clientid = $paypal_settings['paypal-test-clientid'];
		$paypal_secret = $paypal_settings['paypal-test-secret'];
	}
	elseif( $args['system'] == 'live' ) {
		if( !isset($paypal_settings['paypal-live-endpoint']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3176', 'msg'=>'Paypal processing not configured'));
		}
		if( !isset($paypal_settings['paypal-live-clientid']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3175', 'msg'=>'Paypal processing not configured'));
		}
		if( !isset($paypal_settings['paypal-live-secret']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3174', 'msg'=>'Paypal processing not configured'));
		}
		$paypal_endpoint = "https://api-3t.paypal.com/nvp";
        $paypal_redirect_url = "https://www.paypal.com/webscr?cmd=_express-checkout";
		$paypal_clientid = $paypal_settings['paypal-live-clientid'];
		$paypal_secret = $paypal_settings['paypal-live-secret'];
	}
	else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3173', 'msg'=>'Paypal processing not configured'));
	}

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

    $nvpreq="METHOD=SetExpressCheckout"
        . "&VERSION=93"
        . "&PWD=" . $paypal_secret 
        . "&USER=" . $paypal_clientid
        . "&SIGNATURE=" . $paypal_secret
        . "&PAYMENTREQUEST_0_AMT=" . urlencode($args['amount']),
        . "&PAYMENTREQUEST_0_PAYMENTACTION=" . urlencode($args['type']),
        . "&RETURNURL=" . urlencode($args['returnurl']),
        . "&CANCELURL=" . urlencode($args['cancelurl']),
        . "&PAYMENTREQUEST_0_CURRENCYCODE=" . urlencode($args['currency']),
        . "";
   
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    // Execute
    $response = curl_exec($ch);

    // Parse response
    $nvpResArray = array();
    while(strlen($response)) {
        $kvs = explode('&', $response);
        foreach($kvs as $kv) {
            list($key, $value) = explode('=', $kv);
            $nvpResArray[urldecode[$key]] = urldecode[$value];
        }
    }

    if( curl_errno($ch)) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3171', 'msg'=>'Error processing request: ' . curl_error($ch)));
    } else {
        curl_close($ch);
    }
    if( $nvpResArray['ACK'] == 'SUCCESS' || $nvpResArray['ACK'] == 'SUCCESSWITHWARNING' ) {
        $paypal_token = $urldecode($nvpResArray['TOKEN']);
        $_SESSION['paypal_token'] = $paypal_token;
        //
        // Redirect user to paypal
        //
        header("Location: " . $paypal_redirect_url . '&token=' . $paypal_token);
        exit;
    } 

    error_log("PAYPAL-ERR: " . urldecode($nvpResArray['L_ERROCODE0']) . '-' . urldecode($nvpResArray['L_SHORTMESSAGE0']));

	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3181', 'msg'=>'Oops, we seem to have an error. Please try again or contact us for help. '));
}
?>
