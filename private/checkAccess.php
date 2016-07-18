<?php
//
// Description
// -----------
// This function will check if the user has access to the pos module.  
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_checkAccess(&$ciniki, $business_id, $method) {
    //
    // Check if the business is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'checkModuleAccess');
    $rc = ciniki_businesses_checkModuleAccess($ciniki, $business_id, 'ciniki', 'sapos');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1688', 'msg'=>'Permission denied', 'err'=>$rc['err']));
    }
    $modules = $rc['modules'];

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1271', 'msg'=>'No permissions granted'));
    }

    //
    // Get the list of permission_groups the user is a part of
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT permission_group FROM ciniki_business_users "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "    // Active user
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.businesses', 'groups', 'permission_group');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['groups']) ) {
        //
        // Sysadmins are allowed full access
        //
        if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
            return array('stat'=>'ok', 'modules'=>$modules);
        }

        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2023', 'msg'=>'Access denied'));
    }
    $groups = $rc['groups'];

    $perms = 0;
    if( in_array('owners', $groups) ) { $perms |= 0x01; }
    if( in_array('employees', $groups) ) { $perms |= 0x02; }
    if( in_array('salesreps', $groups) ) { $perms |= 0x04; }
    $ciniki['business']['user']['perms'] = $perms;

    //
    // If the user is a part of owners they have access to everything
    //
    if( ($perms&0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // Sysadmins are allowed full access
    //
    if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
        return array('stat'=>'ok', 'modules'=>$modules);
    }

    //
    // Employee methods
    //
    if( ($perms&0x02) == 0x02 
        && $method != 'ciniki.sapos.settingsGet' 
        && $method != 'ciniki.sapos.settingsUpdate'
        && $method != 'ciniki.sapos.settingsHistory'
        ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // If the user is part of the salesreps, ensure they have access to request method
    //
    $salesreps_methods = array(
        'ciniki.sapos.invoiceAdd',
        'ciniki.sapos.invoiceGet',
        'ciniki.sapos.invoiceItemAdd',
        'ciniki.sapos.invoiceItemGet',
        'ciniki.sapos.invoiceItemSearch',
        'ciniki.sapos.invoiceItemUpdate',
        'ciniki.sapos.invoiceItemDelete',
        'ciniki.sapos.invoiceDelete',
        'ciniki.sapos.invoiceUpdate',
        'ciniki.sapos.invoiceAction',
        'ciniki.sapos.history',
        'ciniki.sapos.shipmentGet',
        );
    if( in_array($method, $salesreps_methods) && ($perms&0x04) == 0x04 ) {
        return array('stat'=>'ok', 'modules'=>$modules, 'perms'=>$perms);
    }

    //
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1258', 'msg'=>'Access denied'));
}
?>
