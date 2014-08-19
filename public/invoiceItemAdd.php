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
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'),
		'object'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object'),
		'object_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object ID'),
		'price_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Price'),
		'description'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Description'),
		'quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'type'=>'int', 'name'=>'Quantity'),
		'shipped_quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'int', 'name'=>'Shipped'),
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
	// Check if item already exists in the invoice
	//
	$strsql = "SELECT id, invoice_id, object, object_id, "
		. "quantity, unit_amount, unit_discount_amount, unit_discount_percentage, "
		. "subtotal_amount, discount_amount, total_amount "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
		. "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
		. "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$existing_id = 0;
	if( isset($rc['rows']) && isset($rc['rows'][0]) ) {
		$existing_id = $rc['rows'][0]['id'];
		$item = $rc['rows'][0];
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	if( $existing_id == 0 ) {
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
		// Check for a callback to the object
		//
		if( $args['object'] != '' && $args['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $args['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemAdd');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], $args['invoice_id'], $args);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				// Update the invoice item with the new object and object_id
				if( (isset($rc['object']) && $rc['object'] != $args['object'])
					|| (isset($rc['flags']) && $rc['flags'] != $args['flags'])
					) {
					$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', 
						$item_id, $rc, 0x04);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}
	} else {
		//
		// If item object already exists in invoice, then add
		//

		//
		// Calculate the final amount for the item in the invoice
		//
		$item['old_quantity'] = $item['quantity'];
		$new_args = array('quantity'=>($item['quantity'] + $args['quantity']));
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
		$rc = ciniki_sapos_itemCalcAmount($ciniki, array(
			'quantity'=>$new_args['quantity'],
			'unit_amount'=>(isset($args['unit_amount'])?$args['unit_amount']:$item['unit_amount']),
			'unit_discount_amount'=>(isset($args['unit_discount_amount'])?$args['unit_discount_amount']:$item['unit_discount_amount']),
			'unit_discount_percentage'=>(isset($args['unit_discount_percentage'])?$args['unit_discount_percentage']:$item['unit_discount_percentage']),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$new_args['subtotal_amount'] = $rc['subtotal'];
		$new_args['discount_amount'] = $rc['discount'];
		$new_args['total_amount'] = $rc['total'];

		//
		// Update the item
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
		$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', 
			$item['id'], $new_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}
		
		//
		// Update the item values for callbacks
		//
		if( isset($args['quantity']) && $args['quantity'] != $item['quantity'] ) {
			$item['old_quantity'] = $item['quantity'];
			$item['quantity'] = $args['quantity'];
		}

		//
		// Check for a callback to the object
		//
		if( $item['object'] != '' && $item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemUpdate');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], $item['invoice_id'], $item);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
		$item_id = $item['id'];
	}

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
