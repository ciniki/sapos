<?php
//
// Description
// -----------
// This function will return the list of invoices for a customer.
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
// method:				The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_invoiceLoad($ciniki, $business_id, $invoice_id) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// The the invoice details
	//
	$strsql = "SELECT id, "
		. "invoice_number, "
		. "customer_id, "
		. "status, "
		. "status AS status_text, "
		. "DATE_FORMAT(invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS invoice_date, "
		. "DATE_FORMAT(due_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS due_date, "
		. "billing_name, "
		. "billing_address1, "
		. "billing_address2, "
		. "billing_city, "
		. "billing_province, "
		. "billing_postal, "
		. "billing_country, "
		. "shipping_name, "
		. "shipping_address1, "
		. "shipping_address2, "
		. "shipping_city, "
		. "shipping_province, "
		. "shipping_postal, "
		. "shipping_country, "
		. "total_amount, "
		. "invoice_notes, "
		. "internal_notes, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
		. "FROM ciniki_sapos_invoices "
		. "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id',
			'fields'=>array('id', 'invoice_number', 'customer_id', 'status', 'status_text',
				'invoice_date', 'due_date',
				'billing_name', 'billing_address1', 'billing_address2', 'billing_city', 
				'billing_province', 'billing_postal', 'billing_country',
				'shipping_name', 'shipping_address1', 'shipping_address2', 'shipping_city', 
				'shipping_province', 'shipping_postal', 'shipping_country',
				'total_amount', 'invoice_notes', 'internal_notes', 'date_added'),
			'maps'=>array('status_text'=>array('10'=>'Creating', '20'=>'Entered', 
				'40'=>'Partial Payment', '50'=>'Paid', '60'=>'Void')),
			)
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoices']) || !isset($rc['invoices'][$invoice_id]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Invoice does not exist'));
	}
	$invoice = $rc['invoices'][$invoice_id];

	//
	// Get the customer details
	//
	$invoice['customer'] = array();
	if( $invoice['customer_id'] > 0 ) {
		$strsql = "SELECT id, CONCAT_WS(' ', prefix, first, middle, last, suffix) AS name, "	
			. "company "
			. "FROM ciniki_customers "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $invoice['customer_id']) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
				'fields'=>array('id', 'name', 'company')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['customers']) && isset($rc['customers'][0]['customer']) ) {
			$invoice['customer'] = $rc['customers'][0]['customer'];
		}
	}

	//
	// Get the item details
	//
	$strsql = "SELECT id, "	
		. "line_number, "
		. "status, "
		. "object, "
		. "object_id, "
		. "description, "
		. "quantity, "
		. "unit_amount, "
		. "amount, "
		. "taxes, "
		. "notes, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY line_number, date_added "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'line_number', 'status',
				'object', 'object_id',
				'description', 'quantity', 'unit_amount', 'amount', 'taxes', 'notes', 'date_added')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items']) ) {
		$invoice['items'] = array();
	} else {
		$invoice['items'] = $rc['items'];
	}

	// 
	// Get the taxes
	//
	$strsql = "SELECT id, "	
		. "line_number, "
		. "description, "
		. "percentage, "
		. "amount "
		. "FROM ciniki_sapos_invoice_taxes "
		. "WHERE ciniki_sapos_invoice_taxes.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_taxes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY line_number, date_added "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'taxes', 'fname'=>'id', 'name'=>'tax',
			'fields'=>array('id', 'line_number', 'description',
				'percentage', 'amount')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['taxes']) ) {
		$invoice['taxes'] = array();
	} else {
		$invoice['taxes'] = $rc['taxes'];
	}

	//
	// Get the transactions
	//
	$strsql = "SELECT id, "	
		. "transaction_type, "
		. "DATE_FORMAT(CONVERT_TZ(transaction_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS transaction_date "
		. "source, "
		. "customer_amount, "
		. "transaction_fees, "
		. "business_amount, "
		. "notes, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
		. "FROM ciniki_sapos_invoice_transactions "
		. "WHERE ciniki_sapos_invoice_transactions.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_transactions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY transaction_date "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'transactions', 'fname'=>'id', 'name'=>'transaction',
			'fields'=>array('id', 'transaction_type', 'transaction_date',
				'source', 'source_text', 'customer_amount', 'transaction_fees', 'business_amount', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['transactions']) ) {
		$invoice['transactions'] = array();
	} else {
		$invoice['transactions'] = $rc['transactions'];
	}

	return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
