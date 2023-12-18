<?php
//
// Description
// ===========
// This function is the final call in express checkout to collect the payment.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_wng_stripeCustomerCharge(&$ciniki, $tnid, $request, $args) {

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    if( !isset($args['stripe-token']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.367', 'msg'=>'Internal Error'));
    }
    if( !isset($args['stripe-email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.368', 'msg'=>'Internal Error'));
    }
    if( !isset($args['charge-amount']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.369', 'msg'=>'Internal Error'));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'core', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_currency = $rc['settings']['intl-default-currency'];

    if( !isset($settings['stripe-sk']) || $settings['stripe-sk'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.371', 'msg'=>'Stripe processing not configured'));
    }

    //
    // Load stripe library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/Stripe/init.php');
    \Stripe\Stripe::setApiKey($settings['stripe-sk']);
//    \Stripe\Stripe::setApiVersion('2023-10-16');

    //
    // Create customer
    //
    try {
        $customer = \Stripe\Customer::create(array(
            'email'=>$args['stripe-email'],
            'source'=>$args['stripe-token'],
            ));
    } catch( Exception $e) {
        error_log("STRIPE-ERR: Unable to setup customer: " . $e->getMessage());
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.372', 'msg'=>$e->getMessage()));
    }
    
    //
    // Process the charge
    //
    if( $args['charge-amount'] > 0 ) {
        try {
            $charge = \Stripe\Charge::create(array(
                'customer' => $customer->id,
                'amount'   => number_format($args['charge-amount'] * 100, 0, '', ''),
                'currency' => $intl_currency,
                'metadata' => [
                    'invoice_number' => $args['invoice_number'],
                    ],
                ));
        } catch( Exception $e) {
            error_log("STRIPE-ERR: Unable to charge customer: " . $e->getMessage());
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.373', 'msg'=>$e->getMessage()));
        }

        //
        // Check for a balance transaction
        //
        if( isset($charge['balance_transaction']) ) {
            try {
                $balance = \Stripe\BalanceTransaction::retrieve($charge['balance_transaction']);
            } catch( Exception $e) {
                error_log("STRIPE-ERR: Unable to get balance: " . $e->getMessage());
            }
        }
    } else {
        //
        // If nothing to charge, store the customer details as part of the transaction.
        //
        $charge = $customer;
    }

    //
    // Add the transaction to the ciniki_sapos_transactions
    //
    if( isset($args['invoice_id']) ) {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        if( isset($balance['fee']) ) {
            $fees = bcdiv($balance['fee'], 100, 2);
        } else {
            $fees = 0;
        }
        if( isset($charge['outcome']['seller_message']) ) {
            $gateway_status = $charge['outcome']['seller_message'];
        } else {
            $gateway_status = 'Unknown';
        }
        $transaction_args = array(
            'invoice_id'=>$args['invoice_id'],
            'status'=>40,
            'transaction_type'=>20,
            'transaction_date'=>$dt->format('Y-m-d H:i:s'),
            'source'=>30,
            'customer_amount'=>$args['charge-amount'],
            'transaction_fees'=>$fees,
            'tenant_amount'=>bcsub($args['charge-amount'], $fees, 6),
            'user_id'=>0,
            'notes'=>'',
            'gateway'=>30,
            'gateway_token'=>$charge['id'],
            'gateway_status'=>$gateway_status,
            'gateway_response'=>serialize($charge),
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.sapos.transaction', $transaction_args);
        if( $rc['stat'] != 'ok' ) {
            error_log("STRIPE-ERR: Unable to record transaction: " . $e->getMessage());
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.374', 'msg'=>$e->getMessage()));
        }
    }

    return array('stat'=>'ok');
}
?>
