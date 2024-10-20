<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_sapos_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
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

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

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
    // Setup current date in tenant timezone
    //
    $cur_date = new DateTime('now', new DateTimeZone($intl_timezone));

    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get the invoices for a customer
    //
    $strsql = "SELECT ciniki_sapos_invoices.id, "
        . "ciniki_sapos_invoices.invoice_type, "
        . "ciniki_customers.display_name AS customer_name, "
        . "ciniki_sapos_invoices.invoice_number, "
        . "ciniki_sapos_invoices.po_number, "
        . "ciniki_sapos_invoices.invoice_date, "
        . "ciniki_sapos_invoices.invoice_date AS sort_date, "
        . "ciniki_sapos_invoices.status, "
//      . "ciniki_sapos_invoices.status AS status_text, "
        . "ciniki_sapos_invoices.work_type, "
        . "ciniki_sapos_invoices.work_address1, "
        . "ciniki_sapos_invoices.work_address2, "
        . "ciniki_sapos_invoices.work_city, "
        . "ciniki_sapos_invoices.work_province, "
        . "CONCAT_WS('.', ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.status) AS status_text, "
        . "ciniki_sapos_invoices.total_amount "
        . "FROM ciniki_sapos_invoices "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_sapos_invoices.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    if( isset($args['customer_id']) ) {
        $strsql .= "AND ciniki_sapos_invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
        $strsql .= "AND ciniki_sapos_invoices.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        return array('stat'=>'ok');
    }
    $strsql .= "ORDER BY ciniki_sapos_invoices.invoice_type, ciniki_sapos_invoices.invoice_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'types', 'fname'=>'invoice_type', 'fields'=>array('type'=>'invoice_type')),
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array('id', 'customer_name', 'invoice_number', 'po_number', 'invoice_date', 'sort_date',
                'work_type', 'work_address1', 'work_address2', 'work_city', 'work_province',
                'status', 'status_text', 'total_amount'),
            'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
            'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format))), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    

    //
    // Setup the section for invoices
    //
    $sections = array();
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x01) ) {
        $sections['ciniki.sapos.invoices'] = array(
            'label' => 'Invoices',
            'type' => 'simplegrid', 
            'num_cols' => 4,
            'headerValues' => array('Invoice #', 'Date', 'Amount', 'Status'),
            'cellClasses' => array('', ''),
            'noData' => 'No invoices',
            'addTxt' => 'Add Invoice',
            'addApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array(
                'customer_id'=>(isset($args['customer_ids'][0]) ? $args['customer_ids'][0] : $args['customer_id']),
                'invoice_type'=>'10',
                )),
            'editApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.id;')),
            'data' => array(),
            'sortable' => 'yes',
            'sortTypes' => array('number', 'date', 'number', 'text'),
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.total_amount_display;',
                '3' => 'd.status_text;',
                ),
            );
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x020000) ) {
            $sections['ciniki.sapos.invoices']['cellClasses'] = array('multiline', '', '', '');
            $sections['ciniki.sapos.invoices']['cellValues']['0'] = 'M.multiline(d.invoice_number, d.work_address);';
        }
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x08) ) {
        $carts['ciniki.sapos.carts'] = array(
            'label' => 'Carts',
            'type' => 'simplegrid', 
            'num_cols' => 4,
            'headerValues' => array('Invoice #', 'Date', 'Amount', 'Status'),
            'cellClasses' => array('', ''),
            'noData' => 'No carts',
            'editApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.id;')),
            'data' => array(),
            'sortable' => 'yes',
            'sortTypes' => array('number', 'date', 'number', 'text'),
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.total_amount_display;',
                '3' => 'd.status_text;',
                ),
            );
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x20) ) {
        $sections['ciniki.sapos.orders'] = array(
            'label' => 'Orders',
            'type' => 'simplegrid', 
            'num_cols' => 4,
            'headerValues' => array('Order #', 'Date', 'Amount', 'Status'),
            'cellClasses' => array('', ''),
            'noData' => 'No carts',
            'editApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.id;')),
            'data' => array(),
            'sortable' => 'yes',
            'sortTypes' => array('number', 'date', 'number', 'text'),
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.total_amount_display;',
                '3' => 'd.status_text;',
                ),
            );
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x010000) ) {
        $quotes['ciniki.sapos.quotes'] = array(
            'label' => 'Quotes',
            'type' => 'simplegrid', 
            'num_cols' => 3,
            'headerValues' => array('Quote #', 'Date', 'Amount'),
            'cellClasses' => array('', ''),
            'noData' => 'No carts',
            'editApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.id;')),
            'data' => array(),
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.total_amount_display;',
                ),
            );
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x020000) ) {
            $sections['ciniki.sapos.quotes']['cellClasses'] = array('multiline', '', '', '');
            $sections['ciniki.sapos.quotes']['cellValues']['0'] = 'M.multiline(d.invoice_number, d.work_address);';
        }
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x1000) ) {   // Recurring
        $recurring['ciniki.sapos.monthly'] = array(
            'id' => 'ciniki.sapos.monthly',
            'label' => 'Monthly',
            'type' => 'simplegrid', 
            'num_cols' => 3,
            'headerValues' => array('Invoice #', 'Date', 'Amount'),
            'cellClasses' => array('', ''),
            'noData' => 'No monthly recurring invoices',
            'editApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.id;')),
            'data' => array(),
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.total_amount_display;',
                ),
            );
        $recurring['ciniki.sapos.yearly'] = array(
            'id' => 'ciniki.sapos.yearly',
            'label' => 'Yearly',
            'type' => 'simplegrid', 
            'num_cols' => 3,
            'headerValues' => array('Invoice #', 'Date', 'Amount'),
            'cellClasses' => array('', ''),
            'noData' => 'No yearly recurring invoices',
            'editApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.id;')),
            'data' => array(),
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.total_amount_display;',
                ),
            );
    }
    if( !isset($rc['types']) ) {
        return array('stat'=>'ok', 'tabs'=>array(array(
            'id' => 'ciniki.sapos.invoices',
            'label' => 'Invoices',
            'sections' => $sections,
            )));
    }
    $types = $rc['types'];
    foreach($types as $tid => $type) {
        foreach($type['invoices'] as $iid => $invoice) {
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x020000) ) { 
                if( $invoice['work_type'] != '' ) { 
                    $invoice['invoice_number'] .= ' - ' . $invoice['work_type'];
                }
                $invoice['work_address'] = $invoice['work_address1'];
                if( $invoice['work_address2'] != '' ) {
                    $invoice['work_address'] .= ($invoice['work_address'] != '' ? ', ' : '') . $invoice['work_address1'];
                }
                if( $invoice['work_city'] != '' ) {
                    $invoice['work_address'] .= ($invoice['work_address'] != '' ? ', ' : '') . $invoice['work_city'];
                }
                if( $invoice['work_province'] != '' ) {
                    $invoice['work_address'] .= ($invoice['work_address'] != '' ? ', ' : '') . $invoice['work_province'];
                }
            }
            
            $invoice['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, $invoice['total_amount'], $intl_currency);
            if( $type['type'] == 10 && isset($sections['ciniki.sapos.invoices']) ) {
                $sections['ciniki.sapos.invoices']['data'][] = $invoice;
            } elseif( $type['type'] == 20 && isset($carts['ciniki.sapos.carts']) ) {
                $carts['ciniki.sapos.carts']['data'][] = $invoice;
            } elseif( $type['type'] == 11 ) {
                $recurring['ciniki.sapos.monthly']['data'][] = $invoice;
            } elseif( $type['type'] == 19 ) {
                $recurring['ciniki.sapos.yearly']['data'][] = $invoice;
            } elseif( $type['type'] == 30 && isset($sections['ciniki.sapos.invoices']) ) {
                $sections['ciniki.sapos.invoices']['data'][] = $invoice;
            } elseif( $type['type'] == 40 && isset($sections['ciniki.sapos.orders']) ) {
                $sections['ciniki.sapos.orders']['data'][] = $invoice;
            } elseif( $type['type'] == 90 && isset($quotes['ciniki.sapos.quotes']) ) {
                $quotes['ciniki.sapos.quotes']['data'][] = $invoice;
            }
        }
    }
    usort($sections['ciniki.sapos.invoices']['data'], function($a, $b) {
        if( $a['sort_date'] == $b['sort_date'] ) {
            return 0;
        }
        return $a['sort_date'] > $b['sort_date'] ? -1 : 1;
        });

    //
    // Add a tab the customer UI data screen with the certificate list
    //
    $rsp['tabs'][] = array(
        'id' => 'ciniki.sapos.invoices',
        'label' => 'Invoices',
        'priority' => 8010,
        'sections' => $sections,
        );
    if( isset($recurring) ) {
        $rsp['tabs'][] = array(
            'id' => 'ciniki.sapos.recurring',
            'label' => 'Recurring',
            'priority' => 8006,
            'sections' => $recurring,
            );
    }
    if( isset($carts) ) {
        $rsp['tabs'][] = array(
            'id' => 'ciniki.sapos.carts',
            'label' => 'Carts',
            'priority' => 8001,
            'sections' => $carts,
            );
    }
    if( isset($quotes) ) {
        $rsp['tabs'][] = array(
            'id' => 'ciniki.sapos.quotes',
            'label' => 'Quotes',
            'priority' => 8005,
            'sections' => $quotes,
            );
    }

    //
    // Check for donations
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x02000000) ) {
        $strsql = "SELECT invoices.id, "
            . "invoices.invoice_type, "
            . "invoices.invoice_number, "
            . "invoices.invoice_date, "
            . "invoices.invoice_date AS sort_date, "
            . "invoices.status, "
            . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS status_text, "
            . "items.id AS item_id, "
            . "items.flags AS item_flags, "
            . "items.quantity, "
            . "items.unit_donation_amount, "
            . "items.total_amount "
            . "FROM ciniki_sapos_invoices AS invoices "
            . "INNER JOIN ciniki_sapos_invoice_items AS items ON ("
                . "invoices.id = items.invoice_id "
                . "AND (items.flags&0x8800) > 0 "   // Full or partial donation
                . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND invoices.invoice_type IN (10,30) "
            . "";
        if( isset($args['customer_id']) ) {
            $strsql .= "AND invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
        } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
            $strsql .= "AND invoices.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
        } else {
            return array('stat'=>'ok');
        }
        $strsql .= "ORDER BY invoices.invoice_date DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'invoices', 'fname'=>'id', 
                'fields'=>array('id', 'invoice_number', 'invoice_date', 'sort_date', 'status', 'status_text'),
                'maps'=>array('status_text'=>$maps['invoice']['typestatus']),
                'utctotz'=>array('invoice_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
                ), 
            array('container'=>'items', 'fname'=>'item_id', 
                'fields'=>array('id'=>'item_id', 'flags'=>'item_flags', 'quantity', 'unit_donation_amount', 'total_amount'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
            error_log(print_r($rc,true));
        $invoices = isset($rc['invoices']) ? $rc['invoices'] : array();
        foreach($invoices as $iid => $invoice) {
            $invoices[$iid]['donation_amount'] = 0;
            if( isset($invoice['items']) ) {
                foreach($invoice['items'] as $item) {
                    if( ($item['flags']&0x0800) == 0x0800 ) {
                        $invoices[$iid]['donation_amount'] = bcadd($invoices[$iid]['donation_amount'], ($item['quantity'] * $item['unit_donation_amount']), 6);
                    } elseif( ($item['flags']&0x8000) == 0x8000 ) {
                        $invoices[$iid]['donation_amount'] = bcadd($invoices[$iid]['donation_amount'], $item['total_amount'], 6);
                    } 
                }
            }
            $invoices[$iid]['donation_amount'] = '$' . number_format($invoices[$iid]['donation_amount'], 2);
        }
        $donations = array(
            'label' => 'Donations',
            'type' => 'simplegrid', 
            'num_cols' => 4,
            'headerValues' => array('Invoice #', 'Date', 'Amount', 'Status'),
            'cellClasses' => array('', ''),
            'noData' => 'No donations',
            'addTxt' => 'Add Invoice',
            'addApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array(
                'customer_id'=>(isset($args['customer_ids'][0]) ? $args['customer_ids'][0] : $args['customer_id']),
                'invoice_type'=>'10',
                )),
            'editApp' => array('app'=>'ciniki.sapos.invoice', 'args'=>array('invoice_id'=>'d.id;')),
            'data' => $invoices,
            'sortable' => 'yes',
            'sortTypes' => array('number', 'date', 'number', 'text'),
            'cellValues' => array(
                '0' => 'd.invoice_number;',
                '1' => 'd.invoice_date;',
                '2' => 'd.donation_amount;',
                '3' => 'd.status_text;',
                ),
            );
        $rsp['tabs'][] = array(
            'id' => 'ciniki.sapos.donations',
            'label' => 'Donations',
            'priority' => 1501,
            'sections' => array(
                'ciniki.sapos.donations' => $donations,
                ),
            );
    }

    return $rsp;
}
?>
