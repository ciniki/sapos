<?php
//
// Description
// ===========
// Since the donation packages are part of the accounting module, they must be in here as a hook even though 
// it's called from the same module it makes the code cleaner.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sapos_sapos_cartItemLookup($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.210', 'msg'=>'No product specified.'));
    }


    //
    // Lookup the requested product if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.sapos.donationpackage' && isset($args['object_id']) && is_numeric($args['object_id']) && $args['object_id'] > 0 ) {
        
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.221', 'msg'=>'Donation Package not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['package']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.222', 'msg'=>'Unable to find Donation Package'));
        }
        $package = $rc['package'];

        //
        // Setup the product
        //
        $product = array(
            'flags'=>0x8008,
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
        } elseif( isset($args['user_amount']) && is_numeric($args['user_amount']) ) {
            if( $args['user_amount'] < 0 ) {
                return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.sapos.426', 'msg'=>'You cannot specify a negative donation.'));
            } 
            $product['unit_amount'] = $args['user_amount'];
        } else {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.sapos.426', 'msg'=>'You must specify the amount of the donation.'));
        }

        return array('stat'=>'ok', 'item'=>$product);
    }

    //
    // Check for basic donations
    //
    if( $args['object'] == 'ciniki.sapos.cartdonation' && isset($args['object_id']) && is_numeric($args['object_id']) ) {
        $product = array(
            'flags'=>0x8000,
            'price_id'=>0,
            'code'=>'',
            'description'=>'Donation',
            'quantity'=>1,
            'object'=>'ciniki.sapos.cartdonation',
            'object_id'=>$args['object_id'],
            'unit_amount' => ((isset($args['user_amount']) && is_numeric($args['user_amount'])) ? $args['user_amount'] : 0),
            'unit_discount_amount'=>0,
            'unit_discount_percentage'=>0,
            'taxtype_id'=>0,
            );

        return array('stat'=>'ok', 'item'=>$product);
    }

    error_log('ERR: Invalid Object: ' . $args['object'] . ' ID: ' . $args['object_id']);
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.211', 'msg'=>'No product specified.'));        
}
?>
