<?php
//
// Description
// ===========
// This method will return the detail for a transaction for an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_shipmentLoad(&$ciniki, $business_id, $shipment_id) {
	//
	// Load the date formating
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	
	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
	$rc = ciniki_sapos_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Get the shipment details
	//
	$strsql = "SELECT ciniki_sapos_shipments.id, "
		. "ciniki_sapos_shipments.invoice_id, "
		. "ciniki_sapos_shipments.shipment_number, "
		. "ciniki_sapos_shipments.status, "
		. "ciniki_sapos_shipments.status AS status_text, "
		. "ciniki_sapos_shipments.flags, "
		. "ciniki_sapos_shipments.flags AS flags_text, "
		. "ciniki_sapos_shipments.weight, "
		. "ciniki_sapos_shipments.weight_units, "
		. "ciniki_sapos_shipments.weight_units AS weight_units_text, "
		. "ciniki_sapos_shipments.shipping_company, "
		. "ciniki_sapos_shipments.tracking_number, "
		. "ciniki_sapos_shipments.td_number, "
		. "ciniki_sapos_shipments.boxes, "
		. "ciniki_sapos_shipments.dimensions, "
		. "ciniki_sapos_shipments.pack_date, "
		. "ciniki_sapos_shipments.ship_date, "
		. "ciniki_sapos_shipments.freight_amount "
		. "FROM ciniki_sapos_shipments "
		. "WHERE ciniki_sapos_shipments.id = '" . ciniki_core_dbQuote($ciniki, $shipment_id) . "' "
		. "AND ciniki_sapos_shipments.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'shipments', 'fname'=>'id', 'name'=>'shipment',
			'fields'=>array('id', 'invoice_id', 'shipment_number', 'status', 'status_text', 
				'flags', 'flags_text', 
				'weight', 'weight_units', 'weight_units_text', 
				'shipping_company', 'tracking_number', 'td_number', 'boxes', 'dimensions', 
				'pack_date', 'ship_date', 'freight_amount'),
			'maps'=>array('status_text'=>$maps['shipment']['status'],
				'weight_units_text'=>$maps['shipment']['weight_units']),
			'flags'=>array('flags_text'=>array('1'=>'TD')),
			'utctotz'=>array('pack_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'ship_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['shipments']) && isset($rc['shipments'][0]['shipment']) ) {
		$shipment = $rc['shipments'][0]['shipment'];
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1926', 'msg'=>'Shipment does not exist'));
	}

	//
	// Check to make sure the invoice belongs to the salesrep
	//
	if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
		$strsql = "SELECT id "
			. "FROM ciniki_sapos_invoices "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2028', 'msg'=>'Permission denied'));
		}
	}

	//
	// Format elements
	//
	$shipment['weight'] = (float)$shipment['weight'];
	$shipment['freight_amount'] = numfmt_format_currency(
		$intl_currency_fmt, $shipment['freight_amount'], $intl_currency);

	//
	// Get a few details about the invoice
	//
	$strsql = "SELECT ciniki_sapos_invoices.id, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "ciniki_sapos_invoices.po_number, "
		. "ciniki_sapos_invoices.status, "
		. "ciniki_sapos_invoices.status AS status_text, "
		. "ciniki_sapos_invoices.customer_notes, "
		. "ciniki_customers.display_name "
		. "FROM ciniki_sapos_invoices "
		. "LEFT JOIN ciniki_customers ON ("
			. "ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
		. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
			'fields'=>array('id', 'invoice_number', 'po_number',
				'status', 'status_text', 'customer_name'=>'display_name', 'customer_notes'),
			'maps'=>array('status_text'=>$maps['invoice']['status'])),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['invoices'][0]['invoice']) ) {
		$invoice = $rc['invoices'][0]['invoice'];
		$shipment['invoice_number'] = $invoice['invoice_number'];
		$shipment['invoice_po_number'] = $invoice['po_number'];
		$shipment['invoice_status_text'] = $invoice['status_text'];
		if( isset($invoice['customer_name']) ) {
			$shipment['customer_name'] = $invoice['customer_name'];
		} else {
			$shipment['customer_name'] = '';
		}
		$shipment['customer_notes'] = $invoice['customer_notes'];
	}

	//
	// Get the items in the invoice
	//
	$strsql = "SELECT ciniki_sapos_invoice_items.id, "
		. "ciniki_sapos_invoice_items.object, "
		. "ciniki_sapos_invoice_items.object_id, "
		. "ciniki_sapos_invoice_items.code, "
		. "ciniki_sapos_invoice_items.description, "
		. "ciniki_sapos_invoice_items.quantity, "
		. "ciniki_sapos_invoice_items.shipped_quantity, "
		. "ciniki_sapos_invoice_items.notes "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'object', 'object_id', 'code', 'description', 
				'quantity', 'shipped_quantity', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['items']) ) {
		$shipment['invoice_items'] = $rc['items'];
	} else {
		$shipment['invoice_items'] = array();
	}

	//
	// Get the inventory available for the items
	//
	$objects = array();
	$object_ids = array();
	foreach($shipment['invoice_items'] as $iid => $item) {
		$shipment['invoice_items'][$iid]['item']['required_quantity'] = $item['item']['quantity'] - $item['item']['shipped_quantity'];
		// Build array of items by id to use in setting up shipment item descriptions below
		$invoice_items[$item['item']['id']] = $item['item'];
		if( $item['item']['object'] != '' ) {
			if( !isset($object_ids[$item['item']['object']]) ) {
				$object_ids[$item['item']['object']] = array();
			}
			$objects[$item['item']['object']][] = $item['item']['object_id'];
		}
	}

	//
	// Get the reserved quantities for objects
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getReservedQuantities');
	$reserved_quantities = array();
	foreach($objects as $object => $object_ids) {
		$rc = ciniki_sapos_getReservedQuantities($ciniki, $business_id,
			$object, $object_ids, $shipment['invoice_id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$reserved_quantities[$object] = $rc['quantities'];
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
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2014', 'msg'=>'Unable to get inventory levels.', 'err'=>$rc['err']));
			}
			//
			// Update the inventory levels for the invoice items
			//
			$quantities = $rc['quantities'];
			foreach($shipment['invoice_items'] as $iid => $item) {
				if( $item['item']['object'] == $object 
					&& isset($quantities[$item['item']['object_id']]) 
					) {
					$shipment['invoice_items'][$iid]['item']['inventory_quantity'] = $quantities[$item['item']['object_id']]['inventory_quantity'];
					if( isset($reserved_quantities[$object][$item['item']['object_id']]['quantity_reserved']) ) {
						$shipment['invoice_items'][$iid]['item']['inventory_reserved'] = (float)$reserved_quantities[$object][$item['item']['object_id']]['quantity_reserved'];
					}
				}
			}
		}
	}

	//
	// Get the items in the shipment
	//
	$strsql = "SELECT ciniki_sapos_shipment_items.id, "
		. "ciniki_sapos_shipment_items.shipment_id, "
		. "ciniki_sapos_shipment_items.item_id, "
		. "ciniki_sapos_shipment_items.quantity, "
		. "ciniki_sapos_shipment_items.notes "
		. "FROM ciniki_sapos_shipment_items "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND shipment_id = '" . ciniki_core_dbQuote($ciniki, $shipment_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'shipment_id', 'item_id', 'quantity', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['items']) ) {
		$shipment['items'] = $rc['items'];
		foreach($shipment['items'] as $iid => $item) {
			$shipment['items'][$iid]['item']['quantity'] = (float)$item['item']['quantity'];
			if( isset($invoice_items[$item['item']['item_id']]) ) {
				$shipment['items'][$iid]['item']['code'] = $invoice_items[$item['item']['item_id']]['code'];
				$shipment['items'][$iid]['item']['description'] = $invoice_items[$item['item']['item_id']]['description'];
			}
		}
	} else {
		$shipment['items'] = array();
	}

	return array('stat'=>'ok', 'shipment'=>$shipment);
}
?>
