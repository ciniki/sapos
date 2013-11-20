<?php
//
// Description
// ===========
// This method will add a new item to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceTransactionAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'),
		'transaction_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
		'transaction_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'datetime', 'name'=>'Date'),
		'source'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'source',
			'validlist'=>array('10','20','30','90','100','105','110','120')),
		'customer_amount'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer Amount'),
		'transaction_fees'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Fees'),
		'business_amount'=>array('required'=>'no', 'blank'=>'no', 'default'=>'', 'name'=>'Business Amount'),
		'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.taxAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Check if business amount not specified, then set the same as customer_amount
	//
	if( !isset($args['business_amount']) || $args['business_amount'] == '' ) {
		$args['business_amount'] = $args['customer_amount'] - $args['transaction_fees'];
	}

	//
	// Get the invoice details
	//
	$strsql = "SELECT status, total_amount "
		. "FROM ciniki_sapos_invoices "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( !isset($rc['invoice']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1382', 'msg'=>'Unable to locate the invoice'));
	}
	$invoice = $rc['invoice'];

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.invoice_transaction', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$transaction_id = $rc['id'];

	//
	// Get the invoice transactions
	//
	$strsql = "SELECT id, transaction_type, customer_amount, transaction_fees, business_amount "
		. "FROM ciniki_sapos_invoice_transactions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( isset($rc['rows']) ) {
		$transactions = $rc['rows'];
		$amount_paid = 0;
		foreach($transaction as $rid => $transaction) {
			if( $transaction['transaction_type'] == 10 || $transaction['transaction_type'] == 20 ) {
				$amount_paid += $transaction['customer_amount'];
			} elseif( $transaction['transaction_type'] == 60 ) {
				$amount_paid -= $transaction['customer_amount'];
			}
		}
	} else {
		$transactions = array();
	}
	$invoice = $rc['invoice'];

	//
	// Check if invoice should be updated status
	//
	$new_status = 0;
	if( $args['transaction_type'] == 10 || $args['transaction_type'] == 20 ) {
		if( $invoice['status'] < 40 ) {
			if( $amount_paid < $invoice['total_amount'] ) {
				$new_status = 40;
			} elseif( $amount_paid >= $invoice['total_amount'] ) {
				$new_status = 50;
			} else {
				continue;
			}
		}
	}
	if( $invoice['status'] == 60 ) {
		// If the enter amount has been refunded, then set status to refunded.
		if( $invoice['status'] >= 40 && $invoice['status'] < 60 && $amount_paid == 0 ) {
			$new_status = 55;
		} 
		// If the amount was only partially refunded, then set the status to partial payment	
		elseif( $invoice['status'] == 50 && $invoice['status'] > 0 ) {
			$new_status = 40;
		}
	}

	//
	// If the status should be changed, update
	//
	if( $new_status > 0 ) {
		$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice', $args['invoice_id'], 
			array('status'=>$new_status), 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}
	}


	//
	// FIXME: Check if callback hooks to item modules
	//


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

	return array('stat'=>'ok', 'id'=>$invoice_id);
}
?>
