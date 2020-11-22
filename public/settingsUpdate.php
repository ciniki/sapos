<?php
//
// Description
// -----------
// This method will update one or more settings for the sapos module.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_sapos_settingsUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.settingsUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Grab the settings for the tenant from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    $rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_sapos_settings', 'tnid', $args['tnid'], 'ciniki.sapos', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // The list of allowed fields for updating
    //
    $changelog_fields = array(
        'paypal-api-processing',
        'paypal-test-account',
        'paypal-test-endpoint',
        'paypal-test-clientid',
        'paypal-test-secret',
        'paypal-live-endpoint',
        'paypal-live-clientid',
        'paypal-live-secret',
        'paypal-ec-site',
        'paypal-ec-clientid',
        'paypal-ec-password',
        'paypal-ec-signature',
        'invoice-header-image',
        'invoice-header-contact-position',
        'invoice-header-tenant-name',
        'invoice-header-tenant-address',
        'invoice-header-tenant-phone',
        'invoice-header-tenant-cell',
        'invoice-header-tenant-fax',
        'invoice-header-tenant-email',
        'invoice-header-tenant-website',
        'invoice-instore-pickup-name',
        'invoice-instore-pickup-address1',
        'invoice-instore-pickup-address2',
        'invoice-instore-pickup-city',
        'invoice-instore-pickup-province',
        'invoice-instore-pickup-postal',
        'invoice-instore-pickup-country',
        'invoice-tallies-payment-type',
        'invoice-bottom-message',
        'invoice-preorder-message',
        'packingslip-bottom-message',
        'invoice-footer-message',
        'invoice-email-all-addresses',
        'invoice-email-message',
        'invoice-reports-taxes-ontario-hst',
        'cart-email-message',
        'pos-email-message',
        'order-email-message',
        'shipments-default-shipper',
        'shipments-default-weight-units',
        'shipments-hide-weight-units',
        'ui-colours-invoice-item-available',
        'ui-colours-invoice-item-backordered',
        'ui-colours-invoice-item-fulfilled',
        'ui-options-print-picklist',
        'ui-options-print-invoice',
        'ui-options-print-envelope',
        'rules-invoice-duplicate-items',
        'rules-invoice-paid-change-items',
        'rules-invoice-submit-require-po_number',
        'rules-shipment-shipped-require-weight',
        'rules-shipment-shipped-require-tracking_number',
        'rules-shipment-shipped-require-boxes',
        'quote-notes-product-synopsis',
        'quote-bottom-message',
        'quote-footer-message',
        'quote-email-message',
        'stripe-pk',
        'stripe-sk',
        'donation-receipt-header-image',
        'donation-receipt-header-contact-position',
        'donation-receipt-header-tenant-name',
        'donation-receipt-header-tenant-address',
        'donation-receipt-header-tenant-phone',
        'donation-receipt-header-tenant-cell',
        'donation-receipt-header-tenant-fax',
        'donation-receipt-header-tenant-email',
        'donation-receipt-header-tenant-website',
        'donation-receipt-minimum-amount',
        'donation-receipt-invoice-include',
        'donation-receipt-signing-officer',
        'donation-receipt-charity-number',
        'donation-receipt-location-issued',
        'donation-invoice-message',
        'donation-receipt-thankyou-message',
        'donation-receipt-signature-image',
        'transaction-gateway-delete',
        );
    //
    // Check each valid setting and see if a new value was passed in the arguments for it.
    // Insert or update the entry in the ciniki_sapos_settings table
    //
    foreach($changelog_fields as $field) {
        if( isset($ciniki['request']['args'][$field]) 
            && (!isset($settings[$field]) || $ciniki['request']['args'][$field] != $settings[$field]) ) {
            $strsql = "INSERT INTO ciniki_sapos_settings (tnid, detail_key, detail_value, date_added, last_updated) "
                . "VALUES ('" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['tnid']) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $field) . "'"
                . ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
                . ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
                . "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
                . ", last_updated = UTC_TIMESTAMP() "
                . "";
            $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.sapos');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.sapos');
                return $rc;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.sapos', 'ciniki_sapos_history', $args['tnid'], 
                2, 'ciniki_sapos_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
            $ciniki['syncqueue'][] = array('push'=>'ciniki.sapos.setting', 
                'args'=>array('id'=>$field));
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.sapos');
    if( $rc['stat'] != 'ok' ) {
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
