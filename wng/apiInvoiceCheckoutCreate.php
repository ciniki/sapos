<?php
//
// Description
// -----------
// This function is used for the quick buy options on a website.
// This function will create a customer and cart which will be passed
// back to the website so it can continue on to stripe payment.
// This function was developed for the stripeCheckoutTickets function.
//
// Returns
// -------
//
function ciniki_sapos_wng_apiInvoiceCheckoutCreate(&$ciniki, $tnid, &$request) {

    //
    // Make sure first, last, email passed
    //
    if( !isset($request['args']['first']) || $request['args']['first'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.484', 'msg'=>'No first name provided'));
    }
    if( !isset($request['args']['last']) || $request['args']['last'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.485', 'msg'=>'No last name provided'));
    }
    if( !isset($request['args']['email']) || $request['args']['email'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.486', 'msg'=>'No email provided'));
    }
    if( !isset($request['args']['objects']) || $request['args']['objects'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.489', 'msg'=>'No tickets specified'));
    }
    $objects = json_decode($request['args']['objects'], true);

    //
    // Check if the email exists for a customer
    //
    if( isset($request['session']['customer']['first']) 
        && $request['session']['customer']['first'] == $request['args']['first']
        && isset($request['session']['customer']['last']) 
        && $request['session']['customer']['last'] == $request['args']['last']
        && isset($request['session']['customer']['email']) 
        && $request['session']['customer']['email'] == $request['args']['email']
        ) {
        $customer_id = $request['session']['customer']['id'];    
    } else {
        $strsql = "SELECT customers.id, "
            . "customers.first, "
            . "customers.last, "
            . "customers.stripe_customer_id "
            . "FROM ciniki_customer_emails AS emails "
            . "INNER JOIN ciniki_customers AS customers ON ("
                . "emails.customer_id = customers.id "
                . "AND customers.status < 60 "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE emails.email = '" . ciniki_core_dbQuote($ciniki, $request['args']['email']) . "' "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY emails.last_updated DESC "
            . "LIMIT 1 "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.487', 'msg'=>'Internal Error', 'err'=>$rc['err']));
        }
        if( isset($rc['customer']['id']) ) {
            $customer_id = $rc['customer']['id'];
        } else {
            //
            // Add customer
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'wng', 'customerAdd');
            $rc = ciniki_customers_wng_customerAdd($ciniki, $tnid, $request, [
                'first' => $request['args']['first'],
                'last' => $request['args']['last'],
                'email_address' => $request['args']['email'],
                'type' => 1,
                ]);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.488', 'msg'=>'Unable to setup customer', 'err'=>$rc['err']));
            }
            $customer_id = $rc['id'];
        }
    }

    //
    // Create the cart
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAdd');
    $rc = ciniki_sapos_invoiceAdd($ciniki, $tnid, [
        'customer_id' => $customer_id,
        'objects' => $objects,
        'invoice_type' => 20,   // Cart
        ]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.490', 'msg'=>'Unable to setup checkout', 'err'=>$rc['err']));
    }
    $invoice_id = $rc['id'];

    //
    // Return the invoice id
    //
    return array('stat'=>'ok', 'invoice_id'=>$invoice_id);
}
?>
