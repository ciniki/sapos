<?php
//
// Description
// ===========
// This method will produce a PDF of the invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_dailySalesPDF(&$ciniki) {
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
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.dailySalesPDF'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the report
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'reporting', 'blockDailySales');
    $rc = ciniki_sapos_reporting_blockDailySales($ciniki, $args['tnid'], array('days'=>1));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.415', 'msg'=>'Unable to open report', 'err'=>$rc['err']));
    }
    $chunks = isset($rc['chunks']) ? $rc['chunks'] : array();

    //
    // Setup the report
    //
    $report = array(
        'title' => 'Daily Sales Report',
        'flags' => 0,
        );

    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportStart');
    $rc = ciniki_reporting_reportStart($ciniki, $args['tnid'], $report);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.416', 'msg'=>'Unable to start report', 'err'=>$rc['err']));
    }

    //
    // Go through chunks
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportChunkTable');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportChunkText');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'reporting', 'private', 'reportBlock');
    $rc = ciniki_reporting_reportBlock($ciniki, $args['tnid'], $report, array(
        'title' => 'Daily Sales',
        'chunks' => $chunks,
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.417', 'msg'=>'', 'err'=>$rc['err']));
    }

    $filename = 'Daily Sales ' . $dt->format('Y-m-d');
    $report['pdf']->Output($filename . '.pdf', 'I');
    return array('stat'=>'exit');
}
?>
