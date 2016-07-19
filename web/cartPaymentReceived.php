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
function ciniki_sapos_web_cartPaymentReceived(&$ciniki, $settings, $business_id, $cart) {

    //
    // Issue callbacks for invoice items
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'cartItemPaymentReceived');
    foreach($cart['items'] as $item) {
        $rc = ciniki_sapos_web_cartItemPaymentReceived($ciniki, $settings, $business_id, array(
            'invoice_id'=>$cart['id'],
            'item_id'=>$item['item']['id'],
            'student_id'=>$item['item']['student_id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the cart status
    //
    $cart['payment_status'] = 50;
    if( $cart['shipping_status'] > 0 ) {
        $cart['status'] = 30;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'submitOrder');
        $rc = ciniki_sapos_web_submitOrder($ciniki, $settings, $business_id, $cart);
    } else {
        $cart['status'] = 50;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'submitInvoice');
        $rc = ciniki_sapos_web_submitInvoice($ciniki, $settings, $business_id, $cart);
    }
    if( $rc['stat'] != 'ok' ) {
        $carterrors = "Oops, we seem to have had a problem with your order.";
        error_log('CART-ERR: Error with submitting a cart to Order/Invoice. ' . print_r($rc['err'], true));
        return $rc;
    } 

    //
    // Email the receipt to the dealer
    //
    if( isset($cart['customer']['emails'][0]['email']['address'])) {
        //
        // Load business details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
        $rc = ciniki_businesses_businessDetails($ciniki, $business_id);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $business_details = array();
        if( isset($rc['details']) && is_array($rc['details']) ) {
            $business_details = $rc['details'];
        }

        //
        // Load the invoice settings
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'business_id', $business_id, 'ciniki.sapos', 'settings', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sapos_settings = array();
        if( isset($rc['settings']) ) {
            $sapos_settings = $rc['settings'];
        }
        
        //
        // Create the pdf
        //
        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'templates', 'default');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $fn = $rc['function_call'];
        $rc = $fn($ciniki, $business_id, $cart['id'], $business_details, $sapos_settings, 'email');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Email the pdf to the customer
        //
        $filename = $rc['filename'];
        $invoice = $rc['invoice'];
        $pdf = $rc['pdf'];

        $subject = "Invoice #" . $invoice['invoice_number'];
        $textmsg = "Thank you for your order, please find the receipt attached.";
        $ciniki['emailqueue'][] = array('to'=>$invoice['customer']['emails'][0]['email']['address'],
            'to_name'=>(isset($invoice['customer']['display_name'])?$invoice['customer']['display_name']:''),
            'business_id'=>$business_id,
            'subject'=>$subject,
            'textmsg'=>$textmsg,
            'attachments'=>array(array('string'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename)),
            );

        //
        // Email a notification if requested
        //
        if( isset($settings['page-cart-payment-success-emails']) && $settings['page-cart-payment-success-emails'] != '' ) {
            $emails = explode(',', $settings['page-cart-payment-success-emails']);

            $subject = "Order #" . $invoice['invoice_number'];
            $textmsg = "You have received a new order:\n";
            $textmsg .= "\n";
            $textmsg .= "Invoice: " . $invoice['invoice_number'] . "\n";
            $textmsg .= "Bill To: " . $invoice['billing_name'] . "\n";
            $textmsg .= "\n";
            $textmsg .= "Items: \n";
            foreach($invoice['items'] as $item) {
                $item = $item['item'];
                $textmsg .= $item['description'] . "\n";
            }
            $textmsg .= "\n";
            foreach($emails as $email) {
                $email = trim($email);
                $ciniki['emailqueue'][] = array('to'=>$email,
                    'to_name'=>'',
                    'business_id'=>$business_id,
                    'subject'=>$subject,
                    'textmsg'=>$textmsg,
                    );
            }

        }
    }

    return array('stat'=>'ok');
}
?>
