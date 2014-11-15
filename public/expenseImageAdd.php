<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseImageAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'expense_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Expense'), 
		'image_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Image'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseImageAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    } 

	if( $args['expense_id'] <= 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1418', 'msg'=>'No expense specified'));
	}
   
	//
	// Add the expense image to the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.expense_image', $args, 0x07);
}
?>
