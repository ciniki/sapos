<?php
//
// Description
// ===========
// This method will return the details about a quick invoice item.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_qiItemGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.qiItemGet'); 
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
	// Get the item details
	//
	$strsql = "SELECT id, "
		. "name, "
		. "description, "
		. "quantity, "
		. "unit_amount, "
		. "unit_discount_amount, "
		. "unit_discount_percentage, "
		. "taxtype_id "
		. "FROM ciniki_sapos_qi_items "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'name', 'description', 'quantity', 
				'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
				'taxtype_id'),
		)));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['items'][0]['item']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1423', 'msg'=>'Unable to find quick invoice item.'));
	}
	$item = $rc['items'][0]['item'];

	//
	// Format currencies
	//
	$item['unit_amount'] = numfmt_format_currency($intl_currency_fmt, 
		$item['unit_amount'], $intl_currency);
	$item['unit_discount_amount'] = numfmt_format_currency($intl_currency_fmt, 
		$item['unit_discount_amount'], $intl_currency);

	return array('stat'=>'ok', 'item'=>$item);
}
?>
