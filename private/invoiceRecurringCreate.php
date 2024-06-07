<?php
//
// Description
// -----------
// This function is called from invoiceRecurringSetup to create a new recurring invoice
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_invoiceRecurringCreate(&$ciniki, $tnid, $invoice, $items) {

    //
    // Get the next invoice number
    //
    $strsql = "SELECT MAX(CAST(invoice_number AS UNSIGNED)) AS curmax "
        . "FROM ciniki_sapos_invoices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'max_num');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['max_num']) ) {
        $invoice['invoice_number'] = intval($rc['max_num']['curmax']) + 1;
    } else {
        $invoice['invoice_number'] = '1';
    }

    //
    // Save the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.invoice', $invoice, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $new_invoice_id = $rc['id'];

    //
    // Add the items to the invoice
    //
    foreach($items as $item) {
        $item['id'] = 0;
        $item['invoice_id'] = $new_invoice_id;
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.invoice_item', $item, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $new_item_id = $rc['id'];
    }

    //
    // Update the taxes/shipping incase something relavent changed
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $new_invoice_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $new_invoice_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
