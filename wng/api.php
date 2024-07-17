<?php
//
// Description
// -----------
// This function will process api requests for wng.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get sapos request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_sapos_wng_api(&$ciniki, $tnid, &$request) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sapos.269', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Check if start to stripe session
    //
    if( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'invoiceStripeIntentCreate' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'apiInvoiceStripeIntentCreate');
        return ciniki_sapos_wng_apiInvoiceStripeIntentCreate($ciniki, $tnid, $request); 
    }
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'invoiceStripeIntentCancel' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'apiInvoiceStripeIntentCancel');
        return ciniki_sapos_wng_apiInvoiceStripeIntentCancel($ciniki, $tnid, $request); 
    }
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'invoiceCheckoutCreate' 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'apiInvoiceCheckoutCreate');
        return ciniki_sapos_wng_apiInvoiceCheckoutCreate($ciniki, $tnid, $request); 
    }

    //
    // If no cart created, then create one now
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartLoad');
    $rc = ciniki_sapos_wng_cartLoad($ciniki, $tnid, $request);
    if( $rc['stat'] == 'noexist' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCreate');
        $rc = ciniki_sapos_wng_cartCreate($ciniki, $tnid, $request);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.277', 'msg'=>'Unable to create shopping cart. Please try again or contact us for help.'));
        }
//        $request['session']['cart'] = array();
//        $request['session']['cart']['sapos_id'] = $rc['sapos_id'];
//        $request['session']['cart']['num_items'] = 0;
    } elseif( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.271', 'msg'=>'Unable to load cart. Please try again or contact us for help.', 'err'=>$rc['err']));
    } else {
//        $request['session']['cart'] = $rc['cart'];
//        $request['session']['cart']['num_items'] = count($request['session']['cart']['items']);
    }

    //
    // Check if no customer, create dummy information
    //
    if( !isset($request['session']['customer']) ) {
        $request['session']['customer'] = array(
            'price_flags'=>0x01,
            'first'=>'',
            'last'=>'',
            'display_name'=>'',
            'email'=>'',
            );
    }

    //
    // cartLoad - Load the cart for the customer/session
    //
    if( isset($request['uri_split'][$request['cur_uri_pos']]) && $request['uri_split'][$request['cur_uri_pos']] == 'cartLoad' ) {
        return array('stat'=>'ok', 'cart'=>$request['session']['cart']);
    }

    //
    // cartItemAdd - Add an item to the cart
    //
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'cartItemAdd' 
        && isset($request['args']['object'])
        && isset($request['args']['object_id'])
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemAdd');
        return ciniki_sapos_wng_cartItemAdd($ciniki, $tnid, $request, array(
            'object'=>($request['args']['object'] ? $request['args']['object'] : ''),
            'object_id'=>($request['args']['object_id'] ? $request['args']['object_id'] : ''),
            'quantity'=>(isset($request['args']['quantity']) && $request['args']['quantity'] ? $request['args']['quantity'] : '1'),
            'price_id'=>(isset($request['args']['price_id']) && $request['args']['price_id'] ? $request['args']['price_id'] : '0'),
            ));
    }
    
    //
    // cartItemAdd - Add an item to the cart
    //
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'cartItemUpdate' 
        && isset($request['args']['item_id'])
        && isset($request['args']['quantity']) 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemUpdate');
        return ciniki_sapos_wng_cartItemUpdate($ciniki, $tnid, $request, array(
            'item_id'=>($request['args']['item_id'] ? $request['args']['item_id'] : '0'),
            'quantity'=>($request['args']['quantity'] ? $request['args']['quantity'] : '1'),
            ));
    }
    
    //
    // cartItemRemove - Remove an item to the cart
    //
    elseif( isset($request['uri_split'][$request['cur_uri_pos']]) 
        && $request['uri_split'][$request['cur_uri_pos']] == 'cartItemDelete' 
        && isset($request['args']['item_id'])
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemDelete');
        return ciniki_sapos_wng_cartItemDelete($ciniki, $tnid, $request, array(
            'item_id'=>($request['args']['item_id'] ? $request['args']['item_id'] : '0'),
            ));
    }
    
    return array('stat'=>'ok');
}
?>
