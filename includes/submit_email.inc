<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_submit_email($form, $form_state) {

  global $user;
  global $base_url;
  $admin = variable_get('gttn_tpps_admin_email', "treegenesdb@gmail.com");

  $params = array();

  $to = $user->mail;
  $from = variable_get('site_mail', '');
  $params['subject'] = "GTTN-TPPS Submission Received: {$form_state['accession']}";

  $params['organisms'] = $form_state['saved_values'][GTTN_PAGE_1]['organism'];
  $params['accession'] = $form_state['accession'];
  $params['base_url'] = $base_url;
  $params['body'] = '';

  $params['headers'][] = 'MIME-Version: 1.0';
  $params['headers'][] = 'Content-type: text/html; charset=iso-8859-1';

  drupal_mail('gttn_tpps', 'user_recieved', $to, user_preferred_language($user), $params, $from, TRUE);

  $params['user_mail'] = $user->mail;

  drupal_mail('gttn_tpps', 'admin_recieved', $admin, user_preferred_language($user), $params, $from, TRUE);

  return;
}
