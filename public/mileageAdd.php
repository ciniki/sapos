<?php
//
// Description
// ===========
// This method adds a new mileage entry for a business.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_mileageAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Start Name'), 
        'start_address'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Start Address'), 
        'end_name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'End Name'), 
        'end_address'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'End Address'), 
		'travel_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 
			'name'=>'Travel Date'),
        'distance'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Distance'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.mileageAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Create the expense
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.mileage', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	return $rc;
}
?>
