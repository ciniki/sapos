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
function ciniki_sapos_updateInvoiceTaxes($ciniki, $business_id, $invoice_id) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'businessDateTaxes');

	//
	// Get the invoice details, so we know what taxes are applicable for the invoice date
	//
	$strsql = "SELECT status, invoice_date, total_amount "
		. "FROM ciniki_sapos_invoices "
		. "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoice']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Unable to find invoice'));
	}
	$invoice = $rc['invoice'];

	//
	// Get the taxes for a business, that are for the time period the invoice is in.
	//
	$rc = ciniki_sapos_businessDateTaxes($ciniki, $business_id, $invoice['invoice_date']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['taxes']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Unable to load taxes'));
	}
	$business_taxes = $rc['taxes'];		// Taxes in array by id
	// 
	// Set the calculated amount to zero
	//
	foreach($business_taxes as $tid => $tax) {
		$business_taxes[$tid]['calculated_items_amount'] = 0;
		$business_taxes[$tid]['calculated_invoice_amount'] = 0;
	}

	//
	// Get the items from the invoice
	//
	$strsql = "SELECT id, quantity, unit_amount, amount, taxtypes "
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
	// Get the existing taxes for the invoice
	//
	$strsql = "SELECT id, uuid, tax_id, description, amount "
		. "FROM ciniki_sapos_invoice_taxes "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.sapos', 'taxes', 'tax_id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$invoice_taxes = array();
	if( isset($rc['taxes']) ) {
		$invoice_taxes = $rc['taxes'];
	}
	
	//
	// Calculate what the taxes should be
	//
	foreach($items as $iid => $item) {
		foreach($business_taxes as $tid => $tax) {
			//
			// Check if the tax should be applied
			//
			if( ($tax['taxtypes']&$item['taxtypes']) > 0 ) {
				if( $tax['item_percentage'] > 0 ) {
					$business_taxes[$tid]['calculated_items_amount'] += 
						($item['quantity'] * $item['unit_amount'])*($tax['item_percentage']/100);
				}
				if( $tax['item_amount'] > 0 ) {
					$business_taxes[$tid]['calculated_items_amount'] += $item['quantity']*$tax['item_amount'];
				}
				if( $tax['invoice_amount'] > 0 ) {
					$business_taxes[$tid]['calculated_invoice_amount'] = $tax['invoice_amount'];
				}
			}
		}
	}

	//
	// Check if invoice taxes need to be updated or added 
	//
	foreach($business_taxes as $tid => $tax) {
		$tax_amount = $tax['calculated_items_amount'] + $tax['calculated_invoice_amount'];
		if( isset($invoice_taxes[$tid]) ) {
			// Update tax if the amount is different
			if( $tax_amount != $invoice_taxes[$tid]['amount'] ) {
				$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_tax', $invoice_taxes[$tid]['id'], 
					array('amount'=>$tax_amount), 0x04);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		} else {
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice_tax', 
				array('amount'=>$tax_amount), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
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

	return array('stat'=>'ok');
}
?>
