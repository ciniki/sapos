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
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_invoiceGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        'inventory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventory'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoiceGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

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
	// Check if the inventory should be added
	//
	if( isset($args['inventory']) && $args['inventory'] == 'yes' ) {
		$objects = array();
		foreach($invoice['items'] as $iid => $item) {
			$item = $item['item'];
			if( !isset($objects[$item['object']]) ) {
				$objects[$item['object']] = array();
			}
			$objects[$item['object']][] = $item['object_id'];
		}
		// 
		// Get the inventory levels for each object
		//
		foreach($objects as $object => $object_ids) {
			list($pkg,$mod,$obj) = explode('.', $object);
			$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryLevels');
			if( $rc['stat'] == 'ok' ) {
				$fn = $rc['function_call'];
				$rc = $fn($ciniki, $args['business_id'], array(
					'object'=>$object,
					'object_ids'=>$object_ids,
					));
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1995', 'msg'=>'Unable to get inventory levels.', 'err'=>$rc['err']));
				}
				//
				// Update the inventory levels for the invoice items
				//
				$quantities = $rc['quantities'];
				foreach($invoice['items'] as $iid => $item) {
					if( $item['item']['object'] == $object 
						&& isset($quantities[$item['item']['object_id']]) 
						) {
						$invoice['items'][$iid]['item']['inventory_quantity'] = $quantities[$item['item']['object_id']]['inventory_quantity'];
					}
				}
			}
		}
	}

	return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
