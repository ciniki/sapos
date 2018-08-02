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
function ciniki_sapos_maps($ciniki) {

    $maps = array();
    $maps['invoice'] = array(
        'invoice_type'=>array(
            '10'=>'Invoice',
            '11'=>'Monthly Invoice',
            '16'=>'Quarterly Invoice',
            '19'=>'Yearly Invoice',
            '20'=>'Cart',
            '30'=>'POS',
            '40'=>'Order',
            '90'=>'Quote',
            ),
        'status'=>array(
            '10'=>'Entered',
            '15'=>'On Hold',
            '20'=>'Pending Manufacturing',
            '30'=>'Pending Shipping',
            '40'=>'Payment Required',
            '50'=>'Fulfilled',
            '55'=>'Refund Required',
            '65'=>'Refunded',
            ),
        'typestatus'=>array(
            '10.10'=>'Payment Required',
            '10.20'=>'Processing',
            '10.30'=>'Processing',
            '10.40'=>'Payment Required',
            '10.50'=>'Paid',
            '10.55'=>'Refund Required',
            '10.60'=>'Refunded',
            '10.65'=>'Void',
            '11.10'=>'Payment Required',
            '11.20'=>'Processing',
            '11.30'=>'Processing',
            '11.40'=>'Payment Required',
            '11.50'=>'Paid',
            '11.55'=>'Refund Required',
            '11.60'=>'Refunded',
            '11.65'=>'Void',
            '16.10'=>'Payment Required',
            '16.20'=>'Processing',
            '16.30'=>'Processing',
            '16.40'=>'Payment Required',
            '16.50'=>'Paid',
            '16.55'=>'Refund Required',
            '16.60'=>'Refunded',
            '16.65'=>'Void',
            '19.10'=>'Payment Required',
            '19.20'=>'Processing',
            '19.30'=>'Processing',
            '19.40'=>'Payment Required',
            '19.50'=>'Paid',
            '19.55'=>'Refund Required',
            '19.60'=>'Refunded',
            '19.65'=>'Void',
            '20.10'=>'Incomplete Cart',
            '20.15'=>'On Hold',
            '20.20'=>'Pending Manufacturing',
            '20.30'=>'Pending Shipping',
            '20.40'=>'Payment Required',
            '20.50'=>'Fulfilled',
            '20.55'=>'Refund Required',
            '20.60'=>'Refunded',
            '20.65'=>'Void',
            '30.10'=>'Entered',
            '30.15'=>'On Hold',
            '30.20'=>'Pending Manufacturing',
            '30.30'=>'Pending Shipping',
            '30.40'=>'Payment Required',
            '30.50'=>'Paid',
            '30.55'=>'Refund Required',
            '30.60'=>'Refunded',
            '30.65'=>'Void',
            '40.10'=>'Incomplete Order',
            '40.15'=>'On Hold',
            '40.20'=>'Pending Manufacturing',
            '40.30'=>'Pending Shipping',
            '40.40'=>'Payment Required',
            '40.50'=>'Fulfilled',
            '40.55'=>'Refund Required',
            '40.60'=>'Refunded',
            '40.65'=>'Void',
            '90.10'=>'Entered',
            ),
        'payment_status'=>array(
            '10'=>'Payment Required',
            '40'=>'Deposit',
            '50'=>'Paid',
            '55'=>'Refund Required',
            '60'=>'Refunded',
            ),
        'shipping_status'=>array(
            '0'=>'',        // No shipping
            '10'=>'Shipping Required',
            '30'=>'Partial Shipment',
            '50'=>'Shipped',
            ),
        'manufacturing_status'=>array(
            '0'=>'',        // No shipping
            '10'=>'Manufacturing Required',
            '30'=>'Manufacturing In Progress',
            '50'=>'Manufactured',
            ),
        'donationreceipt_status'=>array(
            '0'=>'Not Applicable',
            '20'=>'Pending',
            '40'=>'Printed',
            '60'=>'Mailed',
            '80'=>'Received',
            ),
        );
    $maps['transaction'] = array(
        'status'=>array(
            '40'=>'Completed',
            '60'=>'Deposited',
            ),
        'transaction_type'=>array(
            '10'=>'Deposit',
            '20'=>'Payment',
            '60'=>'Refund',
            ),
        'source'=>array(
            '10'=>'Paypal',
            '20'=>'Square',
            '30'=>'Stripe',
            '50'=>'Visa',
            '55'=>'Mastercard',
            '60'=>'Discover',
            '65'=>'Amex',
            '90'=>'Interac',
            '100'=>'Cash',
            '105'=>'Check',
            '110'=>'Email Transfer',
            '120'=>'Other',
            ),
        );
    $maps['shipment'] = array(
        'status'=>array(
            '10'=>'Packing',
            '20'=>'Packed',
            '30'=>'Shipped',
            '40'=>'Received',
            ),
        'weight_units'=>array(
            '10'=>array('field'=>'weight', 's'=>'lb', 'p'=>'lbs'),
            '20'=>array('field'=>'weight', 's'=>'kg', 'p'=>'kgs'),
            ),
        );
    $maps['rule'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Inactive',
            '60'=>'Deleted',
            ),
        );
    
    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
