<?php
//
// Description
// ===========
// This method will return the detail for a tax for a business.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_taxGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'tax_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tax'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.taxGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'timezoneOffset');
	$utc_offset = ciniki_businesses_timezoneOffset($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the tax details
	//
	$strsql = "SELECT name, "
		. "item_percentage, "
		. "item_amount, "
		. "invoice_amount, "
		. "taxtypes, "
		. "flags, "
		. "DATE_FORMAT(CONVERT_TZ(start_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(CONVERT_TZ(end_date, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), "
			. "'" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
		. "FROM ciniki_sapos_taxes "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['tax_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'tax');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['tax']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Unable to find tax'));
	}

	return array('stat'=>'ok', 'tax'=>$rc['tax']);
}
?>
