<?php
//
// Description
// ===========
// This method will return the detail for a transaction for an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_transactionGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'transaction_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Transaction'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.transactionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Get business/user settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Get the transaction details
    //
    $strsql = "SELECT id, "
        . "status, "
        . "transaction_type, "
        . "transaction_date, "
        . "source, "
        . "customer_amount, "
        . "transaction_fees, "
        . "business_amount, "
        . "notes "
        . "FROM ciniki_sapos_transactions "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['transaction_id']) . "' "
        . "ORDER BY transaction_date ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'transactions', 'fname'=>'id', 'name'=>'transaction',
            'fields'=>array('id', 'status', 'transaction_type', 'transaction_date', 'source', 'customer_amount', 'transaction_fees', 'business_amount'),
            'utctotz'=>array('transaction_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)), 
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['transactions']) || !isset($rc['transactions'][0]['transaction']) ) {
        return array('stat'=>'ok', 'invoices'=>array());
    }
    $transaction = $rc['transactions'][0]['transaction'];
    $transaction['customer_amount'] = numfmt_format_currency($intl_currency_fmt, $transaction['customer_amount'], $intl_currency);
    $transaction['transaction_fees'] = numfmt_format_currency($intl_currency_fmt, $transaction['transaction_fees'], $intl_currency);
    $transaction['business_amount'] = numfmt_format_currency($intl_currency_fmt, $transaction['business_amount'], $intl_currency);

    return array('stat'=>'ok', 'transaction'=>$transaction);
}
?>
