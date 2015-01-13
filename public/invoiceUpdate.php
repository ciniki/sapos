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
        'salesrep_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Salesrep'), 
		'invoice_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Number'),
		'invoice_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Type'),
		'po_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'PO Number'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
		'payment_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Payment Status'),
		'shipping_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Shipping Status'),
		'manufacturing_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Manufacturing Status'),
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
		'invoice_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Invoice Date'),
		'due_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Due Date'),
		'billing_update'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Update Billing from Customer'),
		'billing_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Name'),
		'billing_address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Address Line 1'),
		'billing_address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Address Line 2'),
		'billing_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing City'),
		'billing_province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Province'),
		'billing_postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Postal'),
		'billing_country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Billing Country'),
		'shipping_update'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Update Shipping from Customer'),
		'shipping_name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Name'),
		'shipping_address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Address Line 1'),
		'shipping_address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Address Line 2'),
		'shipping_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping City'),
		'shipping_province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Province'),
		'shipping_postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Postal'),
		'shipping_country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Country'),
		'shipping_phone'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Phone'),
		'shipping_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Notes'),
		'work_update'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Update Work from Customer'),
		'work_address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Address Line 1'),
		'work_address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Address Line 2'),
		'work_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work City'),
		'work_province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Province'),
		'work_postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Postal'),
		'work_country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Country'),
		'tax_location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Location'),
		'pricepoint_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Pricepoint'),
		'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'),
		'invoice_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'),
		'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'),
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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Load the settings
	//
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 
		'business_id', $args['business_id'], 'ciniki.sapos', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = isset($rc['settings'])?$rc['settings']:array();

	if( !isset($args['unit_discount_percentage']) || $args['unit_discount_percentage'] == '' ) {
		$args['unit_discount_percentage'] = 0;
	}

	//
	// Get the existing invoice details to compare fields
	//
	$strsql = "SELECT invoice_number, customer_id, salesrep_id, tax_location_id, pricepoint_id "
		. "FROM ciniki_sapos_invoices "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoice']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2011', 'msg'=>'Unable to find invoice'));
	}
	$invoice = $rc['invoice'];

	//
	// Check to make sure the invoice belongs to the salesrep, if they aren't also owners/employees
	//
	if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
		if( $invoice['salesrep_id'] != $ciniki['session']['user']['id'] ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2035', 'msg'=>'Permission Denied'));
		}
		$update_args = array();
		//
		// Sales rep is only allowed to update certain fields
		//
		if( isset($settings['rules-salesreps-invoice-po_number']) 
			&& $settings['rules-salesreps-invoice-po_number'] == 'edit' 
			&& isset($args['po_number'])
			) {
			$update_args['po_number'] = $args['po_number'];
		}
		if( isset($settings['rules-salesreps-invoice-pricepoint_id']) 
			&& $settings['rules-salesreps-invoice-pricepoint_id'] == 'edit' 
			&& isset($args['pricepoint_id'])
			) {
			$update_args['pricepoint_id'] = $args['pricepoint_id'];
		}
		if( isset($settings['rules-salesreps-invoice-notes']) 
			&& $settings['rules-salesreps-invoice-notes'] == 'edit' 
			) {
			if( isset($args['customer_notes']) ) {
				$update_args['customer_notes'] = $args['customer_notes'];
			}
			if( isset($args['internal_notes']) ) {
				$update_args['internal_notes'] = $args['internal_notes'];
			}
		}
		$address_args = array('name', 'address1', 'address2', 'city', 'province', 'postal', 'country', 'phone');
		if( isset($settings['rules-salesreps-invoice-billing']) 
			&& $settings['rules-salesreps-invoice-billing'] == 'edit' 
			) {
			foreach($address_args as $arg) {
				if( isset($args['billing_' . $arg]) ) {
					$update_args['billing_' . $arg] = $args['billing_' . $arg];
				}
			}
		}
		if( isset($settings['rules-salesreps-invoice-shipping']) 
			&& $settings['rules-salesreps-invoice-shipping'] == 'edit' 
			) {
			foreach($address_args as $arg) {
				if( isset($args['shipping_' . $arg]) ) {
					$update_args['shipping_' . $arg] = $args['shipping_' . $arg];
				}
			}
		}
	} else {
		$update_args = $args;
	}

	//
	// Only owners/employee/sysadmins can update customer,
	// If a customer is specified, then lookup the customer details and fill out the invoice
	// based on the customer.  
	//
	if( (!isset($ciniki['business']['user']['perms']) || ($ciniki['business']['user']['perms']&0x03) > 0 || ($ciniki['session']['user']['perms']&0x01) > 0 ) 
		&& isset($args['customer_id']) && $args['customer_id'] > 0 ) {
		$strsql = "SELECT ciniki_customers.id, ciniki_customers.type, ciniki_customers.display_name, "
			. "ciniki_customers.company, "
			. "ciniki_customers.salesrep_id, "
			. "ciniki_customers.tax_location_id, "
			. "ciniki_customers.pricepoint_id, "
			. "ciniki_customer_addresses.id AS address_id, "
			. "ciniki_customer_addresses.flags, "
			. "ciniki_customer_addresses.address1, "
			. "ciniki_customer_addresses.address2, "
			. "ciniki_customer_addresses.city, "
			. "ciniki_customer_addresses.province, "
			. "ciniki_customer_addresses.postal, "
			. "ciniki_customer_addresses.country, "
			. "ciniki_customer_addresses.phone "
			. "FROM ciniki_customers "
			. "LEFT JOIN ciniki_customer_addresses ON (ciniki_customers.id = ciniki_customer_addresses.customer_id "
				. "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 
				'fields'=>array('id', 'type', 'display_name', 'company', 
					'salesrep_id', 'tax_location_id', 'pricepoint_id')),
			array('container'=>'addresses', 'fname'=>'address_id',
				'fields'=>array('id'=>'address_id', 'flags', 'address1', 'address2', 
					'city', 'province', 'postal', 'country', 'phone')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['customers']) && isset($rc['customers'][$args['customer_id']]) ) {
			$customer = $rc['customers'][$args['customer_id']];
			if( isset($customer['salesrep_id']) && $customer['salesrep_id'] > 0 
				&& (!isset($args['salesrep_id']) && $invoice['salesrep_id'] == 0) 
				) {
				// Only set the salesrep_id if there isn't already one set.
				$update_args['salesrep_id'] = $customer['salesrep_id'];
			}
			if( isset($customer['tax_location_id']) && $customer['tax_location_id'] > 0 
				&& (!isset($args['tax_location_id']) && $invoice['tax_location_id'] == 0) 
				) {
				$update_args['tax_location_id'] = $customer['tax_location_id'];
			}
			if( isset($customer['pricepoint_id']) && $customer['pricepoint_id'] > 0 
				&& (!isset($args['pricepoint_id']) && $invoice['pricepoint_id'] == 0) 
				) {
				$update_args['pricepoint_id'] = $customer['pricepoint_id'];
			}
//			$rc['customers'][$args['customer_id']]['name'] = $customer['display_name'];

			if( (isset($args['billing_name']) && $args['billing_name'] == '') || $args['billing_update'] == 'yes' ) {
//				if( $customer['type'] == 2 ) {
//					$args['billing_name'] = $customer['company'];
//				} else {
					$update_args['billing_name'] = $customer['display_name'];
//				}
			}
			if( (isset($args['shipping_name']) && $args['shipping_name'] == '') || $args['shipping_update'] == 'yes' ) {
//				if( $customer['type'] == 2 ) {
//					$args['shipping_name'] = $customer['company'];
//				} else {
					$update_args['shipping_name'] = $customer['display_name'];
//				}
			}
			if( isset($customer['addresses']) ) {
				foreach($customer['addresses'] as $aid => $address) {
					if( ($address['flags']&0x01) == 0x01 
						&& ((isset($args['shipping_address1']) && $args['shipping_address1'] == '') 
							|| $args['shipping_update'] == 'yes') ) {
						$update_args['shipping_address1'] = $address['address1'];
						$update_args['shipping_address2'] = $address['address2'];
						$update_args['shipping_city'] = $address['city'];
						$update_args['shipping_province'] = $address['province'];
						$update_args['shipping_postal'] = $address['postal'];
						$update_args['shipping_country'] = $address['country'];
						$update_args['shipping_phone'] = $address['phone'];
					}
					if( ($address['flags']&0x02) == 0x02 
						&& ((isset($args['billing_address1']) && $args['billing_address1'] == '' )
							|| $args['billing_update'] == 'yes') ) {
						$update_args['billing_address1'] = $address['address1'];
						$update_args['billing_address2'] = $address['address2'];
						$update_args['billing_city'] = $address['city'];
						$update_args['billing_province'] = $address['province'];
						$update_args['billing_postal'] = $address['postal'];
						$update_args['billing_country'] = $address['country'];
					}
				}
			}
		} else {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1096', 'msg'=>'Unable to find customer'));
		}
	}

	//
	// Start the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Update the invoice
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice', 
		$args['invoice_id'], $update_args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Check if pricepoint was updated, and the invoice prices need to change
	//
	if( isset($update_args['pricepoint_id']) && $update_args['pricepoint_id'] != $invoice['pricepoint_id'] ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdatePrices');
		$rc = ciniki_sapos_invoiceUpdatePrices($ciniki, $args['business_id'], $args['invoice_id'],
			array('pricepoint_id'=>$update_args['pricepoint_id']));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
	}

	//
	// Return the invoice record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$invoice = $rc['invoice'];

	//
	// Check for callbacks
	//
	if( isset($invoice['items']) ) {
		foreach($invoice['items'] as $iid => $item) {
			$item = $item['item'];
			if( $item['object'] != '' && $item['object_id'] != '' ) {
				list($pkg,$mod,$obj) = explode('.', $item['object']);
				$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'invoiceUpdate');
				if( $rc['stat'] == 'ok' ) {
					$fn = $rc['function_call'];
					$rc = $fn($ciniki, $args['business_id'], $invoice['id'], $item);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}
	}

	//
	// Update the taxes/shipping incase something relavent changed
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
	$rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Reload the invoice record incase anything has changed
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$invoice = $rc['invoice'];

	//
	// Commit the transaction
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'sapos');

	return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
