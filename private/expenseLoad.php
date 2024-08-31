<?php
//
// Description
// -----------
// This function will load an expense and all the pieces for it.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_expenseLoad($ciniki, $tnid, $expense_id) {
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
//  $time_format = ciniki_users_timeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
//  $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // The the expense details
    //
    $strsql = "SELECT id, "
        . "expense_type, "
        . "name, "
        . "description, "
        . "IFNULL(DATE_FORMAT(ciniki_sapos_expenses.invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS invoice_date, "
        . "IFNULL(DATE_FORMAT(ciniki_sapos_expenses.paid_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS paid_date, "
        . "total_amount, "
        . "notes "
        . "FROM ciniki_sapos_expenses "
        . "WHERE ciniki_sapos_expenses.id = '" . ciniki_core_dbQuote($ciniki, $expense_id) . "' "
        . "AND ciniki_sapos_expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'expenses', 'fname'=>'id', 
            'fields'=>array('id', 'expense_type', 'name', 'description', 
                'invoice_date', 'paid_date', 
                'total_amount', 'notes'),
//          'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
//              'invoice_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
//              'invoice_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
//              'paid_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
//              'paid_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
//              'paid_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
//              ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['expenses']) || !isset($rc['expenses'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.17', 'msg'=>'Expense does not exist'));
    }
    $expense = $rc['expenses'][0];

    //
    // Get the item details
    //
    $strsql = "SELECT items.id, "  
        . "items.category_id, "
        . "categories.name, "
        . "items.amount, "
        . "items.notes "
        . "FROM ciniki_sapos_expense_items AS items "
        . "LEFT JOIN ciniki_sapos_expense_categories AS categories ON ("
            . "items.category_id = categories.id "
            . "AND categories.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE items.expense_id = '" . ciniki_core_dbQuote($ciniki, $expense_id) . "' "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY categories.sequence "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'items', 'fname'=>'id',
            'fields'=>array('id', 'category_id', 'name', 'amount', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $expense['items'] = isset($rc['items']) ? $rc['items'] : array();
    foreach($expense['items'] as $iid => $item) {
        $expense['items'][$iid]['amount'] = '$' . number_format($item['amount'], 2);
    }

    //
    // Format the currency numbers
    //
    $expense['total_amount'] = '$' . number_format($expense['total_amount'], 2);

    return array('stat'=>'ok', 'expense'=>$expense);
}
?>
