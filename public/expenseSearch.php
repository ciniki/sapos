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
function ciniki_sapos_expenseSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'sort'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search String'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'15', 'name'=>'Limit'), 
        'items'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Items'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.expenseSearch'); 
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
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Build the query to get the list of expenses
    //
    $strsql = "SELECT ciniki_sapos_expenses.id, "
        . "ciniki_sapos_expenses.name, "
        . "ciniki_sapos_expenses.description, "
        . "IFNULL(DATE_FORMAT(ciniki_sapos_expenses.invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS invoice_date, "
        . "ciniki_sapos_expenses.total_amount ";
    if( isset($args['items']) && $args['items'] == 'yes' ) {
        $strsql .= ", ciniki_sapos_expense_items.id AS item_id, "
            . "ciniki_sapos_expense_items.category_id, "
            . "ciniki_sapos_expense_items.amount "
            . "FROM ciniki_sapos_expenses "
            . "LEFT JOIN ciniki_sapos_expense_items ON (ciniki_sapos_expenses.id = ciniki_sapos_expense_items.expense_id "
            . "AND ciniki_sapos_expense_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    } else {
        $strsql .= "FROM ciniki_sapos_expenses ";
    }
    $strsql .= "WHERE ciniki_sapos_expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $strsql .= "AND (ciniki_sapos_expenses.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR ciniki_sapos_expenses.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") ";
    if( isset($args['sort']) ) {
        if( $args['sort'] == 'latest' ) {
            $strsql .= "ORDER BY ciniki_sapos_expenses.last_updated DESC ";
        } elseif( $args['sort'] == 'reverse' ) {
            $strsql .= "ORDER BY ciniki_sapos_expenses.invoice_date DESC ";
        } else {
            $strsql .= "ORDER BY ciniki_sapos_expenses.invoice_date DESC ";
        }
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    if( isset($args['items']) && $args['items'] == 'yes' ) {
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'expenses', 'fname'=>'name', 'name'=>'expense',
                'fields'=>array('id', 'invoice_date', 'name', 'description', 'total_amount')),
            array('container'=>'items', 'fname'=>'item_id', 'name'=>'item',
                'fields'=>array('id'=>'item_id', 'category_id', 'amount')),
            ));
    } else {
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'expenses', 'fname'=>'id', 'name'=>'expense',
                'fields'=>array('id', 'invoice_date', 'name', 'total_amount')),
            ));
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['expenses']) ) {
        return array('stat'=>'ok', 'expenses'=>array());
    }
    foreach($rc['expenses'] as $iid => $expense) {
        $rc['expenses'][$iid]['expense']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
            $expense['expense']['total_amount'], $intl_currency);
        if( isset($expense['expense']['items']) ) {
            foreach($expense['expense']['items'] as $item_id => $item) {
                $rc['expenses'][$iid]['expense']['items'][$item_id]['item']['amount_display'] = numfmt_format_currency($intl_currency_fmt, $item['item']['amount'], $intl_currency);
            }
        }
    }

    return array('stat'=>'ok', 'expenses'=>$rc['expenses']);
}
?>
