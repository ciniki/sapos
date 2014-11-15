<?php
//
// Description
// ===========
// This method will lookup the item details for a new invoice
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceItemsLookup(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'items'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'objectlist', 'name'=>'Event'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceItemsLookup'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	
	$items = array();
	foreach($args['items'] as $object => $object_id) {
		list($pkg, $mod, $obj) = explode('.', $object);
		$lookup_function = "{$pkg}_{$mod}_sapos_{$obj}Details";
		// Check if function is already loaded
		if( !is_callable($lookup_function) ) {
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', "{$obj}Details");
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1253', 'msg'=>'Unable to load invoice item details'));
			}
		}
		
		// If still not callable, was not able to load and should fail
		if( !is_callable($lookup_function) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1152', 'msg'=>'Unable to load invoice item details'));
		}

		$rc = $lookup_function($ciniki, $args['business_id'], $object_id);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1122', 'msg'=>'Unable to load invoice item details', 'err'=>$rc['err']));
		}
		if( !isset($rc['details']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1117', 'msg'=>'Unable to load invoice item details'));
		}
		$items[] = array('item'=>$rc['details']);
	}
	
	return array('stat'=>'ok', 'items'=>$items);
}
?>
