<?php

/**
 * @file
 */

/**
 * 
 */
function gttn_tpps_isotope_callback(&$form, $form_state) {
  return $form['isotope'];
}

/**
 * 
 */
function gttn_tpps_genetic_callback(&$form, $form_state) {
  return $form['genetic'];
}

/**
 * This function returns the newly built BioProject Id field.
 *
 * @param $form
 *   array The newly built form.
 * @param $form_state
 *   array The state of the newly built form.
 *
 * @return array The newly built BioProject Id field.
 */
function ajax_bioproject_callback(&$form, $form_state) {

  // Get the organism number associated with the current BioProject Id field.
  $ajax_id = $form_state['triggering_element']['#parents'][0];

  // Return the current BioProject Id field.
  return $form[$ajax_id]['genotype']['assembly-auto'];
}

/**
 * This function displays the new genotype assay file if the snps option is selected from the genotype marker types.
 *
 * @param $form
 *   array The newly built form.
 * @param $form_state
 *   array The state of the newly built form.
 *
 * @return array The ajax commands to be executed.
 */
function snps_file_callback($form, $form_state) {
  // Get the organism number associated with the current genotype marker type field.
  $id = $form_state['triggering_element']['#parents'][0];
  $commands = array();
  // Replace the old genotype file field with the new one.
  $commands[] = ajax_command_replace("#edit-$id-genotype-file-ajax-wrapper", drupal_render($form[$id]['genotype']['file']));
  // If the Genotype Assay file type is not selected, then hide the genotype file field.
  if (!$form_state['complete form'][$id]['genotype']['file-type']['Genotype Assay']['#value']) {
    $commands[] = ajax_command_invoke(".form-item-$id-genotype-file", 'hide');
  }
  // Otherwise, show the genotype file field.
  else {
    $commands[] = ajax_command_invoke(".form-item-$id-genotype-file", 'show');
  }

  // Return the ajax commands.
  return array('#type' => 'ajax', '#commands' => $commands);
}
