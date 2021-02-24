<?php
//
// Description
// ===========
// This method returns the sales the current day for the tenant.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_posSales(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.posSales'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'core', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
   
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Get the list of invoices for today
    //
    $strsql = "SELECT invoices.id, "
        . "invoices.invoice_number, "
        . "invoices.invoice_type, "
        . "invoices.status, "
        . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
        . "invoices.invoice_date, "
        . "invoices.billing_name, "
        . "invoices.total_amount "
        . "FROM ciniki_sapos_invoices AS invoices "
        . "WHERE invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
        . "AND invoices.invoice_type = 30 " // POS Sales only
        . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY invoice_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array('id', 'invoice_number', 'invoice_type', 'status', 'status_text', 'invoice_date', 'billing_name', 'total_amount'),
            'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.68', 'msg'=>'Unable to load invoices', 'err'=>$rc['err']));
    }
    $totals = array(
        'total_amount' => 0,
        );
    $invoice_ids = array();
    $invoices = array();
    if( isset($rc['invoices']) ) {
        $invoices = $rc['invoices'];
        foreach($invoices as $k => $v) {
            $invoices[$k]['total_amount_display'] = '$' . number_format($v['total_amount'], 2);
            $totals['total_amount'] += $v['total_amount'];
            $invoice_ids[] = $v['id'];
        }
    }

    $totals['total_amount_display'] = '$' . number_format($totals['total_amount'], 2);

    $rsp = array('stat'=>'ok', 'invoices'=>$invoices, 'invoice_ids'=>$invoice_ids, 'totals'=>$totals);

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x20000000) ) {
        //
        // Get the list of orders that require packing
        //
        $strsql = "SELECT invoices.id, "
            . "invoices.invoice_number, "
            . "invoices.invoice_type, "
            . "invoices.status, "
            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
            . "invoices.invoice_date, "
            . "invoices.billing_name, "
            . "invoices.total_amount "
            . "FROM ciniki_sapos_invoices AS invoices "
            . "WHERE invoices.invoice_type != 20 "
            . "AND invoices.status = 45 "
            . "AND invoices.shipping_status = 20 "
            . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY invoice_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'invoices', 'fname'=>'id', 
                'fields'=>array('id', 'invoice_number', 'invoice_type', 'status', 'status_text', 'invoice_date', 'billing_name', 'total_amount'),
                'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.70', 'msg'=>'Unable to load invoices', 'err'=>$rc['err']));
        }
        $rsp['packing_required'] = isset($rc['invoices']) ? $rc['invoices'] : array();

        //
        // Get the list of orders that are for pickup
        //
        $strsql = "SELECT invoices.id, "
            . "invoices.invoice_number, "
            . "invoices.invoice_type, "
            . "invoices.status, "
            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
            . "invoices.invoice_date, "
            . "invoices.billing_name, "
            . "invoices.total_amount "
            . "FROM ciniki_sapos_invoices AS invoices "
            . "WHERE invoices.invoice_type != 20 "
            . "AND invoices.status = 45 "
            . "AND invoices.shipping_status = 55 "
            . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY invoice_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'invoices', 'fname'=>'id', 
                'fields'=>array('id', 'invoice_number', 'invoice_type', 'status', 'status_text', 'invoice_date', 'billing_name', 'total_amount'),
                'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.249', 'msg'=>'Unable to load invoices', 'err'=>$rc['err']));
        }
        $rsp['pickups'] = isset($rc['invoices']) ? $rc['invoices'] : array();
    }

    return $rsp;
}
?>
