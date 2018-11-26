<?php
//
// Description
// -----------
// Merge the mail from secondary_customer_id into primary_customer_id
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_hooks_customerMerge($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    if( !isset($args['primary_customer_id']) || $args['primary_customer_id'] == '' 
        || !isset($args['secondary_customer_id']) || $args['secondary_customer_id'] == '' ) {
        return array('stat'=>'ok');
    }

    //
    // Keep track of how many items we've updated
    //
    $updated = 0;

    //
    // Get the list of cert customers to update
    //
    $strsql = "SELECT id "
        . "FROM ciniki_sapos_invoices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.258', 'msg'=>'Unable to find invoices', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.259', 'msg'=>'Unable to update invoices.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    return array('stat'=>'ok', 'updated'=>$updated);
}
?>
