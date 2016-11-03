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
function ciniki_sapos_web_submitOrder($ciniki, $settings, $business_id, $cart) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'checkOrder');
    $rc = ciniki_sapos_web_checkOrder($ciniki, $settings, $business_id, $cart);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current invoice_type and status
    //
    $strsql = "SELECT invoice_type, status, customer_id, payment_status, shipping_status "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['cart']['sapos_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.200', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.201', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
    }
    $invoice = $rc['invoice'];

    //
    // Get the current customer status
    //
    if( isset($invoice['customer_id']) && $invoice['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerStatus');
        $rc = ciniki_customers_hooks_customerStatus($ciniki, $business_id, 
            array('customer_id'=>$invoice['customer_id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['customer']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.202', 'msg'=>'Customer does not exist for this invoice'));
        }
        $customer = $rc['customer'];
    }

    //
    // Update the invoice type and status
    //
    $args = array();
    if( $invoice['invoice_type'] == 20 ) {
        $args['invoice_type'] = 40;
    }
    if( $invoice['status'] == 10 ) {
        $args['status'] = 30;
        // Check if order should be put on hold
        if( isset($customer) && isset($customer['status']) && $customer['status'] > 10 ) {
            $args['status'] = 15;
        }
    }
    if( isset($ciniki['session']['customer']['display_name']) ) {
        $args['submitted_by'] = $ciniki['session']['customer']['display_name'];
    } else {
        $args['submitted_by'] = '';
    }
    if( isset($ciniki['session']['customer']['email']) && $ciniki['session']['customer']['email'] != '' ) {
        $args['submitted_by'] .= ($args['submitted_by']!=''?' [' . $ciniki['session']['customer']['email'] . ']':$ciniki['session']['customer']['email']);
    }
//  if( $invoice['payment_status'] < 10 ) {
//      $args['payment_status'] = 10;
//  }
//  if( $invoice['shipping_status'] < 10 ) {
//      $args['shipping_status'] = 10;
//  }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice', 
        $ciniki['session']['cart']['sapos_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return array('stat'=>'ok');
}
?>
