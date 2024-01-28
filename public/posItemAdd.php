<?php
//
// Description
// ===========
// This method will add a new item to an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_posItemAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'),
        'line_number'=>array('required'=>'no', 'blank'=>'no', 'default'=>'1', 'name'=>'Line Number'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Status'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Object ID'),
        'price_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Price'),
        'code'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Code'),
        'description'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Description'),
        'quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'type'=>'int', 'name'=>'Quantity'),
        'shipped_quantity'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'int', 'name'=>'Shipped'),
        'unit_amount'=>array('required'=>'yes', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
            'name'=>'Unit Amount'),
        'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'type'=>'currency', 
            'name'=>'Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 
            'name'=>'Discount Percentage'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Tax Type'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.posItemAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load auto category settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', 'invoice-autocat');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    //
    // Start the transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Check if invoice needs to be created first
    //
    if( $args['invoice_id'] == 0 || $args['invoice_id'] == '' ) {
        $invoice = array(
            'invoice_type' => 30,
            'status' => 10,
            'customer_id' => 0,
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAdd');
        $rc = ciniki_sapos_invoiceAdd($ciniki, $args['tnid'], $invoice);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.253', 'msg'=>'Unable to create new invoice', 'err'=>$rc['err']));
        }
        $args['invoice_id'] = $rc['id'];
    }

    //
    // Check for auto categories
    //
    if( isset($item['object']) && isset($settings['invoice-autocat-' . $item['object']]) 
        && (!isset($args['category']) || $args['category'] == '') 
        ) {
        $args['category'] = $settings['invoice-autocat-' . $item['object']];
    }

    //
    // Add the item
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
    $rc = ciniki_sapos_invoiceAddItem($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.254', 'msg'=>'Unable to add the item to the invoice', 'err'=>$rc['err']));
    }
    $item_id = $rc['id'];

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

    //
    // Load the invoice to return 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'id'=>$item_id, 'invoice'=>$rc['invoice']);
}
?>
