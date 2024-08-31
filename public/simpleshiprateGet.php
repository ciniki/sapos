<?php
//
// Description
// ===========
// This method will return all the information about an shipping rate.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the shipping rate is attached to.
// rate_id:          The ID of the shipping rate to get the details for.
//
// Returns
// -------
//
function ciniki_sapos_simpleshiprateGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'rate_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Shipping Rate'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.simpleshiprateGet');
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
    // Return default for new Shipping Rate
    //
    if( $args['rate_id'] == 0 ) {
        $simpleshiprate = array('id'=>0,
            'country'=>'',
            'province'=>'',
            'city'=>'',
            'minimum_amount'=>'0',
            'rate'=>'',
        );
    }

    //
    // Get the details for an existing Shipping Rate
    //
    else {
        $strsql = "SELECT ciniki_sapos_simpleshiprates.id, "
            . "ciniki_sapos_simpleshiprates.country, "
            . "ciniki_sapos_simpleshiprates.province, "
            . "ciniki_sapos_simpleshiprates.city, "
            . "ciniki_sapos_simpleshiprates.minimum_amount, "
            . "ciniki_sapos_simpleshiprates.rate "
            . "FROM ciniki_sapos_simpleshiprates "
            . "WHERE ciniki_sapos_simpleshiprates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sapos_simpleshiprates.id = '" . ciniki_core_dbQuote($ciniki, $args['rate_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'simpleshiprates', 'fname'=>'id', 
                'fields'=>array('country', 'province', 'city', 'minimum_amount', 'rate'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.283', 'msg'=>'Shipping Rate not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['simpleshiprates'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.284', 'msg'=>'Unable to find Shipping Rate'));
        }
        $simpleshiprate = $rc['simpleshiprates'][0];
        $simpleshiprate['minimum_amount'] = '$' . number_format($simpleshiprate['minimum_amount'], 2);
        $simpleshiprate['rate'] = '$' . number_format($simpleshiprate['rate'], 2);
    }

    return array('stat'=>'ok', 'simpleshiprate'=>$simpleshiprate);
}
?>
