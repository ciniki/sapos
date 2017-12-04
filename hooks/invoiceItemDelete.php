<?php
//
// Description
// -----------
// This function will return the list of invoices for a customer.
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_hooks_invoiceItemDelete($ciniki, $tnid, $args) {

    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' 
        || !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.3', 'msg'=>'No invoice or item specified.'));
    }

    //
    // Load the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['settings'])?$rc['settings']:array();

    //
    // Get the details of the item
    //
    $strsql = "SELECT id, uuid, invoice_id, object, object_id, quantity "
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
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.4', 'msg'=>'Unable to find invoice item'));
    }
    $item = $rc['item'];

    //
    // Check to make sure the invoice belongs to the salesrep
    //
    if( isset($ciniki['tenant']['user']['perms']) && ($ciniki['tenant']['user']['perms']&0x07) == 0x04 ) {
        $strsql = "SELECT id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $item['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.5', 'msg'=>'Permission denied'));
        }
    }

    //
    // Check to make sure the invoice hasn't been paid
    //
    $strsql = "SELECT id, uuid, status "
        . "FROM ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $item['invoice_id']) . "' "
        . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.6', 'msg'=>'Invoice does not exist'));
    }
    $invoice = $rc['invoice'];

    //
    // Invoice has already been paid, we don't want to remove this item
    //
    if( $invoice['status'] >= 50 && (!isset($settings['rules-invoice-paid-change-items']) || $settings['rules-invoice-paid-change-items'] == 'no')) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.7', 'msg'=>'Invoice has been paid, unable to remove item'));
    }

    //
    // Check to make sure the item isn't part of a shipment
    //
    $strsql = "SELECT id "
        . "FROM ciniki_sapos_shipment_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND item_id = '" . ciniki_core_dbQuote($ciniki, $item['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.8', 'msg'=>'Item is part of a shipment and cannot be removed.'));
    }

    //
    // Remove the item
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice_item', 
        $item['id'], $item['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if invoice should be deleted when nothing in it
    //
    if( $invoice['status'] < 50 && isset($args['deleteinvoice']) && $args['deleteinvoice'] == 'yes' ) {
        $remove = 'yes';
        //
        // Check for invoice items
        //
        $strsql = "SELECT COUNT(ciniki_sapos_invoice_items.id) "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice['id']) . "' "
            . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sapos_invoice_items.id <> '" . ciniki_core_dbQuote($ciniki, $item['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num_items');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['num_items']) || $rc['num_items'] != 0 ) {
            $remove = 'no';
        }
        //
        // Check for invoice shipments
        //
        $strsql = "SELECT COUNT(ciniki_sapos_shipments.id) "
            . "FROM ciniki_sapos_shipments "
            . "WHERE ciniki_sapos_shipments.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice['id']) . "' "
            . "AND ciniki_sapos_shipments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num_items');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['num_items']) || $rc['num_items'] != 0 ) {
            $remove = 'no';
        }
        //
        // Check for invoice transactions
        //
        $strsql = "SELECT COUNT(ciniki_sapos_transactions.id) "
            . "FROM ciniki_sapos_transactions "
            . "WHERE ciniki_sapos_transactions.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice['id']) . "' "
            . "AND ciniki_sapos_transactions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num_items');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['num_items']) || $rc['num_items'] != 0 ) {
            $remove = 'no';
        }

        if( $remove == 'yes' ) {
            //
            // Remove the invoice
            //
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice', $invoice['id'], $invoice['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return $rc;
            }
        }
    }

    if( !isset($remove) || $remove != 'yes' ) {
        //
        // Update the invoice status
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
        $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $item['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the invoice status
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
        $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $item['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'sapos');

    return array('stat'=>'ok');
}
?>
