<?php
//
// Description
// ===========
// This method will return the details about a expense category.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseCategoryGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.expenseCategoryGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//
	// Load the business intl settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	//
	// Get the category details
	//
	$strsql = "SELECT id, "
		. "name, "
		. "sequence, "
		. "flags, "
		. "taxrate_id, "
		. "start_date, "
		. "end_date "
		. "FROM ciniki_sapos_expense_categories "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
			'fields'=>array('id', 'name', 'sequence', 'flags', 'taxrate_id', 'start_date', 'end_date'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				'end_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
		)));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['categories'][0]['category']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1423', 'msg'=>'Unable to find expense category.'));
	}
	$category = $rc['categories'][0]['category'];

	return array('stat'=>'ok', 'category'=>$category);
}
?>
