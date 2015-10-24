<?php
//
// Description
// ===========
// This cron job checks for any recurring invoices that need to be created in any business.
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_sapos_cron_jobs(&$ciniki) {
	ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for sapos jobs', 'severity'=>'5'));

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddFromRecurring');

	//
	// Get the list of recurring invoices with a invoice_date of today or before
	//
	$strsql = "SELECT ri.business_id, "
		. "ri.id AS recurring_id, "
		. "i.id AS invoice_id "
		. "FROM ciniki_sapos_invoices AS ri "
		. "LEFT JOIN ciniki_sapos_invoices AS i ON ("
			. "ri.id = i.source_id "
			. "AND ri.business_id = i.business_id "
			. "AND ri.invoice_date = i.invoice_date "
			. ") "
		. "WHERE (ri.invoice_type = 11 OR ri.invoice_type = 12) "
		. "AND ri.invoice_date < UTC_TIMESTAMP() "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['rows']) ) {
		return array('stat'=>'ok');
	}
	$recurring = $rc['rows'];
	
	foreach($recurring as $ri) {
		//
		// Add the missing recurring invoices
		//
		$rc = ciniki_sapos_invoiceAddFromRecurring($ciniki, $ri['business_id'], $ri['recurring_id']);
		if( $rc['stat'] != 'ok' ) {
			//
			// Log the message but don't exit, there might be many more to setup
			//
			ciniki_cron_logMsg($ciniki, $rc['business_id'], array('code'=>'2621', 'msg'=>'Unable to add recurring invoice',
				'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
				));
		}
	}

	return array('stat'=>'ok');
}
?>
