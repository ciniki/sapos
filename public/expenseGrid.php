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
// <rsp stat='ok' id='34' />
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
	$date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Build the start and end dates
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
		. "ciniki_sapos_expenses.invoice_date, "
		. "ciniki_sapos_expenses.paid_date, "
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
		$strsql .= "AND ciniki_sapos_expenses.invoice_date >= '" . $start_date->format('Y-m-d H:i:s') . "' ";
		$strsql .= "AND ciniki_sapos_expenses.invoice_date < '" . $end_date->format('Y-m-d H:i:s') . "' ";
	}

	//
	// Order the expenses
	//
	$strsql .= "ORDER BY ciniki_sapos_expenses.invoice_date ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'expenses', 'fname'=>'id', 'name'=>'expense',
			'fields'=>array('id', 'name', 'invoice_date', 'paid_date', 'total_amount'),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			),
		array('container'=>'items', 'fname'=>'item_id', 'name'=>'item',
			'fields'=>array('id'=>'item_id', 'category_id', 'amount'=>'item_amount')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['expenses']) ) {
		return array('stat'=>'ok', 'categories'=>$categories, 'expenses'=>array(), 'totals'=>array());
	}
	$expenses = $rc['expenses'];
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

	return array('stat'=>'ok', 'categories'=>$categories, 'expenses'=>$expenses, 'totals'=>$totals);
}
?>
