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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.172', 'msg'=>'Internal Error'));
    }
    if( !isset($_SESSION['paypal_payer_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.173', 'msg'=>'Internal Error'));
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.174', 'msg'=>'Paypal processing not configured'));
    }
    $paypal_settings = $rc['settings'];

    if( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'live' ) {
        $paypal_endpoint = "https://api-3t.paypal.com/nvp";
        $paypal_redirect_url = "https://www.paypal.com/webscr?cmd=_express-checkout";
    } elseif( isset($paypal_settings['paypal-ec-site']) && $paypal_settings['paypal-ec-site'] == 'sandbox' ) {
        $paypal_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
        $paypal_redirect_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.175', 'msg'=>'Paypal processing not configured'));
    }

    if( !isset($paypal_settings['paypal-ec-clientid']) || $paypal_settings['paypal-ec-clientid'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.176', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-password']) || $paypal_settings['paypal-ec-password'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.177', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($paypal_settings['paypal-ec-signature']) || $paypal_settings['paypal-ec-signature'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.178', 'msg'=>'Paypal processing not configured'));
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.179', 'msg'=>'Error processing request: ' . curl_error($ch)));
    } else {
        curl_close($ch);
    }
    if( strtolower($nvpResArray['ACK']) == 'success' || strtolower($nvpResArray['ACK']) == 'successwithwarning' ) {
        //
        // Add a transaction to the invoice
        //
        if( isset($args['invoice_id']) ) {
            $dt = new DateTime('now', new DateTimeZone('UTC'));
            $transaction_args = array(
                'invoice_id'=>$args['invoice_id'],
                'status'=>40,
                'transaction_type'=>20,
                'transaction_date'=>$dt->format('Y-m-d H:i:s'),
                'source'=>'10',
                'customer_amount'=>$nvpResArray['PAYMENTINFO_0_AMT'],
                'transaction_fees'=>$nvpResArray['PAYMENTINFO_0_FEEAMT'],
                'business_amount'=>BCSUB($nvpResArray['PAYMENTINFO_0_AMT'], $nvpResArray['PAYMENTINFO_0_FEEAMT'], 4),
                'user_id'=>0,
                'notes'=>'',
                'gateway'=>10,
                'gateway_token'=>$nvpResArray['TOKEN'],
                'gateway_status'=>$nvpResArray['ACK'],
                'gateway_response'=>serialize($nvpResArray),
                );
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.transaction', $transaction_args);
            if( $rc['stat'] != 'ok' ) {
                error_log("PAYPAL-ERR: Unable to record transaction: " . print_r($rc['err'], true));
            }
        }

        return array('stat'=>'ok');
    } 

    error_log("PAYPAL-ACK: " . urldecode($nvpResArray['ACK']));
    
    error_log("PAYPAL-ERR: " . urldecode($nvpResArray['L_ERRORCODE0']) . '-' . urldecode($nvpResArray['L_LONGMESSAGE0']));
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.180', 'msg'=>'Oops, we seem to have an error. Please try again or contact us for help. '));
}
?>
