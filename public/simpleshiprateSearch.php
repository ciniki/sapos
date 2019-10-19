<?php
//
// Description
// -----------
// This method searchs for a Shipping Rates for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Shipping Rate for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_sapos_simpleshiprateSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.simpleshiprateSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of simpleshiprates
    //
    $strsql = "SELECT ciniki_sapos_simpleshiprates.id, "
        . "ciniki_sapos_simpleshiprates.country, "
        . "ciniki_sapos_simpleshiprates.province, "
        . "ciniki_sapos_simpleshiprates.city, "
        . "ciniki_sapos_simpleshiprates.minimum_amount, "
        . "ciniki_sapos_simpleshiprates.rate "
        . "FROM ciniki_sapos_simpleshiprates "
        . "WHERE ciniki_sapos_simpleshiprates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "country LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR country LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR province LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR province LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR city LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR city LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "ORDER BY country, province, city, minimum_amount, rate "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'simpleshiprates', 'fname'=>'id', 
            'fields'=>array('id', 'country', 'province', 'city', 'minimum_amount', 'rate')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['simpleshiprates']) ) {
        $simpleshiprates = $rc['simpleshiprates'];
        $simpleshiprate_ids = array();
        foreach($simpleshiprates as $iid => $simpleshiprate) {
            $simpleshiprate_ids[] = $simpleshiprate['id'];
            $simpleshiprates[$iid]['minimum_amount_display'] = '$' . number_format($simpleshiprate['minimum_amount'], 2);
            $simpleshiprates[$iid]['rate_display'] = '$' . number_format($simpleshiprate['rate'], 2);
        }
    } else {
        $simpleshiprates = array();
        $simpleshiprate_ids = array();
    }

    return array('stat'=>'ok', 'simpleshiprates'=>$simpleshiprates, 'nplist'=>$simpleshiprate_ids);
}
?>
