<?php
//
// Description
// ===========
// This function will lookup the object details from other modules to be used on an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_lookupObjects(&$ciniki, $business_id, $objects) {
	$items = array();
	foreach($objects as $object => $object_id) {
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

		$rc = $lookup_function($ciniki, $business_id, $object_id);
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

