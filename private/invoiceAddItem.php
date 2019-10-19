<?php
//
// Description
// ===========
// This method will add a new item to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceAddItem($ciniki, $tnid, $args) {
    //
    // Load the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['settings'])?$rc['settings']:array();

    if( !isset($args['unit_discount_percentage']) || $args['unit_discount_percentage'] == '' ) {
        $args['unit_discount_percentage'] = 0;
    }
    if( !isset($args['unit_preorder_amount']) || $args['unit_preorder_amount'] == '' ) {
        $args['unit_preorder_amount'] = 0;
    }

    //
    // Load the invoice salesrep
    //
    $strsql = "SELECT id, customer_id, salesrep_id "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.45', 'msg'=>'Invoice not found.'));
    }
    $invoice = $rc['invoice'];

    //
    // Check to make sure the invoice belongs to the salesrep
    //
    if( isset($ciniki['tenant']['user']['perms']) && ($ciniki['tenant']['user']['perms']&0x07) == 0x04 ) {
        if( $invoice['salesrep_id'] != $ciniki['session']['user']['id'] ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.68', 'msg'=>'Permission denied'));
        }
    }

    //
    // Get the max line_number for this invoice
    //
    if( !isset($args['line_number']) || $args['line_number'] == '' || $args['line_number'] == 0 ) {
        $strsql = "SELECT MAX(line_number) AS maxnum "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']) && isset($rc['num']['maxnum']) ) {
            $args['line_number'] = intval($rc['num']['maxnum']) + 1;
        } else {
            $args['line_number'] = 1;
        }
    }

    //
    // Check if item already exists in the invoice
    //
    $existing_id = 0;
    if( isset($settings['rules-invoice-duplicate-items'])
        && $settings['rules-invoice-duplicate-items'] == 'no' 
        ) {
        $strsql = "SELECT id, invoice_id, object, object_id, "
            . "quantity, unit_amount, unit_discount_amount, unit_discount_percentage, unit_preorder_amount, price_id, "
            . "subtotal_amount, discount_amount, total_amount "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) && isset($rc['rows'][0]) ) {
            $existing_id = $rc['rows'][0]['id'];
            $item = $rc['rows'][0];
        }
    }

    //
    // Get any information available on the object, required to decide if should be split to 1 per line
    //
    $num_lines = 1;
    if( $args['object'] != '' && $args['object_id'] != '' ) {
        list($pkg,$mod,$obj) = explode('.', $args['object']);
        if( !isset($args['pricepoint_id']) ) {
            $args['pricepoint_id'] = 0;
        }
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemLookup');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, $args);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['item']) ) {
                // 
                // Check if the item must only be one per line
                //
                if( isset($rc['item']['flags']) && ($rc['item']['flags']&0x08) > 0 ) {
                    // Each line must only contain one item
                    $num_lines = $args['quantity'];
                    $args['quantity'] = 1;
                    // Force each on it's own invoice line
                    $existing_id = 0;             
                }
                if( isset($rc['item']['flags']) && is_numeric($rc['item']['flags']) ) {
                    if( !isset($args['flags']) || $args['flags'] == '' ) {
                        $args['flags'] = $rc['item']['flags'];
                    } else {
                        $args['flags'] |= $rc['item']['flags'];
                    }
                }
                //
                // Setup any missing fields
                //
                foreach($rc['item'] as $k => $v) {
                    if( !isset($args[$k]) ) {
                        $args[$k] = $v;
                    }
                }
                
                //
                // Check if synopsis should be notes
                //
                if( isset($settings['quote-notes-product-synopsis']) && $settings['quote-notes-product-synopsis'] != '' 
                    && isset($rc['item']['synopsis']) 
                    ) {
                    $args['notes'] = $rc['item']['synopsis'];
                }
            }
        }
    }

    //
    // Check if a global customer discount
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.customers', 0x08000000) ) {
        $strsql = "SELECT discount_percent "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $invoice['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customer']['discount_percent']) && $rc['customer']['discount_percent'] > 0 ) {
            $args['unit_discount_percentage'] = $rc['customer']['discount_percent'];
        }
    }

    //
    // Calculate the final amount for each item in the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
    $rc = ciniki_sapos_itemCalcAmount($ciniki, array(
        'quantity'=>$args['quantity'],
        'unit_amount'=>$args['unit_amount'],
        'unit_discount_amount'=>$args['unit_discount_amount'],
        'unit_discount_percentage'=>$args['unit_discount_percentage'],
        'unit_preorder_amount'=>$args['unit_preorder_amount'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args['subtotal_amount'] = $rc['subtotal'];
    $args['discount_amount'] = $rc['discount'];
    $args['total_amount'] = $rc['total'];

    $item_id = 0;
    for($line_num = 1; $line_num<=$num_lines; $line_num++) {
        if( $existing_id == 0 ) {
            //
            // Check for auto categories
            //
            if( (!isset($args['category']) || $args['category'] != '') && isset($args['object']) && isset($settings['invoice-autocat-' . $args['object']]) ) {
                $args['category'] = $settings['invoice-autocat-' . $args['object']];
            }

            //
            // Add the item
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.invoice_item', $args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $item_id = $rc['id'];

            //
            // Check for a callback to the object
            //
            if( $args['object'] != '' && $args['object_id'] != '' ) {
                list($pkg,$mod,$obj) = explode('.', $args['object']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemAdd');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $tnid, $args['invoice_id'], $args);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    // Update the invoice item with the new object and object_id
                    if( (isset($rc['object']) && $rc['object'] != $args['object'])
                        || (isset($rc['flags']) && $rc['flags'] != $args['flags'])
                        ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item', 
                            $item_id, $rc, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                    }
                }
            }
        } else {
            //
            // If item object already exists in invoice, then add
            //

            //
            // Calculate the final amount for the item in the invoice
            //
            $item['old_quantity'] = $item['quantity'];
            $new_args = array('quantity'=>($item['quantity'] + $args['quantity']));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
            $rc = ciniki_sapos_itemCalcAmount($ciniki, array(
                'quantity'=>$new_args['quantity'],
                'unit_amount'=>(isset($args['unit_amount'])?$args['unit_amount']:$item['unit_amount']),
                'unit_discount_amount'=>(isset($args['unit_discount_amount'])?$args['unit_discount_amount']:$item['unit_discount_amount']),
                'unit_discount_percentage'=>(isset($args['unit_discount_percentage'])?$args['unit_discount_percentage']:$item['unit_discount_percentage']),
                'unit_preorder_amount'=>(isset($args['unit_preorder_amount'])?$args['unit_preorder_amount']:$item['unit_preorder_amount']),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $new_args['subtotal_amount'] = $rc['subtotal'];
            $new_args['discount_amount'] = $rc['discount'];
            $new_args['total_amount'] = $rc['total'];

            //
            // Update the item
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item', 
                $item['id'], $new_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            
            //
            // Update the item values for callbacks
            //
            if( isset($args['quantity']) && $args['quantity'] != $item['quantity'] ) {
                $item['old_quantity'] = $item['quantity'];
                $item['quantity'] = $args['quantity'];
            }

            //
            // Check for a callback to the object
            //
            if( $item['object'] != '' && $item['object_id'] != '' ) {
                list($pkg,$mod,$obj) = explode('.', $item['object']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemUpdate');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $tnid, $item['invoice_id'], $item);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }
            $item_id = $item['id'];
        }
        $args['line_number']++;
    }

    //
    // Update the taxes
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'id'=>$item_id);
}
?>
