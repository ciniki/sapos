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
function ciniki_sapos_invoicePDFDefault(&$ciniki, $business_id, $invoice_id, $business_details, $sapos_settings) {
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
	// Load TCPDF library
	//
	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

	error_log(print_r($business_details, true));

	//
	// Start a new document
	//
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor($business_details['name']);
	$pdf->SetTitle('Invoice ' . $invoice['invoice_number']);
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// set margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
	$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);


	// set font
	$pdf->SetFont('times', 'BI', 10);

	// add a page
	$pdf->AddPage();
	$pdf->SetFillColor(255);
	$pdf->SetTextColor(0);
	$pdf->SetDrawColor(200);
	$pdf->SetLineWidth(0.1);

	//
	// Add the header to the invoice
	//
	// If logo set, place left
	// else place business address left


	$invoice_details = array(
		array('label'=>'Invoice Number', 'value'=>$invoice['invoice_number']),
		array('label'=>'Invoice Date', 'value'=>$invoice['invoice_date']),
		);
	if( isset($invoice['due_date']) && $invoice['due_date'] != '' ) {
		$invoice_details[] = array('label'=>'Due Date', 'value'=>$invoice['due_date']);
	} else {
//		$invoice_details[] = array('label'=>'', 'value'=>'');
	}
//	$invoice_details[] = array('label'=>'', 'value'=>'');
	$invoice_details[] = array('label'=>'Status', 'value'=>$invoice['status_text']);

	if( count($invoice_details) <= 5 ) {
		$num_elements = count($invoice_details);
		if( $num_elements == 3 ) {
			$w = array(60,60,60);
		} elseif( $num_elements == 4 ) {
			$w = array(45,45,45,45);
		} else {
			$w = array(36,36,36,36,36);
		}
		$lh = 6;
		$pdf->SetFont('', 'B');
		for($i=0;$i<$num_elements;$i++) {
			if( $invoice_details[$i]['label'] != '' ) {
				$pdf->SetFillColor(232);
				$pdf->Cell($w[$i], $lh, $invoice_details[$i]['label'], 1, 0, 'C', 1);
			} else {
				$pdf->SetFillColor(255);
				$pdf->Cell($w[$i], $lh, '', 'T', 0, 'C', 1);
			}
		}
		$pdf->Ln();
		$pdf->SetFillColor(255);
		for($i=0;$i<$num_elements;$i++) {
			if( $invoice_details[$i]['label'] != '' ) {
				$pdf->Cell($w[$i], $lh, $invoice_details[$i]['value'], 1, 0, 'C', 1);
			} else {
				$pdf->Cell($w[$i], $lh, '', 0, 0, 'C', 1);
			}
		}
		$pdf->Ln();
	}
	$pdf->Ln();
//	$pdf->Cell($w[1], $lh, 'Invoice Date', 1, 0, 'C', 1);
//	if( isset($invoice['due_date']) && $invoice['due_date'] != '' ) {
//		$pdf->Cell($w[2], $lh, 'Due Date', 1, 0, 'C', 1);
//	} else {
//		$pdf->SetFillColor(255);
//		$pdf->Cell($w[2], $lh, '', 'LT', 0, 'C', 1);
//	}
//	$pdf->SetFillColor(255);
//	$pdf->Cell($w[3], $lh, ' ', 'T', 0, 'C', 1);
//	$pdf->Ln();
//	$pdf->SetFont('');
//	$pdf->SetFillColor(255);
//	$pdf->Cell($w[0], $lh, $invoice['invoice_number'], 1, 0, 'C', 1);
//	$pdf->Cell($w[1], $lh, $invoice['invoice_date'], 1, 0, 'C', 1);
//	if( isset($invoice['due_date']) && $invoice['due_date'] != '' ) {
//		$pdf->Cell($w[2], $lh, $invoice['due_date'], 1, 0, 'C', 1);
//	}
//	$pdf->Ln();
//	$pdf->Ln();

//	$lh = 5;

//	foreach($invoice_details as $label => $text) {
//		$pdf->SetFont('', 'B');
//		$pdf->Cell(40, $lh, $label . ':', 0, 0, 'R', 1);
//		$pdf->SetFont('');
//		$pdf->Cell(120, $lh, $text, 0, 0, 'L', 1);
//		$pdf->Ln();
//	}
//	$pdf->Ln();

