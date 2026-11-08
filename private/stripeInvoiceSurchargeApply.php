<?php
//
// Description
// -----------
// This function will create a customer in Stripe and link to customer in Ciniki
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_stripeInvoiceSurchargeApply(&$ciniki, $tnid, &$invoice) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLogAdd');

    //
    // Load stripe settings
    //
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.539', 'msg'=>'Unable to load settings', 'err'=>$rc['err']));
    }
    $settings = isset($rc['settings']) ? $rc['settings'] : array();

    
    if( isset($invoice['stripe_surcharge']) && $invoice['stripe_surcharge'] == 'yes' 
        && isset($invoice['stripe_surcharge_applied']) && $invoice['stripe_surcharge_applied'] != 'yes' 
        && isset($invoice['stripe_surcharge_amount']) && $invoice['stripe_surcharge_amount'] > 0
        ) {
        if( $invoice['stripe_surcharge_applied'] == 'partial' ) {
            //
            // Find the invoice item
            //
            foreach($invoice['items'] as $item) {
                if( $item['item']['object'] == 'ciniki.sapos.stripesurcharge' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateItem');
                    $rc = ciniki_sapos_invoiceUpdateItem($ciniki, $tnid, $invoice['id'], [
                        'object'=>$item['item']['object'],
                        'object_id'=>$item['item']['object_id'],
                        'unit_amount' => $item['item']['unit_amount'] + $invoice['stripe_surcharge_amount'],
                        'total_amount' => $item['item']['total_amount'] + $invoice['stripe_surcharge_amount'],
                        ]);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.540', 'msg'=>'', 'err'=>$rc['err']));
                    }
                }
            }
        } else {
            $item = [
                'invoice_id' => $invoice['id'],
                'flags' => 0x011088, // Required Item, Surcharge, No Dups, No Quantity
                'object' => 'ciniki.sapos.stripesurcharge',
                'object_id' => '',
                'price_id' => 0,
                'description' => isset($settings['stripe-surcharge-invoice-description']) && $settings['stripe-surcharge-invoice-description'] != '' ? $settings['stripe-surcharge-invoice-description'] : 'Surcharge ' . $settings['stripe-surcharge-percent'] . '%',
                'quantity' => 1,
                'unit_amount' => $invoice['stripe_surcharge_amount'],
                'unit_discount_amount' => 0,
                'unit_discount_percentage' => 0,
                ];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
            $rc = ciniki_sapos_invoiceAddItem($ciniki, $tnid, $item);
            if( $rc['stat'] == 'warn' ) {
                return $rc;
            }
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.538', 'msg'=>'Unable to add the item to the invoice', 'err'=>$rc['err']));
            }
            $item['id'] = $rc['id'];
        }

        //
        // Reload the invoice
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
        $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $invoice['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.537', 'msg'=>'Unable to load invoice'));
        }
        $invoice = $rc['invoice'];
    }

    return array('stat'=>'ok');
}
?>
