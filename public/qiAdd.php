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
//
function ciniki_sapos_qiAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Name'), 
        'items'=>array('required'=>'yes', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Items'),
        'transaction_source'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Source',
            'validlist'=>array('10','20','50','55','60','65','90','100','105','110','115','120')),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'10', 'name'=>'Limit'),
        // Option args
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
        'shipping_phone'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Phone'),
        'invoice_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Invoice Notes'),
        'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Internal Notes'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.qiAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load auto category settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', 'invoice-autocat');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Get tenant/user settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Get the status maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];


    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Set the user id who created the invoice
    //
    $args['user_id'] = $ciniki['session']['user']['id'];

    //
    // If a new customer, add them to the database
    //
    if( $args['customer_id'] == 0 && $args['name'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'public', 'add');
        $rc = ciniki_customers_add($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $args['customer_id'] = $rc['id'];
    }

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
                . "AND ciniki_customer_addresses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.101', 'msg'=>'Unable to find customer'));
        }
    }

    //
    // Get the quick invoice items to be added
    //
    $invoice_items = array();
    if( isset($args['items']) && is_array($args['items']) && count($args['items']) > 0 ) {
        $strsql = "SELECT id, name, "
            . "object, object_id, "
            . "description, "
            . "quantity, "
            . "unit_amount, "
            . "unit_discount_amount, "
            . "unit_discount_percentage, "
            . "taxtype_id "
            . "FROM ciniki_sapos_qi_items "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['items']) . ") "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'items', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'object', 'object_id', 'description',
                    'quantity', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
                    'taxtype_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['items']) ) {
            $invoice_items = $rc['items'];
        }
    }

    //
    // Get the next available invoice number for the tenant
    //
    if( !isset($args['invoice_number']) || $args['invoice_number'] == '' ) {
        $strsql = "SELECT MAX(CAST(invoice_number AS UNSIGNED)) AS curmax "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
    $args['preorder_amount'] = 0;
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
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.invoice', $args, 0x04);
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
        $item['status'] = 0;
        $item['line_number'] = $line_number++;
        $item['notes'] = '';
        //
        // Check for auto categories
        //
        if( isset($settings['invoice-autocat-' . $item['object']]) ) {
            $item['category'] = $settings['invoice-autocat-' . $item['object']];
        }
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

        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', $item, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
    }

    //
    // Update the shipping costs, taxes, and total
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['tnid'], $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    $total = $rc['total_amount'];

    //
    // Add the transaction for the total
    //
    $transaction_args = array(
        'invoice_id'=>$invoice_id,
        'transaction_type'=>'20',
        'transaction_date'=>$args['invoice_date'],
        'source'=>$args['transaction_source'],
        'customer_amount'=>$total,
        'transaction_fees'=>0,
        'tenant_amount'=>$total,
        'user_id'=>$ciniki['session']['user']['id'],
        'notes'=>'',
        'gateway'=>'',
        'gateway_token'=>'',
        'gateway_status'=>'',
        'gateway_response'=>'',
        );

    //
    // Add the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.transaction', 
        $transaction_args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    $transaction_id = $rc['id'];

    //
    // Update the invoice status to paid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $invoice_id);
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    //
    // Return the latest invoices
    //
    $strsql = "SELECT ciniki_sapos_invoices.id, "
        . "ciniki_sapos_invoices.invoice_number, "
        . "ciniki_sapos_invoices.invoice_date, "
        . "ciniki_sapos_invoices.status, "
        . "ciniki_sapos_invoices.status AS status_text, "
        . "ciniki_customers.type AS customer_type, "
        . "ciniki_customers.display_name AS customer_display_name, "
        . "total_amount "
        . "FROM ciniki_sapos_invoices "
        . "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_sapos_invoices.last_updated DESC "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 10 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
            'fields'=>array('id', 'invoice_number', 'invoice_date', 'status', 'status_text', 
                'customer_type', 'customer_display_name', 'total_amount'),
            'maps'=>array('status_text'=>$maps['invoice']['status']),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoices']) ) {
        $invoices = array();
    } else {    
        foreach($rc['invoices'] as $iid => $invoice) {
            $rc['invoices'][$iid]['invoice']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
                $invoice['invoice']['total_amount'], $intl_currency);
        }
        $invoices = $rc['invoices'];
    }


    return array('stat'=>'ok', 'id'=>$invoice_id, 'latest'=>$invoices);
}
?>
