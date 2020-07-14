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
function ciniki_sapos_getCustomer(&$ciniki, $tnid, $args) {
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql = "SELECT ciniki_customers.id, type, display_name, "
            . "ciniki_customers.parent_id, "
            . "ciniki_customers.status, "
            . "ciniki_customers.tax_location_id, "
            . "ciniki_customers.company, "
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
                . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 
                'fields'=>array('id', 'display_name', 'company', 
                    'status', 'tax_location_id')),
            array('container'=>'addresses', 'fname'=>'address_id',
                'fields'=>array('id'=>'address_id', 'flags', 'address1', 'address2', 
                    'city', 'province', 'postal', 'country', 'phone')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) && isset($rc['customers'][$args['customer_id']]) ) {
            $customer = $rc['customers'][$args['customer_id']];
            if( isset($customer['tax_location_id']) && (!isset($args['tax_location_id']) || $args['tax_location_id'] == 0 || $args['tax_location_id'] == '') ) {
                $args['tax_location_id'] = $customer['tax_location_id'];
            }
            if( !isset($args['billing_name']) || $args['billing_name'] == '' ) {
                $args['billing_name'] = $customer['display_name'];
            }
            if( !isset($args['shipping_name']) || $args['shipping_name'] == '' ) {
                $args['shipping_name'] = $customer['display_name'];
            }
            if( isset($customer['addresses']) ) {
                foreach($customer['addresses'] as $aid => $address) {
                    if( ($address['flags']&0x01) == 0x01 && (!isset($args['shipping_address1']) || $args['shipping_address1'] == '') ) {
                        $args['shipping_address1'] = $address['address1'];
                        $args['shipping_address2'] = $address['address2'];
                        $args['shipping_city'] = $address['city'];
                        $args['shipping_province'] = $address['province'];
                        $args['shipping_postal'] = $address['postal'];
                        $args['shipping_country'] = $address['country'];
                        $args['shipping_phone'] = $address['phone'];
                    }
                    if( ($address['flags']&0x02) == 0x02 && (!isset($args['billing_address1']) || $args['billing_address1'] == '') ) {
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.19', 'msg'=>'Unable to find customer'));
        }
    }

    return array('stat'=>'ok', 'args'=>$args);
}
?>
