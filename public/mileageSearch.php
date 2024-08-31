<?php
//
// Description
// ===========
// This method will search the mileage entries for matching names/addresses.  
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_mileageSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.mileageSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_distance_units = $rc['settings']['intl-default-distance-units'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Build the query to get the list of invoices
    //
    $strsql = "SELECT ciniki_sapos_mileage.id, "
        . "ciniki_sapos_mileage.start_name, "
        . "ciniki_sapos_mileage.start_address, "
        . "ciniki_sapos_mileage.end_name, "
        . "ciniki_sapos_mileage.end_address, "
        . "ciniki_sapos_mileage.travel_date, "
        . "ciniki_sapos_mileage.distance, "
        . "ciniki_sapos_mileage.flags, "
        . "IF((flags&0x01)>0,'Round Trip','One Way') AS round_trip, "
        . "IF((flags&0x01)>0,(distance*2),distance) AS total_distance "
        . "FROM ciniki_sapos_mileage "
        . "WHERE ciniki_sapos_mileage.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (start_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR start_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR start_address LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR start_address LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR end_name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR end_name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR end_address LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR end_address LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "ORDER BY ciniki_sapos_mileage.travel_date DESC "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . intval($args['limit']) . " ";
    } elseif( !isset($args['limit']) ) {
        $strsql .= "LIMIT 15 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'mileages', 'fname'=>'id', 'name'=>'mileage',
            'fields'=>array('id', 'start_name', 'start_address', 'end_name', 'end_address', 
                'travel_date', 'distance', 'total_distance', 'flags', 'round_trip'),
            'utctotz'=>array('travel_date'=>array('timezone'=>'UTC', 'format'=>$date_format))),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['mileages']) ) {
        $mileages = array();
    } else {
        $mileages = $rc['mileages'];
        foreach($mileages as $mid => $mileage) {
            $mileages[$mid]['mileage']['total_distance'] = (float)$mileage['mileage']['total_distance'];
            $mileages[$mid]['mileage']['units'] = $intl_distance_units;
        }
    }
    
    return array('stat'=>'ok', 'mileages'=>$mileages);
}
?>
