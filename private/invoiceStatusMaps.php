<?php
//
// Description
// -----------
// This function returns the array of status text for ciniki_sapos_invoices.status.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_invoiceStatusMaps($ciniki) {
	
	$status_maps = array(
		'10'=>'Shopping Cart',
		'20'=>'',	// Created - no status
		'30'=>'Entered',
		'40'=>'Deposit',
		'50'=>'Paid',
		'55'=>'Refunded',
		'60'=>'Void',
		);
	
	return array('stat'=>'ok', 'maps'=>$status_maps);
}
?>
