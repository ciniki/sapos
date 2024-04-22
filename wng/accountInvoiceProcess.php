<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// This code has subtle differences from a cart checkout. 
// It is assumed this was setup over the phone or by an admin and just requires payment.
// Customers are unable to change invoice or items.
//

// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_accountInvoiceProcess(&$ciniki, $tnid, &$request, $args) {

    $js = '';
    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];
    
    //
    // Check if invoice specified
    //
    if( !isset($args['invoice_id']) || $args['invoice_id'] == 0 ) {
        return array('stat'=>'errmsg', 'level'=>'error', 'content'=>'No invoice specified');
    }

    //
    // Load the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'errmsg', 'level'=>'error', 'content'=>'Invoice not found');
    }
    $invoice = $rc['invoice'];

    //
    // Check if a payment_intent was passed for success or failure from stripe
    //
    if( isset($_GET['payment_intent']) ) {
        $blocks[] = array(
            'type' => 'title',
            'level' => 2,
            'title' => 'Invoice #' . $invoice['invoice_number'] . ' - ' . $invoice['status_text'],
            );
        if( isset($_GET['redirect_status']) && $_GET['redirect_status'] == 'succeeded' ) {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'success',
                'content' => 'Thank you for your payment',
                );
        } else if( isset($_GET['redirect_status']) && $_GET['redirect_status'] == 'pending' ) {     
            // FIXME: find out pending status when waiting for interac to complete
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'success',
                'content' => 'Thank you for your payment',
                );
        } else {
            $blocks[] = array(
                'type' => 'msg',
                'level' => 'error',
                'content' => 'There was a problem with your payment, please try again or contact us for help.',
                );
        }
        $blocks[] = [
            'type' => 'buttons',
            'align' => 'center',
            'items' => array(
                array('text'=>'Continue', 'url'=>"{$request['ssl_domain_base_url']}/account/invoices"), 
                ),
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }


    if( !isset($invoice['items']) ) {
        $invoice['items'] = array();
    }
    foreach($invoice['items'] as $iid => $item) {
        $item = $item['item'];
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x0400) && $item['code'] != '' ) {
            $item['description'] = $item['code'] . ' - ' . $item['description'];
        }
        if( $item['notes'] != '' ) {
            $item['description'] .= "<div class='indent notes'>" . preg_replace("/\n/", '<br/>', $item['notes']) . "</div>";
        }

        $discount_text = '';
        if( $item['unit_discount_amount'] > 0 ) {
            $discount_text .= '-' . numfmt_format_currency($intl_currency_fmt, 
                $item['unit_discount_amount'], $intl_currency)
                . (($item['quantity']>1)?'x'.$item['quantity']:'');
        }
        if( $item['unit_discount_percentage'] > 0 ) {
            $discount_text .= ($discount_text!=''?', ':'') . '-' . $item['unit_discount_percentage'] . '%';
        }
        $item['price_display'] = numfmt_format_currency($intl_currency_fmt, $item['unit_amount'], $intl_currency)
                . ($discount_text!=''?('<br/>' . $discount_text . ' ('
                    . numfmt_format_currency($intl_currency_fmt, $item['discount_amount'], $intl_currency)) . ')':'')
                . "</td>";

        //
        // Remove extra xml reference 'item'
        //
        $invoice['items'][$iid] = $item;
    }

    //
    // Setup footers
    //
    $footers = array();
    $duenow = '';
    if( isset($invoice['preorder_subtotal_amount']) && $invoice['preorder_subtotal_amount'] > 0 ) {
        $duenow = ' (Due Now)';
        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Pre-Order Subtotal:", 'colspan'=>2, 'class'=>'alignright'),
            array('value'=>numfmt_format_currency($intl_currency_fmt, $invoice['preorder_subtotal_amount'], $intl_currency),
                'class'=>'alignright'),
            );
        $shipping_value = '';
        if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x04)
            && !ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x10000000)
            && ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x20000000)
            ) {
            $shipping_value = 'Curbside Pickup';
        } elseif( $invoice['preorder_subtotal_amount'] > 0 && $invoice['customer_id'] == 0 ) {
            $shipping_value = "TBD";
        } else {
            $shipping_value = numfmt_format_currency($intl_currency_fmt, $invoice['preorder_shipping_amount'], $intl_currency);
        }
        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Shipping:", 'colspan'=>2, 'class'=>'alignright'),
            array('value'=>$shipping_value, 'class'=>'alignright'),
            );

        if( isset($invoice['preorder_taxes']) ) {
            foreach($invoice['preorder_taxes'] as $tax) {
                $tax = $tax['tax'];
                $footers[] = array(
                    array('value'=>"{$tax['description']}:", 'colspan'=>2, 'class'=>'alignright'),
                    array('value'=>numfmt_format_currency($intl_currency_fmt, $tax['amount'], $intl_currency),
                        'class'=>'alignright'),
                    );
            }
        }
        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Pre-Order Total (Due on Shipment):", 'colspan'=>2, 'class'=>'bold alignright'),
            array('value'=>numfmt_format_currency($intl_currency_fmt, $invoice['preorder_total_amount'], $intl_currency),
                'class'=>'alignright'),
            );
    }

    if( $invoice['shipping_status'] > 0 || (isset($invoice['taxes']) && count($invoice['taxes']) > 0) ) {
        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>'Subtotal:', 'colspan'=>2, 'class'=>'alignright'),
            array('value'=>numfmt_format_currency($intl_currency_fmt, $invoice['subtotal_amount'], $intl_currency),
                'class'=>'alignright'),
            );
    } 
    if( $invoice['shipping_status'] > 0 || (isset($invoice['shipping_amount']) && $invoice['shipping_amount'] > 0) ) {
        $shipping_value = '';
        if( !ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x04)
            && !ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x10000000)
            && ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x20000000)
            ) {
            $shipping_value = 'Curbside Pickup';
        } elseif( $invoice['subtotal_amount'] > 0 && $invoice['customer_id'] == 0 ) {
            $shipping_value = "TBD";
        } else {
            $shipping_value = numfmt_format_currency($intl_currency_fmt, $invoice['shipping_amount'], $intl_currency);
        }
        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Shipping:", 'colspan'=>2, 'class'=>'alignright'),
            array('value'=>$shipping_value, 'class'=>'alignright'),
            );
    }
    if( isset($invoice['taxes']) ) {
        foreach($invoice['taxes'] as $tax) {
            $tax = $tax['tax'];
            $footers[] = array(
                array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
                array('value'=>"{$tax['description']}:", 'colspan'=>2, 'class'=>'alignright'),
                array('value'=>numfmt_format_currency($intl_currency_fmt, $tax['amount'], $intl_currency),
                    'class'=>'alignright'),
                );
        }
    }

    //
    // Display list of existing transactions and balance due
    //
    if( (isset($invoice['transactions']) && count($invoice['transactions']) > 0) || $invoice['paid_amount'] > 0 ) {
        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Total:", 'colspan'=>2, 'class'=>'bold alignright'),
            array('value'=>numfmt_format_currency($intl_currency_fmt, $invoice['total_amount'], $intl_currency),
                'class'=>'alignright'),
            );

        // FIXME: Add option to allow for individual transaction list (included stripe type and last 4 digits)
