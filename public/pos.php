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
function ciniki_sapos_pos(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        'price_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Price ID'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer ID'),
        'quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Quantity'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.pos'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the existing invoice details to compare fields
    //
    if( $args['invoice_id'] > 0 ) {
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.264', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
    } else {
        $invoice = array();
    }

    //
    // Check if an item is to be added to the invoice
    //
    if( isset($args['action']) && $args['action'] == 'additem' ) {
        //
        // Check to make sure both object and object_id were passed
        //
        if( !isset($args['quantity']) ) {
            $args['quantity'] = 1;
        }
        if( !isset($args['object']) || $args['object'] == ''
            || !isset($args['object_id']) || $args['object_id'] == '' 
            ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.246', 'msg'=>'No object or object_id specified'));
        }
        //
        // Create invoice if required
        //
        if( $args['invoice_id'] == 0 || $args['invoice_id'] == '' ) {
            $invoice = array(
                'invoice_type' => 30,
                'status' => 10,
                'customer_id' => 0,
                'objects' => array(
                    array(
                        'object' => $args['object'], 
                        'id' => $args['object_id'], 
                        'quantity' => $args['quantity'],
                        'price_id' => $args['price_id'],
                        ),
                    ),
                );
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAdd');
            $rc = ciniki_sapos_invoiceAdd($ciniki, $args['tnid'], $invoice);
            if( $rc['stat'] == 'warn' ) {
                return $rc;
            }
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.255', 'msg'=>'Unable to create new invoice', 'err'=>$rc['err']));
            }
            $args['invoice_id'] = $rc['id'];
        } else {
            //
            // Add the item
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
            $rc = ciniki_sapos_invoiceAddItem($ciniki, $args['tnid'], $args);
            if( $rc['stat'] == 'warn' ) {   
                return $rc;
            }
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.250', 'msg'=>'Unable to add item to invoice', 'err'=>$rc['err']));
            }
        }
    }

    if( isset($args['action']) && $args['action'] == 'updatecustomer' ) {
        if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
            $customer_id = $args['customer_id'];
        } elseif( isset($invoice['customer_id']) ) {
            $customer_id = $invoice['customer_id'];
        }
        //
        // Create invoice if required
        //
        if( ($args['invoice_id'] == 0 || $args['invoice_id'] == '') && isset($args['customer_id']) && $args['customer_id'] > 0 ) {
            $invoice = array(
                'invoice_type' => 30,
                'status' => 10,
                'payment_status' => 10,
                'customer_id' => $args['customer_id'],
                );
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAdd');
            $rc = ciniki_sapos_invoiceAdd($ciniki, $args['tnid'], $invoice);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.247', 'msg'=>'Unable to create new invoice', 'err'=>$rc['err']));
            }
            $args['invoice_id'] = $rc['id'];
        } else {
            //
            // Update the customer
            //
            if( isset($invoice['customer_id']) ) {
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
                    . "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
                    . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
                $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
                    array('container'=>'customers', 'fname'=>'id', 
                        'fields'=>array('id', 'type', 'display_name', 'company', 'tax_location_id')),
                    array('container'=>'addresses', 'fname'=>'address_id',
                        'fields'=>array('id'=>'address_id', 'flags', 'address1', 'address2', 'city', 'province', 'postal', 'country', 'phone')),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['customers']) && isset($rc['customers'][$customer_id]) ) {
                    $customer = $rc['customers'][$customer_id];
                    $update_args = array();
                    if( $invoice['customer_id'] != $customer_id ) {
                        $update_args['customer_id'] = $customer_id;
                    }
                    if( isset($customer['tax_location_id']) && $customer['tax_location_id'] > 0 
                        && (!isset($args['tax_location_id']) && $invoice['tax_location_id'] == 0) 
                        ) {
                        $update_args['tax_location_id'] = $customer['tax_location_id'];
                    }
                    $update_args['billing_name'] = $customer['display_name'];
                    $update_args['shipping_name'] = $customer['display_name'];
                    if( isset($customer['addresses']) ) {
                        foreach($customer['addresses'] as $aid => $address) {
                            //
                            // Only add customer address when drop ship not set
                            //
                            if( ($invoice['flags']&0x02) == 0 && ($address['flags']&0x01) == 0x01 ) {
                                $update_args['shipping_address1'] = $address['address1'];
                                $update_args['shipping_address2'] = $address['address2'];
                                $update_args['shipping_city'] = $address['city'];
                                $update_args['shipping_province'] = $address['province'];
                                $update_args['shipping_postal'] = $address['postal'];
                                $update_args['shipping_country'] = $address['country'];
                                $update_args['shipping_phone'] = $address['phone'];
                            }
                            if( ($address['flags']&0x02) == 0x02 ) {
                                $update_args['billing_address1'] = $address['address1'];
                                $update_args['billing_address2'] = $address['address2'];
                                $update_args['billing_city'] = $address['city'];
                                $update_args['billing_province'] = $address['province'];
                                $update_args['billing_postal'] = $address['postal'];
                                $update_args['billing_country'] = $address['country'];
                            }
                        }
                    }
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice', $args['invoice_id'], $update_args, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.251', 'msg'=>'Unable to find customer', 'err'=>$rc['err']));
                    }

                    //
                    // Check for callbacks
                    //
                    $strsql = "SELECT id, object, object_id "
                        . "FROM ciniki_sapos_invoice_items "
                        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
                        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                        . "";
                    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.272', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
                    }
                    if( isset($rc['rows']) ) {
                        $items = $rc['rows'];
                        foreach($items as $item) {
                            if( $item['object'] != '' && $item['object_id'] != '' ) {
                                list($pkg,$mod,$obj) = explode('.', $item['object']);
                                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'invoiceUpdate');
                                if( $rc['stat'] == 'ok' ) {
                                    $fn = $rc['function_call'];
                                    $rc = $fn($ciniki, $args['tnid'], $args['invoice_id'], $item);
                                    if( $rc['stat'] != 'ok' ) {
                                        return $rc;
                                    }
                                }
                            }
                        }
                    }
                    
                } else {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.252', 'msg'=>'Unable to find customer'));
                }
            }
        }
    }

    
    if( $args['invoice_id'] == 0 ) {
        $invoice = array(
            'id' => 0,
            'invoice_type' => 30,
            'status' => 10,
            'payment_status' => 0,
            'customer_id' => 0,
            'details' => array(
                array('label'=>'Invoice #', 'value'=>'New Invoice'),
                array('label'=>'Status', 'value'=>''),
                ),
            'customer_details' => array(),
            'items' => array(),
            'tallies' => array(),
            'messages' => array(),
            );

    } else {
        //
        // Return the invoice record
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'posInvoiceLoad');
        $rc = ciniki_sapos_posInvoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $invoice = $rc['invoice'];
        $invoice['details'] = array(
            array('label'=>'Invoice #', 'value'=>$invoice['invoice_number']),
            array('label'=>'Status', 'value'=>$invoice['status_text']),
            );

        //
        // Check if invoice is to be delete, that it doesn't have any items or transactions
        //
        if( isset($args['action']) && $args['action'] == 'deleteempty' ) {
            if( isset($invoice['items']) && count($invoice['items']) > 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.256', 'msg'=>'Unable to remove sale with items.'));
            }
            if( isset($invoice['transactions']) && count($invoice['transactions']) > 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.257', 'msg'=>'Unable to remove sale with transactions.'));
            }

            //
            // Remove the invoice
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sapos.invoice', $args['invoice_id'], NULL, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            return array('stat'=>'ok');
        }

        //
        // Check if there are any messages for this invoice
        //
        if( isset($ciniki['tenant']['modules']['ciniki.mail']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
            $rc = ciniki_mail_hooks_objectMessages($ciniki, $args['tnid'], 
                array('object'=>'ciniki.sapos.invoice', 'object_id'=>$invoice['id']));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['messages']) ) {
                $invoice['messages'] = $rc['messages'];
            }
        } 
    }

    $rsp = array('stat'=>'ok', 'invoice'=>$invoice);

    return $rsp;
}
?>
