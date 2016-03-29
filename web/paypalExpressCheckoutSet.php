<?php
//
// Description
// ===========
// This function is called at the start of a Paypal Express Checkout. When paypal returns the user to the set paypalExpressCheckoutGet should be called.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_web_paypalExpressCheckoutSet(&$ciniki, $business_id, $args) {

    if( !isset($args['amount']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3172', 'msg'=>'No amount specified.'));
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3180', 'msg'=>'Paypal processing not configured'));
	}
	$paypal_settings = $rc['settings'];

    if( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'live' ) {
		$paypal_endpoint = "https://api-3t.paypal.com/nvp";
        $paypal_redirect_url = "https://www.paypal.com/webscr?cmd=_express-checkout";
    } elseif( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'sandbox' ) {
		$paypal_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
        $paypal_redirect_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout";
    } else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3173', 'msg'=>'Paypal processing not configured'));
	}

    if( !isset($paypal_settings['paypal-ec-clientid']) || $paypal_settings['paypal-ec-clientid'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3182', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-password']) || $paypal_settings['paypal-ec-password'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3183', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-signature']) || $paypal_settings['paypal-ec-signature'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3184', 'msg'=>'Paypal processing not configured'));
    }

    $paypal_clientid = $paypal_settings['paypal-ec-clientid'];
    $paypal_password = $paypal_settings['paypal-ec-password'];
    $paypal_signature = $paypal_settings['paypal-ec-signature'];


	// If an address is supplied, fill it in
/*	if( $args['address1'] != '' 
		&& $args['city'] != '' 
		&& $args['state'] != '' 
		&& $args['postal_code'] != '' 
		&& $args['country_code'] != '' 
		) {
		$paypal_transaction['payer']['funding_instruments'][0]['credit_card']['billing_address'] = array(
			'line1'=>$args['address1'],
			'line2'=>(isset($args['address2'])?$args['address2']:''),
			'city'=>$args['city'],
			'state'=>$args['state'],
			'postal_code'=>$args['postal_code'],
			'country_code'=>$args['country_code'],
			'phone'=>(isset($args['phone'])?$args['phone']:''),
		);
	} */

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

    $nvpreq="METHOD=SetExpressCheckout"
        . "&VERSION=93"
        . "&PWD=" . $paypal_password 
        . "&USER=" . $paypal_clientid
        . "&SIGNATURE=" . $paypal_signature
        . "&PAYMENTREQUEST_0_AMT=" . urlencode(sprintf("%.02f", $args['amount']))
        . "&PAYMENTREQUEST_0_PAYMENTACTION=" . urlencode($args['type'])
        . "&RETURNURL=" . urlencode($args['returnurl'])
        . "&CANCELURL=" . urlencode($args['cancelurl'])
        . "&PAYMENTREQUEST_0_CURRENCYCODE=" . urlencode($args['currency'])
        . "&NOSHIPPING=1" // All shipping information is handled in Ciniki
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3178', 'msg'=>'Error processing request: ' . curl_error($ch)));
    } else {
        curl_close($ch);
    }

    if( !isset($nvpResArray['ACK']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3164', 'msg'=>"There is a problem currently with Paypal, please try again later."));
    }

    if( strtolower($nvpResArray['ACK']) == 'success' || strtolower($nvpResArray['ACK']) == 'successwithwarning' ) {
        $paypal_token = urldecode($nvpResArray['TOKEN']);
        $_SESSION['paypal_token'] = $paypal_token;

        //
        // Redirect user to paypal
        //
        header("Location: " . $paypal_redirect_url . '&token=' . $paypal_token);
        exit;
    } 

    error_log("PAYPAL-ACK: " . urldecode($nvpResArray['ACK']));
    
    error_log("PAYPAL-ERR: " . urldecode($nvpResArray['L_ERRORCODE0']) . '-' . urldecode($nvpResArray['L_LONGMESSAGE0']));
	return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3179', 'msg'=>'Oops, we seem to have an error. Please try again or contact us for help. '));
}
?>
