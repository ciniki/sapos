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
function ciniki_sapos_expenseList(&$ciniki) {
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseList'); 
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
	// Build the query to get the list of expenses
	//
	$strsql = "SELECT ciniki_sapos_expenses.id, "
		. "name, "
		. "invoice_date, "
		. "paid_date, "
		. "total_amount "
		. "FROM ciniki_sapos_expenses "
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
			'fields'=>array('id', 'name', 'invoice_date', 'paid_date', 'total_amount'),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['expenses']) ) {
		return array('stat'=>'ok', 'expenses'=>array());
	}
	$totals = array(
		'total_amount'=>0,
		);
	foreach($rc['expenses'] as $iid => $expense) {
		$totals['total_amount'] = bcadd($totals['total_amount'], $expense['expense']['total_amount']);
	}

	$totals['total_amount'] = numfmt_format_currency($intl_currency_fmt,
		$totals['total_amount'], $intl_currency);
	$totals['num_expenses'] = count($rc['expenses']);

	return array('stat'=>'ok', 'totals'=>$totals, 'expenses'=>$rc['expenses']);
}
?>
