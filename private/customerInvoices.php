<?php
//
// Description
// -----------
// This function will return the list of invoices for a customer.
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
function ciniki_sapos_customerInvoices($ciniki, $tnid, $customer_id, $limit=0) {
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
    // Get the invoices for a customer
    //
    $strsql = "SELECT ciniki_sapos_invoices.id, "
        . "ciniki_customers.display_name AS customer_name, "
        . "ciniki_sapos_invoices.invoice_number, "
        . "ciniki_sapos_invoices.invoice_date, "
        . "ciniki_sapos_invoices.status, "
        . "ciniki_sapos_invoices.status AS status_text, "
        . "ciniki_sapos_invoices.total_amount "
        . "FROM ciniki_sapos_invoices "
        . "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_sapos_invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
        . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY ciniki_sapos_invoices.invoice_date DESC "
        . "";
    if( $limit > 0 ) {
        $strsql .= "LIMIT $limit ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
            'fields'=>array('id', 'customer_name', 'invoice_number', 'invoice_date',
                'status', 'status_text', 'total_amount'),
            'maps'=>array('status_text'=>$maps['invoice']['status']),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$php_date_format))), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoices']) ) {
        return array('stat'=>'ok', 'invoices'=>array());
    }
    $invoices = $rc['invoices'];

    foreach($invoices as $iid => $invoice) {
        $invoices[$iid]['invoice']['total_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $invoices[$iid]['invoice']['total_amount'], $intl_currency);
    }

    return array('stat'=>'ok', 'invoices'=>$invoices);
}
?>
