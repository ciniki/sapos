<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_accountSubMenuItems($ciniki, $settings, $business_id) {

    $submenu = array();

    $strsql = "SELECT "
        . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS typestatus, "
        . "COUNT(id) "
        . "FROM ciniki_sapos_invoices "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
        . "AND (invoice_type = 40 OR invoice_type = 10) "
        . "AND status IN (15, 30, 50) "
        . "GROUP BY invoice_type, status "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'stats');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // If there are orders, add the orders submenu
    //
    if( isset($rc['stats']['40.15']) || isset($rc['stats']['40.30']) || isset($rc['stats']['40.50']) || isset($rc['stats']['10.50']) ) {
        $submenu[] = array('name'=>'Orders', 'priority'=>350, 'package'=>'ciniki', 'module'=>'sapos', 'url'=>$ciniki['request']['base_url'] . '/account/orders');
    }

    return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
