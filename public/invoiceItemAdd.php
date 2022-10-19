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
function ciniki_sapos_invoiceItemAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'),
        'line_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Line Number'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Status'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'donation_category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Donation Category'),
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
        'unit_donation_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Donation Portion'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Tax Type'),
        'shipping_profile_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Profile'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceItemAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    
    //
    // Set the flags for the item if partial donation
    //
    if( isset($args['flags']) && $args['flags'] == '' ) {
        $args['flags'] = 0;
    }
    if( isset($args['unit_donation_amount']) && $args['unit_donation_amount'] > 0 ) {
        $args['flags'] = (isset($args['flags']) ? $args['flags'] | 0x0800 : 0x0800);
    }

    //
    // Get the next line number
    //
    if( !isset($args['line_number']) ) {
        $strsql = "SELECT MAX(line_number) AS line_number "
            . "FROM ciniki_sapos_invoice_items "
            . "WHERE invoice_id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['max']['line_number']) ) {
            $args['line_number'] = $rc['max']['line_number']++;
        } else {
            $args['line_number'] = 1;
        }
    }

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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
    $rc = ciniki_sapos_invoiceAddItem($ciniki, $args['tnid'], $args);
    if( $rc['stat'] == 'warn' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.248', 'msg'=>'Unable to add the item to the invoice', 'err'=>$rc['err']));
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
