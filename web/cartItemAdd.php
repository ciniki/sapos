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
function ciniki_sapos_web_cartItemAdd($ciniki, $settings, $business_id, $args) {

	//
	// Check that a cart does not exist
	//
	if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
		$invoice_id = $ciniki['session']['cart']['sapos_id'];	
		//
		// Check that an item was specified
		//
		if( !isset($args['object']) || $args['object'] == '' 
			|| !isset($args['object_id']) || $args['object_id'] == ''
			|| !isset($args['quantity']) || $args['quantity'] == ''
			) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1609', 'msg'=>'No item specified'));
		}

		if( !isset($args['price_id']) ) {
			$args['price_id'] = 0;
		}
	
		//
		// Lookup the object
		//
		$item = array();
		if( $args['object'] != '' && $args['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $args['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'cartItemLookup');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $business_id, $ciniki['session']['customer'], $args);
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1817', 'msg'=>'Unable to find item', 'err'=>$rc['err']));
				}
				$item = $rc['item'];
			} else {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3159', 'msg'=>'Unable to find item', 'err'=>$rc['err']));
            }
		}
		if( isset($item['object']) ) { $args['object'] = $item['object']; }
		if( isset($item['object_id']) ) { $args['object_id'] = $item['object_id']; }
		if( isset($item['price_id']) ) { $args['price_id'] = $item['price_id']; }
		
		//
		// Check quantity available
		//
		if( isset($item['limited_units']) && $item['limited_units'] == 'yes' 
			&& isset($item['units_available']) && $item['units_available'] != '' ) {
			if( $args['quantity'] > $item['units_available'] ) {
				if( $item['units_available'] > 0 ) {
					$args['quantity'] = $item['units_available'];
				} else {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1602', 'msg'=>"I'm sorry there are no more available."));
				}
			}
		}

		//
		// Setup item pricing
		//
		$args['shipped_quantity'] = 0;
		$args['flags'] = isset($item['flags'])?$item['flags']:0;
		$args['invoice_id'] = $ciniki['session']['cart']['sapos_id'];
		$args['status'] = 0;
		if( ($ciniki['business']['modules']['ciniki.sapos']['flags']&0x0400) > 0 ) {
			$args['code'] = (isset($item['code'])?$item['code']:'');
			$args['description'] = $item['description'];
		} else {
			$args['code'] = '';
			if( isset($item['code']) && $item['code'] != '' ) {
				$args['description'] = $item['code'] . ' - ' . $item['description'];
			} else {
				$args['description'] = $item['description'];
			}
		}
		$args['unit_amount'] = $item['unit_amount'];
		$args['unit_discount_amount'] = $item['unit_discount_amount'];
		$args['unit_discount_percentage'] = $item['unit_discount_percentage'];
		$args['taxtype_id'] = $item['taxtype_id'];
		$args['notes'] = '';

		//
		// Calculate the final amount for each item in the invoice
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
		$rc = ciniki_sapos_itemCalcAmount($ciniki, array(
			'quantity'=>$args['quantity'],
			'unit_amount'=>$item['unit_amount'],
			'unit_discount_amount'=>$item['unit_discount_amount'],
			'unit_discount_percentage'=>$item['unit_discount_percentage'],
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
				. "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
				. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
		// Add the item
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice_item', $args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1813', 'msg'=>'Internal Error', 'err'=>$rc['err']));
		}
		$item_id = $rc['id'];

		//
		// Check for a callback to the object
		//
		if( $args['object'] != '' && $args['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $args['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'cartAdd');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $business_id, $invoice_id, $args);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				// Update the invoice item with the new object and object_id
				if( isset($rc['object']) && $rc['object'] != $args['object'] ) {
					$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_item', 
						$item_id, $rc, 0x04);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
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

	return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1608', 'msg'=>'Cart does not exist'));
}
?>
