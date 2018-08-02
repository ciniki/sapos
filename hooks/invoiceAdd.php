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
//
function ciniki_sapos_hooks_invoiceAdd($ciniki, $tnid, $args) {
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $tnid, 'ciniki.sapos.invoiceAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Create the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAdd');
    $rc = ciniki_sapos_invoiceAdd($ciniki, $tnid, $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice_id = $rc['id'];

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'sapos');

    return array('stat'=>'ok', 'id'=>$invoice_id);
}
?>
