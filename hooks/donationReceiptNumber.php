<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_hooks_donationReceiptNumber(&$ciniki, $tnid, $args) {

    //
    // Get the next donation receipt number
    //
    $strsql = "SELECT detail_value "
        . "FROM ciniki_sapos_settings "
        . "WHERE detail_key = 'donation-receipt-next-number' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.388', 'msg'=>'Unable to load donation number', 'err'=>$rc['err']));
    }
    $receipt_number = isset($rc['item']['detail_value']) ? $rc['item']['detail_value'] : 1;
   
    //
    // Check invoices to see if higher number
    //
    $strsql = "SELECT MAX(CAST(receipt_number AS UNSIGNED)) AS max_num "
        . "FROM ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.225', 'msg'=>'Unable to find next available receipt number', 'err'=>$rc['err']));
    }
    if( isset($rc['num']['max_num']) && ($rc['num']['max_num']+1) > $receipt_number ) {
        $receipt_number = $rc['num']['max_num'] + 1;
    }

    //
    // Update settings with next receipt number
    //
    $next_receipt_number = $receipt_number + 1;
    $strsql = "INSERT INTO ciniki_sapos_settings (tnid, detail_key, detail_value, date_added, last_updated) "
        . "VALUES ('" . ciniki_core_dbQuote($ciniki, $tnid) . "'"
        . ", 'donation-receipt-next-number'"
        . ", '" . ciniki_core_dbQuote($ciniki, $next_receipt_number) . "'"
        . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
        . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $next_receipt_number) . "' "
        . ", last_updated = UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', $tnid, 
        2, 'ciniki_sapos_settings', 'donation-receipt-next-number', 'detail_value', $next_receipt_number);

    return array('stat'=>'ok', 'receipt_number'=>$receipt_number);
}
?>
