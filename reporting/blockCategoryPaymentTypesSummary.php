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
function ciniki_sapos_reporting_blockCategoryPaymentTypesSummary(&$ciniki, $tnid, $args) {
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

    if( isset($args['start_date']) && $args['start_date'] != '' 
        && isset($args['end_date']) && $args['end_date'] != '' 
        ) {
        $start_dt = new DateTime($args['start_date'] . ' 00:00:00' , new DateTimezone($intl_timezone));
        $end_dt = new DateTime($args['end_date'] . ' 23:59:59', new DateTimezone($intl_timezone));
    }
    elseif( isset($args['start_date']) && $args['start_date'] != '' 
        && (!isset($args['end_date']) || $args['end_date'] == '') 
        ) {
        $start_dt = new DateTime($args['start_date'] . ' 00:00:00' , new DateTimezone($intl_timezone));
        $end_dt = clone($start_dt);
        $end_dt->add(new DateInterval('P1M'));
        $end_dt->sub(new DateInterval('PT1S'));
    }
    else {
        $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
        $start_dt->setDate($start_dt->format('Y'),$start_dt->format('m'),1);
        $start_dt->setTime(0,0,0);
        $end_dt = clone($start_dt);
        $end_dt->add(new DateInterval('P1M'));
        $end_dt->sub(new DateInterval('PT1S'));
    }

    $title = $start_dt->format('M j, Y') . ' - ' . $end_dt->format('M j, Y');
    $start_dt->setTimezone(new DateTimezone('UTC'));
    $end_dt->setTimezone(new DateTimezone('UTC'));

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT IF(category = '', 'Uncategorized', category) AS category "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY category "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array( 
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    //
    // Get the list of transaction sources
    //
    $strsql = "SELECT DISTINCT transactions.source, transactions.source AS name "
        . "FROM ciniki_sapos_invoices AS invoices "
        . "INNER JOIN ciniki_sapos_transactions AS transactions ON ("
            . "invoices.id = transactions.invoice_id "
            . "AND transactions.status >= 40 "
            . "AND transactions.transaction_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
            . "AND transactions.transaction_date <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' "
            . "AND transactions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY source "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array( 
        array('container'=>'sources', 'fname'=>'source', 
            'fields'=>array('source', 'name'),
            'maps'=>array('name'=>$maps['transaction']['source']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sources = isset($rc['sources']) ? $rc['sources'] : array();
    foreach($sources as $sid => $source) {
        $sources[$sid]['total'] = 0;
    }

    foreach($categories as $cid => $cat) {  
        foreach($sources as $source) {
            $categories[$cid][$source['source']] = 0;
        }
        $categories[$cid]['total'] = 0;
    }

    //
    // Get the invoice transactions
    //
    $strsql = "SELECT invoices.id, "
        . "transactions.id AS transaction_id, "
        . "transactions.source, "
        . "transactions.customer_amount, "
        . "transactions.transaction_fees "
        . "FROM ciniki_sapos_invoices AS invoices "
        . "INNER JOIN ciniki_sapos_transactions AS transactions ON ("
            . "invoices.id = transactions.invoice_id "
            . "AND transactions.status >= 40 "
            . "AND transactions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['use-date']) && $args['use-date'] == 'transaction' ) {
        $strsql .= "AND transactions.transaction_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
            . "AND transactions.transaction_date <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' ";
    } else {
        $strsql .= "AND invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
            . "AND invoices.invoice_date <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' ";
    }
    $strsql .= "ORDER BY invoices.id, transactions.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array('id'),
            ),
        array('container'=>'transactions', 'fname'=>'transaction_id', 
            'fields'=>array('id'=>'transaction_id', 'invoice_id'=>'id', 'source', 'customer_amount', 'transaction_fees'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.442', 'msg'=>'Unable to load transactions', 'err'=>$rc['err']));
    }
    $transactions = isset($rc['invoices']) ? $rc['invoices'] : array();
    $invoice_ids = array_keys($transactions);

    //
    // Get the invoice items and their categories
    //
    $strsql = "SELECT invoices.id, "
        . "items.id AS item_id, "
        . "IF(items.category = '', 'Uncategorized', items.category) AS category, "
        . "invoices.invoice_number, "
        . "items.total_amount "
        . "FROM ciniki_sapos_invoices AS invoices "
        . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
            . "invoices.id = items.invoice_id "
            . "AND items.total_amount > 0 "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (invoices.invoice_type = 10 "
            . "OR invoices.invoice_type = 30 "
            . "OR invoices.invoice_type = 40 "
            . ") ";
    if( isset($args['use-date']) && $args['use-date'] == 'transaction' && count($invoice_ids) > 0 ) {
        $strsql .= "AND invoices.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $invoice_ids) . ") ";
    } else {
        $strsql .= "AND invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
            . "AND invoices.invoice_date <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' ";
    }
    $strsql .= "ORDER BY invoices.id, items.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array('id', 'invoice_number'),
            ),
        array('container'=>'items', 'fname'=>'item_id', 
            'fields'=>array('id'=>'item_id', 'invoice_id'=>'id', 'category', 'total_amount'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.427', 'msg'=>'Unable to load items', 'err'=>$rc['err']));
    }
    $invoices = isset($rc['invoices']) ? $rc['invoices'] : array();

    //
    // Go through items and add totals to categories
    //
    foreach($invoices as $invoice_id => $invoice) {
        //
        // Get the total amounts for each category
        //
        $invoice_categories = array();
        foreach($invoice['items'] as $item) {
            if( !isset($invoice_categories[$item['category']]) ) {
                $invoice_categories[$item['category']] = $item['total_amount'];
            } else {
                $invoice_categories[$item['category']] += $item['total_amount'];
            }
        }

        //
        // Get the total amounts for each transaction source
        //
        $invoice_sources = array();
        if( isset($transactions[$invoice_id]['transactions']) ) {
            foreach($transactions[$invoice_id]['transactions'] as $transaction) {
                if( !isset($invoice_sources[$transaction['source']]) ) {
                    $invoice_sources[$transaction['source']] = $transaction['customer_amount'];
                } else {
                    $invoice_sources[$transaction['source']] += $transaction['customer_amount'];
                }
            }
        }

        foreach($invoice_categories as $cid => $category_total) {
            foreach($invoice_sources as $sid => $source_total) {
                if( $category_total < $source_total ) {
                    $amt = $category_total;
                }
                elseif( $category_total >= $source_total ) {
                    $amt = $source_total;
                }
                if( !isset($categories[$cid][$sid]) ) {
                    $categories[$cid][$sid] = $amt;
                } else {
                    $categories[$cid][$sid] += $amt;
                }
                $categories[$cid]['total'] += $amt;
                $sources[$sid]['total'] += $amt;
                // 
                // Reduce the amounts 
                //
                $invoice_categories[$cid] -= $amt;
                $invoice_sources[$sid] -= $amt;
            }
        }
    }
  
    //
    // Setup columns
    //
    $columns = array(
        array('label'=>'Category', 'pdfwidth'=>'10%', 'field'=>'name'),
        );
    $footer = array(
        array('value'=>'Totals', 'pdfwidth'=>'10%'),
        );
    $cell_width = round(90/(count($sources) + 1), 2) . '%';
    $total = 0;
    foreach($sources as $sid => $source) {
        $columns[] = array('label'=>$source['name'], 'pdfwidth'=>$cell_width, 'type'=>'dollar', 'field'=>$source['source']);
        $footer[] = array('value'=>$source['total'], 'pdfwidth'=>$cell_width, 'type'=>'dollar');
        $total += $source['total'];
    }
    $columns[] = array('label'=>'Total', 'pdfwidth'=>$cell_width, 'type'=>'dollar', 'field'=>'total');
    $footer[] = array('value'=>$total, 'pdfwidth'=>$cell_width, 'type'=>'dollar');

    $chunks[] = array(
        'title' => $title,
        'type' => 'table',
        'columns' => $columns,
        'footer' => $footer,        
        'data' => $categories,
        'textlist' => 'No text output',
        );

    return array('stat'=>'ok', 'dates'=>'yes', 'chunks'=>$chunks);
}
?>
