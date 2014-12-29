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
function ciniki_sapos_invoiceAddFromRecurring($ciniki, $business_id, $invoice_id) {
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
	$rc = ciniki_sapos_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	//
	// Get the recurring invoice details
	//
	$strsql = "SELECT id, "
		. "invoice_number, "
		. "po_number, "
		. "customer_id, "
		. "salesrep_id, "
		. "invoice_type, "
		. "status, "
		. "payment_status, "
		. "shipping_status, "
		. "manufacturing_status, "
		. "flags, "
		. "invoice_date, "
//		. "invoice_date AS invoice_time, "
//		. "invoice_date AS invoice_datetime, "
		. "due_date, "
//		. "due_date AS due_time, "
//		. "due_date AS due_datetime, "
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
		. "shipping_phone, "
		. "shipping_notes, "
		. "tax_location_id, "
		. "pricepoint_id, "
		. "shipping_amount, "
		. "subtotal_amount, "
		. "subtotal_discount_percentage, "
		. "subtotal_discount_amount, "
		. "discount_amount, "
		. "total_amount, "
		. "total_savings, "
		. "paid_amount, "
		. "balance_amount, "
		. "user_id, "
		. "customer_notes, "
		. "invoice_notes, "
		. "internal_notes, "
		. "submitted_by "
		. "FROM ciniki_sapos_invoices "
		. "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	// Check if only a sales rep
	if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
		$strsql .= "AND ciniki_sapos_invoices.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
	}
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
			'fields'=>array('id', 'invoice_number', 'invoice_type', 'po_number', 'customer_id', 'salesrep_id',
				'status', 'payment_status', 'shipping_status', 'manufacturing_status',
				'flags', 'invoice_date', 'due_date',
				'billing_name', 'billing_address1', 'billing_address2', 'billing_city', 
				'billing_province', 'billing_postal', 'billing_country',
				'shipping_name', 'shipping_address1', 'shipping_address2', 'shipping_city', 
				'shipping_province', 'shipping_postal', 'shipping_country', 'shipping_phone', 'shipping_notes',
				'tax_location_id', 'pricepoint_id', 
				'subtotal_amount', 'subtotal_discount_amount', 'subtotal_discount_percentage', 
				'discount_amount', 'shipping_amount', 'total_amount', 'total_savings', 
				'paid_amount', 'balance_amount', 'user_id',
				'customer_notes', 'invoice_notes', 'internal_notes', 'submitted_by'),
