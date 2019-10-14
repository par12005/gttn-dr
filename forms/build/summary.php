<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_summary_create_form(&$form, $form_state) {

  $form['Back'] = array(
    '#type' => 'submit',
    '#value' => t('Back'),
    '#prefix' => gttn_tpps_table_display($form_state),
  );

  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
  );

  return $form;
}
