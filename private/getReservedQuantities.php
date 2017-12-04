<?php
//
// Description
// ===========
// This function will return the number of units that are on order but have not been 
// picked and packed.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_getReservedQuantities(&$ciniki, $tnid, $object, $object_ids, $invoice_id) {
    //
    // Get the quantity of each object that has been ordered by not shipped.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    $strsql = "SELECT object_id, "
        . "SUM(ciniki_sapos_invoice_items.quantity - ciniki_sapos_invoice_items.shipped_quantity) "
            . "AS quantity "
        . "FROM ciniki_sapos_invoice_items, ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_sapos_invoice_items.object = '" . ciniki_core_dbQuote($ciniki, $object) . "' "
        . "AND ciniki_sapos_invoice_items.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $object_ids) . ") "
        . "AND ciniki_sapos_invoice_items.invoice_id = ciniki_sapos_invoices.id "
        . "AND ciniki_sapos_invoice_items.invoice_id <> '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_sapos_invoices.status < 50 " // any incomplete invoices
        . "GROUP BY object_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'quantities', 'fname'=>'object_id',
            'fields'=>array('object_id', 'quantity_reserved'=>'quantity')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
//  print $strsql;
//  print "<pre>" . print_r($rc, true) . "</pre>";
    if( isset($rc['quantities']) ) {
        return array('stat'=>'ok', 'quantities'=>$rc['quantities']);
    }

    return array('stat'=>'ok', 'quantities'=>array());
}
?>
