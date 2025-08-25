<?php
//
// Description
// ===========
// This method will produce a PDF of the invoice.
//
// This code is also in default.php ags/templates/donationreceipt.php
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
//function ciniki_sapos_templates_donationreceipt(&$ciniki, $tnid, $invoice_id, $tenant_details, $sapos_settings, $output='download') {
function ciniki_sapos_templates_donationreceipt(&$ciniki, $tnid, $args) {
    //
    // Get the invoice record
    //
    if( isset($args['invoice_id']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
        $rc = ciniki_sapos_invoiceLoad($ciniki, $tnid, $args['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $invoice = $rc['invoice'];
    } elseif( isset($args['invoice']) ) {
        $invoice = $args['invoice'];
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.491', 'msg'=>'No invoice specified for donation receipt'));
    }

    //
    // Load tenant details
    //
    if( !isset($args['tenant_details']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
        $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $tenant_details = isset($rc['details']) ? $rc['details'] : array();
    } else {
        $tenant_details = $args['tenant_details'];
    }

    //
    // Setup defaults if settings hasn't been configured for receipts
    //
    if( !isset($args['sapos_settings']) ) {
        //
        // Load the invoice settings
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
        $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'tnid', $tnid, 'ciniki.sapos', 'settings', '');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $sapos_settings = isset($rc['settings']) ? $rc['settings'] : array();
    } else {
        $sapos_settings = $args['sapos_settings'];
    }

    if( !isset($sapos_settings['donation-receipt-location-issued']) ) {
        $sapos_settings['donation-receipt-location-issued'] = '';
    }
    if( !isset($sapos_settings['donation-receipt-charity-number']) ) {
        $sapos_settings['donation-receipt-charity-number'] = '';
    }
    if( !isset($sapos_settings['donation-receipt-signing-officer']) ) {
        $sapos_settings['donation-receipt-signing-officer'] = '';
    }

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
    if( !isset($args['pdf']) ) {
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
        $args['pdf'] = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

        //
        // Figure out the header tenant name and address information
        //
        $args['pdf']->header_height = 0;
        $args['pdf']->header_name = '';
        if( !isset($sapos_settings['invoice-header-contact-position'])
            || $sapos_settings['invoice-header-contact-position'] != 'off' ) {
            if( !isset($sapos_settings['invoice-header-tenant-name'])
                || $sapos_settings['invoice-header-tenant-name'] == 'yes' ) {
                $args['pdf']->header_name = $tenant_details['name'];
                $args['pdf']->header_height = 8;
            }
            if( !isset($sapos_settings['invoice-header-tenant-address'])
                || $sapos_settings['invoice-header-tenant-address'] == 'yes' ) {
                if( isset($tenant_details['contact.address.street1']) 
                    && $tenant_details['contact.address.street1'] != '' ) {
                    $args['pdf']->header_addr[] = $tenant_details['contact.address.street1'];
                }
                if( isset($tenant_details['contact.address.street2']) 
                    && $tenant_details['contact.address.street2'] != '' ) {
                    $args['pdf']->header_addr[] = $tenant_details['contact.address.street2'];
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
                    $args['pdf']->header_addr[] = $city;
                }
            }
            if( !isset($sapos_settings['invoice-header-tenant-phone'])
                || $sapos_settings['invoice-header-tenant-phone'] == 'yes' ) {
                if( isset($tenant_details['contact.phone.number']) 
                    && $tenant_details['contact.phone.number'] != '' ) {
                    $args['pdf']->header_addr[] = 'phone: ' . $tenant_details['contact.phone.number'];
                }
                if( isset($tenant_details['contact.tollfree.number']) 
                    && $tenant_details['contact.tollfree.number'] != '' ) {
                    $args['pdf']->header_addr[] = 'phone: ' . $tenant_details['contact.tollfree.number'];
                }
            }
            if( !isset($sapos_settings['invoice-header-tenant-cell'])
                || $sapos_settings['invoice-header-tenant-cell'] == 'yes' ) {
                if( isset($tenant_details['contact.cell.number']) 
                    && $tenant_details['contact.cell.number'] != '' ) {
                    $args['pdf']->header_addr[] = 'cell: ' . $tenant_details['contact.cell.number'];
                }
            }
            if( (!isset($sapos_settings['invoice-header-tenant-fax'])
                || $sapos_settings['invoice-header-tenant-fax'] == 'yes')
                && isset($tenant_details['contact.fax.number']) 
                && $tenant_details['contact.fax.number'] != '' ) {
                $args['pdf']->header_addr[] = 'fax: ' . $tenant_details['contact.fax.number'];
            }
            if( (!isset($sapos_settings['invoice-header-tenant-email'])
                || $sapos_settings['invoice-header-tenant-email'] == 'yes')
                && isset($tenant_details['contact.email.address']) 
                && $tenant_details['contact.email.address'] != '' ) {
                $args['pdf']->header_addr[] = $tenant_details['contact.email.address'];
            }
            if( (!isset($sapos_settings['invoice-header-tenant-website'])
                || $sapos_settings['invoice-header-tenant-website'] == 'yes')
                && isset($tenant_details['contact.website.url']) 
                && $tenant_details['contact.website.url'] != '' ) {
                $args['pdf']->header_addr[] = $tenant_details['contact.website.url'];
            } 
            elseif( (!isset($sapos_settings['invoice-header-tenant-website'])
                || $sapos_settings['invoice-header-tenant-website'] == 'yes')
                && isset($tenant_details['contact-website-url']) 
                && $tenant_details['contact-website-url'] != '' ) {
                $args['pdf']->header_addr[] = $tenant_details['contact-website-url'];
            }
        }
        $args['pdf']->header_height += (count($args['pdf']->header_addr)*5);

        //
        // Set the minimum header height
        //
        if( $args['pdf']->header_height < 30 ) {
            $args['pdf']->header_height = 30;
        }

        //
        // Load the header image
        //
        if( isset($sapos_settings['invoice-header-image']) && $sapos_settings['invoice-header-image'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
            $rc = ciniki_images_loadImage($ciniki, $tnid, 
                $sapos_settings['invoice-header-image'], 'original');
            if( $rc['stat'] == 'ok' ) {
                $args['pdf']->header_image = $rc['image'];
            }
        }

        $args['pdf']->tenant_details = $tenant_details;
        $args['pdf']->sapos_settings = $sapos_settings;

        //
        // Setup the PDF basics
        //
        $args['pdf']->SetCreator('Ciniki');
        $args['pdf']->SetAuthor($tenant_details['name']);
        $args['pdf']->SetTitle('Receipt #' . $invoice['receipt_number']);
        $args['pdf']->SetSubject('');
        $args['pdf']->SetKeywords('');

        // set margins
        $args['pdf']->SetMargins(PDF_MARGIN_LEFT, $args['pdf']->header_height+33, PDF_MARGIN_RIGHT);
        $args['pdf']->SetHeaderMargin(PDF_MARGIN_HEADER);
        $args['pdf']->SetFooterMargin(PDF_MARGIN_FOOTER);


        // set font
        $args['pdf']->SetFont('times', 'BI', 10);
        $args['pdf']->SetCellPadding(2);
    } else {
        $args['pdf']->setY(($args['pdf']->header_height)+15);
        $args['pdf']->header_details = array();
        $args['pdf']->SetCellPadding(2);
    }

    // add a page
    $args['pdf']->SetFillColor(255);
    $args['pdf']->SetTextColor(0);
    $args['pdf']->SetDrawColor(51);
    $args['pdf']->SetLineWidth(0.15);

    $donation_amount = 0;
    if( isset($invoice['items']) ) {
        foreach($invoice['items'] as $item) {
            //
            // Check for donations
            //
            if( ($item['item']['flags']&0x8000) == 0x8000 ) {
                $donation_amount = bcadd($donation_amount, $item['item']['total_amount'], 6);
            } elseif( ($item['item']['flags']&0x0800) == 0x0800 ) {
                $donation_amount = bcadd($donation_amount, ($item['item']['quantity'] * $item['item']['unit_donation_amount']), 6);
            }
            $discount = '';
        }
    } elseif( isset($invoice['donation_amount']) ) {
        $donation_amount = $invoice['donation_amount'];
    }

    //
    // Check if there is a donation receipt to attached
    //
    $donation_minimum = 25;
    if( isset($sapos_settings['donation-receipt-minimum-amount']) && $sapos_settings['donation-receipt-minimum-amount'] != '' ) {
        $donation_minimum = $sapos_settings['donation-receipt-minimum-amount'];
    }
    if( $donation_amount >= $donation_minimum ) {
        $args['pdf']->AddPage();
        $args['pdf']->setY(($args['pdf']->header_height)+15);

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
        $args['pdf']->SetFillColor(255);
        $args['pdf']->setCellPadding(0.5);
        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Receipt Number:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, $invoice['receipt_number'], 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[0])?$addr[0]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Amount Received:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, '$' . number_format($donation_amount, 2), 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[1])?$addr[1]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Date Received:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, (isset($invoice['received_date']) ? $invoice['received_date'] : $invoice['invoice_date']), 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[2])?$addr[2]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Date Issued:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, $invoice['invoice_date'], 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[3])?$addr[3]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Location Issued:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, $sapos_settings['donation-receipt-location-issued'], 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[4])?$addr[4]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();
        
        $w = array(50, 50, 80);
        if( isset($sapos_settings['donation-receipt-thankyou-message']) && $sapos_settings['donation-receipt-thankyou-message'] != '' ) {
            $args['pdf']->SetFont('', 'B');
//            $args['pdf']->Cell($w[0]+$w[1], $lh*4, $sapos_settings['donation-receipt-thankyou-message'], 0, 0, 'L', 1);
            $args['pdf']->Ln(10);
            $args['pdf']->MultiCell($w[0]+$w[1]-10, 20, $sapos_settings['donation-receipt-thankyou-message'], 0, 'L', 0, 0);
        } else {
            $args['pdf']->Cell($w[0]+$w[1], $lh*4, '', 0, 0, 'L', 1);
        }
        if( isset($sapos_settings['donation-receipt-signature-image']) && $sapos_settings['donation-receipt-signature-image'] != '' ) {
            $args['pdf']->getY();
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
            $rc = ciniki_images_loadImage($ciniki, $tnid, $sapos_settings['donation-receipt-signature-image'], 'original');
            if( $rc['stat'] == 'ok' ) {
                $height = $rc['image']->getImageHeight();
                $width = $rc['image']->getImageWidth();
                $image_ratio = $width/$height;
                $available_ratio = $w[2]/25;
                if( $available_ratio < $image_ratio ) {
                    $args['pdf']->Image('@'.$rc['image']->getImageBlob(), '', '', $w[2], 0, 'JPEG', '', 'C', 2, '150');
                } else {
                    $left_padding = floor(($w[2] - ceil($image_ratio * 25))/2);
                    $args['pdf']->Image('@'.$rc['image']->getImageBlob(), 115 + $left_padding, '', 0, 25, 'JPEG', '', 'C', 0, '150', '', false, false, 0, true);
//                    $args['pdf']->Image('@'.$rc['image']->getImageBlob(), $args['pdf']->getX() + $left_padding, '', 0, 25, 'JPEG', '', 'C', 2, '150', '', false, false, 1);
                }
            }
        }
        $args['pdf']->Ln(25);
        $args['pdf']->SetFont('', '');
        $args['pdf']->setCellPadding(2);
        //
        // Output charity information and signature
        //
        $args['pdf']->Cell($w[0]+$w[1], $lh, 'Charity BN/Registration #: ' . $sapos_settings['donation-receipt-charity-number'], 0, 0, 'L', 1);

        $args['pdf']->Cell($w[2], $lh, $sapos_settings['donation-receipt-signing-officer'], 'T', 0, 'R', 1); 
        $args['pdf']->Ln(10);
        $args['pdf']->Cell(180, $lh, 'Official ' . $invoice['donation_year'] . ' Donation Receipt for Income Tax Purposes, Canada Revenue Agency: canada.ca/charities-giving', 0, 0, 'C', 1);
        $args['pdf']->Ln(10);

        //
        // Separator between official receipt and summary for customer to keep
        //
        $args['pdf']->Ln(4);
        $args['pdf']->Cell(180, $lh, 'detach and retain for your records', array('T'=>array('dash'=>4, 'color'=>array(125,125,125))), 0, 'C', 1);

        $args['pdf']->setCellPadding(1);
        $args['pdf']->Ln(8);

        $args['pdf']->Header();
        $args['pdf']->Ln(10);
        $args['pdf']->SetCellPadding(0.5);

        $w = array(45, 45, 90);
        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Receipt Number:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, $invoice['receipt_number'], 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[0])?$addr[0]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Eligible Amount:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, '$' . number_format($donation_amount, 2), 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[1])?$addr[1]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Date Received:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, (isset($invoice['received_date']) ? $invoice['received_date'] : $invoice['invoice_date']), 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[2])?$addr[2]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Date Issued:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, $invoice['invoice_date'], 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[3])?$addr[3]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->SetFont('', 'B');
        $args['pdf']->Cell($w[0], $lh, 'Location Issued:', 0, 0, 'R', 1);
        $args['pdf']->SetFont('', '');
        $args['pdf']->Cell($w[1], $lh, $sapos_settings['donation-receipt-location-issued'], 0, 0, 'L', 1);
        $args['pdf']->Cell($w[2], $lh, (isset($addr[4])?$addr[4]:''), 0, 0, 'L', 1);
        $args['pdf']->Ln();
        
        if( isset($sapos_settings['donation-receipt-thankyou-message']) && $sapos_settings['donation-receipt-thankyou-message'] != '' ) {
            $args['pdf']->SetFont('', 'B');
            $args['pdf']->Cell(180, $lh*2, $sapos_settings['donation-receipt-thankyou-message'], 0, 0, 'C', 1);
        } else {
            $args['pdf']->Cell(180, $lh, '', 0, 0, 'C', 1);
        }
        $args['pdf']->SetFont('', '');
        $args['pdf']->Ln();

        //
        // Output charity information and signature
        //
        $args['pdf']->Cell($w[0]+$w[1], $lh, 'Charity BN/Registration #: ' . $sapos_settings['donation-receipt-charity-number'], 0, 0, 'L', 1);
        $args['pdf']->Ln();

        $args['pdf']->Cell($w[0] + $w[1], $lh, 'Canada Revenue Agency: canada.ca/charities-giving', 0, 0, 'L', 1);
        $args['pdf']->Ln();
    } 
    
    else {
        $args['pdf']->AddPage();
        $args['pdf']->setY(($args['pdf']->header_height)+15);

        $args['pdf']->Cell(180, 12, 'Minimum donation amount not received, receipt not printed.', 0, 0, 'L', 1);
    }

    //
    // Check if invoice.donationreceipt_status should be updated
    //
    if( isset($args['output']) && $args['output'] == 'email' && $invoice['donationreceipt_status'] < 40 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.sapos.invoice', $invoice['id'], ['donationreceipt_status' => 70], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.57', 'msg'=>'Unable to update the invoice', 'err'=>$rc['err']));
        }
    }


    // ---------------------------------------------------------

//    if( $args['output'] == 'email' ) {
//        return array('stat'=>'ok', 'filename'=>'receipt_' . $invoice['receipt_number'] . '.pdf', 'pdf'=>$args['pdf'], 'invoice'=>$invoice);
//    } else {
//        //Close and output PDF document
//        $args['pdf']->Output('receipt_' . $invoice['receipt_number'] . '.pdf', 'I');
//    }

    return array('stat'=>'ok', 'pdf'=>$args['pdf']);
}
?>
