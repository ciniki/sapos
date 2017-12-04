<?php
//
// Description
// ===========
// This method will process a paypal payment through the paypal REST API.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_paypalProcess(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Invoice'), 
        'type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Card Type'),
        'number'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Card Number'),
        'expire_month'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Card Expire Month'),
        'expire_year'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Card Expire Year'),
        'cvv2'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Card Security Code'),
        'total'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Total Amount'),
        'currency'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Currency'),
        'system'=>array('required'=>'no', 'blank'=>'no', 'default'=>'test', 'name'=>'Live or Test system'),
        'first_name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'First Name'),
        'last_name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Last Name'),
        'line1'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address Line 1'),
        'line2'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address Line 2'),
        'city'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'City'),
        'state'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Province/State'),
        'postal_code'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Postal/Zip'),
        'country_code'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Country'),
        'phone'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Phone'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.paypalProcess'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load paypal settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', 'paypal');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.89', 'msg'=>'Paypal processing not configured'));
    }
    $paypal_settings = $rc['settings'];

    if( $args['system'] == 'test' ) {
        if( !isset($paypal_settings['paypal-test-endpoint']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.90', 'msg'=>'Paypal processing not configured'));
        }
        if( !isset($paypal_settings['paypal-test-clientid']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.91', 'msg'=>'Paypal processing not configured'));
        }
        if( !isset($paypal_settings['paypal-test-secret']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.92', 'msg'=>'Paypal processing not configured'));
        }
        $paypal_endpoint = $paypal_settings['paypal-test-endpoint'];
        $paypal_clientid = $paypal_settings['paypal-test-clientid'];
        $paypal_secret = $paypal_settings['paypal-test-secret'];
    }
    elseif( $args['system'] == 'live' ) {
        if( !isset($paypal_settings['paypal-live-endpoint']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.93', 'msg'=>'Paypal processing not configured'));
        }
        if( !isset($paypal_settings['paypal-live-clientid']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.94', 'msg'=>'Paypal processing not configured'));
        }
        if( !isset($paypal_settings['paypal-live-secret']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.95', 'msg'=>'Paypal processing not configured'));
        }
        $paypal_endpoint = $paypal_settings['paypal-live-endpoint'];
        $paypal_clientid = $paypal_settings['paypal-live-clientid'];
        $paypal_secret = $paypal_settings['paypal-live-secret'];
    }
    else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.96', 'msg'=>'Paypal processing not configured'));
    }

    //
    // Prepare the payment
    //
    $paypal_transaction = array('intent'=>'sale',
        'payer'=>array(
            'payment_method'=>'credit_card',
            'funding_instruments'=>array(array(
                'credit_card'=>array(
                    'number'=>$args['number'],
                    'type'=>$args['type'],
                    'expire_month'=>$args['expire_month'],
                    'expire_year'=>$args['expire_year'],
                    'cvv2'=>$args['cvv2'],
                    'first_name'=>$args['first_name'],
                    'last_name'=>$args['last_name'],
                ),
            )),
        ),
        'transactions'=>array(array(
            'amount'=>array(
                'currency'=>$args['currency'],
                'total'=>$args['total'],
            ),
        )),
    );
    // If an address is supplied, fill it in
    if( $args['line1'] != '' 
        && $args['city'] != '' 
        && $args['state'] != '' 
        && $args['postal_code'] != '' 
        && $args['country_code'] != '' 
        ) {
        $paypal_transaction['payer']['funding_instruments'][0]['credit_card']['billing_address'] = array(
            'line1'=>$args['line1'],
            'line2'=>$args['line2'],
            'city'=>$args['city'],
            'state'=>$args['state'],
            'postal_code'=>$args['postal_code'],
            'country_code'=>$args['country_code'],
            'phone'=>$args['phone'],
        );
    }

    //
    // Authenticate with paypal
    //
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://" . $paypal_endpoint . "/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $paypal_clientid . ":" . $paypal_secret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

    $result = curl_exec($ch);
    if( empty($result) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.97', 'msg'=>'Unable to authenticate with paypal, please check your settings and try again.'));
    } 
    $json = json_decode($result, true);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if( $http_status != '200' ) {
        error_log("Paypal Auth Error[$http_status]: " . print_r($json, true));
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.98', 'msg'=>'Paypal Error: ' . $json['name'] . ' - ' . $json['message']));
    }
    $paypal_access_token = $json['access_token'];
    $paypal_token_type = $json['token_type'];
    $paypal_app_id = $json['app_id'];
    curl_close($ch);
    
    //
    // Process the payment
    //
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://" . $paypal_endpoint . "/v1/payments/payment");
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $post_data = json_encode($paypal_transaction);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer $paypal_access_token", 
        "Content-length: " . strlen($post_data))
    );
    $result = curl_exec($ch);
    if( empty($result) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.99', 'msg'=>'Unable to process payment with paypal, please check your settings and try again.'));
    } 
    $paypal_response = json_decode($result, true);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if( $http_status != '200' && $http_status != '201' ) {
        error_log("Paypal Payment Error[$http_status]: " . print_r($paypal_response, true));
        $last_err = null;
        if( isset($paypal_response['details']) ) {
            foreach($paypal_response['details'] as $did => $detail) {
                if( isset($detail['field']) ) {
                    $err = array('code'=>'ciniki.sapos.203', 'msg'=>'Paypal Error: ' . $detail['field'] . ' - ' . $detail['issue']);
                }
                if( $last_err != null ) {
                    $err['err'] = $last_err;
                }
                $last_err = $err;
            }
        }
        $err = array('code'=>'ciniki.sapos.204', 'msg'=>'Paypal Error: ' . $paypal_response['name'] . ' - ' . $paypal_response['message']);
        if( $last_err != null ) {
            $err['err'] = $last_err;
        } 
        return array('stat'=>'fail', 'err'=>$err);
    }
    error_log('Paypal Log: ' . print_r($paypal_response, true));    
    if( $paypal_response['state'] != 'approved' 
        && $paypal_response['state'] != 'pending' 
        && $paypal_response['state'] != 'created' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.100', 'msg'=>'The transaction was not approved.'));
    }
    curl_close($ch);

    //
    // Start the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Add the transaction
    //
    $transaction = array(
        'invoice_id'=>$args['invoice_id'],
        'transaction_type'=>'20',
        'transaction_date'=>strftime("%Y-%m-%d %H:%M:%S"),
        'source'=>'10',
        'customer_amount'=>$args['total'],
        'transaction_fees'=>0,
        'tenant_amount'=>$args['total'],
        'user_id'=>$ciniki['session']['user']['id'],
        'notes'=>'',
        'gateway'=>'10',
        'gateway_token'=>'',
        'gateway_status'=>'',
        'gateway_response'=>serialize($paypal_response),
    );
    if( isset($paypal_response['id']) ) {
        $transaction['gateway_token'] = $paypal_response['id'];
    }
    if( isset($paypal_response['state']) ) {
        $transaction['gateway_status'] = $paypal_response['state'];
    }
    if( isset($paypal_response['transactions'][0]['amount']['details']['fee']) ) {
        $fee = $paypal_response['transactions'][0]['amount']['details']['fee'];
        $transaction['transaction_fees'] = $fee;
        $transaction['tenant_amount'] = bcsub($transaction['customer_amount'], $fee, 2);
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.transaction', $transaction, 0x04);
    if( $rc['stat'] != 'ok' ) {
        error_log('ERR: paypal transaction completed, but failed to add transaction to database');
        return $rc;
    }

    //
    // If this payment is going against an invoice, record the transaction
    //
    if( $args['invoice_id'] > 0 ) {
        //
        // Update the shipping costs, taxes, and total
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
        $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $args['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok');
}
?>
