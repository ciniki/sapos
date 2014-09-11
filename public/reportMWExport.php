<?php
//
// Description
// ===========
// This method will return the list of shipments for a given date range, and
// include the information for filling out customs forms.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_reportMWExport(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_date'=>array('required'=>'yes', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'End Date'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.reportSmartBorder'); 
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
	// Build the date range
	//
	$rsp = array('stat'=>'ok');
	$start_date = new DateTime($args['start_date'], new DateTimeZone('UTC'));
	$args['start_date'] = $start_date->format('Y-m-d H:i:s');
	if( !isset($args['end_date']) || $args['end_date'] == '' ) {
		// Set the end date if not specified to one day in the future
		$end_date = clone($start_date);
		$end_date->add(new DateInterval('P1D'));
		$args['end_date'] = $end_date->format('Y-m-d H:i:s');
		$start_date->setTimezone(new DateTimeZone($intl_timezone));
		$excel_title = "Export_" . $start_date->format('Y-m-d');
		$sheet_title = $start_date->format('Y-m-d');
	} else {
		// Make sure end date is set to end of day
		$end_date = new DateTime($args['end_date'], new DateTimeZone('UTC'));

		$start_date->setTimezone(new DateTimeZone($intl_timezone));
		$excel_title = "Export_" . $start_date->format('Y-m-d');
		$sheet_title = $start_date->format('Y-m-d');
		$end_date->setTimezone(new DateTimeZone($intl_timezone));
		$sheet_title .= ' - ' . $end_date->format('Y-m-d');

		$end_date->add(new DateInterval('P1D'));
		$args['end_date'] = $end_date->format('Y-m-d H:i:s');
	}

	//
	// Query for the ship date on shipments
	//

	$strsql = "SELECT ciniki_sapos_shipments.id, "
		. "ciniki_sapos_shipments.invoice_id, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "ciniki_sapos_shipments.shipment_number, "
		. "ciniki_customers.display_name AS customer_display_name, "
		. "ciniki_sapos_invoices.invoice_date, "
		. "ciniki_sapos_shipments.ship_date, "
		. "ciniki_sapos_shipments.boxes, "
		. "ciniki_sapos_shipments.weight, "
		. "ciniki_sapos_shipments.weight_units, "
		. "ciniki_sapos_shipments.weight_units AS weight_units_text, "
		. "ciniki_sapos_shipments.status, "
		. "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
		. "ciniki_sapos_shipment_items.id AS item_id, "
		. "ciniki_sapos_shipment_items.quantity, "
		. "ciniki_sapos_invoice_items.code, "
		. "ciniki_sapos_invoice_items.description, "
		. "ciniki_sapos_invoice_items.unit_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_percentage, "
		. "ciniki_sapos_invoice_items.taxtype_id "
		. "FROM ciniki_sapos_shipments "
		. "LEFT JOIN ciniki_sapos_invoices ON ("
			. "ciniki_sapos_shipments.invoice_id = ciniki_sapos_invoices.id "
			. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_sapos_shipment_items ON ( "
			. "ciniki_sapos_shipments.id = ciniki_sapos_shipment_items.shipment_id "
			. "AND ciniki_sapos_shipment_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_sapos_invoice_items ON ( "
			. "ciniki_sapos_shipments.invoice_id = ciniki_sapos_invoice_items.invoice_id "
			. "AND ciniki_sapos_shipment_items.item_id = ciniki_sapos_invoice_items.id "
			. "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_sapos_shipments.status > 20 "
		. "AND ciniki_sapos_shipments.ship_date >= '" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "' "
		. "AND ciniki_sapos_shipments.ship_date < '" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "' "
		. "ORDER BY ciniki_sapos_shipments.ship_date ASC, ciniki_sapos_invoices.invoice_number, ciniki_customers.display_name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	//
	// The response is a list of items, sorted by invoice
	//
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'item_id', 'name'=>'item',
			'fields'=>array('id', 'invoice_id', 'invoice_number', 'shipment_number', 'status_text', 
				'customer_display_name', 'status',
				'weight', 'weight_units', 'weight_units_text', 'num_boxes'=>'boxes', 'invoice_date', 'ship_date',
				'item_id', 'code', 'description', 'shipment_quantity'=>'quantity', 
				'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id'
				),
			'maps'=>array('status_text'=>$maps['invoice']['typestatus'],
				'weight_units_text'=>$maps['shipment']['weight_units']),
			'utctotz'=>array(
				'ship_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items']) ) {
		$items = array();
	} else {
		$items = $rc['items'];
	}

