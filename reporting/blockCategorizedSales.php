<?php
//
// Description
// -----------
// Return the report of sales by category for the last X days.
//
// Arguments
// ---------
// ciniki:
// tnid:                The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days forward to look for upcoming birthdays. Must be between 1-31.
// 
// Returns
// -------
//
function ciniki_sapos_reporting_blockCategorizedSales(&$ciniki, $tnid, $args) {

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the tenant settings
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

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 366 ) {
        $days = $args['days'];
    } else {
        $days = 7;
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt = clone $start_dt;
    $end_dt->sub(new DateInterval('P' . $days . 'D'));

    //
    // Store the report block chunks
    //
    $chunks = array();

    $stats = array();
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStats');
    $rc = ciniki_sapos__invoiceStats($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['stats']) ) {
        $stats = $rc['stats'];
    }
    $categories = array();
    if( isset($stats['categories']) ) {
        foreach($stats['categories'] as $cid => $c) {
            $categories[$c['name']] = array('name'=>$c['name'], 'total_amount'=>0);
        }
    }
    $totals['total_amount'] = 0;
    $totals['num_invoices'] = 0;

    $textlist = '';

    //
    // Build the query to get the list of invoices
    //
    $strsql = "SELECT invoices.id, "
        . "invoices.invoice_number, "
        . "invoices.invoice_date, "
