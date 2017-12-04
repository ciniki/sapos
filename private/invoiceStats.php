<?php
//
// Description
// -----------
// This function will return the invoice stats for a tenant.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos__invoiceStats($ciniki, $tnid) {
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

    $rsp = array('stat'=>'ok', 'stats'=>array());

    //
    // Check the number of orders that need packing
    //
    $strsql = "SELECT status, COUNT(id) "
        . "FROM ciniki_sapos_shipments "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY status "
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'stats');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['stats']['shipments'] = array('status'=>$rc['stats']);

    //
    // Get the number of orders that have items left to be shipped
    //
    $strsql = "SELECT IF((ciniki_sapos_invoice_items.flags&0x0340)=0x0040,'available','backordered') AS bo_status, "
        . "COUNT(DISTINCT ciniki_sapos_invoices.id) "
        . "FROM ciniki_sapos_invoices, ciniki_sapos_invoice_items "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_sapos_invoices.status >= 20 "
        . "AND ciniki_sapos_invoices.shipping_status > 0 "
        . "AND ciniki_sapos_invoices.shipping_status < 50 "
        . "AND ciniki_sapos_invoices.id = ciniki_sapos_invoice_items.invoice_id "
        . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_sapos_invoice_items.quantity - ciniki_sapos_invoice_items.shipped_quantity) > 0 "
        . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY bo_status "
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'stats');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['stats']['shipping'] = array('status'=>$rc['stats']);

    //
    // Get the number
    //
    $strsql = "SELECT "
        . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS typestatus, "
        . "COUNT(id) "
        . "FROM ciniki_sapos_invoices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "GROUP BY invoice_type, status "
        . "";
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'stats');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['stats']['invoices'] = array('typestatus'=>$rc['stats']);

    //
    // Build the query to get the list of invoices
    //
    $strsql = "SELECT "
        . "MIN(invoice_date) AS min_invoice_date, "
        . "MIN(invoice_date) AS min_invoice_date_year, "
        . "MAX(invoice_date) AS max_invoice_date, "
        . "MAX(invoice_date) AS max_invoice_date_year "
        . "FROM ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND invoice_date <> '0000-00-00 00:00:00' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'stats', 'fname'=>'min_invoice_date', 'name'=>'stats',
            'fields'=>array('min_invoice_date', 'min_invoice_date_year', 'max_invoice_date', 
                'max_invoice_date_year'),
            'utctotz'=>array(
                'min_invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'min_invoice_date_year'=>array('timezone'=>$intl_timezone, 'format'=>'Y'),
                'max_invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'max_invoice_date_year'=>array('timezone'=>$intl_timezone, 'format'=>'Y'),
                ), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['stats'][0]['stats']['min_invoice_date_year']) ) {
        $rsp['stats']['min_invoice_date'] = $rc['stats'][0]['stats']['min_invoice_date'];
        $rsp['stats']['min_invoice_date_year'] = $rc['stats'][0]['stats']['min_invoice_date_year'];
        $rsp['stats']['max_invoice_date'] = $rc['stats'][0]['stats']['max_invoice_date'];
        $rsp['stats']['max_invoice_date_year'] = $rc['stats'][0]['stats']['max_invoice_date_year'];
    }

    //
    // Get the list of categories used in invoices
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x01000000) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $strsql = "SELECT DISTINCT IF(category = '', 'Uncategorized', category) AS category "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array( 
            array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['categories']) ) {
            $rsp['stats']['categories'] = $rc['categories'];
        } else {
            $rsp['stats']['categories'] = array();
        }
    }

    return $rsp;
}
?>
