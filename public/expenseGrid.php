<?php
//
// Description
// ===========
// This method will return a list of expenses.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseGrid(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output Format'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseGrid'); 
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
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Build the start and end dates to get the categories.  These need to have the time
    // added and are different than the start and end for expenses.
    //
    if( isset($args['year']) && $args['year'] != '' ) {
        //
        // Set the start and end date for the business timezone, then convert to UTC
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
    }

    //
    // Get the categories
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_sapos_expense_categories "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    //
    // Select categories which are valid for the time period requested
    //
    if( isset($args['year']) && $args['year'] != '' ) {
        $strsql .= "AND start_date <= '" . $end_date->format('Y-m-d H:i:s') . "' ";
        $strsql .= "AND (end_date = '0000-00-00 00:00:00' "
            . "OR end_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
            . ") ";
    }
    $strsql .= "ORDER BY sequence ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        $categories = array();
    } else {
        $categories = $rc['categories'];
    }

    //
    // Build an index of categories for easy reference while calculating category totals
    //
    $cidx = array();
    foreach($categories as $cid => $category) {
        $categories[$cid]['category']['total_amount'] = 0;
        $cidx[$category['category']['id']] = $cid;
    }
    
    //
    // Build the query to get the list of expenses
    //
    $strsql = "SELECT ciniki_sapos_expenses.id, "
        . "ciniki_sapos_expenses.name, "
        . "ciniki_sapos_expenses.description, "
        . "IFNULL(DATE_FORMAT(ciniki_sapos_expenses.invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS invoice_date, "
        . "IFNULL(DATE_FORMAT(ciniki_sapos_expenses.paid_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS paid_date, "
        . "ciniki_sapos_expenses.total_amount, "
        . "ciniki_sapos_expense_items.id AS item_id, "
        . "ciniki_sapos_expense_items.category_id, "
        . "ciniki_sapos_expense_items.amount AS item_amount "
        . "FROM ciniki_sapos_expenses "
        . "LEFT JOIN ciniki_sapos_expense_items ON (ciniki_sapos_expenses.id = ciniki_sapos_expense_items.expense_id "
            . "AND ciniki_sapos_expense_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_sapos_expenses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['year']) && $args['year'] != '' ) {
        //
        // Set the start and end date for the business timezone, don't convert to UTC.  These dates are stored
        // without time and are local timezone.
        //
        $tz = new DateTimeZone($intl_timezone);
        if( isset($args['month']) && $args['month'] != '' && $args['month'] > 0 ) {
            $start_date = new DateTime($args['year'] . '-' . $args['month'] . '-01 00:00:00', $tz);
            $end_date = clone $start_date;
            // Find the end of the month
            $end_date->add(new DateInterval('P1M'));
        } else {
            $start_date = new DateTime($args['year'] . '-01-01 00:00:00', $tz);
            $end_date = clone $start_date;
            // Find the end of the year
            $end_date->add(new DateInterval('P1Y'));
        }
//        $start_date->setTimezone(new DateTimeZone('UTC'));
//        $end_date->setTimeZone(new DateTimeZone('UTC'));
        //
        // Add to SQL string
        //
        $strsql .= "AND ciniki_sapos_expenses.invoice_date >= '" . $start_date->format('Y-m-d') . "' ";
        $strsql .= "AND ciniki_sapos_expenses.invoice_date < '" . $end_date->format('Y-m-d') . "' ";
    }

    //
    // Order the expenses
    //
    $strsql .= "ORDER BY ciniki_sapos_expenses.invoice_date ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'expenses', 'fname'=>'id', 'name'=>'expense',
            'fields'=>array('id', 'name', 'description', 'invoice_date', 'paid_date', 'total_amount')),
