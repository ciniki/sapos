<?php
//
// Description
// -----------
// Check and invoice item form has been filled out and validated
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_sapos_wng_cartItemFormCheck(&$ciniki, $tnid, &$request, $item) {

    if( !isset($item['form_id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.407', 'msg'=>'No form specified'));
    }

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $item['form_id'], $item['student_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.406', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }
    $form = isset($rc['form']) ? $rc['form'] : array();

    //
    // Load the submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
    $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.406', 'msg'=>'Unable to load form', 'err'=>$rc['err']));
    }

    //
    // Apply defaults if no submission
    //
    if( $form['submission_id'] == 0 || $form['submission_id'] == 'new' ) {
        //
        // Create new submission
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.submission', array(
            'form_id' => $form['id'],
            'object' => $form['object'],
            'object_id' => $form['object_id'],
            'customer_id' => $form['customer_id'],
            'invoice_id' => 0,
            'status' => 10,
            'label' => 'New Submission',
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.forms.71', 'msg'=>'Unable to add the submission', 'err'=>$rc['err']));
        }
        $form['submission_id'] = $rc['id'];
        $form['submission_uuid'] = $rc['uuid'];

        //
        // Apply the defaults
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formDefaultsApply');
        $rc = ciniki_forms_wng_formDefaultsApply($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, invalid submission."
                )));
        }

        //
        // Reload the submission to get the default values
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
        $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, invalid submission."
                )));
        }
    }

    //
    // Check if POST to update form
    //
    if( isset($_POST['action']) && $_POST['action'] == 'submit' 
        && isset($_POST['f-student_id']) && $_POST['f-student_id'] == $item['student_id']
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formPOSTApply');
        $rc = ciniki_forms_wng_formPOSTApply($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.408', 'msg'=>'Error updating form.', 'err'=>$rc['err']));
        }

        //
        // Save the submission
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionSave');
        $rc = ciniki_forms_wng_submissionSave($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.sapos.409', 'msg'=>'Unable to save submission', 'err'=>$rc['err']));
        }

        //
        // Validate the submission
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionValidate');
        $rc = ciniki_forms_submissionValidate($ciniki, $tnid, $form);
        if( $rc['stat'] == 'fail' && isset($rc['problems']) ) {
            $problem_list = "You must complete all the required field in the form. The following fields are missing:\n\n";
            foreach($rc['problems'] as $pid => $problem) {
                $problem_list .= $problem . "\n";
            }
            //
            // Form invalidated, Update status
            //
            $dt_now = new DateTime('now', new DateTimezone('UTC'));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $form['submission']['id'], array(
                'status' => 10,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'ok', 'blocks'=>array(array(
                    'type' => 'msg', 
                    'level' => 'error',
                    'content' => "Error, unable to submit form."
                    )));
            }
        }
        elseif( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, unable to save form."
                )));
        }
        else {
            //
            // Form validated, update submitted dt
            //
            $dt_now = new DateTime('now', new DateTimezone('UTC'));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $form['submission']['id'], array(
                'status' => 90,
                'dt_last_submitted' => $dt_now->format('Y-m-d H:i:s'),
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'ok', 'blocks'=>array(array(
                    'type' => 'msg', 
                    'level' => 'error',
                    'content' => "Error, unable to submit form."
                    )));
            }
            //
            // Form is submitted and validated
            //
            return array('stat'=>'ok');
        }
    }

    //
    // Check if form was submitted and validated in the last 24 hours
    //
    if( !isset($problem_list) ) {
        $lastday = new DateTime('now', new DateTimezone('UTC'));
        $lastday->sub(new DateInterval('P1D'));
        if( isset($form['submission']['dt_last_submitted']) 
            && $form['submission']['dt_last_submitted'] != '' 
            && $form['submission']['dt_last_submitted'] != '0000-00-00 00:00:00' 
            ) {
            $dt_last_submitted = new DateTime($form['submission']['dt_last_submitted'], new DateTimezone('UTC'));
            if( isset($form['submission']['status']) && $form['submission']['status'] == 90 && $dt_last_submitted > $lastday ) {
                //
                // Form is submitted and validated
                //
                return array('stat'=>'ok');
            }
        }
    }
    $form['sections']['submit']['fields']['student_id'] = array(
        'id' => 'student_id',
        'ftype' => 'hidden',
        'label' => '',
        'value' => $item['student_id'],
        );
    $form['sections']['submit']['fields']['submit'] = array(
        'id' => 'submit', 
        'ftype' => 'submit',
//        'label' => (isset($form['submit_label']) && $form['submit_label'] != '' ? $form['submit_label'] : 'Continue'),
        'label' => 'Checkout',
        );

    //
    // Add the form to the blocks
    //
    $blocks[] = array(
        'title' => $form['name'],
        'type' => 'form',
        'checkout' => 'yes',
        'section-selector' => 'no',
        'problem-list' => isset($problem_list) ? $problem_list : '',
        'form-id' => $form['id'],
        'api-save-url' => $request['api_url'] . "/ciniki/forms/submissionSave",
        'api-image-url' => $request['api_url'] . "/ciniki/forms/submissionImage/" . $form['id'] . '/' . $form['submission_id'],
        'api-args' => array(
            'form_id' => $form['id'],
            'submission_id' => $form['submission_id'],
            'customer_id' => $form['customer_id'],
            ),
        'guidelines' => $form['guidelines'],
        'form-sections' => $form['sections'],
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
