<?php
//
// Description
// -----------
// This method will add a new shipping profile rate for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Shipping Profile Rate to.
//
// Returns
// -------
//
function ciniki_sapos_shippingRateAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'profile_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Profile'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'min_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Minimum Quantity'),
        'max_quantity'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Maximum Quantity'),
        'min_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Minimum $ Amount'),
        'max_amount'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Maximum $ Amount'),
        'shipping_amount_us'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping to US'),
        'shipping_amount_ca'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping to Canada'),
        'shipping_amount_intl'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Shipping International'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    if( isset($args['shipping_amount_us']) ) {
        $args['shipping_amount_us'] = preg_replace("/[^0-9\.]/", '', $args['shipping_amount_us']);
    }
    if( isset($args['shipping_amount_ca']) ) {
        $args['shipping_amount_ca'] = preg_replace("/[^0-9\.]/", '', $args['shipping_amount_ca']);
    }
    if( isset($args['shipping_amount_intl']) ) {
        $args['shipping_amount_intl'] = preg_replace("/[^0-9\.]/", '', $args['shipping_amount_intl']);
    }

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.shippingRateAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the shipping profile rate to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.shippingrate', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    $rate_id = $rc['id'];

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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.sapos.shippingRate', 'object_id'=>$rate_id));

    return array('stat'=>'ok', 'id'=>$rate_id);
}
?>
