<?php
//
// Description
// -----------
// This function returns the list of invoice status
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
function ciniki_sapos_hooks_invoiceStatus($ciniki, $business_id, $args) {

	//
	// Load intl settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
	$rc = ciniki_sapos_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	if( isset($args['invoice_ids']) && is_array($args['invoice_ids']) && count($args['invoice_ids']) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
		$rsp = array('stat'=>'ok');
		$strsql = "SELECT ciniki_sapos_invoices.id, "
			. "ciniki_sapos_invoices.invoice_number, "
			. "ciniki_sapos_invoices.invoice_date, "
			. "ciniki_sapos_invoices.status, "
			. "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
			. "ciniki_sapos_invoices.total_amount "
			. "FROM ciniki_sapos_invoices "
			. "WHERE ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_sapos_invoices.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['invoice_ids']) . ") "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
			array('container'=>'invoices', 'fname'=>'id', 
				'fields'=>array('id', 'invoice_number', 'invoice_date', 'status', 'status_text', 'total_amount'),
				'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
				'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format))), 
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['invoices']) ) {
			$rsp['invoices'] = array();
		} else {
			$rsp['invoices'] = $rc['invoices'];
		}
		return $rsp;
	}

	return array('stat'=>'ok');
}
?>
