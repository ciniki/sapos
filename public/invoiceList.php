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
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'sort'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sort Order'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'15', 'name'=>'Limit'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceList'); 
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
	$date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStatusMaps');
	$rc = ciniki_sapos_invoiceStatusMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$status_maps = $rc['maps'];

	//
	// Build the query to get the list of invoices
	//
	$strsql = "SELECT ciniki_sapos_invoices.id, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "invoice_date, "
		. "ciniki_sapos_invoices.status, "
		. "ciniki_sapos_invoices.status AS status_text, "
		. "ciniki_customers.type AS customer_type, "
		. "ciniki_customers.display_name AS customer_display_name, "
		. "total_amount "
		. "FROM ciniki_sapos_invoices "
		. "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
		$strsql .= "AND ciniki_sapos_invoices.invoice_date >= '" . $start_date->format('Y-m-d H:i:s') . "' ";
		$strsql .= "AND ciniki_sapos_invoices.invoice_date < '" . $end_date->format('Y-m-d H:i:s') . "' ";
	}
	if( isset($args['status']) && $args['status'] > 0 ) {
		$strsql .= "AND ciniki_sapos_invoices.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
	}
	if( isset($args['sort']) ) {
		if( $args['sort'] == 'latest' ) {
			$strsql .= "ORDER BY ciniki_sapos_invoices.last_updated DESC ";
		}
	}
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . intval($args['limit']) . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
			'fields'=>array('id', 'invoice_number', 'invoice_date', 'status', 'status_text', 
				'customer_type', 'customer_display_name', 'total_amount'),
			'maps'=>array('status_text'=>$status_maps),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoices']) ) {
		$invoices = array();
	} else {
		$invoices = $rc['invoices'];
	}
	$totals = array(
		'total_amount'=>0,
		);
	foreach($invoices as $iid => $invoice) {
		$invoices[$iid]['invoice']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
			$invoice['invoice']['total_amount'], $intl_currency);
		$totals['total_amount'] = bcadd($totals['total_amount'], $invoice['invoice']['total_amount'], 2);
	}

	$totals['total_amount'] = numfmt_format_currency($intl_currency_fmt,
		$totals['total_amount'], $intl_currency);
	$totals['num_invoices'] = count($invoices);

	
	//
	// Check if output should be excel
	//
	if( isset($args['output']) && $args['output'] == 'excel' ) {
		ini_set('memory_limit', '4192M');
		require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
		$objPHPExcel = new PHPExcel();
		$title = "Invoices";
		$sheet_title = "Invoices"; 	// Will be overwritten, which is fine
		if( isset($args['year']) && $args['year'] != '' ) {
			$title .= " - " . $args['year'];
			$sheet_title = $args['year'];
		}
		if( isset($args['month']) && $args['month'] > 0 ) {
			$title .= " - " . $args['month'];
			$sheet_title .= " - " . $args['month'];
		}
		if( isset($args['status']) && $args['status'] > 0 ) {
			$title .= " - " . $status_maps[$args['status']];
			$sheet_title .= " - " . $status_maps[$args['status']];
		}
		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		$sheet->setTitle($sheet_title);

		//
		// Headers
		//
		$i = 0;
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Invoice #', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Date', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Amount', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Status', false);
		$sheet->getStyle('A1:E1')->getFont()->setBold(true);

		//
		// Output the invoice list
		//
		$row = 2;
		foreach($invoices as $iid => $invoice) {
			$invoice = $invoice['invoice'];
			$i = 0;
			$sheet->setCellValueByColumnAndRow($i++, $row, $invoice['invoice_number'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $invoice['invoice_date'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $invoice['customer_display_name'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $invoice['total_amount'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $invoice['status_text'], false);
			$row++;
		}
		if( $row > 2 ) {
			$sheet->setCellValueByColumnAndRow(0, $row, $totals['num_invoices'], false);
			$sheet->setCellValueByColumnAndRow(3, $row, "=SUM(D2:D" . ($row-1) . ")", false);
			$sheet->getStyle('D2:D' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
			$sheet->getStyle('A' . $row)->getFont()->setBold(true);
			$sheet->getStyle('D' . $row)->getFont()->setBold(true);
		}
		$sheet->getColumnDimension('A')->setAutoSize(true);
		$sheet->getColumnDimension('B')->setAutoSize(true);
		$sheet->getColumnDimension('C')->setAutoSize(true);
		$sheet->getColumnDimension('D')->setAutoSize(true);
		$sheet->getColumnDimension('E')->setAutoSize(true);

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

	return array('stat'=>'ok', 'totals'=>$totals, 'invoices'=>$invoices);
}
?>