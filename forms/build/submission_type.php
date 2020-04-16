<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_submission_type_create_form(&$form, &$form_state) {

  global $user;
  $user = gttn_profile_load_user($user->uid);

  $form['project'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
  );

  $form['project']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Submission Name: *'),
    '#prefix' => '<div class="gttn-fieldset"><span class="fieldset-legend"><span class="fieldset-legend-prefix element-invisible">Show</span> Project basic information<span class="summary"></span></span><div class="gttn-fieldset-wrapper">',
  );

  $form['project']['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Data Collection Purpose: *'),
    '#description' => t('Please provide a brief description of why this data was collected.'),
    '#suffix' => '</div></div>',
  );

  $form['project']['props'] = array(
    '#type' => 'fieldset',
    '#title' => t('Project background'),
    '#collapsible' => FALSE,
    '#collapsed' => TRUE,
  );

  if (count($user->organizations) > 1) {
    $options = array();
    $orgs = gttn_profile_organization_load($user->organizations);
    foreach ($orgs as $org) {
      $options[$org->organization_id] = $org->name;
    }
    $form['project']['props']['organization'] = array(
      '#type' => 'radios',
      '#title' => t('Which of your organization are you submitting for?*'),
      '#options' => $options,
    );
  }
  else {
    $form['project']['props']['organization'] = array(
      '#type' => 'hidden',
      '#value' => current($user->organizations),
    );
  }

  $form['project']['props']['analysis_date'] = array(
    '#type' => 'date',
    '#title' => t('Analysis Date: *'),
    '#gttn_tpps_data' => array(
      'cv' => 'tripal_analysis',
    ),
  );

  $form['project']['props']['pub_doi'] = array(
    '#type' => 'textfield',
    '#title' => t('Publication DOI:'),
    '#gttn_tpps_val' => array(),
  );

  $form['project']['props']['data_doi'] = array(
    '#type' => 'textfield',
    '#title' => t('Data DOI:'),
    '#gttn_tpps_val' => array(),
  );

  $form['project']['props']['db_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Original Database URL:'),
    '#gttn_tpps_val' => array(),
  );

  $form['project']['props']['project_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Project Name (Funding Agency/Grant Number):'),
    '#gttn_tpps_val' => array(),
  );

  $form['project']['props']['type'] = array(
    '#type' => 'select',
    '#title' => t('Submission Type: *'),
    '#options' => array(
      '- Select -',
      'New Trees' => 'New Trees',
      'Old Trees' => 'Old Trees',
      'Mixed new/old Trees' => 'Mixed new/old Trees',
    ),
  );

  $form['project']['props']['permissions'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Data permissions'),
    '#description' => t('Please select the organizations which are allowed to view or browse this data'),
    '#options' => array(),
  );

  $query = db_select('gttn_profile_organization', 'o')
    ->fields('o', array('organization_id', 'name'))
    ->execute();

  while (($org = $query->fetchObject())) {
    $form['project']['props']['permissions']['#options'][$org->organization_id] = $org->name;
    $and = db_and()
      ->condition('uid', $user->uid)
      ->condition('organization_id', $org->organization_id);
    $member_query = db_select('gttn_profile_organization_members', 'm')
      ->fields('m', array('organization_id'))
      ->condition($and)
      ->execute();
    if (!empty($member_query->fetchObject()->organization_id)) {
      $form['project']['props']['permissions'][$org->organization_id]['#default_value'] = $org->organization_id;
    }
  }

  $form['project']['props']['disclaimer'] = array(
    '#type' => 'checkbox',
    '#title' => t('I have read and agree to the following disclaimer:'),
    '#description' => t('This is the placeholder disclaimer'),
    '#required' => TRUE,
  );

  // Create the next button.
  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Next'),
  );

  return $form;
}
