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
        $invoice = $rc['invoice'];


        //
        // Check to make sure the invoice is still in shopping cart status
        //
        if( $invoice['status'] != '10' || $invoice['invoice_type'] != '20' ) {
            return array('stat'=>'noexist', 'cart'=>array());
        }

        if( !isset($invoice['items']) ) {
            $invoice['items'] = array();
        }
        //
        // Check for edit urls for the items
        //
        foreach($invoice['items'] as $iid => $item) {
            $item = $item['item'];
            if( isset($item['object']) && $item['object'] != '' ) {
                list($pkg, $mod, $obj) = explode('.', $item['object']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'cartItemEditURL');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $tnid, $invoice['id'], array(
                        'object'=>$item['object'],
                        'object_id'=>$item['object_id'],
                        'price_id'=>$item['price_id'],
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    if( isset($rc['url']) && $rc['url'] != '' ) {
                        $invoice['items'][$iid]['item']['edit_url'] = $request['ssl_domain_base_url'] . $rc['url'];
                    }
                }
            }
        }

        $invoice['sapos_id'] = $invoice['id'];
        $invoice['num_items'] = count($invoice['items']);
        $request['session']['cart'] = $invoice;
        $request['session']['cart']['num_items'] = count($invoice['items']);

        return array('stat'=>'ok', 'cart'=>$invoice);
    }

    return array('stat'=>'noexist', 'cart'=>array());
}
?>
