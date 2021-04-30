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
    // Image, Menu with no drop downs/submenus
    //
    $sections['ciniki.sapos.donationpackages'] = array(
        'name'=>'Donation Packages',
        'module' => 'Accounting',
        'settings'=>array(
            'title' => array('label' => 'Title', 'type' => 'text'),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
