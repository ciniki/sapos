<?php
//
// Description
// ===========
// This method will add a new invoice to the system, creating item entries if specified.  If
// a customer is specified, the billing/shipping address will be pulled from the customer record.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_invoiceGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
        'inventory'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Inventory'), 
        'salesreps'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sales Reps'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Check to make sure the invoice belongs to the salesrep
    //
    if( isset($ciniki['tenant']['user']['perms']) && ($ciniki['tenant']['user']['perms']&0x07) == 0x04 ) {
        $strsql = "SELECT id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['invoice_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.66', 'msg'=>'Permission denied'));
        }
    }

    //
    // Return the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $args['tnid'], $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    //
    // Check if there are any messages for this invoice
    //
    if( isset($ciniki['tenant']['modules']['ciniki.mail']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
        $rc = ciniki_mail_hooks_objectMessages($ciniki, $args['tnid'], 
            array('object'=>'ciniki.sapos.invoice', 'object_id'=>$invoice['id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            $invoice['messages'] = $rc['messages'];
        }
    } 

    //
    // Check if the inventory should be added
    //
/*    if( isset($args['inventory']) && $args['inventory'] == 'yes' ) {
        $objects = array();
        foreach($invoice['items'] as $iid => $item) {
            $item = $item['item'];
            if( !isset($objects[$item['object']]) ) {
                $objects[$item['object']] = array();
            }
            $objects[$item['object']][] = $item['object_id'];
        }
        // 
        // Get the inventory levels for each object
        //
        foreach($objects as $object => $object_ids) {
            list($pkg,$mod,$obj) = explode('.', $object);
            $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryLevels');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $args['tnid'], array(
                    'object'=>$object,
                    'object_ids'=>$object_ids,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.67', 'msg'=>'Unable to get inventory levels.', 'err'=>$rc['err']));
                }
                //
                // Update the inventory levels for the invoice items
                //
                $quantities = $rc['quantities'];
                foreach($invoice['items'] as $iid => $item) {
                    if( $item['item']['object'] == $object 
                        && isset($quantities[$item['item']['object_id']]) 
                        ) {
                        $invoice['items'][$iid]['item']['inventory_quantity'] = $quantities[$item['item']['object_id']]['inventory_quantity'];
                    }
                }
            }
        }
    }
*/
    $rsp = array('stat'=>'ok', 'invoice'=>$invoice);

    //
    // Check if we need to return the list of sales reps
    //
    if( ($modules['ciniki.sapos']['flags']&0x0800) > 0
        && isset($args['salesreps']) && $args['salesreps'] == 'yes' 
        ) {
        //
        // Get the active sales reps
        //
        $strsql = "SELECT ciniki_users.id, ciniki_users.display_name "
            . "FROM ciniki_tenant_users, ciniki_users "
            . "WHERE ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_tenant_users.package = 'ciniki' "
            . "AND ciniki_tenant_users.permission_group = 'salesreps' "
            . "AND ciniki_tenant_users.status < 60 "
            . "AND ciniki_tenant_users.user_id = ciniki_users.id "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'salesreps', 'fname'=>'id', 'name'=>'user',
                'fields'=>array('id', 'name'=>'display_name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['salesreps']) ) {
            $rsp['salesreps'] = $rc['salesreps'];
        } else {
            $rsp['salesreps'] = array();
        }
    }

    return $rsp;
}
?>
