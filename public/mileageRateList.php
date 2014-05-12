<?php
//
// Description
// ===========
// This method will return the list of rates for a business.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_mileageRateList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.mileageRateList'); 
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
	// Get the list of rates
	//
	$strsql = "SELECT id, rate, "
		. "start_date, "
		. "end_date "
		. "FROM ciniki_sapos_mileage_rates "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY start_date DESC "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'rates', 'fname'=>'id', 'name'=>'rate',
			'fields'=>array('id', 'rate', 'start_date', 'end_date'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'end_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				)), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['rates']) ) {
		return array('stat'=>'ok', 'rates'=>array());
	}

	$rates = $rc['rates'];
	foreach($rates as $rid => $rate) {
		$rates[$rid]['rate']['rate_display'] = numfmt_format_currency($intl_currency_fmt,
			$rate['rate']['rate'], $intl_currency);
	}

	return array('stat'=>'ok', 'rates'=>$rates);
}
?>
