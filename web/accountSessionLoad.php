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
function ciniki_sapos_web_accountSessionLoad(&$ciniki, $settings, $tnid) {

    //
    // Check if the customer is signed in and look for an open cart
    //
    if( (!isset($ciniki['session']['cart']['sapos_id']) || $ciniki['session']['cart']['sapos_id'] == 0)
        && isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 
        ) {
        $strsql = "SELECT id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' "
            . "AND invoice_type = 20 "
            . "AND status = 10 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.products', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows'][0]['id']) ) {
            if( !isset($ciniki['session']['cart']) ) {
                $ciniki['session']['cart'] = array('sapos_id'=>$rc['rows'][0]['id']);
            } else {
                $ciniki['session']['cart']['sapos_id'] = $rc['rows'][0]['id'];
            }
        }
    }

    //
    // Load the cart if one exists
    //
    if( isset($ciniki['session']['cart']['sapos_id']) && $ciniki['session']['cart']['sapos_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
        $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $ciniki['session']['cart']['sapos_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $cart = $rc['invoice'];

        //
        // Check to make sure the invoice is still in shopping cart status
        //
        if( $rc['invoice']['invoice_type'] != '20' ) {
            return array('stat'=>'ok');
        }

        if( !isset($rc['invoice']['items']) ) {
            $rc['invoice']['items'] = array();
        }
    
        $_SESSION['cart'] = $rc['invoice'];
        $_SESSION['cart']['sapos_id'] = $rc['invoice']['id'];
        $_SESSION['cart']['num_items'] = count($rc['invoice']['items']);
        $ciniki['session']['cart'] = $_SESSION['cart'];
        $ciniki['session']['cart']['sapos_id'] = $_SESSION['cart']['sapos_id'];
        $ciniki['session']['cart']['num_items'] = $_SESSION['cart']['num_items'];

        //
        // Attach the customer to the cart
        //
        if( isset($rc['invoice']['customer_id']) && $rc['invoice']['customer_id'] == 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartCustomerUpdate');
            $rc = ciniki_sapos_web_cartCustomerUpdate($ciniki, $settings, $tnid);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }

        //
        // Check for older carts and remove
        //
        $strsql = "SELECT id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "' " 
            . "AND invoice_type = 20 " // Cart
            . "AND status = 10 "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $cart['id']) . "' " 
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.5', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
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
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.11', 'msg'=>'Unable to remove older cart', 'err'=>$rc['err']));
                }
            }
        }
        
        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
