<?php
//
// Description
// -----------
// This function will update the status of an invoice based on the payments
// made.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_invoiceUpdateStatus($ciniki, $business_id, $invoice_id) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	//
	// Get the invoice details
	//
	$strsql = "SELECT status, "
		. "ROUND(total_amount, 2) AS total_amount "
		. "FROM ciniki_sapos_invoices "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( !isset($rc['invoice']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1397', 'msg'=>'Unable to locate the invoice'));
	}
	$invoice = $rc['invoice'];

	//
	// Get the invoice transactions
	//
	$strsql = "SELECT id, transaction_type, "
		. "ROUND(customer_amount, 2) AS customer_amount, "
		. "ROUND(transaction_fees, 2) AS transaction_fees, "
		. "ROUND(business_amount, 2) AS business_amount "
		. "FROM ciniki_sapos_transactions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   
	if( isset($rc['rows']) ) {
		$transactions = $rc['rows'];
		$amount_paid = 0;
		foreach($transactions as $rid => $ta) {
			if( $ta['transaction_type'] == 10 || $ta['transaction_type'] == 20 ) {
				$amount_paid = bcadd($amount_paid, $ta['customer_amount'], 2);
			} elseif( $ta['transaction_type'] == 60 ) {
				$amount_paid = bcsub($amount_paid, $ta['customer_amount'], 2);
			}
		}
	} else {
		$transactions = array();
	}

	//
	// Check if invoice should be updated status
	//
	$new_status = 0;
	if( $invoice['status'] < 40 ) {
		if( $amount_paid > 0 && $amount_paid < $invoice['total_amount'] ) {
			$new_status = 40;
		} elseif( $amount_paid >= $invoice['total_amount'] ) {
			$new_status = 50;
		}
	}
	elseif( $invoice['status'] == 40 ) {
		if( $amount_paid >= $invoice['total_amount'] ) {
			$new_status = 50;
		}
	}
	elseif( $invoice['status'] == 50 ) {
		if( $amount_paid > 0 && $amount_paid < $invoice['total_amount']) {
			$new_status = 40;
		}
		if( $amount_paid == 0 ) {
			$new_status = 55;
		}
	}
	elseif( $invoice['status'] == 55 ) {
		if( $amount_paid > 0 && $amount_paid < $invoice['total_amount']) {
			$new_status = 40;
		}
		if( $amount_paid >= $invoice['total_amount'] ) {
			$new_status = 50;
		}
	}
	// If status is currently 60 (Void) then don't change.

	//
	// If the status should be changed, update
	//
	if( $new_status > 0 ) {
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice', 
			$invoice_id, array('status'=>$new_status), 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	return array('stat'=>'ok');
}
?>
