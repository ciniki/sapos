<?php
//
// Description
// -----------
// Add an expense from recurring expense
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
function ciniki_sapos_expenseAddFromRecurring($ciniki, $tnid, $expense_id) {
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the recurring expense details
    //
    $strsql = "SELECT id, "
        . "expense_type, "
        . "name, "
        . "description, "
        . "invoice_date, "
        . "paid_date, "
        . "total_amount, "
        . "notes "
        . "FROM ciniki_sapos_expenses "
        . "WHERE ciniki_sapos_expenses.id = '" . ciniki_core_dbQuote($ciniki, $expense_id) . "' "
        . "AND ciniki_sapos_expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'expense');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.317', 'msg'=>'Unable to load expense', 'err'=>$rc['err']));
    }
    if( !isset($rc['expense']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.318', 'msg'=>'Unable to find requested expense'));
    }
    $expense = $rc['expense'];
    
    //
    // Get the item details
    //
    $strsql = "SELECT items.id, "  
        . "items.category_id, "
        . "items.amount, "
        . "items.notes "
        . "FROM ciniki_sapos_expense_items AS items "
        . "WHERE items.expense_id = '" . ciniki_core_dbQuote($ciniki, $expense_id) . "' "
        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY items.date_added "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'items', 'fname'=>'id', 'fields'=>array('id', 'category_id', 'amount', 'notes')),
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
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Create the expense
    //
    $new_expense = $expense;
    $new_expense['id'] = 0;
    $new_expense['source_id'] = $expense_id;
    $new_expense['expense_type'] = 10;

    //
    // Save the expense
    //
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.expense', $new_expense, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    $new_expense_id = $rc['id'];

    //
    // Add the items to the expense
    //
    foreach($items as $iid => $item) {
        $new_item = $item;
        $new_item['id'] = 0;
        $new_item['expense_id'] = $new_expense_id;

        //
        // Save the item
        //
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.expense_item', $new_item, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        $new_item_id = $rc['id'];
    }

    //
    // Update the recurring expense to the next invoice date
    //
    $invoice_date = new DateTime($expense['invoice_date'] . ' 12:00:00', new DateTimezone($intl_timezone));
    if( $expense['expense_type'] == '20' ) {
        $invoice_date->add(new DateInterval('P1M'));
    } elseif( $expense['expense_type'] == '30' ) {
        $invoice_date->add(new DateInterval('P3M'));
    } elseif( $expense['expense_type'] == '40' ) {
        $invoice_date->add(new DateInterval('P1Y'));
    }
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.expense', $expense_id, array('invoice_date'=>$invoice_date->format('Y-m-d')), 0x04);
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
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'sapos');

    return array('stat'=>'ok', 'expense'=>$expense);
}
?>
