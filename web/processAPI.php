<?php
//
// Description
// -----------
// This function will process api requests for web.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get sapos request for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_sapos_web_processAPI(&$ciniki, $settings, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sapos.269', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

/*    //
    // Check to make sure customer is logged in
    //
    if( !isset($ciniki['session']['customer']['id']) || $ciniki['session']['customer']['id'] < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sapos.270', 'msg'=>"I'm sorry, but you must be logged in to do that.")); 
    } */

    //
    // If no cart created, then create one now
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartLoad');
    $rc = ciniki_sapos_web_cartLoad($ciniki, $settings, $tnid);
    if( $rc['stat'] == 'noexist' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartCreate');
        $rc = ciniki_sapos_web_cartCreate($ciniki, $settings, $ciniki['request']['tnid'], array());
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.277', 'msg'=>'Unable to create shopping cart. Please try again or contact us for help.'));
        }
        $cart = $_SESSION['cart'];
        $_SESSION['cart']['num_items'] = 0;
        $ciniki['session']['cart']['num_items'] = 0;
    } elseif( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.271', 'msg'=>'Unable to load cart. Please try again or contact us for help.', 'err'=>$rc['err']));
    } else {
        $cart = $rc['cart'];
        $_SESSION['cart']['num_items'] = count($cart['items']);
        $ciniki['session']['cart']['num_items'] = count($cart['items']);
    }

    //
    // Check if no customer, create dummy information
    //
    if( !isset($ciniki['session']['customer']) ) {
        $_SESSION['customer'] = array(
            'price_flags'=>0x01,
            'pricepoint_id'=>0,
            'first'=>'',
            'last'=>'',
            'display_name'=>'',
            'email'=>'',
            );
        $ciniki['session']['customer'] = $_SESSION['customer'];
    }


    //
    // cartLoad - Load the cart for the customer/session
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'cartLoad' ) {
        return array('stat'=>'ok', 'cart'=>$cart);
    }

    //
    // cartItemAdd - Add an item to the cart
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'cartItemAdd' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartItemAdd');
        return ciniki_sapos_web_cartItemAdd($ciniki, $settings, $tnid, array(
            'object'=>($ciniki['request']['args']['object'] ? $ciniki['request']['args']['object'] : ''),
            'object_id'=>($ciniki['request']['args']['object_id'] ? $ciniki['request']['args']['object_id'] : ''),
            'quantity'=>($ciniki['request']['args']['quantity'] ? $ciniki['request']['args']['quantity'] : '1'),
            'price_id'=>($ciniki['request']['args']['price_id'] ? $ciniki['request']['args']['price_id'] : '0'),
            ));
    }
    
    //
    // cartItemAdd - Add an item to the cart
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'cartItemUpdate' 
        && isset($ciniki['request']['args']['item_id'])
        && isset($ciniki['request']['args']['quantity']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartItemUpdate');
        return ciniki_sapos_web_cartItemUpdate($ciniki, $settings, $tnid, array(
            'item_id'=>($ciniki['request']['args']['item_id'] ? $ciniki['request']['args']['item_id'] : '0'),
            'quantity'=>($ciniki['request']['args']['quantity'] ? $ciniki['request']['args']['quantity'] : '1'),
            ));
    }
    
    //
    // cartItemRemove - Remove an item to the cart
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'cartItemDelete' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartItemDelete');
        return ciniki_sapos_web_cartItemDelete($ciniki, $settings, $tnid, array(
            'item_id'=>($ciniki['request']['args']['item_id'] ? $ciniki['request']['args']['item_id'] : '0'),
            ));
    }
    
    return array('stat'=>'ok');
}
?>
