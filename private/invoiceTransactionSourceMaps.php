<?php
//
// Description
// -----------
// This function returns the array of source text for ciniki_sapos_invoice_transactions.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_invoiceTransactionSourceMaps($ciniki) {
	$maps = array(
		'10'=>'Paypal',
		'20'=>'Visa',
		'30'=>'Mastercard',
		'90'=>'Interac',
		'100'=>'Cash',
		'105'=>'Check',
		'110'=>'Email Transfer',
		'120'=>'Other',
		);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>
