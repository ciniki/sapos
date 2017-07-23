<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_processRequest(&$ciniki, $settings, $business_id, $args) {

    if( !isset($ciniki['business']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sapos.212', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    if( isset($args['module_page']) && $args['module_page'] == 'ciniki.sapos.donations' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'web', 'processRequestDonations');
        return ciniki_sapos_web_processRequestDonations($ciniki, $settings, $business_id, $args);
    }

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sapos.213', 'msg'=>"I'm sorry, the page you requested does not exist."));
}
?>
