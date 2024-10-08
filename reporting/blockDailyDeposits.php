<?php
//
// Description
// -----------
// Return the report of sales by category for the last X days.
//
// Arguments
// ---------
// ciniki:
// tnid:                The ID of the tenant.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days forward to look for upcoming birthdays. Must be between 1-31.
// 
// Returns
// -------
//
function ciniki_sapos_reporting_blockDailyDeposits(&$ciniki, $tnid, $args) {
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
        $days = 1;
    }

    //
    // Store the report block chunks
    //
    $chunks = array();

    if( isset($args['start_date']) && $args['start_date'] != '' 
        && isset($args['end_date']) && $args['end_date'] != '' 
        ) {
        $start_dt = new DateTime($args['start_date'] . ' 00:00:00' , new DateTimezone($intl_timezone));
        $end_dt = new DateTime($args['end_date'] . ' 23:59:59', new DateTimezone($intl_timezone));
        if( $start_dt > $end_dt ) {
            $interval = $end_dt->diff($start_dt);
            $days = $interval->format("%a");
            $start_dt = clone $end_dt;
            $start_dt->sub(new DateInterval('P1D'));
        }
        elseif( $start_dt < $end_dt ) {
            $interval = $start_dt->diff($end_dt);
            $days = $interval->format("%a") + 1;
        } elseif( $start_dt == $end_dt ) {
            $days = 1;
        } else {
            $days = 1;
        }
        $start_dt->setTimezone(new DateTimezone('UTC'));
    }
    elseif( isset($args['start_date']) && $args['start_date'] != '' 
        && (!isset($args['end_date']) || $args['end_date'] == '') 
        ) {
        $start_dt = new DateTime($args['start_date'] . ' 00:00:00' , new DateTimezone($intl_timezone));
        $start_dt->setTimezone(new DateTimezone('UTC'));
    }
    else {
        $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
        $start_dt->setTime(0,0,0);
        if( $days > 1 ) {
            $start_dt->sub(new DateInterval('P' . $days . 'D'));
        }
        $start_dt->setTimezone(new DateTimezone('UTC'));
    }
    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('P1D'));
    for($i = 0; $i < $days; $i++) {
        //
        // Get the list of invoice items by category
        //
        $strsql = "SELECT m.id, "
            . "i.id AS invoice_id, "
            . "i.invoice_number, "
            . "i.invoice_date, "
//            . "DATE_FORMAT(i.invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS invoice_date, "
            . "i.payment_status, "
            . "i.payment_status AS payment_status_text, "
            . "i.po_number, "
            . "IFNULL(c.type, '') AS customer_type, "
            . "IFNULL(c.display_name, '') AS display_name, "
            . "m.quantity, "
            . "i.total_amount, "
            . "m.code, "
            . "m.description, "
            . "IF(IFNULL(m.category, '') = '', 'Uncategorized', category) AS category, "
            . "m.total_amount AS amount, "
            . "IFNULL(t.id, 0) AS transaction_id, "
            . "IFNULL(t.transaction_type, '') AS transaction_type, "
            . "IFNULL(t.customer_amount, 0) AS customer_amount, "
            . "IFNULL(t.source, '') AS source "
            . "FROM ciniki_sapos_invoices AS i "
            . "LEFT JOIN ciniki_customers AS c ON ("
                . "i.customer_id = c.id "
                . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoice_items AS m ON ("
                . "i.id = m.invoice_id "
                . "AND m.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_sapos_transactions AS t ON ("
                . "i.id = t.invoice_id "
                . "AND t.status >= 40 "
                . "AND t.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE i.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . "AND DATE(i.invoice_date) = '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
            . "AND t.transaction_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
            . "AND t.transaction_date < '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' "
            . "AND (i.invoice_type = 10 OR i.invoice_type = 30) "
            . "AND (i.status = 45 OR i.status = 50 OR i.status = 60 ) "
            . "ORDER BY i.invoice_number, i.invoice_date, c.display_name, m.line_number, m.id, t.id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'invoices', 'fname'=>'invoice_id', 
                'fields'=>array('id'=>'invoice_id', 'display_name', 'invoice_number', 'invoice_date', 
                    'payment_status', 'payment_status_text', 'total_amount',
                    ),
                'maps'=>array('payment_status_text'=>$maps['invoice']['payment_status']),
                'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                ),
            array('container'=>'items', 'fname'=>'id', 
                'fields'=>array('id', 'category', 'code', 'description', 'amount', 'quantity',),
                ),
            array('container'=>'transactions', 'fname'=>'transaction_id', 
                'fields'=>array('id'=>'transaction_id', 'type'=>'transaction_type', 'source', 'customer_amount'),
                'maps'=>array('source'=>$maps['transaction']['source']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.494', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
        }
        $invoices = isset($rc['invoices']) ? $rc['invoices'] : array();
//        $items = isset($rc['items']) ? $rc['items'] : array();
        $totals = array();

        //
        // Calculate the sums for each category
        //
        $total = 0;
        $textlist = '';
        $prev_invoice_number = '';
        $report_lines = array();
        foreach($invoices as $invoice_id => $invoice) {
            $invoice_total = 0;
            $item = null;
            if( isset($invoice['items']) ) {
                foreach($invoice['items'] as $iid => $item) {
                    $report_lines[] = array(
                        'invoice_number' => '#' . $invoice['invoice_number'],
                        'invoice_date' => $invoice['invoice_date'],
                        'code_desc' => ($item['code'] != '' ? $item['code'] . ' - ' : '') . $item['description'],
                        'quantity' => (float)$item['quantity'],
                        'amount' => $item['amount'],
                        'source' => '',
                        'payment_status_text' => '',
                        );
                    $items[$iid]['quantity'] = (float)$item['quantity'];
                    $items[$iid]['invoice_number'] = '#' . $invoice['invoice_number'];
                    $items[$iid]['code_desc'] = ($item['code'] != '' ? $item['code'] . ' - ' : '') . $item['description'];
                    if( isset($item['transactions']) ) {
                        foreach($item['transactions'] as $transaction) {
                            if( $prev_invoice_number != $invoice['invoice_number'] ) {
                                if( $transaction['type'] == 60 ) {
                                    $total = bcsub($total, $transaction['customer_amount'], 6);
                                    $invoice_total = bcsub($total, $transaction['customer_amount'], 6);
                                } else {
                                    $total = bcadd($total, $transaction['customer_amount'], 6);
                                    $invoice_total = bcadd($total, $transaction['customer_amount'], 6);
                                }
                                if( !isset($totals["{$transaction['source']}-{$transaction['type']}"]) ) {
                                    $totals["{$transaction['source']}-{$transaction['type']}"] = array(
                                        'source' => $transaction['source'],
                                        'type' => $transaction['type'],
                                        'total' => $transaction['customer_amount'],
                                        );
                                } else {
                                    $totals["{$transaction['source']}-{$transaction['type']}"]['total'] += $transaction['customer_amount'];
                                }
                            }
                        }
                    }
                    $textlist .= '#' . $invoice['invoice_number'] . ' ' . $item['description'] . ' ' . '$' . number_format($item['amount'], 2) . "\n";
                    $prev_invoice_number = $invoice['invoice_number'];
                }

                //
                // Add invoice total
                //
                $report_lines[] = array(
                    'invoice_number' => '',
                    'invoice_date' => '',
                    'code_desc' => '',
                    'quantity' => 'Total',
                    'amount' => $invoice['total_amount'],
                    'source' => '', 
                    'payment_status_text' => $invoice['payment_status_text'],
                    );
                $textlist .= 'Total: ' . '$' . number_format($total, 2) . "\n";

                //
                // Add transactions
                //
                if( isset($item['transactions']) ) {
                    foreach($item['transactions'] as $transaction) {
                        $report_lines[] = array(
                            'invoice_number' => '',
                            'invoice_date' => '',
                            'code_desc' => '',
                            'quantity' => '',
                            'amount' => $transaction['customer_amount'],
                            'payment_status_text' => $transaction['source'],
                            );
                    }
                }
            }
            $texttotals = '';
            foreach($totals as $tid => $t) {
                if( $t['type'] == 60 ) {
                    $totals[$tid]['type_text'] = 'Refunds';
                } else {
                    $totals[$tid]['type_text'] = 'Payments';
                }
    //            $texttotals .= $t['source'] . ': ' . number_format($t['total'], 2);
            }
        }

        //
        // List the items foreach category
        //
        if( count($report_lines) > 0 ) {
            $chunks[] = array(
                'type' => 'table',
                'title' => $start_dt->format('M j, Y'),
                'columns' => array(
                    array('label'=>'#', 'pdfwidth'=>'10%', 'field'=>'invoice_number', 'line2'=>'display_name'),
                    array('label'=>'Invoice Date', 'pdfwidth'=>'13%', 'field'=>'invoice_date', 'line2'=>'display_name'),
                    array('label'=>'Item', 'pdfwidth'=>'43%', 'field'=>'code_desc'),
                    array('label'=>'Qty', 'pdfwidth'=>'9%', 'field'=>'quantity', 'align'=>'right'),
                    array('label'=>'Amount', 'pdfwidth'=>'12%', 'type'=>'dollar', 'field'=>'amount', 'align'=>'right'),
//                    array('label'=>'Payment', 'pdfwidth'=>'13%', 'field'=>'source', 'align'=>'right'),
                    array('label'=>'Status', 'pdfwidth'=>'13%', 'field'=>'payment_status_text', 'align'=>'right'),
                    ),
                'footer' => array(
                    array('value'=>'Total', 'colspan'=>4, 'pdfwidth'=>'75%', 'align'=>'right'),
                    array('value'=>$total, 'pdfwidth'=>'12%', 'type'=>'dollar', 'align'=>'right'),
                    array('value'=>'', 'colspan'=>1, 'pdfwidth'=>'13%'),
                    ),
                'data' => $report_lines,
                'textlist' => $textlist,
                );
        } elseif( !isset($args['pdf-hide-nosales']) || $args['pdf-hide-nosales'] != 'yes' ) {
            $chunks[] = array(
                'type' => 'text',
                'title' => $start_dt->format('M j, Y'),
                'content' => 'No Sales',
                );

        }
        //
        // Summary
        //
        if( count($totals) > 0 ) {
            $chunks[] = array(
                'type' => 'table',
                'title' => $start_dt->format('M j, Y') . ' - Summary',
                'columns' => array(
                    array('label'=>'Payment', 'pdfwidth'=>'70%', 'field'=>'source', 'align'=>'right'),
                    array('label'=>'Type', 'pdfwidth'=>'15%', 'field'=>'type_text', 'align'=>'right'),
                    array('label'=>'Total', 'pdfwidth'=>'15%', 'type'=>'dollar', 'field'=>'total', 'align'=>'right'),
                    ),
                'footer' => array(
                    array('value'=>'Total', 'colspan'=>2, 'pdfwidth'=>'85%', 'align'=>'right'),
                    array('value'=>$total, 'pdfwidth'=>'15%', 'align'=>'right', 'type'=>'dollar'),
                    ),
                'data' => $totals,
                'textlist' => $texttotals,
                );
        }
        $start_dt->add(new DateInterval('P1D'));
        $end_dt->add(new DateInterval('P1D'));
    }
    
    if( count($chunks) == 0 ) {
        $chunks[] = array(
            'type' => 'text',
            'content' => 'No Sales',
            );
    }

    return array('stat'=>'ok', 'dates'=>'yes', 'chunks'=>$chunks);
}
?>
