<?php
//
// Description
// ===========
// This function will product a PDF of a mailing envelope.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_templates_envelope(&$ciniki, $tnid, $invoice_id, $tenant_details, $sapos_settings) {
    //
    // Get the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
    $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $invoice_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];
    
    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public function Header() {
        }
        public function Footer() {
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, "COMMENV_N10", true, 'UTF-8', false);
//  $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $return_addr_height = 0;
    $return_addr = array();

//  if( !isset($sapos_settings['invoice-header-tenant-name'])
//      || $sapos_settings['invoice-header-tenant-name'] == 'yes' ) {
        $return_addr[] = $tenant_details['name'];
//  }
    if( isset($tenant_details['contact.address.street1']) 
        && $tenant_details['contact.address.street1'] != '' ) {
        $return_addr[] = $tenant_details['contact.address.street1'];
    }
    if( isset($tenant_details['contact.address.street2']) 
        && $tenant_details['contact.address.street2'] != '' ) {
        $return_addr[] = $tenant_details['contact.address.street2'];
    }
    $city = '';
    if( isset($tenant_details['contact.address.city']) 
        && $tenant_details['contact.address.city'] != '' ) {
        $city .= $tenant_details['contact.address.city'];
    }
    if( isset($tenant_details['contact.address.province']) 
        && $tenant_details['contact.address.province'] != '' ) {
        $city .= ($city!='')?', ':'';
        $city .= $tenant_details['contact.address.province'];
    }
    if( isset($tenant_details['contact.address.postal']) 
        && $tenant_details['contact.address.postal'] != '' ) {
        $city .= ($city!='')?'  ':'';
        $city .= $tenant_details['contact.address.postal'];
    }
    if( $city != '' ) {
        $return_addr[] = $city;
    }
    $return_addr_height += (count($return_addr)*5);

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor('');
    $pdf->SetTitle('Invoice #' . $invoice['invoice_number'] . ' envelope');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);


    // set font
    $pdf->SetFont('times', 'B', 12);

    // add a page
    $pdf->AddPage();
    $pdf->SetTextColor(0);

    //
    // Determine the billing address information
    //
    $baddr = array();
    if( isset($invoice['billing_name']) && $invoice['billing_name'] != '' ) {
        $baddr[] = $invoice['billing_name'];
    }
    if( isset($invoice['billing_address1']) && $invoice['billing_address1'] != '' ) {
        $baddr[] = $invoice['billing_address1'];
    }
    if( isset($invoice['billing_address2']) && $invoice['billing_address2'] != '' ) {
        $baddr[] = $invoice['billing_address2'];
    }
    $city = '';
    if( isset($invoice['billing_city']) && $invoice['billing_city'] != '' ) {
        $city = $invoice['billing_city'];
    }
    if( isset($invoice['billing_province']) && $invoice['billing_province'] != '' ) {
        $city .= (($city!='')?', ':'') . $invoice['billing_province'];
    }
    if( isset($invoice['billing_postal']) && $invoice['billing_postal'] != '' ) {
        $city .= (($city!='')?',  ':'') . $invoice['billing_postal'];
    }
    if( $city != '' ) {
        $baddr[] = $city;
    }
    if( isset($invoice['billing_country']) && $invoice['billing_country'] != '' ) {
        $baddr[] = $invoice['billing_country'];
    }

    $pdf->MultiCell(100, $return_addr_height, implode("\n", $return_addr), 0, 'L', 0, 
        0, 10, 10, true, 0, false, true, 0, 'T', false);

    $pdf->MultiCell(0, 0, implode("\n", $baddr), 0, 'L', 0, 
        0, 85, 50, true, 0, false, true, 0, 'T', false);

    //Close and output PDF document
    $pdf->Output('invoice_' . $invoice['invoice_number'] . '_env.pdf', 'D');

    return array('stat'=>'exit');
}
?>
