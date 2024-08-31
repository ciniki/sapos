<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseStats(&$ciniki) {
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.expenseStats'); 
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
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the min and max invoice date for expenses
    //
    $strsql = "SELECT "
        . "MIN(invoice_date) AS min_invoice_date, "
        . "MIN(invoice_date) AS min_invoice_date_year, "
        . "MAX(invoice_date) AS max_invoice_date, "
        . "MAX(invoice_date) AS max_invoice_date_year "
        . "FROM ciniki_sapos_expenses "
        . "WHERE ciniki_sapos_expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
    if( !isset($rc['stats']) || !isset($rc['stats'][0]) ) {
        $stats = array();
    } else {
        $stats = $rc['stats'][0]['stats'];
    }

//    //
//    // Get the list of categories
//    //
//    $strsql = "SELECT id, "
//        . "name, "
//        . "sequence, "
//        . "flags, "
//        . "taxrate_id, "
//        . "start_date, "
//        . "end_date "
//        . "FROM ciniki_sapos_expense_categories "
//        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//        . "ORDER BY sequence "
//        . "";
//    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
//    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
//        array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
//            'fields'=>array('id', 'name', 'sequence', 'flags', 'taxrate_id', 'start_date', 'end_date'),
//            'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
//                'end_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
//        )));
//    if( $rc['stat'] != 'ok' ) {
//        return $rc;
//    }
//    if( !isset($rc['categories']) ) {
//        $categories = array();
//    } else {
//        $categories = $rc['categories'];
//    }

    return array('stat'=>'ok', 'stats'=>$stats);
}
?>
