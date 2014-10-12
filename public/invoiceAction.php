<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceAction(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
		'action'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Action',
			'validlist'=>array('submit')),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceAction'); 
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
	// Check the discount
	//
	if( !isset($args['unit_discount_percentage']) || $args['unit_discount_percentage'] == '' ) {
		$args['unit_discount_percentage'] = 0;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	if( isset($args['action']) && $args['action'] == 'submit' ) {
		$strsql = "SELECT po_number, invoice_type, status, shipping_status, submitted_by "
			. "FROM ciniki_sapos_invoices "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		// Check if only a sales rep
		if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
			$strsql .= "AND ciniki_sapos_invoices.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
		}
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2033', 'msg'=>'Unable to find invoice'));
		}
		$invoice = $rc['invoice'];
		//
		// Only allow orders to be submitted if still in incomplete status
		//
		if( ($invoice['invoice_type'] == 40 || $invoice['invoice_type'] == 20) && $invoice['status'] == 10 ) {
			if( isset($settings['rules-invoice-submit-require-po_number']) 
				&& $settings['rules-invoice-submit-require-po_number'] == 'yes' 
				&& (!isset($invoice['po_number']) || $invoice['po_number'] == '') 
				) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2042', 'msg'=>'The order must have a PO Number before it can be submitted.'));
			}
			$args['status'] = 30;
			$args['submitted_by'] = $ciniki['session']['user']['display_name'];
		}
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2034', 'msg'=>'No action specified'));
	}

	//
	// Start the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Update the invoice
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice', 
		$args['invoice_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Return the invoice record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$invoice = $rc['invoice'];

	//
	// Check for callbacks
	//
	if( isset($invoice['items']) ) {
		foreach($invoice['items'] as $iid => $item) {
			$item = $item['item'];
			if( $item['object'] != '' && $item['object_id'] != '' ) {
				list($pkg,$mod,$obj) = explode('.', $item['object']);
				$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'invoiceUpdate');
				if( $rc['stat'] == 'ok' ) {
					$fn = $rc['function_call'];
					$rc = $fn($ciniki, $args['business_id'], $invoice['id'], $item);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}
	}

	//
	// Update the taxes/shipping incase something relavent changed
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
	$rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Reload the invoice record incase anything has changed
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$invoice = $rc['invoice'];

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

	return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
