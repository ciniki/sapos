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
function ciniki_sapos_web_cartItemPaymentReceived(&$ciniki, $settings, $tnid, $args) {

    //
    // Check that an item was specified
    //
    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.158', 'msg'=>'No cart specified'));
    }

    //
    // Check that an item was specified
    //
    if( !isset($args['item_id']) || $args['item_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.159', 'msg'=>'No item specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the existing item details
    //
    $strsql = "SELECT id, invoice_id, object, object_id, price_id, student_id, "
        . "quantity, unit_amount, unit_discount_amount, unit_discount_percentage, "
        . "subtotal_amount, discount_amount, total_amount "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
        . "AND invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.160', 'msg'=>'Unable to locate the invoice item'));
    }
    $item = $rc['item'];

    //
    // Lookup the object
    //
    if( $item['object'] != '' && $item['object_id'] != '' ) {
        list($pkg,$mod,$obj) = explode('.', $item['object']);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'cartItemPaymentReceived');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, $ciniki['session']['customer'], array(
                'object'=>$item['object'],
                'object_id'=>$item['object_id'],
                'price_id'=>$item['price_id'],
                'student_id'=>$item['student_id'],
                'quantity'=>$item['quantity'],
                'customer_id'=>$ciniki['session']['customer']['id'],
                'invoice_id'=>$item['invoice_id'],
                'total_amount'=>$item['total_amount'],
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
        
    return array('stat'=>'ok');
}
?>
