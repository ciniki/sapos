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
function ciniki_sapos_web_accountSessionUnload($ciniki, $settings, $tnid) {

    unset($ciniki['session']['cart']);
    unset($_SESSION['cart']);

    return array('stat'=>'ok');
}
?>
