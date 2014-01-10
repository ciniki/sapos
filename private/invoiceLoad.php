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
	//
	// Get the time information for business and user
	//
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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
	$time_format = ciniki_users_timeFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
	
	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStatusMaps');
	$rc = ciniki_sapos_invoiceStatusMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$status_maps = $rc['maps'];

	//
	// Load the transaction source maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'transactionSourceMaps');
	$rc = ciniki_sapos_transactionSourceMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$source_maps = $rc['maps'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	//
	// The the invoice details
	//
	$strsql = "SELECT id, "
		. "invoice_number, "
		. "customer_id, "
		. "status, "
		. "status AS status_text, "
		. "flags, "
		. "invoice_date, "
		. "invoice_date AS invoice_time, "
		. "invoice_date AS invoice_datetime, "
		. "due_date, "
		. "due_date AS due_time, "
		. "due_date AS due_datetime, "
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
		. "shipping_notes, "
		. "shipping_amount, "
		. "subtotal_amount, "
		. "subtotal_discount_percentage, "
		. "subtotal_discount_amount, "
		. "discount_amount, "
		. "total_amount, "
		. "total_savings, "
		. "paid_amount, "
		. "balance_amount, "
		. "invoice_notes, "
		. "internal_notes "
		. "FROM ciniki_sapos_invoices "
		. "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
			'fields'=>array('id', 'invoice_number', 'customer_id', 'status', 'status_text',
				'flags', 'invoice_date', 'invoice_time', 'invoice_datetime', 'due_date',
				'billing_name', 'billing_address1', 'billing_address2', 'billing_city', 
				'billing_province', 'billing_postal', 'billing_country',
				'shipping_name', 'shipping_address1', 'shipping_address2', 'shipping_city', 
				'shipping_province', 'shipping_postal', 'shipping_country', 'shipping_notes',
				'subtotal_amount', 'subtotal_discount_percentage', 'subtotal_discount_amount', 
				'discount_amount', 'shipping_amount', 'total_amount', 'total_savings', 
				'paid_amount', 'balance_amount',
				'invoice_notes', 'internal_notes'),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'invoice_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
				'invoice_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				'due_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'due_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
				'due_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				),
			'maps'=>array('status_text'=>$status_maps)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoices']) || !isset($rc['invoices'][0]['invoice']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1083', 'msg'=>'Invoice does not exist'));
	}
	$invoice = $rc['invoices'][0]['invoice'];
	$invoice['subtotal_discount_percentage'] = (float)$invoice['subtotal_discount_percentage'];

	//
	// Get the customer details
	//
	$invoice['customer'] = array();
	if( $invoice['customer_id'] > 0 ) {
		$strsql = "SELECT ciniki_customers.id, type, "
			. "ciniki_customers.name AS name, "
			. "phone_home, phone_work, phone_fax, phone_cell, "
			. "ciniki_customers.company, "
			. "ciniki_customer_emails.email AS emails "
			. "FROM ciniki_customers "
			. "LEFT JOIN ciniki_customer_emails ON (ciniki_customers.id = ciniki_customer_emails.customer_id "
				. "AND ciniki_customer_emails.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $invoice['customer_id']) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 'name'=>'customer',
				'fields'=>array('id', 'type', 'name', 'company', 
					'phone_home', 'phone_work', 'phone_cell', 'phone_fax', 'emails'),
				'lists'=>array('emails'),
				),
//			array('container'=>'emails', 'fname'=>'email', 'name'=>'email',
//				'fields'=>array('email')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['customers']) && isset($rc['customers'][0]['customer']) ) {
			$invoice['customer'] = $rc['customers'][0]['customer'];
