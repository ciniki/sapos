<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_sapos_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default 
    //
    $rsp = array('stat'=>'ok', 'settings'=>array(), 'menu_items'=>array(), 'settings_menu_items'=>array());  

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $rsp['settings'] = $rc['settings'];
    }

    //
    // Check if orders should be shown
    //
/*  Moved to customer package
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x60) 
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5910,
            'label'=>'Orders', 
            'edit'=>array('app'=>'ciniki.sapos.orders'),
            'add'=>array('app'=>'ciniki.sapos.invoice', 'args'=>array('action'=>'"\'addorder\'"', 'invoice_id'=>'0', 'invoice_type'=>'40')),
            'search'=>array(
                'method'=>'ciniki.sapos.invoiceSearch',
                'args'=>array('sort'=>'reverse'),
                'container'=>'invoices',
                'cols'=>5,
                'headerValues'=>array('Invoice #', 'Date', 'Customer', 'Amount', 'Status'),
                'cellValues'=>array(
                    '0'=>'d.invoice.invoice_number;',
                    '1'=>'d.invoice.invoice_date;',
                    '2'=>'d.invoice.customer_display_name;',
                    '3'=>'d.invoice.total_amount_display;',
                    '4'=>'d.invoice.status_text;',
                    ),
                'noData'=>'No orders found',
                'edit'=>array('method'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.invoice.id;')),
                ),
            );
        $rsp['menu_items'][] = $menu_item;
    } 
*/
    //
    // Show the Accounting item
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sapos'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5900,
            'label'=>'Accounting', 
            'edit'=>array('app'=>'ciniki.sapos.main'),
            'add'=>array('app'=>'ciniki.sapos.invoice', 'args'=>array()),
            'search'=>array(
                'method'=>'ciniki.sapos.invoiceSearch',
                'args'=>array('sort'=>'reverse]'),
                'container'=>'invoices',
                'cols'=>5,
                'headerValues'=>array('Invoice #', 'Date', 'Customer', 'Amount', 'Status'),
                'cellValues'=>array(
                    '0'=>'d.invoice.invoice_number;',
                    '1'=>'d.invoice.invoice_date;',
                    '2'=>'d.invoice.customer_display_name;',
                    '3'=>'d.invoice.total_amount_display;',
                    '4'=>'d.invoice.status_text;',
                    ),
                'noData'=>'No invoices found',
                'edit'=>array('method'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.invoice.id;')),
                ),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Show the POS Terminal
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x10)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['retail'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>4000,
            'label'=>'Checkout', 
            'edit'=>array('app'=>'ciniki.sapos.pos'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Accounting Settings
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sapos'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>5900, 'label'=>'Accounting', 'edit'=>array('app'=>'ciniki.sapos.settings'));
    }

    return $rsp;
}
?>
