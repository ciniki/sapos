<?php
//
// Description
// ===========
// This method will return a list of invoices.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Type'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'sort'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search String'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'15', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

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
    // Build the query to get the list of invoices
    //
    $strsql = "SELECT ciniki_sapos_invoices.id, "
        . "ciniki_sapos_invoices.invoice_number, "
        . "ciniki_sapos_invoices.po_number, "
        . "invoice_date, "
        . "ciniki_sapos_invoices.status, "
        . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
        . "ciniki_customers.type AS customer_type, "
        . "ciniki_customers.display_name AS customer_display_name, "
        . "total_amount "
        . "FROM ciniki_sapos_invoices "
        . "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['invoice_type']) && $args['invoice_type'] != '' ) {
        $strsql .= "AND ciniki_sapos_invoices.invoice_type = '" . ciniki_core_dbQuote($ciniki, $args['invoice_type']) . "' ";
    }
    if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
        $strsql .= "AND ciniki_sapos_invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    }
    if( is_numeric($args['start_needle']) ) { 
        $strsql .= "AND (ciniki_sapos_invoices.invoice_number LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//            . "OR ciniki_sapos_invoices.invoice_number LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_sapos_invoices.po_number LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_sapos_invoices.po_number LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_sapos_invoices.po_number LIKE '%-" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") ";
    } elseif( preg_match("/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec) /i", $args['start_needle'], $matches) ) {
        $search_str = str_replace(' ', '%', $args['start_needle']);
        $strsql .= "AND DATE_FORMAT(invoice_date, '%M %d %Y') LIKE '%" . ciniki_core_dbQuote($ciniki, $search_str) . "%' ";

    } else {
        if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
            $strsql .= "AND (DATE_FORMAT(invoice_date, '%M %Y') LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR DATE_FORMAT(invoice_date, '%M %Y') LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_sapos_invoices.po_number LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_sapos_invoices.po_number LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_sapos_invoices.po_number LIKE '%-" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") ";
        } else {
            $strsql .= "AND (ciniki_customers.display_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_customers.display_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_customers.company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_customers.company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_customers.eid LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_sapos_invoices.po_number LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_sapos_invoices.po_number LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR ciniki_sapos_invoices.po_number LIKE '%-" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR DATE_FORMAT(invoice_date, '%M %Y') LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . "OR DATE_FORMAT(invoice_date, '%M %Y') LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
                . ") ";
        }
    }
    if( isset($args['sort']) ) {
        if( $args['sort'] == 'latest' ) {
            $strsql .= "ORDER BY ciniki_sapos_invoices.last_updated DESC ";
        } elseif( $args['sort'] == 'reverse' ) {
            $strsql .= "ORDER BY ciniki_sapos_invoices.invoice_date DESC ";
        }
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
            'fields'=>array('id', 'invoice_number', 'po_number', 'invoice_date', 'status', 'status_text', 
                'customer_type', 'customer_display_name', 'total_amount'),
            'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoices']) ) {
        return array('stat'=>'ok', 'invoices'=>array());
    }
    foreach($rc['invoices'] as $iid => $invoice) {
        $rc['invoices'][$iid]['invoice']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
            $invoice['invoice']['total_amount'], $intl_currency);
    }

    return array('stat'=>'ok', 'invoices'=>$rc['invoices']);
}
?>
