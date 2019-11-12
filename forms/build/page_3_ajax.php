<?php

/**
 * @file
 */

/**
 * 
 */
function gttn_tpps_accession_pop_group(array &$form, array &$form_state) {
  $species_id = $form_state['triggering_element']['#parents'][1];
  return $form['tree-accession'][$species_id]['pop-group'];
}

/**
 * 
 */
function gttn_tpps_accession_multi_file(&$form, &$form_state) {
  return $form['tree-accession'];
}

/**
 * 
 */
function gttn_tpps_samples_callback(&$form, &$form_state) {
  return $form['samples'];
}