<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_sapos_reporting_blocks(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.226', 'msg'=>"I'm sorry, the block you requested does not exist."));
    }

    $blocks = array();

    //
    // Get the list of categories
    //
    $strsql = "SELECT DISTINCT IF(items.category='','Uncategorized', items.category) AS category "
        . "FROM ciniki_sapos_invoice_items AS items "
        . "WHERE items.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('id'=>'category', 'name'=>'category')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.410', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array();
    array_unshift($categories, array('id'=>0, 'name'=>'All Categories'));

    //
    // Return the list of blocks for the tenant
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x02000000) ) {
        $blocks['ciniki.sapos.categorysales'] = array(
            'name'=>'Sales by Category',
            'module' => 'Accounting',
            'options'=>array(
                'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
                'months'=>array('label'=>'Number of Months Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
                'category'=>array('label'=>'Category', 'type'=>'select', 'default'=>'0',
                    'complex_options'=>array('value'=>'id', 'name'=>'name'),
                    'options'=>$categories
                    ),
                ),
            );
        $blocks['ciniki.sapos.categorizedsales'] = array(
            'name'=>'Categorized Sales',
            'module' => 'Accounting',
            'options'=>array(
                'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
                ),
            );
    }

    //
    // Basic sales report
    //
    $blocks['ciniki.sapos.sales'] = array(
        'name'=>'All Sales',
        'module' => 'Accounting',
        'options'=>array(
            'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
            ),
        );

    //
    // Daily sales report
    //
    $blocks['ciniki.sapos.dailysales'] = array(
        'name'=>'Daily Sales',
        'module' => 'Accounting',
        'dates' => 'yes',
        'options'=>array(
            'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
            'pdf-hide-nosales'=>array('label'=>'Hide No Sales Days in PDF', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no'=>'No',
                'yes'=>'Yes',
                )),
            ),
        );

    //
    // Daily sales report
    //
    $blocks['ciniki.sapos.dailydeposits'] = array(
        'name'=>'Daily Deposits',
        'module' => 'Accounting',
        'dates' => 'yes',
        'options'=>array(
            'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
            'pdf-hide-nosales'=>array('label'=>'Hide No Sales Days in PDF', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no'=>'No',
                'yes'=>'Yes',
                )),
            ),
        );

    //
    // Ontario HST Report, designed to run quarterly
    //
    if( ciniki_core_checkModuleActive($ciniki, 'ciniki.taxes') ) {
        $blocks['ciniki.sapos.ontarioquarterlyhst'] = array(
            'name'=>'Ontario Quarterly HST',
            'module' => 'Accounting',
            'options'=>array(
                'quarters'=>array('label'=>'Number of Previous Quarters', 'type'=>'text', 'size'=>'small', 'default'=>'1'),
                'current'=>array('label'=>'Include Current Quarter', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                    'no'=>'No',
                    'yes'=>'Yes',
                    )),
                ),
            );
    }

    //
    // Category Payment Types Summary 
    //
    $blocks['ciniki.sapos.categorypaymenttypessummary'] = array(
        'name'=>'Category Payment Types Summary',
        'module' => 'Accounting',
        'dates' => 'yes',
        'options'=>array(
            'use-date'=>array('label'=>'Based On', 'type'=>'toggle', 'default'=>'invoice', 'toggles'=>array(
                'invoice'=>'Invoice Date',
                'transaction'=>'Transaction Date',
                )),
//            'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
            ),
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
