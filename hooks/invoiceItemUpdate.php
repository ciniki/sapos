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
function ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $tnid, $args) {

    if( !isset($args['item_id']) || $args['item_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.9', 'msg'=>'No item specified.'));
    }

    //
    // Get the existing item details
    //
    $strsql = "SELECT id, invoice_id, object, object_id, "
        . "quantity, unit_amount, unit_discount_amount, unit_discount_percentage, unit_preorder_amount, price_id, "
        . "subtotal_amount, discount_amount, total_amount "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.10', 'msg'=>'Unable to locate the invoice item'));
    }
    $item = $rc['item'];

    //
    // Check if item is to move invoices
    //
    if( isset($args['new_invoice_id']) ) {
        if( $args['new_invoice_id'] == 0 ) {
            //
            // FIXME: Create a new invoice based on old invoice information
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddFromExisting');
            $rc = ciniki_sapos_invoiceAddFromExisting($ciniki, $tnid, $item['invoice_id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $args['new_invoice_id'] = $rc['id'];
        }

        $args['invoice_id'] = $args['new_invoice_id'];
    }

    //
    // Check if quantity or unit_amount has changed, and update the amount
    //
    if( isset($args['quantity']) 
        || isset($args['unit_amount']) 
        || isset($args['unit_discount_amount']) 
        || isset($args['unit_discount_percentage']) 
        || isset($args['unit_preorder_amount']) 
        ) {

        //
        // Calculate the final amount for the item in the invoice
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
        $rc = ciniki_sapos_itemCalcAmount($ciniki, array(
            'quantity'=>(isset($args['quantity'])?$args['quantity']:$item['quantity']),
            'unit_amount'=>(isset($args['unit_amount'])?$args['unit_amount']:$item['unit_amount']),
            'unit_discount_amount'=>(isset($args['unit_discount_amount'])?$args['unit_discount_amount']:$item['unit_discount_amount']),
            'unit_discount_percentage'=>(isset($args['unit_discount_percentage'])?$args['unit_discount_percentage']:$item['unit_discount_percentage']),
            'unit_preorder_amount'=>(isset($args['unit_preorder_amount'])?$args['unit_preorder_amount']:$item['unit_preorder_amount']),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $args['subtotal_amount'] = $rc['subtotal'];
        $args['discount_amount'] = $rc['discount'];
        $args['total_amount'] = $rc['total'];
    }

    //
    // Update the item
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item', $args['item_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

//
// Callbacks may loop, don't do them
//

    //
    // Update the item values for callbacks
    //
//  if( isset($args['quantity']) && $args['quantity'] != $item['quantity'] ) {
//      $item['old_quantity'] = $item['quantity'];
//      $item['quantity'] = $args['quantity'];
//  }

    //
    // Check for a callback to the object
    //
//  if( $item['object'] != '' && $item['object_id'] != '' ) {
//      list($pkg,$mod,$obj) = explode('.', $item['object']);
//      $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemUpdate');
//      if( $rc['stat'] == 'ok' ) {
//          $fn = $rc['function_call'];
//          $rc = $fn($ciniki, $tnid, $item['invoice_id'], $item);
//          if( $rc['stat'] != 'ok' ) {
//              return $rc;
//          }
//      }
//  }

    //
    // Update the taxes
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $item['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $item['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // If the item moved invoiced, update the new invoice
    //
    if( isset($args['invoice_id']) && $args['invoice_id'] != $item['invoice_id'] ) {
        //
        // Update the taxes
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
        $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $args['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }

        //
        // Update the invoice status
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
        $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $args['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
            return $rc;
        }

        return array('stat'=>'ok', 'invoice_id'=>$args['invoice_id']);
    }

    return array('stat'=>'ok');
}
?>
