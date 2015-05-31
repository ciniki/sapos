<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_hooks_checkObjectUsed($ciniki, $business_id, $args) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

	// Set the default to not used
	$used = 'no';
	$count = 0;
	$msg = '';


	if( $args['object'] == 'ciniki.taxes.type' ) {
		//
		// Check the invoice items
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_invoice_items "
			. "WHERE taxtype_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg = "There " . ($count==1?'is':'are') . " $count invoice line item" . ($count==1?'':'s') . " still using this tax type.";
		}
	
		//
		// Check the quick invoice items
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_qi_items "
			. "WHERE taxtype_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count quick invoice item" . ($count==1?'':'s') . " still using this tax type.";
		}
	}

	elseif( $args['object'] == 'ciniki.taxes.rate' ) {
		//
		// Check the expense categories
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_expense_categories "
			. "WHERE taxrate_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg = "There are $count expense categories still using this tax rate.";
			$msg = "There " . ($count==1?'is':'are') . " $count expense categor" . ($count==1?'y':'ies') . " still using this tax rate.";
		}

		//
		// Check the invoice taxes
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_invoice_taxes "
			. "WHERE taxrate_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count invoice taxes item" . ($count==1?'':'s') . " still using this tax rate.";
		}
	}

	elseif( $args['object'] == 'ciniki.customers.customer' ) {
		//
		// Check the invoice customers
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_invoices "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count invoice" . ($count==1?'':'s') . " for this customer.";
		}
	}

	elseif( $args['object'] == 'ciniki.artcatalog.item' ) {
		//
		// Check for artcatalog products on an invoice
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_invoice_items "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
			. "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg = "There " . ($count==1?'is':'are') . " $count invoice" . ($count==1?'':'s') . " still using this item.";
		}
	}

	elseif( $args['object'] == 'ciniki.artcatalog.product' ) {
		//
		// Check for artcatalog products on an invoice
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_invoice_items "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
			. "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg = "There " . ($count==1?'is':'are') . " $count invoice item" . ($count==1?'':'s') . " still using this product.";
		}
	}

	return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
