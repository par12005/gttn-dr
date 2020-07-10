<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_manage_doi($accession) {
  $output = "";

  $form_state = gttn_tpps_load_submission($accession);

  $token = variable_get('gttn_tpps_zenodo_api_key', NULL);
  if (!empty($token) and empty($form_state['zenodo_id'])) {
    $form_state['zenodo_id'] = gttn_tpps_generate_doi($accession)->id;
    $doi_object = gttn_tpps_doi_publish($form_state['zenodo_id']);
    gttn_tpps_update_submission($form_state);
    $output .= "<h4>DOI successfully published!</h4>";
  }
  elseif (!empty($token) and !empty($form_state['zenodo_id'])) {
    $doi_object = gttn_tpps_doi_load($form_state['zenodo_id']);
  }

  if (empty($token) or empty($doi_object) or (isset($doi_object->status) and $doi_object->status != 200)) {
    $output .= "<h4>Unable to load Zenodo DOI. There is likely a problem with the Zenodo API key. Please contact your system administrator.</h4>";
  }
  else {
    $doi = $doi_object->doi;
    $url = $doi_object->doi_url;
    $badge = $doi_object->links->badge;
    $edit = $doi_object->links->html;
    $output .= "DOI: <a target=\"blank\" href=\"$url\">$doi</a><br>"
    . "DOI Badge: <a target=\"blank\" href=\"$badge\"><img src=\"$badge\"></a><br>";
  }

  return render($output);
}
