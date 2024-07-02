<?php
//
// Description
// -----------
// This function will add a log entry for an invoice.
//
// Arguments
// ---------
// ciniki:
//
//
// Returns
// -------
//
function ciniki_sapos_invoiceLogAdd($ciniki, $tnid, $args) {
/*
    if( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif( isset($_SERVER['REMOTE_ADDR']) ) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip_address = 'unknown';
    }

    if( !isset($args['action']) || $args['action'] == '' ) {
        $args['action'] = 'unknown';
    }

    $strsql = "INSERT INTO ciniki_customer_logs (uuid, tnid, "
        . "invoice_id, customer_id, log_date, status, ip_address, action, error_code, error_msg, "
        . "date_added, last_updated) VALUES ("
        . "UUID(), "
        . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, isset($args['invoice_id']) ? $args['invoice_id'] : 0) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, isset($args['customer_id']) ? $args['customer_id'] : 0) . "', "
        . "UTC_TIMESTAMP(), "
        . "'" . ciniki_core_dbQuote($ciniki, isset($args['status']) ? $args['status'] : 0) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $ip_address) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['action']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, isset($args['code']) ? $args['code'] : '') . "', "
        . "'" . ciniki_core_dbQuote($ciniki, isset($args['msg']) ? $args['msg'] : '') . "', "
        . "UTC_TIMESTAMP(), "
        . "UTC_TIMESTAMP() "
        . ") ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.customers');
    if( $rc['stat'] != 'ok' ) {
        error_log("INVOICE-LOG: Unable to add log entry");
        error_log(print_r($args,true));
        // Don't return an error, log it to error log
    }

    //
    // FIXME: Check if message should be sent as a notification
    //
    if( isset($args['notify']) && $args['notify'] == 'yes' ) {
        
    }
*/
    return array('stat'=>'ok');
}
?>
