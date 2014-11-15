<?php
//
// Description
// ===========
// This method will remove a shipment from the system for an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_shipmentDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'shipment_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shipment'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.shipmentDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the shipment details
	//
	$strsql = "SELECT invoice_id, ship_date, status "
		. "FROM ciniki_sapos_shipments "
		. "WHERE ciniki_sapos_shipments.id = '" . ciniki_core_dbQuote($ciniki, $args['shipment_id']) . "' "
		. "AND ciniki_sapos_shipments.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipment');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	if( !isset($rc['shipment']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1996', 'msg'=>'Shipment does not exist'));
	}
	$shipment = $rc['shipment'];

	//
	// Reject if shipment is already shipped
	//
	if( $shipment['status'] > 20 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2040', 'msg'=>'Shipment has already been shipped.'));
	}

	//
	// Get the items in the shipment
	//
	$strsql = "SELECT ciniki_sapos_shipment_items.id, "
		. "ciniki_sapos_shipment_items.uuid, "
		. "ciniki_sapos_invoice_items.id AS invoice_item_id, "
		. "IFNULL(ciniki_sapos_invoice_items.object, '') AS object, "
		. "IFNULL(ciniki_sapos_invoice_items.object_id, '') AS object_id, "
		. "ciniki_sapos_invoice_items.shipped_quantity, "
		. "ciniki_sapos_shipment_items.item_id, "
		. "ciniki_sapos_shipment_items.quantity "
		. "FROM ciniki_sapos_shipment_items "
		. "LEFT JOIN ciniki_sapos_shipments ON ("
			. "ciniki_sapos_shipment_items.shipment_id = ciniki_sapos_shipments.id "
			. "AND ciniki_sapos_shipments.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_sapos_invoice_items ON ("
			. "ciniki_sapos_shipment_items.item_id = ciniki_sapos_invoice_items.id "
			. "AND ciniki_sapos_shipments.invoice_id = ciniki_sapos_invoice_items.invoice_id "
			. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sapos_shipment_items.shipment_id = '" . ciniki_core_dbQuote($ciniki, $args['shipment_id']) . "' "
		. "AND ciniki_sapos_shipment_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['rows']) ) {
		$items = $rc['rows'];
	} else {
		$items = array();
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Remove the items from the inventory, update the quantity_shipped and remove from the shipment
	//
	foreach($items as $iid => $item) {
		//
		// Update the shipped quantity
		//
		$new_shipped_quantity = $item['shipped_quantity'] - $item['quantity'];
		if( $new_shipped_quantity < 0 ) {
			$new_shipped_quantity = 0;
		}
		$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', 
			$item['invoice_item_id'], array('shipped_quantity'=>$new_shipped_quantity), 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Replace the quantity in inventory
		//
		if( $item['object'] != '' && $item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryRemove');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], array(
					'object'=>$item['object'],
					'object_id'=>$item['object_id'],
					'quantity'=>$item['quantity']));
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1994', 'msg'=>'Unable to replace inventory', 'err'=>$rc['err']));
				}
			}
		}
		
		//
		// Remove the item from the shipment
		//
		$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.sapos.shipment_item', 
			$item['id'], $item['uuid'], 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Remove the shipment
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.sapos.shipment', $args['shipment_id'], $args, 0x07);
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
