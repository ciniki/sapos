<?php
//
// Description
// ===========
// This function will update the sequences for expense categories.
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_sapos_expenseUpdateCategorySequences($ciniki, $business_id, $category_id, $new_seq, $old_seq) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

	//
	// Get the sequences
	//
	$strsql = "SELECT id, sequence AS number "
		. "FROM ciniki_sapos_expense_categories "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	// Use the last_updated to determine which is in the proper position for duplicate numbers
	if( $new_seq < $old_seq || $old_seq == -1) {
		$strsql .= "ORDER BY sequence, last_updated DESC";
	} else {
		$strsql .= "ORDER BY sequence, last_updated ";
	}
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'sequence');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$cur_number = 1;
	if( isset($rc['rows']) ) {
		$categories = $rc['rows'];
		foreach($categories as $cid => $category) {
			//
			// If the number is not where it's suppose to be, change
			//
			if( $cur_number != $category['number'] ) {
				$strsql = "UPDATE ciniki_sapos_expense_categories SET "
					. "sequence = '" . ciniki_core_dbQuote($ciniki, $cur_number) . "' "
					. ", last_updated = UTC_TIMESTAMP() "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
					. "AND id = '" . ciniki_core_dbQuote($ciniki, $category['id']) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.sapos');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
				}
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sapos', 
					'ciniki_sapos_history', $business_id, 
					2, 'ciniki_sapos_expense_categories', $category['id'], 'sequence', $cur_number);
				$ciniki['syncqueue'][] = array('push'=>'ciniki.sapos.expense_category', 
					'args'=>array('id'=>$category['id']));
				
			}
			$cur_number++;
		}
	}
	
	return array('stat'=>'ok');
}
?>
