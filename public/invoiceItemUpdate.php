<?php
//
// Description
// ===========
// This method will update the item in an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceItemUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Item'),
        'line_number'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Line Number'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Options'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'subcategory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subcategory'),
        'object'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object'),
        'object_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Object ID'),
        'price_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Price'),
        'code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Code'),
        'description'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Description'),
        'quantity'=>array('required'=>'no', 'blank'=>'no', 'type'=>'int', 'name'=>'Quantity'),
        'shipped_quantity'=>array('required'=>'no', 'blank'=>'no', 'type'=>'int', 'name'=>'Shipped'),
        'unit_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Unit Amount'),
        'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 
            'name'=>'Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Discount Percentage'),
        'unit_preorder_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Preorder Amount'),
        'unit_donation_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Donation Amount'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tax Type'),
        'shipping_profile_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Shipping Profile'),
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceItemUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    if( isset($args['unit_discount_percentage']) && $args['unit_discount_percentage'] == '' ) {
        $args['unit_discount_percentage'] = 0;
    }
    if( isset($args['unit_preorder_amount']) && $args['unit_preorder_amount'] == '' ) {
        $args['unit_preorder_amount'] = 0;
    }

    //
    // Get the existing item details
    //
    $strsql = "SELECT id, invoice_id, flags, object, object_id, "
        . "quantity, unit_amount, unit_discount_amount, unit_discount_percentage, unit_donation_amount, "
        . "unit_preorder_amount, price_id, "
        . "subtotal_amount, discount_amount, total_amount "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['item_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.74', 'msg'=>'Unable to locate the invoice item'));
    }
    $item = $rc['item'];

    //
    // Set the flags for the item if partial donation
    //
    if( isset($args['unit_donation_amount']) ) {
        if( $args['unit_donation_amount'] > 0 ) {
            $args['flags'] = (isset($args['flags']) ? $args['flags'] | 0x0800 : $item['flags'] | 0x0800);
        } else {
            $args['flags'] = (isset($args['flags']) ? $args['flags'] & 0xF7FF : $item['flags'] & 0xF7FF);
        }
    } 

    //
    // FIXME: Add check that donation cannot be larger than unit amount
    //

    //
    // Check if quantity or unit_amount has changed, and update the amount
    //
    if( isset($args['quantity']) 
        || isset($args['unit_amount']) 
        || isset($args['unit_discount_amount']) 
        || isset($args['unit_discount_percentage']) 
        || isset($args['unit_preorder_amount']) 
        ) {

        //
        // Calculate the final amount for the item in the invoice
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'itemCalcAmount');
        $rc = ciniki_sapos_itemCalcAmount($ciniki, array(
            'quantity'=>(isset($args['quantity'])?$args['quantity']:$item['quantity']),
            'unit_amount'=>(isset($args['unit_amount'])?$args['unit_amount']:$item['unit_amount']),
            'unit_discount_amount'=>(isset($args['unit_discount_amount'])?$args['unit_discount_amount']:$item['unit_discount_amount']),
            'unit_discount_percentage'=>(isset($args['unit_discount_percentage'])?$args['unit_discount_percentage']:$item['unit_discount_percentage']),
            'unit_preorder_amount'=>(isset($args['unit_preorder_amount'])?$args['unit_preorder_amount']:$item['unit_preorder_amount']),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $args['subtotal_amount'] = $rc['subtotal'];
        $args['discount_amount'] = $rc['discount'];
        $args['total_amount'] = $rc['total'];
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the item
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.invoice_item', $args['item_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }
    
    //
    // Update the item values for callbacks
    //
    if( isset($args['quantity']) && $args['quantity'] != $item['quantity'] ) {
        $item['old_quantity'] = $item['quantity'];
        $item['quantity'] = $args['quantity'];
    }

    //
    // Check for a callback to the object
    //
    if( $item['object'] != '' && $item['object_id'] != '' ) {
        list($pkg,$mod,$obj) = explode('.', $item['object']);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'sapos', 'itemUpdate');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], $item['invoice_id'], $item);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    //
    // Update the taxes
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
    $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $args['tnid'], $item['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    //
    // Update the invoice status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateStatusBalance');
    $rc = ciniki_sapos_invoiceUpdateStatusBalance($ciniki, $args['tnid'], $item['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

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
