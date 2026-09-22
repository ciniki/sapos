<?php
//
// Description
// -----------
// This method will return the list of Templates for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Template for.
//
// Returns
// -------
//
function ciniki_sapos_emailTemplateList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.emailTemplateList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of templates
    //
    $strsql = "SELECT ciniki_sapos_email_templates.id, "
        . "ciniki_sapos_email_templates.name, "
        . "ciniki_sapos_email_templates.subject "
        . "FROM ciniki_sapos_email_templates "
        . "WHERE ciniki_sapos_email_templates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'templates', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'subject')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $templates = isset($rc['templates']) ? $rc['templates'] : array();
    $template_ids = array();
    foreach($templates as $iid => $template) {
        $template_ids[] = $template['id'];
    }

    return array('stat'=>'ok', 'templates'=>$templates, 'nplist'=>$template_ids);
}
?>
