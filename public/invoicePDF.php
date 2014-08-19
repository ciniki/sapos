<?php
//
// Description
// ===========
// This method will produce a PDF of the invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoicePDF(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
		'type'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'invoice', 'name'=>'Output Type'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoicePDF'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Load business details
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
	$rc = ciniki_businesses_businessDetails($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['details']) && is_array($rc['details']) ) {	
		$business_details = $rc['details'];
	} else {
		$business_details = array();
	}

	//
	// Load the invoice settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'business_id', $args['business_id'],
		'ciniki.sapos', 'settings', 'invoice');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['settings']) ) {
		$sapos_settings = $rc['settings'];
	} else {
		$sapos_settings = array();
	}
	
	//
	// check for invoice-default-template
	//
	if( isset($args['type']) && $args['type'] == 'picklist' ) {
		$invoice_template = 'picklist';
	} else {
		if( !isset($sapos_settings['invoice-default-template']) 
			|| $sapos_settings['invoice-default-template'] == '' ) {
			$invoice_template = 'default';
		} else {
			$invoice_template = $sapos_settings['invoice-default-template'];
		}
	}
	
	$rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'templates', $invoice_template);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$fn = $rc['function_call'];

	return $fn($ciniki, $args['business_id'], $args['invoice_id'],
		$business_details, $sapos_settings);
}
?>
