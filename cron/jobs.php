<?php
//
// Description
// ===========
// This cron job checks for any recurring invoices that need to be created in any tenant.
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_sapos_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for sapos jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddFromRecurring');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'expenseAddFromRecurring');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');

    //
    // Get the list of recurring invoices with a invoice_date of today or before
    //
    $strsql = "SELECT ri.tnid, "
        . "ri.id AS recurring_id, "
        . "i.id AS invoice_id "
        . "FROM ciniki_sapos_invoices AS ri "
        . "LEFT JOIN ciniki_sapos_invoices AS i ON ("
            . "ri.id = i.source_id "
            . "AND ri.tnid = i.tnid "
            . "AND ri.invoice_date = i.invoice_date "
            . ") "
        . "WHERE (ri.invoice_type = 11 OR ri.invoice_type = 16 OR ri.invoice_type = 19) "
        . "AND ri.invoice_date < UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $recurring = $rc['rows'];
        
        foreach($recurring as $ri) {
            //
            // We need the modules that are enabled for this tenant
            //
            $ciniki['tenant']['modules'] = array();
            $rc = ciniki_tenants_checkModuleAccess($ciniki, $ri['tnid'], 'ciniki', 'sapos');
            if( $rc['stat'] != 'ok' ) {
                ciniki_cron_logMsg($ciniki, $rc['tnid'], array('code'=>'ciniki.sapos.206', 'msg'=>'Unable to check module access.',
                    'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                    ));
                return $rc;
            }
            //
            // Add the missing recurring invoices
            //
            $rc = ciniki_sapos_invoiceAddFromRecurring($ciniki, $ri['tnid'], $ri['recurring_id']);
            if( $rc['stat'] != 'ok' ) {
                //
                // Log the message but don't exit, there might be many more to setup
                //
                ciniki_cron_logMsg($ciniki, $rc['tnid'], array('code'=>'ciniki.sapos.205', 'msg'=>'Unable to add recurring invoice',
                    'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                    ));
            }
            $ciniki['tenant']['modules'] = array();
        }
    }

    //
    // Get the list of recurring expenses with a invoice_date of today or before
    //
    $strsql = "SELECT re.tnid, "
        . "re.id AS recurring_id, "
        . "e.id AS expense_id "
        . "FROM ciniki_sapos_expenses AS re "
        . "LEFT JOIN ciniki_sapos_expenses AS e ON ("
            . "re.id = e.source_id "
            . "AND re.tnid = e.tnid "
            . "AND re.invoice_date = e.invoice_date "
            . ") "
        . "WHERE (re.expense_type = 20 OR re.expense_type = 30 OR re.expense_type = 40) "
        . "AND re.invoice_date < UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $recurring = $rc['rows'];
        
        foreach($recurring as $expense) {
            //
            // We need the modules that are enabled for this tenant
            //
            $ciniki['tenant']['modules'] = array();
            $rc = ciniki_tenants_checkModuleAccess($ciniki, $expense['tnid'], 'ciniki', 'sapos');
            if( $rc['stat'] != 'ok' ) {
                ciniki_cron_logMsg($ciniki, $rc['tnid'], array('code'=>'ciniki.sapos.313', 'msg'=>'Unable to check module access.',
                    'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                    ));
                return $rc;
            }
            //
            // Add the missing recurring invoices
            //
            $rc = ciniki_sapos_expenseAddFromRecurring($ciniki, $expense['tnid'], $expense['recurring_id']);
            if( $rc['stat'] != 'ok' ) {
                //
                // Log the message but don't exit, there might be many more to setup
                //
                ciniki_cron_logMsg($ciniki, $rc['tnid'], array('code'=>'ciniki.sapos.314', 'msg'=>'Unable to add recurring invoice',
                    'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                    ));
            }
            $ciniki['tenant']['modules'] = array();
        }
    }

    //
    // Check for carts that should be expired
    //
    $strsql = "SELECT id, tnid, customer_id, DATEDIFF(UTC_TIMESTAMP(), last_updated) AS age "
        . "FROM ciniki_sapos_invoices "
        . "WHERE invoice_type = 20 "
        . "AND status = 10 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'cart');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $carts = $rc['rows'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'cartDelete');
        foreach($carts as $cart) {
            // 
            // Expire customer carts after 30 days
            // OR Expire non-customer carts after 1 days
            //
            if( ($cart['customer_id'] > 0 && $cart['age'] > 30) 
                || ($cart['customer_id'] == 0 && $cart['age'] > 1) 
                ) {
                error_log('removing cart: ' . $cart['tnid'] . '-' . $cart['id']);
                $rc = ciniki_sapos_cartDelete($ciniki, $cart['tnid'], $cart['id']);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_cron_logMsg($ciniki, $cart['tnid'], array('code'=>'ciniki.sapos.273', 'msg'=>'Unable to remove cart',
                        'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                        ));
                }
            }
        }
    }


    return array('stat'=>'ok');
}
?>
