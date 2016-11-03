<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_web_customerOrder(&$ciniki, $settings, $business_id, $customer_id, $args) {

    if( !isset($args['invoice_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.171', 'msg'=>"No invoice specified"));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $date_format = 'M j, Y';

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $business_id, $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    //
    // Load shipment information
    //
    if( isset($invoice['shipments']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'shipmentLoad');
        foreach($invoice['shipments'] as $sid => $shipment) {
            $rc = ciniki_sapos_shipmentLoad($ciniki, $business_id, $shipment['shipment']['id']);
            if( $rc['stat'] != 'ok' ) {
                continue;
            }
            if( isset($rc['shipment']['items']) ) {
                $invoice['shipments'][$sid]['shipment'] = $rc['shipment'];
            }
        }
    }

    return array('stat'=>'ok', 'invoice'=>$invoice);
}
?>
