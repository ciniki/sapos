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
function ciniki_sapos_reporting_blockOntarioQuarterlyHST(&$ciniki, $tnid, $args) {
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

    if( isset($args['quarters']) && $args['quarters'] != '' && $args['quarters'] > 0 && $args['quarters'] < 250 ) {
        $quarters = $args['quarters'];
    } else {
        $quarters = 1;
    }

    //
    // Set start date and time to midnight on the 1st of the month 
    //
    $end_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt->setDate($end_dt->format('Y'), $end_dt->format('n'), 1);
    $end_dt->setTime(0, 0, 0);
    if( isset($args['current']) && $args['current'] == 'yes' ) {
        if( (($end_dt->format('n'))%3) == 0 ) {
            error_log('1');
            $end_dt->add(new DateInterval('P1M'));
        } elseif( (($end_dt->format('n'))%3) == 1 ) {
            error_log('2');
            $end_dt->add(new DateInterval('P3M'));
        } elseif( (($end_dt->format('n'))%3) == 2 ) {
            error_log('3');
            $end_dt->add(new DateInterval('P2M'));
        }
        $quarters++;
    } else {
        if( (($end_dt->format('n'))%3) == 2 ) {
            $end_dt->sub(new DateInterval('P1M'));
        } elseif( (($end_dt->format('n'))%3) == 0 ) {
            $end_dt->sub(new DateInterval('P2M'));
        }
    }
    $start_dt = clone $end_dt;
    $end_dt->sub(new DateInterval('PT1S'));
    $start_dt->sub(new DateInterval('P' . ($quarters*3) . 'M'));

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Load the taxrates
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_tax_rates "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
        array('container'=>'taxrates', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $taxrates = isset($rc['taxrates']) ? $rc['taxrates'] : array();

    //
    // NOTE: If needed in the future to limit to just HST, then 
    // look for HST in the name and create array of taxtype_ids
    // then use below when quering invoices
    //

    //
    // Get the expense categories (assume all tagged for taxes are HST)
    // NOTE: in future might need to query for ones with HST in name.
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_sapos_expense_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    $category_ids = array_keys($categories);
    error_log(print_r($category_ids,true));

    //
    // Process each quarter
    //
    for($i = 1; $i <= $quarters; $i++) {
        $text_details = '';
        $table_details = array();
        $end_dt = clone($start_dt);
        $end_dt->add(new DateInterval('P3M'));
        $end_dt->sub(new DateInterval('PT1S'));

        $invoiced_total = 0;
        $hst_invoiced = 0;
        $hst_expenses = 0;

        //
        // For each month of the quarter, get invoice totals
        //
        $m_start_dt = clone($start_dt);
        for($j = 1; $j <= 3; $j++ ) {   
            $m_end_dt = clone($m_start_dt);
            $m_end_dt->add(new DateInterval('P1M'));
            $m_end_dt->sub(new DateInterval('PT1S'));
            // Convert into UTC dates
            $utc_start_dt = clone($m_start_dt);
            $utc_start_dt->setTimezone(new DateTimezone('UTC'));
            $utc_end_dt = clone($m_end_dt);
            $utc_end_dt->setTimezone(new DateTimezone('UTC'));

        error_log($m_start_dt->format('Y-m-d H:i:s'));
        error_log($m_end_dt->format('Y-m-d H:i:s'));
            //
            // Get the invoice totals for the month
            //
            $strsql = "SELECT SUM(total_amount) AS num "
                . "FROM ciniki_sapos_invoices "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND invoice_type IN (10, 30, 40) " //Invoices, POS, Orders
                . "AND invoice_date >= '" . ciniki_core_dbQuote($ciniki, $utc_start_dt->format('Y-m-d H:i:s')) . "' "
                . "AND invoice_date <= '" . ciniki_core_dbQuote($ciniki, $utc_end_dt->format('Y-m-d H:i:s')) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.51', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $month_total = isset($rc['num']) ? $rc['num'] : 0;
//            $table_details[] = array(
//                'label' => $m_start_dt->format('F'),
//                'value' => '$' . number_format($month_total, 2),
//                );
//            $text_details .= $m_start_dt->format('F') . ': ' . '$' . number_format($month_total, 2) . "\n";
            $invoiced_total += $month_total;

            //
            // Get the tax totals for the month
            //
            $strsql = "SELECT SUM(taxes.amount) AS num "
                . "FROM ciniki_sapos_invoices AS invoices "
                . "INNER JOIN ciniki_sapos_invoice_taxes AS taxes ON ("
                    . "invoices.id = taxes.invoice_id "
                    . "AND taxes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . ") "
                . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND invoices.invoice_type IN (10, 30, 40) " //Invoices, POS, Orders
                . "AND invoices.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $utc_start_dt->format('Y-m-d')) . "' "
                . "AND invoices.invoice_date <= '" . ciniki_core_dbQuote($ciniki, $utc_end_dt->format('Y-m-d')) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
            $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.52', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
            }
            $month_total = isset($rc['num']) ? $rc['num'] : 0;
            $hst_invoiced += $month_total;

            //
            // Get the HST expenses
            //
            if( count($category_ids) > 0 ) {
                $strsql = "SELECT SUM(items.amount) AS num "
                    . "FROM ciniki_sapos_expenses AS expenses "
                    . "INNER JOIN ciniki_sapos_expense_items AS items ON ("
                        . "expenses.id = items.expense_id "
                        . "AND items.category_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $category_ids) . ") "
                        . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . ") "
                    . "AND expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND expenses.invoice_date >= '" . ciniki_core_dbQuote($ciniki, $m_start_dt->format('Y-m-d H:i:s')) . "' "
                    . "AND expenses.invoice_date <= '" . ciniki_core_dbQuote($ciniki, $m_end_dt->format('Y-m-d H:i:s')) . "' "
                    . "";
                $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num');
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.66', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
                }
                $month_total = isset($rc['num']) ? $rc['num'] : 0;
                $hst_expenses += $month_total;
            }

            $m_start_dt->add(new DateInterval('P1M'));
        }
        $table_details[] = array(
            'label' => 'Total Invoiced (line 101)',
            'value' => '$' . number_format($invoiced_total, 2),
            );
        $text_details .= 'Total Invoiced (line 101): $' . number_format($invoiced_total, 2) . "\n";
        $table_details[] = array(
            'label' => 'HST Invoiced (line 105)',
            'value' => '$' . number_format($hst_invoiced, 2),
            );
        $text_details .= 'HST Invoiced (line 105): $' . number_format($hst_invoiced, 2) . "\n";
        $table_details[] = array(
            'label' => 'HST Expenses (line 108)',
            'value' => '$' . number_format($hst_expenses, 2),
            );
        $text_details .= 'HST Expenses (line 108): $' . number_format($hst_expenses, 2) . "\n";

        //
        // Calculate owing
        //
        $table_details[] = array(
            'label' => 'Net HST Payment (calculated)',
            'value' => '$' . number_format(($hst_invoiced - $hst_expenses), 2),
            );
        $text_details .= 'Net HST Payment (calculated): $' . number_format(($hst_invoiced - $hst_expenses), 2) . "\n";

        $chunks[] = array(
            'title' => $start_dt->format('F j, Y') . ' - ' . $end_dt->format('F j, Y'),
            'type' => 'table',
            'header' => 'no',
            'columns' => array(
                array('label'=>'', 'pdfwidth'=>'65%', 'field'=>'label'),
                array('label'=>'', 'pdfwidth'=>'35%', 'field'=>'value'),
                ),
            'data' => $table_details,
            'textlist' => $text_details,
            );
        $start_dt->add(new DateInterval('P3M'));
    }


    //
    // Get the expenses for the timespan
    //

    //
    // Get the list of invoice items by category
    //
