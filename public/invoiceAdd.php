<?php
//
// Description
// ===========
// This method will add a new invoice to the system, creating item entries if specified.  If
// a customer is specified, the billing/shipping address will be pulled from the customer record.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		'invoice_number'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Invoice Number'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'20', 'name'=>'Status'),
		'invoice_date'=>array('required'=>'no', 'blank'=>'no', 'default'=>'now', 'type'=>'datetimetoutc', 'name'=>'Invoice Date'),
		'due_date'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'type'=>'datetimetoutc', 'name'=>'Due Date'),
		'billing_name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Billing Name'),
		'billing_address1'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Billing Address Line 1'),
		'billing_address2'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Billing Address Line 2'),
		'billing_city'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Billing City'),
		'billing_province'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Billing Province'),
		'billing_postal'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Billing Postal'),
		'billing_country'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Billing Country'),
		'shipping_name'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Name'),
		'shipping_address1'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Address Line 1'),
		'shipping_address2'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Address Line 2'),
		'shipping_city'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping City'),
		'shipping_province'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Province'),
		'shipping_postal'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Postal'),
		'shipping_country'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Country'),
		'invoice_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Invoice Notes'),
		'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Internal Notes'),
		'objects'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'objectlist', 'name'=>'Items'),
		'items'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'json', 'name'=>'Items'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// Force the invoice_date and due_date to be a date time with 12:00:00 (noon)
	// This is used for calculating taxes based on invoice_date
	//
//	if( $args['invoice_date'] != '' ) {
//		$args['invoice_date'] .= ' 12:00:00';
//	}
//	if( $args['due_date'] != '' ) {
//		$args['due_date'] .= ' 12:00:00';
//	}

	//
	// Set the user id who created the invoice
	//
	$args['user_id'] = $ciniki['session']['user']['id'];

	//
	// If a customer is specified, then lookup the customer details and fill out the invoice
	// based on the customer.  
	//
	if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
		$strsql = "SELECT ciniki_customers.id, type, display_name, "
			. "ciniki_customers.company, "
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
				. "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'customers', 'fname'=>'id', 
				'fields'=>array('id', 'display_name', 'company')),
			array('container'=>'addresses', 'fname'=>'address_id',
				'fields'=>array('id'=>'address_id', 'flags', 'address1', 'address2', 'city', 'province', 'postal', 'country')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['customers']) && isset($rc['customers'][$args['customer_id']]) ) {
			$customer = $rc['customers'][$args['customer_id']];
			$customer_name = $customer['display_name'];
			if( $args['billing_name'] == '' ) {
// Just use the display_name for billing purposes.  If changing, change also in invoiceUpdate
//				if( $customer['type'] == 2 ) {
//					$args['billing_name'] = $customer['company'];
//				} else {
					$args['billing_name'] = $customer['display_name'];
//				}
			}
			if( $args['shipping_name'] == '' ) {
// Just use the display_name for shipping purposes.  If changing, change also in invoiceUpdate
//				if( $customer['type'] == 2 ) {
//					$args['shipping_name'] = $customer['company'];
//				} else {
					$args['shipping_name'] = $customer['display_name'];
//				}
			}
			if( isset($customer['addresses']) ) {
				foreach($customer['addresses'] as $aid => $address) {
					if( ($address['flags']&0x01) == 0x01 && $args['shipping_address1'] == '' ) {
						$args['shipping_address1'] = $address['address1'];
						$args['shipping_address2'] = $address['address2'];
						$args['shipping_city'] = $address['city'];
						$args['shipping_province'] = $address['province'];
						$args['shipping_postal'] = $address['postal'];
						$args['shipping_country'] = $address['country'];
					}
					if( ($address['flags']&0x02) == 0x02 && $args['billing_address1'] == '' ) {
						$args['billing_address1'] = $address['address1'];
						$args['billing_address2'] = $address['address2'];
						$args['billing_city'] = $address['city'];
						$args['billing_province'] = $address['province'];
						$args['billing_postal'] = $address['postal'];
						$args['billing_country'] = $address['country'];
					}
				}
			}
		} else {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1109', 'msg'=>'Unable to find customer'));
		}
	}

	//
	// Get the object details and turn them into item details for the invoice
	//
	$invoice_items = array();
	if( isset($args['objects']) && is_array($args['objects']) && count($args['objects']) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'lookupObjects');
		$rc = ciniki_sapos_lookupObjects($ciniki, $args['business_id'], $args['objects']);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1524', 'msg'=>'Unable to lookup invoice item reference', 'err'=>$rc['err']));
		}
		if( isset($rc['items']) ) {
			$invoice_items = $rc['items'];
		} else {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1101', 'msg'=>'Unable to find specified items.'));
		}
	}

	if( isset($args['items']) && is_array($args['items']) && count($args['items']) > 0 ) {
		foreach($args['items'] as $item) {
			array_push($invoice_items, $item);
		}
	}

	//
	// Get the next available invoice number for the business
	//
	if( !isset($args['invoice_number']) || $args['invoice_number'] == '' ) {
		$strsql = "SELECT MAX(CAST(invoice_number AS UNSIGNED)) AS curmax "
			. "FROM ciniki_sapos_invoices "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'max_num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['max_num']) ) {
			$args['invoice_number'] = intval($rc['max_num']['curmax']) + 1;
		} else {
			$args['invoice_number'] = '1';
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
	// Set the defaults for the invoice
	//
	$args['subtotal_amount'] = 0;
	$args['subtotal_discount_amount'] = 0;
	$args['subtotal_discount_percentage'] = 0;
	$args['discount_amount'] = 0;
	$args['shipping_amount'] = 0;
	$args['total_amount'] = 0;
	$args['total_savings'] = 0;
	$args['paid_amount'] = 0;
	$args['balance_amount'] = 0;

	//
	// Create the invoice
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.invoice', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$invoice_id = $rc['id'];

	//
	// Add the items to the invoice
	//
	$line_number = 1;
	foreach($invoice_items as $i => $item) {
		$item['invoice_id'] = $invoice_id;
		$item['line_number'] = $line_number++;
		if( !isset($item['amount']) ) {
			//
			// Calculate the final amount for each item in the invoice
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
			$rc = ciniki_sapos_itemCalcAmount($ciniki, $item);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$item['subtotal_amount'] = $rc['subtotal'];
			$item['discount_amount'] = $rc['discount'];
			$item['total_amount'] = $rc['total'];
		}
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', $item, 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}

		//
		// Check if there's a callback for the object
		//
		if( $item['object'] != '' && $item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemAdd');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], $invoice_id, $item);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				// Update the invoice item with the new object and object_id
				if( isset($rc['object']) && $rc['object'] != $args['object'] ) {
					$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', 
						$item_id, $rc, 0x04);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}
	}

	//
	// Update the shipping costs, taxes, and total
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
	$rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['business_id'], $invoice_id);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the invoice status and balance 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $invoice_id);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

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

	//
	// Return the invoice record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $invoice_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'invoice'=>$rc['invoice']);
}
?>
