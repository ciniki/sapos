<?php
//
// Description
// ===========
// This method returns the list of taxes both current and past for a business.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_taxList(&$ciniki) {
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.taxAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'timezoneOffset');
	$utc_offset = ciniki_businesses_timezoneOffset($ciniki);

	//
	// Get the list of future taxes
	//
	$strsql = "SELECT id, name, "
		. "item_percentage, item_amount, invoice_amount, "
		. "taxtypes, flags, "
		. "DATE_FORMAT(CONVERT_TZ(start_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(CONVERT_TZ(end_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
		. "FROM ciniki_sapos_taxes "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND start_date > UTC_TIMESTAMP() "
		. "ORDER BY start_date DESC, end_date DESC"
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'taxes', 'fname'=>'id', 'name'=>'tax',
			'fields'=>array('id', 'name', 'item_percentage', 'item_amount', 'invoice_amount',
				'taxtypes', 'flags', 'start_date', 'end_date'))
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['taxes']) ) {
		$future = $rc['taxes'];
	} else {
		$future = array();
	}

	//
	// Get the list of current taxes
	//
	$strsql = "SELECT id, name, "
		. "item_percentage, item_amount, invoice_amount, "
		. "taxtypes, flags, "
		. "DATE_FORMAT(CONVERT_TZ(start_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(CONVERT_TZ(end_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
		. "FROM ciniki_sapos_taxes "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND start_date < UTC_TIMESTAMP() "
		. "AND (end_date = '0000-00-00 00:00:00' OR end_date > UTC_TIMESTAMP()) "
		. "ORDER BY start_date DESC, end_date DESC"
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'taxes', 'fname'=>'id', 'name'=>'tax',
			'fields'=>array('id', 'name', 'item_percentage', 'item_amount', 'invoice_amount',
				'taxtypes', 'flags', 'start_date', 'end_date'))
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['taxes']) ) {
		$current = $rc['taxes'];
	} else {
		$current = array();
	}

	//
	// Get the list of past taxes
	//
	$strsql = "SELECT id, name, "
		. "item_percentage, item_amount, invoice_amount, "
		. "taxtypes, flags, "
		. "DATE_FORMAT(CONVERT_TZ(start_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(CONVERT_TZ(end_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
		. "FROM ciniki_sapos_taxes "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND end_date < UTC_TIMESTAMP() "
		. "ORDER BY start_date DESC, end_date DESC"
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'taxes', 'fname'=>'id', 'name'=>'tax',
			'fields'=>array('id', 'name', 'item_percentage', 'item_amount', 'invoice_amount',
				'taxtypes', 'flags', 'start_date', 'end_date'))
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['taxes']) ) {
		$past = $rc['taxes'];
	} else {
		$past = array();
	}

	return array('stat'=>'ok', 'future'=>$future, 'current'=>$current, 'past'=>$past);
}
?>
