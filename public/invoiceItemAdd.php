<?php
//
// Description
// ===========
// This method will add a new item to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceItemAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'),
		'line_number'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Line Number'),
		'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Status'),
		'object'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object'),
		'object_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object ID'),
		'description'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Description'),
		'quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Quantity'),
		'unit_amount'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Unit Amount'),
		'amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Amount'),
		'taxtypes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Tax Types'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.taxAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the invoice id to update the taxes
	//
	$strsql = "SELECT invoice_id "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( !isset($rc['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1380', 'msg'=>'Unable to locate the item'));
	}
	$invoice_id = $rc['item']['invoice_id'];

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateTaxesAndTotal');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the item
	//
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$item_id = $rc['id'];

	//
	// Update the taxes
	//
	$rc = ciniki_sapos_invoiceUpdateTaxesAndTotal($ciniki, $args['business_id'], $invoice_id);
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

	return array('stat'=>'ok', 'id'=>$item_id);
}
?>
