<?php
//
// Description
// ===========
// This method will return all the information about an shipping profile rate.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the shipping profile rate is attached to.
// rate_id:          The ID of the shipping profile rate to get the details for.
//
// Returns
// -------
//
function ciniki_sapos_shippingRateGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'rate_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shipping Profile Rate'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shippingRateGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Shipping Profile Rate
    //
    if( $args['rate_id'] == 0 ) {
        $rate = array('id'=>0,
            'profile_id'=>'',
            'flags'=>'0',
            'min_quantity'=>'',
            'max_quantity'=>'',
            'min_amount'=>'',
            'max_amount'=>'',
            'shipping_amount_us'=>'',
            'shipping_amount_ca'=>'',
            'shipping_amount_intl'=>'',
        );
    }

    //
    // Get the details for an existing Shipping Profile Rate
    //
    else {
        $strsql = "SELECT ciniki_sapos_shipping_rates.id, "
            . "ciniki_sapos_shipping_rates.profile_id, "
            . "ciniki_sapos_shipping_rates.flags, "
            . "ciniki_sapos_shipping_rates.min_quantity, "
            . "ciniki_sapos_shipping_rates.max_quantity, "
            . "ciniki_sapos_shipping_rates.min_amount, "
            . "ciniki_sapos_shipping_rates.max_amount, "
            . "ciniki_sapos_shipping_rates.shipping_amount_us, "
            . "ciniki_sapos_shipping_rates.shipping_amount_ca, "
            . "ciniki_sapos_shipping_rates.shipping_amount_intl "
            . "FROM ciniki_sapos_shipping_rates "
            . "WHERE ciniki_sapos_shipping_rates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sapos_shipping_rates.id = '" . ciniki_core_dbQuote($ciniki, $args['rate_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'rates', 'fname'=>'id', 
                'fields'=>array('profile_id', 'flags', 'min_quantity', 'max_quantity', 'min_amount', 'max_amount', 'shipping_amount_us', 'shipping_amount_ca', 'shipping_amount_intl'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.301', 'msg'=>'Shipping Profile Rate not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['rates'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.302', 'msg'=>'Unable to find Shipping Profile Rate'));
        }
        $rate = $rc['rates'][0];

        //
        // Format the display variables
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shippingRateFormat');
        $rc = ciniki_sapos_shippingRateFormat($ciniki, $args['tnid'], $rate);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.306', 'msg'=>'Unable to format rate', 'err'=>$rc['err']));
        }
        $rate['shipping_amount_us'] = $rc['rate']['shipping_amount_us_display'];
        $rate['shipping_amount_ca'] = $rc['rate']['shipping_amount_ca_display'];
        $rate['shipping_amount_intl'] = $rc['rate']['shipping_amount_intl_display'];
    }

    return array('stat'=>'ok', 'rate'=>$rate);
}
?>
