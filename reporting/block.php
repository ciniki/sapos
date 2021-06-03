<?php
//
// Description
// -----------
// This function will return the report details for a requested report block.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_sapos_reporting_block(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.227', 'msg'=>"That report is not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($args['block_ref']) || !isset($args['options']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.228', 'msg'=>"No block specified."));
    }

    //
    // Return the list of reports for the tenant
    //
    if( $args['block_ref'] == 'ciniki.sapos.sales' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'reporting', 'blockSales');
        return ciniki_sapos_reporting_blockSales($ciniki, $tnid, $args['options']);
    } 
    elseif( $args['block_ref'] == 'ciniki.sapos.categorysales' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'reporting', 'blockCategorySales');
        return ciniki_sapos_reporting_blockCategorySales($ciniki, $tnid, $args['options']);
    } 
    elseif( $args['block_ref'] == 'ciniki.sapos.categorizedsales' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'reporting', 'blockCategorizedSales');
        return ciniki_sapos_reporting_blockCategorizedSales($ciniki, $tnid, $args['options']);
    } 
    elseif( $args['block_ref'] == 'ciniki.sapos.ontarioquarterlyhst' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'reporting', 'blockOntarioQuarterlyHST');
        return ciniki_sapos_reporting_blockOntarioQuarterlyHST($ciniki, $tnid, $args['options']);
    }

    return array('stat'=>'ok');
}
?>
