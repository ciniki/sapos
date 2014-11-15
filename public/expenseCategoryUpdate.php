<?php
//
// Description
// ===========
// This method will update the expense category.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseCategoryUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Expense Category'),
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sequence'),
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'),
		'taxrate_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Rate'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseCategoryUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	if( isset($args['sequence']) && $args['sequence'] == '' ) {
		$args['sequence'] = 1;
	}
	if( isset($args['sequence']) && $args['sequence'] == '0' ) {
		$args['sequence'] = 1;
	}

	//
	// Get the existing category info
	//
	$strsql = "SELECT id, sequence "
		. "FROM ciniki_sapos_expense_categories "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'category');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	if( !isset($rc['category']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1439', 'msg'=>'Unable to find category'));
	}
	$category = $rc['category'];


	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Update the category
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.expense_category', $args['category_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the sequence numbers, if the sequence is different
	//
	if( isset($args['sequence']) && $args['sequence'] != $category['sequence'] ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'expenseUpdateCategorySequences');
		$rc = ciniki_sapos_expenseUpdateCategorySequences($ciniki, $args['business_id'], 
			$args['category_id'], $args['sequence'], $category['sequence']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}
	}

	//
	// Commit the transaction
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'sapos');

	return array('stat'=>'ok');
}
?>
