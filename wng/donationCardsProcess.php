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
function ciniki_sapos_wng_donationCardsProcess($ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    $s = isset($section['settings']) ? $section['settings'] : array();

    $blocks = array();

    //
    // Get the specified packages
    //
    $package_ids = [];
    for($i = 1; $i <= 10; $i++) {
        for($j = 1; $j < 5; $j++) {
            if( isset($s["package-{$j}-id-{$i}"]) && $s["package-{$j}-id-{$i}"] != '' && $s["package-{$j}-id-{$i}"] > 0 ) {
                $package_ids[] = $s["package-{$j}-id-{$i}"];
            }
        }
    }

    //
    // Check to make sure some packages are specified
    //
    if( count($package_ids) <= 0 ) {
        return array('stat'=>'ok', 'blocks'=>[]);
    }

    //
    // Load the list of packages
    //
    $strsql = "SELECT id, "
        . "name, "
        . "subname, "
        . "sequence, "
        . "flags, "
        . "amount "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id IN (" . ciniki_core_dbQuoteIDs($ciniki, $package_ids) . ") "
        . "AND (flags&0x01) = 0x01 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'packages', 'fname'=>'id', 'fields'=>array('id', 'name', 'subname', 'amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.503', 'msg'=>'Unable to load packages', 'err'=>$rc['err']));
    }
    $packages = isset($rc['packages']) ? $rc['packages'] : array();

    //
    // Generate the pricecards
    //
    $items = [];
    for($i = 1; $i <= 10; $i++) {
        if( isset($s["title-{$i}"]) && $s["title-{$i}"] != '' ) {
            $prices = [];
            for($j = 1; $j < 5; $j++) {
                if( isset($s["package-{$j}-id-{$i}"]) 
                    && isset($packages[$s["package-{$j}-id-{$i}"]]) 
                    ) {
                    $package = $packages[$s["package-{$j}-id-{$i}"]];
                    $prices[] = [
                        'object' => 'ciniki.sapos.donationpackage',
                        'object_id' => $package['id'],
                        'quantity' => 1,
                        'limited_units' => 'yes',
                        'units_available' => 1,
                        'unit_amount' => $package['amount'],
                        'add_text' => $s["package-{$j}-text-{$i}"],
                        ];
                }
            }
            $benefits = [];
            for($j = 1; $j < 10; $j++) {
                if( isset($s["benefit-{$j}-{$i}"]) && $s["benefit-{$j}-{$i}"] != '' ) {
                    $benefits[] = ['synopsis' => $s["benefit-{$j}-{$i}"]];
                }
            }
            $items[] = [
                'title' => $s["title-{$i}"],
                'subtitle' => isset($s["subtitle-{$i}"]) ? $s["subtitle-{$i}"] : '',
                'intro' => isset($s["intro-{$i}"]) ? $s["intro-{$i}"] : '',
                'benefits' => $benefits,
                'ending' => isset($s["ending-{$i}"]) ? $s["ending-{$i}"] : '',
                'prices' => $prices,
                ];

        }
    }
     
    $blocks[] = [
        'type' => 'pricecards',
        'title' => isset($s['title']) ? $s['title'] : '',
        'synopsis' => isset($s['synopsis']) ? $s['synopsis'] : '',
        'items' => $items,
        ];

/*
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
    }
*/

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
