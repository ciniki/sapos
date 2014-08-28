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
function ciniki_sapos_shipmentItemUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'sitem_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shipment Item'),
		// The only change that can happen is quantity, so it must be specified
		'quantity'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Quantity'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.shipmentItemUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Check if quantity is <= 0
	//
	if( $args['quantity'] <= 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1980', 'msg'=>'Quantity must be specified and cannot be zero.'));
	}

	//
	// Get the existing details
	//
	$strsql = "SELECT id, shipment_id, item_id, quantity "
		. "FROM ciniki_sapos_shipment_items "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['sitem_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1935', 'msg'=>'Item does not exist.'));
	}
	$item = $rc['item'];

	//
	// Get the details of the shipment
	//
	$strsql = "SELECT id, invoice_id, status "
		. "FROM ciniki_sapos_shipments "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $item['shipment_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipment');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['shipment']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1936', 'msg'=>'Shipment does not exist.'));
	}
	$shipment = $rc['shipment'];

	//
	// Get the details of the item from the invoice
	//
	$strsql = "SELECT id, invoice_id, object, object_id, quantity, shipped_quantity "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['item_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1937', 'msg'=>'Invoice does not exist.'));
	}
	$invoice_item = $rc['item'];

	//
	// Quantity is the same, nothing to do
	//
	if( $item['quantity'] == $args['quantity'] ) {
		return array('stat'=>'ok');
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

	//
	// New quantity is less
	//
	if( $args['quantity'] < $item['quantity'] ) {
		// The amount removed from the quantity
		$quantity_removed = $item['quantity'] - $args['quantity'];

		$new_shipped_quantity = $invoice_item['shipped_quantity'] - $quantity_removed;
		if( $new_shipped_quantity < 0 ) {
			$new_shipped_quantity = 0;
		}
		$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', $invoice_item['id'], array('shipped_quantity'=>$new_shipped_quantity), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1984', 'msg'=>'Unable to update the invoice.'));
		}

		//
		// Replace the quantity in inventory
		//
		if( $invoice_item['object'] != '' && $invoice_item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $invoice_item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryReplace');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], array(
					'object'=>$invoice_item['object'],
					'object_id'=>$invoice_item['object_id'],
					'quantity'=>$quantity_removed));
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1990', 'msg'=>'Unable to replace inventory', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// New quantity is more, adjust inventory
	//
	elseif( $args['quantity'] > $item['quantity'] ) {
		// The amount added to the quantity
		$quantity_added = $args['quantity'] - $item['quantity'];

		//
		// Check that shipped quantity will not be greater than quantity
		//
		if( ($invoice_item['quantity'] - $invoice_item['shipped_quantity'] - $quantity_added) < 0 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1978', 'msg'=>'The quantity is more than was ordered.'));
		}

		// 
		// Check the new shipped quantity for invoice item is valid
		//
		$new_shipped_quantity = $invoice_item['shipped_quantity'] + $quantity_added;
		if( $new_shipped_quantity < 0 ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1987', 'msg'=>'The new shipped quantity for the invoice item will be less than zero.'));
		}
		if( $new_shipped_quantity > $invoice_item['quantity'] ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1989', 'msg'=>'The new shipped quantity for the invoice item will be less than zero.'));
		}

		//
		// Replace the quantity in inventory
		//
		if( $invoice_item['object'] != '' && $invoice_item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $invoice_item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryRemove');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], array(
					'object'=>$invoice_item['object'],
					'object_id'=>$invoice_item['object_id'],
					'quantity'=>$quantity_added));
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1982', 'msg'=>'Unable to replace inventory', 'err'=>$rc['err']));
				}
			}
		}

		//
		// Update the shipped quantity for the invoice item after adjusting inventory to make sure 
		// inventory is available
		//
		$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', $invoice_item['id'], array('shipped_quantity'=>$new_shipped_quantity), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1988', 'msg'=>'Unable to update the invoice.'));
		}

	}

	//
	// Update the item
	//
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.shipment_item', $args['sitem_id'], $args, 0x04);
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

	return array('stat'=>'ok');
}
?>
