<?php
//
// Description
// -----------
// This function will check for competitors in the music festivals
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_wng_accountInvoicesProcess(&$ciniki, $tnid, &$request, $args) {

    $blocks = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Check if any invoices
    //
    $strsql = "SELECT invoices.id, "
        . "invoices.invoice_number, "
        . "CONCAT('#', invoices.invoice_number) AS invoice_number_text, "
        . "CONCAT_WS('.', invoices.invoice_type, invoices.status) AS typestatus, "
        . "invoices.status, "
        . "invoices.status AS status_text, "
        . "invoices.payment_status, "
        . "invoices.payment_status AS payment_status_text, "
        . "DATE_FORMAT(invoices.invoice_date, '%b %e, %Y') AS invoice_date, "
        . "invoices.total_amount "
        . "FROM ciniki_sapos_invoices AS invoices "
        . "WHERE invoices.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
        . "AND invoices.status > 15 "
        . "AND (invoices.invoice_type = 10 OR invoices.invoice_type = 30) "
        . "AND invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY invoices.invoice_date DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'invoices', 'fname'=>'id', 
            'fields'=>array(
                'id', 'invoice_number', 'invoice_number_text', 'typestatus', 'status', 'status_text', 
                'payment_status', 'payment_status_text', 'invoice_date', 'total_amount',
                ),
            'maps'=>array(
                'typestatus'=>$maps['invoice']['typestatus'],
                'status_text'=>$maps['invoice']['status'],
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.432', 'msg'=>'Unable to load invoices', 'err'=>$rc['err']));
    }
    $invoices = isset($rc['invoices']) ? $rc['invoices'] : array();

    $unpaid_invoices = array();
    $paid_invoices = array(); 
    foreach($invoices as $invoice) {
        //
        // Check if download requested
        //
        if( isset($request['uri_split'][3]) 
            && $request['uri_split'][2] == "download"
            && $request['uri_split'][3] == "{$invoice['invoice_number']}.pdf" 
            ) {
            //
            // Load tenant details
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
            $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['details']) && is_array($rc['details']) ) {    
                $tenant_details = $rc['details'];
            } else {
                $tenant_details = array();
            }

            //
            // Load the invoice settings
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
            $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['settings']) ) {
                $sapos_settings = $rc['settings'];
            } else {
                $sapos_settings = array();
            }
            
            // 
            // Generate the invoice
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'templates', 'default');
            $rc = ciniki_sapos_templates_default($ciniki, $tnid, $invoice['id'], $tenant_details, $sapos_settings, 'email');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['pdf']) ) {
                $rc['pdf']->Output('invoice_' . $invoice['invoice_number'] . '.pdf', 'I');
                return array('stat'=>'exit');
            }
        }
        elseif( isset($request['uri_split'][3]) 
            && $request['uri_split'][2] == "view"
            && $request['uri_split'][3] == $invoice['invoice_number']
            ) {
            $args['invoice_id'] = $invoice['id'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'wng', 'accountInvoiceProcess');
            $rc = ciniki_sapos_wng_accountInvoiceProcess($ciniki, $tnid, $request, $args);
            if( $rc['stat'] == 'errmsg' ) {
                $blocks[] = array(
                    'type' => 'msg',
                    'level' => $rc['level'],
                    'content' => $rc['content'],
                    );
            } else {
                return $rc;
            }
        }

        $invoice['total_amount_display'] = '$' . number_format($invoice['total_amount'], 2);
        if( $invoice['status'] == 40 || $invoice['status'] == 42 ) {
            $unpaid_invoices[] = $invoice;
        } elseif( $invoice['status'] >= 45 ) {
            $paid_invoices[] = $invoice;
        }
    }


    if( count($unpaid_invoices) > 0 ) {
        foreach($unpaid_invoices as $pid => $invoice) {
            $unpaid_invoices[$pid]['actions'] = '';
            if( $invoice['status'] == 40 ) {
                // FIXME: Add pay now capability
                $unpaid_invoices[$pid]['actions'] .= "<a class='button' href='{$request['ssl_domain_base_url']}/account/invoices/view/" . $invoice['invoice_number'] . "'>Open</a>";
            }
//            $unpaid_invoices[$pid]['actions'] .= ($unpaid_invoices[$pid]['actions'] != '' ? ' ' : '') 
//                . "<a class='button' href='/account/invoices/download/" . $invoice['invoice_number'] . ".pdf'>Open PDF</a>";
        }
        $blocks[] = array(
            'type' => 'table', 
            'title' => 'Unpaid Invoices',
            'class' => 'fold-at-40 limit-width limit-width-60',
            'columns' => array(
                array('label'=>'Number', 'fold-label'=>'Invoice:', 'field'=>'invoice_number_text', 'class'=>'', ),
                array('label'=>'Date', 'fold-label'=>'Date:', 'field'=>'invoice_date', 'class'=>'', ),
                array('label'=>'Status', 'fold-label'=>'Status:', 'field'=>'typestatus', 'class'=>'', ),
                array('label'=>'Total', 'fold-label'=>'Total:', 'field'=>'total_amount_display', 'class'=>'', ),
                array('label'=>'', 'field'=>'actions', 'class'=>'alignright buttons', ),
                ),
            'rows' => $unpaid_invoices,
            );
    }

    if( count($paid_invoices) > 0 ) {
        foreach($paid_invoices as $pid => $invoice) {
            $paid_invoices[$pid]['actions'] = '';
            $paid_invoices[$pid]['actions'] .= "<a class='button' href='{$request['ssl_domain_base_url']}/account/invoices/view/" . $invoice['invoice_number'] . "'>Open</a>";
        }
        $blocks[] = array(
            'type' => 'table', 
            'title' => 'Paid Invoices',
            'class' => 'fold-at-40 limit-width limit-width-60',
            'columns' => array(
                array('label'=>'Number', 'fold-label'=>'Invoice:', 'field'=>'invoice_number_text', 'class'=>''),
                array('label'=>'Date', 'fold-label'=>'Date:', 'field'=>'invoice_date', 'class'=>''),
                array('label'=>'Status', 'fold-label'=>'Status:', 'field'=>'typestatus', 'class'=>''),
                array('label'=>'Total', 'fold-label'=>'Total:', 'field'=>'total_amount_display', 'class'=>''),
                array('label'=>'', 'field'=>'actions', 'class'=>'alignright buttons'),
                ),
            'rows' => $paid_invoices,
            );
    }

    

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
