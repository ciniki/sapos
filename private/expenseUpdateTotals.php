<?php
//
// Description
// -----------
// This function will update the taxes for an expense.  Taxes may be added or removed based on the items
// in the expense.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_expenseUpdateTotals($ciniki, $tnid, $expense_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the expense details, so we know what taxes are applicable for the expense date
    //
    $strsql = "SELECT invoice_date, "
        . "paid_date, "
        . "total_amount "
        . "FROM ciniki_sapos_expenses "
        . "WHERE ciniki_sapos_expenses.id = '" . ciniki_core_dbQuote($ciniki, $expense_id) . "' "
        . "AND ciniki_sapos_expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'expense');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['expense']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.18', 'msg'=>'Unable to find expense'));
    }
    $expense = $rc['expense'];

    //
    // Get the items from the expense
    //
    $strsql = "SELECT id, "
        . "amount "
        . "FROM ciniki_sapos_expense_items "
        . "WHERE ciniki_sapos_expense_items.expense_id = '" . ciniki_core_dbQuote($ciniki, $expense_id) . "' "
        . "AND ciniki_sapos_expense_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $items = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Add the totals for the expense items
    //
    $expense_total_amount = 0;
    if( count($items) > 0 ) {
        foreach($items as $iid => $item) {
            $expense_total_amount = bcadd($expense_total_amount, $item['amount'], 4);
        }
    }
    
    $args = array();
    if( $expense_total_amount != floatval($expense['total_amount']) ) {
        $args['total_amount'] = $expense_total_amount;
    }
    if( count($args) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.expense', 
            $expense_id, $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
