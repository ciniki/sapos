<?php
//
// Description
// ===========
// This method will return the detail for a transaction for an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceTransactionGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'transaction_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Transaction'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceTransactionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	//
	// Get business/user settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	//
	// Get the transaction details
	//
	$strsql = "SELECT id, "
		. "transaction_type, "
		. "transaction_date, "
		. "source, "
		. "ROUND(customer_amount, 2) AS customer_amount, "
		. "ROUND(transaction_fees, 2) AS transaction_fees, "
		. "ROUND(business_amount, 2) AS business_amount, "
		. "notes "
		. "FROM ciniki_sapos_invoice_transactions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['transaction_id']) . "' "
		. "ORDER BY transaction_date ASC "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
		array('container'=>'transactions', 'fname'=>'id', 'name'=>'transaction',
			'fields'=>array('id', 'transaction_type', 'transaction_date', 'source', 
				'customer_amount', 'transaction_fees', 'business_amount'),
			'utctotz'=>array('transaction_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)), 
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['transactions']) || !isset($rc['transactions'][0]['transaction']) ) {
		return array('stat'=>'ok', 'invoices'=>array());
	}
	$transaction = $rc['transactions'][0]['transaction'];

	return array('stat'=>'ok', 'transaction'=>$transaction);
}
?>
