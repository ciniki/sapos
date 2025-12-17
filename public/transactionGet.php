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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'), 
        'transaction_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Transaction'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.transactionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.512', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Get tenant/user settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');


    if( $args['transaction_id'] == 0 ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $transaction = array(
            'id' => 0,
            'status' => 40,
            'transaction_type' => 20,
            'sourse' => 0,
            'transaction_date' => $dt->format($datetime_format),
            'customer_amount' => '',
            'transaction_fees' => '',
            'tenant_amount' => '',
            'notes' => '',
            );

        //
        // Check if etransfer checkout enabled
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x40000000) ) {
            $transaction['source'] = 110;
        }

        //
        // Lookup invoice if specified
        //
        if( isset($args['invoice_id']) && $args['invoice_id'] > 0 ) {
            $strsql = "SELECT status, "
                . "payment_status "
                . "FROM ciniki_sapos_invoices "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.414', 'msg'=>'Unable to load invoice', 'err'=>$rc['err']));
            }
            if( isset($rc['invoice']['payment_status']) && $rc['invoice']['payment_status'] == 20 ) {
                $transaction['source'] = 110;
            }
        }
         
        return array('stat'=>'ok', 'transaction'=>$transaction);
    }

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
        . "tenant_amount, "
        . "gateway, "
        . "gateway_token, "
        . "notes "
        . "FROM ciniki_sapos_transactions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['transaction_id']) . "' "
        . "ORDER BY transaction_date ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'transactions', 'fname'=>'id', 'name'=>'transaction',
            'fields'=>array('id', 'status', 'transaction_type', 'transaction_date', 'source', 'customer_amount', 'transaction_fees', 'tenant_amount', 'gateway', 'gateway_token', 'notes'),
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
    $transaction['tenant_amount'] = numfmt_format_currency($intl_currency_fmt, $transaction['tenant_amount'], $intl_currency);

    return array('stat'=>'ok', 'transaction'=>$transaction);
}
?>
