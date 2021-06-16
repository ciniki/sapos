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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
        'invoice_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Number'),
        'invoice_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Type'),
        'po_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'PO Number'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'payment_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Payment Status'),
        'shipping_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Shipping Status'),
        'manufacturing_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Manufacturing Status'),
        'donationreceipt_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Donation Receipt Status'),
        'preorder_status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Pre-Order Status'),
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
        'work_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Type'),
        'work_address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Address Line 1'),
        'work_address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Address Line 2'),
        'work_city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work City'),
        'work_province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Province'),
        'work_postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Postal'),
        'work_country'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Work Country'),
        'tax_location_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tax Location'),
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
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Load the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 
        'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
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
    $strsql = "SELECT invoice_number, customer_id, flags, tax_location_id "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.85', 'msg'=>'Unable to find invoice'));
    }
    $invoice = $rc['invoice'];

    $update_args = $args;

    if( isset($args['flags']) ) {
        $invoice['flags'] = $args['flags'];
    }
    if( ($invoice['flags']&0x02) && isset($args['shipping_phone']) && $args['shipping_phone'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.208', 'msg'=>'A shipping phone number must be specified'));
    }

    //
    // Only owners/employee/sysadmins can update customer,
    // If a customer is specified, then lookup the customer details and fill out the invoice
    // based on the customer.  
    //
    if( (!isset($ciniki['tenant']['user']['perms']) || ($ciniki['tenant']['user']['perms']&0x03) > 0 || ($ciniki['session']['user']['perms']&0x01) > 0 ) 
        && isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql = "SELECT ciniki_customers.id, ciniki_customers.type, ciniki_customers.display_name, "
            . "ciniki_customers.company, "
            . "ciniki_customers.tax_location_id, "
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
                . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'id', 
                'fields'=>array('id', 'type', 'display_name', 'company', 'tax_location_id')),
            array('container'=>'addresses', 'fname'=>'address_id',
                'fields'=>array('id'=>'address_id', 'flags', 'address1', 'address2', 
                    'city', 'province', 'postal', 'country', 'phone')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customers']) && isset($rc['customers'][$args['customer_id']]) ) {
            $customer = $rc['customers'][$args['customer_id']];
            if( isset($customer['tax_location_id']) && $customer['tax_location_id'] > 0 
                && (!isset($args['tax_location_id']) && $invoice['tax_location_id'] == 0) 
                ) {
                $update_args['tax_location_id'] = $customer['tax_location_id'];
            }
//            $rc['customers'][$args['customer_id']]['name'] = $customer['display_name'];

            if( (isset($args['billing_name']) && $args['billing_name'] == '') || $args['billing_update'] == 'yes' ) {
                $update_args['billing_name'] = $customer['display_name'];
            }
            if( (isset($args['shipping_name']) && $args['shipping_name'] == '') || $args['shipping_update'] == 'yes' ) {
                $update_args['shipping_name'] = $customer['display_name'];
            }
            if( isset($customer['addresses']) ) {
                foreach($customer['addresses'] as $aid => $address) {
                    //
                    // Only add customer address when drop ship not set
                    //
                    if( ($invoice['flags']&0x02) == 0 
                        && ($address['flags']&0x01) == 0x01 
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.87', 'msg'=>'Unable to find customer'));
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
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice', 
        $args['invoice_id'], $update_args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Return the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
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
                    $rc = $fn($ciniki, $args['tnid'], $invoice['id'], $item);
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
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Reload the invoice record incase anything has changed
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
