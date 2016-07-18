<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_cartUpdate($ciniki, $settings, $business_id, $args) {

    //
    // Check that a cart does not exist
    //
    if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
        $invoice_id = $ciniki['session']['cart']['sapos_id'];   
        //
        // Check that an item was specified
        //

        //
        // Get the existing invoice details
        //
        $strsql = "SELECT id, po_number, customer_notes "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['cart']['sapos_id']) . "' "
            . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }   
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2016', 'msg'=>'Unable to locate the invoice'));
        }
        $invoice = $rc['invoice'];

        $update_args = array();
        if( isset($args['po_number']) ) {
            $update_args['po_number'] = $args['po_number'];
        }
        if( isset($args['customer_notes']) ) {
            $update_args['customer_notes'] = $args['customer_notes'];
        }

        //
        // Update the item
        //
        if( count($update_args) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.sapos.invoice', $ciniki['session']['cart']['sapos_id'], $update_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
        
        return array('stat'=>'ok');
    }

    return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'2017', 'msg'=>'Cart does not exist'));
}
?>
