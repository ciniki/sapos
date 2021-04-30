<?php
//
// Description
// -----------
// This function will return the b
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_sapos_wng_process(&$ciniki, $tnid, &$request, $section) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.365', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.370', 'msg'=>"No section specified."));
    }

    //
    // Check which section to process
    //
    if( $section['ref'] == 'ciniki.sapos.donationpackages' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'donationPackagesProcess');
        return ciniki_sapos_wng_donationPackagesProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
