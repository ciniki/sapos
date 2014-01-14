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
function ciniki_sapos_customerInvoices($ciniki, $business_id, $customer_id, $limit=0) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the invoices for a customer
	//
	$strsql = "SELECT ciniki_customers.display_name AS customer_name, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "ciniki_sapos_invoices.invoice_date, "
		. "ciniki_sapos_invoices.status, 
		. "ciniki_sapos_invoices.status AS status_text, "
		. "ciniki_sapos_invoices.total_amount "
		. "FROM ciniki_sapos_invoices "
		. "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
		. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY ciniki_sapos_invoices.invoice_date DESC "
		. "";
	if( $limit > 0 ) {
		$strsql .= "LIMIT $limit ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
			'fields'=>array('id, 'customer_name', 'invoice_number', 'invoice_date',
				'status', 'status_text', 'total_amount'),
			'maps'=>array('status_text'=>array('10'=>'Pending', '40'=>'Partial Payment', '50'=>'Paid', '60'=>'Cancelled'))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['invoices']) ) {
		return array('stat'=>'ok', 'invoices'=>$rc['invoices']);
	}

	return array('stat'=>'ok', 'invoices'=>array());
}
?>
