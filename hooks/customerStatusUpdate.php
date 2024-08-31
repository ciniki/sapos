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
function ciniki_sapos_hooks_customerStatusUpdate($ciniki, $tnid, $args) {
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Update open orders
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 
        && isset($args['status']) && ($args['status'] == '40'  || $args['status'] == 41)
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
        $strsql = "SELECT id, invoice_type, status "
            . "FROM ciniki_sapos_invoices "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status < 50 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $invoices = $rc['rows'];
            foreach($invoices as $invoice) {
                $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $tnid, $invoice['id']);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
