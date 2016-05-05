<?php
//
// Description
// ===========
// This method will add a new invoice to the system, creating item entries if specified.  If
// a customer is specified, the billing/shipping address will be pulled from the customer record.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceObjectsAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Invoice'), 
        'bill_parent'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Bill Parent'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Customer'), 
		'objects'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'objectlist', 'name'=>'Items'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

	//
	// If a customer is specified, then lookup the customer details and fill out the invoice
	// based on the customer.  
	//
	if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // If requested, find the parent if any
        //
        if( isset($args['bill_parent']) && $args['bill_parent'] == 'yes' ) {
            $strsql = "SELECT parent_id "
                . "FROM ciniki_customers "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']['parent_id']) && $rc['customer']['parent_id'] > 0 ) {
                $args['student_id'] = $args['customer_id'];
                $args['customer_id'] = $rc['customer']['parent_id'];
            }
        }

		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getCustomer');
		$rc = ciniki_sapos_getCustomer($ciniki, $args['business_id'], $args);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$args = $rc['args'];
	}

    //
    // Get the customer_id of the invoice
    //
    $strsql = "SELECT customer_id "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['invoice']['customer_id']) && (!isset($args['customer_id']) || $rc['invoice']['customer_id'] != $args['customer_id']) ) {
        // $args['customer_id'] = $rc['invoice']['customer_id'];
    }

	//
	// Get the object details and turn them into item details for the invoice
	//
	$invoice_items = array();
	if( isset($args['objects']) && is_array($args['objects']) && count($args['objects']) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'lookupObjects');
		$rc = ciniki_sapos_lookupObjects($ciniki, $args['business_id'], $args['objects']);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3314', 'msg'=>'Unable to lookup invoice item reference', 'err'=>$rc['err']));
		}
		if( isset($rc['items']) ) {
			$invoice_items = $rc['items'];
		} else {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3315', 'msg'=>'Unable to find specified items.'));
		}
	}

	if( isset($args['items']) && is_array($args['items']) && count($args['items']) > 0 ) {
		foreach($args['items'] as $item) {
			array_push($invoice_items, $item);
		}
	}

	//
	// Start the transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Set the defaults for the invoice
	//
	$args['subtotal_amount'] = 0;
	$args['subtotal_discount_amount'] = 0;
	$args['subtotal_discount_percentage'] = 0;
	$args['discount_amount'] = 0;
	$args['shipping_amount'] = 0;
	$args['total_amount'] = 0;
	$args['total_savings'] = 0;
	$args['paid_amount'] = 0;
	$args['balance_amount'] = 0;

    //
    // Get the max line number 
    //
    $strsql = "SELECT MAX(line_number) AS line_number "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'max');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['max']['line_number']) ) {
        $line_number = $rc['max']['line_number']++;
    } else {
        $line_number = 1;
    }

	//
	// Add the items to the invoice
	//
	foreach($invoice_items as $i => $item) {
		$item['invoice_id'] = $args['invoice_id'];
		$item['line_number'] = $line_number++;
		if( !isset($item['amount']) ) {
			//
			// Calculate the final amount for each item in the invoice
			//
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
			$rc = ciniki_sapos_itemCalcAmount($ciniki, $item);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$item['subtotal_amount'] = $rc['subtotal'];
			$item['discount_amount'] = $rc['discount'];
			$item['total_amount'] = $rc['total'];
		}
		$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', $item, 0x04);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
			return $rc;
		}
		$item_id = $rc['id'];

        // If there is a student ID to pass into itemAdd
        if( isset($args['student_id']) && $args['student_id'] != '' && $args['student_id'] > 0 ) {
            $item['student_id'] = $args['student_id'];
        }

		//
		// Check if there's a callback for the object
		//
		if( $item['object'] != '' && $item['object_id'] != '' ) {
			list($pkg,$mod,$obj) = explode('.', $item['object']);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemAdd');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], $args['invoice_id'], $item);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				// Update the invoice item with the new object and object_id
				if( isset($rc['object']) && $rc['object'] != $item['object'] ) {
					$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.invoice_item', 
						$item_id, $rc, 0x04);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
				}
			}
		}
	}

	//
	// Update the shipping costs, taxes, and total
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
	$rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	//
	// Update the invoice status and balance 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
	$rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['business_id'], $args['invoice_id']);
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

	//
	// Return the invoice record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $args['invoice_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'invoice'=>$rc['invoice']);
}
?>
