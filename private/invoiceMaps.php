<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_sapos_invoices.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_invoiceMaps($ciniki) {

	$maps = array(
		'invoice_type'=>array(
			'10'=>'Invoice',
			'20'=>'Cart',
			'30'=>'POS',
			'40'=>'Order',
			),
		'status'=>array(
			'10'=>'Entered',
			'20'=>'Pending Manufacturing',
			'30'=>'Pending Shipping',
			'40'=>'Payment Required',
			'50'=>'Fulfilled',
			'55'=>'Refunded',
			'60'=>'Void',
			),
		'typestatus'=>array(
			'1010'=>'Payment Required',
			'1020'=>'Processing',
			'1030'=>'Processing',
			'1040'=>'Payment Required',
			'1050'=>'Paid',
			'1055'=>'Refunded',
			'1060'=>'Void',
			'2010'=>'Incomplete',
			'2020'=>'Pending Manufacturing',
			'2030'=>'Pending Shipping',
			'2040'=>'Payment Required',
			'2050'=>'Fulfilled',
			'2055'=>'Refunded',
			'2060'=>'Void',
			'3010'=>'Entered',
			'3020'=>'Pending Manufacturing',
			'3030'=>'Pending Shipping',
			'3040'=>'Payment Required',
			'3050'=>'Fulfilled',
			'3055'=>'Refunded',
			'3060'=>'Void',
			'4010'=>'Entered',
			'4020'=>'Pending Manufacturing',
			'4030'=>'Pending Shipping',
			'4040'=>'Payment Required',
			'4050'=>'Fulfilled',
			'4055'=>'Refunded',
			'4060'=>'Void',
			),
		'payment_status'=>array(
			'10'=>'Payment Required',
			'40'=>'Deposit',
			'50'=>'Paid',
			'55'=>'Refunded',
			),
		'shipping_status'=>array(
			'0'=>'',		// No shipping
			'10'=>'Shipping Required',
			'30'=>'Partial Shipment',
			'50'=>'Shipped',
			),
		'manufacturing_status'=>array(
			'0'=>'',		// No shipping
			'10'=>'Manufacturing Required',
			'30'=>'Manufacturing In Progress',
			'50'=>'Manufactured',
			),
		);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