/*        if( isset($category['items']) ) {
            foreach($category['items'] as $iid => $item) {
                $categories[$cid]['items'][$iid]['quantity'] = (float)$item['quantity'];
                $categories[$cid]['items'][$iid]['invoice_number'] = '#' . $item['invoice_number'];
                $categories[$cid]['items'][$iid]['code_desc'] = ($item['code'] != '' ? $item['code'] . ' - ' : '') . $item['description'];
                $categories[$cid]['total'] = bcadd($categories[$cid]['total'], $item['amount'], 6);
                $categories[$cid]['textlist'] .= '#' . $item['invoice_number'] . ' ' . $item['display_name'] . ' ' . $categories[$cid]['items'][$iid]['code_desc'] . ' ' . '$' . number_format($item['amount'], 2) . "\n";
            }
            $categories[$cid]['textlist'] .= 'Total: ' . '$' . number_format($categories[$cid]['total'], 2) . "\n";
        }*/

    //
    // List the items foreach category
    //
    /*
    if( count($categories) > 0 ) {
        foreach($categories as $category) {
            if( count($category['items']) <= 0 ) {
                continue;
            }
            $chunks[] = array(
                'title' => $category['name'],
                'type' => 'table',
                'columns' => array(
                    array('label'=>'#', 'pdfwidth'=>'10%', 'field'=>'invoice_number'),
                    array('label'=>'Name', 'pdfwidth'=>'26%', 'field'=>'invoice_date', 'line2'=>'display_name'),
                    array('label'=>'Item', 'pdfwidth'=>'35%', 'field'=>'code_desc'),
                    array('label'=>'Qty', 'pdfwidth'=>'5%', 'field'=>'quantity'),
                    array('label'=>'Amount', 'pdfwidth'=>'12%', 'type'=>'dollar', 'field'=>'amount'),
                    array('label'=>'Payment', 'pdfwidth'=>'12%', 'field'=>'source'),
                    ),
                'footer' => array(
                    array('value'=>'Total', 'colspan'=>3, 'pdfwidth'=>'76%'),
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
    */
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
