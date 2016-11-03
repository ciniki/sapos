<?php
//
// Description
// -----------
// This function will calculate the item's final amount, give the quantity,
// unit amount and discounts.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_itemCalcAmount($ciniki, $item) {

    if( !isset($item['quantity']) || !isset($item['unit_amount']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.30', 'msg'=>'Unable to calculate the item amount, missing quantity or unit amount'));
    }

    $unit_amount = $item['unit_amount'];
    //
    // Apply the dollar amount discount first
    //
    if( isset($item['unit_discount_amount']) && $item['unit_discount_amount'] > 0 ) {
        $unit_amount = bcsub($unit_amount, $item['unit_discount_amount'], 4);
    }
    //
    // Apply the percentage discount second
    //
    if( isset($item['unit_discount_percentage']) && $item['unit_discount_percentage'] > 0 ) {
        $percentage = bcdiv($item['unit_discount_percentage'], 100, 4);
        $unit_amount = bcsub($unit_amount, bcmul($unit_amount, $percentage, 4), 4);
    }

    //
    // Calculate what the amount should have been without discounts
    //
    $subtotal = bcmul($item['quantity'], $item['unit_amount'], 2);

    //
    // Apply the quantity
    //
    $total = bcmul($item['quantity'], $unit_amount, 2);

    //
    // Calculate the total discount on the item
    //
    $discount = bcsub(bcmul($item['quantity'], $item['unit_amount'], 2), $total, 2);

    return array('stat'=>'ok', 'subtotal'=>$subtotal, 'discount'=>$discount, 'total'=>$total);
}
?>
