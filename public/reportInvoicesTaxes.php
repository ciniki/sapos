<?php
//
// Description
// ===========
// This method will return a list of invoices.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_sapos_reportInvoicesTaxes(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['tnid'], 'ciniki.sapos.reportInvoicesTaxes'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

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
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
    $rc = ciniki_sapos_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // FIXME: Get year end from sapos settings
    //
    $year_end_month = 12;
    $year_end_day = 31;
    $qinterval = new DateInterval('P3M');
    $onesecond = new DateInterval('PT1S');

    $ltz = new DateTimeZone($intl_timezone);
    $utc = new DateTimeZone('UTC');
    $cur_date = new DateTime('now', $ltz);
    $last_quarter_start_date = new DateTime($cur_date->format("Y-$year_end_month-$year_end_day 23:59:59"), $ltz);
    $last_quarter_start_date->add(new DateInterval('PT1S'));
    $last_quarter_start_date->sub($qinterval);

    //
    // Find the current last quarter
    //
    while( $last_quarter_start_date > $cur_date ) {
        $last_quarter_start_date->sub($qinterval);
    }

    //
    // Get the list of all tax rates for the tenant
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_tax_rates "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
        array('container'=>'taxrates', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['taxrates']) ) {
        $taxrates = $rc['taxrates'];
    } else {
        $taxrates = array();
    }

    //
    // Get the list of all taxes for invoices
    //
    $strsql = "SELECT ciniki_sapos_invoices.invoice_date, "
        . "ciniki_sapos_invoice_taxes.id, "
        . "ciniki_sapos_invoice_taxes.taxrate_id, "
        . "ciniki_sapos_invoice_taxes.amount "
        . "FROM ciniki_sapos_invoices "
        . "LEFT JOIN ciniki_sapos_invoice_taxes ON ("
            . "ciniki_sapos_invoices.id = ciniki_sapos_invoice_taxes.invoice_id "
            . "AND ciniki_sapos_invoice_taxes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_sapos_invoices.invoice_type IN (10, 30, 40) " //Invoices, POS, Orders
        . "ORDER BY ciniki_sapos_invoices.invoice_date "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'taxes', 'fname'=>'id', 
            'fields'=>array('invoice_date', 'taxrate_id', 'amount'),
            'utctodate'=>array('invoice_date'=>$intl_timezone),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['taxes']) ) {
        $taxes = $rc['taxes'];
    } else {
        $taxes = array();
    }

    //
    // Get the list of expense categories
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_sapos_expense_categories "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.taxes', array(
        array('container'=>'categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['categories']) ) {
        $expensecategories = $rc['categories'];
    } else {
        $expensecategories = array();
    }

    //
    // Get the tax expenses
    //
    $strsql = "SELECT e.invoice_date, "
        . "i.id, "
        . "i.category_id, "
        . "i.amount "
        . "FROM ciniki_sapos_expense_categories AS c "
        . "LEFT JOIN ciniki_sapos_expense_items AS i ON ("
            . "c.id = i.category_id "
            . "AND i.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_expenses AS e ON ("
            . "i.expense_id = e.id "
            . "AND e.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE c.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (c.flags&0x01) = 0x01 "
        . "ORDER BY e.invoice_date ";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'expenses', 'fname'=>'id', 
            'fields'=>array('invoice_date', 'category_id', 'amount'),
            'utctodate'=>array('invoice_date'=>$intl_timezone),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['expenses']) ) {
        $expenses = $rc['expenses'];
    } else {
        $expenses = array();
    }

    //
    // Process the taxes and expenses into quarters
    //
    $quarters = array();
    if( count($taxes) > 0 || count($expenses) > 0 ) {
       
        if( isset($taxes[0]['invoice_date']) ) {
            if( isset($expenses[0]['invoice_date']) && $expenses[0]['invoice_date'] < $taxes[0]['invoice_date'] ) {
                $start_date = clone $expenses[0]['invoice_date'];
            } else {
                $start_date = clone $taxes[0]['invoice_date'];
            }
        } else {
            $start_date = clone $expenses[0]['invoice_date'];
        }
        
        $qstart = clone $last_quarter_start_date;
        $qstart->add($qinterval);
        do {
            $qstart->sub($qinterval);
            $quarter = array(
                'start_date'=>clone $qstart, 
                'end_date'=>clone $qstart,
                'total_amount'=>0,
                'taxrates'=>array(),
                'expenses'=>array(),
                );
            $quarter['end_date']->add($qinterval);
            $quarter['end_date']->sub($onesecond);
            foreach($taxrates as $rate) {
                $quarter['taxrates'][$rate['id']] = array('amount'=>0);
            }
            foreach($expensecategories as $c) {
                $quarter['expenses'][$c['id']] = array('amount'=>0);
            }
            // Push to top of array so array is in date order
            array_unshift($quarters, $quarter);

        } while($qstart > $start_date);

        //
        // Process that taxes into quarters
        //
        $current_quarter = 0;
        foreach($taxes as $tid => $tax) {
            if( $tax['invoice_date'] > $cur_date ) {
                break;
            }
            while( $tax['invoice_date'] > $quarters[$current_quarter]['end_date'] ) {
                $current_quarter++;
                if( !isset($quarters[$current_quarter]) ) {
                    break;
                }
            }
            if( !isset($quarters[$current_quarter]) ) {
                break;
            }
            $quarters[$current_quarter]['taxrates'][$tax['taxrate_id']]['amount'] = bcadd($quarters[$current_quarter]['taxrates'][$tax['taxrate_id']]['amount'], $tax['amount'], 4);
            $quarters[$current_quarter]['total_amount'] = bcadd($quarters[$current_quarter]['total_amount'], $tax['amount'], 4);
        }

        //
        // Process the expenses
        //
        $current_quarter = 0;
        foreach($expenses as $eid => $expense) {
            if( $expense['invoice_date'] > $cur_date ) {
                break;
            }
            while( $expense['invoice_date'] > $quarters[$current_quarter]['end_date'] ) {
                $current_quarter++;
                if( !isset($quarters[$current_quarter]) ) {
                    break;
                }
            }
            if( !isset($quarters[$current_quarter]) ) {
                break;
            }
            $quarters[$current_quarter]['expenses'][$expense['category_id']]['amount'] = bcadd($quarters[$current_quarter]['expenses'][$expense['category_id']]['amount'], $expense['amount'], 4);
            $quarters[$current_quarter]['total_amount'] = bcsub($quarters[$current_quarter]['total_amount'], $expense['amount'], 4);
        }
       
        //
        // Convert dates into strings
        //
        foreach($quarters as $qid => $quarter) {
            $quarters[$qid]['start_datetime'] = $quarter['start_date']->format('M j, Y H:i:s');
            $quarters[$qid]['end_datetime'] = $quarter['end_date']->format('M j, Y H:i:s');
            $quarters[$qid]['start_date'] = $quarter['start_date']->format('M j, Y');
            $quarters[$qid]['end_date'] = $quarter['end_date']->format('M j, Y');
            foreach($quarter['taxrates'] as $tid => $rate) {
                $quarters[$qid]['taxrates'][$tid]['amount_display'] = numfmt_format_currency($intl_currency_fmt, $rate['amount'], $intl_currency);
            }
            foreach($quarter['expenses'] as $eid => $expense) {
                $quarters[$qid]['expenses'][$eid]['amount_display'] = numfmt_format_currency($intl_currency_fmt, $expense['amount'], $intl_currency);
            }
            $quarters[$qid]['total_amount_display'] = numfmt_format_currency($intl_currency_fmt, $quarter['total_amount'], $intl_currency);
        }
    }

    //
    // Convert taxrates into array
    //
    $r_taxrates = array();
    foreach($taxrates as $rate) {
        $r_taxrates[] = $rate;
    }

    //
    // Convert expensecategories into array
    //
    $r_expensecategories = array();
    foreach($expensecategories as $e) {
        $r_expensecategories[] = $e;
    }

    return array('stat'=>'ok', 'taxrates'=>$r_taxrates, 'expensecategories'=>$r_expensecategories, 'quarters'=>$quarters);
}
?>
