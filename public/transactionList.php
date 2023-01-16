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
function ciniki_sapos_transactionList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'sort'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sort Order'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output Format'), 
        'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Details'), 
        'shipments'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipments'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.transactionList'); 
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

    if( isset($args['stats']) && $args['stats'] == 'yes' ) {
        $rsp['stats'] = array();
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStats');
        $rc = ciniki_sapos__invoiceStats($ciniki, $args['tnid']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['stats']) ) {
            $rsp['stats'] = $rc['stats'];
        }
    }

    //
    // Get the list of transactions
    //
    $strsql = "SELECT t.id, "
        . "t.status AS transaction_status, "
        . "t.status AS status_text, "
        . "t.transaction_type, "
        . "t.transaction_date, "
        . "t.source, "
        . "t.source AS source_text, "
        . "t.customer_amount, "
        . "t.transaction_fees, "
        . "t.tenant_amount, "
        . "i.invoice_number, "
        . "i.invoice_date, "
        . "i.status AS invoice_status, "
        . "c.display_name AS customer_display_name "
        . "FROM ciniki_sapos_transactions AS t "
        . "LEFT JOIN ciniki_sapos_invoices AS i ON ("
            . "t.invoice_id = i.id "
            . "AND i.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS c ON ("
            . "i.customer_id = c.id "
            . "AND c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE t.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
        $strsql .= "AND t.transaction_date >= '" . $start_date->format('Y-m-d H:i:s') . "' ";
        $strsql .= "AND t.transaction_date < '" . $end_date->format('Y-m-d H:i:s') . "' ";
    }

    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $container = array(
        array('container'=>'transactions', 'fname'=>'id', 'name'=>'transaction',
            'fields'=>array('id', 'transaction_status', 'status_text', 'transaction_type', 'transaction_date', 'source', 'source_text',
                'customer_display_name', 
                'customer_amount', 'transaction_fees', 'tenant_amount', 'invoice_number', 'invoice_date', 'invoice_status'),
            'maps'=>array(
                'status_text'=>$maps['transaction']['status'],
                'source_text'=>$maps['transaction']['source'],
                'invoice_status'=>$maps['invoice']['status'],
                'transaction_type'=>$maps['transaction']['transaction_type'],
                ),
            'utctotz'=>array(
                'transaction_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                ), 
            ));
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', $container);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['transactions']) ) {
        $rsp['transactions'] = array();
    } else {
        $rsp['transactions'] = $rc['transactions'];
    }
    $rsp['totals'] = array(
        'customer_amount'=>0,
        'transaction_fees'=>0,
        'tenant_amount'=>0,
        );
    foreach($rsp['transactions'] as $tid => $transaction) {
        $rsp['transactions'][$tid]['customer_amount_display'] = numfmt_format_currency($intl_currency_fmt, $transaction['customer_amount'], $intl_currency);
        $rsp['transactions'][$tid]['transaction_fees_display'] = numfmt_format_currency($intl_currency_fmt, $transaction['transaction_fees'], $intl_currency);
        $rsp['transactions'][$tid]['tenant_amount_display'] = numfmt_format_currency($intl_currency_fmt, $transaction['tenant_amount'], $intl_currency);
        $rsp['totals']['customer_amount'] = bcadd($rsp['totals']['customer_amount'], $transaction['customer_amount'], 2);
        $rsp['totals']['transaction_fees'] = bcadd($rsp['totals']['transaction_fees'], $transaction['transaction_fees'], 2);
        $rsp['totals']['tenant_amount'] = bcadd($rsp['totals']['tenant_amount'], $transaction['tenant_amount'], 2);
    }

    $rsp['totals']['customer_amount_display'] = numfmt_format_currency($intl_currency_fmt, $rsp['totals']['customer_amount'], $intl_currency);
    $rsp['totals']['transaction_fees_display'] = numfmt_format_currency($intl_currency_fmt, $rsp['totals']['transaction_fees'], $intl_currency);
    $rsp['totals']['tenant_amount_display'] = numfmt_format_currency($intl_currency_fmt, $rsp['totals']['tenant_amount'], $intl_currency);
    $rsp['totals']['num_transactions'] = count($rsp['transactions']);

    
    //
    // Check if output should be excel
    //
    if( isset($args['output']) && $args['output'] == 'excel' ) {
        ini_set('memory_limit', '4192M');
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $title = "Transactions";
        $sheet_title = "Transactions";     // Will be overwritten, which is fine
        if( isset($args['year']) && $args['year'] != '' ) {
            $title .= " - " . $args['year'];
            $sheet_title = $args['year'];
        }
        if( isset($args['month']) && $args['month'] > 0 ) {
            $title .= " - " . $args['month'];
            $sheet_title .= " - " . $args['month'];
        }
//        if( isset($args['status']) && $args['status'] > 0 ) {
//            $title .= " - " . $maps['invoice']['status'][$args['status']];
//            $sheet_title .= " - " . $maps['invoice']['status'][$args['status']];
//        }
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle($sheet_title);

        //
        // Headers
        //
        $i = 0;
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Type', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Source', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Date', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Invoice #', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Amount', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Fees', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Net', false);
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x080000) ) {
            $sheet->setCellValueByColumnAndRow($i++, 1, 'Status', false);
            $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        } else {
            $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        }

        //
        // Output the invoice list
        //
        $row = 2;
        foreach($rsp['transactions'] as $tid => $transaction) {
//            $transaction = $transaction['invoice'];
            $i = 0;
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['transaction_type'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['source_text'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['transaction_date'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['invoice_number'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['customer_display_name'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['customer_amount'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['transaction_fees'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['tenant_amount'], false);
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x080000) ) {
                $sheet->setCellValueByColumnAndRow($i++, $row, $transaction['status_text'], false);
            }
            $row++;
        }
        if( $row > 2 ) {
//            $sheet->setCellValueByColumnAndRow(0, $row, $rsp['totals']['num_invoices'], false);
            $sheet->setCellValueByColumnAndRow(5, $row, "=SUM(F2:F" . ($row-1) . ")", false);
            $sheet->getStyle('F2:F' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            $sheet->setCellValueByColumnAndRow(6, $row, "=SUM(G2:G" . ($row-1) . ")", false);
            $sheet->getStyle('G2:G' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            $sheet->setCellValueByColumnAndRow(7, $row, "=SUM(H2:H" . ($row-1) . ")", false);
            $sheet->getStyle('H2:H' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            $sheet->getStyle('G' . $row)->getFont()->setBold(true);
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);
        }
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x080000) ) {
            $sheet->getColumnDimension('I')->setAutoSize(true);
        }

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
