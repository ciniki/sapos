<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_hooks_objectExpenses($ciniki, $tnid, $args) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Check for expenses
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {

        $strsql = "SELECT expenses.id, "
            . "expenses.name, "
            . "expenses.description, "
            . "expenses.invoice_date, "
            . "expenses.invoice_date AS invoice_date_display, "
            . "expenses.total_amount "
            . "FROM ciniki_sapos_expenses AS expenses "
            . "WHERE expenses.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND expenses.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'expenses', 'fname'=>'id', 
                'fields'=>array(
                    'id', 'name', 'description', 'invoice_date', 'invoice_date_display', 'total_amount',
                    ),
                'utctotz'=>array(
                    'invoice_date_display'=>array('format'=>$date_format, 'timezone'=>'UTC'),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.418', 'msg'=>'Unable to load expenses', 'err'=>$rc['err']));
        }
        $expenses = isset($rc['expenses']) ? $rc['expenses'] : array();

        $total_amount = 0;
        foreach($expenses as $eid => $expense) {
            $total_amount += $expense['total_amount'];
            $expenses[$eid]['total_amount_display'] = '$' . number_format($expense['total_amount'], 2);
        }

        return array('stat'=>'ok', 'expenses'=>$expenses, 'total'=>$total_amount);
    }

    return array('stat'=>'ok');
}
?>
