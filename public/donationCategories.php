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
function ciniki_sapos_donationCategories(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'sort'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sort Order'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output Format'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'payment_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment Status'), 
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stats'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', 'fiscal');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.423', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();
    
    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $rsp['stats'] = array();
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStats');
    $rc = ciniki_sapos__invoiceStats($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['stats']) ) {
        $rsp['stats'] = $rc['stats'];
    }
    $rsp['categories'] = array();
    if( isset($rsp['stats']['donationcategories']) ) {
        foreach($rsp['stats']['donationcategories'] as $cid => $c) {
            $rsp['categories'][$c['name']] = array('name'=>$c['name'], 'total_amount'=>0);
        }
    }
    $rsp['totals']['total_amount'] = 0;
    $rsp['totals']['num_invoices'] = 0;

    //
    // Build the query to get the list of invoices
    //
    $strsql = "SELECT ciniki_sapos_invoices.id, "
        . "ciniki_sapos_invoices.invoice_number, "
        . "ciniki_sapos_invoices.receipt_number, "
        . "ciniki_sapos_invoices.invoice_date, "
        . "ciniki_sapos_invoices.payment_status, "
        . "ciniki_sapos_invoices.payment_status AS payment_status_text, "
        . "ciniki_sapos_invoices.po_number, "
        . "ciniki_customers.type AS customer_type, "
        . "ciniki_customers.display_name AS customer_display_name, "
        . "(SELECT GROUP_CONCAT(email) AS emails "
            . "FROM ciniki_customer_emails AS emails "
            . "WHERE ciniki_customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") AS emails, "
        . "ciniki_sapos_invoices.total_amount, "
        . "items.id AS item_id, "
        . "IF(IFNULL(items.subcategory, '') = '', 'Uncategorized', subcategory) AS category, "
