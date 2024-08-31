<?php
//
// Description
// -----------
// This function will update open orders when a customer status changes
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_hooks_inventoryUpdated($ciniki, $tnid, $args) {
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Check for any open orders that have this item
    //
    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != '' && $args['object_id'] > 0 
        && isset($args['new_inventory_level']) && $args['new_inventory_level'] != '' 
        ) {
        //
        // Get the orders that are unfulfilled which contain this item
        //
        $strsql = "SELECT ciniki_sapos_invoice_items.id, "
            . "ciniki_sapos_invoices.invoice_type, "
            . "ciniki_sapos_invoices.status, "
            . "ciniki_sapos_invoice_items.flags "
            . "FROM ciniki_sapos_invoice_items, ciniki_sapos_invoices "
            . "WHERE ciniki_sapos_invoice_items.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND ciniki_sapos_invoice_items.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_sapos_invoice_items.quantity > ciniki_sapos_invoice_items.shipped_quantity "
            . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sapos_invoice_items.invoice_id = ciniki_sapos_invoices.id "
            . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sapos_invoices.status < 50 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['rows']) ) {
            return array('stat'=>'ok');
        }
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            if( $args['new_inventory_level'] > 0 ) {
                // Check if shipped, inventory and backorderable item and currently backordered
                if( ($item['flags']&0x0146) == 0x0146 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item',
                        $item['id'], array('flags'=>(((int)$item['flags'])&~0x0100)), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            } elseif( $args['new_inventory_level'] <= 0 ) {
                // Check if shipped, inventory and backorderable item and not backordered
                if( ($item['flags']&0x0046) == 0x0046 ) {    
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice_item',
                        $item['id'], array('flags'=>(((int)$item['flags'])|0x0100)), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            } 
        }
    }

    return array('stat'=>'ok');
}
?>
