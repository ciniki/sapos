<?php
//
// Description
// ===========
// This method will return all the information about an donation package.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the donation package is attached to.
// package_id:          The ID of the donation package to get the details for.
//
// Returns
// -------
//
function ciniki_sapos_packageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'package_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Donation Package'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.packageGet');
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
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Donation Package
    //
    if( $args['package_id'] == 0 ) {
        $strsql = "SELECT MAX(sequence) AS maxseq "
            . "FROM ciniki_sapos_donation_packages "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.sapos', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $seq = 1;
        if( isset($rc['max']) && $rc['max'] > 1 ) {
            $seq = $rc['max'] + 1;
        }
        $package = array('id'=>0,
            'name'=>'',
            'invoice_name'=>'',
            'subname'=>'',
            'permalink'=>'',
            'flags'=>0x01,
            'sequence'=>$seq,
            'category'=>'',
            'amount'=>'',
            'primary_image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Donation Package
    //
    else {
        $strsql = "SELECT ciniki_sapos_donation_packages.id, "
            . "ciniki_sapos_donation_packages.name, "
            . "ciniki_sapos_donation_packages.subname, "
            . "ciniki_sapos_donation_packages.permalink, "
            . "ciniki_sapos_donation_packages.invoice_name, "
            . "ciniki_sapos_donation_packages.flags, "
            . "ciniki_sapos_donation_packages.category, "
            . "ciniki_sapos_donation_packages.subcategory, "
            . "ciniki_sapos_donation_packages.sequence, "
            . "ciniki_sapos_donation_packages.amount, "
            . "ciniki_sapos_donation_packages.primary_image_id, "
            . "ciniki_sapos_donation_packages.synopsis, "
            . "ciniki_sapos_donation_packages.description "
            . "FROM ciniki_sapos_donation_packages "
            . "WHERE ciniki_sapos_donation_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sapos_donation_packages.id = '" . ciniki_core_dbQuote($ciniki, $args['package_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'packages', 'fname'=>'id', 
                'fields'=>array('invoice_name', 'name', 'subname', 'permalink', 'sequence', 'flags', 
                    'category', 'subcategory', 'amount', 'primary_image_id', 'synopsis', 'description',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.218', 'msg'=>'Donation Package not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['packages'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.219', 'msg'=>'Unable to find Donation Package'));
        }
        $package = $rc['packages'][0];
        $package['amount'] = number_format($package['amount'], 2);
    }

    return array('stat'=>'ok', 'package'=>$package);
}
?>
