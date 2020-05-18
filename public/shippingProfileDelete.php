<?php
//
// Description
// -----------
// This method will delete an shipping profile.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the shipping profile is attached to.
// profile_id:            The ID of the shipping profile to be removed.
//
// Returns
// -------
//
function ciniki_sapos_shippingProfileDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'profile_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Shipping Profile'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shippingProfileDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the shipping profile
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_sapos_shipping_profiles "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['profile_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'profile');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['profile']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.293', 'msg'=>'Shipping Profile does not exist.'));
    }
    $profile = $rc['profile'];

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT COUNT(profile_id) "
        . "FROM ciniki_sapos_shipping_rates "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND profile_id = '" . ciniki_core_dbQuote($ciniki, $args['profile_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.309', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    if( isset($rc['num']) && $rc['num'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.303', 'msg'=>'Rates must be deleted for this profile first.'));
    }

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.sapos.shippingProfile', $args['profile_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.294', 'msg'=>'Unable to check if the shipping profile is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.295', 'msg'=>'The shipping profile is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the profile
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.sapos.shippingprofile',
        $args['profile_id'], $profile['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok');
}
?>
