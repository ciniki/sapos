<?php
//
// Description
// -----------
// This method searchs for a Invoice Costings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Invoice Costing for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_sapos_invoiceCostingSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceCostingSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of costings
    //
    $strsql = "SELECT ciniki_sapos_invoice_costing.id, "
        . "ciniki_sapos_invoice_costing.invoice_id, "
        . "ciniki_sapos_invoice_costing.line_number, "
        . "ciniki_sapos_invoice_costing.description, "
        . "ciniki_sapos_invoice_costing.quantity, "
        . "ciniki_sapos_invoice_costing.cost, "
        . "ciniki_sapos_invoice_costing.price "
        . "FROM ciniki_sapos_invoice_costing "
        . "WHERE ciniki_sapos_invoice_costing.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "description LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR description LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "ORDER BY description, date_added DESC "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'costings', 'fname'=>'id', 
            'fields'=>array('id', 'invoice_id', 'line_number', 'description', 'quantity', 'cost', 'price')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['costings']) ) {
        $costings = $rc['costings'];
        $costing_ids = array();
        foreach($costings as $iid => $costing) {
            $costing_ids[] = $costing['id'];
            $costings[$iid]['cost'] = '$' . number_format($costing['cost'], 2);
            $costings[$iid]['price'] = '$' . number_format($costing['price'], 2);
        }
    } else {
        $costings = array();
        $costing_ids = array();
    }

    return array('stat'=>'ok', 'costings'=>$costings, 'nplist'=>$costing_ids);
}
?>
