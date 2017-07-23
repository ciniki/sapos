<?php
//
// Description
// -----------
// This function will generate the donations page for the business.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure, similar to ciniki variable but only web specific information.
//
// Returns
// -------
//
function ciniki_sapos_web_processRequestDonations(&$ciniki, $settings, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.sapos']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.sapos.209', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        'submenu'=>array(),
        );

    $ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');

    //
    // Store the content created by the page
    // Make sure everything gets generated ok before returning the content
    //
    $content = '';
    $page_content = '';
    $page_title = 'Donations';
    if( $page['title'] == '' ) {
        $page['title'] = 'Donations';
    }
    if( count($page['breadcrumbs']) == 0 ) {
        $page['breadcrumbs'][] = array('name'=>$page['title'], 'url'=>$args['base_url']);
    }

    //
    // Get the list of packages
    //
    $strsql = "SELECT id, name, subname, sequence, flags, amount, primary_image_id, synopsis "
        . "FROM ciniki_sapos_donation_packages "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (flags&0x01) = 0x01 "
        . "ORDER BY sequence "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'package');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $packages = $rc['rows'];
    }

    //
    // Check for a intro
    //
    if( isset($settings['sapos-donations-intro']) && $settings['sapos-donations-intro'] != '' ) {
        $page['blocks'][] = array('type'=>'content', 'wide'=>'yes', 'content'=>$settings['sapos-donations-intro']);
    }

    //
    // Add the packages
    //
    if( isset($packages) ) {    
        $content = '';
        $cards = array();
        foreach($packages as $package) {
            $package['object'] = 'ciniki.sapos.donationpackage';
            $package['object_id'] = $package['id'];
            if( ($package['flags']&0x02) == 0 ) {
                $package['amount'] = 0;
            }
            $cards[] = $package;
        }
        $page['blocks'][] = array('type'=>'pricecards', 'cards'=>$cards);
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
