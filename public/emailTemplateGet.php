<?php
//
// Description
// ===========
// This method will return all the information about an template.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the template is attached to.
// template_id:          The ID of the template to get the details for.
//
// Returns
// -------
//
function ciniki_sapos_emailTemplateGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'template_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Template'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.emailTemplateGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Template
    //
    if( $args['template_id'] == 0 ) {
        $template = array('id'=>0,
            'name'=>'',
            'subject'=>'',
            'message'=>'',
        );
    }

    //
    // Get the details for an existing Template
    //
    else {
        $strsql = "SELECT ciniki_sapos_email_templates.id, "
            . "ciniki_sapos_email_templates.name, "
            . "ciniki_sapos_email_templates.subject, "
            . "ciniki_sapos_email_templates.message "
            . "FROM ciniki_sapos_email_templates "
            . "WHERE ciniki_sapos_email_templates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sapos_email_templates.id = '" . ciniki_core_dbQuote($ciniki, $args['template_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'templates', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'subject', 'message'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.522', 'msg'=>'Template not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['templates'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.523', 'msg'=>'Unable to find Template'));
        }
        $template = $rc['templates'][0];
    }

    return array('stat'=>'ok', 'template'=>$template);
}
?>
