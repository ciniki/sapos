<?php
//
// Description
// -----------
// This function takes an existing cart that was created before the customer was logged in
// and adds the customer information to it when they login
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_cartCustomerUpdate($ciniki, $tnid, $request) {

    //
    // Check that a cart does not exist
    //
    if( isset($request['session']['cart']['sapos_id']) 
        && $request['session']['cart']['sapos_id'] > 0 
        && (!isset($request['session']['cart']['customer_id']) || $request['session']['cart']['customer_id'] == 0)
        && isset($request['session']['customer']['id'])
        && $request['session']['customer']['id'] > 0 
        ) {
        
        //
        // Check if customer is already logged in
        //
        $cart_args = array();
        if( isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 ) {
            $cart_args['customer_id'] = $request['session']['customer']['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getCustomer');
            $rc = ciniki_sapos_getCustomer($ciniki, $tnid, $cart_args);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $cart_args = $rc['args'];

            //
            // Update the cart
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', $request['session']['cart']['sapos_id'], $cart_args, 0x07);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.320', 'msg'=>'Internal error', 'err'=>$rc['err']));
            }

            //
            // Update the invoice
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
            $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $request['session']['cart']['sapos_id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.321', 'msg'=>'Unable to update cart', 'err'=>$rc['err']));
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
