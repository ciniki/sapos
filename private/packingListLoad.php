<?php
//
// Description
// ===========
// This method will return the detail for a transaction for an invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_packingListLoad(&$ciniki, $tnid, $shipment_id) {
    //
    // Load the date formating
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    
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
    // Get the shipment details
    //
    $strsql = "SELECT ciniki_sapos_shipments.id, "
        . "ciniki_sapos_shipments.invoice_id, "
        . "ciniki_sapos_shipments.shipment_number, "
        . "ciniki_sapos_shipments.status, "
        . "ciniki_sapos_shipments.status AS status_text, "
        . "ciniki_sapos_shipments.weight, "
        . "ciniki_sapos_shipments.weight_units, "
        . "ciniki_sapos_shipments.shipping_company, "
        . "ciniki_sapos_shipments.tracking_number, "
        . "ciniki_sapos_shipments.boxes, "
        . "ciniki_sapos_shipments.pack_date, "
        . "ciniki_sapos_shipments.ship_date "
        . "FROM ciniki_sapos_shipments "
        . "WHERE ciniki_sapos_shipments.id = '" . ciniki_core_dbQuote($ciniki, $shipment_id) . "' "
        . "AND ciniki_sapos_shipments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'shipments', 'fname'=>'id', 'name'=>'shipment',
            'fields'=>array('id', 'invoice_id', 'shipment_number', 'status', 'weight',
                'weight_units', 'shipping_company', 'tracking_number', 'boxes', 
                'pack_date', 'ship_date'),
            'maps'=>array('status_text'=>$maps['shipment']['status']),
            'utctotz'=>array('pack_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'ship_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['shipments']) && isset($rc['shipments'][0]['shipment']) ) {
        $shipment = $rc['shipments'][0]['shipment'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.36', 'msg'=>'Shipment does not exist'));
    }
    
    //
    // Load the invoice details
    //
    $strsql = "SELECT id, "
        . "invoice_number, "
        . "po_number, "
        . "customer_id, "
        . "salesrep_id, "
        . "invoice_type, "
        . "invoice_type AS invoice_type_text, "
        . "status, "
        . "CONCAT_WS('.', invoice_type, status) AS status_text, "
        . "payment_status, "
        . "payment_status AS payment_status_text, "
        . "shipping_status, "
        . "shipping_status AS shipping_status_text, "
        . "manufacturing_status, "
        . "manufacturing_status AS manufacturing_status_text, "
        . "flags, "
        . "invoice_date, "
        . "invoice_date AS invoice_time, "
        . "invoice_date AS invoice_datetime, "
        . "due_date, "
        . "due_date AS due_time, "
        . "due_date AS due_datetime, "
        . "billing_name, "
        . "billing_address1, "
        . "billing_address2, "
        . "billing_city, "
        . "billing_province, "
        . "billing_postal, "
        . "billing_country, "
        . "shipping_name, "
        . "shipping_address1, "
        . "shipping_address2, "
        . "shipping_city, "
        . "shipping_province, "
        . "shipping_postal, "
        . "shipping_country, "
        . "shipping_phone, "
        . "shipping_notes, "
        . "shipping_amount, "
        . "subtotal_amount, "
        . "subtotal_discount_percentage, "
        . "subtotal_discount_amount, "
        . "discount_amount, "
        . "total_amount, "
        . "total_savings, "
        . "paid_amount, "
        . "balance_amount, "
        . "customer_notes, "
        . "invoice_notes, "
        . "internal_notes "
        . "FROM ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
        . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
            'fields'=>array('id', 'invoice_number', 'po_number', 'customer_id', 
                'invoice_type', 'invoice_type_text', 'status', 'status_text', 'salesrep_id',
                'payment_status', 'payment_status_text',
                'shipping_status', 'shipping_status_text',
                'manufacturing_status', 'manufacturing_status_text',
                'flags', 'invoice_date', 'invoice_time', 'invoice_datetime', 'due_date',
                'billing_name', 'billing_address1', 'billing_address2', 'billing_city', 
                'billing_province', 'billing_postal', 'billing_country',
                'shipping_name', 'shipping_address1', 'shipping_address2', 'shipping_city', 
                'shipping_province', 'shipping_postal', 'shipping_country', 'shipping_phone', 'shipping_notes',
                'subtotal_amount', 'subtotal_discount_percentage', 'subtotal_discount_amount', 
                'discount_amount', 'shipping_amount', 'total_amount', 'total_savings', 
                'paid_amount', 'balance_amount',
                'customer_notes', 'invoice_notes', 'internal_notes'),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'invoice_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                'invoice_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'due_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'due_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                'due_datetime'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                ),
            'maps'=>array('status_text'=>$maps['invoice']['typestatus'], 
                'invoice_type_text'=>$maps['invoice']['invoice_type'],
                'payment_status_text'=>$maps['invoice']['payment_status'],
                'shipping_status_text'=>$maps['invoice']['shipping_status'],
                'manufacturing_status_text'=>$maps['invoice']['manufacturing_status'],
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['invoices']) || !isset($rc['invoices'][0]['invoice']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.sapos.37', 'msg'=>'Invoice does not exist'));
    }
    $shipment['invoice'] = $rc['invoices'][0]['invoice'];

    //
    // Add sales rep info
    //
    if( $shipment['invoice']['salesrep_id'] > 0 ) {
        $strsql = "SELECT display_name "
            . "FROM ciniki_tenant_users, ciniki_users "
            . "WHERE ciniki_tenant_users.user_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice']['salesrep_id']) . "' "
            . "AND ciniki_tenant_users.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_tenant_users.package = 'ciniki' "
            . "AND ciniki_tenant_users.permission_group = 'salesreps' "
            . "AND ciniki_tenant_users.user_id = ciniki_users.id "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['user']) ) {
            $shipment['salesrep_id_text'] = $rc['user']['display_name'];
        }
    }

    //
    // Get the customer information
    //
    $shipment['customer'] = array('email'=>'', 'phone'=>'', 'tax_number'=>'');

    //
    // Get the items in the invoice
    //
    $strsql = "SELECT ciniki_sapos_invoice_items.id, "
        . "ciniki_sapos_invoice_items.object, "
        . "ciniki_sapos_invoice_items.object_id, "
        . "ciniki_sapos_invoice_items.code, "
        . "ciniki_sapos_invoice_items.description, "
        . "ciniki_sapos_invoice_items.quantity, "
        . "ciniki_sapos_invoice_items.shipped_quantity, "
        . "ciniki_sapos_invoice_items.quantity - shipped_quantity AS backordered_quantity, "
        . "ciniki_sapos_invoice_items.notes "
        . "FROM ciniki_sapos_invoice_items "
        . "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $shipment['invoice_id']) . "' "
        . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY ciniki_sapos_invoice_items.code, ciniki_sapos_invoice_items.description "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'items', 'fname'=>'id', 'name'=>'item',
            'fields'=>array('id', 'object', 'object_id', 'code', 'description', 
                'quantity', 'shipped_quantity', 'backordered_quantity', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['items']) ) {
        $shipment['invoice']['items'] = $rc['items'];
    } else {
        $shipment['invoice']['items'] = array();
    }

    //
    // Get the items in the shipment
    //
    $strsql = "SELECT ciniki_sapos_shipment_items.id, "
        . "ciniki_sapos_shipment_items.shipment_id, "
        . "ciniki_sapos_shipment_items.item_id, "
        . "ciniki_sapos_shipment_items.quantity, "
        . "ciniki_sapos_shipment_items.notes "
        . "FROM ciniki_sapos_shipment_items "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND shipment_id = '" . ciniki_core_dbQuote($ciniki, $shipment_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'items', 'fname'=>'item_id',
            'fields'=>array('id', 'shipment_id', 'item_id', 'quantity', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['items']) ) {
        $shipment['items'] = $rc['items'];
    } else {
        $shipment['items'] = array();
    }

    //
    // Setup the invoice items for shipment_quantity
    //
    if( isset($shipment['invoice']['items']) ) {
        foreach($shipment['invoice']['items'] as $iid => $item) {
            if( isset($shipment['items'][$item['item']['id']]) ) {
                $shipment['invoice']['items'][$iid]['item']['shipment_quantity'] = (float)$shipment['items'][$item['item']['id']]['quantity'];
                $shipment['invoice']['items'][$iid]['item']['shipment_notes'] = $shipment['items'][($item['item']['id'])]['notes'];
            } else {
                $shipment['invoice']['items'][$iid]['item']['shipment_quantity'] = 0;
            }
            $shipment['invoice']['items'][$iid]['item']['backordered_quantity'] = (float)$item['item']['backordered_quantity'];
        }
    }

    //
    // Setup other fields
    //
    $shipment['packing_slip_number'] = $shipment['invoice']['invoice_number'];
    if( $shipment['shipment_number'] != '' ) {
        $shipment['packing_slip_number'] .= '-' . $shipment['shipment_number'];
    }

    return array('stat'=>'ok', 'shipment'=>$shipment);
}
?>
