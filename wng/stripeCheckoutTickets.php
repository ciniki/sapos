<?php
//
// Description
// -----------
// This function will create button that can be added to wng block buttons which will
// start a stripe checkout process.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_wng_stripeCheckoutTickets(&$ciniki, $tnid, &$request, $args) {

    if( !isset($request['response']['js']) ) {
        $request['response']['js'] = '';
    }
    if( !isset($request['response']['head']['scripts']['stripe']) ) {
        $request['response']['head']['scripts']['stripe'] = array(
            'src' => 'https://js.stripe.com/v3/',
            );
    }

    $cid = (isset($args['checkout_id']) ? $args['checkout_id'] : '');
    $jsname = "C.stripetickets" . $cid;
    $iprefix = "stripeticket_{$cid}_";

    //        might have several different event tickets sold on single page
    if( strstr($request['response']['js'], "{$jsname} = {") === false ) {
      
        $tickets_html = '';
        $prices = array();
        $start_total = 0;
        $pay_button_label = 'Pay Now';
        if( isset($args['prices']) ) {
            foreach($args['prices'] as $price) {
                $prices[] = array(
                    'price_id' => $price['price_id'],
                    'object' => $price['object'],
                    'object_id' => $price['object_id'],
                    'unit_amount' => $price['unit_amount'],
                    );
                $label = $price['name'];
                if( $price['unit_amount'] == 0 ) {
                    $label .= ": Free";
                } else {
                    $label .= ": $" . number_format($price['unit_amount'], 2);
                }
                $quantity = 0;
                if( count($args['prices']) == 1 ) {
                    $quantity = 1;
                    $start_total = $price['unit_amount'];
                }
                $tickets_html .=  "<div class=\"ciniki-input ciniki-ticket\">"
                    . "<label for=\"{$iprefix}{$price['price_id']}\">{$label}</label>"
                    . "<input class=\"quantity\" id=\"{$iprefix}{$price['price_id']}\" name=\"{$iprefix}{$price['price_id']}\" "
                        . "value=\"{$quantity}\" onkeyup=\"{$jsname}.qUpdt();\">"
                    . "</div>";
            }
        }
        if( $start_total > 0 ) {
            $pay_button_label = 'Pay $' . number_format($start_total, 2);
        }

        $request['response']['js'] .= "{$jsname} = {};"
            . "{$jsname}.checkout = function() {"
            // 
            // Start a new stripe session via API
            //
            . "{$jsname}.form = C.aE('form', 'stripe-payment-form', 'block-stripecheckout tickets', '<div class=\"wrap\"><div class=\"content\">"
                . '<div class="spinner"><svg width="48" height="48" stroke="#000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_V8m1{transform-origin:center;animation:spinner_zKoa 2s linear infinite}.spinner_V8m1 circle{stroke-linecap:round;animation:spinner_YpZS 1.5s ease-in-out infinite}@keyframes spinner_zKoa{100%{transform:rotate(360deg)}}@keyframes spinner_YpZS{0%{stroke-dasharray:0 150;stroke-dashoffset:0}47.5%{stroke-dasharray:42 150;stroke-dashoffset:-16}95%,100%{stroke-dasharray:42 150;stroke-dashoffset:-59}}</style><g class="spinner_V8m1"><circle cx="12" cy="12" r="9.5" fill="none" stroke-width="3"></circle></g></svg></div>'
                . "<div class=\"ciniki-form-elements\">"
                    . "<div class=\"ciniki-tickets\">"
                        . $tickets_html
                        . "<div class=\"error-msg hidden\" id=\"{$iprefix}quantity_error\">You must select at least 1 ticket.</div>"
                    . "</div>"
                    . "<div class=\"ciniki-total\">"
                        . "<span class=\"label\">Total:</span><span id=\"{$iprefix}ticket_total\" class=\"total\">$"
                            . number_format($start_total, 2) . "</span>"
                    . "</div>"
                    . "<div class=\"ciniki-customer\">"
                        . "<div class=\"ciniki-input ciniki-input-first\">"
                            . "<label for=\"{$iprefix}first\">First Name</label>"
                            . "<input class=\"\" id=\"{$iprefix}first\" name=\"{$iprefix}first\" value=\"" 
                                . (isset($request['session']['customer']['first']) ? $request['session']['customer']['first'] : '') 
                                . "\">"
                            . "<div class=\"error-msg\" id=\"{$iprefix}first_error\"></div>"
                        . "</div>"
                        . "<div class=\"ciniki-input ciniki-input-last\">"
                            . "<label for=\"{$iprefix}last\">Last Name</label>"
                            . "<input class=\"\" id=\"{$iprefix}last\" name=\"{$iprefix}last\" value=\""
                                . (isset($request['session']['customer']['last']) ? $request['session']['customer']['last'] : '') 
                                . "\">"
                            . "<div class=\"error-msg\" id=\"{$iprefix}last_error\"></div>"
                        . "</div>"
                        . "<div class=\"ciniki-input\">"
                            . "<label for=\"{$iprefix}email\">Email</label>"
                            . "<input class=\"\" id=\"{$iprefix}email\" type=\"email\" name=\"{$iprefix}email\" value=\""
                                . (isset($request['session']['customer']['email']) ? $request['session']['customer']['email'] : '') 
                                . "\">"
                            . "<div class=\"error-msg\" id=\"{$iprefix}email_error\"></div>"
                        . "</div>"
                    . "</div>"
                    . "<a id=\"{$iprefix}ciniki_paynow_button\" class=\"button\" onclick=\"{$jsname}.paynow();\">{$pay_button_label}</a>"
                    . "<a id=\"{$iprefix}ciniki_cancel_button\" class=\"button cancel-button\" onclick=\"{$jsname}.close();\">Cancel</a>"
                . "</div>"
                . "<div class=\"stripe-form-elements\">"
                    . "<div id=\"stripe-payment-element\"></div>"
                    . "<button id=\"stripe-payment-button\" class=\"button\"></button>"
                    . "<div id=\"stripe-error-messages\" class=\"hidden\"></div>"
                    . "<a id=\"stripe-cancel-button\" class=\"button cancel-button\" onclick=\"{$jsname}.cancel();\">Cancel</a>"
                . "</div>"
                . "</div></div>'"
                . ");"
            . "C.gE('page-container').appendChild({$jsname}.form);"
            . "};"
            //
            // Functions for collecting ticket quantities, names, emails
            //
            . "{$jsname}.tPrices = " . json_encode($prices) . ";"
            . "{$jsname}.qUpdt = function(){"
                . "var t=0;"
                . "for(var i in {$jsname}.tPrices) {"
                    . "var q=C.gE('{$iprefix}' + {$jsname}.tPrices[i].price_id).value;"
                    . "if(q!=null&&q>0){"
                        . "t+=parseFloat({$jsname}.tPrices[i].unit_amount)*q;"
                    . "}"
                    . "C.gE('{$iprefix}ciniki_paynow_button').innerHTML='Pay ' + C.fD(t,2);"
                    . "C.gE('{$iprefix}ticket_total').innerHTML=C.fD(t,2);"
                . "}"
            . "};"
            . "{$jsname}.paynow = function(){"
                . "var errs=0;"
                . "var args={'invoice':'new'};"
                . "var objs={};"
                . "var tq=0;" // Total quantity to see if there is at least 1 ticket
                . "for(var i in {$jsname}.tPrices) {"
                    . "var q=C.gE('{$iprefix}' + {$jsname}.tPrices[i].price_id).value;"
                    . "if(q>0){"
                        . "tq+=q;"
                        . "objs[i]={'object':{$jsname}.tPrices[i].object,"
                            . "'object_id':{$jsname}.tPrices[i].object_id,"
                            . "'price_id':{$jsname}.tPrices[i].price_id,"
                            . "'quantity':q"
                            . "};"
                    . "}"
                . "}"
                . "var e=C.gE('{$iprefix}quantity_error');"
                . "if(tq==0){"
                    . "e.classList.remove('hidden');"
                    . "errs=1;"
                . "}else{"
                    . "e.classList.add('hidden');"
                . "}"
                // Check to make sure there is a quantity, first, last and email
                . "var e=C.gE('{$iprefix}first');"
                . "if(e.value==''){"
                    . "e.parentNode.classList.add('error');"
                    . "var e=C.gE('{$iprefix}first_error');"
                    . "e.innerHTML='You must provide a first name.';"
                    . "errs=1;"
                . "}else{"
                    . "e.parentNode.classList.remove('error');"
                    . "args['first']=e.value;"
                . "}"
                . "var e=C.gE('{$iprefix}last');"
                . "if(e.value==''){"
                    . "e.parentNode.classList.add('error');"
                    . "var e=C.gE('{$iprefix}last_error');"
                    . "e.innerHTML='You must provide a last name.';"
                    . "errs=1;"
                . "}else{"
                    . "e.parentNode.classList.remove('error');"
                    . "args['last']=e.value;"
                . "}"
                . "var e=C.gE('{$iprefix}email');"
                . "var re=/\S+@\S+\.\S+/;"
                . "if(e.value==''||!re.test(e.value)){"
                    . "e.parentNode.classList.add('error');"
                    . "var e=C.gE('{$iprefix}email_error');"
                    . "e.innerHTML='You must provide a valid email address.';"
                    . "errs=1;"
                . "}else{"
                    . "e.parentNode.classList.remove('error');"
                    . "args['email']=e.value;"
                . "}"
                . "if(errs==0){"
                    . "{$jsname}.form.classList.add('waiting');"    // Show spinner
                    . "{$jsname}.form.classList.remove('tickets');" // Hide tickets
                    . "var fdata = new FormData();"
                    . "fdata.append('objects', JSON.stringify(objs));"
                    . "C.postFDBg('{$request['ssl_domain_base_url']}/cpi/ciniki/sapos/invoiceCheckoutCreate', args, fdata, function(rsp) {"
                        . "if(rsp.stat==null){"
                            . "{$jsname}.form.remove();"
                            . "C.alert('Unable to initialize payment system. Please refresh the page and try again.');"
                            . "return false;"
                        . "}"
                        . "if(rsp.stat!='ok'){"
                            . "{$jsname}.form.remove();"
                            . "C.alert(rsp.err.msg);"
                            . "return false;"
                        . "}"
                        // All is good, then open payment 
                        . "if(rsp.invoice_id!=null&&rsp.invoice_id>0){"
                            . "{$jsname}.openPayment(rsp.invoice_id,rsp.total_amount);"
                        . "}"
                        . "});"
                . "}"
            . "};"
            . "{$jsname}.close = function(){"
                . "{$jsname}.form.remove();"
            . "};"
            //
            // Functions for collecting stripe payment
            //
            . "{$jsname}.openPayment = function(i){"
                // Set a timeout incase there are failures with ciniki setup
                . "{$jsname}.timer = setTimeout({$jsname}.timeout, 60000);"
                . "C.getBg('{$request['ssl_domain_base_url']}/cpi/ciniki/sapos/invoiceStripeIntentCreate', {'invoice_id':i}, function(rsp) {"
                    . "clearTimeout({$jsname}.timer);"
                    . "if(rsp.stat==null){"
                        . "{$jsname}.form.remove();"
                        . "C.alert('Unable to initialize payment system. Please refresh the page and try again.');"
                        . "return false;"
                    . "}"
                    . "else if(rsp.stat!='ok'){"
                        . "{$jsname}.form.remove();"
                        . "C.alert(rsp.err.msg);"
                        . "return false;"
                    . "}"
                    // Set a timeout incase there are failures with the stripe setup (2 minutes)
                    . "{$jsname}.timer = setTimeout({$jsname}.stripetimeout, 120000);"
                    . "const stripe = Stripe('{$request['site']['settings']['stripe-pk']}');"
                    . "{$jsname}.intent_id = rsp.intent_id;"
                    . "const elements = stripe.elements({"
                        . "clientSecret: rsp.payment_secret,"
                        . "});"
                    . "{$jsname}.paymentElement = elements.create('payment');"
                    . "C.gE('stripe-payment-button').innerHTML = rsp.button_text;"
                    . "{$jsname}.paymentElement.on('ready', function(event){"
                        . "clearTimeout({$jsname}.timer);"
                        // Setup timeout incase they take longer than 10 minutes to fill out the form
                        . "{$jsname}.timer = setTimeout({$jsname}.formtimeout, 600000);"
                        . "{$jsname}.form.classList.add('loaded');"
                        . "{$jsname}.form.classList.remove('waiting');"
                        . "});"
                    . "{$jsname}.paymentElement.mount('#stripe-payment-element');"
                    . "{$jsname}.form.addEventListener('submit', async(e) => {"
                        . "e.preventDefault();"
                        . "const {error} = await stripe.confirmPayment({"
                            . "elements,"
                            . "confirmParams: {"
                                . "return_url: '{$args['return_url']}'"
                            . "}"
                        . "});"
                        . "if(error) {"
                            . "const messages = C.gE('stripe-error-messages');"
                            . "messages.classList.remove('hidden');"
                            . "messages.innerText = error.message;"
                        . "}"
                    . "});"
                    . "});"
            . "};"
            . "{$jsname}.timeout = function(){"
                . "clearTimeout({$jsname}.timer);"
                . "C.alert('Communitcation Error - We are unable to connect to payment services at this time, please reload this page and try again or contact us for help.');"
            . "};"
            . "{$jsname}.stripetimeout = function(){"
                . "clearTimeout({$jsname}.timer);"
                . "{$jsname}.cancel();"
                . "C.alert('Session Timeout - You will need to restart the payment process.');"
            . "};"
            . "{$jsname}.formtimeout = function(){"
                . "clearTimeout({$jsname}.timer);"
                . "{$jsname}.cancel();"
                . "C.alert('Session Timeout - You will need to restart the payment process.');"
            . "};"
            . "{$jsname}.cancel = function(){"
                . "{$jsname}.paymentElement.destroy();"
                . "{$jsname}.form.remove();"
                . "C.getBg('{$request['ssl_domain_base_url']}/cpi/ciniki/sapos/invoiceStripeIntentCancel', {intent_id:{$jsname}.intent_id}, function(rsp) {"
                    . "return true;"
                . "});"
            . "};";

    }

    return array('stat'=>'ok', 'js'=>"{$jsname}.checkout();");
}
?>
