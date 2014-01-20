<?php
//
// Description
// ===========
// This method will add a new quick invoice item.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_qiItemUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Item'), 
		'template'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Template'),
		'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
		'object'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object'),
		'object_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object ID'),
		'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
		'quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'type'=>'int', 'name'=>'Quantity'),
		'unit_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
			'name'=>'Unit Amount'),
		'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
			'name'=>'Discount Amount'),
		'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 
			'name'=>'Discount Percentage'),
		'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Tax Type'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.qiItemUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Add the item
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.qi_item', $args, 
		$args['item_id'], 0x07);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}
	$item_id = $rc['id'];

	return array('stat'=>'ok');
}
?>
