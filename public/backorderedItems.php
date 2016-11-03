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
function ciniki_sapos_backorderedItems(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output Format'), 
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
    $rc = ciniki_sapos_checkAccess($ciniki, $args['business_id'], 'ciniki.sapos.backorderedItems'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
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

    $strsql = "SELECT "
        . "CONCAT_WS('-', code, object, object_id) AS rowid, "
        . "ciniki_sapos_invoice_items.code, "
        . "ciniki_sapos_invoice_items.description, "
        . "ciniki_sapos_invoice_items.object, "
        . "ciniki_sapos_invoice_items.object_id, "
        . "ciniki_sapos_invoice_items.flags, "
        . "SUM(ciniki_sapos_invoice_items.quantity - ciniki_sapos_invoice_items.shipped_quantity) AS reserved_quantity "
        . "FROM ciniki_sapos_invoice_items, ciniki_sapos_invoices "
        . "WHERE ciniki_sapos_invoice_items.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND (ciniki_sapos_invoice_items.flags&0x40) = 0x40 "    // Shipped item
//        . "AND (ciniki_sapos_invoice_items.flags&0x0300) = 0x0300 " // backordered item
        . "AND (ciniki_sapos_invoice_items.quantity - ciniki_sapos_invoice_items.shipped_quantity) > 0 "
        . "AND ciniki_sapos_invoice_items.invoice_id = ciniki_sapos_invoices.id "
        . "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_sapos_invoices.status < 50 "
        . "GROUP BY code, object, object_id "
        . "ORDER BY ciniki_sapos_invoice_items.code, "
            . "ciniki_sapos_invoice_items.object, ciniki_sapos_invoice_items.object_id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.sapos', array(
        array('container'=>'items', 'fname'=>'rowid', 'name'=>'item',
            'fields'=>array('code', 'description', 'object', 'object_id', 'reserved_quantity')),
            ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['items']) ) {
        return array('stat'=>'ok', 'items'=>array());
    }
    $items = $rc['items'];

    $objects = array();
    foreach($items as $iid => $item) {
        if( !isset($objects[$item['item']['object']]) ) {
            $objects[$item['item']['object']] = array();
        }
        $objects[$item['item']['object']][] = $item['item']['object_id'];
    }

    foreach($objects as $object => $object_ids) {
        list($pkg,$mod,$obj) = explode('.', $object);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'inventoryLevels');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['business_id'], array(
                'object'=>$object,
                'object_ids'=>$object_ids,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.46', 'msg'=>'Unable to get inventory levels.', 'err'=>$rc['err']));
            }
            //
            // Update the inventory levels for the invoice items
            //
            $objects[$object]['quantities'] = $rc['quantities'];
        }
    }

    $rsp = array('stat'=>'ok', 'items'=>array());
    foreach($items as $iid => $item) {
        $item['item']['reserved_quantity'] = (float)$item['item']['reserved_quantity'];
        if( isset($objects[$item['item']['object']]['quantities'][$item['item']['object_id']]['inventory_quantity']) ) {
            $item['item']['inventory_current_num'] = (float)$objects[$item['item']['object']]['quantities'][$item['item']['object_id']]['inventory_quantity'];
            $item['item']['backordered_quantity'] = $item['item']['reserved_quantity'] - $item['item']['inventory_current_num'];
            if( $item['item']['backordered_quantity'] > 0 ) {
                $rsp['items'][] = $item;
            }
        } elseif( ($item['item']['flags']&0x0300) > 0 ) {
            $item['item']['inventory_current_num'] = '';
            $item['item']['backordered_quantity'] = '';
            $rsp['items'][] = $item;
        }
    }

    //
    // Check if output should be excel
    //
    if( isset($args['output']) && $args['output'] == 'excel' ) {
        ini_set('memory_limit', '4192M');
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $title = "Backordered Items";
        $sheet_title = "Backordered Items";     // Will be overwritten, which is fine
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle($sheet_title);

        //
        // Headers
        //
        $i = 0;
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Item', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Inventory', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Ordered', false);
        $sheet->setCellValueByColumnAndRow($i++, 1, 'Backordered', false);
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        //
        // Output the invoice list
        //
        $row = 2;
        foreach($rsp['items'] as $iid => $item) {
            $item = $item['item'];
            $i = 0;
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['code'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['inventory_current_num'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['reserved_quantity'], false);
            $sheet->setCellValueByColumnAndRow($i++, $row, $item['backordered_quantity'], false);
            $row++;
        }
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);

        //
        // Output the excel
        //
        header('Content-Type: application/vnd.ms-excel');
        $filename = preg_replace('/[^a-zA-Z0-9\-]/', '', $title);
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

        return array('stat'=>'exit');
    }

    $rsp['stat'] = 'ok';
    return $rsp;
}
?>
