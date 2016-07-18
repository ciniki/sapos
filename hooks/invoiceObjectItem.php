<?php
//
// Description
// -----------
// This function will return the list of invoices for a customer.
//
// Arguments
// ---------
// ciniki:
// business_id:         The business ID to check the session user against.
// method:              The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_sapos_hooks_invoiceObjectItem($ciniki, $business_id, $invoice_id, $object, $object_id) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Get the time information for business and user
    //
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
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

    $rsp = array('stat'=>'ok');

    //
    // The the invoice details
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
        . "work_address1, "
        . "work_address2, "
        . "work_city, "
        . "work_province, "
        . "work_postal, "
        . "work_country, "
        . "tax_location_id, "
        . "pricepoint_id, "
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
        . "internal_notes, "
        . "submitted_by "
        . "FROM ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoices.id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    // Check if only a sales rep
    if( isset($ciniki['business']['user']['perms']) && ($ciniki['business']['user']['perms']&0x07) == 0x04 ) {
        $strsql .= "AND ciniki_sapos_invoices.salesrep_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' ";
    }
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 'name'=>'invoice',
            'fields'=>array('id', 'invoice_number', 'po_number', 'customer_id', 'salesrep_id',
                'invoice_type', 'invoice_type_text', 'status', 'status_text',
                'payment_status', 'payment_status_text',
                'shipping_status', 'shipping_status_text',
                'manufacturing_status', 'manufacturing_status_text',
                'flags', 'invoice_date', 'invoice_time', 'invoice_datetime', 'due_date',
                'billing_name', 'billing_address1', 'billing_address2', 'billing_city', 
                'billing_province', 'billing_postal', 'billing_country',
                'shipping_name', 'shipping_address1', 'shipping_address2', 'shipping_city', 
                'shipping_province', 'shipping_postal', 'shipping_country', 'shipping_phone', 'shipping_notes',
                'work_address1', 'work_address2', 'work_city', 'work_province', 'work_postal', 'work_country',
                'tax_location_id', 'pricepoint_id', 
                'subtotal_amount', 'subtotal_discount_percentage', 'subtotal_discount_amount', 
                'discount_amount', 'shipping_amount', 'total_amount', 'total_savings', 
                'paid_amount', 'balance_amount',
                'customer_notes', 'invoice_notes', 'internal_notes', 'submitted_by'),
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
        return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'2371', 'msg'=>'Invoice does not exist'));
    }
    $rsp['invoice'] = $rc['invoices'][0]['invoice'];
    $rsp['invoice']['subtotal_discount_percentage'] = (float)$rsp['invoice']['subtotal_discount_percentage'];

    //
    // Get the customer details
    //
    $rsp['invoice']['customer'] = array();
    if( $rsp['invoice']['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'customerDetails');
        $rc = ciniki_customers__customerDetails($ciniki, $business_id, $rsp['invoice']['customer_id'], 
            array('phones'=>'yes', 'emails'=>'yes', 'addresses'=>'no', 'subscriptions'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['invoice']['customer'] = $rc['customer'];
        $rsp['invoice']['customer_details'] = $rc['details'];
    }

    //
    // Build the shipping address
    //
    $rsp['invoice']['shipping_address'] = '';
    if( $rsp['invoice']['shipping_name'] != '' 
        && (!isset($rsp['invoice']['customer']['display_name']) 
            || $rsp['invoice']['customer']['display_name'] != $rsp['invoice']['shipping_name']) 
        ) {
        $rsp['invoice']['shipping_address'] .= 
            ($rsp['invoice']['shipping_address']!=''?"\n":'') . $rsp['invoice']['shipping_name'];
    }
    if( $rsp['invoice']['shipping_address1'] != '' ) {
        $rsp['invoice']['shipping_address'] .= 
            ($rsp['invoice']['shipping_address']!=''?"\n":'') . $rsp['invoice']['shipping_address1'];
    }
    if( $rsp['invoice']['shipping_address2'] != '' ) {
        $rsp['invoice']['shipping_address'] .= 
            ($rsp['invoice']['shipping_address']!=''?"\n":'') . $rsp['invoice']['shipping_address2'];
    }
    $city = '';
    if( $rsp['invoice']['shipping_city'] != '' ) {
        $city .= ($city!=''?"":'') . $rsp['invoice']['shipping_city'];
    }
    if( $rsp['invoice']['shipping_province'] != '' ) {
        $city .= ($city!=''?", ":'') . $rsp['invoice']['shipping_province'];
    }
    if( $rsp['invoice']['shipping_postal'] != '' ) {
        $city .= ($city!=''?"  ":'') . $rsp['invoice']['shipping_postal'];
    }
    if( $city != '' ) { 
        $rsp['invoice']['shipping_address'] .= ($rsp['invoice']['shipping_address']!=''?"\n":'') . $city;
    }
    if( $rsp['invoice']['shipping_country'] != '' ) {
        $rsp['invoice']['shipping_address'] .= 
            ($rsp['invoice']['shipping_address']!=''?"\n":'') . $rsp['invoice']['shipping_country'];
    }

    //
    // Check if salesrep specified
    //
    if( $rsp['invoice']['salesrep_id'] > 0 ) {
        $strsql = "SELECT display_name "
            . "FROM ciniki_business_users, ciniki_users "
            . "WHERE ciniki_business_users.user_id = '" . ciniki_core_dbQuote($ciniki, $rsp['invoice']['salesrep_id']) . "' "
            . "AND ciniki_business_users.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_business_users.package = 'ciniki' "
            . "AND ciniki_business_users.permission_group = 'salesreps' "
            . "AND ciniki_business_users.user_id = ciniki_users.id "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'user');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['user']) ) {
            $rsp['invoice']['salesrep_id_text'] = $rc['user']['display_name'];
        }
    }

    //
    // Get the item details
    //
    $strsql = "SELECT ciniki_sapos_invoice_items.id, "  
        . "ciniki_sapos_invoice_items.line_number, "
        . "ciniki_sapos_invoice_items.flags, "
        . "ciniki_sapos_invoice_items.status, "
        . "ciniki_sapos_invoice_items.object, "
        . "ciniki_sapos_invoice_items.object_id, "
        . "ciniki_sapos_invoice_items.code, "
        . "ciniki_sapos_invoice_items.description, "
        . "ciniki_sapos_invoice_items.quantity, "
        . "ciniki_sapos_invoice_items.shipped_quantity, "
        . "ciniki_sapos_invoice_items.quantity - shipped_quantity AS required_quantity, "
        . "ROUND(ciniki_sapos_invoice_items.unit_amount, 2) AS unit_amount, "
        . "ROUND(ciniki_sapos_invoice_items.unit_discount_amount, 2) AS unit_discount_amount, "
        . "ciniki_sapos_invoice_items.unit_discount_percentage, "
        . "ROUND(ciniki_sapos_invoice_items.subtotal_amount, 2) AS subtotal_amount, "
        . "ROUND(ciniki_sapos_invoice_items.discount_amount, 2) AS discount_amount, "
        . "ROUND(ciniki_sapos_invoice_items.total_amount, 2) AS total_amount, "
        . "ciniki_sapos_invoice_items.notes, "
        . "ciniki_sapos_invoice_items.taxtype_id, "
        . "IFNULL(ciniki_tax_types.name, '') AS taxtype_name "
        . "FROM ciniki_sapos_invoice_items "
        . "LEFT JOIN ciniki_tax_types ON (ciniki_sapos_invoice_items.taxtype_id = ciniki_tax_types.id "
            . "AND ciniki_tax_types.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_sapos_invoice_items.invoice_id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
        . "AND ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_sapos_invoice_items.object = '" . ciniki_core_dbQuote($ciniki, $object) . "' "
        . "AND ciniki_sapos_invoice_items.object_id = '" . ciniki_core_dbQuote($ciniki, $object_id) . "' "
        . "ORDER BY ciniki_sapos_invoice_items.line_number, ciniki_sapos_invoice_items.date_added "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'line_number', 'flags', 'status',
                'object', 'object_id',
                'code', 'description', 'quantity', 'shipped_quantity', 'required_quantity', 
                'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 
                'subtotal_amount', 'discount_amount', 'total_amount', 'taxtype_id', 'notes', 'taxtype_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['items']) && count($rc['items']) > 0 ) {
        $rsp['item'] = array_pop($rc['items']);
        //
        // Apply the dollar amount discount first
        //
        $unit_discounted_amount = $rsp['item']['unit_amount'];
        if( isset($rsp['item']['unit_discount_amount']) && $rsp['item']['unit_discount_amount'] > 0 ) {
            $unit_discounted_amount = bcsub($unit_discounted_amount, $rsp['item']['unit_discount_amount'], 4);
        }
        //
        // Apply the percentage discount second
        //
        if( isset($rsp['item']['unit_discount_percentage']) && $rsp['item']['unit_discount_percentage'] > 0 ) {
            $percentage = bcdiv($rsp['item']['unit_discount_percentage'], 100, 4);
            $unit_discounted_amount = bcsub($unit_discounted_amount, bcmul($unit_discounted_amount, $percentage, 4), 4);
        }
        $rsp['item']['unit_discounted_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $unit_discounted_amount, $intl_currency);

        $rsp['item']['unit_discount_percentage'] = (float)$rsp['item']['unit_discount_percentage'];
        $rsp['item']['quantity'] = (float)$rsp['item']['quantity'];
        $rsp['item']['shipped_quantity'] = (float)$rsp['item']['shipped_quantity'];
        $rsp['item']['required_quantity'] = (float)$rsp['item']['required_quantity'];
        $rsp['item']['inventory_quantity'] = '';
        $rsp['item']['unit_discount_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $rsp['item']['unit_discount_amount'], $intl_currency);
        $rsp['item']['unit_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $rsp['item']['unit_amount'], $intl_currency);
        $rsp['item']['subtotal_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $rsp['item']['subtotal_amount'], $intl_currency);
        $rsp['item']['discount_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $rsp['item']['discount_amount'], $intl_currency);
        $rsp['item']['total_amount_display'] = numfmt_format_currency(
            $intl_currency_fmt, $rsp['item']['total_amount'], $intl_currency);
    }

    //
    // Format the currency numbers
    //
    $rsp['invoice']['subtotal_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['subtotal_amount'], $intl_currency);
    $rsp['invoice']['subtotal_discount_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['subtotal_discount_amount'], $intl_currency);
    $rsp['invoice']['discount_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['discount_amount'], $intl_currency);
    $rsp['invoice']['shipping_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['shipping_amount'], $intl_currency);
    $rsp['invoice']['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['total_amount'], $intl_currency);
    $rsp['invoice']['total_savings_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['total_savings'], $intl_currency);
//  $rsp['invoice']['taxes_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
//      $rsp['invoice']['taxes_amount'], $intl_currency);
    $rsp['invoice']['paid_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['paid_amount'], $intl_currency);
    $rsp['invoice']['balance_amount_display'] = numfmt_format_currency($intl_currency_fmt, 
        $rsp['invoice']['balance_amount'], $intl_currency);

    return $rsp;
}
?>
