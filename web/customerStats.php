<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_customerStats($ciniki, $settings, $tnid, $customer_id) {

    $stats = array();

    $strsql = "SELECT "
        . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS typestatus, "
        . "COUNT(id) "
        . "FROM ciniki_sapos_invoices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "GROUP BY invoice_type, status "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'stats');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $stats['invoices'] = array('typestatus'=>$rc['stats']);

    return array('stat'=>'ok', 'stats'=>$stats);
}
?>
