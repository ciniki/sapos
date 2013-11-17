<?php
//
// Description
// -----------
// This function will return the taxes applicable for a certain date for a business.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_businessDateTaxes($ciniki, $business_id, $tax_date) {
	
	//
	// Get the taxes for the business, based on the tax_date supplied
	//
	$strsql = "SELECT id, name, item_percentage, item_amount, invoice_amount, taxtypes "
		. "FROM ciniki_sapos_taxes "
		. "WHERE ciniki_sapos_taxes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND start_date <= '" . ciniki_core_dbQuote($ciniki, $tax_date) . "' "
		. "AND (end_date == '0000-00-00 00:00:00' " // No end date specified
			. "OR end_date >= '" . ciniki_core_dbQuote($ciniki, $tax_date) . "') "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.sapos', 'taxes', 'id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['taxes']) ) {
		return array('stat'=>'ok', 'taxes'=>array());
	}
	$taxes = $rc['taxes'];

	//
	// FIXME: Check for any blank out dates, or tax holidays
	//

	return array('stat'=>'ok', 'taxes'=>$taxes);
}
?>
