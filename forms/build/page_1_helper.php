<?php

/**
 * @file
 */

/**
 * Creates the organism set of fields for the first page and adds them to the
 * form element.
 *
 * @param array $form
 *   The form element to add the organism fields to.
 * @param array $values
 *   The loaded values from the saved values array.
 *
 * @return array The form element with the organism fields added.
 */
function organism(&$form, $values) {

  // Create the organism fieldset.
  $form['organism'] = array(
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#title' => t('<div class="fieldset-title">Organism information:</div>'),
    '#description' => t('Up to 5 organisms per submission.'),
    '#collapsible' => TRUE,
  );

  // Create the Add Organism button.
  $form['organism']['add'] = array(
    '#type' => 'button',
    '#title' => t('Add Organism'),
    '#button_type' => 'button',
    '#value' => t('Add Organism'),
  );

  // Create the Remove Organism button.
  $form['organism']['remove'] = array(
    '#type' => 'button',
    '#title' => t('Remove Organism'),
    '#button_type' => 'button',
    '#value' => t('Remove Organism'),
  );

  // Create the hidden organism number field.
  $form['organism']['number'] = array(
    '#type' => 'hidden',
    '#default_value' => isset($values['organism']['number']) ? $values['organism']['number'] : '1',
  );

  // Create 5 organism name fields. Not all of the fields will necessarily be shown.
  for ($i = 1; $i <= 5; $i++) {
    // Create the field for organism $i.
    $form['organism']["$i"] = array(
      '#type' => 'textfield',
      '#title' => t("Species $i: *"),
        // Call the GTTN-TPPS species autocomplete function when the user
        // starts typing.
      '#autocomplete_path' => "gttn-species/autocomplete",
        // Add the bootstrap tooltip for this field.
      '#attributes' => array(
        'data-toggle' => array('tooltip'),
        'data-placement' => array('left'),
        'title' => array('If your species is not in the autocomplete list, don\'t worry about it! We will create a new organism entry in the database for you.'),
      ),
      '#description' => 'Please select your species from the autocomplete list. If your species is not in the autocomplete list, then a new species will be added to the database.',
    );
  }

  return $form;
}
