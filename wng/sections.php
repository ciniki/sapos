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

    $strsql = "SELECT id, dpcategory, name "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE (flags&0x01) = 0x01 "   // Visible
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY dpcategory, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'packages', 'fname'=>'id', 'fields'=>array('id', 'dpcategory', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.502', 'msg'=>'Unable to load packages', 'err'=>$rc['err']));
    }
    $packages = isset($rc['packages']) ? $rc['packages'] : array();
    foreach($packages as $pid => $package) {
        if( $package['dpcategory'] != '' ) {
            $packages[$pid]['name'] = $package['dpcategory'] . ' - ' . $package['name'];
        }
    }
    array_unshift($packages, ['name'=>'None']);

    //
    // Donation cards
    //
    $sections['ciniki.sapos.donationcards'] = [
        'name'=>'Donation Cards',
        'module' => 'Accounting',
        'settings' => [
            'title' => ['label' => 'Title', 'type' => 'text'],
            'content' => ['label' => 'Intro', 'type' => 'textarea', 'size'=>'medium'],
            ],
        'repeats' => [
            'label' => 'Cards',
            'headerValues' => ['Title'],
            'cellClasses' => [''],
            'dataMaps' => ['title'],
            'addTxt' => 'Add Card',
            'fields' => [
                'title' => ['label' => 'Card Title', 'type' => 'text'],
                'subtitle' => ['label' => 'Subtitle', 'type' => 'text'],
                'intro' => ['label' => 'intro', 'type' => 'textarea', 'size' => 'medium'],
                'benefit-1' => ['label' => 'Benefit 1', 'type' => 'text'],
                'benefit-2' => ['label' => 'Benefit 2', 'type' => 'text'],
                'benefit-3' => ['label' => 'Benefit 3', 'type' => 'text'],
                'benefit-4' => ['label' => 'Benefit 4', 'type' => 'text'],
                'benefit-5' => ['label' => 'Benefit 5', 'type' => 'text'],
                'benefit-6' => ['label' => 'Benefit 6', 'type' => 'text'],
                'benefit-7' => ['label' => 'Benefit 7', 'type' => 'text'],
                'benefit-8' => ['label' => 'Benefit 8', 'type' => 'text'],
                'benefit-9' => ['label' => 'Benefit 9', 'type' => 'text'],
                'ending' => ['label' => 'intro', 'type' => 'textarea', 'size' => 'medium'],
                'package-1-id' => ['label' => 'Package 1', 'type' => 'select', 'separator'=>'yes',
                    'options' => $packages,
                    'complex_options' => ['name'=>'name', 'value'=>'id'],
                    ],
                'package-1-text' => ['label' => 'Button Text', 'type' => 'text'],
                'package-2-id' => ['label' => 'Package 2', 'type' => 'select', 'separator'=>'yes',
                    'options' => $packages,
                    'complex_options' => ['name'=>'name', 'value'=>'id'],
                    ],
                'package-2-text' => ['label' => 'Button Text', 'type' => 'text'],
                'package-3-id' => ['label' => 'Package 3', 'type' => 'select', 'separator'=>'yes',
                    'options' => $packages,
                    'complex_options' => ['name'=>'name', 'value'=>'id'],
                    ],
                'package-3-text' => ['label' => 'Button Text', 'type' => 'text'],
                ],
            ],
        ];


    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