/*        foreach($invoice['transactions'] as $transaction) {
            $footers[] = array(
                array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
                array('value'=>"Payment:", 'colspan'=>2, 'class'=>'alignright'),
                array('value'=>$transaction['transaction']['customer_amount'], 'class'=>'alignright'),
                );
        } */

        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Paid:", 'colspan'=>2, 'class'=>'alignright'),
            array('value'=>numfmt_format_currency($intl_currency_fmt, $invoice['paid_amount'], $intl_currency),
                'class'=>'alignright'),
            );

        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Balance{$duenow}:", 'colspan'=>2, 'class'=>'bold alignright'),
            array('value'=>numfmt_format_currency($intl_currency_fmt, $invoice['balance_amount'], $intl_currency),
                'class'=>'alignright'),
            );

    } else {
        $footers[] = array(
            array('value'=>'', 'colspan'=>1, 'class'=>'spacer'),
            array('value'=>"Total{$duenow}:", 'colspan'=>2, 'class'=>'bold alignright'),
            array('value'=>numfmt_format_currency($intl_currency_fmt, $invoice['total_amount'], $intl_currency),
                'class'=>'alignright'),
            );
    }

    //
    // Display the invoice
    //
    $blocks[] = array(
        'type' => 'table',
        'title' => 'Invoice #' . $invoice['invoice_number'] . ' - ' . $invoice['status_text'],
        'class' => 'fold-at-50 invoice no-fold-footers limit-width limit-width-60',
        'columns' => array(
            array('label'=>'Item', 'field'=>'description', 'class'=>'item-description aligntop', 'flex-basis'=>'100'),
            array('label'=>'Quantity', 'field'=>'quantity', 'class'=>'item-quantity aligntop alignright'),
            array('label'=>'Price', 'field'=>'price_display', 'class'=>'item-price aligntop alignright'),
            array('label'=>'Total', 'field'=>'total_amount_display', 'class'=>'item-total aligntop alignright'),
            ),
        'rows' => $invoice['items'],
        'footers' => $footers,
        );

    $tenant_flags = isset($ciniki['tenant']['modules']['ciniki.sapos']['flags']) ? $ciniki['tenant']['modules']['ciniki.sapos']['flags'] : 0;
    // 
    // Make sure shipping, simple shipping or instore pickup are enabled
    //
    if( ($tenant_flags&0x30000040) > 0 
        && isset($invoice['shipping_nameaddress']) && $invoice['shipping_nameaddress'] != '' 
        && ($invoice['shipping_status'] > 0 || ($invoice['preorder_total_amount'] > 0)) 
        ) {
        $blocks[] = array(
            'type' => 'table',
            'class' => 'fold-at-50 fold-label-headers no-fold-footers limit-width limit-width-60',
            'columns' => array(
                array('label'=>'Bill To:', 'field'=>'billto', 'fold-label'=>'Bill To:', 'class'=>'aligntop'),
                array('label'=>'Ship To:', 'field'=>'shipto', 'fold-label'=>'Ship To:', 'class'=>'aligntop'),
                ),
            'rows' => array(
                array(
                    'billto'=>str_replace("\n", "<br/>", $invoice['billing_nameaddress']), 
                    'shipto'=>str_replace("\n", "<br/>", $invoice['shipping_nameaddress']),
                    ),
                ),
            );
    } else {
        $blocks[] = array(
            'type' => 'table',
            'class' => 'limit-width limit-width-60',
            'columns' => array(
                array('label'=>'Bill To:', 'field'=>'billto', 'fold-label'=>'Bill To:', 'class'=>'aligntop'),
                ),
            'rows' => array(
                array('billto'=>str_replace("\n", "<br/>", $invoice['billing_nameaddress'])),
                ),
            );
    }

    if( isset($invoice['customer_notes']) && $invoice['customer_notes'] != '' ) {
        $blocks[] = array(
            'type' => 'table',
            'class' => 'limit-width limit-width-60',
            'columns' => array(
                array('label'=>'Notes', 'field'=>'customer_notes'),
                ),
            'rows' => array(
                array('customer_notes'=>$invoice['customer_notes']),
                ),
            );
    }

    //
    // Setup default buttons
    //
    $buttons = array(
        array('text' => 'Back', 'url' => $request['ssl_domain_base_url'] . '/account/invoices'),
        array('text' => 'Save PDF', 'target'=>'_blank', 'url' => "{$request['ssl_domain_base_url']}/account/invoices/download/{$invoice['invoice_number']}.pdf"),
        );

    //
    // Check if payment is required and pending and stripe hosted checkout is enabled
    //
    if( $invoice['status'] == 40 
        && isset($request['site']['settings']['stripe-pk']) && $request['site']['settings']['stripe-pk'] != '' 
        && isset($request['site']['settings']['stripe-sk']) && $request['site']['settings']['stripe-sk'] != '' 
        && isset($request['site']['settings']['stripe-version']) && $request['site']['settings']['stripe-version'] == 'elements' 
        ) {
        //
        // Setup stripe payment and display stripe payment button
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'stripeCheckoutCreate');
        $rc = ciniki_sapos_wng_stripeCheckoutCreate($ciniki, $tnid, $request, array(
            'invoice_id' => $invoice['id'],
            'return_url' => "{$request['ssl_domain_base_url']}/account/invoices/view/{$invoice['invoice_number']}",
            ));
        if( $rc['stat'] != 'ok' ) {
            $blocks[] = array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => 'Unable to setup checkout',
                );
        } else {
            $buttons[] = array(
                'text' => 'Pay Now',
                'js' => $rc['js'],
                );
        }
    }

    $blocks[] = array(
        'type' => 'buttons',
        'align' => 'right',
        'class' => 'limit-width limit-width-60',
        'items' => $buttons,
        );

//    $blocks[] = array(
//        'type' => 'html',
//        'html' => '<pre>' . print_r($invoice, true) . '</pre>',
//        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
