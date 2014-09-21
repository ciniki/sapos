<?php
//
// Description
// ===========
// This method will add a new shipment to the system for an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_shipmentUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'shipment_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shipment'), 
        'shipment_number'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Shipment Number'), 
		'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
		'weight'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Weight'),
		'weight_units'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Weight Units'),
		'shipping_company'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping Company'),
		'tracking_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tracking Number'),
		'td_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'TD Number'),
		'boxes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Boxes'),
		'pack_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Date Packed'),
		'ship_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Date Shipped'),
		'freight_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Freight Amount'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.shipmentUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Load the settings
	//
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 
		'business_id', $args['business_id'], 'ciniki.sapos', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = isset($rc['settings'])?$rc['settings']:array();

	//
	// Get the invoice of the shipment
	//
	$strsql = "SELECT invoice_id, ship_date, status, boxes, tracking_number, weight "
		. "FROM ciniki_sapos_shipments "
		. "WHERE ciniki_sapos_shipments.id = '" . ciniki_core_dbQuote($ciniki, $args['shipment_id']) . "' "
		. "AND ciniki_sapos_shipments.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipment');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	if( !isset($rc['shipment']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2041', 'msg'=>'Shipment does not exist'));
	}
	$shipment = $rc['shipment'];

	//
	// Reject if shipment is already shipped
	//
	if( $shipment['status'] > 20 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2036', 'msg'=>'Shipment has already been shipped.'));
	}

	//
	// If the status is being changed to shipped, check for rules
	//
	if( $shipment['status'] < 30 
		&& isset($args['status']) && $args['status'] >= 30 
		) {
		// Make sure weight is specified if required
		if( isset($settings['rules-shipment-shipped-require-weight'])
			&& $settings['rules-shipment-shipped-require-weight'] == 'yes'
			&& $shipment['weight'] == 0
			&& (!isset($args['weight']) || $args['weight'] == '' || $args['weight'] <= 0)
			) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2043', 'msg'=>'Shipping weight must be specified.'));
		}
		// Make sure tracking_number is specified if required
		if( isset($settings['rules-shipment-shipped-require-tracking_number'])
			&& $settings['rules-shipment-shipped-require-tracking_number'] == 'yes'
			&& ($shipment['tracking_number'] == '' || $shipment['tracking_number'] == '0')
			&& (!isset($args['tracking_number']) || $args['tracking_number'] == '' || $args['tracking_number'] <= 0)
			) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2048', 'msg'=>'Tracking number must be specified.'));
		}
		// Make sure boxes is specified if required
		if( isset($settings['rules-shipment-shipped-require-boxes'])
			&& $settings['rules-shipment-shipped-require-boxes'] == 'yes'
			&& $shipment['boxes'] == 0
			&& (!isset($args['boxes']) || $args['boxes'] == '' || $args['boxes'] <= 0)
			) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2044', 'msg'=>'The number of boxes in a shipment must be specified.'));
		}
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Check if ship_date should be set
	//
	if( $shipment['ship_date'] == '0000-00-00 00:00:00' && isset($args['status']) && $args['status'] == '30' ) {
		$date = new DateTime('now', new DateTimeZone('UTC'));
		$args['ship_date'] = $date->format('Y-m-d H:i:s');
	}

	//
	// Update the shipment
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.shipment', $args['shipment_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the shipment
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shipmentUpdateStatus');
	$rc = ciniki_sapos_shipmentUpdateStatus($ciniki, $args['business_id'], $args['shipment_id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the invoice
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $shipment['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Commit the transaction
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
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
