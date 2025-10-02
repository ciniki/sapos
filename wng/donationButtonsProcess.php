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
function ciniki_sapos_wng_donationButtonsProcess($ciniki, $tnid, &$request, $section) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    $s = isset($section['settings']) ? $section['settings'] : array();

    $blocks = array();

    $blocks[] = [
        'type' => 'donatebuttons',
        'title' => isset($s['title']) ? $s['title'] : '',
        'content' => isset($s['content']) ? $s['content'] : '',
        'amounts' => $s['amounts'],
        'other' => isset($s['other']) ? $s['other'] : 'yes',
        ];

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
