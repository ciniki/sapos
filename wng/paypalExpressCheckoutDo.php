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
function ciniki_sapos_wng_paypalExpressCheckoutDo(&$ciniki, $tnid, $request, $args) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    if( !isset($request['session']['paypal_token']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.341', 'msg'=>'Internal Error'));
    }
    if( !isset($request['session']['paypal_payer_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.342', 'msg'=>'Internal Error'));
    }

    if( isset($settings['paypal-ec-site']) && $settings['paypal-ec-site'] == 'live' ) {
        $paypal_endpoint = "https://api-3t.paypal.com/nvp";
        $paypal_redirect_url = "https://www.paypal.com/webscr?cmd=_express-checkout";
    } elseif( isset($settings['paypal-ec-site']) && $settings['paypal-ec-site'] == 'sandbox' ) {
        $paypal_endpoint = "https://api-3t.sandbox.paypal.com/nvp";
        $paypal_redirect_url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.343', 'msg'=>'Paypal processing not configured'));
    }

    if( !isset($settings['paypal-ec-clientid']) || $settings['paypal-ec-clientid'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.351', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($settings['paypal-ec-password']) || $settings['paypal-ec-password'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.358', 'msg'=>'Paypal processing not configured'));
    }
    if( !isset($settings['paypal-ec-signature']) || $settings['paypal-ec-signature'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.363', 'msg'=>'Paypal processing not configured'));
    }

    $paypal_clientid = $settings['paypal-ec-clientid'];
    $paypal_password = $settings['paypal-ec-password'];
    $paypal_signature = $settings['paypal-ec-signature'];

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
        . "&TOKEN=" . urlencode($request['session']['paypal_token'])
        . "&PAYERID=" . urlencode($request['session']['paypal_payer_id'])
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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.348', 'msg'=>'Error processing request: ' . curl_error($ch)));
    } else {
        curl_close($ch);
    }
    if( strtolower($nvpResArray['ACK']) == 'success' || strtolower($nvpResArray['ACK']) == 'successwithwarning' ) {

        //
        // Make sure it wasn't a duplicate request
        //
        if( isset($nvpResArray['L_ERRORCODE0']) && $nvpResArray['L_ERRORCODE0'] == '11607' ) {
            error_log("PAYPAL-ERR: DUP CART SUBMITTED: " . urldecode($nvpResArray['L_ERRORCODE0']) . '-' . urldecode($nvpResArray['L_LONGMESSAGE0']));
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.349', 'msg'=>'Oops, looks like something went wrong. Please contact us for help.'));
        }

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
                'tenant_amount'=>BCSUB($nvpResArray['PAYMENTINFO_0_AMT'], $nvpResArray['PAYMENTINFO_0_FEEAMT'], 4),
                'user_id'=>0,
                'notes'=>'',
                'gateway'=>10,
                'gateway_token'=>$nvpResArray['TOKEN'],
                'gateway_status'=>$nvpResArray['ACK'],
                'gateway_response'=>serialize($nvpResArray),
                );
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.transaction', $transaction_args);
            if( $rc['stat'] != 'ok' ) {
                error_log("PAYPAL-ERR: Unable to record transaction: " . print_r($rc['err'], true));
            }
        }

        return array('stat'=>'ok');
    } 

    error_log("PAYPAL-ACK: " . urldecode($nvpResArray['ACK']));
    
    error_log("PAYPAL-ERR: " . urldecode($nvpResArray['L_ERRORCODE0']) . '-' . urldecode($nvpResArray['L_LONGMESSAGE0']));
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.350', 'msg'=>'Oops, we seem to have an error. Please try again or contact us for help. '));
}
?>
