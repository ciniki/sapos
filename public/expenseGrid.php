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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'expense_type'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Type'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output Format'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.expenseGrid'); 
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
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Build the start and end dates to get the categories.  These need to have the time
    // added and are different than the start and end for expenses.
    //
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
    }

    //
    // Get the categories
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_sapos_expense_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    //
    // Build an index of categories for easy reference while calculating category totals
    //
    $cidx = array();
    foreach($categories as $cid => $category) {
        $categories[$cid]['total_amount'] = 0;
        $cidx[$category['id']] = $cid;
    }

    //
    // Build the query to get the list of expenses
    //
    $strsql = "SELECT expenses.id, "
        . "expenses.name, "
        . "expenses.description, "
        . "IFNULL(DATE_FORMAT(expenses.invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS invoice_date, "
        . "IFNULL(DATE_FORMAT(expenses.paid_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS paid_date, "
        . "expenses.total_amount, "
        . "items.id AS item_id, "
        . "items.category_id, "
        . "items.amount AS item_amount "
        . "FROM ciniki_sapos_expenses AS expenses "
        . "LEFT JOIN ciniki_sapos_expense_items AS items ON ("
            . "expenses.id = items.expense_id "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['expense_type']) && $args['expense_type'] != 10 ) {
        $strsql .= "AND expenses.expense_type = '" . ciniki_core_dbQuote($ciniki, $args['expense_type']) . "' ";
    } else {
        $strsql .= "AND expenses.expense_type = 10 ";
    }
    if( isset($args['year']) && $args['year'] != '' ) {
        //
        // Set the start and end date for the tenant timezone, don't convert to UTC.  These dates are stored
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
        $strsql .= "AND expenses.invoice_date >= '" . $start_date->format('Y-m-d') . "' ";
        $strsql .= "AND expenses.invoice_date < '" . $end_date->format('Y-m-d') . "' ";
    }

    //
    // Order the expenses
    //
    $strsql .= "ORDER BY expenses.invoice_date ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'expenses', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'description', 'invoice_date', 'paid_date', 'total_amount'),
            ),
        array('container'=>'items', 'fname'=>'item_id', 
            'fields'=>array('id'=>'item_id', 'category_id', 'amount'=>'item_amount'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $expenses = isset($rc['expenses']) ? $rc['expenses'] : array();
    $totals = array(
        'total_amount'=>0,
        );

    //
    // Calculate totals for all expenses and categories
    //
    foreach($expenses as $eid => $expense) {
        $totals['total_amount'] = bcadd($totals['total_amount'], $expense['total_amount'], 2);

        $expenses[$eid]['total_amount_display'] = '$' . number_format($expense['total_amount'], 2);

        if( !isset($expense['items']) ) {
            $expense['items'] = array();
            $expenses[$eid]['items'] = array();
        }
        foreach($expense['items'] as $iid => $item) {
            $category_id = $item['category_id'];
            if( isset($cidx[$category_id]) ) {
                $cid = $cidx[$category_id];
                $categories[$cid]['total_amount'] = bcadd(
                    $categories[$cid]['total_amount'], $item['amount'], 2);
                $expenses[$eid]['items'][$iid]['amount_display'] = '$' . number_format($item['amount'], 2);
            }
        }
    }

    //
    // Format the totals
    //
    foreach($categories as $cid => $category) {
        if( $categories[$cid]['total_amount'] > 0 ) {
            $categories[$cid]['total_amount_display'] = '$' . number_format($categories[$cid]['total_amount'], 2);
        } else {
            $categories[$cid]['total_amount_display'] = '';
        }
    }

    $totals['total_amount_display'] = '$' . number_format($totals['total_amount'], 2);
    $totals['num_expenses'] = count($expenses);

    $rsp = array('stat'=>'ok', 'categories'=>$categories, 'expenses'=>$expenses, 'totals'=>$totals);

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
            $sheet->setCellValueByColumnAndRow($i++, 1, $category['name'], false)->getStyle()->getFont()->setBold(true);
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
            $i = 0;
            $sheet->setCellValueByColumnAndRow($i++, $row, $expense['invoice_date'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $expense['name'], false);
            foreach($categories as $cid => $category) {
                $value = '0';
                foreach($expense['items'] as $iid => $item) {
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

    return $rsp;
}
?>
