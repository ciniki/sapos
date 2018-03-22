<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get donations for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_sapos_hooks_webOptions(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.46', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'tnid', $tnid, 'ciniki.web', 'settings', 'sapos');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }

    //
    // FIXME: Add settings
    //
    $pages = array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x02000000) ) {
        $options = array();
        $options[] = array(
            'label'=>'Introduction',
            'setting'=>'sapos-donations-intro', 
            'type'=>'textarea',
            'value'=>(isset($settings['sapos-donations-intro'])?$settings['sapos-donations-intro']:''),
            'hint'=>'',
        );
        $pages['ciniki.sapos.donations'] = array('name'=>'Donation Packages', 'options'=>$options);
    }

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>
