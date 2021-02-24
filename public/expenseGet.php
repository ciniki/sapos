<?php
//
// Description
// ===========
// This method will add a new expense to the system, creating item entries if specified.  If
// a customer is specified, the billing/shipping address will be pulled from the customer record.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_expenseGet(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'expense_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.expenseGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Load the categories
    //
    $strsql = "SELECT categories.id, "
        . "categories.name, "
        . "categories.sequence, "
        . "categories.flags, "
        . "categories.taxrate_id, "
        . "categories.start_date, "
        . "categories.end_date "
        . "FROM ciniki_sapos_expense_categories AS categories "
        . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY categories.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'sequence', 'flags', 'taxrate_id', 'start_date', 'end_date'),
            'utctotz'=>array(
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'end_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                ),
        )));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();

    if( $args['expense_id'] == 0 ) {
        $dt = new DateTime('now', new DateTimezone($intl_timezone));
        $expense = array(
            'id' => 0,
            'expense_type' => 10,
            'invoice_date' => $dt->format($date_format),
            );
    } else {
        //
        // Return the expense record
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'expenseLoad');
        $rc = ciniki_sapos_expenseLoad($ciniki, $args['tnid'], $args['expense_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $expense = isset($rc['expense']) ? $rc['expense'] : array();
    }

    return array('stat'=>'ok', 'expense'=>$expense, 'categories'=>$categories);
}
?>
