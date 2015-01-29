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
//
function ciniki_sapos_shipmentItemAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'shipment_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Shipment'),
		'invoice_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Invoice'),
		'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'),
		'quantity'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Quantity'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.shipmentItemAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Check if quantity is <= 0
	//
	if( $args['quantity'] <= 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1981', 'msg'=>'Quantity must be specified and cannot be zero.'));
	}

	//
	// If no shipment or invoice id specified
	//
	if( !isset($args['shipment_id']) && !isset($args['invoice_id']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2163', 'msg'=>'An existing shipment or invoice must be provided to add an item.'));
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shipmentUpdateStatus');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// If shipment does not exist, then add it
	//
	if( !isset($args['shipment_id']) || $args['shipment_id'] == '0' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'public', 'shipmentAdd');
		$rc = ciniki_sapos_shipmentAdd($ciniki);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$shipment = $rc['shipment'];
		$args['shipment_id'] = $shipment['id'];

		//
		// If is something other than packing, it should be set to packing
		//
		if( isset($shipment['status']) && $shipment['status'] > 10 ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'public', 'shipmentAdd');
			$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.shipment', $shipment['id'], 
				array('status'=>'10'), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	} else {
		//
		// Get the details of the shipment
		//
		$strsql = "SELECT id, invoice_id, status "
			. "FROM ciniki_sapos_shipments "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['shipment_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipment');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['shipment']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1930', 'msg'=>'Shipment does not exist.'));
		}
		$shipment = $rc['shipment'];
	}

	//
	// Check if item already exists in the shipment, don't allow updates through Add
	//
	$strsql = "SELECT id "
		. "FROM ciniki_sapos_shipment_items "
		. "WHERE shipment_id = '" . ciniki_core_dbQuote($ciniki, $args['shipment_id']) . "' "
		. "AND item_id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$existing_id = 0;
	if( isset($rc['rows']) && isset($rc['rows'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1928', 'msg'=>'Item already exists in the shipment.'));
	}


	//
	// Reject if shipment is already shipped
	//
	if( $shipment['status'] > 20 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2038', 'msg'=>'Shipment has already been shipped.'));
	}

	//
	// Get the details of the item from the invoice
	//
	$strsql = "SELECT id, invoice_id, object, object_id, quantity, shipped_quantity "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1931', 'msg'=>'Invoice does not exist.'));
	}
	$invoice_item = $rc['item'];

	if( ($invoice_item['quantity'] - $invoice_item['shipped_quantity'])	<= 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1932', 'msg'=>'This item has already been shipped.'));
	}

	//
	// Check the quantity being added is still required to invoice.  Don't let them add more than
	// they requested to be shipped
	//
	if( $args['quantity'] > ($invoice_item['quantity'] - $invoice_item['shipped_quantity']) ) {
		$left = $invoice_item['quantity'] - $invoice_item['shipped_quantity'];
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1933', 'msg'=>"Quantity too high, there was only $left ordered."));
	}

	//
	// Check for a callback to the object
	//
	if( $invoice_item['object'] != '' && $invoice_item['object_id'] != '' ) {
		list($pkg,$mod,$obj) = explode('.', $invoice_item['object']);
		$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryRemove');
		if( $rc['stat'] == 'ok' ) {
			$fn = $rc['function_call'];
			$rc = $fn($ciniki, $args['business_id'], array(
				'object'=>$invoice_item['object'],
				'object_id'=>$invoice_item['object_id'],
				'quantity'=>$args['quantity']));
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1934', 'msg'=>'Unable to remove from inventory', 'err'=>$rc['err']));
			}
		}
	}

	//
	// Update the shipped_quantity in the invoice item
	//
	$new_shipped_quantity = $invoice_item['shipped_quantity'] + $args['quantity'];
	if( $new_shipped_quantity < 0 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1985', 'msg'=>'The new shipped quantity for the invoice item will be less than zero.'));
	}
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', $invoice_item['id'], array('shipped_quantity'=>$new_shipped_quantity), 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1986', 'msg'=>'Unable to update the invoice.'));
	}

	//
	// Add the item
	//
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.shipment_item', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$item_id = $rc['id'];

	//
	// Update the shipment status
	//
	$rc = ciniki_sapos_shipmentUpdateStatus($ciniki, $args['business_id'], $args['shipment_id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the invoice status
	//
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $shipment['invoice_id']);
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

	//
	// Load the shipment and return full record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shipmentLoad');
    $rc = ciniki_sapos_shipmentLoad($ciniki, $args['business_id'], $args['shipment_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	error_log(print_r($rc['shipment']['invoice_items'], true));

	return array('stat'=>'ok', 'id'=>$item_id, 'shipment'=>$rc['shipment']);
}
?>
