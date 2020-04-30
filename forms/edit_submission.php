<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_main_edit($form, &$form_state, $accession = NULL) {

  $state = gttn_tpps_load_submission($accession);
  if (empty($accession) or empty($state)) {
    drupal_goto('<front>');
  }

  $form['accession'] = array(
    '#type' => 'hidden',
    '#value' => $accession,
  );

  $form['project_name'] = array(
    '#type' => 'textfield', 
    '#title' => t('Submission Name'),
    '#default_value' => $state['data']['project']['name'],
  );

  $form['organism'] = array(
    '#type' => 'fieldset',
    '#title' => '<div class="fieldset-title">' . t('Species Names') . '</div>',
    '#tree' => TRUE,
    '#collapsible' => TRUE,
  );

  foreach ($state['data']['organism'] as $key => $org) {
    $form['organism'][$key] = array(
      '#type' => 'textfield',
      '#title' => "Species $key: *",
      '#autocomplete_path' => 'gttn-species/autocomplete',
      '#attributes' => array(
        'data-toggle' => array('tooltip'),
        'data-placement' => array('left'),
        'title' => array('If your species is not in the autocomplete list, don\'t worry about it! We will create a new organism entry in the database for you.'),
      ),
      '#description' => 'Please select your species from the autocomplete list. If your species is not in the autocomplete list, then a new species will be added to the database.',
      '#default_value' => "{$org['genus']} {$org['species']}",
      '#gttn_tpps_val' => array(
        'function' => 'gttn_tpps_validate_organism',
        'standard' => TRUE,
      ),
    );
  }

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  drupal_add_js(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_JS_PATH);
  drupal_add_css(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_CSS_PATH);

  return $form;
}

/**
 *
 */
function gttn_tpps_main_edit_validate($form, &$form_state) {
  if ($form_state['submitted']) {
    module_load_include('php', 'gttn_tpps', 'forms/validate');
    gttn_tpps_validate($form, $form_state);
  }
}

/**
 *
 */
function gttn_tpps_main_edit_submit($form, &$form_state) {
  $vals = $form_state['values'];
  $accession = $vals['accession'];
  $state = gttn_tpps_load_submission($accession);

  if ($state['data']['project']['name'] != $vals['project_name']) {
    $state['data']['project']['name'] = $vals['project_name'];
    $state['saved_values'][GTTN_TYPE_PAGE]['project']['name'] = $vals['project_name'];
  }

  foreach ($state['data']['organism'] as $id => $info) {
    $org_name = "{$info['genus']} {$info['species']}";
    if ($org_name != $vals['organism'][$id]) {
      $parts = explode(' ', $vals['organism'][$id]);
      $state['saved_values'][GTTN_PAGE_1]['organism'][$id] = $vals['organism'][$id];
      $state['data']['organism'][$id] = array(
        'genus' => $parts[0],
        'species' => implode(' ', array_slice($parts, 1)),
      );
    }
  }

  gttn_tpps_update_submission($state);

  module_load_include('php', 'gttn_tpps', 'forms/submit/submit_all');

  global $user;
  $uid = $user->uid;
  $state['submitting_uid'] = $uid;

  $includes = array();
  $includes[] = module_load_include('php', 'gttn_tpps', 'forms/submit/submit_all');
  $args = array($accession);

  $jid = tripal_add_job("GTTN-TPPS Edit Submission - $accession", 'gttn_tpps', 'gttn_tpps_submit_all', $args, $state['submitting_uid'], 10, $includes, TRUE);
  $state['job_id'] = $jid;
  gttn_tpps_update_submission($state);

  drupal_goto("gttn-completed-submission/$accession");
}
