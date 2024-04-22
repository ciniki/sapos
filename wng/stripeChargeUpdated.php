<?php
//
// Description
// -----------
// This function will mark the transaction succeeded.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_wng_stripeChargeUpdated(&$ciniki, $tnid, &$request, $args) {

    //
    // Lookup the gateway token
    //
    if( isset($args['gateway_token']) && $args['gateway_token'] != '' 
        && isset($args['balance_transaction']) && $args['balance_transaction'] != '' 
        ) {
        //
        // Open Stripe
        //
        if( isset($args['stripe']) ) {
            $stripe = $args['stripe'];
        } else {
            require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev14/init.php');

            $stripe = new \Stripe\StripeClient([
                'api_key' => $request['site']['settings']['stripe-sk'],
                'stripe_version' => '2024-04-10',
                ]);
        }

        //
        // Load the stripe balance transaction
        //
        try {
            $txn = $stripe->balanceTransactions->retrieve($args['balance_transaction'], []);
        } catch(Exception $e) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.465', 'msg'=>$e->getMessage()));
        }

        //
        // Load the ciniki transaction for the gateway_token (payment_intent)
        //
        $strsql = "SELECT transactions.id, "
            . "transactions.uuid, "
            . "transactions.invoice_id, "
            . "transactions.status, "
            . "transactions.customer_amount, "
            . "transactions.transaction_fees, "
            . "transactions.tenant_amount, "
            . "transactions.gateway_response "
            . "FROM ciniki_sapos_transactions AS transactions "
            . "WHERE transactions.gateway_token = '" . ciniki_core_dbQuote($ciniki, $args['gateway_token']) . "' "
            . "AND transactions.gateway = 30 "
            . "AND transactions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'transaction');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.466', 'msg'=>'Unable to load transaction', 'err'=>$rc['err']));
        }
        if( isset($rc['transaction']) ) {
            $transaction = $rc['transaction'];

            $update_args = array();
            if( isset($txn->fee) && $transaction['transaction_fees'] != ($txn->fee/100) ) {
                $update_args['transaction_fees'] = ($txn->fee/100);
                $update_args['tenant_amount'] = $transaction['customer_amount'] - $update_args['transaction_fees'];
            }
            $serialized_txn = serialize($txn);
            if( $transaction['gateway_response'] != $serialized_txn ) {
                $update_args['gateway_response'] = $serialized_txn;
            }
           
            if( count($update_args) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.transaction', $transaction['id'], $update_args, 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.462', 'msg'=>'Unable to update the transaction', 'err'=>$rc['err']));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
