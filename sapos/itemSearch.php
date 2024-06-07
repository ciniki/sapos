<?php
//
// Description
// ===========
// This function will search the donation packages for the ciniki.sapos module.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sapos_sapos_itemSearch($ciniki, $tnid, $args) {

    if( $args['start_needle'] == '' ) {
        return array('stat'=>'ok', 'items'=>array());
    }

    //
    // Get the details about the donation package
    //
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
        . "ciniki_sapos_donation_packages.synopsis "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE ciniki_sapos_donation_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_sapos_donation_packages.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_sapos_donation_packages.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_sapos_donation_packages.invoice_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_sapos_donation_packages.invoice_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'package');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.223', 'msg'=>'Donation Package not found', 'err'=>$rc['err']));
    }
    $items = array();
    if( isset($rc['rows']) ) {
        $packages = $rc['rows'];
        //
        // Setup the product
        //
        foreach($packages as $package) {
            $product = array(
                'status'=>0,
                'flags'=>0x8000,
                'price_id'=>0,
                'code'=>'',
                'subcategory'=>$package['subcategory'],
                'description'=>$package['invoice_name'],
                'quantity'=>1,
                'object'=>'ciniki.sapos.donationpackage',
                'object_id'=>$package['id'],
                'unit_discount_amount'=>0,
                'unit_discount_percentage'=>0,
                'taxtype_id'=>0,
                'notes'=>'',
                );
            //
            // Check if donation package is recurring monthly (0x20) or yearly (0x80)
            //
            if( ($package['flags']&0x20) == 0x20 ) {
                $product['flags'] |= 0x200000;
            } elseif( ($package['flags']&0x80) == 0x80 ) {
                $product['flags'] |= 0x800000;
            }
            if( ($package['flags']&0x02) == 0x02 ) {    
                $product['unit_amount'] = $package['amount'];
            } elseif( isset($args['user_amount']) ) {
                $product['unit_amount'] = $args['user_amount'];
            } else {
                $product['unit_amount'] = 0;
            }
            $items[] = array('item'=>$product);
        }
    }

    return array('stat'=>'ok', 'items'=>$items);        
}
?>
