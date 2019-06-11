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
function ciniki_sapos_cartDelete(&$ciniki, $tnid, $invoice_id) {
    //
    // Check if there are shipments attached to invoice
    //
    $strsql = "SELECT COUNT(id) AS num_shipments "
        . "FROM ciniki_sapos_shipments "
        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' " 
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'shipments');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['shipments']) && $rc['shipments']['num_shipments'] > 0 ) {
        $n = $rc['shipments']['num_shipments'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.276', 'msg'=>"Unable to remove, you have " . $n . " shipment" . ($n>1?'s':'') ."."));
    }

    //
    // Load the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    if( $invoice['invoice_type'] != 20 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.274', 'msg'=>'Invoice is not a shopping cart and cannot be removed.'));
    }

    if( $invoice['status'] != 10 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.275', 'msg'=>'This invoice is already recorded in the accounting system, and cannot be removed.'));
    }

    if( isset($invoice['transaction']) && count($invoice['transactions']) > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.276', 'msg'=>'There are transactions recorded for this invoice, it cannot be removed from the system.'));
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
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice_tax', $tax['tax']['id'], NULL, 0x04);
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
                    $rc = $fn($ciniki, $tnid, $invoice_id, $item);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }

            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice_item', $item['id'], NULL, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return $rc;
            }
        }
    }

    //
    // Remove the invoice
    //
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.sapos.invoice', $invoice_id, NULL, 0x04);
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
    ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'sapos');

    return array('stat'=>'ok');
}
?>
