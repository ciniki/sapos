<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_accountSessionLoad(&$ciniki, $tnid, &$request) {

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
                $request['session']['cart'] = array(
                    'id'=>$rc['rows'][0]['id'],
                    'sapos_id'=>$rc['rows'][0]['id'],
                    );
            } else {
                $request['session']['cart']['id'] = $rc['rows'][0]['id'];
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
        $cart = $rc['invoice'];

        //
        // Check to make sure the invoice is still in shopping cart status
        //
        if( $rc['invoice']['invoice_type'] != '20' ) {
            $request['session']['cart']['sapos_id'] = 0;
            return array('stat'=>'ok');
        }

        if( !isset($rc['invoice']['items']) ) {
            $rc['invoice']['items'] = array();
        }
    
        $request['session']['cart'] = $rc['invoice'];
        $request['session']['cart']['sapos_id'] = $rc['invoice']['id'];
        $request['session']['cart']['num_items'] = count($rc['invoice']['items']);

        //
        // Attach the customer to the cart
        //
        if( isset($rc['invoice']['customer_id']) && $rc['invoice']['customer_id'] == 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartCustomerUpdate');
            $rc = ciniki_sapos_wng_cartCustomerUpdate($ciniki, $tnid, $request);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        //
        // Check for older carts and remove
        //
        $strsql = "SELECT id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' " 
            . "AND invoice_type = 20 " // Cart
            . "AND status = 10 "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $cart['id']) . "' " 
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.316', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            //
            // Remove older carts
            //
            $carts = $rc['rows'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'cartDelete');
            foreach($carts as $c) {
                $rc = ciniki_sapos_cartDelete($ciniki, $tnid, $c['id']);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.319', 'msg'=>'Unable to remove older cart', 'err'=>$rc['err']));
                }
            }
        }
        
        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
