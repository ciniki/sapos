<?php
//
// Description
// ===========
// This function will get the customer details and update the args to create an invoice
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_getCustomer(&$ciniki, $business_id, $args) {
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
				. "AND ciniki_customer_addresses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
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
			if( $args['billing_name'] == '' ) {
				$args['billing_name'] = $customer['display_name'];
			}
			if( $args['shipping_name'] == '' ) {
				$args['shipping_name'] = $customer['display_name'];
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1612', 'msg'=>'Unable to find customer'));
		}
	}

	return array('stat'=>'ok', 'args'=>$args);
}
?>