//			$invoice['customer']['name'] = preg_replace('/  /', ' ', $invoice['customer']['name']); 
//			$invoice['customer']['name'] = rtrim($invoice['customer']['name'], ' ');
//			$invoice['customer']['name'] = ltrim($invoice['customer']['name'], ' ');
		}
	}

	//
	// Get the item details
	//
	$strsql = "SELECT ciniki_sapos_invoice_items.id, "	
		. "ciniki_sapos_invoice_items.line_number, "
		. "ciniki_sapos_invoice_items.status, "
		. "ciniki_sapos_invoice_items.object, "
		. "ciniki_sapos_invoice_items.object_id, "
		. "ciniki_sapos_invoice_items.description, "
		. "ciniki_sapos_invoice_items.quantity, "
		. "ROUND(ciniki_sapos_invoice_items.unit_amount, 2) AS unit_amount, "
		. "ROUND(ciniki_sapos_invoice_items.unit_discount_amount, 2) AS unit_discount_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_percentage, "
		. "ROUND(ciniki_sapos_invoice_items.subtotal_amount, 2) AS subtotal_amount, "
		. "ROUND(ciniki_sapos_invoice_items.discount_amount, 2) AS discount_amount, "
		. "ROUND(ciniki_sapos_invoice_items.total_amount, 2) AS total_amount, "
		. "ciniki_sapos_invoice_items.notes, "
		. "IFNULL(ciniki_tax_types.name, '') AS taxtype_name "
		. "FROM ciniki_sapos_invoice_items "
		. "LEFT JOIN ciniki_tax_types ON (ciniki_sapos_invoice_items.taxtype_id = ciniki_tax_types.id "
			. "AND ciniki_tax_types.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY ciniki_sapos_invoice_items.line_number, ciniki_sapos_invoice_items.date_added "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'line_number', 'status',
				'object', 'object_id',
				'description', 'quantity', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 
				'subtotal_amount', 'discount_amount', 'total_amount', 'notes', 'taxtype_name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items']) ) {
		$invoice['items'] = array();
	} else {
		$invoice['items'] = $rc['items'];
		foreach($invoice['items'] as $iid => $item) {
			$invoice['items'][$iid]['item']['unit_discount_percentage'] = (float)$item['item']['unit_discount_percentage'];
			$invoice['items'][$iid]['item']['quantity'] = (float)$item['item']['quantity'];
			$invoice['items'][$iid]['item']['unit_discount_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $item['item']['unit_discount_amount'], $intl_currency);
			$invoice['items'][$iid]['item']['unit_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $item['item']['unit_amount'], $intl_currency);
			$invoice['items'][$iid]['item']['subtotal_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $item['item']['subtotal_amount'], $intl_currency);
			$invoice['items'][$iid]['item']['discount_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $item['item']['discount_amount'], $intl_currency);
			$invoice['items'][$iid]['item']['total_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $item['item']['total_amount'], $intl_currency);
		}
	}

	// 
	// Get the taxes
	//
	$strsql = "SELECT id, "	
		. "line_number, "
		. "description, "
		. "ROUND(amount, 2) AS amount "
		. "FROM ciniki_sapos_invoice_taxes "
		. "WHERE ciniki_sapos_invoice_taxes.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_taxes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY line_number, date_added "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'taxes', 'fname'=>'id', 'name'=>'tax',
			'fields'=>array('id', 'line_number', 'description', 'amount')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['taxes']) ) {
		$invoice['taxes'] = array();
		$invoice['taxes_amount'] = 0;
	} else {
		$invoice['taxes'] = $rc['taxes'];
		$invoice['taxes_amount'] = 0;
		foreach($rc['taxes'] as $tid => $tax) {
			if( $tax['tax']['amount'] > 0 ) {
				$invoice['taxes_amount'] = bcadd($invoice['taxes_amount'], $tax['tax']['amount'], 2);
			} 
			$invoice['taxes'][$tid]['tax']['amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $tax['tax']['amount'], $intl_currency);
		}
	}

	//
	// Get the transactions
	//
	$strsql = "SELECT id, "	
		. "transaction_type, "
		. "transaction_type AS transaction_type_text, "
		. "transaction_date, "
		. "source, "
		. "source AS source_text, "
		. "customer_amount, "
		. "transaction_fees, "
		. "business_amount, "
		. "notes "
		. "FROM ciniki_sapos_transactions "
		. "WHERE ciniki_sapos_transactions.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_transactions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY transaction_date "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'transactions', 'fname'=>'id', 'name'=>'transaction',
			'fields'=>array('id', 'transaction_type', 'transaction_type_text', 'transaction_date',
				'source', 'source_text', 
				'customer_amount', 'transaction_fees', 'business_amount', 'notes'),
			'maps'=>array(
				'source_text'=>$source_maps,
				'transaction_type_text'=>array('10'=>'Deposit', '20'=>'Payment', '60'=>'Refund'),
				),
			'utctotz'=>array('transaction_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['transactions']) ) {
		$invoice['transactions'] = array();
//		$invoice['balance_amount'] = $invoice['total_amount'];
	} else {
		$invoice['transactions'] = $rc['transactions'];
		//
		// Sum up the transactions for a current balance
		//
//		$balance = $invoice['total_amount'];
		foreach($rc['transactions'] as $tid => $transaction) {	
//			if( $transaction['transaction']['transaction_type'] == 10 
//				|| $transaction['transaction']['transaction_type'] == 20 ) {
//				$balance = bcsub($balance, $transaction['transaction']['customer_amount'], 4);
//			} elseif( $transaction['transaction']['transaction_type'] == 60 ) {
//				$balance = bcadd($balance, $transaction['transaction']['customer_amount'], 4);
//			}
			$invoice['transactions'][$tid]['transaction']['customer_amount'] = numfmt_format_currency(
				$intl_currency_fmt, $transaction['transaction']['customer_amount'], $intl_currency);
			$invoice['transactions'][$tid]['transaction']['business_amount'] = numfmt_format_currency(
				$intl_currency_fmt, $transaction['transaction']['business_amount'], $intl_currency);
		}
//		$invoice['balance_amount'] = numfmt_format_currency(
//			$intl_currency_fmt, doubleval($balance), $intl_currency);
	}

	//
	// Format the currency numbers
	//
	$invoice['subtotal_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['subtotal_amount'], $intl_currency);
	$invoice['subtotal_discount_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['subtotal_discount_amount'], $intl_currency);
	$invoice['discount_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['discount_amount'], $intl_currency);
	$invoice['shipping_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['shipping_amount'], $intl_currency);
	$invoice['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['total_amount'], $intl_currency);
	$invoice['total_savings_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['total_savings'], $intl_currency);
	$invoice['taxes_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['taxes_amount'], $intl_currency);
	$invoice['paid_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['paid_amount'], $intl_currency);
	$invoice['balance_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
		$invoice['balance_amount'], $intl_currency);

	return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
