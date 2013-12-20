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
		'line_number'=>array('required'=>'no', 'blank'=>'no', 'default'=>'1', 'name'=>'Line Number'),
		'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Status'),
		'object'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object'),
		'object_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object ID'),
		'description'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Description'),
		'quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'type'=>'int', 'name'=>'Quantity'),
		'unit_amount'=>array('required'=>'yes', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
			'name'=>'Unit Amount'),
		'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
			'name'=>'Discount Amount'),
		'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 
			'name'=>'Discount Percentage'),
		'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Tax Type'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceItemAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	if( !isset($args['unit_discount_percentage']) || $args['unit_discount_percentage'] == '' ) {
		$args['unit_discount_percentage'] = 0;
	}

	//
	// Calculate the final amount for each item in the invoice
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
	$rc = ciniki_sapos_itemCalcAmount($ciniki, array(
		'quantity'=>$args['quantity'],
		'unit_amount'=>$args['unit_amount'],
		'unit_discount_amount'=>$args['unit_discount_amount'],
		'unit_discount_percentage'=>$args['unit_discount_percentage'],
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args['subtotal_amount'] = $rc['subtotal'];
	$args['discount_amount'] = $rc['discount'];
	$args['total_amount'] = $rc['total'];

	//
	// Get the max line_number for this invoice
	//
	if( !isset($args['line_number']) || $args['line_number'] == '' || $args['line_number'] == 0 ) {
		$strsql = "SELECT MAX(line_number) AS maxnum "
			. "FROM ciniki_sapos_invoice_items "
			. "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']) && isset($rc['num']['maxnum']) ) {
			$args['line_number'] = intval($rc['num']['maxnum']) + 1;
		} else {
			$args['line_number'] = 1;
		}
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
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
	$rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the invoice status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $args['invoice_id']);
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
