<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_sapos_sapos_objectList($ciniki, $tnid) {

    $objects = array(
        //
        // this object should only be added to carts
        //
        'ciniki.sapos.donationpackage' => array(
            'name' => 'Donation Package',
            ),
        'ciniki.sapos.cartdonation' => array(
            'name' => 'Cart Donation',
            ),
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
