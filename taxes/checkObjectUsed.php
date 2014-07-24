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
function ciniki_sapos_taxes_checkObjectUsed($ciniki, $modules, $business_id, $object, $object_id) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

	// Set the default to not used
	$used = 'no';
	$count = 0;
	$msg = '';


	if( $object == 'ciniki.taxes.type' ) {
		//
		// Check the invoice items
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_invoice_items "
			. "WHERE taxtype_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
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
			. "WHERE taxtype_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
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

	elseif( $object == 'ciniki.taxes.rate' ) {
		//
		// Check the expense categories
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_sapos_expense_categories "
			. "WHERE taxrate_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
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
			. "WHERE taxrate_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
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

	return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
