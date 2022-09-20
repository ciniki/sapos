<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_sapos_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Invoices')),
        array('flag'=>array('bit'=>'2', 'name'=>'Expenses')),
        array('flag'=>array('bit'=>'3', 'name'=>'Quick Invoices')),
        array('flag'=>array('bit'=>'4', 'name'=>'Shopping Cart')),
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'POS')),
        array('flag'=>array('bit'=>'6', 'name'=>'Purchase Orders')),
        array('flag'=>array('bit'=>'7', 'name'=>'Shipping')),       // Enables shipping profiles
        array('flag'=>array('bit'=>'8', 'name'=>'Manufacturing')),
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Mileage')),
        array('flag'=>array('bit'=>'10', 'name'=>'Payments')),  // This use to be Paypal API (deprecated)
        array('flag'=>array('bit'=>'11', 'name'=>'Item Codes')), // Should be enabled along with product codes in ciniki.products
        array('flag'=>array('bit'=>'12', 'name'=>'Quantity Totals')),
        // 0x1000
        array('flag'=>array('bit'=>'13', 'name'=>'Recurring Invoices/Expenses')), // Must also have Invoices enabled
//        array('flag'=>array('bit'=>'14', 'name'=>'Recurring Expenses')), // Must also have Expenses enabled
//        array('flag'=>array('bit'=>'15', 'name'=>'Recurring Purchase Orders')), // Must also have Purchase Orders enabled
//        array('flag'=>array('bit'=>'16', 'name'=>'')),
        // 0x010000
        array('flag'=>array('bit'=>'17', 'name'=>'Quotes')), 
        array('flag'=>array('bit'=>'18', 'name'=>'Work Location')),     // Address where the work was done
        array('flag'=>array('bit'=>'19', 'name'=>'Drop Ships')), 
        array('flag'=>array('bit'=>'20', 'name'=>'Payment Deposited')),
        // 0x100000
        array('flag'=>array('bit'=>'21', 'name'=>'Paypal Payments')), 
        array('flag'=>array('bit'=>'22', 'name'=>'Paypal Express Checkout')),
        array('flag'=>array('bit'=>'23', 'name'=>'Pre-Orders')), 
        array('flag'=>array('bit'=>'24', 'name'=>'Stripe Checkout')),
        // 0x01000000
        array('flag'=>array('bit'=>'25', 'name'=>'Item Categories')), 
        array('flag'=>array('bit'=>'26', 'name'=>'Donations')),
        array('flag'=>array('bit'=>'27', 'name'=>'Donation Portions')), 
        array('flag'=>array('bit'=>'28', 'name'=>'Donation Categories')),
        // 0x10000000
        array('flag'=>array('bit'=>'29', 'name'=>'Simple Shipping')), // Do not use with Shipping 0x40 flag
        array('flag'=>array('bit'=>'30', 'name'=>'Instore Pickup')),
        array('flag'=>array('bit'=>'31', 'name'=>'E-Transfer Checkout')), 
//        array('flag'=>array('bit'=>'32', 'name'=>'')), // Do NOT USE

        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
