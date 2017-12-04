<?php
//
// Description
// ===========
// This function will update an item details when the link was updated in another module.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceUpdateItem(&$ciniki, $tnid, $invoice_id, $item) {

    if( !isset($item['object']) || $item['object'] == '' || !isset($item['object_id']) && $item['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.24', 'msg'=>'No object specified for updating invoice'));
    }
    
    //
    // Get the existing item details
    //
    $strsql = "SELECT id, invoice_id, object, object_id, "
        . "quantity, unit_amount, unit_discount_amount, unit_discount_percentage, "
        . "subtotal_amount, discount_amount, total_amount "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND object = '" . ciniki_core_dbQuote($ciniki, $item['object']) . "' "
        . "AND object_id ='" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.25', 'msg'=>'Unable to locate the invoice item'));
    }
    $existing_item = $rc['item'];

    //
    // Check if quantity or unit_amount has changed, and update the amount
    //
    if( isset($item['quantity']) 
        || isset($item['unit_amount']) 
        || isset($item['unit_discount_amount']) 
        || isset($item['unit_discount_percentage']) 
        ) {

        //
        // Calculate the final amount for the item in the invoice
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
        $rc = ciniki_sapos_itemCalcAmount($ciniki, array(
            'quantity'=>(isset($item['quantity'])?$item['quantity']:$existing_item['quantity']),
            'unit_amount'=>(isset($item['unit_amount'])?$existing_item['unit_amount']:$existing_item['unit_amount']),
            'unit_discount_amount'=>(isset($existing_item['unit_discount_amount'])?$existing_item['unit_discount_amount']:$existing_item['unit_discount_amount']),
            'unit_discount_percentage'=>(isset($existing_item['unit_discount_percentage'])?$existing_item['unit_discount_percentage']:$existing_item['unit_discount_percentage']),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $item['subtotal_amount'] = $rc['subtotal'];
        $item['discount_amount'] = $rc['discount'];
        $item['total_amount'] = $rc['total'];
    }

    //
    // Update the item
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item', 
        $existing_item['id'], $item, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // No callbacks from here, it was already called from another module
    //
    
    //
    // Update the taxes
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
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