//		elseif( $i == 1 ) {
//			$pdf->Cell($w[0]/2, $lh, 'Invoice Date:', 0, 0, 'L', 1);
//			$pdf->Cell($w[0]/2, $lh, $invoice['invoice_date'], 0, 0, 'L', 1);
//		}
//		elseif( $i == 2 && isset($invoice['due_date']) && $invoice['due_date'] != '' ) {
//			$pdf->Cell($w[0]/2, $lh, 'Due Date', 0, 0, 'L', 1);
//			$pdf->Cell($w[0]/2, $lh, $invoice['due_date'], 0, 0, 'L', 1);
//		} 
//		else {
//			$pdf->Cell($w[0], $lh, '', 0, 0, 'L', 1);
//		}


	//
	// Add the invoice bill to
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

	$saddr = array();
	if( 1==1 || ($invoice['flags']&0x03) > 1 ) {
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

	$w = array(90, 90);
	$pdf->setCellPaddings(2, 1, 2, 1);
	if( count($baddr) > 0 || count($saddr) > 0 ) {
		$pdf->SetFont('', 'B');
		$pdf->Cell($w[0], $lh, 'Bill To:', 0, 0, 'L', 1);
		$border = 0;
		if( 1 ==1 || ($invoice['flags']&0x03) > 1 ) {
			$pdf->Cell($w[1], $lh, 'Ship To:', 0, 0, 'L', 1);
			$border = 1;
		}
		$pdf->Ln();	
		$pdf->SetFont('');
		$pdf->MultiCell($w[0], $lh, implode("\n", $baddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
		if( 1 ==1 || ($invoice['flags']&0x03) > 1 ) {
			$pdf->MultiCell($w[1], $lh, implode("\n", $saddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
		}
		$pdf->Ln($lh);
	}
//	if( count($baddr) > 0 ) {
//		array_unshift($baddr, 'Bill to:');
//	}
//	if( count($saddr) > 0 ) {
//		array_unshift($saddr, 'Ship to:');
//	}
//	$pdf->SetFont('');
//	for($i=0;$i<5;$i++) {
//		if( $i == 0 ) { $pdf->SetFont('', 'B'); }

//		if( $i == 0 ) {
//			$pdf->Cell($w[0]/2, $lh, 'Invoice #', 0, 0, 'L', 1);
//			$pdf->Cell($w[0]/2, $lh, $invoice['invoice_number'], 0, 0, 'L', 1);
//		} 
//		elseif( $i == 1 ) {
//			$pdf->Cell($w[0]/2, $lh, 'Invoice Date:', 0, 0, 'L', 1);
//			$pdf->Cell($w[0]/2, $lh, $invoice['invoice_date'], 0, 0, 'L', 1);
//		}
//		elseif( $i == 2 && isset($invoice['due_date']) && $invoice['due_date'] != '' ) {
//			$pdf->Cell($w[0]/2, $lh, 'Due Date', 0, 0, 'L', 1);
//			$pdf->Cell($w[0]/2, $lh, $invoice['due_date'], 0, 0, 'L', 1);
//		} 
//		else {
//			$pdf->Cell($w[0], $lh, '', 0, 0, 'L', 1);
//		}
//		$pdf->Cell($w[0], $lh, ((isset($baddr[$i]))?$baddr[$i]:''), 0, 0, 'L', 1);
//		$pdf->Cell($w[1], $lh, ((isset($saddr[$i]))?$saddr[$i]:''), 0, 0, 'L', 1);
//		$pdf->Ln();
//		$pdf->SetFont('');
//	}
	$pdf->Ln();

	//
	// Add the invoice items
	//
	$w = array(100, 50, 30);
	$pdf->SetFillColor(232);
	$pdf->SetFont('', 'B');
	$pdf->Cell($w[0], 7, 'Item', 1, 0, 'C', 1);
	$pdf->Cell($w[1], 7, 'Quantity/Price', 1, 0, 'C', 1);
	$pdf->Cell($w[2], 7, 'Total', 1, 0, 'C', 1);
	$pdf->Ln();
	$pdf->SetFillColor(244, 244, 244);
	$pdf->SetTextColor(0);
	$pdf->SetFont('');
	$pdf->SetCellPadding(2);
	$fill=0;
	foreach($invoice['items'] as $item) {
		$discount = '';
		if( $item['item']['discount_amount'] != 0 ) {
			if( $item['item']['unit_discount_amount'] > 0 ) {
				$discount .= '-$' . number_format($item['item']['unit_discount_amount'], 2) . (($item['item']['quantity']>0&&$item['item']['quantity']!=1)?('x'.$item['item']['quantity']):'');
			}
			if( $item['item']['unit_discount_percentage'] > 0 ) {
				if( $discount != '' ) { 
					$discount .= ', '; 
				}
				$discount .= '-' . $item['item']['unit_discount_percentage'] . '%';
			}
			$discount .= ' (-$' . number_format($item['item']['discount_amount'], 2) . ')';
		}
		$lh = ($discount!='')?13:6;
		$pdf->Cell($w[0], $lh, $item['item']['description'], 1, 0, 'L', $fill, '', 0, false, 'T', 'T');
		$quantity = (($item['item']['quantity']>0&&$item['item']['quantity']!=1)?($item['item']['quantity'].' @ '):'');
		if( $discount == '' ) {
			$pdf->Cell($w[1], $lh, $quantity . '$' . $item['item']['unit_amount'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
		} else {
			$pdf->MultiCell($w[1], $lh, $quantity . '$' . number_format($item['item']['unit_amount'], 2) . (($discount!='')?"\n" . $discount:""), 1, 'R', $fill, 0, '', '', true, 0, false, true, 0, 'T', false);
		}
		$pdf->Cell($w[2], $lh, '$' . number_format($item['item']['total_amount'], 2), 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
		$pdf->Ln();	
		$fill=!$fill;
	}

//	$lh = 6;
//	$pdf->Cell($w[0], $lh, '', 1, 0, 'L', $fill, '', 0, false, 'T', 'T');
//	$pdf->Cell($w[1], $lh, '', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
//	$pdf->Cell($w[2], $lh, '', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
//	$pdf->Ln();
//	$fill=!$fill;

	//
	// Output the invoice tallies
	//
	$lh = 6;
	$pdf->Cell($w[0], $lh, '', 'L');
	$pdf->Cell($w[1], $lh, 'Subtotal', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
	$pdf->Cell($w[2], $lh, '$' . number_format($invoice['subtotal_amount'], 2), 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
	$pdf->Ln();
	$fill=!$fill;
	if( $invoice['discount_amount'] > 0 ) {
		$discount = '';
		if( $invoice['subtotal_discount_amount'] != 0 ) {
			$discount = '-$' . number_format($invoice['subtotal_discount_amount'], 2);
		}
		if( $invoice['subtotal_discount_percentage'] != 0 ) {
			$discount .= (($invoice['subtotal_discount_amount']!=0)?', ':'') . '-' . $invoice['subtotal_discount_percentage'] . '%';
		}
		$pdf->Cell($w[0], $lh, '', 'L');
		$pdf->Cell($w[1], $lh, 'Overall Discount (' . $discount . ')', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
		$pdf->Cell($w[2], $lh, '$' . number_format($invoice['subtotal_amount'], 2), 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
		$pdf->Ln();
		$fill=!$fill;
	}

	if( ($invoice['flags']&0x03) > 1 ) {
		$pdf->Cell($w[0], $lh, '', 'L');
		$pdf->Cell($w[1], $lh, 'Shipping & Handling', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
		$pdf->Cell($w[2], $lh, '$' . ((isset($invoice['shipping_amount'])&&$invoice['shipping_amount']>0)?number_format($invoice['shipping_amount'], 2):'0.00'), 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
		$pdf->Ln();
		$fill=!$fill;
	}

	//
	// Add taxes
	//
	if( isset($invoice['taxes']) && count($invoice['taxes']) > 0 ) {
		foreach($invoice['taxes'] as $tax) {
			$pdf->Cell($w[0], $lh, '', 'L');
			$pdf->Cell($w[1], $lh, $tax['tax']['description'], 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
			$pdf->Cell($w[2], $lh, '$' . number_format($tax['tax']['amount'], 2), 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
			$pdf->Ln();
			$fill=!$fill;
			
		}
	}

	$pdf->SetFont('', 'B');
	$pdf->Cell($w[0], $lh, '', 'LB');
	$pdf->Cell($w[1], $lh, 'Total:', 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
	$pdf->Cell($w[2], $lh, '$' . number_format($invoice['total_amount'], 2), 1, 0, 'R', $fill, '', 0, false, 'T', 'T');
	$pdf->Ln();
	$fill=!$fill;


	// ---------------------------------------------------------

	//Close and output PDF document
	$pdf->Output('invoice_' . $invoice['invoice_number'] . '.pdf', 'D');

	return array('stat'=>'ok', 'invoice'=>$rc['invoice']);
}
?>
