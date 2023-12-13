<?php
//
// Description
// -----------
// This function returns the list of invoice status
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_hooks_invoiceStatus($ciniki, $tnid, $args) {

    //
    // Load intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( isset($args['invoice_ids']) && is_array($args['invoice_ids']) && count($args['invoice_ids']) > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
        $rsp = array('stat'=>'ok');
        $strsql = "SELECT ciniki_sapos_invoices.id, "
            . "ciniki_sapos_invoices.customer_id, "
            . "ciniki_sapos_invoices.invoice_number, "
            . "ciniki_sapos_invoices.invoice_date, "
            . "ciniki_sapos_invoices.status, "
            . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
            . "ciniki_sapos_invoices.total_amount "
            . "FROM ciniki_sapos_invoices "
            . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sapos_invoices.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['invoice_ids']) . ") "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'invoices', 'fname'=>'id', 
                'fields'=>array('id', 'customer_id', 'invoice_number', 'invoice_date', 'status', 'status_text', 'total_amount'),
                'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
                'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format))), 
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['invoices']) ) {
            $rsp['invoices'] = array();
        } else {
            $rsp['invoices'] = $rc['invoices'];
        }
        return $rsp;
    }

    if( isset($args['invoice_id']) && $args['invoice_id'] > 0 ) {
        $strsql = "SELECT ciniki_sapos_invoices.id, "
            . "ciniki_sapos_invoices.customer_id, "
            . "ciniki_sapos_invoices.invoice_number, "
            . "ciniki_sapos_invoices.invoice_date, "
            . "ciniki_sapos_invoices.status, "
            . "ciniki_sapos_invoices.payment_status, "
            . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
            . "ciniki_sapos_invoices.total_amount "
            . "FROM ciniki_sapos_invoices "
            . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'invoices', 'fname'=>'id', 
                'fields'=>array('id', 'customer_id', 'invoice_number', 'invoice_date', 'status', 'status_text', 'total_amount'),
                'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
                'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format))), 
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['invoices']) || count($rc['invoices']) == 0 ) {
            return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.sapos.390', 'msg'=>'No invoice found'));
        }
        $invoice = $rc['invoices'][$args['invoice_id']];
        return array('stat'=>'ok', 'invoice'=>$invoice);
    }

    return array('stat'=>'ok');
}
?>