//			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
//				'invoice_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
//				'invoice_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
//				'due_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
//				'due_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
//				'due_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				),
//			'maps'=>array('status_text'=>$maps['invoice']['typestatus'], 
//				'invoice_type_text'=>$maps['invoice']['invoice_type'],
//				'payment_status_text'=>$maps['invoice']['payment_status'],
//				'shipping_status_text'=>$maps['invoice']['shipping_status'],
//				'manufacturing_status_text'=>$maps['invoice']['manufacturing_status'],
//				)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoices']) || !isset($rc['invoices'][0]['invoice']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'2116', 'msg'=>'Invoice does not exist'));
	}
	$invoice = $rc['invoices'][0]['invoice'];

	//
	// Get the item details
	//
	$strsql = "SELECT ciniki_sapos_invoice_items.id, "	
		. "ciniki_sapos_invoice_items.line_number, "
		. "ciniki_sapos_invoice_items.flags, "
		. "ciniki_sapos_invoice_items.status, "
		. "ciniki_sapos_invoice_items.object, "
		. "ciniki_sapos_invoice_items.object_id, "
		. "ciniki_sapos_invoice_items.price_id, "
		. "ciniki_sapos_invoice_items.code, "
		. "ciniki_sapos_invoice_items.description, "
		. "ciniki_sapos_invoice_items.quantity, "
		. "ciniki_sapos_invoice_items.shipped_quantity, "
		. "ciniki_sapos_invoice_items.unit_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_percentage, "
		. "ciniki_sapos_invoice_items.subtotal_amount, "
		. "ciniki_sapos_invoice_items.discount_amount, "
		. "ciniki_sapos_invoice_items.total_amount, "
		. "ciniki_sapos_invoice_items.taxtype_id, "
		. "ciniki_sapos_invoice_items.notes "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY ciniki_sapos_invoice_items.line_number, ciniki_sapos_invoice_items.date_added "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'id',
			'fields'=>array('id', 'line_number', 'flags', 'status',
				'object', 'object_id', 'price_id', 'code', 'description', 'quantity', 'shipped_quantity',
				'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 
				'subtotal_amount', 'discount_amount', 'total_amount', 'taxtype_id', 'notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items']) ) {
		$items = array();
	} else {
		$items = $rc['items'];
	}
	
	//
	// Set the time to business timezone
	//
	$invoice_date = new DateTime($invoice['invoice_date'], new DateTimeZone('UTC'));
	$invoice_date->setTimezone(new DateTimeZone($intl_timezone));

	//
	// Start the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Check if the invoice already exists
	//
	$strsql = "SELECT id, invoice_date "
		. "FROM ciniki_sapos_invoices "
		. "WHERE source_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "AND invoice_date = '" . ciniki_core_dbQuote($ciniki, $invoice['invoice_date']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	if( !isset($rc['num_rows']) || $rc['num_rows'] == 0 ) {
		//
		// Setup lastmonth and lastyear dates
		//
		$dates['lastmonth'] = clone $invoice_date;
		$dates['lastmonth']->sub(new DateInterval('P1M'));
		$dates['lastyear'] = clone $invoice_date;
		$dates['lastyear']->sub(new DateInterval('P1Y'));
		$dates['thismonth'] = clone $invoice_date;
		$dates['nextmonth'] = clone $invoice_date;
		$dates['nextmonth']->add(new DateInterval('P1M'));
		$dates['nextyear'] = clone $invoice_date;
		$dates['nextyear']->add(new DateInterval('P1Y'));

		//
		// Create the invoice
		//
		$new_invoice = $invoice;
		$new_invoice['id'] = 0;
		$new_invoice['source_id'] = $invoice_id;
		// Set the date back to UTC
		$new_invoice_date = clone($invoice_date);
		$new_invoice_date->setTimezone(new DateTimeZone('UTC'));
		$new_invoice['invoice_date'] = date_format($new_invoice_date, 'Y-m-d H:i:s');
		if( $new_invoice['invoice_type'] > 10 && $new_invoice['invoice_type'] < 20 ) {
			$new_invoice['invoice_type'] = 10;
		}

		$strsql = "SELECT MAX(CAST(invoice_number AS UNSIGNED)) AS curmax "
			. "FROM ciniki_sapos_invoices "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'max_num');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}
		if( isset($rc['max_num']) ) {
			$new_invoice['invoice_number'] = intval($rc['max_num']['curmax']) + 1;
		} else {
			$new_invoice['invoice_number'] = '1';
		}

		//
		// Save the invoice
		//
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice', $new_invoice, 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}
		$new_invoice_id = $rc['id'];

		//
		// Add the items to the invoice
		//
		foreach($items as $iid => $item) {
			$new_item = $item;
			$new_item['id'] = 0;
			$new_item['invoice_id'] = $new_invoice_id;

			//
			// Search and replace the notes with values
			//
			$new_item['notes'] = preg_replace_callback(
				'/({{([^}]+)\[([^\]]*)\]}})/',
				function ($matches) use ($dates) {
					$args = preg_replace("/\'/", '', $matches[3]);
					if( isset($dates[$matches[2]]) ) {
						return $dates[$matches[2]]->format($args);
					}
//					if( $matches[2] == 'lastmonth' ) { return $lastmonth->format($args); }
//					elseif( $matches[2] == 'lastyear' ) { return $lastyear->format($args); }
//					elseif( $matches[2] == 'thismonth' ) { return $thismonth->format($args); }
//					elseif( $matches[2] == 'nextmonth' ) { return $nextmonth->format($args); }
//					elseif( $matches[2] == 'nextyear' ) { return $nextyear->format($args); }
				}, $item['notes']);

			$new_item['description'] = preg_replace_callback(
				'/({{([^}]+)\[([^\]]*)\]}})/',
				function ($matches) use ($dates) {
					$args = preg_replace("/\'/", '', $matches[3]);
					if( isset($dates[$matches[2]]) ) {
						return $dates[$matches[2]]->format($args);
					}
//					if( $matches[2] == 'lastmonth' ) { return $lastmonth->format($args); }
//					elseif( $matches[2] == 'lastyear' ) { return $lastyear->format($args); }
//					elseif( $matches[2] == 'thismonth' ) { return $thismonth->format($args); }
//					elseif( $matches[2] == 'nextmonth' ) { return $nextmonth->format($args); }
//					elseif( $matches[2] == 'nextyear' ) { return $nextyear->format($args); }
				}, $item['description']);
			
			//
			// Save the item
			//
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice_item', $new_item, 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
				return $rc;
			}
			$new_item_id = $rc['id'];
		}
	}

	//
	// Update the recurring invoice to the next invoice date
	//
	if( $invoice['invoice_type'] == '11' ) {
		$invoice_date->add(new DateInterval('P1M'));
	} elseif( $invoice['invoice_type'] == '12') {
		$invoice_date->add(new DateInterval('P1Y'));
	}
	$invoice_date->setTimezone(new DateTimeZone('UTC'));
	$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice', $invoice_id, array('invoice_date'=>$invoice_date->format('Y-m-d H:i:s')), 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Commit the transaction
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, 'ciniki', 'sapos');

	return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
