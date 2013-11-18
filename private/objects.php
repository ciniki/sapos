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
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'status'=>array(),
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
			'shipping_amount'=>array(),
			'total_amount'=>array(),
			'invoice_notes'=>array(),
			'internal_notes'=>array(),
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
			'object'=>array(),
			'object_id'=>array(),
			'description'=>array(),
			'quantity'=>array(),
			'unit_amount'=>array(),
			'amount'=>array(),
			'taxtypes'=>array(),
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
			'tax_id'=>array('ref'=>'ciniki.sapos.tax'),
			'line_number'=>array(),
			'description'=>array(),
			'amount'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['invoice_transactions'] = array(
		'name'=>'Invoice Transaction',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_invoice_transactions',
		'fields'=>array(
			'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
			'transaction_type'=>array(),
			'source'=>array(),
			'customer_amount'=>array(),
			'transaction_fees'=>array(),
			'business_amount'=>array(),
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
	$objects['expense_item'] = array(
		'name'=>'Expense Item',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_expense_items',
		'fields'=>array(
			'expense_id'=>array('ref'=>'ciniki.sapos.expense'),
			'category'=>array(),
			'amount'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	$objects['tax'] = array(
		'name'=>'Tax',
		'sync'=>'yes',
		'table'=>'ciniki_sapos_taxes',
		'fields'=>array(
			'name'=>array(),
			'item_percentage'=>array(),
			'item_amount'=>array(),
			'invoice_amount'=>array(),
			'taxtypes'=>array(),
			'flags'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			),
		'history_table'=>'ciniki_sapos_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>