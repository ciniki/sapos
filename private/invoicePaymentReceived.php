<?php
//
// Description
// -----------
// This function will update the status of an invoice based on the payments
// made.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_invoicePaymentReceived($ciniki, $tnid, $invoice_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the invoice details
    //
    $strsql = "SELECT customer_id, invoice_type, status, "
        . "receipt_number, "
        . "payment_status, shipping_status, manufacturing_status, "
        . "ROUND(total_amount, 2) AS total_amount, "
        . "ROUND(paid_amount, 2) AS paid_amount, "
        . "ROUND(balance_amount, 2) AS balance_amount "
        . "FROM ciniki_sapos_invoices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.235', 'msg'=>'Unable to locate the invoice'));
    }
    $invoice = $rc['invoice']; 
    
    //
    // Get the items on the invoice
    //
    $strsql = "SELECT id, object, object_id, price_id, student_id, quantity, invoice_id "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND object <> '' "
        . "AND object_id <> '' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $items = $rc['rows'];

    //
    // Check for hooks for each item
    //
    foreach($items as $iid => $item) {
        if( $item['object'] != '' && $item['object_id'] != '' ) {
            list($pkg,$mod,$obj) = explode('.', $item['object']);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemPaymentReceived');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $tnid, array(
                    'object'=>$item['object'],
                    'object_id'=>$item['object_id'],
                    'price_id'=>$item['price_id'],
                    'student_id'=>$item['student_id'],
                    'quantity'=>$item['quantity'],
                    'customer_id'=>$invoice['customer_id'],
                    'invoice_id'=>$item['invoice_id'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                //
                // Update the invoice item with the new object and object_id
                //
                if( (isset($rc['object']) && $rc['object'] != $item['object']) || (isset($rc['flags']) && $rc['flags'] != $args['flags'])) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item', $item['id'], $rc, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }
            
        }
    }

    return array('stat'=>'ok');
}
?>
