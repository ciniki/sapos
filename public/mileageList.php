<?php
//
// Description
// ===========
// This method will return a list of mileage entries.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_mileageList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.mileageList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_distance_units = $rc['settings']['intl-default-distance-units'];
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
    $strsql = "SELECT ciniki_sapos_mileage.id, "
        . "ciniki_sapos_mileage.start_name, "
        . "ciniki_sapos_mileage.start_address, "
        . "ciniki_sapos_mileage.end_name, "
        . "ciniki_sapos_mileage.end_address, "
        . "ciniki_sapos_mileage.travel_date, "
        . "ciniki_sapos_mileage.distance, "
        . "ciniki_sapos_mileage.flags, "
        . "IF((ciniki_sapos_mileage.flags&0x01)>0,'Round Trip','One Way') AS round_trip, "
        . "ciniki_sapos_mileage.notes, "
        . "ciniki_sapos_mileage_rates.rate "
        . "FROM ciniki_sapos_mileage "
        . "LEFT JOIN ciniki_sapos_mileage_rates ON ("
            . "ciniki_sapos_mileage.travel_date >= ciniki_sapos_mileage_rates.start_date "
            . "AND (ciniki_sapos_mileage_rates.end_date = '0000-00-00 00:00:00' "
                . "OR ciniki_sapos_mileage.travel_date <= ciniki_sapos_mileage_rates.end_date "
                . ") "
            . "AND ciniki_sapos_mileage_rates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_sapos_mileage.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
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
        //
        // Add to SQL string
        //
        $strsql .= "AND ciniki_sapos_mileage.travel_date >= '" . $start_date->format('Y-m-d H:i:s') . "' ";
        $strsql .= "AND ciniki_sapos_mileage.travel_date < '" . $end_date->format('Y-m-d H:i:s') . "' ";
    }
    if( isset($args['sort']) ) {
        if( $args['sort'] == 'latest' ) {
            $strsql .= "ORDER BY ciniki_sapos_mileage.last_updated DESC ";
        }
    }
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'mileages', 'fname'=>'id', 'name'=>'mileage',
            'fields'=>array('id', 'start_name', 'start_address', 'end_name', 'end_address', 
                'travel_date', 'distance', 'round_trip', 'flags', 'rate', 'notes'),
            'utctotz'=>array('travel_date'=>array('timezone'=>'UTC', 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['mileages']) ) {
        $mileages = array();
    } else {
        $mileages = $rc['mileages'];
    }
    $totals = array('distance'=>0, 'amount'=>0);
    foreach($mileages as $mid => $mileage) {
        // Calculate the total distance of the trip 
        if( ($mileage['mileage']['flags']&0x01) > 0 ) {
            // Round trip
            $mileages[$mid]['mileage']['total_distance'] = (float)bcmul($mileage['mileage']['distance'], 2, 2);
        } else {
            $mileages[$mid]['mileage']['total_distance'] = (float)$mileage['mileage']['distance'];
        }
        // Calculate amount to be expensed/paid back
        $mileages[$mid]['mileage']['amount'] = bcmul($mileages[$mid]['mileage']['total_distance'], $mileage['mileage']['rate'], 2);
        // Setup
        $mileages[$mid]['mileage']['amount_display'] = numfmt_format_currency($intl_currency_fmt,
            $mileages[$mid]['mileage']['amount'], $intl_currency);
        $mileages[$mid]['mileage']['rate_display'] = numfmt_format_currency($intl_currency_fmt, 
            $mileage['mileage']['rate'], $intl_currency);
        $mileages[$mid]['mileage']['units'] = $intl_distance_units;
        // add distance and amount to totals
        $totals['distance'] = bcadd($totals['distance'], $mileages[$mid]['mileage']['total_distance'], 2);
        $totals['amount'] = bcadd($totals['amount'], $mileages[$mid]['mileage']['amount'], 2);
    }

    // Format the total amount for display
    $totals['distance'] = (float)$totals['distance'];
    $totals['units'] = $intl_distance_units;
    $totals['amount_display'] = numfmt_format_currency($intl_currency_fmt, $totals['amount'], $intl_currency);
    $totals['num_entries'] = count($mileages);

    
    //
    // FIXME: Output to excel
    //
    if( isset($args['output']) && $args['output'] == 'excel' ) {
        ini_set('memory_limit', '4192M');
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $title = "Mileage";
        $sheet_title = "Mileage";     // Will be overwritten, which is fine
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
        // Headers
        //
        $i = 0;
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Date', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'From', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Address', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'To', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Address', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Distance (' . $intl_distance_units . ')', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Options', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Rate', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Amount', false);
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        //
        // Output the invoice list
        //
        $row = 2;
        foreach($mileages as $mid => $mileage) {
            $mileage = $mileage['mileage'];
            $i = 0;
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['travel_date'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['start_name'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['start_address'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['end_name'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['end_address'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['total_distance'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['round_trip'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['rate'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $mileage['amount'], false);
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            $row++;
        }
        if( $row > 2 ) {
            $sheet->setCellValueByColumnAndRow(5, $row, "=SUM(F2:F" . ($row-1) . ")", false);
            $sheet->setCellValueByColumnAndRow(8, $row, "=SUM(I2:I" . ($row-1) . ")", false);
            $sheet->getStyle('I2:I' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            $sheet->getStyle('I' . $row)->getFont()->setBold(true);
        }
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);

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

    return array('stat'=>'ok', 'totals'=>$totals, 'mileages'=>$mileages);
}
?>