//	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
	foreach($items as $iid => $item) {
		$items[$iid]['item']['shipment_quantity'] = (float)$item['item']['shipment_quantity'];
	}
//		$num_pieces = 0;
//		$total_amount = 0;
//				$num_pieces += $item['item']['quantity'];
//				$rc = ciniki_sapos_itemCalcAmount($ciniki, array(
//					'quantity'=>$item['item']['quantity'],
//					'unit_amount'=>$item['item']['unit_amount'],
//					'unit_discount_amount'=>$item['item']['unit_discount_amount'],
//					'unit_discount_percentage'=>$item['item']['unit_discount_percentage'],
//					));
//				if( $rc['stat'] != 'ok' ) {
//					return $rc;
//				}
//				$total_amount = bcadd($total_amount, $rc['total'], 4);
//			}
//		}
//		$shipments[$sid]['shipment']['weight'] = (float)$shipment['shipment']['weight'];
//		if( $shipment['shipment']['weight'] != 1 ) {
//			$shipments[$sid]['shipment']['weight_units_text'] = $maps['shipment']['weight_units'][$shipment['shipment']['weight_units']][
//		}
//		$shipments[$sid]['shipment']['total_amount'] = $total_amount;
//		$shipments[$sid]['shipment']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt,
//			$total_amount, $intl_currency);
//		$shipments[$sid]['shipment']['num_pieces'] = $num_pieces;
//	}
//	
	//
	// Check if output should be excel
	//
	if( isset($args['output']) && $args['output'] == 'excel' ) {
		ini_set('memory_limit', '4192M');
		require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
		$objPHPExcel = new PHPExcel();
		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		error_log($sheet_title);
		$sheet->setTitle($sheet_title);

		//
		// Headers
		//
		$i = 0;
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Invoice #', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Ship #', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Order Date', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Ship Date', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Code', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Description', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Quantity', false);
		$sheet->getStyle('A1:H1')->getFont()->setBold(true);

		//
		// Output the invoice list
		//
		$row = 2;
		foreach($items as $iid => $item) {
			$item = $item['item'];
			$i = 0;
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['invoice_number'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipment_number'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['invoice_date'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['ship_date'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['customer_display_name'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['code'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['description'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipment_quantity'], false);
			$row++;
		}
		$sheet->getColumnDimension('A')->setAutoSize(true);
		$sheet->getColumnDimension('B')->setAutoSize(true);
		$sheet->getColumnDimension('C')->setAutoSize(true);
		$sheet->getColumnDimension('D')->setAutoSize(true);
		$sheet->getColumnDimension('E')->setAutoSize(true);
		$sheet->getColumnDimension('F')->setAutoSize(true);
		$sheet->getColumnDimension('G')->setAutoSize(true);
		$sheet->getColumnDimension('H')->setAutoSize(true);

		//
		// Output the excel
		//
		header('Content-Type: application/vnd.ms-excel');
		$filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $excel_title);
		header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
		header('Cache-Control: max-age=0');
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');

		return array('stat'=>'exit');
	}

	elseif( isset($args['output']) && $args['output'] == 'tab' ) {
		// FIXME: output tab delimited version
	}

	

	return array('stat'=>'ok', 'items'=>$items);
}
?>
