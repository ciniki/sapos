<?php
//
// Description
// -----------
// Check and invoice item form has been filled out and validated
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_wng_cartAddressCheck(&$ciniki, $tnid, &$request, $args) {

    if( !isset($args['cart']['customer_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.498', 'msg'=>'No customer for cart'));
    }
    $cart = $args['cart'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, [
        'customer_id' => $cart['customer_id'],
        'addresses' => 'mailing',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.499', 'msg'=>'Unable to find customer', 'err'=>$rc['err']));
    }
    $customer = $rc['customer'];
    $customer_address = isset($rc['customer']['addresses'][0]) ? $rc['customer']['addresses'][0] : array();

    $fields = [
        'actions' => [
            'id' => 'action',
            'label' => '',
            'ftype' => 'hidden',
            'value' => 'submit',
            ],
        'billing_address1' => [
            'id' => 'billing_address1',
            'customer_field' => 'address1',
            'label' => 'Address 1',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'large',
            'value' => $cart['billing_address1'],
            ],
        'billing_address2' => [
            'id' => 'billing_address2',
            'customer_field' => 'address2',
            'label' => 'Address 2',
            'ftype' => 'text',
            'required' => 'no',
            'size' => 'large',
            'value' => $cart['billing_address2'],
            ],
        'billing_city' => [
            'id' => 'billing_city',
            'customer_field' => 'city',
            'label' => 'City',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'medium',
            'value' => $cart['billing_city'],
            ],
        'billing_province' => [
            'id' => 'billing_province',
            'customer_field' => 'province',
            'label' => 'Province/State',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'medium',
            'value' => $cart['billing_province'],
            ],
        'billing_postal' => [
            'id' => 'billing_postal',
            'customer_field' => 'postal',
            'label' => 'Postal Code/Zip Code',
            'ftype' => 'text',
            'required' => 'yes',
            'size' => 'medium',
            'value' => $cart['billing_postal'],
            ],
        'billing_country' => [
            'id' => 'billing_country',
            'customer_field' => 'country',
            'label' => 'Country',
            'ftype' => 'text',
            'required' => 'no',
            'size' => 'medium',
            'value' => $cart['billing_country'],
            ],
        ];
   
    //
    // Check for any updates from form submission or customer addresses
    //
    $update_args = [];
    $errors = [];
    if( isset($_POST['f-action']) && $_POST['f-action'] == 'submit' ) {
        foreach($fields as $fid => $f) {
            if( $f['ftype'] == 'hidden' ) {
                continue;
            }
            if( isset($_POST["f-{$fid}"]) && $_POST["f-{$fid}"] != $cart[$fid] ) {
                $update_args[$fid] = $_POST["f-{$fid}"];
                $cart[$fid] = $_POST["f-{$fid}"];
                $request['session']['cart'][$fid] = $_POST["f-{$fid}"];
                $fields[$fid]['value'] = $_POST["f-{$fid}"];
            }

            if( isset($f['required']) && $f['required'] == 'yes' 
                && $cart[$fid] == ''
                ) {
                $errors[] = ['msg' => "You must specify {$f['label']}"];
            }
        }
    } else {
        //
        // Check if customer billing address can be copied to cart
        //
        foreach($fields as $fid => $f) {
            if( $f['ftype'] == 'hidden' ) {
                continue;
            }
            if( (!isset($cart[$fid]) || $cart[$fid] == '')
                && isset($customer_address[$f['customer_field']]) && $customer_address[$f['customer_field']] != '' 
                ) {
                $update_args[$fid] = $customer_address[$f['customer_field']];
                $cart[$fid] = $customer_address[$f['customer_field']];
                $request['session']['cart'][$fid] = $customer_address[$f['customer_field']];
                $fields[$fid]['value'] = $customer_address[$f['customer_field']];
            }
        }
    }

    $form_errors = ''; 
    if( count($errors) > 0 ) {
        foreach($errors as $err) {
            $form_errors .= ($form_errors != '' ? '<br/>' : '') . $err['msg'];
        }
    }

    // FIXME: Add check if updates required to customer billing address so on record for next time


    //
    // Check if there is updates
    //
    if( count($errors) == 0 && count($update_args) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', $cart['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.500', 'msg'=>'Unable to update the cart', 'err'=>$rc['err']));
        }
        $updated = 'yes';
        //
        // Check if customer is missing billing and added billing address
        //
        if( !isset($customer['addresses']) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.customers.address', [
                'customer_id' => $cart['customer_id'],
                'flags' => 0x04,
                'address1' => $cart['billing_address1'],
                'address2' => $cart['billing_address2'],
                'city' => $cart['billing_city'],
                'province' => $cart['billing_province'],
                'postal' => $cart['billing_postal'],
                'country' => $cart['billing_country'],
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.501', 'msg'=>'Unable to save the customer address', 'err'=>$rc['err']));
            }
        }
    }

    if( count($errors) > 0 
        || $cart['billing_address1'] == ''
        || $cart['billing_city'] == ''
        || $cart['billing_province'] == ''
        || $cart['billing_postal'] == '' 
        ) {

        $blocks[] = [
            'type' => 'form',
            'guidelines' => isset($args['msg']) ? $args['msg'] : '',
            'title' => 'Billing Address',
            'checkout' => 'yes',
            'class' => 'limit-width limit-width-60',
            'problem-list' => $form_errors,
            'cancel-label' => 'Cancel',
            'submit-label' => 'Checkout',
            'fields' => $fields,
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }
    

    if( isset($updated) && $updated == 'yes' ) {
        return array('stat'=>'ok', 'cart'=>$cart);
    }

    return array('stat'=>'ok');
}
?>
