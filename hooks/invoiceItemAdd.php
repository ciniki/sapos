<?php
//
// Description
// ===========
// This hook will call the private method to add a new item to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_hooks_invoiceItemAdd($ciniki, $tnid, $args) {
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $tnid, 'ciniki.sapos.invoiceItemAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Create the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
    $rc = ciniki_sapos_invoiceAddItem($ciniki, $tnid, $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $item_id = $rc['id'];

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'sapos');

    return array('stat'=>'ok', 'id'=>$item_id);
}
?>
