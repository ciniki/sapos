<?php
//
// Description
// -----------
// This function will update open orders when a customer status changes
//
// Arguments
// ---------
// ciniki:
// tnid:         The tenant ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_hooks_customerAddressUpdate($ciniki, $tnid, $args) {
    
    if( !isset($args['customer_id']) || $args['customer_id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.288', 'msg'=>'No customer specified'));
    }
    
    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');

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
    // Check for open shopping carts
    //
    $strsql = "SELECT id, billing_name, shipping_name, "   
        . "billing_address1, billing_address2, billing_city, billing_province, billing_postal, billing_country, "
        . "shipping_address1, shipping_address2, shipping_city, shipping_province, shipping_postal, shipping_country "
        . "FROM ciniki_sapos_invoices "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND invoice_type = 20 "  //shopping carts
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.308', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $invoices = isset($rc['rows']) ? $rc['rows'] : array();
        
    if( count($invoices) > 0 ) {
        //
        // Update shopping carts if address changed
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'getCustomer');
        $rc = ciniki_sapos_getCustomer($ciniki, $tnid, array('customer_id'=>$args['customer_id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.289', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        $customer = $rc['args'];

        $fields = array('name', 'address1', 'address2', 'city', 'province', 'postal', 'country');

        foreach($invoices as $invoice) {
            $update_args = array();
            foreach($fields as $field) {
                if( isset($customer['shipping_' . $field]) && $customer['shipping_' . $field] != $invoice['shipping_' . $field] ) {
                    $update_args['shipping_' . $field] = $customer['shipping_' . $field];
                } 
                if( isset($customer['billing_' . $field]) && $customer['billing_' . $field] != $invoice['billing_' . $field] ) {
                    $update_args['billing_' . $field] = $customer['billing_' . $field];
                }
            }
            if( count($update_args) > 0 ) {
                //
                // Update the invoice
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', $invoice['id'], $update_args, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.290', 'msg'=>'Unable to update the cart'));
                }

                //
                // Update the invoice shipping status
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateShippingTaxesTotal');
                $rc = ciniki_sapos_invoiceUpdateShippingTaxesTotal($ciniki, $tnid, $invoice['id']);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.291', 'msg'=>'Unable to update cart', 'err'=>$rc['err']));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
