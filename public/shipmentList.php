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
function ciniki_sapos_shipmentList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
//        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
//        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
//        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
//        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
//        'shipping_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Status'), 
//        'payment_status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Payment Status'), 
        'sort'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sort Order'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output Format'), 
        'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Details'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.shipmentList'); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
	$rc = ciniki_sapos_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// If customer_id is specified, get the details
	//
//	if( isset($args['customer']) && $args['customer'] == 'yes' 
//		&& isset($args['customer_id']) && $args['customer_id'] != '' ) {
//		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
//		$rc = ciniki_customers__customerDetails($ciniki, $args['business_id'], $args['customer_id'], 
//			array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
//		if( $rc['stat'] != 'ok' ) {
//			return $rc;
//		}
//		$customer = $rc['details'];
//	}

	//
	// Build the query to get the list of invoices
	//
	$strsql = "SELECT ciniki_sapos_shipments.id, "
		. "ciniki_sapos_shipments.invoice_id, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "ciniki_sapos_invoices.invoice_date, "
		. "ciniki_sapos_invoices.status, "
		. "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
		. "ciniki_customers.type AS customer_type, "
		. "ciniki_customers.display_name AS customer_display_name, "
		. "ciniki_sapos_invoices.total_amount "
		. "FROM ciniki_sapos_shipments "
		. "LEFT JOIN ciniki_sapos_invoices ON ("
			. "ciniki_sapos_shipments.invoice_id = ciniki_sapos_invoices.id "
			. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
/*	if( isset($args['year']) && $args['year'] != '' ) {
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
*/
	if( isset($args['status']) && $args['status'] > 0 ) {
		$strsql .= "AND ciniki_sapos_shipments.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
	}
/*	if( isset($args['payment_status']) && $args['payment_status'] > 0 ) {
		$strsql .= "AND ciniki_sapos_invoices.payment_status = '" . ciniki_core_dbQuote($ciniki, $args['payment_status']) . "' ";
	}
	if( isset($args['shipping_status']) ) {
		if( $args['shipping_status'] == 'unshipped' ) {
			$strsql .= "AND ciniki_sapos_invoices.shipping_status > 0 "
				. "AND ciniki_sapos_invoices.shipping_status < 50 ";
		} elseif( $args['shipping_status'] > 0 ) {
			$strsql .= "AND ciniki_sapos_invoices.shipping_status = '" . ciniki_core_dbQuote($ciniki, $args['shipping_status']) . "' ";
		}
	}
	if( isset($args['type']) && $args['type'] > 0 ) {
		$strsql .= "AND ciniki_sapos_invoices.invoice_type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
	}
	if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
		$strsql .= "AND ciniki_sapos_invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
	}
*/
	if( isset($args['sort']) ) {
		if( $args['sort'] == 'latest' ) {
			$strsql .= "ORDER BY ciniki_sapos_invoices.last_updated DESC ";
		} elseif( $args['sort'] == 'invoice_date' ) {
			$strsql .= "ORDER BY ciniki_sapos_invoices.invoice_date ASC, ciniki_sapos_invoices.invoice_number COLLATE latin1_general_cs ASC ";
		}
	}
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . intval($args['limit']) . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'shipments', 'fname'=>'id', 'name'=>'shipment',
			'fields'=>array('id', 'invoice_id', 'invoice_number', 'invoice_date', 'status', 'status_text', 
				'customer_type', 'customer_display_name', 'total_amount'),
			'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['shipments']) ) {
		$shipments = array();
	} else {
		$shipments = $rc['shipments'];
	}
	
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
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Date', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Amount', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Status', false);
		$sheet->getStyle('A1:E1')->getFont()->setBold(true);

		//
		// Output the invoice list
		//
		$row = 2;
		foreach($shipments as $iid => $shipment) {
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

	if( isset($customer) ) {
		return array('stat'=>'ok', 'customer'=>$customer, 'shipments'=>$shipments);
	}

	return array('stat'=>'ok', 'shipments'=>$shipments);
}
?>
