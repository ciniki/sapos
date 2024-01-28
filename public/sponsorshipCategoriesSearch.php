<?php
//
// Description
// ===========
// This method will return a list of invoices.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_sponsorshipCategoriesSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Search String'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'15', 'name'=>'Limit'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.sponsorshipCategoriesSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT subcategory AS category "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND object = 'ciniki.sponsors.package' "
        . "AND subcategory <> '' "
        . "";
    if( isset($args['start_needle']) && $args['start_needle'] != '' ) {
        $strsql .= "AND (subcategory like '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR subcategory like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    return ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('name'=>'category')),
        ));
}
?>
