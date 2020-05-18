<?php
//
// Description
// -----------
// Format the shipping rate to.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_shippingRateFormat(&$ciniki, $tnid, $rate) {

    if( !isset($rate['flags']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.305', 'msg'=>'Unable to format rate'));
    }

    if( ($rate['flags']&0x01) == 0x01 ) {
        $rate['min_quantity_display'] = (int)$rate['min_quantity'];
    } else {
        $rate['min_quantity_display'] = '';
    }

    if( ($rate['flags']&0x02) == 0x02 ) {
        $rate['max_quantity_display'] = (int)$rate['max_quantity'];
    } else {
        $rate['max_quantity_display'] = '';
    }

    if( ($rate['flags']&0x80) == 0x80 ) {
        $rate['shipping_amount_ca_display'] = '';
        $rate['shipping_amount_us_display'] = '';
        $rate['shipping_amount_intl_display'] = '';
    } else {
        $rate['shipping_amount_ca_display'] = '$' . number_format($rate['shipping_amount_ca'], 2);
        $rate['shipping_amount_us_display'] = '$' . number_format($rate['shipping_amount_us'], 2);
        $rate['shipping_amount_intl_display'] = '$' . number_format($rate['shipping_amount_intl'], 2);
    }

    if( ($rate['flags']&0x80) == 0x80 ) {
        $rate['options_display'] = 'Pickup Only';
    } elseif( ($rate['flags']&0x10) == 0x10 ) {
        $rate['options_display'] = '';
    } else {
        $rate['options_display'] = 'Each';
    }

    return array('stat'=>'ok', 'rate'=>$rate);
}
?>
