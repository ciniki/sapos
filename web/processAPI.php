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
    // cartLoad - Load the cart for the customer/session
    //
    if( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'cartLoad' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartLoad');
        return ciniki_sapos_web_cartLoad($ciniki, $settings, $tnid);
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
    // cartItemRemove - Remove an item to the cart
    //
    elseif( isset($args['uri_split'][0]) && $args['uri_split'][0] == 'cartItemDelete' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartItemDelete');
        error_log($ciniki['request']['args']['item_id']);
        return ciniki_sapos_web_cartItemDelete($ciniki, $settings, $tnid, array(
            'item_id'=>($ciniki['request']['args']['item_id'] ? $ciniki['request']['args']['item_id'] : '0'),
            ));
    }
    
/*    //
    // repeatObjectUpdate/object/object_id
    //
    elseif( isset($args['uri_split'][2]) && $args['uri_split'][0] == 'repeatObjectUpdate' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'web', 'apiRepeatObjectUpdate');
        return ciniki_poma_web_apiRepeatObjectUpdate($ciniki, $settings, $tnid, array(
            'object'=>$args['uri_split'][1],
            'object_id'=>$args['uri_split'][2],
            ));
    }
    
    //
    // orderItemUpdate/item_id
    //
    elseif( isset($args['uri_split'][1]) && $args['uri_split'][0] == 'orderItemUpdate' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'web', 'apiOrderItemUpdate');
        return ciniki_poma_web_apiOrderItemUpdate($ciniki, $settings, $tnid, array(
            'item_id'=>$args['uri_split'][1],
            ));
    }

    //
    // orderSubstitutionAdd/item_id/object/object_id
    //
    elseif( isset($args['uri_split'][3]) && $args['uri_split'][0] == 'orderSubstitutionAdd' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'web', 'apiOrderSubstitutionAdd');
        return ciniki_poma_web_apiOrderSubstitutionAdd($ciniki, $settings, $tnid, array(
            'item_id'=>$args['uri_split'][1],
            'object'=>$args['uri_split'][2],
            'object_id'=>$args['uri_split'][3],
            ));
    }

    //
    // orderSubstitutionUpdate/item_id/subitem_id
    //
    elseif( isset($args['uri_split'][2]) && $args['uri_split'][0] == 'orderSubstitutionUpdate' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'web', 'apiOrderSubstitutionUpdate');
        return ciniki_poma_web_apiOrderSubstitutionUpdate($ciniki, $settings, $tnid, array(
            'item_id'=>$args['uri_split'][1],
            'subitem_id'=>$args['uri_split'][2],
            ));
    }

    //
    // queueObjectUpdate/object/object_id
    //
    elseif( isset($args['uri_split'][2]) && $args['uri_split'][0] == 'queueObjectUpdate' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'poma', 'web', 'apiQueueObjectUpdate');
        return ciniki_poma_web_apiQueueObjectUpdate($ciniki, $settings, $tnid, array(
            'object'=>$args['uri_split'][1],
            'object_id'=>$args['uri_split'][2],
            ));
    } */
    
    return array('stat'=>'ok');
}
?>
