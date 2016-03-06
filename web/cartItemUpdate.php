<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_cartItemUpdate($ciniki, $settings, $business_id, $args) {

	//
	// Check that a cart does not exist
	//
	if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
		$invoice_id = $ciniki['session']['cart']['sapos_id'];	
		//
		// Check that an item was specified
		//
		if( !isset($args['item_id']) || $args['item_id'] == '' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1605', 'msg'=>'No item specified'));
		}

		//
		// Get the existing item details
		//
		$strsql = "SELECT id, invoice_id, object, object_id, price_id, "
			. "quantity, unit_amount, unit_discount_amount, unit_discount_percentage, "
			. "subtotal_amount, discount_amount, total_amount "
			. "FROM ciniki_sapos_invoice_items "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
		if( $rc['stat'] != 'ok' ) { 
			return $rc;
		}   
		if( !isset($rc['item']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1607', 'msg'=>'Unable to locate the invoice item'));
		}
		$item = $rc['item'];

		//
		// Lookup the object
		//
		if( $item['object'] != '' && $item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'cartItemLookup');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $business_id, $ciniki['session']['customer'], array(
					'object'=>$item['object'],
					'object_id'=>$item['object_id'],
					'price_id'=>$item['price_id'],
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$object_item = $rc['item'];
			}
		}
		
		//
		// Check if quantity or unit_amount has changed, and update the amount
		//
		if( isset($args['quantity']) ) {

			//
			// Check if quantity is zero or below, remove
			//
			if( $args['quantity'] <= 0 ) {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartItemDelete');
				return ciniki_sapos_web_cartItemDelete($ciniki, $settings, $business_id, $args);
			}

			//
			// If increasing the quantity, check to make sure there is enough
			//
			if( $args['quantity'] > $item['quantity'] ) {
				if( isset($object_item['limited_units']) && $object_item['limited_units'] == 'yes'
					&& isset($object_item['units_available']) && $object_item['units_available'] != '' ) {
					if( $args['quantity'] > $object_item['units_available'] ) {
						// Check if there's enough units available, otherwise use the maximum available
						if( $object_item['units_available'] > 0 ) {
							$args['quantity'] = $object_item['units_available'];
						} else {
							return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1700', 'msg'=>'No more available'));
						}
					}
				}
			}

			//
			// Calculate the final amount for the item in the invoice
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
			$rc = ciniki_sapos_itemCalcAmount($ciniki, array(
				'quantity'=>(isset($args['quantity'])?$args['quantity']:$item['quantity']),
				'unit_amount'=>(isset($args['unit_amount'])?$args['unit_amount']:$item['unit_amount']),
				'unit_discount_amount'=>(isset($args['unit_discount_amount'])?$args['unit_discount_amount']:$item['unit_discount_amount']),
				'unit_discount_percentage'=>(isset($args['unit_discount_percentage'])?$args['unit_discount_percentage']:$item['unit_discount_percentage']),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$args['subtotal_amount'] = $rc['subtotal'];
			$args['discount_amount'] = $rc['discount'];
			$args['total_amount'] = $rc['total'];
		}

		//
		// Update the item
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_item', $args['item_id'], $args, 0x04);
		if( $rc['stat'] != 'ok' ) {
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
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'cartItemUpdate');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $business_id, $invoice_id, $item);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}

		//
		// Update the taxes
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
		$rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $business_id, $invoice_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Update the invoice status
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
		$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $business_id, $invoice_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		return array('stat'=>'ok');
	}

	return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1611', 'msg'=>'Cart does not exist'));
}
?>
