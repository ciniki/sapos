<?php
//
// Description
// ===========
// This method will add a new expense.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_expenseAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Description'), 
		'invoice_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'datetimetoutc', 
			'name'=>'Invoice Date'),
		'paid_date'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'type'=>'datetimetoutc', 
			'name'=>'Paid Date'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Start the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Create the expense
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.expense', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$expense_id = $rc['id'];

	//
	// Check for items in the expense
	//
	for($i=0;$i<25;$i++) {
		if( isset($ciniki['request']['args']["item_category_$i"]) 
			&& $ciniki['request']['args']["item_category_$i"] != '' ) {
				
			$item_args = array('category'=>$ciniki['request']['args']["item_category_$i"]);
//			if( isset($ciniki['request']['args']["item_id_$i"])
//				&& $ciniki['request']['args']["item_id_$i"] != '' ) {
//				$item_args['id'] = $ciniki['request']['args']["item_id_$i"];
//			}

			if( isset($ciniki['request']['args']["item_amount_$i"])
				&& $ciniki['request']['args']["item_amount_$i"] != '' ) {
				$amt = numfmt_parse_currency($intl_currency_fmt, 
					$ciniki['request']['args']["item_amount_$i"], $intl_currency);
				if( $amt === FALSE ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1430', 'msg'=>'Invalid amount format'));
				}
				$item_args['amount'] = $amt;
			} else {
				$item_args['amount'] = 0;
			}

			//
			// Add the item
			//
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

	return array('stat'=>'ok', 'expense'=>$rc['expense']);
}
?>
