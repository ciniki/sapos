<?php
//
// Description
// -----------
// This method searchs for a Shipping Profiles for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Shipping Profile for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_sapos_shippingProfileSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shippingProfileSearch');
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
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
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
        foreach($profiles as $iid => $profile) {
            $profile_ids[] = $profile['id'];
        }
    } else {
        $profiles = array();
        $profile_ids = array();
    }

    return array('stat'=>'ok', 'profiles'=>$profiles, 'nplist'=>$profile_ids);
}
?>
