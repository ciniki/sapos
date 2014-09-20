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
function ciniki_sapos_invoiceUpdatePrices($ciniki, $business_id, $invoice_id, $args) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');

	//
	// Get the items from the invoice that have an object defined
	//
	$strsql = "SELECT id, object, object_id, price_id, quantity, "
		. "unit_amount, unit_discount_amount, unit_discount_percentage "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['rows']) ) {
		// No items to update
		return array('stat'=>'ok');
	}
	$items = $rc['rows'];

	//
	// Update the item prices
	//
	foreach($items as $item) {
		//
		// Get the new price for the object
		//
		if( $item['object'] != '' && $item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemLookup');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $business_id, array(
					'object'=>$item['object'],
					'object_id'=>$item['object_id'],
					'pricepoint_id'=>$args['pricepoint_id'],
					));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				if( isset($rc['item']) ) {
					if( $rc['item']['price_id'] != $item['price_id'] ) {
						$update_args['price_id'] = $rc['item']['price_id'];
					}
					if( $rc['item']['unit_amount'] != $item['unit_amount'] ) {
						$update_args['unit_amount'] = $rc['item']['unit_amount'];
					}
					if( $rc['item']['unit_discount_amount'] > 0
						&& $rc['item']['unit_discount_amount'] != $item['unit_discount_amount'] ) {
						$update_args['unit_discount_amount'] = $rc['item']['unit_discount_amount'];
					}
					if( $rc['item']['unit_discount_percentage'] > 0 
						&& $rc['item']['unit_discount_percentage'] != $item['unit_discount_percentage'] ) {
						$update_args['unit_discount_percentage'] = $rc['item']['unit_discount_percentage'];
					}
				}
			}
		}

		//
		// Calculate new item totals
		//
		$rc = ciniki_sapos_itemCalcAmount($ciniki, array(
			'quantity'=>$item['quantity'],
			'unit_amount'=>(isset($update_args['unit_amount'])?$update_args['unit_amount']:$item['unit_amount']),
			'unit_discount_amount'=>(isset($update_args['unit_discount_amount'])?$update_args['unit_discount_amount']:$item['unit_discount_amount']),
			'unit_discount_percentage'=>(isset($update_args['unit_discount_percentage'])?$update_args['unit_discount_percentage']:$item['unit_discount_percentage']),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$update_args['subtotal_amount'] = $rc['subtotal'];
		$update_args['discount_amount'] = $rc['discount'];
		$update_args['total_amount'] = $rc['total'];

		//
		// Update the item 
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_item', 
			$item['id'], $update_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}
	}



	return array('stat'=>'ok');
}
?>
