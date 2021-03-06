<?php
//
// Description
// ===========
// This method will remove a shipment item from the system for an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_shipmentItemDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'sitem_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shipment Item'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shipmentItemDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Check that the item already exists in the shipment
    //
    $strsql = "SELECT id, shipment_id, item_id, quantity "
        . "FROM ciniki_sapos_shipment_items "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['sitem_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.118', 'msg'=>'Item does not exist.'));
    }
    $item = $rc['item'];

    //
    // Get the details of the shipment
    //
    $strsql = "SELECT id, invoice_id, status, shipment_number "
        . "FROM ciniki_sapos_shipments "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $item['shipment_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipment');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['shipment']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.119', 'msg'=>'Shipment does not exist.'));
    }
    $shipment = $rc['shipment'];

    //
    // Reject if shipment is already shipped
    //
    if( $shipment['status'] > 20 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.120', 'msg'=>'Shipment has already been shipped.'));
    }

    //
    // Get the details of the item from the invoice
    //
    $strsql = "SELECT id, invoice_id, object, object_id, quantity, shipped_quantity "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['item_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.121', 'msg'=>'Invoice does not exist.'));
    }
    $invoice_item = $rc['item'];

    //
    // Load the invoice/order number
    //
    $strsql = "SELECT invoice_number "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.103', 'msg'=>'Invoice does not exist.'));
    }
    $invoice = $rc['invoice'];

    $history_notes = 'Order #' . $invoice['invoice_number'] . '-' . $shipment['shipment_number'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // New quantity is less
    //
    $quantity_removed = $item['quantity'];

    //
    // Update the shipped quantity
    //
    $new_shipped_quantity = $invoice_item['shipped_quantity'] - $quantity_removed;
    if( $new_shipped_quantity < 0 ) {
        $new_shipped_quantity = 0;
    }
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', $invoice_item['id'], array('shipped_quantity'=>$new_shipped_quantity), 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.122', 'msg'=>'Unable to update the invoice.'));
    }

    //
    // Replace the quantity in inventory
    //
    if( $invoice_item['object'] != '' && $invoice_item['object_id'] != '' ) {
        list($pkg,$mod,$obj) = explode('.', $invoice_item['object']);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryReplace');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array(
                'object'=>$invoice_item['object'],
                'object_id'=>$invoice_item['object_id'],
                'quantity'=>$quantity_removed,
                'history_notes'=>(float)$quantity_removed . " replaced from " . $history_notes,
                ));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.123', 'msg'=>'Unable to replace inventory', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Update the item
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sapos.shipment_item', $args['sitem_id'], $args, 0x04);
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

    return array('stat'=>'ok');
}
?>
