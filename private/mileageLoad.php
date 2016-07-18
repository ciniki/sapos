<?php
//
// Description
// -----------
// This function will load an mileage record and all the pieces for it.
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_mileageLoad($ciniki, $business_id, $mileage_id) {
    //
    // Get the time information for business and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_distance_units = $rc['settings']['intl-default-distance-units'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // The the mileage details
    //
    $strsql = "SELECT ciniki_sapos_mileage.id, "
        . "ciniki_sapos_mileage.start_name, "
        . "ciniki_sapos_mileage.start_address, "
        . "ciniki_sapos_mileage.end_name, "
        . "ciniki_sapos_mileage.end_address, "
        . "ciniki_sapos_mileage.travel_date, "
        . "ciniki_sapos_mileage.distance, "
        . "ciniki_sapos_mileage.flags, "
        . "ciniki_sapos_mileage.notes, "
        . "IFNULL(ciniki_sapos_mileage_rates.rate, 0) AS rate "
        . "FROM ciniki_sapos_mileage "
        . "LEFT JOIN ciniki_sapos_mileage_rates ON ("
            . "ciniki_sapos_mileage.travel_date >= ciniki_sapos_mileage_rates.start_date "
            . "AND (ciniki_sapos_mileage_rates.end_date = '0000-00-00 00:00:00' "
                . "OR ciniki_sapos_mileage.travel_date <= ciniki_sapos_mileage_rates.end_date "
                . ") "
            . "AND ciniki_sapos_mileage_rates.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_sapos_mileage.id = '" . ciniki_core_dbQuote($ciniki, $mileage_id) . "' "
        . "AND ciniki_sapos_mileage.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'mileages', 'fname'=>'id', 'name'=>'mileage',
            'fields'=>array('id', 'start_name', 'start_address', 'end_name', 'end_address', 
                'travel_date', 'distance', 'flags', 'notes', 'rate'),
            'utctotz'=>array('travel_date'=>array('timezone'=>'UTC', 'format'=>$date_format))),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['mileages']) || !isset($rc['mileages'][0]['mileage']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1713', 'msg'=>'Mileage entry does not exist'));
    }
    $mileage = $rc['mileages'][0]['mileage'];

    //
    // Check for round trip
    //
    if( ($mileage['flags']&0x01) > 0 ) {
        $mileage['total_distance'] = bcmul($mileage['distance'], 2, 2);
        $mileage['flags_text'] = 'Round Trip';
    } else {
        $mileage['total_distance'] = (float)$mileage['distance'];
        $mileage['flags_text'] = 'One Way';
    }

    //
    // Calculate the cost of the trip
    //
    if( isset($mileage['rate']) && $mileage['rate'] != '' ) {
        $mileage['amount'] = bcmul($mileage['rate'], $mileage['total_distance'], 2);
    } else {
        $mileage['amount'] = 0;
    }

    $mileage['distance'] = (float)$mileage['distance'];
    $mileage['rate_display'] = numfmt_format_currency($intl_currency_fmt, $mileage['rate'], $intl_currency);
    $mileage['amount_display'] = numfmt_format_currency($intl_currency_fmt, $mileage['amount'], $intl_currency);
    $mileage['units'] = isset($intl_distance_units)?$intl_distance_units:'km';

    return array('stat'=>'ok', 'mileage'=>$mileage);
}
?>
