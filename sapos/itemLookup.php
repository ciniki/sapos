<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sapos_sapos_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.224', 'msg'=>'No event specified.'));
    }

    //
    // Lookup the donation package that was added to the invoice
    //
    if( $args['object'] == 'ciniki.sapos.donationpackage' && isset($args['object_id']) && $args['object_id'] > 0 ) {
        
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
            . "ciniki_sapos_donation_packages.sequence, "
            . "ciniki_sapos_donation_packages.amount, "
            . "ciniki_sapos_donation_packages.primary_image_id, "
            . "ciniki_sapos_donation_packages.synopsis, "
            . "ciniki_sapos_donation_packages.description "
            . "FROM ciniki_sapos_donation_packages "
            . "WHERE ciniki_sapos_donation_packages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sapos_donation_packages.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND (flags&0x01) = 0x01 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'package');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.230', 'msg'=>'Donation Package not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['package']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.231', 'msg'=>'Unable to find Donation Package'));
        }
        $package = $rc['package'];

        //
        // Setup the product
        //
        $product = array(
            'flags'=>0x8088,
            'price_id'=>0,
            'code'=>'',
            'description'=>$package['invoice_name'],
            'quantity'=>1,
            'object'=>'ciniki.sapos.donationpackage',
            'object_id'=>$package['id'],
            'unit_discount_amount'=>0,
            'unit_discount_percentage'=>0,
            'taxtype_id'=>0,
            );
        if( ($package['flags']&0x02) == 0x02 ) {    
            $product['unit_amount'] = $package['amount'];
        } elseif( isset($args['user_amount']) ) {
            $product['unit_amount'] = $args['user_amount'];
        } else {
            $product['unit_amount'] = 0;
        }

        return array('stat'=>'ok', 'item'=>$product);
    }

    return array('stat'=>'ok');
}
?>
