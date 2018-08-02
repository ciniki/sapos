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
function ciniki_sapos_transactionAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'transaction_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'transaction_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Date'),
        'source'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'payment type',
            'validlist'=>array('10','20','30','50','55','60','65','90','100','105','110','120')),
        'customer_amount'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'currency', 
            'name'=>'Customer Amount'),
        'transaction_fees'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
            'name'=>'Fees'),
        'tenant_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
            'name'=>'Tenant Amount'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.transactionAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    if( !isset($args['transaction_date']) || $args['transaction_date'] == '' ) {
        $args['transaction_date'] = new DateTime('now', new DateTimezone('UTC'));
    }

    //
    // Set the user id who created the invoice
    //
    $args['user_id'] = $ciniki['session']['user']['id'];

    //
    // Check if fees are blank, set to 0
    //
    if( $args['transaction_fees'] == '' ) {
        $args['transaction_fees'] = 0;
    }

    //
    // FIXME: Check if square, can calculate fees
    //
    if( isset($args['source']) && $args['source'] == 20 ) {
        
    }

    //
    // FIXME: Check if stripe, can calculate fees
    //
    if( isset($args['source']) && $args['source'] == 30 && $args['transaction_fees'] == 0 ) {
        $args['transaction_fees'] = round(bcadd(bcmul($args['customer_amount'], 0.029, 6), 0.30, 6), 2);
    }

    //
    // Check if tenant amount not specified, then set the same as customer_amount
    //
    if( !isset($args['tenant_amount']) || $args['tenant_amount'] == '' || $args['tenant_amount'] == '0' ) {
        $args['tenant_amount'] = bcsub($args['customer_amount'], $args['transaction_fees'], 4);
    }

    $args['gateway'] = '';
    $args['gateway_token'] = '';
    $args['gateway_status'] = '';
    $args['gateway_response'] = '';

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
    // Add the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.sapos.transaction', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    $transaction_id = $rc['id'];

    //
    // Update the invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $args['invoice_id']);
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

    return array('stat'=>'ok', 'id'=>$transaction_id);
}
?>
