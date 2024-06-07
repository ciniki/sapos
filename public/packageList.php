<?php
//
// Description
// -----------
// This method will return the list of Donation Packages for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Donation Package for.
//
// Returns
// -------
//
function ciniki_sapos_packageList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'dpcategory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'dpcategory'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.packageList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of packages
    //
    $strsql = "SELECT ciniki_sapos_donation_packages.id, "
        . "ciniki_sapos_donation_packages.name, "
        . "ciniki_sapos_donation_packages.subname, "
        . "ciniki_sapos_donation_packages.permalink, "
        . "ciniki_sapos_donation_packages.invoice_name, "
        . "ciniki_sapos_donation_packages.flags, "
        . "ciniki_sapos_donation_packages.category, "
        . "ciniki_sapos_donation_packages.amount "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE ciniki_sapos_donation_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['dpcategory']) && $args['dpcategory'] != '' && $args['dpcategory'] != 'All' ) {
        $strsql .= "AND dpcategory = '" . ciniki_core_dbQuote($ciniki, $args['dpcategory']) . "' ";
    }
    $strsql .= "ORDER BY sequence ";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'packages', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'subname', 'permalink', 'invoice_name', 'flags', 'category', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['packages']) ) {
        $packages = $rc['packages'];
        $package_ids = array();
        foreach($packages as $iid => $package) {
            $package_ids[] = $package['id'];
        }
    } else {
        $packages = array();
        $package_ids = array();
    }

    $rsp = array('stat'=>'ok', 'packages'=>$packages, 'nplist'=>$package_ids);

    //
    // Get the categories
    //
    $strsql = "SELECT DISTINCT dpcategory "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND dpcategory <> '' "
        . "ORDER BY dpcategory "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'dpcategory', 'fields'=>array('name'=>'dpcategory')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.478', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $rsp['dpcategories'] = isset($rc['categories']) ? $rc['categories'] : array();
    array_unshift($rsp['dpcategories'], array('name'=>'All'));

    return $rsp;
}
?>
