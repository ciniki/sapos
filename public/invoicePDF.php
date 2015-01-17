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
//
function ciniki_sapos_invoicePDF(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'invoice_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice'), 
		'type'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'invoice', 'name'=>'Output Type'),
		'subject'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Subject'),
		'textmsg'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Text Message'),
		'email'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Email PDF'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.invoicePDF'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Load business details
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
	$rc = ciniki_businesses_businessDetails($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['details']) && is_array($rc['details']) ) {	
		$business_details = $rc['details'];
	} else {
		$business_details = array();
	}

	//
	// Load the invoice settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_sapos_settings', 'business_id', $args['business_id'],
		'ciniki.sapos', 'settings', 'invoice');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['settings']) ) {
		$sapos_settings = $rc['settings'];
	} else {
		$sapos_settings = array();
	}
	
	//
	// check for invoice-default-template
	//
	if( isset($args['type']) && $args['type'] == 'picklist' ) {
		$invoice_template = 'picklist';
	} else {
		if( !isset($sapos_settings['invoice-default-template']) 
			|| $sapos_settings['invoice-default-template'] == '' ) {
			$invoice_template = 'default';
		} else {
			$invoice_template = $sapos_settings['invoice-default-template'];
		}
	}
	
	$rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'templates', $invoice_template);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$fn = $rc['function_call'];

	if( isset($args['email']) && $args['email'] == 'yes' ) {
		$rc = $fn($ciniki, $args['business_id'], $args['invoice_id'],
			$business_details, $sapos_settings, 'email');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		//
		// Email the pdf to the customer
		//
		$filename = $rc['filename'];
		$invoice = $rc['invoice'];
		$pdf = $rc['pdf'];

		//
		// if customer is set
		//
		if( !isset($invoice['customer']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2154', 'msg'=>'No customer attached to the invoice, we are unable to send the email.'));
		}
		if( !isset($invoice['customer']['emails'][0]['email']['address']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2155', 'msg'=>'No customer attached to the invoice, we are unable to send the email.'));
		}

		if( isset($args['subject']) && isset($args['textmsg']) ) {
			$subject = $args['subject'];
			$textmsg = $args['textmsg'];
		} else {
			$subject = 'Invoice #' . $invoice['invoice_number'];
			if( isset($sapos_settings['invoice-email-message']) && $sapos_settings['invoice-email-message'] != '' ) {
				$textmsg = $sapos_settings['invoice-email-message'];
			} else {
				$textmsg = 'Please find your invoice attached.';
			}
			if( $invoice['invoice_type'] == '20' ) {
				$subject = 'Order #' . $invoice['invoice_number'];
				$textmsg = 'Your order receipt is attached.';
				if( isset($sapos_settings['cart-email-message']) && $sapos_settings['cart-email-message'] != '' ) {
					$textmsg = $sapos_settings['cart-email-message'];
				}
			} elseif( $invoice['invoice_type'] == '30' ) {
				$subject = 'Receipt #' . $invoice['invoice_number'];
				$textmsg = 'Your receipt is attached.';
				if( isset($sapos_settings['pos-email-message']) && $sapos_settings['pos-email-message'] != '' ) {
					$textmsg = $sapos_settings['pos-email-message'];
				}
			} elseif( $invoice['invoice_type'] == '40' ) {
				$subject = 'Order #' . $invoice['invoice_number'];
				$textmsg = 'Thank you for your order. Your order details are attached.';
				if( isset($sapos_settings['order-email-message']) && $sapos_settings['order-email-message'] != '' ) {
					$textmsg = $sapos_settings['order-email-message'];
				}
			} elseif( $invoice['invoice_type'] == '90' ) {
				$subject = 'Quote #' . $invoice['invoice_number'];
				$textmsg = 'Here is the quote you requested.';
				if( isset($sapos_settings['quote-email-message']) && $sapos_settings['quote-email-message'] != '' ) {
					$textmsg = $sapos_settings['quote-email-message'];
				}
			}
		}

		$ciniki['emailqueue'][] = array('to'=>$invoice['customer']['emails'][0]['email']['address'],
			'to_name'=>(isset($invoice['customer']['display_name'])?$invoice['customer']['display_name']:''),
			'business_id'=>$args['business_id'],
			'subject'=>$subject,
			'textmsg'=>$textmsg,
			'attachments'=>array(array('string'=>$pdf->Output('invoice', 'S'), 'filename'=>$filename)),
			);
		return array('stat'=>'ok');
	}

	return $fn($ciniki, $args['business_id'], $args['invoice_id'],
		$business_details, $sapos_settings);
}
?>
