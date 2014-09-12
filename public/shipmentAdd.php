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
function ciniki_sapos_shipmentAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        'shipment_number'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipment Number'), 
		'status'=>array('required'=>'no', 'blank'=>'no', 'default'=>'10', 'name'=>'Status'),
		'weight'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Weight'),
		'weight_units'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'10', 'name'=>'Weight Units'),
		'shipping_company'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Company'),
		'tracking_number'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Tracking Number'),
		'td_number'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'TD Number'),
		'boxes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Boxes'),
		'pack_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'now', 'type'=>'datetimetoutc', 'name'=>'Date Packed'),
		'ship_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetimetoutc', 'name'=>'Date Shipped'),
		'freight_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'default'=>'0', 'name'=>'Freight Amount'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.shipmentAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// If the shipment number is blank, or zero, check for latest shipment number for invoice
	//
	if( !isset($args['shipment_number']) || $args['shipment_number'] == '' || $args['shipment_number'] == '0' ) {
		$strsql = "SELECT MAX(shipment_number) AS max_num "
			. "FROM ciniki_sapos_shipments "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['item']) ) {
			$args['shipment_number'] = $rc['item']['max_num'] + 1;
		} else {
			$args['shipment_number'] = '1';
		}
	}

	//
	// Create the shipment
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.shipment', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$shipment_id = $rc['id'];

	//
	// Return the shipment record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shipmentLoad');
	$rc = ciniki_sapos_shipmentLoad($ciniki, $args['business_id'], $shipment_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'shipment'=>$rc['shipment']);
}
?>
