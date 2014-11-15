<?php
//
// Description
// ===========
// This method will return the list of quick invoice items setup in the system.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_qiItemList(&$ciniki) {
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.qiItemList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//
	// Load business INTL settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	//
	// Check existing items in invoices
	//
	$strsql = "SELECT "	
		. "ciniki_sapos_qi_items.id, "
		. "ciniki_sapos_qi_items.name, "
		. "ciniki_sapos_qi_items.unit_amount, "
		. "ciniki_sapos_qi_items.taxtype_id "
		. "FROM ciniki_sapos_qi_items "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'name', 'unit_amount', 'taxtype_id')),
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( !isset($rc['items']) ) {
		return array('stat'=>'ok', 'items'=>array());
	}
	$items = $rc['items'];
	foreach($items as $iid => $item) {
		$items[$iid]['item']['unit_amount'] = numfmt_format_currency($intl_currency_fmt, 
			$item['item']['unit_amount'], $intl_currency);
	}

	return array('stat'=>'ok', 'items'=>$items);
}
?>
