<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_submission_type_create_form(&$form, &$form_state) {

  global $user;

  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Submission Name: *'),
  );

  $form['purpose'] = array(
    '#type' => 'textarea',
    '#title' => t('Data Collection Purpose: *'),
    '#description' => t('Please provide a brief description of why this data was collected.')
  );

  $form['date'] = array(
    '#type' => 'date',
    '#title' => t('Analysis Date: *'),
  );

  $form['pub_doi'] = array(
    '#type' => 'textfield',
    '#title' => t('Publication DOI:'),
    '#gttn_tpps_val' => array(),
  );

  $form['data_doi'] = array(
    '#type' => 'textfield',
    '#title' => t('Data DOI:'),
    '#gttn_tpps_val' => array(),
  );

  $form['db_url'] = array(
    '#type' => 'textfield',
    '#title' => t('Original Database URL:'),
    '#gttn_tpps_val' => array(),
  );

  $form['project_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Project Name (Funding Agency/Grant Number):'),
    '#gttn_tpps_val' => array(),
  );

  $form['type'] = array(
    '#type' => 'select',
    '#title' => t('Submission Type: *'),
    '#options' => array(
      '- Select -',
      'New Trees' => 'New Trees',
      'Old Trees' => 'Old Trees',
      'Mixed new/old Trees' => 'Mixed new/old Trees',
    ),
  );

  $form['permissions'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Data permissions'),
    '#description' => t('Please select the organizations which are allowed to view or browse this data'),
    '#options' => array(),
  );

  $query = db_select('gttn_profile_organization', 'o')
    ->fields('o', array('organization_id', 'name'))
    ->execute();

  while (($org = $query->fetchObject())) {
    $form['permissions']['#options'][$org->organization_id] = $org->name;
    $and = db_and()
      ->condition('uid', $user->uid)
      ->condition('organization_id', $org->organization_id);
    $member_query = db_select('gttn_profile_organization_members', 'm')
      ->fields('m', array('organization_id'))
      ->condition($and)
      ->execute();
    if (!empty($member_query->fetchObject()->organization_id)) {
      $form['permissions'][$org->organization_id]['#default_value'] = $org->organization_id;
    }
  }

  $form['disclaimer'] = array(
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
