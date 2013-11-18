<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
		'invoice_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Number'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
		'invoice_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Invoice Date'),
		'due_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Due Date'),
		'billing_copy'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Update Billing from Customer'),
		'billing_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Name'),
		'billing_address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Address Line 1'),
		'billing_address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Address Line 2'),
		'billing_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing City'),
		'billing_province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Province'),
		'billing_postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Postal'),
		'billing_country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Country'),
		'shipping_copy'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Update Shipping from Customer'),
		'shipping_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Name'),
		'shipping_address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Address Line 1'),
		'shipping_address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Address Line 2'),
		'shipping_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping City'),
		'shipping_province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Province'),
		'shipping_postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Postal'),
		'shipping_country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Country'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Force the invoice_date and due_date to be a date time with 12:00:00 (noon)
	// This is used for calculating taxes based on invoice_date
	//
	if( isset($args['invoice_date']) ) {
		$args['invoice_date'] += ' 12:00:00';
	}
	if( isset($args['due_date']) ) {
		$args['due_date'] += ' 12:00:00';
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// If a customer is specified, then lookup the customer details and fill out the invoice
	// based on the customer.  
	//
	if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
		$strsql = "SELECT ciniki_customers.id, "
			. "CONCAT_WS(' ', ciniki_customers.prefix, ciniki_customers.first, "
				. "ciniki_customers.middle, ciniki_customers.last, ciniki_customers.suffix) AS name, "
			. "ciniki_customers.company "
			. "ciniki_customer_addresses.id AS address_id, "
			. "ciniki_customer_addresses.flags, "
			. "ciniki_customer_addresses.address1, "
			. "ciniki_customer_addresses.address2, "
			. "ciniki_customer_addresses.city, "
			. "ciniki_customer_addresses.province, "
			. "ciniki_customer_addresses.postal, "
			. "ciniki_customer_addresses.country "
			. "FROM ciniki_customers "
			. "LEFT JOIN ciniki_customer_addresses ON (ciniki_customers.id = ciniki_customer_addresses.customer_id "
				. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 
				'fields'=>array('id', 'name', 'company')),
			array('container'=>'addresses', 'fname'=>'address_id',
				'fields'=>array('id'=>'address_id', 'flags', 'address1', 'address2', 'city', 'province', 'postal', 'country')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['customers']) && isset($rc['customers'][$args['customer_id']]) ) {
			$customer = $rc['customers'][$args['customer_id']];
			$customer_name = $customer['name'];
			if( $args['billing_name'] == '' || $args['billing_update'] == 'yes' ) {
				$args['billing_name'] = $customer['name'];
			}
			if( $args['shipping_name'] == '' || $args['shipping_update'] == 'yes' ) {
				$args['shipping_name'] = $customer['name'];
			}
			foreach($customer['addresses'] as $aid => $address) {
				if( ($address['flags']&0x01) == 0x01 && ($args['shipping_address1'] == '' || $args['shipping_update'] == 'yes') ) {
					$args['shipping_address1'] = $address['address1'];
					$args['shipping_address2'] = $address['address2'];
					$args['shipping_city'] = $address['city'];
					$args['shipping_province'] = $address['province'];
					$args['shipping_postal'] = $address['postal'];
					$args['shipping_country'] = $address['country'];
				}
				if( ($address['flags']&0x02) == 0x01 && ($args['billing_address1'] == '' || $args['billing_update'] == 'yes') ) {
					$args['billing_address1'] = $address['address1'];
					$args['billing_address2'] = $address['address2'];
					$args['billing_city'] = $address['city'];
					$args['billing_province'] = $address['province'];
					$args['billing_postal'] = $address['postal'];
					$args['billing_country'] = $address['country'];
				}
			}
		} else {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Unable to find customer'));
		}
	}

	//
	// Update the invoice
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice', $args['invoice_id'], $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Return the invoice record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_core_invoiceLoad($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'invoice'=>$rc['invoice']);
}
?>