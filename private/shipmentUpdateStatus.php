<?php
//
// Description
// -----------
// This function will update the status of an invoice based on the payments
// made.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_shipmentUpdateStatus($ciniki, $business_id, $shipment_id) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	//
	// Get the shipment details
	//
	$strsql = "SELECT id, invoice_id, ship_date, status "
		. "FROM ciniki_sapos_shipments "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $shipment_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipment');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( !isset($rc['shipment']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2003', 'msg'=>'Unable to locate the shipment'));
	}
	$shipment = $rc['shipment'];

	//
	// Load the items in the invoice
	//
	$remaining_quantity = 'none';
	$backordered = 'none';

	//
	// Get the items, to see if there is any quantity left of anything to ship
	//
	$strsql = "SELECT id, flags, object, object_id, quantity-shipped_quantity AS remaining_quantity "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "HAVING remaining_quantity > 0 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$remaining_quantity = 'some';
		$backordered = 'all';
		$items = $rc['rows'];
		//
		// Check if the inventory should be added
		//
		$objects = array();
		foreach($items as $iid => $item) {
			if( !isset($objects[$item['object']]) ) {
				$objects[$item['object']] = array();
			}
			$objects[$item['object']][] = $item['object_id'];
		}
		// 
		// Get the inventory levels for each object
		//
		foreach($objects as $object => $object_ids) {
			list($pkg,$mod,$obj) = explode('.', $object);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryLevels');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $business_id, array(
					'object'=>$object,
					'object_ids'=>$object_ids,
					));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2005', 'msg'=>'Unable to get inventory levels.', 'err'=>$rc['err']));
				}
				//
				// Update the inventory levels for the invoice items
				//
				$quantities = $rc['quantities'];
				foreach($items as $iid => $item) {
					if( $item['object'] == $object 
						&& isset($quantities[$item['object_id']]) 
						) {
						if( $quantities[$item['object_id']]['inventory_quantity'] > 0 ) {
							$backordered = 'some';
							// Check if there is backorder ability on product
							if( ($item['flags']&0x04) == 0x04 && ($item['flags']&0x0100) == 0x0100 ) {
								// Update to set backordered flag on invoice item
								$item['flags'] = ((int)$item['flags']) &~ 0x0100;
								$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_item',
									$item['id'], array('flags'=>$item['flags']), 0x04);
								if( $rc['stat'] != 'ok' ) {
									return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2061', 'msg'=>'Unable to remove backorder flag on invoice item', 'err'=>$rc['err']));
								}
							}
						}
						// Check if there is backorder ability on product
						// Check if no inventory available
						if( ($item['flags']&0x04) == 0x04 
							&& $item['remaining_quantity'] > 0 
							&& $quantities[$item['object_id']]['inventory_quantity'] <= 0 
							&& ($item['flags']&0x0100) == 0 
							) {
							// Update to set backordered flag on invoice item
							$item['flags'] = ((int)$item['flags']|0x0100);
							$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_item',
								$item['id'], array('flags'=>$item['flags']), 0x04);
							if( $rc['stat'] != 'ok' ) {
								return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2060', 'msg'=>'Unable to update backorder flag on invoice item', 'err'=>$rc['err']));
							}
						}
					}
				}
			}
		}
	} else {
		$remaining_quantity = 'none';
	}

	$new_status = $shipment['status'];

	if( ($remaining_quantity == 'none' || ($remaining_quantity == 'some' && $backordered == 'all')) && $new_status == '10' ) {
		$new_status = '20';	
	}

	//
	// Check if any values have changed
	//
	$args = array();
	if( $new_status != $shipment['status'] ) {
		$args['status'] = $new_status;
	}

	if( count($args) > 0 ) {
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.shipment', 
			$shipment_id, $args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	return array('stat'=>'ok');
}
?>
