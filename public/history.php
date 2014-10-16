<?php
//
// Description
// -----------
// This method will return the list of actions that were applied to an element of an invoice. 
// This method is typically used by the UI to display a list of changes that have occured 
// on an element through time. This information can be used to revert elements to a previous value.
//
// Arguments
// ---------
//
// Returns
// -------
// <history>
// <action user_id="2" date="May 12, 2012 10:54 PM" value="Invoice Name" age="2 months" user_display_name="Andrew" />
// ...
// </history>
//
function ciniki_sapos_history($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'object'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object'), 
		'object_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object ID'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
	$rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.history');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check to make sure the invoice belongs to the salesrep
	//
	if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 
		&& $args['object'] == 'ciniki.sapos.invoice'
		) {
		$strsql = "SELECT id "
			. "FROM ciniki_sapos_invoices "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2030', 'msg'=>'Permission denied'));
		}
	}

	//
	// Check to make sure the invoice belongs to the salesrep
	//
	if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 
		&& $args['object'] == 'ciniki.sapos.invoice_item'
		) {
		$strsql = "SELECT ciniki_sapos_invoices.id "
			. "FROM ciniki_sapos_invoice_items, ciniki_sapos_invoices "
			. "WHERE ciniki_sapos_invoice_items.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_sapos_invoice_items.invoice_id = ciniki_sapos_invoices.id "
			. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_sapos_invoices.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2026', 'msg'=>'Permission denied'));
		}
	}

	if( $args['object'] == 'ciniki.sapos.invoice' ) {
		if( $args['field'] == 'invoice_date' || $args['field'] == 'due_date' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_invoices', $args['object_id'], $args['field'], 'utcdate');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_invoices', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.invoice_item' ) {
		if( $args['field'] == 'unit_amount' || $args['field'] == 'unit_discount_amount' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_invoice_items', $args['object_id'], $args['field'], 'currency');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_invoice_items', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.qi_item' ) {
		if( $args['field'] == 'unit_amount' || $args['field'] == 'unit_discount_amount' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_qi_items', $args['object_id'], $args['field'], 'currency');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_qi_items', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.transaction' ) {
		if( $args['field'] == 'transaction_date' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_transactions', $args['object_id'], $args['field'], 
				'utcdatetime');
		} 
		if( $args['field'] == 'customer_amount' 
			|| $args['field'] == 'transaction_fees' 
			|| $args['field'] == 'business_amount' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_transactions', $args['object_id'], $args['field'], 
				'currency');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_transactions', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.shipment' ) {
		if( $args['field'] == 'ship_date' || $args['field'] == 'pack_date' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_shipments', $args['object_id'], $args['field'], 
				'utcdate');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_shipments', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.shipment_item' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_shipment_items', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.expense' ) {
		if( $args['field'] == 'invoice_date' || $args['field'] == 'paid_date' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_expenses', $args['object_id'], $args['field'], 'utcdate');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_expenses', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.expense_item' ) {
		if( $args['field'] == 'amount' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_expense_items', $args['object_id'], $args['field'], 
				'currency');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_expense_items', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.mileage' ) {
		if( $args['field'] == 'travel_date' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_mileage', $args['object_id'], $args['field'], 'utcdate');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_mileage', $args['object_id'], $args['field']);
	}
	elseif( $args['object'] == 'ciniki.sapos.mileage_rate' ) {
		if( $args['field'] == 'rate' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_mileage_rates', $args['object_id'], $args['field'], 
				'currency');
		} 
		elseif( $args['field'] == 'start_date' || $args['field'] == 'end_date' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
			return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
				$args['business_id'], 'ciniki_sapos_mileage_rates', $args['object_id'], $args['field'], 'utcdate');
		} 
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', 
			$args['business_id'], 'ciniki_sapos_mileage_rates', $args['object_id'], $args['field']);
	}
}
?>
