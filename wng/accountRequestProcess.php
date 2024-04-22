<?php
//
// Description
// -----------
// This function will process the account request from accountMenuItems
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_accountRequestProcess(&$ciniki, $tnid, &$request, $item) {

    if( !isset($item['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.429', 'msg'=>'No reference specified'));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.musicfestivals.430', 'msg'=>'Must be logged in'));
    }

    if( $item['ref'] == 'ciniki.sapos.invoices' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'accountInvoicesProcess');
        return ciniki_sapos_wng_accountInvoicesProcess($ciniki, $tnid, $request, $item);
    }

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.musicfestivals.431', 'msg'=>'Account page not found'));
}
?>
