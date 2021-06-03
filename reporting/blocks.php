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
    // Return the list of blocks for the tenant
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.sapos', 0x02000000) ) {
        $blocks['ciniki.sapos.categorysales'] = array(
            'name'=>'Sales by Category',
            'module' => 'Accounting',
            'options'=>array(
                'days'=>array('label'=>'Number of Days Previous', 'type'=>'text', 'size'=>'small', 'default'=>'7'),
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
    // Ontario HST Report, designed to run quarterly
    //
    if( ciniki_core_checkModuleActive($ciniki, 'ciniki.sapos') ) {
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

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
