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
function ciniki_sapos_wng_etransferCheckout(&$ciniki, $tnid, &$request, $cart) {

    //
    // Load the current invoice_type and status
    //
    $strsql = "SELECT invoice_type, status, customer_id, receipt_number, payment_status, shipping_status "
        . "FROM ciniki_sapos_invoices "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $request['session']['cart']['sapos_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.411', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
    }
    if( !isset($rc['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.412', 'msg'=>"I'm sorry, but we seem to have a problem.", 'err'=>$rc['err']));
    }
    $invoice = $rc['invoice'];

    //
    // Get the current customer status
    //
    if( isset($invoice['customer_id']) && $invoice['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerStatus');
        $rc = ciniki_customers_hooks_customerStatus($ciniki, $tnid, array('customer_id'=>$invoice['customer_id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['customer']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.413', 'msg'=>'Customer does not exist for this invoice'));
        }
        $customer = $rc['customer'];
    }

    //
    // Update the invoice type and status
    //
    $args = array();
    if( $invoice['invoice_type'] == 20 ) {
        $args['invoice_type'] = 10;
    }
    $args['status'] = 42;
    $args['payment_status'] = 20;

    //
    // Get the items so they can be checked in each module if update required
    //
    $strsql = "SELECT id, "
        . "invoice_id, "
        . "flags, "
        . "quantity, "
        . "shipped_quantity, "
        . "discount_amount, "
        . "object, "
        . "object_id, "
        . "total_amount, "
        . "unit_donation_amount, "
        . "taxtype_id "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['cart']['sapos_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $donation_amount = 0;
    if( isset($rc['rows']) ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            if( $item['object'] != '' && $item['object_id'] != '' ) {
                list($pkg,$mod,$obj) = explode('.', $item['object']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'cartItemETransferCheckout');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $tnid, $request, array(
                        'object' => $item['object'],
                        'object_id' => $item['object_id'],
                        'invoice_id' => $item['invoice_id'],
                        ));
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                }
            }
/*            if( ($item['flags']&0x8000) == 0x8000 ) {
                $donation_amount = bcadd($donation_amount, $item['total_amount'], 6);
            } elseif( ($item['flags']&0x0800) == 0x0800 ) {
                $donation_amount = bcadd($donation_amount, ($item['quantity'] * $item['unit_donation_amount']), 6);
            } */
        }
/*    } else {
        $items = array(); */
    }
/*
    //
    // Check if invoice should have a receipt_number
    //
    if( $donation_amount > 0 && ($invoice['invoice_type'] == 10 || $args['invoice_type'] == 10)
        && ($invoice['receipt_number'] == '' || $invoice['receipt_number'] == 0) 
        ) {
        $strsql = "SELECT MAX(CAST(receipt_number AS UNSIGNED)) AS max_num "
            . "FROM ciniki_sapos_invoices "
            . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.378', 'msg'=>'Unable to find next available receipt number', 'err'=>$rc['err']));
        }
        if( isset($rc['num']['max_num']) ) {
            $args['receipt_number'] = $rc['num']['max_num'] + 1;
        } else {
            $args['receipt_number'] = 1;
        }
    }
*/
/*    if( isset($request['session']['customer']['display_name']) ) {
        $args['submitted_by'] = $request['session']['customer']['display_name'];
    } else {
        $args['submitted_by'] = '';
    } 
    if( isset($request['session']['customer']['email']) && $request['session']['customer']['email'] != '' ) {
        $args['submitted_by'] .= ($args['submitted_by']!='' ? ' [' . $request['session']['customer']['email'] . ']' : $request['session']['customer']['email']);
    } */

    //
    // Update the invoice with new settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', 
        $request['session']['cart']['sapos_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $request['session']['cart'] = array(
        'id' => 0,
        'sapos_id' => 0,
        'num_items' => 0,
        );

    //
    // Email the invoice to the customer
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
        $textmsg = "Thank you for your order, please send an e-transfer for the total amount.";
        if( isset($sapos_settings['cart-etransfer-submitted-message']) && $sapos_settings['cart-etransfer-submitted-message'] != '' ) {
            $textmsg = $sapos_settings['cart-etransfer-submitted-message'];
        }
        $textmsg = str_replace('{_invoice_total_}', '$' . number_format($invoice['total_amount'], 2), $textmsg);

/*        if( $invoice['shipping_status'] == 20 ) {
            if( isset($sapos_settings['instore-pickup-placed-email-subject']) 
                && $sapos_settings['instore-pickup-placed-email-subject'] != '' 
                ) {
                $subject = $sapos_settings['instore-pickup-placed-email-subject'];
                $subject = str_ireplace("{_invoicenumber_}", $invoice['invoice_number'], $subject);
            }

            if( isset($sapos_settings['instore-pickup-placed-email-content']) 
                && $sapos_settings['instore-pickup-placed-email-content'] != '' 
                ) {
                $textmsg = $sapos_settings['instore-pickup-placed-email-content'];
                $textmsg = str_ireplace("{_invoicenumber_}", $invoice['invoice_number'], $textmsg);
            }
        } */

        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
            'object' => 'ciniki.sapos.invoice',
            'object_id' => $cart['id'],
            'customer_id' => $cart['customer']['id'],
            'customer_email' => $cart['customer']['emails'][0]['email']['address'],
            'customer_name' => $cart['customer']['display_name'],
            'subject' => $subject,
            'html_content' => $textmsg,
            'text_content' => $textmsg,
            'attachments' => array(array('content'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);

        //
        // Email a notification if requested
        //
        if( isset($settings['cart-etransfer-submitted-emails']) && $settings['cart-etransfer-submitted-emails'] != '' ) {
            $emails = explode(',', $settings['cart-etransfer-submitted-emails']);

            $subject = "Invoice #" . $invoice['invoice_number'];
            $textmsg = "You have received a new e-transfer checkout:\n";
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
