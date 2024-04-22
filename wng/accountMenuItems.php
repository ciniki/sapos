<?php
//
// Description
// -----------
// This function will check for registrations in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_accountMenuItems($ciniki, $tnid, $request, $args) {

    $items = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // Check if any invoices for customer, otherwise hide the menu item
    //
    $strsql = "SELECT COUNT(invoices.id) AS num_invoices "
        . "FROM ciniki_sapos_invoices AS invoices "
        . "WHERE invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND invoices.status > 15 "
        . "AND (invoices.invoice_type = 10 OR invoices.invoice_type = 30) "
        . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.428', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    $num_items = isset($rc['num']) ? $rc['num'] : 0;

    if( $num_items > 0 ) {
        $items[] = array(
            'title' => 'Invoices', 
            'priority' => 1200, 
            'selected' => isset($args['selected']) && $args['selected'] == 'invoices' ? 'yes' : 'no',
            'ref' => 'ciniki.sapos.invoices',
            'url' => $base_url . '/invoices',
            );
    }

    return array('stat'=>'ok', 'items'=>$items);
}
?>
