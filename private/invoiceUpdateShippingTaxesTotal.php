<?php
//
// Description
// -----------
// This function will update the taxes for an invoice.  Taxes may be added or removed based on the items
// in the invoice.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $invoice_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the invoice details, so we know what taxes are applicable for the invoice date
    //
    $strsql = "SELECT ciniki_sapos_invoices.status, "
        . "ciniki_sapos_invoices.invoice_type, "
        . "ciniki_sapos_invoices.receipt_number, "
        . "ciniki_sapos_invoices.shipping_status, "
        . "ciniki_sapos_invoices.invoice_date, "
        . "ciniki_sapos_invoices.customer_id, "
        . "ciniki_sapos_invoices.shipping_amount, "
        . "ciniki_sapos_invoices.preorder_subtotal_amount, "
        . "ciniki_sapos_invoices.preorder_shipping_amount, "
        . "ciniki_sapos_invoices.preorder_total_amount, "
        . "ciniki_sapos_invoices.subtotal_amount, "
        . "ciniki_sapos_invoices.subtotal_discount_percentage, "
        . "ciniki_sapos_invoices.subtotal_discount_amount, "
        . "ciniki_sapos_invoices.discount_amount, "
        . "ciniki_sapos_invoices.total_amount, "
        . "ciniki_sapos_invoices.total_savings, "
        . "ciniki_sapos_invoices.shipping_address1, "
        . "ciniki_sapos_invoices.shipping_address2, "
        . "ciniki_sapos_invoices.shipping_city, "
        . "ciniki_sapos_invoices.shipping_province, "
        . "ciniki_sapos_invoices.shipping_postal, "
        . "ciniki_sapos_invoices.shipping_country, "
        . "ciniki_sapos_invoices.tax_location_id "
        . "FROM ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.26', 'msg'=>'Unable to find invoice'));
    }

    //
    // Setup the invoice hash to be passed to ciniki.taxes
    //
    $invoice = array(
        'status'=>$rc['invoice']['status'],
        'customer_id'=>$rc['invoice']['customer_id'],
        'date'=>$rc['invoice']['invoice_date'],
        'invoice_type'=>$rc['invoice']['invoice_type'],
        'receipt_number'=>$rc['invoice']['receipt_number'],
        'preorder_subtotal_amount'=>$rc['invoice']['preorder_subtotal_amount'],
        'preorder_shipping_amount'=>$rc['invoice']['preorder_shipping_amount'],
        'preorder_total_amount'=>$rc['invoice']['preorder_total_amount'],
        'subtotal_amount'=>$rc['invoice']['subtotal_amount'],
        'subtotal_discount_amount'=>$rc['invoice']['subtotal_discount_amount'],
        'subtotal_discount_percentage'=>$rc['invoice']['subtotal_discount_percentage'],
        'discount_amount'=>$rc['invoice']['discount_amount'],
        'shipping_amount'=>$rc['invoice']['shipping_amount'],
        'total_amount'=>$rc['invoice']['total_amount'],
        'total_savings'=>$rc['invoice']['total_savings'],
        'shipping_status'=>$rc['invoice']['shipping_status'],
        'shipping_address1'=>$rc['invoice']['shipping_address1'],
        'shipping_address2'=>$rc['invoice']['shipping_address2'],
        'shipping_city'=>$rc['invoice']['shipping_city'],
        'shipping_province'=>$rc['invoice']['shipping_province'],
        'shipping_postal'=>$rc['invoice']['shipping_postal'],
        'shipping_country'=>$rc['invoice']['shipping_country'],
        'tax_location_id'=>$rc['invoice']['tax_location_id'],
        'items'=>array(),
        );

    //
    // Check for a customer tax location if specified
    //
    if( isset($ciniki['tenant']['modules']['ciniki.taxes']['flags'])
        && ($ciniki['tenant']['modules']['ciniki.taxes']['flags']&0x01) > 0
        && $invoice['tax_location_id'] == 0 && $invoice['customer_id'] > 0 ) {
        $strsql = "SELECT tax_location_id "
            . "FROM ciniki_customers "
            . "WHERE ciniki_customers.id = '" . ciniki_core_dbQuote($ciniki, $invoice['customer_id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customer']) && $rc['customer']['tax_location_id'] > 0 ) {
            $invoice['tax_location_id'] = $rc['customer']['tax_location_id'];
        }
    }

    //
    // Get the items from the invoice
    //
    $strsql = "SELECT id, "
        . "flags, "
        . "quantity, "
        . "unit_preorder_amount, "
        . "shipped_quantity, "
        . "discount_amount, "
        . "total_amount, "
        . "unit_donation_amount, "
        . "taxtype_id "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
    } else {
        $items = array();
    }

    $invoice_preorder_subtotal_amount = 0;
    $invoice_preorder_shipping_amount = 0;
    $invoice_preorder_total_amount = 0;
    $invoice_subtotal_amount = 0;
    $invoice_shipping_amount = 0;
    $invoice_total_savings = 0;

    //
    // Build the hash of invoice details and items to pass to ciniki.taxes for tax calculations
    //
    $shipping_status = $invoice['shipping_status'];
    $donation_amount = 0;
    $shipping_required = 'no';
    $preorder_shipping_required = 'no';
    $invoice_taxtype_id = 0;
    if( count($items) > 0 ) {
        foreach($items as $iid => $item) {
            if( ($item['flags']&0x0440) == 0x0440 ) {
                $preorder_shipping_required = 'yes';
            } elseif( ($item['flags']&0x0440) == 0x0040 ) {
                $shipping_required = 'yes';
            }
            if( $item['taxtype_id'] != 0 && $item['taxtype_id'] != $invoice_taxtype_id ) {
                $invoice_taxtype_id = $item['taxtype_id'];
            }
            if( ($item['flags']&0x8000) == 0x8000 ) {
                $donation_amount = bcadd($donation_amount, $item['total_amount'], 6);
            }
            if( ($item['flags']&0x0800) == 0x0800 && $item['unit_donation_amount'] > 0 ) {
                $donation_amount = bcadd($donation_amount, ($item['quantity'] * $item['unit_donation_amount']), 6);
            }
            $invoice['items'][] = array(
                'id'=>$item['id'],
                'amount'=>$item['total_amount'],
                'preorder_amount'=>bcmul($item['unit_preorder_amount'], $item['quantity'], 2),
                'taxtype_id'=>$item['taxtype_id'],
                );
            $invoice_preorder_subtotal_amount = bcadd($invoice_preorder_subtotal_amount, 
                bcmul($item['unit_preorder_amount'], $item['quantity'], 2), 4);
            $invoice_subtotal_amount = bcadd($invoice_subtotal_amount, $item['total_amount'], 4);
            $invoice_total_savings = bcadd($invoice_total_savings, $item['discount_amount'], 4);
            // Check if shipping item
            if( ($item['flags']&0x0440) == 0x0040 ) {
                if( $item['shipped_quantity'] < $item['quantity'] ) {   
                    $shipping_status = 10;
                }
            }
        }
    }

    //
    // Check if invoice should have a receipt_number
    //
    if( $donation_amount > 0 && ($invoice['invoice_type'] == 10 || $invoice['invoice_type'] == 30)
        && ($invoice['receipt_number'] == '' || $invoice['receipt_number'] == 0) 
        ) {
        $strsql = "SELECT MAX(CAST(receipt_number AS UNSIGNED)) AS max_num "
            . "FROM ciniki_sapos_invoices "
            . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.225', 'msg'=>'Unable to find next available receipt number', 'err'=>$rc['err']));
        }
        if( isset($rc['num']['max_num']) ) {
            $receipt_number = $rc['num']['max_num'] + 1;
        } else {
            $receipt_number = 1;
        }
    }
    
    //
    // Calculate shipping costs, using simple ship rates
    //
    if( $invoice['shipping_status'] > 0 
        && ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x10000000) 
        && $invoice['customer_id'] > 0 
        ) {
        //
        // Load the shipping rates
        //
        $strsql = "SELECT ciniki_sapos_simpleshiprates.id, "
            . "ciniki_sapos_simpleshiprates.country, "
            . "ciniki_sapos_simpleshiprates.province, "
            . "ciniki_sapos_simpleshiprates.city, "
            . "ciniki_sapos_simpleshiprates.minimum_amount, "
            . "ciniki_sapos_simpleshiprates.rate "
            . "FROM ciniki_sapos_simpleshiprates "
            . "WHERE ciniki_sapos_simpleshiprates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (country = '' OR country = '" . ciniki_core_dbQuote($ciniki, $invoice['shipping_country']) . "') "
            . "AND (province = '' OR province = '" . ciniki_core_dbQuote($ciniki, $invoice['shipping_province']) . "') "
            . "AND (city = '' OR city = '" . ciniki_core_dbQuote($ciniki, $invoice['shipping_city']) . "') "
            . "ORDER BY country, province, city, minimum_amount, rate "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.285', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }

        $rates = isset($rc['rows']) ? $rc['rows'] : array();

        $new_ship = ($shipping_required == 'yes' ? 99999.99 : 0);
        $new_preorder_ship = ($preorder_shipping_required == 'yes' ? 99999.99 : 0);
        foreach($rates as $rate) {
            if( $rate['minimum_amount'] <= $invoice_subtotal_amount && $rate['rate'] < $new_ship ) {
                $new_ship = $rate['rate'];
            }
            if( $invoice_preorder_subtotal_amount > 0 
                && $rate['minimum_amount'] <= $invoice_preorder_subtotal_amount 
                && $rate['rate'] < $new_preorder_ship
                ) {
                $new_preorder_ship = $rate['rate'];
            }
        }
        if( $new_ship != 99999.99 ) {
            $invoice_shipping_amount = $new_ship;
        }
        if( $new_preorder_ship != 99999.99 ) {
            $invoice_preorder_shipping_amount = $new_preorder_ship;
        }
    }

    //
    // Pass to the taxes module to calculate the taxes
    //
    $tax_args = $invoice;
    $tax_args['shipping_amount'] = $invoice_shipping_amount;
    $tax_args['preorder_shipping_amount'] = $invoice_preorder_shipping_amount;
    $tax_args['taxtype_id'] = $invoice_taxtype_id;
    ciniki_core_loadMethod($ciniki, 'ciniki', 'taxes', 'private', 'calcInvoiceTaxes');
    $rc = ciniki_taxes_calcInvoiceTaxes($ciniki, $tnid, $tax_args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $new_taxes = $rc['taxes'];
    $new_preorder_taxes = isset($rc['preorder_taxes']) ? $rc['preorder_taxes'] : array();

    //
    // Get the existing taxes for the invoice
    //
    $strsql = "SELECT id, uuid, flags, taxrate_id, description, amount "
        . "FROM ciniki_sapos_invoice_taxes "
        . "WHERE ciniki_sapos_invoice_taxes.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND ciniki_sapos_invoice_taxes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//        . "AND (ciniki_sapos_invoice_taxes.flags&0x02) = 0 "
        . "";
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.sapos', 'taxes', 'id');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $old_taxes = array();
    $old_preorder_taxes = array();
    if( isset($rc['taxes']) ) {
        foreach($rc['taxes'] as $tid => $tax) {
            if( ($tax['flags']&0x02) == 0x02 ) {
                $old_preorder_taxes[$tax['taxrate_id']] = $tax;
            } else {
                $old_taxes[$tax['taxrate_id']] = $tax;
            }
        }
    }
    
    //
    // Check if invoice taxes need to be updated or added 
    //
    $invoice_tax_amount = 0;
    $included_tax_amount = 0;
    foreach($new_taxes as $tid => $tax) {
        $tax_amount = bcadd($tax['calculated_items_amount'], $tax['calculated_invoice_amount'], 4);
        if( isset($old_taxes[$tid]) ) {
            $args = array();
            if( $tax_amount != $old_taxes[$tid]['amount'] ) {
                $args['amount'] = $tax_amount;
            }
            // Check if the name is different, perhaps it was updated
            if( $tax['name'] != $old_taxes[$tid]['description'] ) {
                $args['description'] = $tax['name'];
            }
            if( count($args) > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_tax', 
                    $old_taxes[$tid]['id'], $args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        } else {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.invoice_tax', 
                array(
                    'invoice_id'=>$invoice_id,
                    'taxrate_id'=>$tid,
                    'flags'=>$tax['flags'],
                    'line_number'=>0,
                    'description'=>$tax['name'],
                    'amount'=>$tax_amount,
                    ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
        //
        // Keep track of the total taxes for the invoice
        //
        if( ($tax['flags']&0x01) == 0x01 ) {
            $included_tax_amount = bcadd($included_tax_amount, $tax_amount, 4);
        } else {
            $invoice_tax_amount = bcadd($invoice_tax_amount, $tax_amount, 4);
        }
    }

    //
    // Check if any taxes are no longer applicable
    //
    foreach($old_taxes as $tid => $tax) {
        if( !isset($new_taxes[$tid]) ) {
            // Remove the tax
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice_tax', $tax['id'], $tax['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Check preorder taxes
    //
    $invoice_preorder_tax_amount = 0;
    $included_preorder_tax_amount = 0;
    foreach($new_preorder_taxes as $tid => $tax) {
        $tax_amount = bcadd($tax['calculated_items_amount'], $tax['calculated_invoice_amount'], 4);
        if( isset($old_preorder_taxes[$tid]) ) {
            $args = array();
            if( $tax_amount != $old_preorder_taxes[$tid]['amount'] ) {
                $args['amount'] = $tax_amount;
            }
            // Check if the name is different, perhaps it was updated
            if( $tax['name'] != $old_preorder_taxes[$tid]['description'] ) {
                $args['description'] = $tax['name'];
            }
            if( count($args) > 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_tax', 
                    $old_preorder_taxes[$tid]['id'], $args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        } else {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.invoice_tax', 
                array(
                    'invoice_id'=>$invoice_id,
                    'taxrate_id'=>$tid,
                    'flags'=> ($tax['flags'] | 0x02),
                    'line_number'=>0,
                    'description'=>$tax['name'],
                    'amount'=>$tax_amount,
                    ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.286', 'msg'=>'Unable to add tax', 'err'=>$rc['err']));
            }
        }
        //
        // Keep track of the total taxes for the invoice
        //
        if( ($tax['flags']&0x01) == 0x01 ) {
            $included_preorder_tax_amount = bcadd($included_preorder_tax_amount, $tax_amount, 4);
        } else {
            $invoice_preorder_tax_amount = bcadd($invoice_preorder_tax_amount, $tax_amount, 4);
        }
    }

    //
    // Check if any taxes are no longer applicable
    //
    foreach($old_preorder_taxes as $tid => $tax) {
        if( !isset($new_preorder_taxes[$tid]) ) {
            // Remove the tax
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice_tax', $tax['id'], $tax['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.287', 'msg'=>'Unable to remove old tax', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Check if there are any discounts to be applied for the entire invoice
    //
    $invoice_discount_amount = 0;
    if( $invoice['subtotal_discount_amount'] != 0 ) {
        $invoice_discount_amount = bcadd($invoice_discount_amount, $invoice['subtotal_discount_amount'], 4);
    }
    if( $invoice['subtotal_discount_percentage'] != 0 ) {
        $discount_percent = bcdiv($invoice['subtotal_discount_percentage'], 100, 4);
        if( $invoice_discount_amount > 0 ) {
            $discount_amount = bcmul(
                bcsub($invoice_subtotal_amount, $invoice_discount_amount, 4) , $discount_percent, 4);
        } else {
            $discount_amount = bcmul($invoice_subtotal_amount, $discount_percent, 4);
        }
        $invoice_discount_amount = bcadd($invoice_discount_amount, $discount_amount, 4);
    }
    
    //
    // Update the totals
    //
    $invoice_preorder_total_amount = bcadd($invoice_preorder_subtotal_amount, $invoice_preorder_shipping_amount, 4);
    $invoice_preorder_total_amount = bcadd($invoice_preorder_total_amount, $invoice_preorder_tax_amount, 4);

    // Subtract the discount, add the shipping amount, add the taxes
    $invoice_total_amount = bcsub($invoice_subtotal_amount, $invoice_discount_amount, 4);
    $invoice_total_amount = bcadd($invoice_total_amount, $invoice_shipping_amount, 4);
    $invoice_total_amount = bcadd($invoice_total_amount, $invoice_tax_amount, 4);

    $invoice_total_savings = bcadd($invoice_total_savings, $invoice_discount_amount, 4);

    $args = array();
    if( $invoice_preorder_subtotal_amount != floatval($invoice['preorder_subtotal_amount']) ) {
        $args['preorder_subtotal_amount'] = $invoice_preorder_subtotal_amount;
    }
    if( $invoice_preorder_shipping_amount != floatval($invoice['preorder_shipping_amount']) ) {
        $args['preorder_shipping_amount'] = $invoice_preorder_shipping_amount;
    }
    if( $invoice_preorder_total_amount != floatval($invoice['preorder_total_amount']) ) {
        $args['preorder_total_amount'] = $invoice_preorder_total_amount;
    }
    if( $invoice_subtotal_amount != floatval($invoice['subtotal_amount']) ) {
        $args['subtotal_amount'] = $invoice_subtotal_amount;
    }
    if( $invoice_discount_amount != floatval($invoice['discount_amount']) ) {
        $args['discount_amount'] = $invoice_discount_amount;
    }
    if( $invoice_shipping_amount != floatval($invoice['shipping_amount']) ) {
        $args['shipping_amount'] = $invoice_shipping_amount;
    }
    if( $invoice_total_amount != floatval($invoice['total_amount']) ) {
        $args['total_amount'] = $invoice_total_amount;
    }
    if( $invoice_total_savings != floatval($invoice['total_savings']) ) {
        $args['total_savings'] = $invoice_total_savings;
    }
    if( $shipping_status != $invoice['shipping_status'] ) {
        $args['shipping_status'] = $shipping_status;
    }
    if( isset($receipt_number) && $receipt_number != $invoice['receipt_number'] ) {
        $args['receipt_number'] = $receipt_number;
    }
    if( count($args) > 0 ) {
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', 
            $invoice_id, $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok', 'total_amount'=>$invoice_total_amount);
}
?>
