<?php
//
// Description
// ===========
// This method will return a mileage entry for a tenant.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_mileageGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'mileage_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mileage'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.mileageGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Return the mileage record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'mileageLoad');
    $rc = ciniki_sapos_mileageLoad($ciniki, $args['tnid'], $args['mileage_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'mileage'=>$rc['mileage']);
}
?>
