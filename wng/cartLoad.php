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
function ciniki_sapos_wng_cartLoad(&$ciniki, $tnid, &$request) {

    //
    // Check if the customer is signed in and look for an open cart
    //
    if( (!isset($request['session']['cart']['sapos_id']) || $request['session']['cart']['sapos_id'] == 0)
        && isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 
        ) {
        $strsql = "SELECT id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "AND invoice_type = 20 "
            . "AND status = 10 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows'][0]['id']) ) {
            if( !isset($request['session']['cart']) ) {
                $request['session']['cart'] = array('sapos_id'=>$rc['rows'][0]['id']);
            } else {
                $request['session']['cart']['sapos_id'] = $rc['rows'][0]['id'];
            }
        }
    }

    //
    // Load the cart if one exists
    //
    if( isset($request['session']['cart']['sapos_id']) && $request['session']['cart']['sapos_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
        $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $request['session']['cart']['sapos_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Check to make sure the invoice is still in shopping cart status
        //
        if( $rc['invoice']['status'] != '10' || $rc['invoice']['invoice_type'] != '20' ) {
            return array('stat'=>'noexist', 'cart'=>array());
        }

        if( !isset($rc['invoice']['items']) ) {
            $rc['invoice']['items'] = array();
        }
        $rc['invoice']['sapos_id'] = $rc['invoice']['id'];
        $rc['invoice']['num_items'] = count($rc['invoice']['items']);
        $request['session']['cart'] = $rc['invoice'];
        $request['session']['cart']['num_items'] = count($rc['invoice']['items']);

        return array('stat'=>'ok', 'cart'=>$rc['invoice']);
    }

    return array('stat'=>'noexist', 'cart'=>array());
}
?>
