<?php
//
// Description
// -----------
// This function will return the list of available sections to the ciniki.wng module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_sapos_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.364', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    $sections = array();

    //
    // Get the list of groups
    //
    $strsql = "SELECT DISTINCT dpcategory AS name "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE (flags&0x01) = 0x01 "   // Visible
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND dpcategory <> '' "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'dpcategories', 'fname'=>'name', 'fields'=>array('name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.479', 'msg'=>'Unable to load dpcategories', 'err'=>$rc['err']));
    }
    $dpcategories = isset($rc['dpcategories']) ? $rc['dpcategories'] : array();
    array_unshift($dpcategories, ['name'=>'All']);

    //
    // Image, Menu with no drop downs/submenus
    //
    $sections['ciniki.sapos.donationpackages'] = array(
        'name'=>'Donation Packages',
        'module' => 'Accounting',
        'settings'=>array(
            'title' => array('label' => 'Title', 'type' => 'text'),
            'dpcategory' => array('label' => 'Category', 'type' => 'select', 
                'complex_options'=>array('name'=>'name', 'value'=>'name'),
                'options'=>$dpcategories),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
