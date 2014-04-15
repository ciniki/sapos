<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_cartLoad($ciniki, $settings, $business_id) {

	//
	// Check that a cart does exist
	//
	if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
		$rc = ciniki_sapos_invoiceLoad($ciniki, $business_id, $ciniki['session']['cart']['sapos_id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// Check to make sure the invoice is still in shopping cart status
		//
		if( $rc['cart']['status'] != '10' ) {
			return array('stat'=>'noexist', 'cart'=>array());
		}

		return array('stat'=>'ok', 'cart'=>$rc['invoice']);
	}

	return array('stat'=>'noexist', 'cart'=>array());
}
?>
