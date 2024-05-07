<?php
//
// Description
// -----------
// This function will create a customer in Stripe and link to customer in Ciniki
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_stripeCustomerCreate(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
    $rc = ciniki_customers_hooks_customerDetails2($ciniki, $tnid, [
        'customer_id' => $args['customer_id'],
        'addresses' => 'yes',
        ]);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.469', 'msg'=>'Invalid Customer', 'err'=>$rc['err']));
    }
    $customer = $rc['customer'];

    //
    // Build the arguments to send to stripe to create customer
    //
    $customer_args = [
        'name' => $customer['display_name'],
        'metadata' => [
            'customer_uuid' => $customer['uuid'],
            ],
        ];

    if( isset($customer['emails'][0]['address']) ) {
        $customer_args['email'] = $customer['emails'][0]['address'];
    }

    if( isset($args['stripe']) ) {
        $stripe = $args['stripe'];
    }
    else {
        //
        // Initialize Stripe Library
        //
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/stripev14/init.php');

        $stripe = new \Stripe\StripeClient([
            'api_key' => $request['site']['settings']['stripe-sk'],
            'stripe_version' => '2024-04-10',
            ]);
    }

    //
    // Create the customer in stripe
    //
    try {
        $stripe_customer = $stripe->customers->create($customer_args);
    } catch(Exception $e) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.441', 'msg'=>$e->getMessage()));
    }
    
    //
    // Save the stripe customer id for future use
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.customers.customer', $customer['id'], array(
        'stripe_customer_id' => $stripe_customer->id,
        ), 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.455', 'msg'=>'Unable to update customer account', 'err'=>$rc['err']));
    }
    $invoice['customer']['stripe_customer_id'] = $stripe_customer->id;

    return array('stat'=>'ok', 'stripe_customer_id'=>$stripe_customer->id);
}
?>
