<?php
//
// Description
// ===========
// This method will return the detail for a tax for a tenant.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceItemGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Item'), 
        'taxtypes'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Include Taxtypes'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceItemGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

//    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'timezoneOffset');
//    $utc_offset = ciniki_tenants_timezoneOffset($ciniki);
//    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the item details
    //
    $strsql = "SELECT id, line_number, "
        . "status, "
        . "flags, "
        . "invoice_id, "
        . "object, "
        . "object_id, "
        . "code, "
        . "description, "
        . "quantity, "
        . "unit_amount, "
        . "unit_discount_amount, "
        . "unit_discount_percentage, "
        . "price_id, "
        . "subtotal_amount, "
        . "discount_amount, "
        . "total_amount, "
        . "taxtype_id, "
        . "notes "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.72', 'msg'=>'Unable to find invoice item.'));
    }
    $item = $rc['item'];

    $item['quantity'] = (float)$item['quantity'];
    $item['unit_discount_percentage'] = (float)$item['unit_discount_percentage'];
    $item['unit_amount'] = numfmt_format_currency($intl_currency_fmt, 
        $item['unit_amount'], $intl_currency);
    $item['unit_discount_amount'] = numfmt_format_currency($intl_currency_fmt, 
        $item['unit_discount_amount'], $intl_currency);
    $item['subtotal_amount'] = numfmt_format_currency($intl_currency_fmt, 
        $item['subtotal_amount'], $intl_currency);
    $item['total_amount'] = numfmt_format_currency($intl_currency_fmt, 
        $item['total_amount'], $intl_currency);

    //
    // Check to make sure the invoice belongs to the salesrep
    //
    if( isset($ciniki['tenant']['user']['perms']) && ($ciniki['tenant']['user']['perms']&0x07) == 0x04 ) {
        $strsql = "SELECT id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $item['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.73', 'msg'=>'Permission denied'));
        }
    }

    //
    // Get the tax types available for the tenant
    //
    if( isset($modules['ciniki.taxes']) && isset($args['taxtypes']) && $args['taxtypes'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'taxes', 'private', 'taxTypes');
        $rc = ciniki_taxes_taxTypes($ciniki, $args['tnid']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        return array('stat'=>'ok', 'item'=>$item, 'taxtypes'=>$rc['types']);
    }

    return array('stat'=>'ok', 'item'=>$item);
}
?>
