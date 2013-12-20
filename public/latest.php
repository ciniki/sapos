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
		. "CONCAT_WS(' ', ciniki_customers.prefix, ciniki_customers.first, ciniki_customers.middle, ciniki_customers.last, ciniki_customers.suffix) AS customer_name, "
		. "total_amount "
		. "FROM ciniki_sapos_invoices "
		. "LEFT JOIN ciniki_customers ON (ciniki_sapos_invoices.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
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
				'customer_name', 'total_amount'),
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
		foreach($rc['invoices'] as $iid => $invoice) {
			$rc['invoices'][$iid]['invoice']['customer_name'] = ltrim(rtrim(preg_replace('/  /', ' ', $invoice['invoice']['customer_name']), ' '), ' ');
			$rc['invoices'][$iid]['invoice']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
				$invoice['invoice']['total_amount'], $intl_currency);
		}
		$invoices = $rc['invoices'];
	}

	//
	// Build the query to get the latest expenses
	//
	$strsql = "SELECT ciniki_sapos_expenses.id, "
		. "name, "
		. "invoice_date, "
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
			'maps'=>array('status_text'=>$status_maps),
			'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['expenses']) ) {
		$expenses = array();
	} else {
		foreach($rc['expenses'] as $iid => $expense) {
			$rc['expenses'][$iid]['expense']['total_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $expense['expense']['total_amount'], $intl_currency);
		}
		$expenses = $rc['expenses'];
	}

	return array('stat'=>'ok', 'invoices'=>$invoices, 'expenses'=>$expenses);
}
?>
