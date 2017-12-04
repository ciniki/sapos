<?php
//
// Description
// ===========
// This method will return a list of invoices.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceStats(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceStats'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Build the query to get the list of invoices
    //
    $strsql = "SELECT "
        . "MIN(invoice_date) AS min_invoice_date, "
        . "MIN(invoice_date) AS min_invoice_date_year, "
        . "MAX(invoice_date) AS max_invoice_date, "
        . "MAX(invoice_date) AS max_invoice_date_year "
        . "FROM ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
    if( !isset($rc['stats']) ) {
        return array('stat'=>'ok', 'stats'=>array());
    }
    if( !isset($rc['stats'][0]) ) {
        return array('stat'=>'ok', 'stats'=>array());
    }

    return array('stat'=>'ok', 'stats'=>$rc['stats'][0]['stats']);
}
?>
