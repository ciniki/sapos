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
function ciniki_sapos_web_submitOrder($ciniki, $settings, $business_id, $cart) {

	if( !isset($ciniki['session']['cart']['sapos_id']) || $ciniki['session']['cart']['sapos_id'] == 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1826', 'msg'=>'No order to submit'));
	}

	//
	// Check if customer is already logged in and a dealer/distributor
	//
	if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] == 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1827', 'msg'=>'Please login to submit the order.'));
	}
	
	if( (!isset($ciniki['session']['customer']['dealer_status']) 
			|| $ciniki['session']['customer']['dealer_status'] <= 0
			|| $ciniki['session']['customer']['dealer_status'] >= 60)
		&& (!isset($ciniki['session']['customer']['distributor_status'])
			|| $ciniki['session']['customer']['distributor_status'] != 10)
		) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1828', 'msg'=>"I'm sorry, but you are not allowed to submit orders."));
	}

	//
	// If specified required, make sure po_number is filled in
	//
	if( isset($settings['page-cart-po-number']) && $settings['page-cart-po-number'] == 'required' ) {
		if( $cart['po_number'] == '' ) {
			return array('stat'=>'warn', 'err'=>array('pkg'=>'ciniki', 'code'=>'2018', 'msg'=>'You must enter a Purchase Order Number before submitting your order.'));
		}
	}

	//
	// Load the current invoice_type and status
	//
	$strsql = "SELECT invoice_type, status, payment_status, shipping_status "
		. "FROM ciniki_sapos_invoices "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['cart']['sapos_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'invoice');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1829', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
	}
	if( !isset($rc['invoice']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1825', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
	}

	//
	// Update the invoice type and status
	//
	$args = array();
	if( $rc['invoice']['invoice_type'] == 20 ) {
		$args['invoice_type'] = 40;
	}
	if( $rc['invoice']['status'] == 10 ) {
		$args['status'] = 30;
	}
//	if( $rc['invoice']['payment_status'] < 10 ) {
//		$args['payment_status'] = 10;
//	}
//	if( $rc['invoice']['shipping_status'] < 10 ) {
//		$args['shipping_status'] = 10;
//	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice', 
		$ciniki['session']['cart']['sapos_id'], $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	return array('stat'=>'ok');
}
?>
