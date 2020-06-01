<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_dart_callback(&$form, $form_state) {
  return $form['dart'];
}

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
 *
 */
function gttn_tpps_anatomy_callback(&$form, $form_state) {
  return $form['anatomy'];
}

/**
 *
 */
function gttn_tpps_slides_callback($form, $form_state) {
  return $form['anatomy']['slides'];
}
