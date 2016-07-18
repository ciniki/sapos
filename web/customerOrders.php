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
function ciniki_sapos_web_customerOrders(&$ciniki, $settings, $business_id, $customer_id, $args) {


    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $date_format = 'M j, Y';

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $strsql = "SELECT ciniki_sapos_invoices.id, "
        . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status, "
        . "ciniki_sapos_invoices.invoice_number, "
        . "ciniki_sapos_invoices.po_number, "
        . "ciniki_sapos_invoices.invoice_date "
        . "FROM ciniki_sapos_invoices "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "AND (invoice_type = 40 OR invoice_type = 10) "
        . "AND status > 10 "
        . "ORDER BY invoice_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id',
            'fields'=>array('id', 'status', 'invoice_number', 'po_number', 'invoice_date'),
            'maps'=>array('status'=>$maps['invoice']['typestatus']),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format))),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    return $rc;
}
?>
