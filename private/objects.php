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
function ciniki_sapos_objects($ciniki) {
	
	$objects = array();
	$objects['invoice'] = array(
		'name'=>'Invoice',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_invoices',
		'fields'=>array(
			'invoice_number'=>array(),
			'invoice_type'=>array(),
			'po_number'=>array(),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'salesrep_id'=>array('ref'=>'ciniki.users.user'),
			'status'=>array(),
			'payment_status'=>array(),
			'shipping_status'=>array(),
			'manufacturing_status'=>array(),
			'flags'=>array(),
			'invoice_date'=>array(),
			'due_date'=>array(),
			'billing_name'=>array(),
			'billing_address1'=>array(),
			'billing_address2'=>array(),
			'billing_city'=>array(),
			'billing_province'=>array(),
			'billing_postal'=>array(),
			'billing_country'=>array(),
			'shipping_name'=>array(),
			'shipping_address1'=>array(),
			'shipping_address2'=>array(),
			'shipping_city'=>array(),
			'shipping_province'=>array(),
			'shipping_postal'=>array(),
			'shipping_country'=>array(),
			'shipping_phone'=>array(),
			'tax_location_id'=>array('ref'=>'ciniki.taxes.location'),
			'pricepoint_id'=>array('ref'=>'ciniki.customers.pricepoint'),
			'subtotal_amount'=>array(),
			'subtotal_discount_amount'=>array(),
			'subtotal_discount_percentage'=>array(),
			'discount_amount'=>array(),
			'shipping_amount'=>array(),
			'total_amount'=>array(),
			'total_savings'=>array(),
			'paid_amount'=>array(),
			'balance_amount'=>array(),
			'user_id'=>array(),
			'customer_notes'=>array(),
			'invoice_notes'=>array(),
			'internal_notes'=>array(),
			'submitted_by'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['invoice_item'] = array(
		'name'=>'Invoice Item',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_invoice_items',
		'fields'=>array(
			'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
			'line_number'=>array(),
			'status'=>array(),
			'flags'=>array(),
			'object'=>array(),
			'object_id'=>array(),
			'price_id'=>array(),
			'code'=>array(),
			'description'=>array(),
			'quantity'=>array(),
			'shipped_quantity'=>array(),
			'unit_amount'=>array(),
			'unit_discount_amount'=>array(),
			'unit_discount_percentage'=>array(),
			'subtotal_amount'=>array(),
			'discount_amount'=>array(),
			'total_amount'=>array(),
			'taxtype_id'=>array('ref'=>'ciniki.taxes.type'),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['invoice_tax'] = array(
		'name'=>'Invoice Tax',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_invoice_taxes',
		'fields'=>array(
			'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
			'taxrate_id'=>array('ref'=>'ciniki.taxes.rate'),
			'line_number'=>array(),
			'description'=>array(),
			'amount'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['qi_item'] = array(
		'name'=>'Quick Invoice Item',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_qi_items',
		'fields'=>array(
			'template'=>array(),
			'name'=>array(),
			'object'=>array(),
			'object_id'=>array(),
			'description'=>array(),
			'quantity'=>array(),
			'unit_amount'=>array(),
			'unit_discount_amount'=>array(),
			'unit_discount_percentage'=>array(),
			'taxtype_id'=>array('ref'=>'ciniki.taxes.type'),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['transaction'] = array(
		'name'=>'Transaction',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_transactions',
		'fields'=>array(
			'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
			'transaction_type'=>array(),
			'transaction_date'=>array(),
			'source'=>array(),
			'customer_amount'=>array(),
			'transaction_fees'=>array(),
			'business_amount'=>array(),
			'user_id'=>array(),
			'notes'=>array(),
			'gateway'=>array(),
			'gateway_token'=>array(),
			'gateway_status'=>array(),
			'gateway_response'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['shipment'] = array(
		'name'=>'Shipment',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_shipments',
		'fields'=>array(
			'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
			'shipment_number'=>array(),
			'status'=>array(),
			'flags'=>array(),
			'weight'=>array(),
			'weight_units'=>array(),
			'shipping_company'=>array(),
			'tracking_number'=>array(),
			'td_number'=>array(),
			'boxes'=>array(),
			'dimensions'=>array(),
			'pack_date'=>array(),
			'ship_date'=>array(),
			'freight_amount'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['shipment_item'] = array(
		'name'=>'Shipment Item',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_shipment_items',
		'fields'=>array(
			'shipment_id'=>array('ref'=>'ciniki.sapos.shipment'),
			'item_id'=>array('ref'=>'ciniki.sapos.invoice_item'),
			'quantity'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['expense'] = array(
		'name'=>'Expense',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_expenses',
		'fields'=>array(
			'name'=>array(),
			'description'=>array(),
			'invoice_date'=>array(),
			'paid_date'=>array(),
			'total_amount'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['expense_category'] = array(
		'name'=>'Expense Category',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_expense_categories',
		'fields'=>array(
			'name'=>array(),
			'sequence'=>array(),
			'flags'=>array(),
			'taxrate_id'=>array('ref'=>'ciniki.taxes.rate'),
			'start_date'=>array(),
			'end_date'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['expense_item'] = array(
		'name'=>'Expense Item',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_expense_items',
		'fields'=>array(
			'expense_id'=>array('ref'=>'ciniki.sapos.expense'),
			'category_id'=>array('ref'=>'ciniki.sapos.expense_category'),
			'amount'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['expense_image'] = array(
		'name'=>'Expense Image',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_expense_images',
		'fields'=>array(
			'expense_id'=>array('ref'=>'ciniki.sapos.expense'),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['mileage'] = array(
		'name'=>'Mileage',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_mileage',
		'fields'=>array(
			'start_name'=>array(),
			'start_address'=>array(),
			'end_name'=>array(),
			'end_address'=>array(),
			'travel_date'=>array(),
			'distance'=>array(),
			'flags'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['mileage_rate'] = array(
		'name'=>'Mileage Rate',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_mileage_rates',
		'fields'=>array(
			'rate'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['rule'] = array(
		'name'=>'Rules',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_rules',
		'fields'=>array(
			'status'=>array(),
			'flags'=>array(),
			'sequence'=>array(),
			'formulas'=>array(),
			'code'=>array(),
			'description'=>array(),
			'quantity'=>array(),
			'unit_amount'=>array(),
			'taxtype_id'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
