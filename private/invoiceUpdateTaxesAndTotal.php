<?php
//
// Description
// -----------
// This function will update the taxes for an invoice.  Taxes may be added or removed based on the items
// in the invoice.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_updateInvoiceTaxesAndTotal($ciniki, $business_id, $invoice_id) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'taxes', 'private', 'calcInvoiceTaxes');

	//
	// Get the invoice details, so we know what taxes are applicable for the invoice date
	//
	$strsql = "SELECT status, invoice_date, shipping_amount, sub_total_amount, total_amount "
		. "FROM ciniki_sapos_invoices "
		. "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoice']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1080', 'msg'=>'Unable to find invoice'));
	}

	//
	// Setup the invoice hash to be passed to ciniki.taxes
	//
	$invoice = array(
		'status'=>$rc['invoice']['status'],
		'date'=>$rc['invoice']['invoice_date'],
		'shipping_amount'=>$rc['invoice']['shipping_amount'],
		'sub_total_amount'=>$rc['invoice']['sub_total_amount'],
		'total_amount'=>$rc['invoice']['total_amount'],
		'items'=>array(),
		);

	//
	// Get the items from the invoice
	//
	$strsql = "SELECT id, quantity, unit_amount, taxtype_id "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$items = $rc['rows'];

	//
	// Build the hash of invoice details and items to pass to ciniki.taxes for tax calculations
	//
	$invoice_sub_total_amount = 0;
	foreach($items as $iid => $item) {
		$invoice['items'][] = array(
			'id'=>$item['id'],
			'quantity'=>$item['quantity'],
			'unit_amount'=>$item['unit_amount'],
			'taxtype_id'=>$item['taxtype_id'],
			);
		$invoice['subtotal_amount'] += ($item['quantity'] * $item['unit_amount']);
	}

	//
	// Pass to the taxes module to calculate the taxes
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'taxes', 'private', 'calcInvoiceTaxes');
	$rc = ciniki_taxes_calcInvoiceTaxes($ciniki, $business_id, $invoice);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$business_taxes = $rc['taxes'];

	//
	// Get the existing taxes for the invoice
	//
	$strsql = "SELECT id, uuid, rate_id, description, amount "
		. "FROM ciniki_sapos_invoice_taxes "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.sapos', 'taxes', 'rate_id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$invoice_taxes = array();
	if( isset($rc['taxes']) ) {
		$invoice_taxes = $rc['taxes'];
	}
	
	//
	// Check if invoice taxes need to be updated or added 
	//
	$invoice_tax_amount = 0;
	foreach($business_taxes as $tid => $tax) {
		$tax_amount = $tax['calculated_items_amount'] + $tax['calculated_invoice_amount'];
		if( isset($invoice_taxes[$tid]) ) {
			$args = array();
			// Update tax if the amount is different
			if( $tax_amount != $invoice_taxes[$tid]['amount'] ) {
				$args['amount'] = $tax_amount;
			}
			// Check if the name is different, perhaps it was updated
			if( $tax['name'] != $invoice_taxes[$id]['description'] ) {
				$args['description'] = $tax['name'];
			}
			if( count($args) > 0 ) {
				$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_tax', 
					$invoice_taxes[$tid]['id'], $args, 0x04);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		} else {
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice_tax', 
				array(
					'invoice_id'=>$invoice_id,
					'tax_id'=>$tid,
					'line_number'=>0,
					'description'=>$tax['name'],
					'amount'=>$tax_amount,
					), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
		//
		// Keep track of the total taxes for the invoice
		//
		$invoice_tax_amount += $tax_amount;
	}

	//
	// Check if any taxes are no longer applicable
	//
	foreach($invoice_taxes as $tid => $tax) {
		if( !isset($business_taxes[$tid]) ) {
			// Remove the tax
			$rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.sapos.invoice_tax', $tax['id'], $tax['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Update the subtotal and totals
	//
	$invoice_sub_total_amount += $invoice['shipping_amount'];
	$invoice_total_amount = $invoice_sub_total_amount + $invoice_tax_amount;

	$args = array();
	if( $invoice_sub_total_amount != $invoice['sub_total_amount'] ) {
		$args['sub_total_amount'] = $invoice_sub_total_amount;
	}
	if( $invoice_total_amount != $invoice['total_amount'] ) {
		$args['total_amount'] = $invoice_total_amount;
	}
	if( count($args) > 0 ) {
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice', 
			$invoice_id, $args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	return array('stat'=>'ok');
}
?>
