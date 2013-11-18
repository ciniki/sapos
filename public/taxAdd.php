<?php
//
// Description
// ===========
// This method will update an existing tax.  
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_taxUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'tax_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tax'),
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
		'item_percentage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Item Percentage'),
		'item_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Item Amount'),
		'invoice_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Invoice Amount'),
		'taxtypes'=>array('required'=>'yes', 'blank'=>'yes', 'default'=>'0', 'name'=>'Tax Types'),
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Flags'),
		'start_date'=>array('required'=>'yes', 'blank'=>'yes', 'type'=>'datetime', 'name'=>'Start Date'),
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetime', 'name'=>'End Date'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.taxAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	if( $args['item_percentage'] == '' && $args['item_amount'] == '' && $args['invoice_amount'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'You must specify a item percentage, item amount or invoice amount.'));
	}

	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.sapos.tax', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$rc['id']);
}
?>