//            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
//            ),
        array('container'=>'items', 'fname'=>'item_id', 'name'=>'item',
            'fields'=>array('id'=>'item_id', 'category_id', 'amount'=>'item_amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['expenses']) ) {
        $expenses = array();
//        return array('stat'=>'ok', 'categories'=>$categories, 'expenses'=>array(), 'totals'=>array());
    } else {
        $expenses = $rc['expenses'];
    }
    $totals = array(
        'total_amount'=>0,
        );

    //
    // Calculate totals for all expenses and categories
    //
    foreach($expenses as $eid => $expense) {
        $totals['total_amount'] = bcadd($totals['total_amount'], $expense['expense']['total_amount'], 2);

        $expenses[$eid]['expense']['total_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $expense['expense']['total_amount'], $intl_currency);

        if( !isset($expense['expense']['items']) ) {
            $expense['expense']['items'] = array();
        }
        foreach($expense['expense']['items'] as $iid => $item) {
            $category_id = $item['item']['category_id'];
            if( isset($cidx[$category_id]) ) {
                $cid = $cidx[$category_id];
                $categories[$cid]['category']['total_amount'] = bcadd(
                    $categories[$cid]['category']['total_amount'], $item['item']['amount'], 2);
                $expenses[$eid]['expense']['items'][$iid]['item']['amount_display'] = numfmt_format_currency(
                    $intl_currency_fmt, $item['item']['amount'], $intl_currency);
            }
        }
    }

    //
    // Format the totals
    //
    foreach($categories as $cid => $category) {
        $categories[$cid]['category']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt,
            $categories[$cid]['category']['total_amount'], $intl_currency);
    }

    $totals['total_amount_display'] = numfmt_format_currency($intl_currency_fmt,
        $totals['total_amount'], $intl_currency);
    $totals['num_expenses'] = count($expenses);

    //
    // Output as Excel if requested
    //
    if( isset($args['output']) && $args['output'] == 'excel' ) {
        ini_set('memory_limit', '4192M');
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $title = "Expenses";
        $sheet_title = "Expenses";     // Will be overwritten, which is fine
        if( isset($args['year']) && $args['year'] != '' ) {
            $title .= " - " . $args['year'];
            $sheet_title = $args['year'];
        }
        if( isset($args['month']) && $args['month'] > 0 ) {
            $title .= " - " . $args['month'];
            $sheet_title .= " - " . $args['month'];
        }
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle($sheet_title);
        //
        // Setup headings
        //
        $i = 0;
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Date', false)->getStyle()->getFont()->setBold(true);
//        $sheet->getStyle($)->getFont()->setBold(true);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Name', false)->getStyle()->getFont()->setBold(true);
        foreach($categories as $cid => $category) {
            $sheet->setCellValueByColumnAndRow($i++, 1, $category['category']['name'], false)->getStyle()->getFont()->setBold(true);
            $sheet->getStyle(chr(64+$i) . '1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT); 
        }
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Total', false)->getStyle()->getFont()->setBold(true);
        $sheet->getStyle(chr(64+$i) . '1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT); 
        
        //
        // Output rows
        //
        $row = 1;
        foreach($expenses as $eid => $expense) {
            $row++;
            $expense = $expense['expense'];
            $i = 0;
            $sheet->setCellValueByColumnAndRow($i++, $row, $expense['invoice_date'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $expense['name'], false);
            foreach($categories as $cid => $category) {
                $category = $category['category'];
                $value = '0';
                foreach($expense['items'] as $iid => $item) {
                    $item = $item['item'];
                    if( $item['category_id'] == $category['id'] ) {
                        $value = $item['amount'];
                        break;
                    }
                }
                $sheet->setCellValueByColumnAndRow($i, $row, $value, false);
                $i++;
            }
            
            $sheet->setCellValueByColumnAndRow($i++, $row, "=SUM(C$row:" . chr(63+$i) . "$row)", false);
        }

        //
        // Setup totals row as sum functions
        //
        $i=2;
        $row++;
        $sheet->setCellValueByColumnAndRow(0, $row, "Totals", false)->getStyle()->getFont()->setBold(true);
        $sheet->mergeCells("A$row:B$row");
        $sheet->getStyle("A$row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT); 
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        foreach($categories as $cid => $category) {
            $c = chr(65+$i);
            $sheet->getColumnDimension($c)->setAutoSize(true);
            if( $row > 2 ) {
                $sheet->setCellValueByColumnAndRow($i++, $row, "=SUM($c" . "2:$c" . ($row-1) . ")", false)->getStyle()->getFont()->setBold(true);
            } else {
                $sheet->setCellValueByColumnAndRow($i++, $row, "0", false)->getStyle()->getFont()->setBold(true);
            }
        }
        $c = chr(65+$i);
        $sheet->getColumnDimension($c)->setAutoSize(true);
        if( $row > 2 ) {
            $sheet->setCellValueByColumnAndRow($i, $row, "=SUM($c" . "2:$c" . ($row-1) . ")", false)->getStyle()->getFont()->setBold(true);
        } else {
            $sheet->setCellValueByColumnAndRow($i, $row, "0", false)->getStyle()->getFont()->setBold(true);
        }
        $sheet->getStyle('C2:' . chr(65+$i) . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
        $sheet->getStyle('A1:' . chr(65+$i) . '1')->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':' . chr(65+$i) . $row)->getFont()->setBold(true);
        //
        // Set column sizes
        //
        
    
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

    return array('stat'=>'ok', 'categories'=>$categories, 'expenses'=>$expenses, 'totals'=>$totals);
}
?>
