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
function ciniki_sapos_hooks_invoiceAdd($ciniki, $business_id, $args) {
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $business_id, 'ciniki.sapos.invoiceAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Check if payment_status is used
    //
    if( ($ciniki['business']['modules']['ciniki.sapos']['flags']&0x0200) > 0 ) {
        if( !isset($args['payment_status']) || $args['payment_status'] == '0' ) {
            $args['payment_status'] = '10';
        }
    }

    if( !isset($args['invoice_date']) || $args['invoice_date'] == '' || $args['invoice_date'] == 'now' ) {
        $tz = new DateTimeZone($intl_timezone);
        $dt = new DateTime('now', $tz);
        $args['invoice_date'] = $dt->format('Y-m-d 12:00:00');
    }

    //
    // Set the user id who created the invoice
    //
    $args['user_id'] = $ciniki['session']['user']['id'];

    //
    // If a customer is specified, then lookup the customer details and fill out the invoice
    // based on the customer.  
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getCustomer');
        $rc = ciniki_sapos_getCustomer($ciniki, $business_id, $args);
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
        $rc = ciniki_sapos_lookupObjects($ciniki, $business_id, $args['objects']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.1', 'msg'=>'Unable to lookup invoice item reference', 'err'=>$rc['err']));
        }
        if( isset($rc['items']) ) {
            $invoice_items = $rc['items'];
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.2', 'msg'=>'Unable to find specified items.'));
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
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
    $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice', $args, 0x04);
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
        $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.sapos.invoice_item', $item, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        $item_id = $rc['id'];

        //
        // Check if there's a callback for the object
        //
        if( $item['object'] != '' && $item['object_id'] != '' ) {
            list($pkg,$mod,$obj) = explode('.', $item['object']);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemAdd');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $business_id, $invoice_id, $item);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                // Update the invoice item with the new object and object_id
                if( isset($rc['object']) && $rc['object'] != $item['object'] ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice_item', 
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
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $business_id, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the invoice status and balance 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $business_id, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, 'ciniki', 'sapos');

    return array('stat'=>'ok', 'id'=>$invoice_id);
}
?>
