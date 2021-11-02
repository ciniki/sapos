<?php
//
// Description
// -----------
// This method will complete (capture) the payment intent started by stripeTerminalPaymentIntent
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_stripeTerminalPaymentRefund(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'transaction_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Transaction'), 
//        'payment_intent'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'json', 'name'=>'PaymentIntent'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'interac'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Interac'), 
        'result'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'json', 'name'=>'Refund Result'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Check permissions
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.stripeTerminalPaymentRefund'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.394', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Setup defaults
    //
    $currency = 'cad';

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']['intl-default-currency']) && strtolower($rc['settings']['intl-default-currency']) == 'usd' ) {
        $currency = 'usd';
    }

    //
    // Verify stripe terminal is setup
    //
    if( !isset($settings['stripe-terminal']) || $settings['stripe-terminal'] != 'wisepose'
        || !isset($settings['stripe-pk']) || $settings['stripe-pk'] == '' 
        || !isset($settings['stripe-sk']) || $settings['stripe-sk'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.395', 'msg'=>'Stripe Terminal not setup'));
    }

    //
    // Load the transaction
    //
    $strsql = "SELECT id, "
        . "invoice_id, "
        . "status, "
        . "transaction_type, "
        . "transaction_date, "
        . "source, "
        . "customer_amount, "
        . "transaction_fees, "
        . "tenant_amount, "
        . "gateway, "
        . "gateway_token, "
        . "gateway_status, "
        . "gateway_response, "
        . "notes "
        . "FROM ciniki_sapos_transactions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['transaction_id']) . "' "
        . "ORDER BY transaction_date ASC "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'transaction');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.399', 'msg'=>'Unable to load transaction', 'err'=>$rc['err']));
    }
    if( !isset($rc['transaction']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.400', 'msg'=>'Unable to find transaction'));
    }
    $transaction = $rc['transaction'];
    
    if( $transaction['gateway'] != 30 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.397', 'msg'=>'Transaction not valid for refunds'));
    }
    if( $transaction['gateway_token'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.398', 'msg'=>'Transaction not valid for refunds'));
    }

    //
    // Load the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $transaction['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    //
    // Check if refund was done via interac in person
    //
    if( isset($args['interac']) && $args['interac'] == 'refunded' && isset($args['result']) ) {
        $refund = $args['result'];
        error_log(print_r($refund,true));
    } else {
        //
        // Load stripe
        //
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev7/init.php');
       
        \Stripe\Stripe::setApiKey($settings['stripe-sk']);

        try {
            $intent = \Stripe\PaymentIntent::retrieve($transaction['gateway_token']);
            file_put_contents("/tmp/intent.json", print_r($intent, true));
        } catch( Error $e ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.396', 'msg'=>'Unable to complete transaction: ' . $e->getMessage()));
        }

        if( isset($intent['charges']['data'][0]['payment_method_details']) ) {
            $charge = $intent['charges']['data'][0];
            if( isset($charge['refunded']) && $charge['refunded'] == true ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.400', 'msg'=>'A refund has already issued for this transaction'));
            }
            if( !isset($charge['amount_captured']) || $charge['amount_captured'] <= 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.401', 'msg'=>'This transaction was never completed, no refund issued.'));
            }
            if( !isset($charge['payment_intent']) || $charge['payment_intent'] == '' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.402', 'msg'=>'This was not an in person sale and cannot be refunded.'));
            }

            //
            // Refund a credit card
            //
            if( isset($charge['payment_method_details']['card_present']) ) {
                try {
                    $refund = \Stripe\Refund::create([
                        // Refund full amount
                        'payment_intent' => $charge['payment_intent'],
                        ]);
                } catch( Error $e ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.403', 'msg'=>'Unable to issue refund: ' . $e->getMessage()));
                }

            }
            //
            // Refund a interac card
            //
            elseif( isset($charge['payment_method_details']['interac_present']) ) {
                error_log('refund interac');
                $connection_token = \Stripe\Terminal\ConnectionToken::create();
                return array('stat'=>'interacrefund', 'connection_token'=>$connection_token->secret, 'charge_id'=>$charge['id'], 'amount'=>$charge['amount_captured'], 'err'=>array('code'=>'ciniki.sapos.404', 'msg'=>'Interac Refund required'));
            }
        }
    }


    //
    // Create the refund transaction
    //
    if( isset($refund) ) {
        //
        // Successful Refund
        //
        $dt = new DateTime('now', new DateTimezone('UTC'));
        $args['transaction_date'] = $dt->format('Y-m-d H:i:s');
        $args['invoice_id'] = $transaction['invoice_id'];
        $args['status'] = 10;
        $args['transaction_type'] = 60;
        $args['source'] = 30;
        $args['user_id'] = $ciniki['session']['user']['id'];
        $args['transaction_fees'] = 0;
        $args['customer_amount'] = ($refund['amount']/100);
        // FIXME: Add or capture transaction fees, the following line doesn't work for terminal
        // $args['transaction_fees'] = round(bcadd(bcmul($args['customer_amount'], 0.029, 6), 0.30, 6), 2);
        $args['tenant_amount'] = ($args['customer_amount'] - $args['transaction_fees']);
        $args['gateway'] = 30;
        $args['gateway_token'] = $refund['id'];
        $args['gateway_status'] = isset($refund['status']) ? $refund['status'] : '';
        $args['gateway_response'] = serialize($refund);

        //
        // Start transaction
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   

        //
        // Add the transaction
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.transaction', $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        $transaction_id = $rc['id'];

        //
        // Update the invoice status
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
        $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $args['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }

        //
        // FIXME: Check if callback hooks to item modules
        //


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

        return array('stat'=>'ok', 'id'=>$transaction_id);
    }


    return array('stat'=>'ok');
}
?>
