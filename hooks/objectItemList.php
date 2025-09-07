<?php
//
// Description
// -----------
// This function will return the list of invoice items for an object
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_hooks_objectItemList(&$ciniki, $tnid, $args) {

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
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of items for the specified object
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != '' 
        ) {
        $strsql = "SELECT items.id, "
            . "items.code, "
            . "items.description, "
            . "items.quantity, "
            . "items.total_amount, "
            . "invoices.id AS invoice_id, "
            . "invoices.invoice_number, "
            . "invoices.invoice_date, "
            . "invoices.status, "
            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
            . "invoices.payment_status, "
            . "invoices.payment_status AS payment_status_text, "
            . "customers.display_name AS customer_name "
            . "FROM ciniki_sapos_invoice_items AS items "
            . "INNER JOIN ciniki_sapos_invoices AS invoices ON ("
                . "items.invoice_id = invoices.id "
                . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "invoices.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE items.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND items.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'items', 'fname'=>'id', 
                'fields'=>array('id', 'code', 'description', 'quantity', 'total_amount', 
                    'invoice_id', 'invoice_number', 'invoice_date',
                    'status', 'status_text', 'payment_status', 'payment_status_text',
                    ),
                'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
                'maps'=>array(
                    'status_text' => $maps['invoice']['typestatus'],
                    'payment_status_text' => $maps['invoice']['payment_status'],
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.508', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
        }
        $items = isset($rc['items']) ? $rc['items'] : array();

        return array('stat'=>'ok', 'items'=>$items);
    }

    return array('stat'=>'ok');
}
?>
