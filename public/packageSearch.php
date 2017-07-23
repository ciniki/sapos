<?php
//
// Description
// -----------
// This method searchs for a Donation Packages for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get Donation Package for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_sapos_packageSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.packageSearch');
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
        . "WHERE ciniki_sapos_donation_packages.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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

    return array('stat'=>'ok', 'packages'=>$packages, 'nplist'=>$package_ids);
}
?>
