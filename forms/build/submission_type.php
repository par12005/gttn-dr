<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_submission_type_create_form(&$form, &$form_state) {

  global $user;
  $user = user_load($user->uid);

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
    '#prefix' => '<div id="gttn-tpps-props-wrapper">',
    '#suffix' => '</div>',
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
      '#description' => t('This option will determine which organization your data and metadata belongs to.'),
    );
  }
  elseif (count($user->organizations) == 1) {
    $form['project']['props']['organization'] = array(
      '#type' => 'hidden',
      '#value' => current($user->organizations),
    );
  }
  else {
    $form['project']['props']['organization'] = array(
      '#type' => 'hidden',
      '#value' => 0,
    );
  }

  $form['project']['props']['analysis_date'] = array(
    '#type' => 'date',
    '#title' => t('Analysis Date: *'),
    '#gttn_tpps_data' => array(
      'cv' => 'tripal_analysis',
    ),
    '#description' => t('The date that the analysis data was collected'),
  );

  $form['project']['props']['pub_doi'] = array(
    '#type' => 'textfield',
    '#title' => t('Publication DOI(s):'),
    '#gttn_tpps_val' => array(),
    '#description' => t('If you have multiple publication DOIs, please include a comma in between each.'),
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
    '#description' => t('If this data came from another database originally, please provide the URL here.'),
  );

  $form['project']['props']['project_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Project Name (Funding Agency/Grant Number):'),
    '#gttn_tpps_val' => array(),
    '#description' => t('This is the name of the higher-level project or grant number that the data and metadata are part of.'),
  );

  $form['project']['props']['type'] = array(
    '#type' => 'select',
    '#title' => t('Submission Type: *'),
    '#options' => array(
      '- Select -',
      'New Trees' => 'New Trees',
      'Existing Trees' => 'Existing Trees',
      'Mixed new/existing Trees' => 'Mixed new/existing Trees',
    ),
    '#description' => t('The type of trees. This option allows GTTN-TPPS to search for matching trees within your organization in order to update information where applicable.'),
  );

  $form['project']['props']['permissions'] = array(
    '#type' => 'radios',
    '#description' => t('Please indicate who is allowed to view or browse this data'),
    '#options' => gttn_tpps_submission_permission_options(),
    '#prefix' => '<div class="gttn-fieldset"><span class="fieldset-legend"><span class="fieldset-legend-prefix element-invisible">Show</span>Data Permissions<span class="summary"></span></span><div class="gttn-fieldset-wrapper">',
    '#ajax' => array(
      'callback' => 'gttn_tpps_props_ajax',
      'wrapper' => 'gttn-tpps-props-wrapper',
    ),
  );

  $perms = gttn_tpps_get_ajax_value($form_state, array('project', 'props', 'permissions'), NULL);
  if ($perms == 'org') {
    $form['project']['props']['permission-orgs'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Selected organizations'),
      '#description' => t('Please select the organizations which are allowed to view or browse this data'),
      '#options' => array(),
      '#suffix' => '</div></div>',
    );
    $query = db_select('gttn_profile_organization', 'o')
      ->fields('o', array('organization_id', 'name'))
      ->execute();

    while (($org = $query->fetchObject())) {
      $form['project']['props']['permission-orgs']['#options'][$org->organization_id] = $org->name;
      $and = db_and()
        ->condition('uid', $user->uid)
        ->condition('organization_id', $org->organization_id);
      $member_query = db_select('gttn_profile_organization_members', 'm')
        ->fields('m', array('organization_id'))
        ->condition($and)
        ->execute();
      if (!empty($member_query->fetchObject()->organization_id)) {
        $form['project']['props']['permission-orgs'][$org->organization_id]['#default_value'] = $org->organization_id;
      }
    }
  }
  else {
    $form['project']['props']['permissions']['#suffix'] = '</div></div>';
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

/**
 *
 */
function gttn_tpps_props_ajax(&$form, &$form_state) {
  return $form['project']['props'];
}
