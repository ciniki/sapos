<?php
//
// Description
// ===========
// This method will remove a tax from the ciniki_sapos_taxes, only if there are no
// invoices still attached to this tax.  Otherwise, it needs to stay in the system,
// with a deleted status.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_taxDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'tax_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tax'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.taxDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	
	//
	// Check to make sure no invoices are using this tax
	//
	$strsql = "SELECT 'num_invoices', COUNT(*) "
		. "FROM ciniki_sapos_invoice_taxes "
		. "WHERE tax_id = '" . ciniki_core_dbQuote($ciniki, $args['tax_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.sapos', 'num');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( isset($rc['num']) && $rc['num']['num_invoices'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1113', 'msg'=>'There are invoices still using this tax, it cannot be deleted.'));
	}

	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.sapos.tax', $args['tax_id'], NULL, 0x07);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok', 'id'=>$rc['id']);
}
?>
