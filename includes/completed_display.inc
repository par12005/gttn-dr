<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_completed_display($form, $form_state) {

  global $user;
  global $base_url;
  $params = drupal_get_query_parameters();
  $accession = $params['id'];
  $link = "$base_url/gttn_tpps_details/accession/$accession";

  $old_form_state = variable_get('gttn_tpps_complete_' . $user->mail . $accession);
  dpm($old_form_state);
  $values = $old_form_state['saved_values'];

  $css = "
    .tgdr_complete_submission {
        background: #ffffff;
        border: 1px solid #cccccc;
        border-radius: 4px;
        padding: 0px 10px;
    }		
    ";
  drupal_add_css($css, 'inline');

  $display = gttn_tpps_table_display($old_form_state);

  $form['a'] = array(
    '#type' => 'hidden',
    '#suffix' => $display,
  );

  return $form;
}
