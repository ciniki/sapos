<?php
//
// Description
// ===========
// This method will search the existing items, and will search other
// modules for items that can be linked.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceItemSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceItemSearch'); 
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
	// Setup the array for the items
	//
	$items = array();

	//
	// Check for modules which have searchable items
	//
	foreach($modules as $module => $m) {
		list($pkg, $mod) = explode('.', $module);
		$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemSearch');
		if( $rc['stat'] != 'ok' ) {
			continue;
		}
		$search_function = $pkg . '_' . $mod . '_sapos_itemSearch';
		if( !is_callable($search_function) ) {
			continue;
		}
		$rc = $search_function($ciniki, $args['business_id'], $args['start_needle'], $args['limit']);
		if( $rc['stat'] != 'ok' ) {
			continue;
		}
		if( isset($rc['items']) ) {
			$items = array_merge($items, $rc['items']);
		}
	}

	//
	// Check existing items in invoices
	//
	$strsql = "SELECT DISTINCT "	
		. "ciniki_sapos_invoice_items.object, "
		. "ciniki_sapos_invoice_items.object_id, "
		. "ciniki_sapos_invoice_items.description, "
		. "ciniki_sapos_invoice_items.quantity, "
		. "ciniki_sapos_invoice_items.unit_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_amount, "
		. "ciniki_sapos_invoice_items.unit_discount_percentage, "
		. "ciniki_sapos_invoice_items.taxtype_id "
		. "FROM ciniki_sapos_invoice_items "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND (description LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR description LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "";
	if( isset($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 15 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'items', 'fname'=>'description', 'name'=>'item',
			'fields'=>array('object', 'object_id', 'description', 'quantity',
				'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id')),
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( isset($rc['items']) ) {
		$items = array_merge($rc['items'], $items);
	}

	foreach($items as $i => $item) {
		$items[$i]['item']['unit_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
			$item['item']['unit_amount'], $intl_currency);
		$items[$i]['item']['quantity'] = (float)$item['item']['quantity'];
	}

	return array('stat'=>'ok', 'items'=>$items);
}
?>
