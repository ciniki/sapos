<?php
//
// Description
// -----------
// This function will create a recurring invoice for recurring items in the invoice
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_invoiceRecurringSetup(&$ciniki, $tnid, $args) {

    if( !isset($args['invoice']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.481', 'msg'=>'No invoice specified.'));
    }
    $invoice = $args['invoice'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Find the recurring items
    //
    $monthly_items = array();
    $yearly_items = array();
    
    foreach($invoice['items'] as $item) {
        $item = $item['item'];
        if( ($item['flags']&0x200000) == 0x200000 ) {
            $monthly_items[] = $item;
        } elseif( ($item['flags']&0x800000) == 0x800000 ) {
            $yearly_items[] = $item;
        }
    }

    //
    // Setup auto billing information
    //
    if( isset($args['payment_method']) && $args['payment_method'] != '' ) {
        $invoice['flags'] |= 0x08;      // Setup auto billing
        $invoice['stripe_pm_id'] = $args['payment_method'];
    }

    //
    // Create monthly invoice
    //
    if( count($monthly_items) > 0 ) {
        $invoice_date = new DateTime($invoice['invoice_date'], new DateTimeZone($intl_timezone));
        // Make sure recurring monthly will not be after 28th of each month so it doesn't get screwed up by feb
        if( $invoice_date->format('j') > 28 ) {
            $invoice_date->setDate($invoice_date->format('Y'), $invoice_date->format('n'), 28);
        }
        $invoice_date->add(new DateInterval('P1M'));
        $invoice_date->setTimeZone(new DateTimeZone('UTC'));

        // 
        // Create new monthly invoice
        // 
        $new_invoice = $invoice;
        $new_invoice['invoice_type'] = 11;
        $new_invoice['invoice_date'] = $invoice_date->format('Y-m-d H:i:s');
   
        //
        // Add the new recurring invoice
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceRecurringCreate');
        $rc = ciniki_sapos_invoiceRecurringCreate($ciniki, $tnid, $new_invoice, $monthly_items);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }
    if( count($yearly_items) > 0 ) {
        $invoice_date = new DateTime($invoice['invoice_date'], new DateTimeZone($intl_timezone));
        // Make sure yearly recurring is not feb 29
        if( $invoice_date->format('n') == 2 && $invoice_date->format('j') > 28 ) {
            $invoice_date->setDate($invoice_date->format('Y'), $invoice_date->format('n'), 28);
        }
        $invoice_date->add(new DateInterval('P1Y'));
        $invoice_date->setTimeZone(new DateTimeZone('UTC'));

        // 
        // Create new invoice
        // 
        $new_invoice = $invoice;
        $new_invoice['invoice_type'] = 19;
        $new_invoice['invoice_date'] = $invoice_date->format('Y-m-d H:i:s');

        //
        // Add the new recurring invoice
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceRecurringCreate');
        $rc = ciniki_sapos_invoiceRecurringCreate($ciniki, $tnid, $new_invoice, $yearly_items);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
