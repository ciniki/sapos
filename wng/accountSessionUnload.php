<?php
//
// Description
// -----------
// This function will check for an existing cart to load into the session
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_accountSessionUnload($ciniki, $tnid, $request) {

    unset($request['session']['cart']);
    unset($_SESSION['cart']);

    return array('stat'=>'ok');
}
?>
