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
function ciniki_sapos_web_cartItemDelete($ciniki, $settings, $business_id, $args) {

	//
	// Check that a cart does not exist
	//
	if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
		$invoice_id = $ciniki['session']['cart']['sapos_id'];	
		//
		// Check that an item was specified
		//
		if( !isset($args['item_id']) || $args['item_id'] == '' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1701', 'msg'=>'No item specified'));
		}

		//
		// Get the existing item details
		//
		$strsql = "SELECT id, invoice_id, object, object_id, "
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1702', 'msg'=>'Unable to locate the invoice item'));
		}
		$item = $rc['item'];

		//
		// Check for a callback for the item object
		//
		if( $item['object'] != '' && $item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemDelete');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $business_id, $invoice_id, $item);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
		
		//
		// Remove the item
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
		$rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.sapos.invoice_item', $args['item_id'], $args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
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

	return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'1703', 'msg'=>'Cart does not exist'));
}
?>
