<?php
//
// Description
// ===========
// This method will add a new mileage rate to the business.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_mileageRateAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'rate'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'currency', 'name'=>'Rate'),
		'start_date'=>array('required'=>'yes', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'),
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetimetoutc', 'name'=>'End Date'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.mileageRateAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// FIXME: Check to make sure date doesn't overlap an existing rate
	//

	//
	// Add the mileage rate
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.mileage_rate', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return $rc;
}
?>
