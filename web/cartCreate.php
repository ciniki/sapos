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
function ciniki_sapos_web_cartCreate($ciniki, $settings, $business_id) {

	//
	// Check that a cart does not exist
	//
	if( !isset($ciniki['session']['cart']['sapos_id']) || $ciniki['session']['cart']['sapos_id'] == 0 ) {
		
		//
		// Default args for new invoice
		//
		$cart_args = array(
			'invoice_number'=>'',
			'invoice_type'=>'20',
			'po_number'=>'',
			'status'=>'10',
			'payment_status'=>'10',
			'shipping_status'=>'0',
			'manufacturing_status'=>'0',
			'customer_id'=>'0',
			'invoice_date'=>'',
			'due_date'=>'',
			'billing_name'=>'',
			'billing_address1'=>'',
			'billing_address2'=>'',
			'billing_city'=>'',
			'billing_province'=>'',
			'billing_postal'=>'',
			'billing_country'=>'',
			'shipping_name'=>'',
			'shipping_address1'=>'',
			'shipping_address2'=>'',
			'shipping_city'=>'',
			'shipping_province'=>'',
			'shipping_postal'=>'',
			'shipping_country'=>'',
			'invoice_notes'=>'',
			'internal_notes'=>'',
			'subtotal_amount'=>0,
			'subtotal_discount_amount'=>0,
			'subtotal_discount_percentage'=>0,
			'discount_amount'=>0,
			'shipping_amount'=>0,
			'total_amount'=>0,
			'total_savings'=>0,
			'paid_amount'=>0,
			'balance_amount'=>0,
			'user_id'=>'-2',
			);

		//
		// Check if customer is already logged in
		//
		if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
			$cart_args['customer_id'] = $ciniki['session']['customer']['id'];
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getCustomer');
			$rc = ciniki_sapos_getCustomer($ciniki, $business_id, $cart_args);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$cart_args = $rc['args'];
		}

		$date = new DateTime('now', new DateTimeZone('UTC'));
		$cart_args['invoice_date'] = $date->format('Y-m-d H:i:s');

		//
		// Get the next invoice number
		//
		if( !isset($cart_args['invoice_number']) || $cart_args['invoice_number'] == '' ) {
			$strsql = "SELECT MAX(CAST(invoice_number AS UNSIGNED)) AS curmax "
				. "FROM ciniki_sapos_invoices "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'max_num');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['max_num']) ) {
				$cart_args['invoice_number'] = intval($rc['max_num']['curmax']) + 1;
			} else {
				$cart_args['invoice_number'] = '1';
			}
		}

		//
		// Create the invoice
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice', $cart_args, 0x07);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$invoice_id = $rc['id'];

		return array('stat'=>'ok', 'sapos_id'=>$invoice_id);
	}

	return array('stat'=>'ok', 'sapos_id'=>$ciniki['session']['cart']['sapos_id']);
}
?>