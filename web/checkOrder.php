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
function ciniki_sapos_web_checkOrder($ciniki, $settings, $business_id, $cart) {

    if( !isset($ciniki['session']['cart']['sapos_id']) || $ciniki['session']['cart']['sapos_id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.167', 'msg'=>'No order to submit'));
    }

    //
    // Check if customer is already logged in and a dealer/distributor
    //
    if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.168', 'msg'=>'Please login to submit the order.'));
    }
    
    if( (!isset($ciniki['session']['customer']['dealer_status']) 
            || $ciniki['session']['customer']['dealer_status'] <= 0
            || $ciniki['session']['customer']['dealer_status'] >= 60)
        && (!isset($ciniki['session']['customer']['distributor_status'])
            || $ciniki['session']['customer']['distributor_status'] != 10)
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.169', 'msg'=>"I'm sorry, but you are not allowed to submit orders."));
    }

    //
    // If specified required, make sure po_number is filled in
    //
    if( isset($settings['page-cart-po-number']) && $settings['page-cart-po-number'] == 'required' ) {
        if( $cart['po_number'] == '' ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.sapos.170', 'msg'=>'You must enter a Purchase Order Number before submitting your order.'));
        }
    }

    return array('stat'=>'ok');
}
?>
