<?php
//
// Description
// -----------
// This method will return the list of Shipping Profile Rates for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Shipping Profile Rate for.
//
// Returns
// -------
//
function ciniki_sapos_shippingRateList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shippingRateList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of rates
    //
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
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'rates', 'fname'=>'id', 
            'fields'=>array('id', 'profile_id', 'flags', 'min_quantity', 'max_quantity', 'min_amount', 'max_amount', 'shipping_amount_us', 'shipping_amount_ca', 'shipping_amount_intl')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rates']) ) {
        $rates = $rc['rates'];
        $rate_ids = array();
        foreach($rates as $iid => $rate) {
            $rate_ids[] = $rate['id'];
        }
    } else {
        $rates = array();
        $rate_ids = array();
    }

    return array('stat'=>'ok', 'rates'=>$rates, 'nplist'=>$rate_ids);
}
?>
