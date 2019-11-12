<?php

/**
 * @file
 * Load page 1 helper functions.
 */

require_once 'page_1_ajax.php';

/**
 * Populates the form element for the first page of the form. This page and any
 * subsequent pages are accessible to only authenticated users.
 *
 * @param array $form
 *   The form element to be populated.
 * @param array $form_state
 *   The form state associated with the form to be populated.
 *
 * @return array The populated form element.
 */
function gttn_tpps_page_1_create_form(&$form, $form_state) {

  // Load saved values for the first page if they are available.
  if (isset($form_state['saved_values'][GTTN_PAGE_1])) {
    $values = $form_state['saved_values'][GTTN_PAGE_1];
  }
  else {
    $values = array();
  }

  $field = array(
    '#type' => 'textfield',
    '#title' => "Species !num",
    '#autocomplete_path' => 'gttn-species/autocomplete',
    '#attributes' => array(
      'data-toggle' => array('tooltip'),
      'data-placement' => array('left'),
      'title' => array('If your species is not in the autocomplete list, don\'t worry about it! We will create a new organism entry in the database for you.'),
    ),
    '#description' => 'Please select your species from the autocomplete list. If your species is not in the autocomplete list, then a new species will be added to the database.',
    '#gttn_tpps_val' => array(
      'function' => 'gttn_tpps_validate_organism',
      'standard' => TRUE,
    ),
  );

  gttn_tpps_dynamic_list($form, $form_state, 'organism', $field, array(
    'label' => 'Organism',
    'default' => 1,
    'substitute_fields' => array(
      '#title',
    ),
  ));

  // Create the data type drop-down menu.
  $form['data_type'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Data Type: *'),
    '#options' => array(
      'Sample Data' => 'Sample Data',
      'DART Reference Data' => 'DART Reference Data',
      'Isotope Reference Data' => 'Isotope Reference Data',
      'Genetic Reference Data' => 'Genetic Reference Data',
      'Anatomical Reference Data' => 'Anatomical Reference Data',
    ),
  );

  // Create the back button.
  $form['Back'] = array(
    '#type' => 'submit',
    '#value' => t('Back'),
    '#prefix' => '<div class="input-description">* : Required Field</div>',
  );

  // Create the save information button.
  $form['Save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  // Create the next button.
  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Next'),
  );

  return $form;
}
