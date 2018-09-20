<?php
//
// Description
// ===========
// This method will add a new transaction to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_transactionUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'transaction_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Transaction'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'transaction_type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
        'transaction_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Date'),
        'source'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Payment Type',
            'validlist'=>array('10','20','30','50','55','60','65','80','90','100','105','110','115','120')),
        'customer_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Customer Amount'),
        'transaction_fees'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Fees'),
        'tenant_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Tenant Amount'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.transactionUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the transaction details
    //
    $strsql = "SELECT invoice_id, customer_amount, transaction_fees, tenant_amount "
        . "FROM ciniki_sapos_transactions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['transaction_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'transaction');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['transaction']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.144', 'msg'=>'Unable to locate the invoice'));
    }
    $transaction = $rc['transaction'];

    //
    // Check if we need to recalc the tenant_amount if customer or fees where changed
    //
    if( (isset($args['customer_amount']) || isset($args['transaction_fees'])) 
        && (!isset($args['tenant_amount']) || $args['tenant_amount'] == '') ) {
        if( isset($args['customer_amount']) && isset($args['transaction_fees']) ) {
            $args['tenant_amount'] = bcsub($args['customer_amount'], $args['transaction_fees'], 4);
        } 
        elseif( isset($args['customer_amount']) ) {
            $args['tenant_amount'] = bcsub($args['customer_amount'], $transaction['transaction_fees'], 4);
        }
        elseif( isset($args['transaction_fees']) ) {
            $args['tenant_amount'] = bcsub($transaction['customer_amount'], $args['transaction_fees'], 4);
        }
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.transaction', 
        $args['transaction_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    
    //
    // Update the invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $transaction['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }



    //
    // FIXME: Check if callback hooks to item modules
    //


    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'sapos');

    return array('stat'=>'ok');
}
?>
