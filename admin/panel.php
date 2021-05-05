<?php

/**
 * @file
 */

/**
 * This function creates the admin panel which allows the administrator to manage all of the complete gttn-tpps submissions.
 *
 * @param $form
 *   array The form to be populated.
 * @param $form_state
 *   array The state of the form to be populated.
 *
 * @return array The populated form.
 */
function gttn_tpps_admin_panel($form, &$form_state, $accession = NULL) {

  // Load the global user object.
  global $user;
  // Load the base site url string.
  global $base_url;

  if (empty($accession)) {
    $states = gttn_tpps_load_submission_multiple(array(
      'status' => array(
        'Pending Approval',
        'Submission Job Running',
        'Approved',
        'Approved - Delayed Submission Release',
      ),
      'archived' => array(0, 1),
    ));

    $pending = array();
    $approved = array();
    foreach ($states as $state) {
      if (!empty($state) and gttn_tpps_submission_approval_access($user->uid, $state['accession'])) {
        //dpm($state);
        $row = array(
          l($state['accession'], "$base_url/gttn-approval-panel/{$state['accession']}"),
          $state['data']['project']['name'] . ' ' . gttn_tpps_submission_data_indicators($state),
          $state['status'],
        );
        if ($state['status'] == 'Pending Approval') {
          $pending[(int) substr($state['accession'], 9)] = $row;
        }
        else {
          $approved[(int) substr($state['accession'], 9)] = $row;
        }
      }
    }
    ksort($pending);
    ksort($approved);
    $rows = array_merge($pending, $approved);

    $headers = array(
      'Accession Number',
      'Name',
      'Status',
    );

    $vars = array(
      'header' => $headers,
      'rows' => $rows,
      'attributes' => array(
        'class' => array('view', 'gttn_tpps_table'),
        'id' => 'gttn_tpps_table_display',
      ),
      'caption' => '',
      'colgroups' => NULL,
      'sticky' => FALSE,
      'empty' => '',
    );

    $form['#attributes'] = array('class' => array('hide-me'));
    $form['#suffix'] = "<div class='gttn_tpps_table'><label for='gttn_tpps_table_display'>Completed GTTN-TPPS Submissions</label>" . theme_table($vars) . "</div>";
  }
  // If the accession number was set in the url query, then display the
  // detailed information about that submission, and the appropriate form
  // fields if the submission is still pending approval.
  else {
    $submission_state = gttn_tpps_load_submission($accession);
    $status = $submission_state['status'];
    $display = l(t("Back to GTTN-TPPS Admin Panel"), "$base_url/gttn-approval-panel");
    $display .= gttn_tpps_table_display($submission_state);

    // Add the overview link and detail table to the form.
    $form['form_table'] = array(
      '#type' => 'hidden',
      '#value' => $accession,
      '#suffix' => $display,
    );

    // If the submission is still pending approval, show the appropriate form fields.
    if ($status == "Pending Approval") {
      // Create the approval check field.
      $form['approve-check'] = array(
        '#type' => 'checkbox',
        '#title' => t('This submission has been reviewed and approved.'),
      );

      // Create the rejection reason field.
      $form['reject-reason'] = array(
        '#type' => 'textarea',
        '#title' => t('Reason for rejection:'),
        '#states' => array(
          'invisible' => array(
            ':input[name="approve-check"]' => array('checked' => TRUE),
          ),
        ),
      );

      // Create the reject button.
      $form['REJECT'] = array(
        '#type' => 'submit',
        '#value' => t('Reject'),
        '#states' => array(
          'invisible' => array(
            ':input[name="approve-check"]' => array('checked' => TRUE),
          ),
        ),
      );

      // Create the approve button.
      $form['APPROVE'] = array(
        '#type' => 'submit',
        '#value' => t('Approve'),
        '#states' => array(
          'visible' => array(
            ':input[name="approve-check"]' => array('checked' => TRUE),
          ),
        ),
      );
    }

    $form['state-status'] = array(
      '#type' => 'select',
      '#title' => t('Change state status'),
      '#description' => t('Warning: This feature is experimental and may cause unforseen issues. Please do not change the status of this submission unless you are willing to risk the loss of existing data. The current status of the submission is @status.', array('@status' => $status)),
      '#options' => array(
        'Incomplete' => 'Incomplete',
        'Pending Approval' => 'Pending Approval',
        'Submission Job Running' => 'Submission Job Running',
        'Approved' => 'Approved',
        'Approved - Delayed Submission Release' => 'Approved - Delayed Submission Release',
      ),
      '#default_value' => $status,
    );

    $form['CHANGE_STATUS'] = array(
      '#type' => 'submit',
      '#value' => t('Change Status'),
      '#states' => array(
        'invisible' => array(
          ':input[name="state-status"]' => array('value' => $status),
        ),
      ),
    );

    if ($status == "Approved") {
      $form['ARCHIVE'] = array(
        '#type' => 'button',
        '#value' => t('Archive this data'),
        '#description' => 'This button will archive this submission so it no longer appears in the browse reference data section.',
        '#ajax' => array(
          'callback' => 'gttn_tpps_archive_data',
          'wrapper' => 'archive-wrapper',
        ),
        '#prefix' => '<div id="archive-wrapper">',
        '#suffix' => '</div>',
      );
    }
  }

  drupal_add_js(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_JS_PATH);
  drupal_add_css(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_CSS_PATH);

  return $form;
}

