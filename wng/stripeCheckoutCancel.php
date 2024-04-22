<?php
//
// Description
// -----------
// This function will cancel the transaction in the database for the payment intent
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_wng_stripeCheckoutCancel(&$ciniki, $tnid, &$request, $args) {

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

            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.transaction', $transaction['id'], $transaction['uuid'], 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.461', 'msg'=>'Unable to cancel transaction', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
