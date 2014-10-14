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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'checkOrder');
	$rc = ciniki_sapos_web_checkOrder($ciniki, $settings, $business_id, $cart);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
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
	if( isset($ciniki['session']['customer']['display_name']) ) {
		$args['submitted_by'] = $ciniki['session']['customer']['display_name'];
	} else {
		$args['submitted_by'] = '';
	}
	if( isset($ciniki['session']['customer']['email']) && $ciniki['session']['customer']['email'] != '' ) {
		$args['submitted_by'] .= ($args['submitted_by']!=''?' [' . $ciniki['session']['customer']['email'] . ']':$ciniki['session']['customer']['email']);
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
