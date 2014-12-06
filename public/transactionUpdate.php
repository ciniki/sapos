<?php
//
// Description
// ===========
// This method will add a new transaction to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_transactionUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'transaction_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Transaction'),
		'transaction_type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
		'transaction_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Date'),
		'source'=>array('required'=>'no', 'blank'=>'no', 'name'=>'source',
			'validlist'=>array('10','20','50','55','60','65','90','100','105','110','120')),
		'customer_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Customer Amount'),
		'transaction_fees'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Fees'),
		'business_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Business Amount'),
		'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.transactionUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the transaction details
	//
	$strsql = "SELECT invoice_id, customer_amount, transaction_fees, business_amount "
		. "FROM ciniki_sapos_transactions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['transaction_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'transaction');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( !isset($rc['transaction']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1396', 'msg'=>'Unable to locate the invoice'));
	}
	$transaction = $rc['transaction'];

	//
	// Check if we need to recalc the business_amount if customer or fees where changed
	//
	if( (isset($args['customer_amount']) || isset($args['transaction_fees'])) 
		&& (!isset($args['business_amount']) || $args['business_amount'] == '') ) {
		if( isset($args['customer_amount']) && isset($args['transaction_fees']) ) {
			$args['business_amount'] = bcsub($args['customer_amount'], $args['transaction_fees'], 4);
		} 
		elseif( isset($args['customer_amount']) ) {
			$args['business_amount'] = bcsub($args['customer_amount'], $transaction['transaction_fees'], 4);
		}
		elseif( isset($args['transaction_fees']) ) {
			$args['business_amount'] = bcsub($transaction['customer_amount'], $args['transaction_fees'], 4);
		}
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Update the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.transaction', 
		$args['transaction_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	
	//
	// Update the invoice status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $transaction['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}



	//
	// FIXME: Check if callback hooks to item modules
	//


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
