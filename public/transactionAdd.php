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
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_transactionAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'),
		'transaction_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
		'transaction_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Date'),
		'source'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'source',
			'validlist'=>array('10','20','30','90','100','105','110','120')),
		'customer_amount'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer Amount'),
		'transaction_fees'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Fees'),
		'business_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Business Amount'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.transactionAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Set the user id who created the invoice
	//
	$args['user_id'] = $ciniki['session']['user']['id'];

	//
	// Check if fees are blank, set to 0
	//
	if( $args['transaction_fees'] == '' ) {
		$args['transaction_fees'] = 0;
	}
	//
	// Check if business amount not specified, then set the same as customer_amount
	//
	if( !isset($args['business_amount']) || $args['business_amount'] == '' ) {
		$args['business_amount'] = $args['customer_amount'] - $args['transaction_fees'];
	}

	$args['gateway'] = '';
	$args['gateway_token'] = '';
	$args['gateway_status'] = '';
	$args['gateway_response'] = '';

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
	// Add the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.transaction', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$transaction_id = $rc['id'];

	//
	// Update the invoice status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatus');
	$rc = ciniki_sapos_invoiceUpdateStatus($ciniki, $args['business_id'], $args['invoice_id']);
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

	return array('stat'=>'ok', 'id'=>$transaction_id);
}
?>
