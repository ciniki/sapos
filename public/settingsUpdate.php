<?php
//
// Description
// -----------
// This method will update one or more settings for the sapos module.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_sapos_settingsUpdate(&$ciniki) {
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.settingsUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Grab the settings for the business from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
	$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_sapos_settings', 'business_id', $args['business_id'], 'ciniki.sapos', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// The list of allowed fields for updating
	//
	$changelog_fields = array(
		'paypal-api-processing',
		'paypal-test-account',
		'paypal-test-endpoint',
		'paypal-test-clientid',
		'paypal-test-secret',
		'paypal-live-endpoint',
		'paypal-live-clientid',
		'paypal-live-secret',
		'invoice-header-image',
		'invoice-header-contact-position',
		'invoice-header-business-name',
		'invoice-header-business-address',
		'invoice-header-business-phone',
		'invoice-header-business-cell',
		'invoice-header-business-fax',
		'invoice-header-business-email',
		'invoice-header-business-website',
		'invoice-bottom-message',
		'invoice-footer-message',
		'invoice-email-message',
		'cart-email-message',
		'pos-email-message',
		'order-email-message',
		'shipments-default-shipper',
		'shipments-default-weight-units',
		'shipments-hide-weight-units',
		'ui-colours-invoice-item-available',
		'ui-colours-invoice-item-backordered',
		'ui-colours-invoice-item-fulfilled',
		'ui-options-print-picklist',
		'ui-options-print-invoice',
		'ui-options-print-envelope',
		'rules-invoice-duplicate-items',
		'rules-invoice-paid-change-items',
		'rules-invoice-submit-require-po_number',
		'rules-shipment-shipped-require-weight',
		'rules-shipment-shipped-require-tracking_number',
		'rules-shipment-shipped-require-boxes',
		'rules-salesreps-invoice-po_number',
		'rules-salesreps-invoice-pricepoint_id',
		'rules-salesreps-invoice-billing',
		'rules-salesreps-invoice-shipping',
		'rules-salesreps-invoice-notes',
		'quote-bottom-message',
		'quote-footer-message',
		'quote-email-message',
		);
	//
	// Check each valid setting and see if a new value was passed in the arguments for it.
	// Insert or update the entry in the ciniki_sapos_settings table
	//
	foreach($changelog_fields as $field) {
		if( isset($ciniki['request']['args'][$field]) 
			&& (!isset($settings[$field]) || $ciniki['request']['args'][$field] != $settings[$field]) ) {
			$strsql = "INSERT INTO ciniki_sapos_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $field) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.sapos');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', $args['business_id'], 
				2, 'ciniki_sapos_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
			$ciniki['syncqueue'][] = array('push'=>'ciniki.sapos.setting', 
				'args'=>array('id'=>$field));
		}
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'sapos');

	return array('stat'=>'ok');
}
?>
