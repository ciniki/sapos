<?php
//
// Description
// -----------
// This function will return the expense stats for a tenant.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos__expenseStats($ciniki, $tnid) {
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

    $rsp = array('stat'=>'ok', 'stats'=>array());

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', 'fiscal');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.421', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();
    
    //
    // Build the query to get the list of invoices
    //
    $strsql = "SELECT "
        . "MIN(invoice_date) AS min_invoice_date, "
        . "MIN(invoice_date) AS min_invoice_date_year, "
        . "MAX(invoice_date) AS max_invoice_date, "
        . "MAX(invoice_date) AS max_invoice_date_year "
        . "FROM ciniki_sapos_expenses "
        . "WHERE ciniki_sapos_expenses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND invoice_date <> '0000-00-00 00:00:00' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'stats', 'fname'=>'min_invoice_date', 'name'=>'stats',
            'fields'=>array('min_invoice_date', 'min_invoice_date_year', 'max_invoice_date', 
                'max_invoice_date_year'),
            'utctotz'=>array(
                'min_invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'min_invoice_date_year'=>array('timezone'=>$intl_timezone, 'format'=>'Y'),
                'max_invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'max_invoice_date_year'=>array('timezone'=>$intl_timezone, 'format'=>'Y'),
                ), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['stats'][0]['stats']['min_invoice_date_year']) ) {
        $rsp['stats']['min_invoice_date'] = $rc['stats'][0]['stats']['min_invoice_date'];
        $rsp['stats']['min_invoice_date_year'] = $rc['stats'][0]['stats']['min_invoice_date_year'];
        $rsp['stats']['max_invoice_date'] = $rc['stats'][0]['stats']['max_invoice_date'];
        $rsp['stats']['max_invoice_date_year'] = $rc['stats'][0]['stats']['max_invoice_date_year'];
        if( isset($settings['fiscal-year-start-month']) && $settings['fiscal-year-start-month'] != '' 
            && $settings['fiscal-year-start-month'] > 1 
            ) {
            $dt = new DateTime($rc['stats'][0]['stats']['min_invoice_date'], new DateTimezone($intl_timezone));
            if( $dt->format('m') > $settings['fiscal-year-start-month'] ) { 
                $rsp['stats']['min_invoice_date_year'] += 1;
            }
            $dt = new DateTime($rc['stats'][0]['stats']['max_invoice_date'], new DateTimezone($intl_timezone));
            $now = new DateTime('now', new DateTimezone($intl_timezone));
            if( $dt->format('m') >= $settings['fiscal-year-start-month'] 
                || $now->format('m') >= $settings['fiscal-year-start-month']
                ) { 
                $rsp['stats']['max_invoice_date_year'] += 1;
            }
        }
    } else {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $rsp['stats']['min_invoice_date_year'] = $dt->format('Y');
        $rsp['stats']['max_invoice_date_year'] = $dt->format('Y');
    }

    return $rsp;
}
?>
