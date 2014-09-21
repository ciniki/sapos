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
function ciniki_sapos_templates_picklist(&$ciniki, $business_id, $invoice_id, $business_details, $sapos_settings) {
	//
	// Get the invoice record
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
	$rc = ciniki_sapos_invoiceLoad($ciniki, $business_id, $invoice_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$invoice = $rc['invoice'];

	//
	// Get the inventory
	//
	$objects = array();
	foreach($invoice['items'] as $iid => $item) {
		$item = $item['item'];
		if( !isset($objects[$item['object']]) ) {
			$objects[$item['object']] = array();
		}
		$objects[$item['object']][] = $item['object_id'];
	}
	// 
	// Get the inventory levels for each object
	//
	foreach($objects as $object => $object_ids) {
		list($pkg,$mod,$obj) = explode('.', $object);
		$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryLevels');
		if( $rc['stat'] == 'ok' ) {
			$fn = $rc['function_call'];
			$rc = $fn($ciniki, $business_id, array(
				'object'=>$object,
				'object_ids'=>$object_ids,
				));
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1995', 'msg'=>'Unable to get inventory levels.', 'err'=>$rc['err']));
			}
			//
			// Update the inventory levels for the invoice items
			//
			$quantities = $rc['quantities'];
			foreach($invoice['items'] as $iid => $item) {
				if( $item['item']['object'] == $object 
					&& isset($quantities[$item['item']['object_id']]) 
					) {
					$invoice['items'][$iid]['item']['inventory_quantity'] = $quantities[$item['item']['object_id']]['inventory_quantity'];
				}
			}
		}
	}

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
		public $header_height = 0;		// The height of the image and address
		public $business_details = array();
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
						$img_width, 0, 'JPEG', '', 'L', 2, '150');
				} else {
					$this->Image('@'.$this->header_image->getImageBlob(), 15, 12, 
						0, $this->header_height-5, 'JPEG', '', 'L', 2, '150');
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
	$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	//
	// Figure out the header business name and address information
	//
	$pdf->header_height = 0;
	$pdf->header_name = '';
	if( !isset($sapos_settings['invoice-header-contact-position'])
		|| $sapos_settings['invoice-header-contact-position'] != 'off' ) {
		if( !isset($sapos_settings['invoice-header-business-name'])
			|| $sapos_settings['invoice-header-business-name'] == 'yes' ) {
			$pdf->header_name = $business_details['name'];
			$pdf->header_height = 8;
		}
		if( !isset($sapos_settings['invoice-header-business-address'])
			|| $sapos_settings['invoice-header-business-address'] == 'yes' ) {
			if( isset($business_details['contact.address.street1']) 
				&& $business_details['contact.address.street1'] != '' ) {
				$pdf->header_addr[] = $business_details['contact.address.street1'];
			}
			if( isset($business_details['contact.address.street2']) 
				&& $business_details['contact.address.street2'] != '' ) {
				$pdf->header_addr[] = $business_details['contact.address.street2'];
			}
			$city = '';
			if( isset($business_details['contact.address.city']) 
				&& $business_details['contact.address.city'] != '' ) {
				$city .= $business_details['contact.address.city'];
			}
			if( isset($business_details['contact.address.province']) 
				&& $business_details['contact.address.province'] != '' ) {
				$city .= ($city!='')?', ':'';
				$city .= $business_details['contact.address.province'];
			}
			if( isset($business_details['contact.address.postal']) 
				&& $business_details['contact.address.postal'] != '' ) {
				$city .= ($city!='')?'  ':'';
				$city .= $business_details['contact.address.postal'];
			}
			if( $city != '' ) {
				$pdf->header_addr[] = $city;
			}
		}
		if( !isset($sapos_settings['invoice-header-business-phone'])
			|| $sapos_settings['invoice-header-business-phone'] == 'yes' ) {
			if( isset($business_details['contact.phone.number']) 
				&& $business_details['contact.phone.number'] != '' ) {
				$pdf->header_addr[] = 'phone: ' . $business_details['contact.phone.number'];
			}
			if( isset($business_details['contact.tollfree.number']) 
				&& $business_details['contact.tollfree.number'] != '' ) {
				$pdf->header_addr[] = 'phone: ' . $business_details['contact.tollfree.number'];
			}
		}
		if( !isset($sapos_settings['invoice-header-business-cell'])
			|| $sapos_settings['invoice-header-business-cell'] == 'yes' ) {
			if( isset($business_details['contact.cell.number']) 
				&& $business_details['contact.cell.number'] != '' ) {
				$pdf->header_addr[] = 'cell: ' . $business_details['contact.cell.number'];
			}
		}
		if( (!isset($sapos_settings['invoice-header-business-fax'])
			|| $sapos_settings['invoice-header-business-fax'] == 'yes')
			&& isset($business_details['contact.fax.number']) 
			&& $business_details['contact.fax.number'] != '' ) {
			$pdf->header_addr[] = 'fax: ' . $business_details['contact.fax.number'];
		}
		if( (!isset($sapos_settings['invoice-header-business-email'])
			|| $sapos_settings['invoice-header-business-email'] == 'yes')
			&& isset($business_details['contact.email.address']) 
			&& $business_details['contact.email.address'] != '' ) {
			$pdf->header_addr[] = $business_details['contact.email.address'];
		}
		if( (!isset($sapos_settings['invoice-header-business-website'])
			|| $sapos_settings['invoice-header-business-website'] == 'yes')
			&& isset($business_details['contact-website-url']) 
			&& $business_details['contact-website-url'] != '' ) {
			$pdf->header_addr[] = $business_details['contact-website-url'];
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
		$rc = ciniki_images_loadImage($ciniki, $business_id, 
			$sapos_settings['invoice-header-image'], 'original');
		if( $rc['stat'] == 'ok' ) {
			$pdf->header_image = $rc['image'];
		}
	}

	$pdf->business_details = $business_details;
	$pdf->sapos_settings = $sapos_settings;

	//
	// Determine the header details
	//
	$pdf->header_details = array(
		array('label'=>'Invoice Number', 'value'=>$invoice['invoice_number']),
		array('label'=>'Invoice Date', 'value'=>$invoice['invoice_date']),
		);
	if( isset($invoice['po_number']) && $invoice['po_number'] != '' ) {
		$pdf->header_details[] = array('label'=>'PO Number', 'value'=>$invoice['po_number']);
	}
	if( isset($invoice['due_date']) && $invoice['due_date'] != '' ) {
		$pdf->header_details[] = array('label'=>'Due Date', 'value'=>$invoice['due_date']);
	}
	$pdf->header_details[] = array('label'=>'Status', 'value'=>$invoice['status_text']);
//	$pdf->header_details[] = array('label'=>'Balance', 'value'=>$invoice['balance_amount_display']);

	//
	// Setup the PDF basics
	//
	$pdf->SetCreator('Ciniki');
	$pdf->SetAuthor($business_details['name']);
	$pdf->SetTitle('Pick List #' . $invoice['invoice_number']);
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

	//
	// Determine the shipping information
	//
	$saddr = array();
	if( ($invoice['flags']&0x03) > 1 ) {
		if( isset($invoice['shipping_name']) && $invoice['shipping_name'] != '' ) {
			$saddr[] = $invoice['shipping_name'];
		}
		if( isset($invoice['shipping_address1']) && $invoice['shipping_address1'] != '' ) {
			$saddr[] = $invoice['shipping_address1'];
		}
		if( isset($invoice['shipping_address2']) && $invoice['shipping_address2'] != '' ) {
			$saddr[] = $invoice['shipping_address2'];
		}
		$city = '';
		if( isset($invoice['shipping_city']) && $invoice['shipping_city'] != '' ) {
			$city = $invoice['shipping_city'];
		}
		if( isset($invoice['shipping_province']) && $invoice['shipping_province'] != '' ) {
			$city .= (($city!='')?', ':'') . $invoice['shipping_province'];
		}
		if( isset($invoice['shipping_postal']) && $invoice['shipping_postal'] != '' ) {
			$city .= (($city!='')?',  ':'') . $invoice['shipping_postal'];
		}
		if( $city != '' ) {
			$saddr[] = $city;
		}
		if( isset($invoice['shipping_country']) && $invoice['shipping_country'] != '' ) {
			$saddr[] = $invoice['shipping_country'];
		}
	}

	//
	// Output the bill to and ship to information
	//
	if( ($invoice['flags']&0x03) > 1 ) {
		$w = array(90, 90);
	} else {
		$w = array(100, 80);
	}
	$lh = 6;
	$pdf->setCellPaddings(2, 1, 2, 1);
	if( count($baddr) > 0 || count($saddr) > 0 ) {
		$pdf->SetFont('', 'B');
		$pdf->Cell($w[0], $lh, 'Bill To:', 'B', 0, 'L', 1);
		$border = 1;
		if( ($invoice['flags']&0x03) > 1 ) {
			$pdf->Cell($w[1], $lh, 'Ship To:', 'B', 0, 'L', 1);
			$border = 1;
		}
		$pdf->Ln($lh);	
		$pdf->SetFont('');
		$pdf->MultiCell($w[0], $lh, implode("\n", $baddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
		if( ($invoice['flags']&0x03) > 1 ) {
			$pdf->MultiCell($w[1], $lh, implode("\n", $saddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
		}
		$pdf->Ln($lh);
	}
	$pdf->Ln();

	//
	// Add an extra space for invoices with few items
	//
	if( count($baddr) == 0 && count($saddr) == 0 && count($invoice['items']) < 5 ) {
		$pdf->Ln(10);
	}

	//
	// Add the invoice items
	//
	$w = array(120, 30, 30);
	$pdf->SetFillColor(224);
	$pdf->SetFont('', 'B');
	$pdf->SetCellPadding(2);
	$pdf->Cell($w[0], 6, 'Item', 1, 0, 'C', 1);
	$pdf->Cell($w[1], 6, 'Quantity', 1, 0, 'C', 1);
	$pdf->Cell($w[1], 6, 'Inventory', 1, 0, 'C', 1);
//	$pdf->Cell($w[2], 6, 'Total', 1, 0, 'C', 1);
	$pdf->Ln();
	$pdf->SetFillColor(236);
	$pdf->SetTextColor(0);
	$pdf->SetFont('');

	$fill=0;
	foreach($invoice['items'] as $item) {
		//
		// Skip items which are not to be shipped
		//
		if( ($item['item']['flags']&0x02) > 0 ) {
		}

		if( $item['item']['shipped_quantity'] >= $item['item']['quantity'] ) {
			$quantity = '0';
		} else {
			$quantity = $item['item']['quantity'] - $item['item']['shipped_quantity'];
		}

//		if( $item['item']['inventory_quantity'] >= $item['item']['quantity'] ) {
//			$inventory_quantity = '0';
//		} else {
//			$quantity = $item['item']['quantity'] - $item['item']['shipped_quantity'];
//		}

		$discount = '';
//		if( $item['item']['discount_amount'] != 0 ) {
//			if( $item['item']['unit_discount_amount'] > 0 ) {
//				$discount .= '-' . $item['item']['unit_discount_amount_display'] . (($item['item']['quantity']>0&&$item['item']['quantity']!=1)?('x'.$item['item']['quantity']):'');
//			}
//			if( $item['item']['unit_discount_percentage'] > 0 ) {
//				if( $discount != '' ) { 
//					$discount .= ', '; 
//				}
//				$discount .= '-' . $item['item']['unit_discount_percentage'] . '%';
//			}
//			$discount .= ' (-' . $item['item']['discount_amount_display'] . ')';
//		}
		$lh = (($invoice['flags']&0x01)==0)?13:6;
		$lh = 6;
//		$pdf->Cell($w[0], $lh, $item['item']['description'], 1, 0, 'L', $fill, '', 0, false, 'T', 'T');
		if( isset($item['item']['code']) && $item['item']['code'] != '' ) {
			$item['item']['description'] = $item['item']['code'] . ' - ' . $item['item']['description'];
		}
		$nlines = $pdf->getNumLines($item['item']['description'], $w[0]);
		if( $nlines == 2 ) {
			$lh = 3+($nlines*5);
		} elseif( $nlines > 2 ) {
			$lh = 2+($nlines*5);
		}
		// Check if we need a page break
		if( $pdf->getY() > ($pdf->getPageHeight() - 30) ) {
			$pdf->AddPage();
			$pdf->SetFillColor(224);
			$pdf->SetFont('', 'B');
			$pdf->Cell($w[0], 6, 'Item', 1, 0, 'C', 1);
			$pdf->Cell($w[1], 6, 'Quantity', 1, 0, 'C', 1);
			$pdf->Cell($w[1], 6, 'Inventory', 1, 0, 'C', 1);
//			$pdf->Cell($w[2], 6, 'Total', 1, 0, 'C', 1);
			$pdf->Ln();
			$pdf->SetFillColor(236);
			$pdf->SetTextColor(0);
			$pdf->SetFont('');
		}
		$pdf->MultiCell($w[0], $lh, $item['item']['description'], 1, 'L', $fill, 
			0, '', '', true, 0, false, true, 0, 'T', false);
		$pdf->MultiCell($w[1], $lh, $quantity, 1, 'R', $fill, 0, '', '', true, 0, false, true, 0, 'T', false);
		$pdf->MultiCell($w[1], $lh, $item['item']['inventory_quantity'], 1, 'R', $fill, 0, '', '', true, 0, false, true, 0, 'T', false);
//		$pdf->Cell($w[2], $lh, $item['item']['total_amount_display'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
//		$pdf->MultiCell($w[2], $lh, $item['item']['total_amount_display'], 1, 'R', $fill, 
//			0, '', '', true, 0, false, true, 0, 'T', false);
		$pdf->Ln();	
		$fill=!$fill;
	}

	// Check if we need a page break
	if( $pdf->getY() > ($pdf->getPageHeight() - 40) ) {
		$pdf->AddPage();
	}

	//
	// Output the invoice tallies
	//
	$lh = 6;

	//
	// Check if there is a notes to be displayed
	//
	if( isset($invoice['customer_notes']) 
		&& $invoice['customer_notes'] != '' ) {
		$pdf->Ln();
		$pdf->SetFont('');
		$pdf->MultiCell(180, 5, $invoice['customer_notes'], 0, 'L');
	}

	//
	// Check if there is a message to be displayed
	//
	if( isset($sapos_settings['invoice-bottom-message']) 
		&& $sapos_settings['invoice-bottom-message'] != '' ) {
		$pdf->Ln();
		$pdf->SetFont('');
		$pdf->MultiCell(180, 5, $sapos_settings['invoice-bottom-message'], 0, 'L');
	}

	// ---------------------------------------------------------

	//Close and output PDF document
	$pdf->Output('picklist_' . $invoice['invoice_number'] . '.pdf', 'D');

	return array('stat'=>'exit');
}
?>
