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
function ciniki_sapos_wng_cartPaymentReceived(&$ciniki, $tnid, $request, $cart) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    //
    // Issue callbacks for invoice items
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'cartItemPaymentReceived');
    foreach($cart['items'] as $item) {
        $rc = ciniki_sapos_wng_cartItemPaymentReceived($ciniki, $tnid, $request, array(
            'invoice_id' => $cart['id'],
            'item_id' => $item['item']['id'],
            'student_id' => $item['item']['student_id'],
            'customer' => $cart['customer'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Update the cart status
    //
    $cart['payment_status'] = 50;
/*    if( isset($request['session']['customer']['dealer_status']) 
        && $request['session']['customer']['dealer_status'] > 0 
        && ($cart['shipping_status'] > 0 || $cart['preorder_status'] > 0) 
        ) {
        $cart['status'] = 30;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'submitOrder');
        $rc = ciniki_sapos_wng_submitOrder($ciniki, $settings, $tnid, $cart);
    } else { */
        if( $cart['shipping_status'] == 20 ) {
            $cart['status'] = 45;
        } elseif( $cart['shipping_status'] > 0 || $cart['preorder_status'] > 0 ) {
            $cart['status'] = 30;
        } else {
            $cart['status'] = 50;
        }
        $cart['paid_amount'] = $cart['total_amount'];
        $cart['balance_amount'] = 0;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'submitInvoice');
        $rc = ciniki_sapos_wng_submitInvoice($ciniki, $tnid, $request, $cart);
/*    } */
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
        // Load tenant details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
        $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $tenant_details = array();
        if( isset($rc['details']) && is_array($rc['details']) ) {
            $tenant_details = $rc['details'];
        }

        //
        // Load the invoice settings
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
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
        $rc = $fn($ciniki, $tnid, $cart['id'], $tenant_details, $sapos_settings, 'email');
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

        if( isset($sapos_settings['cart-email-message']) 
            && $sapos_settings['cart-email-message'] != '' 
            ) {
            $textmsg = $sapos_settings['cart-email-message'];
        }

        if( $invoice['shipping_status'] == 20 ) {
            if( isset($sapos_settings['instore-pickup-placed-email-subject']) 
                && $sapos_settings['instore-pickup-placed-email-subject'] != '' 
                ) {
                $subject = $sapos_settings['instore-pickup-placed-email-subject'];
            }

            if( isset($sapos_settings['instore-pickup-placed-email-content']) 
                && $sapos_settings['instore-pickup-placed-email-content'] != '' 
                ) {
                $textmsg = $sapos_settings['instore-pickup-placed-email-content'];
            }
        }
        $subject = str_ireplace("{_invoicenumber_}", $invoice['invoice_number'], $subject);
        $textmsg = str_ireplace("{_invoicenumber_}", $invoice['invoice_number'], $textmsg);

        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
            'object' => 'ciniki.sapos.invoice',
            'object_id' => $cart['id'],
            'customer_id' => $invoice['customer']['id'],
            'customer_email' => $invoice['customer']['emails'][0]['email']['address'],
            'customer_name' => $invoice['customer']['display_name'],
            'subject' => $subject,
            'html_content' => $textmsg,
            'text_content' => $textmsg,
            'attachments' => array(array('content'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
/*        $ciniki['emailqueue'][] = array('to'=>$invoice['customer']['emails'][0]['email']['address'],
            'to_name'=>(isset($invoice['customer']['display_name'])?$invoice['customer']['display_name']:''),
            'tnid'=>$tnid,
            'subject'=>$subject,
            'textmsg'=>$textmsg,
            'attachments'=>array(array('string'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename)),
            ); */

        //
        // Email a notification if requested
        //
        if( isset($settings['cart-payment-success-emails']) && $settings['cart-payment-success-emails'] != '' ) {
            $emails = explode(',', $settings['cart-payment-success-emails']);

            $subject = "Order #" . $invoice['invoice_number'];
            $textmsg = "You have received a new order:\n";
            $textmsg .= "\n";
            $textmsg .= "Invoice: " . $invoice['invoice_number'] . "\n";
            $textmsg .= "Bill To: " . $invoice['billing_name'] . "\n";
            $textmsg .= "Total: " . $invoice['total_amount_display'] . "\n";
            $textmsg .= "\n";
            $textmsg .= "Items: \n";
            foreach($invoice['items'] as $item) {
                $item = $item['item'];
                $textmsg .= $item['description'] . " - " . $item['total_amount_display'] . "\n";
            }
            $textmsg .= "\n";
            foreach($emails as $email) {
                $email = trim($email);
/*                $ciniki['emailqueue'][] = array('to'=>$email,
                    'to_name'=>'',
                    'tnid'=>$tnid,
                    'subject'=>$subject,
                    'textmsg'=>$textmsg,
                    'attachments'=>array(array('string'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename)),
                    ); */
                $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                    'object' => 'ciniki.sapos.invoice',
                    'object_id' => $cart['id'],
                    'customer_email' => $email,
                    'customer_name' => '',
                    'subject' => $subject,
                    'html_content' => $textmsg,
                    'text_content' => $textmsg,
                    'attachments' => array(array('content'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename)),
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);
            }

        }
    }

    return array('stat'=>'ok');
}
?>