//        . "SUM(items.total_amount) AS amount "
        . "SUM(IF((items.flags&0x0800)=0x0800, items.unit_donation_amount, items.total_amount)) AS amount "
        . "FROM ciniki_sapos_invoices "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_sapos_invoices.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
            . "ciniki_sapos_invoices.id = items.invoice_id "
            . "AND (items.flags&0x8800) > 0 "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['year']) && $args['year'] != '' ) {
        //
        // Set the start and end date for the tenant timezone, then convert to UTC
        //
        $tz = new DateTimeZone($intl_timezone);
        if( isset($settings['fiscal-year-start-month']) && isset($settings['fiscal-year-start-day']) 
            && $settings['fiscal-year-start-day'] > 0 && $settings['fiscal-year-start-day'] < 32
            ) {
            if( isset($args['month']) && $args['month'] != '' && $args['month'] > 0 ) {
                if( $args['month'] >= $settings['fiscal-year-start-month'] ) {
                    $start_date = new DateTime(($args['year']-1) . '-' . $args['month'] . '-' . $settings['fiscal-year-start-day'] . ' 00:00:00', $tz);
                } else {
                    $start_date = new DateTime($args['year'] . '-' . $args['month'] . '-' . $settings['fiscal-year-start-day'] . ' 00:00:00', $tz);
                }
                $end_date = clone $start_date;
                $end_date->add(new DateInterval('P1M'));
            } else {
                $end_date = new DateTime($args['year'] . '-' . $settings['fiscal-year-start-month'] . '-' . $settings['fiscal-year-start-day'] . ' 00:00:00', $tz);
                $start_date = clone $end_date;
                $start_date->sub(new DateInterval('P1Y'));
            }
        } else {
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
        }
        $start_date->setTimezone(new DateTimeZone('UTC'));
        $end_date->setTimeZone(new DateTimeZone('UTC'));
        //
        // Add to SQL string
        //
        $strsql .= "AND ciniki_sapos_invoices.invoice_date >= '" . $start_date->format('Y-m-d H:i:s') . "' ";
        $strsql .= "AND ciniki_sapos_invoices.invoice_date < '" . $end_date->format('Y-m-d H:i:s') . "' ";
    }
    if( isset($args['status']) && $args['status'] > 0 ) {
        $strsql .= "AND ciniki_sapos_invoices.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['payment_status']) && $args['payment_status'] > 0 ) {
        $strsql .= "AND ciniki_sapos_invoices.payment_status = '" . ciniki_core_dbQuote($ciniki, $args['payment_status']) . "' ";
    }
    $strsql .= "AND (ciniki_sapos_invoices.invoice_type = 10 || ciniki_sapos_invoices.invoice_type = 30) ";
    $strsql .= "GROUP BY ciniki_sapos_invoices.id, items.subcategory ";
    $strsql .= "ORDER BY ciniki_sapos_invoices.invoice_date ASC, ciniki_sapos_invoices.invoice_number ASC, items.category ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array('id', 'invoice_number', 'receipt_number', 'invoice_date', 'payment_status', 'payment_status_text', 'customer_type', 'customer_display_name', 'emails', 'total_amount'),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
            'maps'=>array('payment_status_text'=>$maps['invoice']['payment_status']),
            ),
        array('container'=>'cats', 'fname'=>'category', 'fields'=>array('name'=>'category', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoices']) ) {
        $rsp['invoices'] = array();
    } else {
        $rsp['invoices'] = $rc['invoices'];
    }
    foreach($rsp['invoices'] as $iid => $invoice) {
        $rsp['invoices'][$iid]['categories'] = array();
        $invoice['total_amount'] = 0;
        if( isset($invoice['cats']) ) {
            foreach($invoice['cats'] as $cid => $category) {
                $category['amount_display'] = numfmt_format_currency($intl_currency_fmt, $category['amount'], $intl_currency);
                $rsp['invoices'][$iid]['categories'][$category['name']] = $category;
//                $rsp['invoices'][$iid]['categories'][$cid]['amount_display'] = numfmt_format_currency($intl_currency_fmt, $category['amount'], $intl_currency);
                $rsp['categories'][$category['name']]['total_amount'] = bcadd($rsp['categories'][$category['name']]['total_amount'], $category['amount'], 6);
                $invoice['total_amount'] = bcadd($invoice['total_amount'], $category['amount'], 2);
            }
        }
        $rsp['totals']['total_amount'] = bcadd($rsp['totals']['total_amount'], $invoice['total_amount'], 6);
        
        $rsp['invoices'][$iid]['total_amount'] = $invoice['total_amount'];
        $rsp['invoices'][$iid]['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, $invoice['total_amount'], $intl_currency);
    }
    $rsp['totals']['total_amount'] = numfmt_format_currency($intl_currency_fmt, $rsp['totals']['total_amount'], $intl_currency);
    $rsp['totals']['num_invoices'] = count($rsp['invoices']);


    $rsp['categories'] = array_values($rsp['categories']);
    foreach($rsp['categories'] as $cid => $c) {
        $rsp['categories'][$cid]['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, $c['total_amount'], $intl_currency);
    }
    
    //
    // Check if output should be excel
    //
    if( isset($args['output']) && $args['output'] == 'excel' ) {
        ini_set('memory_limit', '4192M');
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $title = "Invoices";
        $sheet_title = "Invoices";     // Will be overwritten, which is fine
        if( isset($args['year']) && $args['year'] != '' ) {
            $title .= " - " . $args['year'];
            $sheet_title = $args['year'];
        }
        if( isset($args['month']) && $args['month'] > 0 ) {
            $title .= " - " . $args['month'];
            $sheet_title .= " - " . $args['month'];
        }
        if( isset($args['status']) && $args['status'] > 0 ) {
            $title .= " - " . $maps['invoice']['status'][$args['status']];
            $sheet_title .= " - " . $maps['invoice']['status'][$args['status']];
        }
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle($sheet_title);

        //
        // Headers
        //
        $i = 0;
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Invoice #', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Rcpt #', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Date', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Email', false);
        foreach($rsp['categories'] as $c) {
            $sheet->setCellValueByColumnAndRow($i++, 1, $c['name'], false);
        }
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Total', false);
        $sheet->getStyle('A1:' . chr($i+65) . '1')->getFont()->setBold(true);

        //
        // Output the invoice list
        //
        $row = 2;
        foreach($rsp['invoices'] as $iid => $invoice) {
            $i = 0;
            $sheet->setCellValueByColumnAndRow($i++, $row, $invoice['invoice_number'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $invoice['receipt_number'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $invoice['invoice_date'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $invoice['customer_display_name'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $invoice['emails'], false);
            foreach($rsp['categories'] as $c) {
                if( isset($invoice['categories'][$c['name']]['amount']) ) {
                    $sheet->setCellValueByColumnAndRow($i, $row, $invoice['categories'][$c['name']]['amount'], false);
                }
                $i++;
            }
            $sheet->setCellValueByColumnAndRow($i++, $row, $invoice['total_amount'], false);
            $row++;
        }
        if( $row > 2 ) {
            $sheet->setCellValueByColumnAndRow(0, $row, $rsp['totals']['num_invoices'], false);
            $i = 3;
            foreach($rsp['categories'] as $c) {
                $sheet->setCellValueByColumnAndRow($i, $row, "=SUM(" . chr($i+65) . "2:" . chr($i+65) . ($row-1) . ")", false);
                $sheet->getStyle(chr($i+65) . '2:' . chr($i+65) . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                $sheet->getStyle(chr($i+65) . $row)->getFont()->setBold(true);
                $i++;
            }
            $sheet->setCellValueByColumnAndRow($i, $row, "=SUM(" . chr($i+65) . "2:" . chr($i+65) . ($row-1) . ")", false);
            $sheet->getStyle(chr($i+65) . '2:' . chr($i+65) . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            $sheet->getStyle(chr($i+65) . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        }
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $i = 3;
        foreach($rsp['categories'] as $c) {
            $sheet->getColumnDimension(chr($i+65))->setAutoSize(true);
            $i++;
        }
        $sheet->getColumnDimension(chr($i+65))->setAutoSize(true);
        $sheet->freezePane('A2');

        //
        // Output the excel
        //
        header('Content-Type: application/vnd.ms-excel');
        $filename = preg_replace('/[^a-zA-Z0-9\-]/', '', $title);
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

        return array('stat'=>'exit');
    }

    $rsp['stat'] = 'ok';
    return $rsp;
}
?>
