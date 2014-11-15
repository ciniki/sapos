<?php
//
// Description
// ===========
// This method will add a new expense category.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseCategoryAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
		'sequence'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Sequence'),
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Flags'),
		'taxrate_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Tax Rate'),
		'start_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 
			'type'=>'datetimetoutc', 'name'=>'Start Date'),
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 
			'type'=>'datetimetoutc', 'name'=>'End Date'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseCategoryAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the max sequence number if none specified
	//
	if( $args['sequence'] == '' ) { $args['sequence'] = 0; }
	if( $args['sequence'] == 0 ) {
		$strsql = "SELECT MAX(sequence) AS maxnumber "
			. "FROM ciniki_sapos_expense_categories "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'number');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['number']) && $rc['number']['maxnumber'] < ($args['sequence']-1) ) {
			$args['sequence'] = $rc['number']['maxnumber'] + 1;
		} else {
			$args['sequence'] = 1;
		}
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the item
	//
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.expense_category', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$category_id = $rc['id'];

	//
	// Update the sequence numbers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'expenseUpdateCategorySequences');
	$rc = ciniki_sapos_expenseUpdateCategorySequences($ciniki, $args['business_id'], $category_id, $args['sequence'], -1);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
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

	return array('stat'=>'ok', 'id'=>$category_id);
}
?>