/**
 *
 */
function gttn_tpps_archive_data($form, $form_state) {
  $state = gttn_tpps_load_submission($form_state['values']['form_table']);
  gttn_tpps_update_submission($state, array(
    'archived' => 1,
  ));
  return $form['ARCHIVE'];
}

/**
 * Validates the admin panel form.
 *
 * @param $form
 *   array The form to be validated.
 * @param $form_state
 *   array The state of the form to be validated.
 */
function gttn_tpps_admin_panel_validate($form, &$form_state) {
  if ($form_state['submitted'] == '1') {
    // If the submission is being rejected, the admin must provide a rejection reason.
    if ($form_state['values']['reject-reason'] == '' and $form_state['triggering_element']['#value'] == 'Reject') {
      form_set_error('reject-reason', 'Please explain why the submission was rejected.');
    }
  }
}

/**
 * If the admin panel was validated, make changes to the form state and notify the user accordingly.
 *
 * @param $form
 *   array The form to be submitted.
 * @param $form_state
 *   array The state of the form to be submitted.
 */
function gttn_tpps_admin_panel_submit($form, &$form_state) {

  // Get the base site url string.
  global $base_url;

  $accession = $form_state['values']['form_table'];
  $submission = gttn_tpps_load_submission($accession, FALSE);
  $user = user_load($submission->uid);
  $to = $user->mail;
  $state = unserialize($submission->submission_state);
  $params = array();

  // Get the admin email address.
  $from = variable_get('site_mail', '');
  // Initialize parameters for user notification emails.
  $params['subject'] = "GTTN-TPPS Submission Rejected: {$state['accession']}";
  $params['uid'] = $user->uid;
  $params['reject-reason'] = $form_state['values']['reject-reason'] ?? NULL;
  $params['base_url'] = $base_url;
  $params['accession'] = $state['accession'];
  $params['body'] = '';

  // Add headers for user notification email.
  $params['headers'][] = 'MIME-Version: 1.0';
  $params['headers'][] = 'Content-type: text/html; charset=iso-8859-1';

  // If the reject button was pressed, then reject the submission, notify the user, and change the submission back to an incomplete one.
  if ($form_state['triggering_element']['#value'] == 'Reject') {
    // Send user notification email.
    drupal_mail('gttn_tpps', 'user_rejected', $to, user_preferred_language($user), $params, $from, TRUE);
    // Set submission status to Incomplete.
    unset($state['status']);
    gttn_tpps_update_submission($state, array('status' => 'Incomplete'));
    // Let the admin know that the submission was successfully rejected.
    drupal_set_message(t('Submission Rejected. Message has been sent to user.'), 'status');
    drupal_goto('gttn-approval-panel');
  }
  // If the reject button was not pressed, then the submission is approved.
  elseif ($form_state['triggering_element']['#value'] == 'Approve') {
    // Load the gttn-tpps submission functions.
    module_load_include('php', 'gttn_tpps', 'forms/submit/submit_all');

    global $user;
    $uid = $user->uid;
    $state['submitting_uid'] = $uid;

    $params['subject'] = "GTTN-TPPS Submission Approved: {$state['accession']}";
    $params['accession'] = $state['accession'];
    drupal_set_message(t('Submission Approved! Message has been sent to user.'), 'status');
    drupal_mail('gttn_tpps', 'user_approved', $to, user_preferred_language(user_load_by_name($to)), $params, $from, TRUE);

    // Send email to all admins
    try {
        $rid = 3; // Administrator role id
        $query = db_select('users', 'u');
        $query->fields('u', array('uid', 'name'));
        $query->innerJoin('users_roles', 'r', 'r.uid = u.uid');
        $query->condition('r.rid', $rid);
        $query->orderBy('u.name');

        $result = $query->execute();
        foreach ($result as $admin_user) {
            $to = $admin_user->name;
            drupal_mail('gttn_tpps', 'user_approved', $to, user_preferred_language(user_load_by_name($to)), $params, $from, TRUE);
        }
    }
    catch (Exception $e) {

    }


    $includes = array();
    $includes[] = module_load_include('php', 'gttn_tpps', 'forms/submit/submit_all');
    // TODO: evaluate whether file parsing file is needed.
    $includes[] = module_load_include('inc', 'gttn_tpps', 'includes/file_parsing');
    $args = array($accession);

    $jid = tripal_add_job("GTTN-TPPS Record Submission - $accession", 'gttn_tpps', 'gttn_tpps_submit_all', $args, $state['submitting_uid'], 10, $includes, TRUE);
    $state['job_id'] = $jid;
    gttn_tpps_update_submission($state);
  }
  else {
    $state['status'] = $form_state['values']['state-status'];
    gttn_tpps_update_submission($state);
  }
}