//        . "IFNULL(transactions.transaction_date, invoices.invoice_date) AS invoice_date, "
        . "invoices.payment_status, "
        . "invoices.payment_status AS payment_status_text, "
        . "invoices.po_number, "
        . "customers.type AS customer_type, "
        . "customers.display_name AS customer_display_name, "
        . "invoices.total_amount, "
        . "items.id AS item_id, "
        . "IF(IFNULL(items.category, '') = '', 'Uncategorized', category) AS category, "
        . "items.total_amount AS amount, "
        . "IFNULL(transactions.id, 0) AS transaction_id, "
        . "IFNULL(transactions.source, '') AS source, "
        . "IFNULL(transactions.customer_amount, 0) AS customer_amount "
        . "FROM ciniki_sapos_invoices AS invoices "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "invoices.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_invoice_items AS items ON ("
            . "invoices.id = items.invoice_id "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_transactions AS transactions ON ("
            . "invoices.id = transactions.invoice_id "
            . "AND transactions.status >= 40 "
            . "AND transactions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
        . "AND (invoices.status = 45 OR invoices.status = 50) "
        . "";
    if( isset($args['year']) && $args['year'] != '' ) {
        //
        // Set the start and end date for the tenant timezone, then convert to UTC
        //
        $tz = new DateTimeZone($intl_timezone);
        if( isset($args['month']) && $args['month'] != '' && $args['month'] > 0 ) {
            $start_date = new DateTime($args['year'] . '-' . $args['month'] . '-01 00.00.00', $tz);
            $end_date = clone $start_date;
            // Find the end of the month
            $end_date->add(new DateInterval('P1M'));
        } else {
            $start_date = new DateTime($args['year'] . '-01-01 00.00.00', $tz);
            $end_date = clone $start_date;
            // Find the end of the year
            $end_date->add(new DateInterval('P1Y'));
        }
        $start_date->setTimezone(new DateTimeZone('UTC'));
        $end_date->setTimeZone(new DateTimeZone('UTC'));
        //
        // Add to SQL string
        //
        $strsql .= "AND invoices.invoice_date >= '" . $start_date->format('Y-m-d H:i:s') . "' ";
        $strsql .= "AND invoices.invoice_date < '" . $end_date->format('Y-m-d H:i:s') . "' ";
    }
    if( isset($args['status']) && $args['status'] > 0 ) {
        $strsql .= "AND invoices.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['payment_status']) && $args['payment_status'] > 0 ) {
        $strsql .= "AND invoices.payment_status = '" . ciniki_core_dbQuote($ciniki, $args['payment_status']) . "' ";
    }
    $strsql .= "AND (invoices.invoice_type = 10 || invoices.invoice_type = 30) ";
//    $strsql .= "GROUP BY invoices.id, items.id, transaction_id ";
    $strsql .= "ORDER BY invoices.invoice_date ASC, invoices.invoice_number COLLATE latin1_general_cs ASC, items.id, transaction_id ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array('id', 'invoice_number', 'invoice_date', 'payment_status', 'payment_status_text', 
                'customer_type', 'customer_display_name', 'total_amount',
                ),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
            'maps'=>array(
                'payment_status_text'=>$maps['invoice']['payment_status'],
                ),
            ),
        array('container'=>'items', 'fname'=>'item_id', 'fields'=>array('id'=>'item_id', 'category', 'amount')),
        array('container'=>'transactions', 'fname'=>'transaction_id', 
            'fields'=>array('id'=>'transaction_id', 'source', 'customer_amount'),
            'maps'=>array(
                'source'=>$maps['transaction']['source'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoices = isset($rc['invoices']) ? $rc['invoices'] : array();

    foreach($invoices as $iid => $invoice) {
        $invoices[$iid]['invoice_number_date'] = $invoice['invoice_number'] . ' - ' . $invoice['invoice_date'];
        $invoices[$iid]['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, $invoice['total_amount'], $intl_currency);
        if( !isset($invoice['items']) ) {
            continue;
        }
        $invoice_sources = array();
        if( isset($invoice['items'][0]['transactions']) ) {
            foreach($invoice['items'][0]['transactions'] as $transaction) {
                if( !isset($invoice_sources[$transaction['source']]) ) {
                    $invoice_sources[$transaction['source']] = $transaction['customer_amount'];
                } else {
                    $invoice_sources[$transaction['source']] += $transaction['customer_amount'];
                }
            }
        }
        $invoices[$iid]['source'] = implode(',', array_keys($invoice_sources));
        foreach($invoice['items'] as $item) {
            if( !isset($invoices[$iid][$item['category']]) ) {
                $invoices[$iid][$item['category']] = $item['amount'];
            } else {
                $invoices[$iid][$item['category']] += $item['amount'];
            }
            $categories[$item['category']]['total_amount'] += $item['amount'];
        }
        foreach($categories as $cid => $c) {
            if( isset($invoices[$iid][$cid]) ) {
                $invoices[$iid][$cid] = '$' . number_format($invoices[$iid][$cid], 2);
            }
        }
        $totals['total_amount'] = bcadd($totals['total_amount'], $invoice['total_amount'], 6);
    }
    $totals['total_amount'] = numfmt_format_currency($intl_currency_fmt, $totals['total_amount'], $intl_currency);
    $totals['num_invoices'] = count($invoices);

    if( isset($stats['categories']) ) {
        foreach($categories as $cid => $c) {
            $categories[$cid]['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, $c['total_amount'], $intl_currency);
        }
    }

    $categories = array_values($categories);

    //
    // List the items foreach category
    //
    if( count($invoices) > 0 ) {
        $chunk = array(
            'type' => 'table',
            'columns' => array(
                array('label'=>'# - Paid Date', 'pdfwidth'=>'15%', 'field'=>'invoice_number_date', 'line2'=>'customer_display_name', 'type'=>'multiline'),
                ),
            'footer' => array(
                array('value'=>'Total', 'pdfwidth'=>'15%'),
//                array('value'=>$category['total'], 'pdfwidth'=>'12%', 'type'=>'dollar'),
//                array('value'=>'', 'pdfwidth'=>'12%'),
                ),
            'data' => $invoices,
            'textlist' => $textlist,
            );
        $col_width = (75/(count($categories) + 1));
        foreach($categories as $cid => $category) { 
            $chunk['columns'][] = array('label'=>$category['name'], 'pdfwidth'=>"{$col_width}%", 'field'=>$category['name']);
            $chunk['footer'][] = array('value'=>$category['total_amount_display'], 'pdfwidth'=>"{$col_width}%");
        }
        $chunk['footer'][] = array('value'=>$totals['total_amount'], 'pdfwidth'=>"{$col_width}%");
        $chunk['footer'][] = array('value'=>'', 'pdfwidth'=>'10%');
        $chunk['columns'][] = array('label'=>'Amount', 'pdfwidth'=>"{$col_width}%", 'field'=>'total_amount_display');
        $chunk['columns'][] = array('label'=>'Payment', 'pdfwidth'=>'10%', 'field'=>'source');
        $chunks[] = $chunk;
    }
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No sales in the last ' . ($days == 1 ? 'day' : $days . ' days') . '.');
    }

    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
