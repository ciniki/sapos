<?php
//
// Description
// ===========
// This method will return a mileage entry for a business.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_mileageGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'mileage_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mileage'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.mileageGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Return the mileage record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'mileageLoad');
	$rc = ciniki_sapos_mileageLoad($ciniki, $args['business_id'], $args['mileage_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'mileage'=>$rc['mileage']);
}
?>
