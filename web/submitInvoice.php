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
function ciniki_sapos_web_submitInvoice($ciniki, $settings, $business_id, $cart) {

    //
    // Load the current invoice_type and status
    //
    $strsql = "SELECT invoice_type, status, customer_id, payment_status, shipping_status "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['cart']['sapos_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
        $rc = ciniki_customers_hooks_customerStatus($ciniki, $business_id, array('customer_id'=>$invoice['customer_id']));
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

    if( isset($ciniki['session']['customer']['display_name']) ) {
        $args['submitted_by'] = $ciniki['session']['customer']['display_name'];
    } else {
        $args['submitted_by'] = '';
    }
    if( isset($ciniki['session']['customer']['email']) && $ciniki['session']['customer']['email'] != '' ) {
        $args['submitted_by'] .= ($args['submitted_by']!=''?' [' . $ciniki['session']['customer']['email'] . ']':$ciniki['session']['customer']['email']);
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice', 
        $ciniki['session']['cart']['sapos_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    return array('stat'=>'ok');
}
?>
