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
function ciniki_sapos_expenseUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'expense_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Expense'), 
        'expense_type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
        'invoice_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Invoice Date'),
        'paid_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Paid Date'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'), 
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.expenseUpdate'); 
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

    //
    // Get the existing categories
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_sapos_expense_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    //
    // Get the existing items
    //
    $strsql = "SELECT id, category_id, amount "
        . "FROM ciniki_sapos_expense_items "
        . "WHERE expense_id = '" . ciniki_core_dbQuote($ciniki, $args['expense_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'items', 'fname'=>'category_id', 'fields'=>array('id', 'category_id', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $items = isset($rc['items']) ? $rc['items'] : array();
    
    //
    // Start the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the expense
    //
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.expense', $args['expense_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

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
        if( isset($cargs["category_$cid"]) ) {
            $item_args = array(
                'amount'=>$cargs["category_$cid"]
                );

            //
            // Check if the item already exists
            //
            if( isset($items[$cid]) ) { 
                if( $cargs["category_$cid"] == '' || $cargs["category_$cid"] == 0 ) {
                    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sapos.expense_item', 
                        $items[$cid]['id'], NULL, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                        return $rc;
                    }
                } else {
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.expense_item', 
                        $items[$cid]['id'], $item_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                        return $rc;
                    }
                }
            } else {
                $item_args['expense_id'] = $args['expense_id'];
                $item_args['category_id'] = $cid;
                $item_args['notes'] = '';
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.expense_item', 
                    $item_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                    return $rc;
                }
            }
        }
    }

    //
    // Update the expense status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'expenseUpdateTotals');
    $rc = ciniki_sapos_expenseUpdateTotals($ciniki, $args['tnid'], $args['expense_id']);
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

    return array('stat'=>'ok');
}
?>
