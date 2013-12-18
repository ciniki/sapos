<?php
//
// Description
// -----------
// This function will return the list of invoices for the specified object/object_id.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_objectInvoices($ciniki, $business_id, $object, $object_id, $limit=0) {

//	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'timezoneOffset');
//	$utc_offset = ciniki_businesses_timezoneOffset($ciniki);
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
	// Load the status maps for the text descriptions
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStatusMaps');
	$rc = ciniki_sapos_invoiceStatusMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$status_maps = $rc['maps'];

	//
	// Get the invoices where an object has been invoiced for
	//
	$strsql = "SELECT ciniki_sapos_invoices.id, "
		. "CONCAT_WS(' ', ciniki_customers.prefix, ciniki_customers.first, ciniki_customers.middle, ciniki_customers.last, ciniki_customers.suffix) AS customer_name, "
		. "ciniki_customers.first, ciniki_customers.last, ciniki_customers.company, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "ciniki_sapos_invoices.invoice_date, "
		. "ciniki_sapos_invoices.status, "
		. "ciniki_sapos_invoices.status AS status_text, "
		. "ciniki_sapos_invoice_items.unit_amount, "
		. "ciniki_sapos_invoice_items.total_amount AS item_total_amount, "
		. "ciniki_sapos_invoices.total_amount "
		. "FROM ciniki_sapos_invoice_items "
		. "LEFT JOIN ciniki_sapos_invoices ON (ciniki_sapos_invoice_items.invoice_id = ciniki_sapos_invoices.id "
			. "AND ciniki_sapos_invoices.status < 60 "
			. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoice_items.object = '" . ciniki_core_dbQuote($ciniki, $object) . "' "
		. "AND ciniki_sapos_invoice_items.object_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY ciniki_sapos_invoices.invoice_date DESC "
		. "";
	if( $limit > 0 ) {
		$strsql .= "LIMIT $limit ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
			'fields'=>array('id', 'customer_name', 'first', 'last', 'company', 
				'invoice_number', 'invoice_date', 'status', 'status_text', 
				'item_amount'=>'item_total_amount', 'total_amount'),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			'maps'=>array('status_text'=>$status_maps)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['invoices']) ) {
		foreach($rc['invoices'] as $iid => $invoice) {
			$rc['invoices'][$iid]['invoice']['customer_name'] = ltrim(rtrim(preg_replace('/  /', ' ', $invoice['invoice']['customer_name']), ' '), ' ');
			$rc['invoices'][$iid]['invoice']['item_amount'] = numfmt_format_currency($intl_currency_fmt,
				$invoice['invoice']['item_amount'], $intl_currency);
			$rc['invoices'][$iid]['invoice']['total_amount'] = numfmt_format_currency($intl_currency_fmt,
				$invoice['invoice']['total_amount'], $intl_currency);
		}
		return array('stat'=>'ok', 'invoices'=>$rc['invoices']);
	}

	return array('stat'=>'ok', 'invoices'=>array());
}
?>
