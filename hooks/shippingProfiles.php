<?php
//
// Description
// -----------
// Return the list of shipping profiles
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_hooks_shippingProfiles(&$ciniki, $tnid, $args) {

    //
    // Get the list of profiles
    //
    $strsql = "SELECT ciniki_sapos_shipping_profiles.id, "
        . "ciniki_sapos_shipping_profiles.name "
        . "FROM ciniki_sapos_shipping_profiles "
        . "WHERE ciniki_sapos_shipping_profiles.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'profiles', 'fname'=>'id', 
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $profiles = isset($rc['profiles']) ? $rc['profiles'] : array();

    return array('stat'=>'ok', 'profiles'=>$profiles);
}
?>
