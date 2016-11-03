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
    foreach($objects as $o => $object) {
        list($pkg, $mod, $obj) = explode('.', $object['object']);
        $lookup_function = "{$pkg}_{$mod}_sapos_itemLookup";
        // Check if function is already loaded
        if( !is_callable($lookup_function) ) {
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', "itemLookup");
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.31', 'msg'=>'Unable to load invoice item details'));
            }
        }
        
        // If still not callable, was not able to load and should fail
        if( !is_callable($lookup_function) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.32', 'msg'=>'Unable to load invoice item details'));
        }

        $rc = $lookup_function($ciniki, $business_id, array('object'=>$object['object'], 'object_id'=>$object['id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.33', 'msg'=>'Unable to load invoice item details', 'err'=>$rc['err']));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.34', 'msg'=>'Unable to load invoice item details'));
        }
        // $items[] = array('item'=>$rc['details']);
        $items[] = $rc['item'];
    }
    
    return array('stat'=>'ok', 'items'=>$items);
}
?>
