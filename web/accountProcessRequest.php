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
function ciniki_sapos_web_accountProcessRequest($ciniki, $settings, $business_id, $args) {

    $page = array(
        'title'=>'Orders',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );
    $base_url = $args['base_url'];


	//
	// Get business/user settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $ciniki['request']['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the customer invoices
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'customerOrders');
    $rc = ciniki_sapos_web_customerOrders($ciniki, $settings, $ciniki['request']['business_id'], $ciniki['session']['customer']['id'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['invoices']) ) {
        $invoices = $rc['invoices'];
        $invoice_displayed = 'no';
        $content = '';
        //
        // Check if user requested to view the order details
        //
        if( isset($_POST['action']) && $_POST['action'] == 'orderdetails' 
            && isset($_POST['invoice_id']) && $_POST['invoice_id'] != '' 
            && isset($ciniki['session']['customer']['id'])
            && isset($settings['page-account-invoices-view-details']) && $settings['page-account-invoices-view-details'] == 'yes'
            ) {
            $invoice_id = $_POST['invoice_id'];
            foreach($invoices as $invoice) {
                if( $invoice['id'] == $invoice_id ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'customerOrder');
                    $rc = ciniki_sapos_web_customerOrder($ciniki, $settings, $ciniki['request']['business_id'], 
                        $ciniki['session']['customer']['id'], array('invoice_id'=>$invoice['id']));
                    if( $rc['stat'] != 'ok' ) {
                        $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 
                            'message'=>"We seem to be having problems locating that invoice, please contact us for assistance.");
                        break;
                    }
                    if( isset($rc['invoice']) ) {
                        $customer_invoice = $rc['invoice'];
                    }
                }
            }
            //
            // Display the customer invoice
            //
            if( isset($customer_invoice) ) {
                $page['title'] = "Order #" . $customer_invoice['invoice_number'];

                //
                // Note: Taken from generatePageCart
                //
                $content .= "<div class='cart cart-items'>";
                $content .= "<table class='cart-items'>";
                $content .= "<thead><tr>"
                    . "<th class='aligncenter'>Item</th>"
                    . "<th class='alignright'>Qty Ordered</th>"
                    . "<th class='alignright'>Qty Shipped</th>"
                    . "<th class='alignright'>Price</th>"
                    . "<th class='alignright'>Total</th>"
                    . "</tr></thead>";
                $content .= "<tbody>";
                $count=0;
                foreach($customer_invoice['items'] as $item_id => $item) {
                    $item = $item['item'];
                    $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'><td>";
                    if( isset($item['url']) && $item['url'] != '' ) {
                        $content .= "<a href='" . $item['url'] . "'>" . $item['description'] . "</a>";
                    } else {
                        $content .= $item['description'];
                    }
                    $content .= "</td>";
                    $content .= "<td class='alignright'>" . $item['quantity'] . "</td>";
                    $content .= "<td class='alignright'>" . $item['shipped_quantity'] . "</td>";
                    $discount_text = '';
                    if( $item['unit_discount_amount'] > 0 ) {
                        $discount_text .= '-' . $item['unit_discount_amount_display']
                            . (($item['quantity']>1)?'x'.$item['quantity']:'');
                    }
                    if( $item['unit_discount_percentage'] > 0 ) {
                        $discount_text .= ($discount_text!=''?', ':'') . '-' . $item['unit_discount_percentage'] . '%';
                    }
                    $content .= "<td class='alignright'>" . $item['unit_amount_display']
                            . ($discount_text!=''?('<br/>' . $discount_text . ' (' . $item['discount_amount_display'] .')'):'')
                            . "</td>";
                    $content .= "<td class='alignright'>" 
                            . $item['total_amount_display']
                            . "</td>";
                    $content .= "</tr>";
                    $count++;
                }
                $content .= "</tbody>";
                $content .= "<tfoot>";
                // cart totals
                $num_cols = 4;
                if( $customer_invoice['shipping_amount'] > 0 || (isset($customer_invoice['taxes']) && count($customer_invoice['taxes']) > 0) ) {
                    $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'>";
                    $content .= "<td colspan='$num_cols' class='alignright'>Sub-Total:</td>"
                        . "<td class='alignright'>"
                        . numfmt_format_currency($intl_currency_fmt, $customer_invoice['subtotal_amount'], $intl_currency)
                        . "</td>"
                        . "</tr>";
                    $count++;
                }
                if( isset($customer_invoice['shipping_amount']) && $customer_invoice['shipping_amount'] > 0 ) {
                    $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'>";
                    $content .= "<td colspan='$num_cols' class='alignright'>Shipping:</td>"
                        . "<td class='alignright'>"
                        . numfmt_format_currency($intl_currency_fmt, $customer_invoice['shipping_amount'], $intl_currency)
                        . "</td>"
                        . "</tr>";
                    $count++;
                }
                if( isset($customer_invoice['taxes']) ) {
                    foreach($customer_invoice['taxes'] as $tax) {
                        $tax = $tax['tax'];
                        $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'>";
                        $content .= "<td colspan='$num_cols' class='alignright'>" . $tax['description'] . ":</td>"
                            . "<td class='alignright'>"
                            . numfmt_format_currency($intl_currency_fmt, $tax['amount'], $intl_currency)
                            . "</td>"
                            . "</tr>";
                        $count++;
                    }
                }
                $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'>";
                $content .= "<td colspan='$num_cols' class='alignright'><b>Total:</b></td>"
                    . "<td class='alignright'>" . $customer_invoice['total_amount_display'] . "</td>"
                    . "</tr>";
                $count++;
                $content .= "</foot>";
                $content .= "</table>";
                $content .= "</div>";

                $page['blocks'][] = array('type'=>'content', 'html'=>$content);

                $invoice_displayed = 'yes';
            }
            //
            // Display the shipments
            //
            if( isset($customer_invoice['shipments']) ) {
                $content = '';
                foreach($customer_invoice['shipments'] as $shipment) {
//						$content .= "<pre>" . print_r($shipment, true) . "</pre>";
                    $shipment = $shipment['shipment'];
//                   $content .= "<div class='cart'>";

                    $saddr = '';
                    if( isset($shipment['shipping_name']) && $shipment['shipping_name'] != '' ) { $saddr .= ($saddr!=''?'<br/>':'') . $shipment['shipping_name']; }
                    if( isset($shipment['shipping_address1']) && $shipment['shipping_address1'] != '' ) { $saddr .= ($saddr!=''?'<br/>':'') . $shipment['shipping_address1']; }
                    if( isset($shipment['shipping_address2']) && $shipment['shipping_address2'] != '' ) { $saddr .= ($saddr!=''?'<br/>':'') . $shipment['shipping_address2']; }
                    $city = '';
                    if( isset($shipment['shipping_city']) && $shipment['shipping_city'] != '' ) { $city .= ($city!=''?'':'') . $shipment['shipping_city']; }
                    if( isset($shipment['shipping_province']) && $shipment['shipping_province'] != '' ) { $city .= ($city!=''?', ':'') . $shipment['shipping_province']; }
                    if( isset($shipment['shipping_postal']) && $shipment['shipping_postal'] != '' ) { $city .= ($city!=''?'  ':'') . $shipment['shipping_postal']; }
                    if( $city != '' ) { $saddr .= ($saddr!=''?'<br/>':'') . $city; } 
                    if( isset($shipment['shipping_country']) && $shipment['shipping_country'] != '' ) { $saddr .= ($saddr!=''?'<br/>':'') . $shipment['shipping_country']; }

                    $content .= "<div class='cart cart-details'><table class='cart-details'><tbody>";
                    $count = 1;
                    $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'><th>Status</th><td>" . $shipment['status_text'] . "</td></tr>";
                    $count++;
                    $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'><th>Date Shipped</th><td>" . $shipment['ship_date'] . "</td></tr>";
                    $count++;
                    $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'><th>Address</th><td>" . $saddr . "</td></tr>";
                    $count++;
                    if( preg_match('/fedex/i', $shipment['shipping_company']) && $shipment['tracking_number'] != '' ) {
                        $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'><th>Tracking Number</th><td><a target='_blank' href='https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=" . $shipment['tracking_number'] . "&cntry_code=us'>" . $shipment['tracking_number'] . "</a></td></tr>";
                        $count++;
                    }
                    $content .= "</tbody></table></div>";
                    $content .= "<br/>";
                    $content .= "<div class='cart cart-items'>";
                    $content .= "<table class='cart-items'>";
                    $content .= "<thead><tr>"
                        . "<th class='aligncenter'>Item</th>"
                        . "<th class='alignright'>Quantity</th>"
                        . "</tr></thead>";
                    $content .= "<tbody>";
                    $count=0;
                    foreach($shipment['items'] as $item_id => $item) {
                        $item = $item['item'];
                        $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'>"
                            . "<td>";
//							if( isset($item['object']) && isset($item['permalink']) ) {
//								switch($item['object']) {
//									case 'ciniki.products.product': 
//										$item['url'] = $ciniki['request']['base_url'] . '/products/product/' . $item['permalink'];
//										break;
//								}
//							}
                        if( isset($item['url']) && $item['url'] != '' ) {
                            $content .= "<a href='" . $item['url'] . "'>" . $item['description'] . "</a>";
                        } else {
                            $content .= $item['description'];
                        }
                        $content .= "</td>";
                        $content .= "<td class='alignright'>" . $item['quantity'] . "</td>";
                        $content .= "</tr>";
                        $count++;
                    }
                    $content .= "</tbody>";
                    $content .= "</table>";
                    $content .= "</div>";
//                    $content .= "</div>";

                    $page['blocks'][] = array('type'=>'content', 
                        'title'=>"Shipment" . (count($customer_invoice['shipments'])>1?" #" . $shipment['shipment_number']:''),
                        'html'=>$content);
                }
            }
        }

        //
        // If the invoice could not be found, was locked, etc, then revert display to the list of orders
        //
        if( $invoice_displayed == 'no' ) {
            $content = "<div class='cart cart-items'>";
            if( isset($settings['page-account-invoices-view-details']) && $settings['page-account-invoices-view-details'] == 'yes' ) {
                $content .= "<form class='wide' method='POST' action='" . $ciniki['request']['ssl_domain_base_url'] . "/account/orders'>";
                $content .= "<input type='hidden' name='action' value='orderdetails'/>";
            } else {
                $content .= "<form target='_blank' method='POST' action='" . $ciniki['request']['ssl_domain_base_url'] . "/account/orders'>";
                $content .= "<input type='hidden' name='action' value='downloadorder'/>";
            }
            $content .= "<input type='hidden' id='invoice_id' name='invoice_id' value=''/>";
            $content .= "<table class='cart-items'>";
            $content .= "<thead><tr>"
                . "<th>Invoice #</th>"
                . ((isset($settings['page-cart-po-number']) && $settings['page-cart-po-number'] == 'required')?"<th>PO Number</th>":"")
                . "<th>Date</th>"
                . "<th>Status</th>";
            if( (isset($settings['page-account-invoices-view-pdf']) && $settings['page-account-invoices-view-pdf'] == 'yes')
                || (isset($settings['page-account-invoices-view-details']) && $settings['page-account-invoices-view-details'] == 'yes')
                ) {
                $content .= "<th>Action</th>";
            }
            $content .= "</tr></thead>";
            $content .= "<tbody>";
            $count = 0;
            foreach($invoices as $invoice) {
                $content .= "<tr class='" . (($count%2)==0?'item-even':'item-odd') . "'>"
                    . "<td>" . $invoice['invoice_number'] . "</td>"
                    . ((isset($settings['page-cart-po-number']) && $settings['page-cart-po-number'] == 'required')?"<td>".$invoice['po_number']."</td>":"")
                    . "<td>" . $invoice['invoice_date'] . "</td>"
                    . "<td class='aligncenter'>" . $invoice['status'] . "</td>"
                    . "";
                if( isset($settings['page-account-invoices-view-details']) 
                    && $settings['page-account-invoices-view-details'] == 'yes' ) {
                    $content .= "<td class='aligncenter'>"
                        . "<input class='cart-submit' onclick='document.getElementById(\"invoice_id\").value=" . $invoice['id'] . ";return true;' type='submit' name='details' value='View'/>"
                        . "</td>";
                }
                elseif( isset($settings['page-account-invoices-view-pdf']) 
                    && $settings['page-account-invoices-view-pdf'] == 'yes' ) {
                    $content .= "<td class='aligncenter'>"
                        . "<input class='cart-submit' onclick='document.getElementById(\"invoice_id\").value=" . $invoice['id'] . ";return true;' type='submit' name='pdf' value='View'/>"
                        . "</td>";
                }
                $content .= "</tr>";
                $count++;
            }
            $content .= "</tbody></table>";
            $content .= "</form>";
            $content .= "</div>";

            $page['blocks'][] = array('type'=>'content', 'html'=>$content);
        }
    }
    
    else {
        $page['blocks'][] = array('type'=>'message', 'content'=>'No orders found.');
    }
   
	return array('stat'=>'ok', 'page'=>$page);
}
?>
