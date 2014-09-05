<?php
//
// Description
// -----------
// This function will update open orders when a customer status changes
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
function ciniki_sapos_hooks_inventoryUpdated($ciniki, $business_id, $args) {
	//
	// Get the time information for business and user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
//	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	$php_date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Check for any open orders that have this item
	//
	if( isset($args['object']) && $args['object'] != '' 
		&& isset($args['object_id']) && $args['object_id'] != '' && $args['object_id'] > 0 
		) {
		//
		// Get the orders that are unfulfilled which contain this item
		//
		$strsql = "SELECT id, invoice_type, status, flags "
			. "FROM ciniki_sapos_invoice_items, ciniki_sapos_invoices "
			. "WHERE ciniki_sapos_invoice_items.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
			. "AND ciniki_sapos_invoice_items.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND ciniki_sapos_invoice_items.quantity > ciniki_sapos_invoice_items.shipped_quantity "
			. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_sapos_invoice_items.invoice_id = ciniki_sapos_invoices.id "
			. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_sapos_invoices.status < 50 "
			. "";
	}

	return array('stat'=>'ok');
}
?>
