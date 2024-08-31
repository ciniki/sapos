<?php
//
// Description
// ===========
// This method will return all the information about an invoice costing.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the invoice costing is attached to.
// costing_id:          The ID of the invoice costing to get the details for.
//
// Returns
// -------
//
function ciniki_sapos_invoiceCostingGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'costing_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Costing'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'checkAccess');
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.invoiceCostingGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Invoice Costing
    //
    if( $args['costing_id'] == 0 ) {
        $costing = array('id'=>0,
            'invoice_id'=>'',
            'line_number'=>'1',
            'description'=>'',
            'quantity'=>'1',
            'cost'=>'',
            'price'=>'',
        );
    }

    //
    // Get the details for an existing Invoice Costing
    //
    else {
        $strsql = "SELECT ciniki_sapos_invoice_costing.id, "
            . "ciniki_sapos_invoice_costing.invoice_id, "
            . "ciniki_sapos_invoice_costing.line_number, "
            . "ciniki_sapos_invoice_costing.description, "
            . "ciniki_sapos_invoice_costing.quantity, "
            . "ciniki_sapos_invoice_costing.cost, "
            . "ciniki_sapos_invoice_costing.price "
            . "FROM ciniki_sapos_invoice_costing "
            . "WHERE ciniki_sapos_invoice_costing.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_sapos_invoice_costing.id = '" . ciniki_core_dbQuote($ciniki, $args['costing_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
            array('container'=>'costings', 'fname'=>'id', 
                'fields'=>array('invoice_id', 'line_number', 'description', 'quantity', 'cost', 'price'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.312', 'msg'=>'Invoice Costing not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['costings'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.315', 'msg'=>'Unable to find Invoice Costing'));
        }
        $costing = $rc['costings'][0];
        $costing['quantity'] = (float)$costing['quantity'];
        $costing['cost'] = (float)$costing['cost'];
        $costing['price'] = (float)$costing['price'];
    }

    return array('stat'=>'ok', 'costing'=>$costing);
}
?>
