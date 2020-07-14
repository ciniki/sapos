<?php
//
// Description
// -----------
// This function will check if the user has access to the pos module.  
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_checkAccess(&$ciniki, $tnid, $method) {
    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'sapos');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.13', 'msg'=>'Permission denied', 'err'=>$rc['err']));
    }
    $modules = $rc['modules'];

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.14', 'msg'=>'No permissions granted'));
    }

    //
    // Get the list of permission_groups the user is a part of
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    $strsql = "SELECT permission_group FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "    // Active user
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.tenants', 'groups', 'permission_group');
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

        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.15', 'msg'=>'Access denied'));
    }
    $groups = $rc['groups'];

    $perms = 0;
    if( in_array('owners', $groups) ) { $perms |= 0x01; }
    if( in_array('employees', $groups) ) { $perms |= 0x02; }
    $ciniki['tenant']['user']['perms'] = $perms;

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
    // By default fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.16', 'msg'=>'Access denied'));
}
?>
