<?php
//
// Description
// -----------
// This method will return the list of Shipping Profiles for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Shipping Profile for.
//
// Returns
// -------
//
function ciniki_sapos_shippingProfilesGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'profile_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shippingProfilesGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of profiles
    //
    $strsql = "SELECT ciniki_sapos_shipping_profiles.id, "
        . "ciniki_sapos_shipping_profiles.name "
        . "FROM ciniki_sapos_shipping_profiles "
        . "WHERE ciniki_sapos_shipping_profiles.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'profiles', 'fname'=>'id', 
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['profiles']) ) {
        $profiles = $rc['profiles'];
        $profile_ids = array();
        foreach($profiles as $pid => $profile) {
            $profile_ids[] = $profile['id'];
        }
    } else {
        $profiles = array();
        $profile_ids = array();
    }

    $rsp = array('stat'=>'ok', 'profiles'=>$profiles, 'nplist'=>$profile_ids);

    //
    // Get the list of rates for a profile
    //
    if( isset($args['profile_id']) && $args['profile_id'] > 0 ) {
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
            . "AND ciniki_sapos_shipping_rates.profile_id = '" . ciniki_core_dbQuote($ciniki, $args['profile_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'rates', 'fname'=>'id', 
                'fields'=>array('id', 'profile_id', 'flags', 
                    'min_quantity', 'max_quantity', 'min_amount', 'max_amount', 
                    'shipping_amount_us', 'shipping_amount_ca', 'shipping_amount_intl')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rates']) ) {
            $rsp['rates'] = $rc['rates'];
            $rsp['rate_ids'] = array();
            foreach($rsp['rates'] as $rid => $rate) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shippingRateFormat');
                $rc = ciniki_sapos_shippingRateFormat($ciniki, $args['tnid'], $rate);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.304', 'msg'=>'', 'err'=>$rc['err']));
                }
                $rsp['rates'][$rid] = $rc['rate'];
                $rsp['rate_ids'][] = $rate['id'];
            }
        } else {
            $rsp['rates'] = array();
            $rsp['rate_ids'] = array();
        }
    }

    return $rsp;
}
?>
