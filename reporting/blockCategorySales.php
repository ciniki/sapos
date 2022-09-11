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
function ciniki_sapos_reporting_blockCategorySales(&$ciniki, $tnid, $args) {
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
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $date_text = '';
    if( isset($args['months']) && $args['months'] != '' && $args['months'] > 0 && $args['months'] < 366 ) {
        $months = $args['months'];
        $date_text .= ($months > 1 ? $months . ' months' : 'month');
    } else {
        $months = 0;
    }
    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 366 ) {
        $days = $args['days'];
    } else {
        $days = ($months > 0 ? 0 : 7);
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt = clone $start_dt;
    if( $days != 0 ) {
        $start_dt->sub(new DateInterval('P' . $days . 'D'));
    }
    if( $months != 0 ) {
        $start_dt->sub(new DateInterval('P' . $months . 'M'));
    }

    //
    // Store the report block chunks
    //
    $chunks = array();

    $category_sql = '';
    if( isset($args['category']) && $args['category'] != '0' ) {
        $category_sql = "AND m.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
    }

    //
    // Get the list of invoice items by category
    //
    $strsql = "SELECT m.id, "
        . "i.invoice_number, "
        . "DATE_FORMAT(IFNULL(t.transaction_date, i.invoice_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS invoice_date, "
        . "i.payment_status, "
        . "i.payment_status AS payment_status_text, "
        . "i.po_number, "
        . "c.type AS customer_type, "
        . "c.display_name, "
        . "m.quantity, "
        . "i.total_amount, "
        . "m.code, "
        . "m.description, "
        . "IF(IFNULL(m.category, '') = '', 'Uncategorized', category) AS category, "
        . "m.total_amount AS amount, "
        . "t.source "
        . "FROM ciniki_sapos_invoices AS i "
        . "LEFT JOIN ciniki_customers AS c ON ("
            . "i.customer_id = c.id "
            . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_invoice_items AS m ON ("
            . "i.id = m.invoice_id "
            . $category_sql
            . "AND m.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_transactions AS t ON ("
            . "i.id = t.invoice_id "
            . "AND t.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE i.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND i.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
        . "AND i.invoice_date <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
        . "AND (i.invoice_type = 10 OR i.invoice_type = 30) "
        . "AND (i.status = 45 OR i.status = 50) "
        . "ORDER BY category, invoice_date, c.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'display_name', 'invoice_number', 'invoice_date', 'payment_status', 'payment_status_text', 
                'category', 'code', 'description', 'amount', 'source', 'quantity',
                ),
            'maps'=>array('payment_status_text'=>$maps['invoice']['payment_status'],
                'source'=>$maps['transaction']['source'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.384', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    //
    // Calculate the sums for each category
    //
    foreach($categories as $cid => $category) {
        $categories[$cid]['total'] = 0;
        $categories[$cid]['textlist'] = 0;
        if( isset($category['items']) ) {
            foreach($category['items'] as $iid => $item) {
                $categories[$cid]['items'][$iid]['quantity'] = (float)$item['quantity'];
                $categories[$cid]['items'][$iid]['invoice_number'] = '#' . $item['invoice_number'];
                $categories[$cid]['items'][$iid]['code_desc'] = ($item['code'] != '' ? $item['code'] . ' - ' : '') . $item['description'];
                $categories[$cid]['total'] = bcadd($categories[$cid]['total'], $item['amount'], 6);
                $categories[$cid]['textlist'] .= '#' . $item['invoice_number'] . ' ' . $item['display_name'] . ' ' . $categories[$cid]['items'][$iid]['code_desc'] . ' ' . '$' . number_format($item['amount'], 2) . "\n";
            }
            $categories[$cid]['textlist'] .= 'Total: ' . '$' . number_format($categories[$cid]['total'], 2) . "\n";
        }
    }

    //
    // List the items foreach category
    //
    if( count($categories) > 0 ) {
        foreach($categories as $category) {
            if( !isset($category['items']) || count($category['items']) <= 0 ) {
                continue;
            }
            $chunks[] = array(
                'title' => $category['name'],
                'type' => 'table',
                'columns' => array(
                    array('label'=>'#', 'pdfwidth'=>'10%', 'field'=>'invoice_number'),
                    array('label'=>'Date', 'pdfwidth'=>'26%', 'field'=>'invoice_date', 'line2'=>'display_name'),
                    array('label'=>'Item', 'pdfwidth'=>'35%', 'field'=>'code_desc'),
                    array('label'=>'Qty', 'pdfwidth'=>'5%', 'field'=>'quantity'),
                    array('label'=>'Amount', 'pdfwidth'=>'12%', 'type'=>'dollar', 'field'=>'amount'),
                    array('label'=>'Payment', 'pdfwidth'=>'12%', 'field'=>'source'),
                    ),
                'footer' => array(
                    array('value'=>'Total', 'colspan'=>4, 'pdfwidth'=>'76%'),
                    array('value'=>$category['total'], 'pdfwidth'=>'12%', 'type'=>'dollar'),
                    array('value'=>'', 'pdfwidth'=>'12%'),
                    ),
                'data' => $category['items'],
                'textlist' => $category['textlist'],
                );
        }
    }
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No sales in the last ' . ($days == 1 ? 'day' : $days . ' days') . '.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
