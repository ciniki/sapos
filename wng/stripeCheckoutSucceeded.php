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
function ciniki_sapos_wng_stripeCheckoutSucceeded(&$ciniki, $tnid, &$request, $args) {

    //
    // Lookup the gateway token
    //
    if( isset($args['gateway_token']) && $args['gateway_token'] != '' ) {
        $strsql = "SELECT transactions.id, "
            . "transactions.uuid, "
            . "transactions.invoice_id, "
            . "transactions.status "
            . "FROM ciniki_sapos_transactions AS transactions "
            . "WHERE transactions.gateway_token = '" . ciniki_core_dbQuote($ciniki, $args['gateway_token']) . "' "
            . "AND transactions.gateway = 30 "
            . "AND transactions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'transaction');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.460', 'msg'=>'Unable to load transaction', 'err'=>$rc['err']));
        }
        if( isset($rc['transaction']) ) {
            $transaction = $rc['transaction'];

            $update_args = array();
            if( $transaction['status'] == 20 ) {
                $update_args['status'] = 40;
                $update_args['gateway_status'] = 'succeeded';
            }
           
            if( count($update_args) > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.transaction', $transaction['id'], $update_args, 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.462', 'msg'=>'Unable to update the transaction', 'err'=>$rc['err']));
                }

                //
                // Run an update on the invoice
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
                $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $transaction['invoice_id']);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.463', 'msg'=>'Unable to update invoice', 'err'=>$rc['err']));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
