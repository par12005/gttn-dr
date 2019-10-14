<?php

/**
 * @file
 */

/**
 * This function creates the autocomplete options for the species name fields.
 *
 * @param $string
 *   string The input that the user has already provided to the autocomplete field.
 */
function gttn_tpps_species_autocomplete($string) {
  $matches = array();

  // Separate the input by spaces.
  $parts = explode(" ", $string);
  // If there was less than one word, then initialize the second word to an empty string.
  if (!isset($parts[1])) {
    $parts[1] = "";
  }

  // Get each of the species from the database where the genus starts with the letters of the first word, and the species starts with the letters of the second word.
  $result = db_select('chado.organism', 'organism')
    ->fields('organism', array('genus', 'species'))
    ->condition('genus', db_like($parts[0]) . '%', 'LIKE')
    ->condition('species', db_like($parts[1]) . '%', 'LIKE')
    ->orderBy('genus')
    ->orderBy('species')
    ->execute();

  // Display each of the species found in the database as autocomplete options.
  foreach ($result as $row) {
    $matches[$row->genus . " " . $row->species] = check_plain($row->genus . " " . $row->species);
  }

  // Output the autocomplete options.
  drupal_json_output($matches);
}

/**
 * This function updates the managed_file element when the no-header field is changed.
 *
 * @param $form
 *   array The newly built form.
 * @param $form_state
 *   array The state of the newly built form.
 *
 * @return array The element to be replaced in the old form.
 */
function gttn_tpps_no_header_callback($form, &$form_state) {
  // Get the parent elements of the no-header field.
  $parents = $form_state['triggering_element']['#parents'];
  // Remove the last parent element.
  array_pop($parents);

  // The new element to be replaced is the parent of the no-header field.
  $element = drupal_array_get_nested_value($form, $parents);
  // Return the new element to be replaced.
  return $element;
}
