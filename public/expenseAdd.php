<?php
//
// Description
// ===========
// This method will add a new expense.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'expense_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Description'), 
        'invoice_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 
            'name'=>'Invoice Date'),
        'paid_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'date', 
            'name'=>'Paid Date'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'), 
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'), 
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    $args['total_amount'] = 0;

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.expenseAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Get the list of expense categories
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_sapos_expense_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'id',
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        $categories = array();
    } else {
        $categories = $rc['categories'];
    }

    //
    // Start the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Create the expense
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.expense', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    $expense_id = $rc['id'];

    //
    // Build the list of arguments for each category
    //
    $arg_defs = array();
    foreach($categories as $cid => $category) {
        $arg_defs["category_$cid"] = array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>$category['name']);
    }
    $rc = ciniki_core_prepareArgs($ciniki, 'no', $arg_defs);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $cargs = $rc['args'];

    //
    // Check for items in the expense
    //
    foreach($categories as $cid => $category) {
        if( isset($cargs["category_$cid"]) && $cargs["category_$cid"] != '' ) {
            $item_args = array(
                'expense_id'=>$expense_id,
                'category_id'=>$cid,
                'amount'=>$cargs["category_$cid"],
                'notes'=>'',
                );

            //
            // Add the item
            //
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.expense_item', 
                $item_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return $rc;
            }
        }
    }

    //
    // Update the expense status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'expenseUpdateTotals');
    $rc = ciniki_sapos_expenseUpdateTotals($ciniki, $args['tnid'], $expense_id);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok', 'id'=>$expense_id);
}
?>
