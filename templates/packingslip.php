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
function ciniki_sapos_templates_packingslip(&$ciniki, $tnid, $shipment_id, $tenant_details, $sapos_settings) {
    //
    // Get the invoice record
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'packingListLoad');
    $rc = ciniki_sapos_packinglistLoad($ciniki, $tnid, $shipment_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $shipment = $rc['shipment'];
    
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
                }
                $available_ratio = $img_width/$this->header_height;
                // Check if the ratio of the image will make it too large for the height,
                // and scaled based on either height or width.
                if( $available_ratio < $image_ratio ) {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, 12, 
                        $img_width, 0, '', '', 'L', 2, '150');
                } else {
                    $this->Image('@'.$this->header_image->getImageBlob(), 15, 12, 
                        0, $this->header_height-5, '', '', 'L', 2, '150');
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
                    $this->Cell(180-$img_width, 10, $this->header_name, 
                        0, false, $align, 0, '', 0, false, 'M', 'M');
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

            //
            // Output the invoice details which should be at the top of each page.
            //
            $this->SetCellPadding(2);
            if( count($this->header_details) <= 6 ) {
                if( $this->header_name == '' && count($this->header_addr) == 0 ) {
                    $this->Ln($this->header_height+6);
                } elseif( $this->header_name == '' && count($this->header_addr) > 0 ) {
                    $used_space = 4 + count($this->header_addr)*5;
                    if( $used_space < 30 ) {
                        $this->Ln(30-$used_space+5);
                    } else {
                        $this->Ln(7);
                    }
                } elseif( $this->header_name != '' && count($this->header_addr) > 0 ) {
                    $used_space = 10 + count($this->header_addr)*5;
                    if( $used_space < 30 ) {
                        $this->Ln(30-$used_space+6);
                    } else {
                        $this->Ln(5);
                    }
                } elseif( $this->header_name != '' && count($this->header_addr) == 0 ) {
                    $this->Ln(25);
                }
                $this->SetFont('times', '', 10);
                $num_elements = count($this->header_details);
                if( $num_elements == 3 ) {
                    $w = array(60,60,60);
                } elseif( $num_elements == 4 ) {
                    $w = array(45,45,45,45);
                } elseif( $num_elements == 5 ) {
                    $w = array(36,36,36,36,36);
                } else {
                    $w = array(30,30,30,30,30,30);
                }
                $lh = 6;
                $this->SetFont('', 'B');
                for($i=0;$i<$num_elements;$i++) {
                    if( $this->header_details[$i]['label'] != '' ) {
                        $this->SetFillColor(224);
                        $this->Cell($w[$i], $lh, $this->header_details[$i]['label'], 1, 0, 'C', 1);
                    } else {
                        $this->SetFillColor(255);
                        $this->Cell($w[$i], $lh, '', 'T', 0, 'C', 1);
                    }
                }
                $this->Ln();
                $this->SetFillColor(255);
                $this->SetFont('');
                for($i=0;$i<$num_elements;$i++) {
                    if( $this->header_details[$i]['label'] != '' ) {
                        $this->Cell($w[$i], $lh, $this->header_details[$i]['value'], 1, 0, 'C', 1);
                    } else {
                        $this->Cell($w[$i], $lh, '', 0, 0, 'C', 1);
                    }
                }
                $this->Ln();
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
    // Determine the header details
    //
    $pdf->header_details = array(
        array('label'=>'Packing Slip', 'value'=>$shipment['packing_slip_number']),
        array('label'=>'Order Date', 'value'=>$shipment['invoice']['invoice_date']),
        );
    if( isset($shipment['invoice']['po_number']) && $shipment['invoice']['po_number'] != '' ) {
        $pdf->header_details[] = array('label'=>'PO Number', 'value'=>$shipment['invoice']['po_number']);
    }

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle('Packing Slip #' . $shipment['packing_slip_number']);
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
    $pdf->AddPage();
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Determine the billing address information
    //
    $baddr = array();
    if( isset($shipment['invoice']['billing_name']) && $shipment['invoice']['billing_name'] != '' ) {
        $baddr[] = $shipment['invoice']['billing_name'];
    }
    if( isset($shipment['invoice']['billing_address1']) && $shipment['invoice']['billing_address1'] != '' ) {
        $baddr[] = $shipment['invoice']['billing_address1'];
    }
    if( isset($shipment['invoice']['billing_address2']) && $shipment['invoice']['billing_address2'] != '' ) {
        $baddr[] = $shipment['invoice']['billing_address2'];
    }
    $city = '';
    if( isset($shipment['invoice']['billing_city']) && $shipment['invoice']['billing_city'] != '' ) {
        $city = $shipment['invoice']['billing_city'];
    }
    if( isset($shipment['invoice']['billing_province']) && $shipment['invoice']['billing_province'] != '' ) {
        $city .= (($city!='')?', ':'') . $shipment['invoice']['billing_province'];
    }
    if( isset($shipment['invoice']['billing_postal']) && $shipment['invoice']['billing_postal'] != '' ) {
        $city .= (($city!='')?',  ':'') . $shipment['invoice']['billing_postal'];
    }
    if( $city != '' ) {
        $baddr[] = $city;
    }
    if( isset($shipment['invoice']['billing_country']) && $shipment['invoice']['billing_country'] != '' ) {
        $baddr[] = $shipment['invoice']['billing_country'];
    }

    //
    // Determine the shipping information
    //
    $saddr = array();
    if( $shipment['invoice']['shipping_status'] > 0 ) {
        if( isset($shipment['invoice']['shipping_name']) && $shipment['invoice']['shipping_name'] != '' ) {
            $saddr[] = $shipment['invoice']['shipping_name'];
        }
        if( isset($shipment['invoice']['shipping_address1']) && $shipment['invoice']['shipping_address1'] != '' ) {
            $saddr[] = $shipment['invoice']['shipping_address1'];
        }
        if( isset($shipment['invoice']['shipping_address2']) && $shipment['invoice']['shipping_address2'] != '' ) {
            $saddr[] = $shipment['invoice']['shipping_address2'];
        }
        $city = '';
        if( isset($shipment['invoice']['shipping_city']) && $shipment['invoice']['shipping_city'] != '' ) {
            $city = $shipment['invoice']['shipping_city'];
        }
        if( isset($shipment['invoice']['shipping_province']) && $shipment['invoice']['shipping_province'] != '' ) {
            $city .= (($city!='')?', ':'') . $shipment['invoice']['shipping_province'];
        }
        if( isset($shipment['invoice']['shipping_postal']) && $shipment['invoice']['shipping_postal'] != '' ) {
            $city .= (($city!='')?',  ':'') . $shipment['invoice']['shipping_postal'];
        }
        if( $city != '' ) {
            $saddr[] = $city;
        }
        if( isset($shipment['invoice']['shipping_country']) && $shipment['invoice']['shipping_country'] != '' ) {
            $saddr[] = $shipment['invoice']['shipping_country'];
        }
        if( isset($shipment['invoice']['shipping_phone']) && $shipment['invoice']['shipping_phone'] != '' ) {
            $saddr[] = 'Phone: ' . $shipment['invoice']['shipping_phone'];
        }
    }

    //
    // Output the bill to and ship to information
    //
    if( $shipment['invoice']['shipping_status'] > 0 ) {
        $w = array(90, 90);
    } else {
        $w = array(100, 80);
    }
    $lh = 6;
    $pdf->SetFillColor(224);
//  $pdf->setCellPaddings(2, 1, 2, 1);
    $pdf->setCellPadding(2);
    if( count($baddr) > 0 || count($saddr) > 0 ) {
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], $lh, 'Bill To', 1, 0, 'L', 1);
        $border = 1;
        if( $shipment['invoice']['shipping_status'] > 0 ) {
            $pdf->Cell($w[1], $lh, 'Ship To', 1, 0, 'L', 1);
            $border = 1;
            $diff_lines = (count($baddr) - count($saddr));
            // Add padding so the boxes line up
            if( $diff_lines > 0 ) {
                for($i=0;$i<$diff_lines;$i++) {
                    $saddr[] = " ";
                }
            } elseif( $diff_lines < 0 ) {
                for($i=0;$i<abs($diff_lines);$i++) {
                    $baddr[] = " ";
                }
            }
        }
        $pdf->Ln($lh);  
        $pdf->SetFont('');
        $pdf->setCellPaddings(2, 4, 2, 2);
        $pdf->MultiCell($w[0], $lh, implode("\n", $baddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
        if( $shipment['invoice']['shipping_status'] > 0 ) {
            $pdf->MultiCell($w[1], $lh, implode("\n", $saddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
        }
        $pdf->Ln($lh);
    }
    $pdf->Ln();

    //
    // Add an extra space for invoices with few items
    //
    if( count($baddr) == 0 && count($saddr) == 0 && count($shipment['items']) < 5 ) {
        $pdf->Ln(10);
    }

    //
    // Add the shipment details
    //
    if( isset($sapos_settings['packingslip-shipper-info']) 
        && $sapos_settings['packingslip-shipper-info'] == 'yes' 
        ) {
        if( isset($ciniki['tenant']['modules']['ciniki.customers']['flags'])
            && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x020000) > 0 ) {
            // Tax number
            $w = array(30, 30, 30, 30, 30, 30);
        } else {
            $w = array(35, 35, 40, 35, 35);
        }
        $pdf->SetFillColor(224);
        $pdf->SetFont('', 'B');
        $pdf->SetCellPadding(2);
        $pdf->Cell($w[0], 6, 'Ship Date', 1, 0, 'C', 1);
        $pdf->Cell($w[1], 6, 'Shipper', 1, 0, 'C', 1);
        $pdf->Cell($w[2], 6, 'Tracking #', 1, 0, 'C', 1);
        $pdf->Cell($w[3], 6, 'E-mail', 1, 0, 'C', 1);
        $pdf->Cell($w[4], 6, 'Phone #', 1, 0, 'C', 1);
        if( isset($ciniki['tenant']['modules']['ciniki.customers']['flags'])
            && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x020000) > 0 ) {
            $pdf->Cell($w[5], 6, 'Tax #', 1, 0, 'C', 1);
        }
        $pdf->Ln();

        $pdf->SetFillColor(255);
        $pdf->SetFont('');
        $pdf->Cell($w[0], 6, $shipment['ship_date'], 1, 0, 'C', 1);
        $pdf->Cell($w[1], 6, $shipment['shipping_company'], 1, 0, 'C', 1);
        $pdf->Cell($w[2], 6, $shipment['tracking_number'], 1, 0, 'C', 1);
        $pdf->Cell($w[3], 6, $shipment['customer']['email'], 1, 0, 'C', 1);
        $pdf->Cell($w[4], 6, $shipment['customer']['phone'], 1, 0, 'C', 1);
        if( isset($ciniki['tenant']['modules']['ciniki.customers']['flags'])
            && ($ciniki['tenant']['modules']['ciniki.customers']['flags']&0x020000) > 0 ) {
            $pdf->Cell($w[5], 6, $shipment['customer']['tax_number'], 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->Ln();
    }

    //
    // Add the invoice items
    //
    $w = array(120, 30, 30);
    $pdf->SetFillColor(224);
    $pdf->SetFont('', 'B');
    $pdf->SetCellPadding(2);
    $pdf->Cell($w[0], 6, 'Item', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 6, 'Qty', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 6, 'B/O Qty', 1, 0, 'C', 1);
    $pdf->Ln();
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetFont('');

    $fill=0;
    $lh = 6;
    foreach($shipment['invoice']['items'] as $item) {
        if( isset($item['item']['code']) && $item['item']['code'] != '' ) {
            $item['item']['description'] = $item['item']['code'] . ' - ' . $item['item']['description'];
        }
        if( isset($item['item']['notes']) && $item['item']['notes'] != '' ) {
            $item['item']['description'] .= "\n    " . $item['item']['notes'];
        }
        if( isset($item['item']['shipment_notes']) && $item['item']['shipment_notes'] != '' ) {
            $item['item']['description'] .= "\n    " . $item['item']['shipment_notes'];
        }
        $nlines = $pdf->getNumLines($item['item']['description'], $w[0]);
        if( $nlines == 2 ) {
            $lh = 3+($nlines*5);
        } elseif( $nlines > 2 ) {
            $lh = 2+($nlines*5);
        } else {
            $lh = 6;
        }
        // Check if we need a page break
        if( $pdf->getY() > ($pdf->getPageHeight() - 30) ) {
            $pdf->AddPage();
            $pdf->SetFillColor(224);
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0], 6, 'Item', 1, 0, 'C', 1);
            $pdf->Cell($w[1], 6, 'Qty', 1, 0, 'C', 1);
            $pdf->Cell($w[2], 6, 'B/O Qty', 1, 0, 'C', 1);
            $pdf->Ln();
            $pdf->SetFillColor(236);
            $pdf->SetTextColor(0);
            $pdf->SetFont('');
        }
        $pdf->MultiCell($w[0], $lh, $item['item']['description'], 1, 'L', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->MultiCell($w[1], $lh, $item['item']['shipment_quantity'], 1, 'C', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->MultiCell($w[2], $lh, $item['item']['backordered_quantity'], 1, 'C', $fill, 
            0, '', '', true, 0, false, true, 0, 'T', false);
        $pdf->Ln(); 
        $fill=!$fill;
    }

    //
    // Check if there are notes to be added to the invoice
    //
    if( isset($shipment['invoice']['customer_notes']) && $shipment['invoice']['customer_notes'] != '' ) {
        $pdf->Ln();
        $pdf->SetFont('');
        $pdf->MultiCell(180, 5, $shipment['invoice']['customer_notes'], 0, 'L');
    }

    //
    // Check if there is a message to be displayed
    //
    if( isset($sapos_settings['packingslip-bottom-message']) && $sapos_settings['packingslip-bottom-message'] != '' ) {
        if( $pdf->getY() > ($pdf->getPageHeight() - 40) ) {
            $pdf->AddPage();
        } else {
            $pdf->Ln();
        }
        $pdf->SetFont('');
        $pdf->MultiCell(180, 5, $sapos_settings['packingslip-bottom-message'], 0, 'L');
    }

    // ---------------------------------------------------------

    //Close and output PDF document
    $filename = 'packing-slip-' . preg_replace("/ /", '', $shipment['packing_slip_number']) . '.pdf';
    $pdf->Output($filename, 'D');

    return array('stat'=>'exit');
}
?>
