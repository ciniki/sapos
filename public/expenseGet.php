<?php
//
// Description
// ===========
// This method will add a new expense to the system, creating item entries if specified.  If
// a customer is specified, the billing/shipping address will be pulled from the customer record.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'expense_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
		'images'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Images'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Return the expense record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'expenseLoad');
	$rc = ciniki_sapos_expenseLoad($ciniki, $args['business_id'], $args['expense_id'], $args['images']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'expense'=>$rc['expense']);
}
?>
