<?php
//
// Description
// ===========
// This method will remove a quick invoice item.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_qiItemDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.qiItemDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Remove the category
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.sapos.qi_item', 
		$args['item_id'], NULL, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
