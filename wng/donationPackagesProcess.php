<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_donationPackagesProcess($ciniki, $tnid, &$request, $section) {

    $s = isset($section['settings']) ? $section['settings'] : array();

    $blocks = array();

    //
    // Get the list of packages
    //
    $strsql = "SELECT id, "
        . "name, "
        . "subname, "
        . "sequence, "
        . "flags, "
        . "amount, "
        . "primary_image_id, "
        . "synopsis AS description "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'package');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $packages = $rc['rows'];
        foreach($packages as $pid => $package) {
            $packages[$pid]['cart'] = 'yes';
            $packages[$pid]['limited_units'] = 'yes';
            $packages[$pid]['units_available'] = 1;
            $packages[$pid]['object'] = 'ciniki.sapos.donationpackage';
            $packages[$pid]['object_id'] = $package['id'];
            if( ($package['flags']&0x02) == 0x02 ) {
                $packages[$pid]['unit_amount'] = $package['amount'];
            } else {
                $packages[$pid]['user-amount'] = 'yes';
                $packages[$pid]['unit_amount'] = '0';
            }
        }
        $blocks[] = array(
            'title' => isset($s['title']) ? $s['title'] : '',
            'type' => 'pricelist', 
            'prices' => $packages, 
            'descriptions' => 'yes',
            );
    }


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
