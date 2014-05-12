<?php
//
// Description
// ===========
// This method will return the latest invoices and expenses updated.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_latest(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Type'), 
        'sort'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Sort Order'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'15', 'name'=>'Limit'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.latest'); 
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
	$php_date_format = ciniki_users_dateFormat($ciniki, 'php');
	$date_format = ciniki_users_dateFormat($ciniki, 'mysql');

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceMaps');
	$rc = ciniki_sapos_invoiceMaps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Build the query to get the list of expenses
	//
	if( isset($args['type']) && $args['type'] == 'expenses' ) {
		//
		// Get the number of categories so we can let the user know in the UI to
		// setup the categories
		//
		$num_cats = -1;
		$strsql = "SELECT 'categories', COUNT(*) "
			. "FROM ciniki_sapos_expense_categories "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'cats');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['cats']) ) {
			$num_cats = $rc['cats']['categories'];
		} else {
			$num_cats = 0;
		}
		//
		// Build the query to get the latest expenses
		//
		$strsql = "SELECT ciniki_sapos_expenses.id, "
			. "name, "
			. "IFNULL(DATE_FORMAT(ciniki_sapos_expenses.invoice_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS invoice_date, "
			. "total_amount "
			. "FROM ciniki_sapos_expenses "
			. "WHERE ciniki_sapos_expenses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		if( isset($args['sort']) ) {
			if( $args['sort'] == 'latest' ) {
				$strsql .= "ORDER BY ciniki_sapos_expenses.last_updated DESC ";
			}
		}
		if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
			$strsql .= "LIMIT " . intval($args['limit']) . " ";
		}
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
			array('container'=>'expenses', 'fname'=>'id', 'name'=>'expense',
				'fields'=>array('id', 'name', 'invoice_date', 'total_amount'),
				'maps'=>array('status_text'=>$maps['status']),
	//			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['expenses']) ) {
			$expenses = array();
			return array('stat'=>'ok', 'expenses'=>array(), 'numcats'=>$num_cats);
		} else {
			foreach($rc['expenses'] as $iid => $expense) {
				$rc['expenses'][$iid]['expense']['total_amount_display'] = numfmt_format_currency(
					$intl_currency_fmt, $expense['expense']['total_amount'], $intl_currency);
			}
			$expenses = $rc['expenses'];
		}

		return array('stat'=>'ok', 'expenses'=>$expenses);
	} 

	else if( isset($args['type']) && $args['type'] == 'mileage' ) {
		//
		// Get the number of mileage rates so we can let the user know in the UI to
		// setup the categories
		//
		$num_cats = -1;
		$strsql = "SELECT 'rates', COUNT(*) "
			. "FROM ciniki_sapos_mileage_rates "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'rates');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['rates']) ) {
			$num_rates = $rc['rates']['rates'];
		} else {
			$num_rates = 0;
		}

		//
		// Build the query to get the latest mileage entries
		//
		$strsql = "SELECT ciniki_sapos_mileage.id, "
			. "ciniki_sapos_mileage.travel_date, "
			. "ciniki_sapos_mileage.start_name, "
			. "ciniki_sapos_mileage.end_name, "
			. "ciniki_sapos_mileage.distance, "
			. "ciniki_sapos_mileage.flags, "
			. "ciniki_sapos_mileage_rates.rate "
			. "FROM ciniki_sapos_mileage "
			. "LEFT JOIN ciniki_sapos_mileage_rates ON ("
				. "ciniki_sapos_mileage.travel_date >= ciniki_sapos_mileage_rates.start_date "
				. "AND (ciniki_sapos_mileage_rates.end_date = '0000-00-00 00:00:00' "
					. "OR ciniki_sapos_mileage.travel_date < ciniki_sapos_mileage_rates.end_date "
					. ") "
				. "AND ciniki_sapos_mileage_rates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_sapos_mileage.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
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
				'fields'=>array('id', 'start_name', 'end_name', 'travel_date', 'distance', 'flags', 'rate'),
				'utctotz'=>array('travel_date'=>array('timezone'=>'UTC', 'format'=>$php_date_format)), 
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['mileages']) ) {
			$mileages = array();
			return array('stat'=>'ok', 'mileages'=>array(), 'numrates'=>$num_rates);
		} else {
			foreach($rc['mileages'] as $iid => $mileage) {
				// Check for round trip
				if( ($mileage['mileage']['flags']&0x01) > 0 ) {
					$total_distance = bcmul($mileage['mileage']['distance'], 2, 2);
				} else {
					$total_distance = $mileage['mileage']['distance'];
				}
				$rc['mileages'][$iid]['mileage']['distance'] = (float)$mileage['mileage']['distance'];
				$rc['mileages'][$iid]['mileage']['total_distance'] = (float)$total_distance;
				$rc['mileages'][$iid]['mileage']['amount'] = bcmul($total_distance, $mileage['mileage']['rate'], 2);
				$rc['mileages'][$iid]['mileage']['amount_display'] = numfmt_format_currency(
					$intl_currency_fmt, $rc['mileages'][$iid]['mileage']['amount'], $intl_currency);
				$rc['mileages'][$iid]['mileage']['units'] = $intl_distance_units;
			}
			$mileages = $rc['mileages'];
		}

		return array('stat'=>'ok', 'num_rates'=>$num_rates, 'mileages'=>$mileages);
	}

	//
	//  Get the latest invoices by type
	//
	$strsql = "SELECT ciniki_sapos_invoices.id, "
		. "ciniki_sapos_invoices.invoice_number, "
		. "ciniki_sapos_invoices.invoice_date, "
		. "ciniki_sapos_invoices.status, "
		. "CONCAT(ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
		. "ciniki_customers.type AS customer_type, "
		. "ciniki_customers.display_name AS customer_display_name, "
		. "total_amount "
		. "FROM ciniki_sapos_invoices "
		. "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	if( isset($args['type']) && $args['type'] > 0 ) {
		$strsql .= "AND ciniki_sapos_invoices.invoice_type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
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
			'maps'=>array('status_text'=>$maps['typestatus']),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$php_date_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['invoices']) ) {
		$invoices = array();
	} else {	
		foreach($rc['invoices'] as $iid => $invoice) {
			$rc['invoices'][$iid]['invoice']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
				$invoice['invoice']['total_amount'], $intl_currency);
		}
		$invoices = $rc['invoices'];
	}

	return array('stat'=>'ok', 'invoices'=>$invoices);
}
?>
