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
function ciniki_sapos_invoiceAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
        'bill_parent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bill Parent'), 
        'salesrep_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Customer'), 
        'source_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Source Invoice'),
        'invoice_number'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Invoice Number'),
        'invoice_type'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'10', 'name'=>'Invoice Type'),
        'po_number'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'PO Number'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status'),
        'payment_status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Payment Status'),
        'shipping_status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Shipping Status'),
        'manufacturing_status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Manufacturing Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'),
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
        'shipping_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Notes'),
        'work_address1'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Work Address Line 1'),
        'work_address2'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Work Address Line 2'),
        'work_city'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Work City'),
        'work_province'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Work Province'),
        'work_postal'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Work Postal'),
        'work_country'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Work Country'),
        'tax_location_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Tax Location'),
        'pricepoint_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Pricepoint'),
        'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Customer Notes'),
        'invoice_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Invoice Notes'),
        'internal_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Internal Notes'),
        'submitted_by'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Submitted By'),
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
    // Check if payment_status is used
    //
    if( ($ciniki['business']['modules']['ciniki.sapos']['flags']&0x0200) > 0 ) {
        if( $args['payment_status'] == '0' ) {
            $args['payment_status'] == '10';
        }
    }

    //
    // Check if drop ship specified
    //
    if( isset($args['flags']) && ($args['flags']&0x02) == 0x02 ) {
        if( !isset($args['shipping_phone']) || $args['shipping_phone'] == '' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.207', 'msg'=>'A shipping phone number must be specified'));
        }
    }

    //
    // Force the invoice_date and due_date to be a date time with 12:00:00 (noon)
    // This is used for calculating taxes based on invoice_date
    //
//    if( $args['invoice_date'] != '' ) {
//        $args['invoice_date'] .= ' 12:00:00';
//    }
//    if( $args['due_date'] != '' ) {
//        $args['due_date'] .= ' 12:00:00';
//    }

    //
    // Set the user id who created the invoice
    //
    $args['user_id'] = $ciniki['session']['user']['id'];

    //
    // If a customer is specified, then lookup the customer details and fill out the invoice
    // based on the customer.  
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // If requested, find the parent if any
        //
        if( isset($args['bill_parent']) && $args['bill_parent'] == 'yes' ) {
            $strsql = "SELECT parent_id "
                . "FROM ciniki_customers "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']['parent_id']) && $rc['customer']['parent_id'] > 0 ) {
                $args['student_id'] = $args['customer_id'];
                $args['customer_id'] = $rc['customer']['parent_id'];
            }
        }
    
        //
        // Get the customer details and add to the args if not already set
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getCustomer');
        $rc = ciniki_sapos_getCustomer($ciniki, $args['business_id'], $args);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $args = $rc['args'];
    }


    //
    // Get the object details and turn them into item details for the invoice
    //
    $invoice_items = array();
    if( isset($args['objects']) && is_array($args['objects']) && count($args['objects']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'lookupObjects');
        $rc = ciniki_sapos_lookupObjects($ciniki, $args['business_id'], $args['objects']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.61', 'msg'=>'Unable to lookup invoice item reference', 'err'=>$rc['err']));
        }
        if( isset($rc['items']) ) {
            $invoice_items = $rc['items'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.62', 'msg'=>'Unable to find specified items.'));
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
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
        $item_id = $rc['id'];

        // If there is a student ID to pass into itemAdd
        if( isset($args['student_id']) && $args['student_id'] != '' && $args['student_id'] > 0 ) {
            $item['student_id'] = $args['student_id'];
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
                if( isset($rc['object']) && $rc['object'] != $item['object'] ) {
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
