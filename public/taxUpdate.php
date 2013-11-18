<?php
//
// Description
// ===========
// This method will add a new tax for a business.
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
		'item_percentage'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Item Percentage'),
		'item_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Item Amount'),
		'invoice_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice Amount'),
		'taxtypes'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tax Types'),
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'),
		'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetime', 'name'=>'Start Date'),
		'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetime', 'name'=>'End Date'),
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

	//
	// Check if this tax has been used yet
	//
	$strsql = "SELECT 'invoices', COUNT(*) "
		. "FROM ciniki_sapos_invoices "
		. "WHERE tax_id = '" . ciniki_core_dbQuote($ciniki, $args['tax_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$num_invoices = 0;
	if( isset($rc['num']['invoices']) ) {
		$num_invoices = $rc['num']['invoices'];
	}

	//
	// The tax values for item_percentage, item_amount or invoice_amount can only be changed
	// if there are no invoices that use them.  Once a tax is used, it can no longer be changed,
	// a new tax needs to be created.
	//
	if( (isset($args['item_percentage']) || isset($args['item_amount']) || isset($args['invoice_amount']))
		&& $num_invoices > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'999', 'msg'=>'Unable to update tax, there are invoices using this tax.  Please create a new tax'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.sapos.tax', $args, 0x07);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$rc['id']);
}
?>
