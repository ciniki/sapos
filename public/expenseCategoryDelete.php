<?php
//
// Description
// ===========
// This method will remove an expense category, if it's not currently in use.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_expenseCategoryDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseCategoryDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the number of items still referencing the category
	//
	$strsql = "SELECT 'items', COUNT(*) as num "
		. "FROM ciniki_sapos_expense_items "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND category_id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1424', 'msg'=>'Unable to remove category, there are still expenses using it'));
	}

	//
	// Remove the category
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.sapos.expense_category', 
		$args['category_id'], NULL, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
