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
function ciniki_sapos_wng_stripeCheckoutCreate(&$ciniki, $tnid, &$request, $args) {

    if( !isset($request['response']['js']) ) {
        $request['response']['js'] = '';
    }
    if( !isset($request['response']['head']['scripts']['stripe']) ) {
        $request['response']['head']['scripts']['stripe'] = array(
            'src' => 'https://js.stripe.com/v3/',
            );
    }
    if( strstr($request['response']['js'], "C.stripe = {") === false ) {
        
        $cpi_args = '';
        $cpi_args .= ($cpi_args != '' ? ', ' : '') . "'invoice_id':" . $args['invoice_id'];

        $request['response']['js'] .= "C.stripe = {};"
            . "C.stripe.checkout = function() {"
            // 
            // Start a new stripe session via API
            //
            . "C.stripe.form = C.aE('form', 'stripe-payment-form', 'block-stripecheckout waiting', '<div class=\"wrap\"><div class=\"content\">"
                . '<div class="spinner"><svg width="48" height="48" stroke="#000" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><style>.spinner_V8m1{transform-origin:center;animation:spinner_zKoa 2s linear infinite}.spinner_V8m1 circle{stroke-linecap:round;animation:spinner_YpZS 1.5s ease-in-out infinite}@keyframes spinner_zKoa{100%{transform:rotate(360deg)}}@keyframes spinner_YpZS{0%{stroke-dasharray:0 150;stroke-dashoffset:0}47.5%{stroke-dasharray:42 150;stroke-dashoffset:-16}95%,100%{stroke-dasharray:42 150;stroke-dashoffset:-59}}</style><g class="spinner_V8m1"><circle cx="12" cy="12" r="9.5" fill="none" stroke-width="3"></circle></g></svg></div>'
                . "<div class=\"stripe-form-elements\">"
                . "<div id=\"stripe-payment-element\"></div>"
                . "<button id=\"stripe-payment-button\" class=\"button\"></button>"
                . "<div id=\"stripe-error-messages\" class=\"hidden\"></div>"
                . "<a id=\"stripe-cancel-button\" class=\"button cancel-button\" onclick=\"C.stripe.cancel();\">Cancel</a>"
                . "</div></div></div>'"
                . ");"
            . "C.gE('page-container').appendChild(C.stripe.form);"
            // Set a timeout incase there are failures with ciniki setup
            . "C.stripe.timer = setTimeout(C.stripe.timeout, 60000);"
            . "C.getBg('{$request['ssl_domain_base_url']}/cpi/ciniki/sapos/invoiceStripeIntentCreate', {{$cpi_args}}, function(rsp) {"
                . "clearTimeout(C.stripe.timer);"
                . "if(rsp.stat==null){"
                    . "C.stripe.form.remove();"
                    . "C.alert('Unable to initialize payment system. Please refresh the page and try again.');"
                    . "return false;"
                . "}"
                . "else if(rsp.stat!='ok'){"
                    . "C.stripe.form.remove();"
                    . "C.alert(rsp.err.msg);"
                    . "return false;"
                . "}"
                // Set a timeout incase there are failures with the stripe setup (2 minutes)
                . "C.stripe.timer = setTimeout(C.stripe.stripetimeout, 120000);"
                . "const stripe = Stripe('{$request['site']['settings']['stripe-pk']}');"
                . "C.stripe.intent_id = rsp.intent_id;"
                . "const elements = stripe.elements({"
                    . "clientSecret: rsp.payment_secret,"
                    . "});"
                . "C.stripe.paymentElement = elements.create('payment');"
                . "C.gE('stripe-payment-button').innerHTML = rsp.button_text;"
                . "C.stripe.paymentElement.on('ready', function(event){"
                    . "clearTimeout(C.stripe.timer);"
                    // Setup timeout incase they take longer than 10 minutes to fill out the form
                    . "C.stripe.timer = setTimeout(C.stripe.formtimeout, 600000);"
                    . "C.stripe.form.classList.add('loaded');"
                    . "C.stripe.form.classList.remove('waiting');"
                    . "});"
                . "C.stripe.paymentElement.mount('#stripe-payment-element');"
                . "C.stripe.form.addEventListener('submit', async(e) => {"
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
            . "C.stripe.timeout = function(){"
                . "clearTimeout(C.stripe.timer);"
                . "C.alert('Communitcation Error - We are unable to connect to payment services at this time, please reload this page and try again or contact us for help.');"
            . "};"
            . "C.stripe.stripetimeout = function(){"
                . "clearTimeout(C.stripe.timer);"
                . "C.stripe.cancel();"
                . "C.alert('Session Timeout - You will need to restart the payment process.');"
            . "};"
            . "C.stripe.formtimeout = function(){"
                . "clearTimeout(C.stripe.timer);"
                . "C.stripe.cancel();"
                . "C.alert('Session Timeout - You will need to restart the payment process.');"
            . "};"
            . "C.stripe.cancel = function(){"
                . "C.stripe.paymentElement.destroy();"
                . "C.stripe.form.remove();"
                . "C.getBg('{$request['ssl_domain_base_url']}/cpi/ciniki/sapos/invoiceStripeIntentCancel', {intent_id:C.stripe.intent_id}, function(rsp) {"
                    . "return true;"
                . "});"
            . "};";

    }

    return array('stat'=>'ok', 'js'=>"C.stripe.checkout();");
}
?>
