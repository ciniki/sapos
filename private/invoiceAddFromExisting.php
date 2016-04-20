<?php
//
// Description
// -----------
// This function will create a new invoice based on an old invoice details without the items.
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
function ciniki_sapos_invoiceAddFromExisting($ciniki, $business_id, $invoice_id) {
	//
	// Get the time information for business and user
	//
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
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	//
	// Get the old invoice details
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
		. "due_date, "
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
				),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoices']) || !isset($rc['invoices'][0]['invoice']) ) {
		return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'3309', 'msg'=>'Invoice does not exist'));
	}
	$invoice = $rc['invoices'][0]['invoice'];

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
    // Create the invoice
    //
    $new_invoice = $invoice;
    $new_invoice['source_id'] = 0;
    $new_invoice['id'] = 0;
    // Set the date back to UTC
    $new_invoice_date = new DateTime('now', new DateTimeZone($intl_timezone));
    $new_invoice_date->setTimezone(new DateTimeZone('UTC'));
    $new_invoice['invoice_date'] = date_format($new_invoice_date, 'Y-m-d H:i:s');

    //
    // Get the next invoice number
    //
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

	return array('stat'=>'ok', 'id'=>$new_invoice_id);
}
?>
