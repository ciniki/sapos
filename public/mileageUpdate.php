<?php
//
// Description
// ===========
// This method updates and existing mileage entry for a tenant.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_mileageUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'mileage_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Entry'), 
        'start_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Start Name'), 
        'start_address'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Start Address'), 
        'end_name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'End Name'), 
        'end_address'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'End Address'), 
        'travel_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Travel Date'),
        'distance'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Distance'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.mileageUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Create the expense
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.sapos.mileage', $args['mileage_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
        return $rc;
    }

    return $rc;
}
?>
