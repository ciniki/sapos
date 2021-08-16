<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_submitInvoice($ciniki, $settings, $tnid, $cart) {

    //
    // Load the current invoice_type and status
    //
    $strsql = "SELECT invoice_type, status, customer_id, receipt_number, payment_status, shipping_status "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['cart']['sapos_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.197', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.198', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
    }
    $invoice = $rc['invoice'];

    //
    // Get the current customer status
    //
    if( isset($invoice['customer_id']) && $invoice['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerStatus');
        $rc = ciniki_customers_hooks_customerStatus($ciniki, $tnid, array('customer_id'=>$invoice['customer_id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['customer']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.199', 'msg'=>'Customer does not exist for this invoice'));
        }
        $customer = $rc['customer'];
    }

    //
    // Update the invoice type and status
    //
    $args = array();
    if( $invoice['invoice_type'] == 20 ) {
        $args['invoice_type'] = 10;
    }
    if( $cart['payment_status'] > $invoice['payment_status'] ) {
        $args['payment_status'] = $cart['payment_status'];
    }
    if( $cart['status'] > $invoice['status'] ) {
        $args['status'] = $cart['status'];
    }

    if( isset($cart['paid_amount']) ) {
        $args['paid_amount'] = $cart['paid_amount'];
    }
    if( isset($cart['balance_amount']) ) {
        $args['balance_amount'] = $cart['balance_amount'];
    }

    //
    // Get the items for the invoice so they can be checked for donations
    //
    $strsql = "SELECT id, "
        . "flags, "
        . "quantity, "
        . "shipped_quantity, "
        . "discount_amount, "
        . "total_amount, "
        . "unit_donation_amount, "
        . "taxtype_id "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['cart']['sapos_id']) . "' "
        . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $donation_amount = 0;
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            if( ($item['flags']&0x8000) == 0x8000 ) {
                $donation_amount = bcadd($donation_amount, $item['total_amount'], 6);
            } elseif( ($item['flags']&0x0800) == 0x0800 ) {
                $donation_amount = bcadd($donation_amount, ($item['quantity'] * $item['unit_donation_amount']), 6);
            }
        }
    } else {
        $items = array();
    }

    //
    // Check if invoice should have a receipt_number
    //
    if( $donation_amount > 0 && ($invoice['invoice_type'] == 10 || $args['invoice_type'] == 10)
        && ($invoice['receipt_number'] == '' || $invoice['receipt_number'] == 0) 
        ) {
        $strsql = "SELECT detail_value "
            . "FROM ciniki_sapos_settings "
            . "WHERE detail_key = 'donation-receipt-next-number' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.389', 'msg'=>'Unable to load donation number', 'err'=>$rc['err']));
        }
        $receipt_number = isset($rc['item']['detail_value']) ? $rc['item']['detail_value'] : 1;

        //
        // Get the largest receipt number from the invoices
        //
        $strsql = "SELECT MAX(CAST(receipt_number AS UNSIGNED)) AS max_num "
            . "FROM ciniki_sapos_invoices "
            . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.229', 'msg'=>'Unable to find next available receipt number', 'err'=>$rc['err']));
        }
        if( isset($rc['num']['max_num']) && ($rc['num']['max_num']+1) > $receipt_number ) {
            $receipt_number = $rc['num']['max_num'] + 1;
        }

        //
        // Update settings with next receipt number
        //
        $next_receipt_number = $receipt_number + 1;
        $strsql = "INSERT INTO ciniki_sapos_settings (tnid, detail_key, detail_value, date_added, last_updated) "
            . "VALUES ('" . ciniki_core_dbQuote($ciniki, $tnid) . "'"
            . ", 'donation-receipt-next-number'"
            . ", '" . ciniki_core_dbQuote($ciniki, $next_receipt_number) . "'"
            . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
            . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $next_receipt_number) . "' "
            . ", last_updated = UTC_TIMESTAMP() "
            . "";
        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.sapos');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', $tnid, 
            2, 'ciniki_sapos_settings', 'donation-receipt-next-number', 'detail_value', $next_receipt_number);
    }

    if( isset($ciniki['session']['customer']['display_name']) ) {
        $args['submitted_by'] = $ciniki['session']['customer']['display_name'];
    } else {
        $args['submitted_by'] = '';
    }
    if( isset($ciniki['session']['customer']['email']) && $ciniki['session']['customer']['email'] != '' ) {
        $args['submitted_by'] .= ($args['submitted_by']!=''?' [' . $ciniki['session']['customer']['email'] . ']':$ciniki['session']['customer']['email']);
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', 
        $ciniki['session']['cart']['sapos_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return array('stat'=>'ok');
}
?>
