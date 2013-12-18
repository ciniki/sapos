<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an invoice. 
// This method is typically used by the UI to display a list of changes that have occured 
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Invoice Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_sapos_history($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'object'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object'), 
		'object_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object ID'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
	$rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.history');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( $args['object'] == 'ciniki.sapos.invoice' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_invoices', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.invoice_item' ) {
		if( $args['field'] == 'unit_amount' || $args['field'] == 'unit_discount_amount' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_invoice_items', $args['object_id'], $args['field'], 'currency');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_invoice_items', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.transaction' ) {
		if( $args['field'] == 'customer_amount' 
			|| $args['field'] == 'transaction_fees' 
			|| $args['field'] == 'business_amount' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_transactions', $args['object_id'], $args['field'], 'currency');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_transactions', $args['object_id'], $args['field']);
	}
}
?>
