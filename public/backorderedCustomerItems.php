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
function ciniki_sapos_backorderedCustomerItems(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.backorderedItems'); 
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
	// Check if we need to return the list of sales reps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	if( ($ciniki['business']['modules']['ciniki.sapos']['flags']&0x0800) > 0 ) {
		//
		// Get the active sales reps
		//
		$strsql = "SELECT ciniki_users.id, ciniki_users.display_name "
			. "FROM ciniki_business_users, ciniki_users "
			. "WHERE ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_business_users.package = 'ciniki' "
			. "AND ciniki_business_users.permission_group = 'salesreps' "
			. "AND ciniki_business_users.status < 60 "
			. "AND ciniki_business_users.user_id = ciniki_users.id "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
			array('container'=>'salesreps', 'fname'=>'id', 
				'fields'=>array('id', 'name'=>'display_name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['salesreps']) ) {
			$salesreps = $rc['salesreps'];
		} else {
			$salesreps = array();
		}
	}

	//
	// Check out if tax codes should be loaded
	//
	if( isset($ciniki['business']['modules']['ciniki.taxes'])
		&& ($ciniki['business']['modules']['ciniki.taxes']['flags']&0x01) > 0 
		) {
		$strsql = "SELECT ciniki_tax_locations.id, "
			. "ciniki_tax_locations.code, "
			. "ciniki_tax_locations.name "
			. "FROM ciniki_tax_locations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
			array('container'=>'locations', 'fname'=>'id', 
				'fields'=>array('id', 'code', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['locations']) ) {
			$tax_locations = $rc['locations'];
		} else {
			$tax_locations = array();
		}
	}

	//
	// Check if we need to load pricepoints
	//
	if( isset($ciniki['business']['modules']['ciniki.customers'])
		&& ($ciniki['business']['modules']['ciniki.customers']['flags']&0x1000) > 0 
		) {
		$strsql = "SELECT id, "
			. "code, "
			. "name "
			. "FROM ciniki_customer_pricepoints "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
			array('container'=>'pricepoints', 'fname'=>'id', 
				'fields'=>array('id', 'code', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['pricepoints']) ) {
			$pricepoints = $rc['pricepoints'];
		} else {
			$pricepoints = array();
		}
	}

	//
	// Get the list of items that are backordered
	//
	$strsql = "SELECT "
		. "ciniki_sapos_invoice_items.id AS item_id, "
		. "ciniki_sapos_invoices.id AS invoice_id, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "ciniki_sapos_invoices.po_number, "
		. "ciniki_customers.eid AS customer_eid, "
		. "ciniki_customers.display_name AS customer_display_name, "
		. "ciniki_customers.reward_level, "
		. "ciniki_sapos_invoices.invoice_date, "
		. "ciniki_sapos_invoices.salesrep_id, "
		. "ciniki_sapos_invoices.customer_notes, "
		. "ciniki_sapos_invoices.internal_notes, "
		. "ciniki_sapos_invoices.shipping_name, "
		. "ciniki_sapos_invoices.shipping_address1, "
		. "ciniki_sapos_invoices.shipping_address2, "
		. "ciniki_sapos_invoices.shipping_city, "
		. "ciniki_sapos_invoices.shipping_province, "
		. "ciniki_sapos_invoices.shipping_postal, "
		. "ciniki_sapos_invoices.shipping_country, "
		. "ciniki_sapos_invoices.status, "
		. "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
		. "ciniki_sapos_invoice_items.code, "
		. "ciniki_sapos_invoice_items.description, "
		. "ciniki_sapos_invoice_items.notes, "
		. "ciniki_sapos_invoice_items.quantity AS ordered_quantity, "
		. "ciniki_sapos_invoice_items.shipped_quantity, "
		. "(ciniki_sapos_invoice_items.quantity - ciniki_sapos_invoice_items.shipped_quantity) AS bo_quantity, "
		. "ciniki_sapos_invoice_items.unit_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_percentage, "
		. "ciniki_sapos_invoice_items.object, "
		. "ciniki_sapos_invoice_items.object_id, "
		. "ciniki_sapos_invoice_items.price_id, "
		. "ciniki_sapos_invoice_items.flags, "
		. "IFNULL(ciniki_product_prices.pricepoint_id, 0) AS pricepoint_id, "
		. "ciniki_sapos_invoices.tax_location_id, "
		. "ciniki_sapos_invoice_items.taxtype_id "
		. "FROM ciniki_sapos_invoice_items "
		. "INNER JOIN ciniki_sapos_invoices ON ("
			. "ciniki_sapos_invoice_items.invoice_id = ciniki_sapos_invoices.id "
			. "AND ciniki_sapos_invoices.status < 50 "
			. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customers ON ("
			. "ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_product_prices ON ( "
			. "ciniki_sapos_invoice_items.object = 'ciniki.products.product' "
			. "AND ciniki_sapos_invoice_items.price_id = ciniki_product_prices.id "
			. "AND ciniki_sapos_invoice_items.object_id = ciniki_product_prices.product_id "
			. "AND ciniki_product_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND (ciniki_sapos_invoice_items.flags&0x40) = 0x40 "	// Shipped item
//		. "AND (ciniki_sapos_invoice_items.flags&0x0300) = 0x0300 " // backordered item
		. "AND (ciniki_sapos_invoice_items.quantity - ciniki_sapos_invoice_items.shipped_quantity) > 0 "
//		. "GROUP BY code, object, object_id "
		. "ORDER BY ciniki_sapos_invoices.invoice_number, ciniki_customers.display_name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'item_id', 'name'=>'item',
			'fields'=>array('id'=>'item_id', 'invoice_id', 'invoice_number', 'invoice_date', 'po_number', 'status_text', 
				'shipping_name', 'shipping_address1', 'shipping_address2',
				'shipping_city', 'shipping_province', 'shipping_postal', 'shipping_country',
				'customer_eid', 'customer_display_name', 'reward_level', 'status', 
				'salesrep_id', 'customer_notes', 'internal_notes', 
				'item_id', 'code', 'description', 'notes', 
				'object', 'object_id', 
				'ordered_quantity', 'shipped_quantity', 'bo_quantity', 
				'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 
				'tax_location_id', 'pricepoint_id', 'taxtype_id'
				),
			'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
			'utctotz'=>array(
				'invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items']) ) {
		return array('stat'=>'ok', 'items'=>array());
	}
	$items = $rc['items'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
	$prev_invoice_id = 0;
	$objects = array();
	$invoices = array();	// The array of invoice totals
	foreach($items as $iid => $item) {
		if( !isset($objects[$item['item']['object']]) ) {
			$objects[$item['item']['object']] = array();
		}
		if( !isset($objects[$item['item']['object']][$item['item']['object_id']]) ) {
			$objects[$item['item']['object']][$item['item']['object_id']] = array();
		}
		// Create the array to store the invoice totals by invoice_id
		if( !isset($invoices[$item['item']['invoice_id']]) ) {
			$invoices[$item['item']['invoice_id']] = array('total_amount'=>0, 'num_pieces'=>0);
		}
		if( ($ciniki['business']['modules']['ciniki.sapos']['flags']&0x0800) > 0 ) {
			if( isset($salesreps[$item['item']['salesrep_id']]) ) {
				$items[$iid]['item']['salesrep_display_name'] = $salesreps[$item['item']['salesrep_id']]['name'];
			} else {
				$items[$iid]['item']['salesrep_display_name'] = '';
			}
		}
		if( isset($tax_locations) ) {
			if( isset($tax_locations[$item['item']['tax_location_id']]) ) {
				$items[$iid]['item']['tax_location_code'] = $tax_locations[$item['item']['tax_location_id']]['code'];
			} else {
				$items[$iid]['item']['tax_location_code'] = '';
			}
		}
		if( isset($pricepoints) ) {
			if( isset($pricepoints[$item['item']['pricepoint_id']]) ) {
				$items[$iid]['item']['pricepoint_code'] = $pricepoints[$item['item']['pricepoint_id']]['code'];
			} else {
				$items[$iid]['item']['pricepoint_code'] = '';
			}
		}
		$items[$iid]['item']['ordered_quantity'] = (float)$item['item']['ordered_quantity'];
		$items[$iid]['item']['shipped_quantity'] = (float)$item['item']['shipped_quantity'];

		$items[$iid]['item']['unit_amount_display'] = numfmt_format_currency($intl_currency_fmt,
			$item['item']['unit_amount'], $intl_currency);
		$rc = ciniki_sapos_itemCalcAmount($ciniki, array(
			'quantity'=>$item['item']['bo_quantity'],
			'unit_amount'=>$item['item']['unit_amount'],
			'unit_discount_amount'=>$item['item']['unit_discount_amount'],
			'unit_discount_percentage'=>$item['item']['unit_discount_percentage'],
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$items[$iid]['item']['total_amount'] = $rc['total'];
		$items[$iid]['item']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt,
			$rc['total'], $intl_currency);
	}

	foreach($objects as $object => $object_ids) {
		list($pkg,$mod,$obj) = explode('.', $object);
		$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryLevels');
		if( $rc['stat'] == 'ok' ) {
			$fn = $rc['function_call'];
			$rc = $fn($ciniki, $args['business_id'], array(
				'object'=>$object,
				'object_ids'=>array_keys($object_ids),
				));
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2403', 'msg'=>'Unable to get inventory levels.', 'err'=>$rc['err']));
			}
			//
			// Update the inventory levels for the invoice items
			//
			$objects[$object]['quantities'] = $rc['quantities'];
		}
	}

//	$rsp = array('stat'=>'ok', 'items'=>array());
	foreach($items as $iid => $item) {
		//
		// Set the totals
		//
//		$items[$iid]['item']['num_pieces'] = $invoices[$item['item']['invoice_id']]['num_pieces'];
//		$items[$iid]['item']['invoice_total_amount'] = $invoices[$item['item']['invoice_id']]['total_amount'];
//		$items[$iid]['item']['invoice_total_amount_display'] = numfmt_format_currency($intl_currency_fmt,
//			$invoices[$item['item']['invoice_id']]['total_amount'], $intl_currency);
//		$items[$iid]['item']['reserved_quantity'] = (float)$items[$iid]['item']['reserved_quantity'];
		//
		// Set quantities
		//
		if( isset($objects[$item['item']['object']]['quantities'][$item['item']['object_id']]['inventory_quantity']) ) {
			$items[$iid]['item']['inventory_current_num'] = (float)$objects[$item['item']['object']]['quantities'][$item['item']['object_id']]['inventory_quantity'];
//			$items[$iid]['item']['backordered_quantity'] = $items[$iid]['item']['reserved_quantity'] - $items[$iid]['item']['inventory_current_num'];
//			if( $items[$iid]['item']['backordered_quantity'] > 0 ) {
//				$rsp['items'][] = $item;
//			}
		} elseif( ($item['item']['flags']&0x0300) > 0 ) {
			$items[$iid]['item']['inventory_current_num'] = '';
//			$items[$iid]['item']['backordered_quantity'] = '';
		}
	}

	//
	// Check if output should be excel
	//
	if( isset($args['output']) && $args['output'] == 'excel' ) {
		$excel_title = 'Backordered Items';
		ini_set('memory_limit', '4192M');
		require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
		$objPHPExcel = new PHPExcel();
		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		$sheet->setTitle($excel_title);

		//
		// Headers
		//
		$i = 0;
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Invoice Number', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'PO Number', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Order Date', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Invoice Status', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Customer ID', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Reward Level', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Rep', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Code', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Description', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Notes', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Ordered', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Backordered', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipped', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Price Code', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Unit Amount', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Total', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Tax Code', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipping Name', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipping Address 1', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipping Address 2', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipping City', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipping Province', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipping Postal', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Shipping Country', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Customer Notes', false);
		$sheet->setCellValueByColumnAndRow($i++, 1, 'Internal Notes', false);
		$sheet->getStyle('A1:I1')->getFont()->setBold(true);

		//
		// Output the invoice list
		//
		$row = 2;
		foreach($items as $iid => $item) {
			$item = $item['item'];
			$i = 0;
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['invoice_number'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['po_number'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['invoice_date'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['status_text'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['customer_eid'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['customer_display_name'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['reward_level'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['salesrep_display_name'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['code'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['description'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['notes'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['ordered_quantity'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['bo_quantity'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipped_quantity'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['pricepoint_code'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['unit_amount'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['total_amount'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['tax_location_code'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipping_name'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipping_address1'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipping_address2'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipping_city'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipping_province'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipping_postal'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['shipping_country'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['customer_notes'], false);
			$sheet->setCellValueByColumnAndRow($i++, $row, $item['internal_notes'], false);
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
		$sheet->getColumnDimension('I')->setAutoSize(true);
		$sheet->getColumnDimension('J')->setAutoSize(true);
		$sheet->getColumnDimension('K')->setAutoSize(true);
		$sheet->getColumnDimension('L')->setAutoSize(true);
		$sheet->getColumnDimension('M')->setAutoSize(true);
		$sheet->getColumnDimension('N')->setAutoSize(true);
		$sheet->getColumnDimension('O')->setAutoSize(true);
		$sheet->getColumnDimension('P')->setAutoSize(true);
		$sheet->getColumnDimension('Q')->setAutoSize(true);
		$sheet->getColumnDimension('R')->setAutoSize(true);
		$sheet->getColumnDimension('S')->setAutoSize(true);
		$sheet->getColumnDimension('T')->setAutoSize(true);
		$sheet->getColumnDimension('U')->setAutoSize(true);
		$sheet->getColumnDimension('V')->setAutoSize(true);
		$sheet->getColumnDimension('W')->setAutoSize(true);
		$sheet->getColumnDimension('X')->setAutoSize(true);
		$sheet->getColumnDimension('Y')->setAutoSize(true);

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
