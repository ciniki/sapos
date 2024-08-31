<?php
//
// Description
// ===========
// This method will search the existing items, and will search other
// modules for items that can be linked.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceItemSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'invoice_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Invoice'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'15', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceItemSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Load invoice details
    //
    if( isset($args['invoice_id']) ) {
        $strsql = "SELECT invoice_type "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.55', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
        }
        $invoice = isset($rc['invoice']) ? $rc['invoice'] : array();
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.39', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();
    
    //
    // Load tenant INTL settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Setup the array for the items
    //
    $items = array();

    //
    // Check for modules which have searchable items
    //
    foreach($modules as $module => $m) {
        if( $m['module_status'] != 1 && $m['module_status'] != 2 ) {
            continue;
        }
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemSearch');
        if( $rc['stat'] != 'ok' ) {
            continue;
        }
        $search_function = $pkg . '_' . $mod . '_sapos_itemSearch';
        if( !is_callable($search_function) ) {
            continue;
        }
        $rc = $search_function($ciniki, $args['tnid'], array(
            'start_needle'=>$args['start_needle'], 
            'invoice_id'=>$args['invoice_id'],
            'limit'=>$args['limit']));
        if( $rc['stat'] != 'ok' ) {
            continue;
        }
        if( isset($rc['items']) ) {
            $items = array_merge($items, $rc['items']);
        }
    }

    //
    // Check existing items in invoices, but only if owner/employee
    //
    if( count($items) == 0 
        && (!isset($ciniki['tenant']['user']['perms'])
            || ($ciniki['tenant']['user']['perms']&0x03) > 0
            )
        ) {
        $strsql = "SELECT DISTINCT "    
            . "CONCAT_WS('-', ciniki_sapos_invoice_items.description, ciniki_sapos_invoice_items.unit_amount) AS id, "
            . "ciniki_sapos_invoice_items.flags, "
            . "ciniki_sapos_invoice_items.object, "
            . "ciniki_sapos_invoice_items.object_id, "
            . "ciniki_sapos_invoice_items.description, "
            . "ciniki_sapos_invoice_items.quantity, "
            . "ciniki_sapos_invoice_items.unit_amount, "
            . "ciniki_sapos_invoice_items.unit_discount_amount, "
            . "ciniki_sapos_invoice_items.unit_discount_percentage, "
            . "ciniki_sapos_invoice_items.unit_donation_amount, "
            . "ciniki_sapos_invoice_items.taxtype_id, "
            . "ciniki_sapos_invoice_items.notes "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND object = '' "
            . "AND (description LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR description LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") "
            . "";
        if( isset($args['limit']) && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
        } else {
            $strsql .= "LIMIT 15 ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'items', 'fname'=>'id', 'name'=>'item',
                'fields'=>array('id', 'flags', 'object', 'object_id', 'description', 'quantity',
                    'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'unit_donation_amount', 'taxtype_id', 'notes')),
            ));
        if( $rc['stat'] != 'ok' ) {    
            return $rc;
        }
        if( isset($rc['items']) ) {
            $items = array_merge($rc['items'], $items);
        }
    }

    foreach($items as $i => $item) {
        $items[$i]['item']['unit_amount'] = numfmt_format_currency($intl_currency_fmt, 
            $item['item']['unit_amount'], $intl_currency);
        $items[$i]['item']['unit_discount_amount'] = numfmt_format_currency($intl_currency_fmt, 
            $item['item']['unit_discount_amount'], $intl_currency);
        if( isset($items[$i]['item']['unit_donation_amount']) && $items[$i]['item']['unit_donation_amount'] > 0 ) {
            $items[$i]['item']['unit_donation_amount'] = numfmt_format_currency($intl_currency_fmt, 
                $item['item']['unit_donation_amount'], $intl_currency);
        }
        $items[$i]['item']['quantity'] = (float)$item['item']['quantity'];
        if( !isset($item['item']['flags']) ) {
            $items[$i]['item']['flags'] = 0;
        }
        if( isset($item['item']['synopsis']) && $item['item']['synopsis'] != '' && $item['item']['notes'] == '' 
            && isset($settings['quote-notes-product-synopsis']) && $settings['quote-notes-product-synopsis'] == 'yes'
            && isset($invoice['invoice_type']) && $invoice['invoice_type'] == 90 
            ) {
            $items[$i]['item']['notes'] = $item['item']['synopsis']; 
        }
    }

    return array('stat'=>'ok', 'items'=>$items);
}
?>
