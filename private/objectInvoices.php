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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the invoices where an object has been invoiced for
	//
	$strsql = "SELECT ciniki_sapos_invoices.id, "
		. "CONCAT_WS(' ', ciniki_customers.first, ciniki_customers.last) AS customer_name, "
		. "ciniki_customers.first, ciniki_customers.last, ciniki_customers.company, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "IFNULL(DATE_FORMAT(ciniki_sapos_invoices.invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS invoice_date, "
		. "ciniki_sapos_invoices.invoice_date, "
		. "ciniki_sapos_invoices.status, "
		. "ciniki_sapos_invoices.status AS status_text, "
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
		. "";
	if( $limit > 0 ) {
		$strsql .= "LIMIT $limit ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
			'fields'=>array('id', 'customer_name', 'first', 'last', 'company', 
				'invoice_number', 'invoice_date', 'status', 'status_text', 'total_amount'),
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
