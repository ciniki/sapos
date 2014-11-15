<?php
//
// Description
// ===========
// This method will update and existing mileage rate for a business.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_mileageRateUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'rate_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mileage Rate'), 
		'rate'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'New Rate'),
		'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'),
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'End Date'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.mileageRateUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//
	// FIXME: Check to make sure date doesn't overlap an existing rate
	//
	if( isset($args['start_date']) ) {

	} 
	if( isset($args['end_date']) ) {

	}

	//
	// Update the mileage rate
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.mileage_rate', 
		$args['rate_id'], $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return $rc;
}
?>
