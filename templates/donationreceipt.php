<?php
//
// Description
// ===========
// This method will produce a PDF of the invoice.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_sapos_templates_donationreceipt(&$ciniki, $tnid, $invoice_id, $tenant_details, $sapos_settings, $output='download') {
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
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'core', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        //Page header
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 0;      // The height of the image and address
        public $tenant_details = array();
        public $sapos_settings = array();

        public function Header() {
            //
            // Check if there is an image to be output in the header.   The image
            // will be displayed in a narrow box if the contact information is to
            // be displayed as well.  Otherwise, image is scaled to be 100% page width
            // but only to a maximum height of the header_height (set far below).
            //
            $img_width = 0;
            if( $this->header_image != null ) {
                $height = $this->header_image->getImageHeight();
                $width = $this->header_image->getImageWidth();
                $image_ratio = $width/$height;
                if( count($this->header_addr) == 0 && $this->header_name == '' ) {
                    $img_width = 180;
                } else {
                    $img_width = 120;
                    $name_width = $this->getStringWidth($this->header_name, 'times', 'B', 20);
                    if( $name_width > 60 ) {
                        $img_width = 180 - $name_width - 10;
                    }
                }
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, ($this->getY() + 7), 
                        $img_width, 0, 'JPEG', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, ($this->getY() + 7), 
                        0, $this->header_height-5, 'JPEG', '', 'L', 2, '150');
//                    $this->Image('@'.$this->header_image->getImageBlob(), 15, ($this->getY() + 12), 
//                        0, $this->header_height-5, 'JPEG', '', 'L', 2, '150', '', false, false, 1);
                }
            }

            //
            // Add the contact information
            //
            if( !isset($this->sapos_settings['invoice-header-contact-position']) 
                || $this->sapos_settings['invoice-header-contact-position'] != 'off' ) {
                if( isset($this->sapos_settings['invoice-header-contact-position'])
                    && $this->sapos_settings['invoice-header-contact-position'] == 'left' ) {
                    $align = 'L';
                } elseif( isset($this->sapos_settings['invoice-header-contact-position'])
                    && $this->sapos_settings['invoice-header-contact-position'] == 'right' ) {
                    $align = 'R';
                } else {
                    $align = 'C';
                }
                $this->Ln(8);
                if( $this->header_name != '' ) {
                    $this->SetFont('times', 'B', 20);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, 10, '', 0);
                    }
                    $this->Cell(180-$img_width, 10, $this->header_name, 0, false, $align, 0, '', 0, false, 'M', 'M');
                    $this->Ln(5);
                }
                $this->SetFont('times', '', 10);
                if( count($this->header_addr) > 0 ) {
                    $address_lines = count($this->header_addr);
                    if( $img_width > 0 ) {
                        $this->Cell($img_width, ($address_lines*5), '', 0);
                    }
                    $this->MultiCell(180-$img_width, $address_lines, implode("\n", $this->header_addr), 
                        0, $align, 0, 0, '', '', true, 0, false, true, 0, 'M', false);
                    $this->Ln();
                }
            }
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            if( isset($this->sapos_settings['invoice-footer-message']) 
                && $this->sapos_settings['invoice-footer-message'] != '' ) {
                $this->Cell(90, 10, $this->sapos_settings['invoice-footer-message'],
                    0, false, 'L', 0, '', 0, false, 'T', 'M');
                $this->Cell(90, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                    0, false, 'R', 0, '', 0, false, 'T', 'M');
            } else {
                // Center the page number if no footer message.
                $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                    0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_name = '';
    if( !isset($sapos_settings['invoice-header-contact-position'])
        || $sapos_settings['invoice-header-contact-position'] != 'off' ) {
        if( !isset($sapos_settings['invoice-header-tenant-name'])
            || $sapos_settings['invoice-header-tenant-name'] == 'yes' ) {
            $pdf->header_name = $tenant_details['name'];
            $pdf->header_height = 8;
        }
        if( !isset($sapos_settings['invoice-header-tenant-address'])
            || $sapos_settings['invoice-header-tenant-address'] == 'yes' ) {
            if( isset($tenant_details['contact.address.street1']) 
                && $tenant_details['contact.address.street1'] != '' ) {
                $pdf->header_addr[] = $tenant_details['contact.address.street1'];
            }
            if( isset($tenant_details['contact.address.street2']) 
                && $tenant_details['contact.address.street2'] != '' ) {
                $pdf->header_addr[] = $tenant_details['contact.address.street2'];
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
                $pdf->header_addr[] = $city;
            }
        }
        if( !isset($sapos_settings['invoice-header-tenant-phone'])
            || $sapos_settings['invoice-header-tenant-phone'] == 'yes' ) {
            if( isset($tenant_details['contact.phone.number']) 
                && $tenant_details['contact.phone.number'] != '' ) {
                $pdf->header_addr[] = 'phone: ' . $tenant_details['contact.phone.number'];
            }
            if( isset($tenant_details['contact.tollfree.number']) 
                && $tenant_details['contact.tollfree.number'] != '' ) {
                $pdf->header_addr[] = 'phone: ' . $tenant_details['contact.tollfree.number'];
            }
        }
        if( !isset($sapos_settings['invoice-header-tenant-cell'])
            || $sapos_settings['invoice-header-tenant-cell'] == 'yes' ) {
            if( isset($tenant_details['contact.cell.number']) 
                && $tenant_details['contact.cell.number'] != '' ) {
                $pdf->header_addr[] = 'cell: ' . $tenant_details['contact.cell.number'];
            }
        }
        if( (!isset($sapos_settings['invoice-header-tenant-fax'])
            || $sapos_settings['invoice-header-tenant-fax'] == 'yes')
            && isset($tenant_details['contact.fax.number']) 
            && $tenant_details['contact.fax.number'] != '' ) {
            $pdf->header_addr[] = 'fax: ' . $tenant_details['contact.fax.number'];
        }
        if( (!isset($sapos_settings['invoice-header-tenant-email'])
            || $sapos_settings['invoice-header-tenant-email'] == 'yes')
            && isset($tenant_details['contact.email.address']) 
            && $tenant_details['contact.email.address'] != '' ) {
            $pdf->header_addr[] = $tenant_details['contact.email.address'];
        }
        if( (!isset($sapos_settings['invoice-header-tenant-website'])
            || $sapos_settings['invoice-header-tenant-website'] == 'yes')
            && isset($tenant_details['contact-website-url']) 
            && $tenant_details['contact-website-url'] != '' ) {
            $pdf->header_addr[] = $tenant_details['contact-website-url'];
        }
    }
    $pdf->header_height += (count($pdf->header_addr)*5);

    //
    // Set the minimum header height
    //
    if( $pdf->header_height < 30 ) {
        $pdf->header_height = 30;
    }

    //
    // Load the header image
    //
    if( isset($sapos_settings['invoice-header-image']) && $sapos_settings['invoice-header-image'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, 
            $sapos_settings['invoice-header-image'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    $pdf->tenant_details = $tenant_details;
    $pdf->sapos_settings = $sapos_settings;

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle('Receipt #' . $invoice['receipt_number']);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, $pdf->header_height+33, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);


    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    $donation_amount = 0;
    foreach($invoice['items'] as $item) {
        //
        // Check for donations
        //
        if( ($item['item']['flags']&0x8000) == 0x8000 ) {
            $donation_amount = bcadd($donation_amount, $item['item']['total_amount'], 6);
        }
        $discount = '';
    }

    //
    // Check if there is a donation receipt to attached
    //
    $donation_minimum = 25;
    if( isset($sapos_settings['donation-receipt-minimum-amount']) && $sapos_settings['donation-receipt-minimum-amount'] != '' ) {
        $donation_minimum = $sapos_settings['donation-receipt-minimum-amount'];
    }
    if( $donation_amount >= $donation_minimum ) {
        $pdf->AddPage();
        $pdf->setY(($pdf->header_height)+15);

        $date_issued = new DateTime('now', new DateTimezone($intl_timezone));
        //
        // Determine the billing address information
        //
        $addr = array();
        if( isset($invoice['billing_name']) && $invoice['billing_name'] != '' ) {
            $addr[] = $invoice['billing_name'];
        }
        if( isset($invoice['billing_address1']) && $invoice['billing_address1'] != '' ) {
            $addr[] = $invoice['billing_address1'];
        }
        if( isset($invoice['billing_address2']) && $invoice['billing_address2'] != '' ) {
            $addr[] = $invoice['billing_address2'];
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
            $addr[] = $city;
        }
        if( isset($invoice['billing_country']) && $invoice['billing_country'] != '' ) {
            $addr[] = $invoice['billing_country'];
        }

        //
        // Output the details
        //
        $w = array(45, 45, 90);
        $lh = 6;
        $pdf->SetFillColor(255);
        $pdf->setCellPadding(0.5);
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Receipt Number:', 0, 0, 'R', 1);
        $pdf->SetFont('', '');
        $pdf->Cell($w[1], $lh, $invoice['receipt_number'], 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[0])?$addr[0]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Amount Received:', 0, 0, 'R', 1);
        $pdf->SetFont('', '');
        $pdf->Cell($w[1], $lh, '$' . number_format($donation_amount, 2), 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[1])?$addr[1]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Date Received:', 0, 0, 'R', 1);
        $pdf->SetFont('', '');
        $pdf->Cell($w[1], $lh, $invoice['invoice_date'], 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[2])?$addr[2]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Date Issued:', 0, 0, 'R', 1);
        $pdf->SetFont('', '');
        $pdf->Cell($w[1], $lh, $date_issued->format('M d, Y'), 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[3])?$addr[3]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Location Issued:', 0, 0, 'R', 1);
        $pdf->SetFont('', '');
        $pdf->Cell($w[1], $lh, $sapos_settings['donation-receipt-location-issued'], 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[4])?$addr[4]:''), 0, 0, 'L', 1);
        $pdf->Ln();
        
        $w = array(50, 50, 80);
        if( isset($sapos_settings['donation-receipt-thankyou-message']) && $sapos_settings['donation-receipt-thankyou-message'] != '' ) {
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0]+$w[1], $lh*4, $sapos_settings['donation-receipt-thankyou-message'], 0, 0, 'L', 1);
        } else {
            $pdf->Cell($w[0]+$w[1], $lh*4, '', 0, 0, 'L', 1);
        }
        if( isset($sapos_settings['donation-receipt-signature-image']) && $sapos_settings['donation-receipt-signature-image'] != '' ) {
            $pdf->getY();
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
            $rc = ciniki_images_loadImage($ciniki, $tnid, $sapos_settings['donation-receipt-signature-image'], 'original');
            if( $rc['stat'] == 'ok' ) {
                $height = $rc['image']->getImageHeight();
                $width = $rc['image']->getImageWidth();
                $image_ratio = $width/$height;
                $available_ratio = $w[2]/25;
                if( $available_ratio < $image_ratio ) {
                    $pdf->Image('@'.$rc['image']->getImageBlob(), '', '', $w[2], 0, 'JPEG', '', 'C', 2, '150');
                } else {
                    $pdf->Image('@'.$rc['image']->getImageBlob(), '', '', 0, 25, 'JPEG', '', 'C', 2, '150');
                }
            }
        }
        $pdf->Ln();
        $pdf->SetFont('', '');
        $pdf->setCellPadding(2);
        //
        // Output charity information and signature
        //
        $pdf->Cell($w[0]+$w[1], $lh, 'Charity BN/Registration #: ' . $sapos_settings['donation-receipt-charity-number'], 0, 0, 'L', 1);

        $pdf->Cell($w[2], $lh, $sapos_settings['donation-receipt-signing-officer'], 'T', 0, 'R', 1); 
        $pdf->Ln(10);
        $pdf->Cell(180, $lh, 'Official ' . $invoice['donation_year'] . ' Donation Receipt for Income Tax Purposes, Canada Revenue Agency: www.cra.gc.ca/charitiesandgiving', 0, 0, 'L', 1);
        $pdf->Ln(10);

        //
        // Separator between official receipt and summary for customer to keep
        //
        $pdf->Cell(180, $lh, 'detach and retain for your records', array('T'=>array('dash'=>4, 'color'=>array(125,125,125))), 0, 'C', 1);

        $pdf->setCellPadding(1);
        $pdf->Ln(10);

        $pdf->Header();
        $pdf->Ln(15);

        $w = array(45, 45, 90);
        $pdf->Cell($w[0], $lh, 'Receipt Number:', 0, 0, 'R', 1);
        $pdf->Cell($w[1], $lh, $invoice['receipt_number'], 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[0])?$addr[0]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->Cell($w[0], $lh, 'Eligible Amount:', 0, 0, 'R', 1);
        $pdf->Cell($w[1], $lh, '$' . number_format($donation_amount, 2), 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[1])?$addr[1]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->Cell($w[0], $lh, 'Date Received:', 0, 0, 'R', 1);
        $pdf->Cell($w[1], $lh, $invoice['invoice_date'], 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[2])?$addr[2]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->Cell($w[0], $lh, 'Date Issued:', 0, 0, 'R', 1);
        $pdf->Cell($w[1], $lh, $date_issued->format('M d, Y'), 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[3])?$addr[3]:''), 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->Cell($w[0], $lh, 'Location Issued:', 0, 0, 'R', 1);
        $pdf->Cell($w[1], $lh, $invoice['location_issued'], 0, 0, 'L', 1);
        $pdf->Cell($w[2], $lh, (isset($addr[4])?$addr[4]:''), 0, 0, 'L', 1);
        $pdf->Ln();
        
        if( isset($sapos_settings['donation-receipt-thankyou-message']) && $sapos_settings['donation-receipt-thankyou-message'] != '' ) {
            $pdf->SetFont('', 'B');
            $pdf->Cell(180, $lh*2, $sapos_settings['donation-receipt-thankyou-message'], 0, 0, 'C', 1);
        } else {
            $pdf->Cell(180, $lh, '', 0, 0, 'C', 1);
        }
        $pdf->SetFont('', '');
        $pdf->Ln();

        //
        // Output charity information and signature
        //
        $pdf->Cell($w[0]+$w[1], $lh, 'Charity BN/Registration #: ' . $sapos_settings['donation-receipt-charity-number'], 0, 0, 'L', 1);
        $pdf->Ln();

        $pdf->Cell($w[0] + $w[1], $lh, 'Canada Revenue Agency: www.cra.gc.ca/charitiesandgiving', 0, 0, 'L', 1);
        $pdf->Ln();
    } 
    
    else {
        $pdf->AddPage();
        $pdf->setY(($pdf->header_height)+15);

        $pdf->Cell(180, $lh, 'Minimum donation amount not received, receipt not printed.', 0, 0, 'L', 1);
    }


    // ---------------------------------------------------------

    if( $output == 'email' ) {
        return array('stat'=>'ok', 'filename'=>'receipt_' . $invoice['receipt_number'] . '.pdf', 'pdf'=>$pdf, 'invoice'=>$invoice);
    } else {
        //Close and output PDF document
        $pdf->Output('receipt_' . $invoice['receipt_number'] . '.pdf', 'D');
    }

    return array('stat'=>'exit');
}
?>
