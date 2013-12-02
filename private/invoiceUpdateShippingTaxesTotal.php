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
function ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $business_id, $invoice_id) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

	//
	// Get the invoice details, so we know what taxes are applicable for the invoice date
	//
	$strsql = "SELECT status, invoice_date, "
		. "shipping_amount, subtotal_amount, "
		. "subtotal_discount_percentage, subtotal_discount_amount, "
		. "discount_amount, "
		. "total_amount, total_savings "
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
		'subtotal_amount'=>$rc['invoice']['subtotal_amount'],
		'subtotal_discount_amount'=>$rc['invoice']['subtotal_discount_amount'],
		'subtotal_discount_percentage'=>$rc['invoice']['subtotal_discount_percentage'],
		'discount_amount'=>$rc['invoice']['discount_amount'],
		'shipping_amount'=>$rc['invoice']['shipping_amount'],
		'total_amount'=>$rc['invoice']['total_amount'],
		'total_savings'=>$rc['invoice']['total_savings'],
		'items'=>array(),
		);

	//
	// Get the items from the invoice
	//
	$strsql = "SELECT id, "
		. "discount_amount, "
		. "total_amount, "
		. "taxtype_id "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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

	$invoice_subtotal_amount = 0;
	$invoice_total_savings = 0;

	//
	// Build the hash of invoice details and items to pass to ciniki.taxes for tax calculations
	//
	if( count($items) > 0 ) {
		foreach($items as $iid => $item) {
			$invoice['items'][] = array(
				'id'=>$item['id'],
				'amount'=>$item['total_amount'],
				'taxtype_id'=>$item['taxtype_id'],
				);
			$invoice_subtotal_amount = bcadd($invoice_subtotal_amount, $item['total_amount'], 4);
			$invoice_total_savings = bcadd($invoice_total_savings, $item['discount_amount'], 4);
		}
	}
	
	//
	// FIXME: Calculate shipping costs, if applicable
	//

	//
	// Pass to the taxes module to calculate the taxes
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'taxes', 'private', 'calcInvoiceTaxes');
	$rc = ciniki_taxes_calcInvoiceTaxes($ciniki, $business_id, $invoice);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$new_taxes = $rc['taxes'];

	//
	// Get the existing taxes for the invoice
	//
	$strsql = "SELECT id, uuid, taxrate_id, description, amount "
		. "FROM ciniki_sapos_invoice_taxes "
		. "WHERE ciniki_sapos_invoice_taxes.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_taxes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.sapos', 'taxes', 'taxrate_id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['taxes']) ) {
		$old_taxes = $rc['taxes'];
	} else {
		$old_taxes = array();
	}
	
	//
	// Check if invoice taxes need to be updated or added 
	//
	$invoice_tax_amount = 0;
	foreach($new_taxes as $tid => $tax) {
		$tax_amount = bcadd($tax['calculated_items_amount'], $tax['calculated_invoice_amount'], 4);
		if( isset($old_taxes[$tid]) ) {
			$args = array();
			$args['amount'] = $tax_amount;
			// Check if the name is different, perhaps it was updated
			if( $tax['name'] != $old_taxes[$tid]['description'] ) {
				$args['description'] = $tax['name'];
			}
			if( count($args) > 0 ) {
				$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_tax', 
					$old_taxes[$tid]['id'], $args, 0x04);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		} else {
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice_tax', 
				array(
					'invoice_id'=>$invoice_id,
					'taxrate_id'=>$tid,
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
		$invoice_tax_amount = bcadd($invoice_tax_amount, $tax_amount, 4);
	}

	//
	// Check if any taxes are no longer applicable
	//
	foreach($old_taxes as $tid => $tax) {
		if( !isset($new_taxes[$tid]) ) {
			// Remove the tax
			$rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.sapos.invoice_tax', $tax['id'], $tax['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check if there are any discounts to be applied for the entire invoice
	//
	$invoice_discount_amount = 0;
	if( $invoice['subtotal_discount_amount'] != 0 ) {
		$invoice_discount_amount = bcadd($invoice_discount_amount, $invoice['subtotal_discount_amount'], 4);
	}
	if( $invoice['subtotal_discount_percentage'] != 0 ) {
		$discount_percent = bcdiv($invoice['subtotal_discount_percentage'], 100, 4);
		if( $invoice_discount_amount > 0 ) {
			$discount_amount = bcmul(
				bcsub($invoice_subtotal_amount, $invoice_discount_amount, 4) , $discount_percent, 4);
		} else {
			$discount_amount = bcmul($invoice_subtotal_amount, $discount_percent, 4);
		}
		$invoice_discount_amount = bcadd($invoice_discount_amount, $discount_amount, 4);
	}
	
	//
	// Update the totals
	//
	// Subtract the discount, add the shipping amount, add the taxes
	$invoice_total_amount = bcsub($invoice_subtotal_amount, $invoice_discount_amount, 4);
	$invoice_total_amount = bcadd($invoice_total_amount, $invoice['shipping_amount'], 4);
	$invoice_total_amount = bcadd($invoice_total_amount, $invoice_tax_amount, 4);

	$invoice_total_savings = bcadd($invoice_total_savings, $invoice_discount_amount, 4);

	$args = array();
	if( $invoice_subtotal_amount != floatval($invoice['subtotal_amount']) ) {
		$args['subtotal_amount'] = $invoice_subtotal_amount;
	}
	if( $invoice_discount_amount != floatval($invoice['discount_amount']) ) {
		$args['discount_amount'] = $invoice_discount_amount;
	}
	if( $invoice_total_amount != floatval($invoice['total_amount']) ) {
		$args['total_amount'] = $invoice_total_amount;
	}
	if( $invoice_total_savings != floatval($invoice['total_savings']) ) {
		$args['total_savings'] = $invoice_total_savings;
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
