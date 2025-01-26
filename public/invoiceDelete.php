<?php
//
// Description
// ===========
// This method will delete an invoice, if it has no transactions and has not been voided.  
// If a invoice has transactions, or is in the status deposit/paid/refund then it needs
// to remain in the system, but could be voided.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Check if there are shipments attached to invoice
    //
    $strsql = "SELECT COUNT(id) AS num_shipments "
        . "FROM ciniki_sapos_shipments "
        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' " 
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipments');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['shipments']) && $rc['shipments']['num_shipments'] > 0 ) {
        $n = $rc['shipments']['num_shipments'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.63', 'msg'=>"Unable to remove, you have " . $n . " shipment" . ($n>1?'s':'') ."."));
    }

    //
    // Load the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    if( $invoice['status'] >= 40 && count($invoice['items']) > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.64', 'msg'=>'This invoice is already recorded in the accounting system, and cannot be removed.'));
    }

    if( count($invoice['transactions']) > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.65', 'msg'=>'There are transactions recorded for this invoice, it cannot be removed from the system.'));
    }

    //
    // Start the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Remove the taxes
    //
    if( isset($invoice['taxes']) && count($invoice['taxes']) > 0 ) {
        foreach($invoice['taxes'] as $tid => $tax) {
            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sapos.invoice_tax', 
                $tax['tax']['id'], NULL, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return $rc;
            }
        }
    }

    //
    // Remove the items
    //
    if( isset($invoice['items']) && count($invoice['items']) > 0 ) {
        foreach($invoice['items'] as $iid => $item) {
            $item = $item['item'];
            //
            // Check for a callback for the item object
            //
            if( $item['object'] != '' && $item['object_id'] != '' ) {
                list($pkg,$mod,$obj) = explode('.', $item['object']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemDelete');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $args['tnid'], $args['invoice_id'], $item);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }

            $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', 
                $item['id'], NULL, 0x04);
            if( $rc['stat'] != 'ok' && $rc['stat'] != 'noexist' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return $rc;
            }
        }
    }

    //
    // Remove the invoice
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sapos.invoice', 
        $args['invoice_id'], NULL, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok');
}
?>
