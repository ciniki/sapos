<?php
//
// Description
// ===========
// This method will add a new invoice to the system, creating item entries if specified.  If
// a customer is specified, the billing/shipping address will be pulled from the customer record.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_emailTemplateProcess(&$ciniki, $tnid, $args) {

    if( !isset($args['invoice_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.526', 'msg'=>'No invoice specified'));
    }
    if( !isset($args['template_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.527', 'msg'=>'No template specified'));
    }

    //
    // Return the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $args['invoice_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];
    foreach($invoice['items'] as $iid => $item) {   
        if( isset($item['item']) ) {
            $invoice['items'][$iid] = $item['item'];
        }
    }

    //
    // Load email templates available
    //
    $strsql = "SELECT templates.id, "
        . "templates.name, "
        . "templates.subject, "
        . "templates.message "
        . "FROM ciniki_sapos_email_templates AS templates "
        . "WHERE templates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND templates.id = '" . ciniki_core_dbQuote($ciniki, $args['template_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'template');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.524', 'msg'=>'Unable to load template', 'err'=>$rc['err']));
    }
    if( !isset($rc['template']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.525', 'msg'=>'Unable to find requested template'));
    }
    $template = $rc['template'];
    $subject = $template['subject'];
    $message = $template['message'];

    //
    // Make any substitutions of invoice information
    //
    $subject = str_replace("{_invoicenumber_}", $invoice['invoice_number'], $subject);
    $message = str_replace("{_invoicenumber_}", $invoice['invoice_number'], $message);
    $subject = str_replace("{_invoicedate_}", $invoice['invoice_date'], $subject);
    $message = str_replace("{_invoicedate_}", $invoice['invoice_date'], $message);
    $subject = str_replace("{_invoicetotal_}", '$' . number_format($invoice['total_amount'], 2), $subject);
    $message = str_replace("{_invoicetotal_}", '$' . number_format($invoice['total_amount'], 2), $message);
    $subject = str_replace("{_invoicebalance_}", '$' . number_format($invoice['balance_amount'], 2), $subject);
    $message = str_replace("{_invoicebalance_}", '$' . number_format($invoice['balance_amount'], 2), $message);

    //
    // Get the list of objects from
    //
    $objects = [];
    foreach($invoice['items'] as $item) {
        if( $item['object'] != '' && preg_match("/^(([a-z]+)\.([a-z]+))\./", $item['object'], $m) ) {
            if( !isset($objects[$m[1]]) ) {
                $objects[$m[1]] = [
                    'pkg' => $m[2],
                    'mod' => $m[3],
                    ];
            }
        }
    }
    foreach($objects as $object) {
        $rc = ciniki_core_loadMethod($ciniki, $object['pkg'], $object['mod'], 'hooks', 'invoiceEmailTemplateProcess');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, [
                'invoice' => $invoice,
                'subject' => $subject,
                'message' => $message,
                ]);
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            } 
            if( isset($rc['subject']) ) {
                $subject = $rc['subject'];
            }
            if( isset($rc['message']) ) {
                $message = $rc['message'];
            }
        }
    }

    return array('stat'=>'ok', 'subject'=>$subject, 'message'=>$message);
}
?>
