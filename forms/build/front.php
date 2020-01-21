<?php

/**
 * @file
 */

/**
 * Populates the form element for the front page of the form, accessible to both
 * anonymous and authenticated users.
 *
 * @global string $base_url The base url of the site.
 * @global stdObject $user The user trying to use GTTN-TPPS.
 * @param array $form
 *   The form to be created.
 * @param array $form_state
 *   The state of the form to be created.
 *
 * @return array The created form.
 */
function gttn_tpps_front_create_form(&$form, $form_state) {

  // Get the base url string.
  global $base_url;
  // Get the user object.
  global $user;

  // If the user is logged in, then they can see the options to either create
  // a new submission, or continue an already started one.
  if (isset($user->mail)) {
    // Initialize options array.
    $options_arr = array('new' => 'Create new GTTN-TPPS Submission');

    // Load all of the incomplete submission variables associated with the
    // user's username.
    $states = gttn_tpps_load_submission_multiple(array('status' => 'Incomplete', 'uid' => $user->uid));

    // Iterate through each of the incomplete submission variables.
    foreach ($states as $state) {
      // If the state loads correctly and actually has data associated
      // with it, then add that state to the list of options to choose from.
      if (!empty($state) and isset($state['saved_values'][GTTN_PAGE_1]['organism'][1])) {
        $options_arr["{$state['accession']}"] = "{$state['accession']}";
      }
      // Otherwise, if the state loads correctly and does not have data
      // associated with it, then remove the empty state variable from the
      // database.
      elseif (isset($state) and !isset($state['saved_values'][GTTN_PAGE_1])) {
        gttn_tpps_delete_submission($state['accession'], FALSE);
      }
    }

    // If the options array has more than one option, then that means that
    // the user has incomplete submissions that they can choose from, so we
    // should provide them with a drop-down menu.
    if (count($options_arr) > 1) {
      $form['accession'] = array(
        '#type' => 'select',
        '#title' => t('Would you like to load an old GTTN-TPPS submission, or create a new one?'),
        '#options' => $options_arr,
        '#default_value' => isset($form_state['saved_values']['frontpage']['accession']) ? $form_state['saved_values']['frontpage']['accession'] : 'new',
      );
    }
    // If the options array has only one option, then that means that the
    // user does not have incomplete submissions, so should not be provided
    // with a drop-down menu.
  }

  // Create Continue to GTTN-TPPS button.
  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Continue to GTTN-TPPS'),
  );

  // Front page introductory text.
  $prefix_text = "<div>Welcome to GTTN-TPPS!<br><br>To get started, you will need to have a few things handy:<br><ul><li>An enabled and approved GTTN account - you can create one <a href='$base_url/user/register'>here</a>. There may be a waiting period to have your account approved by a GTTN administrator.</li></ul>If you would like to submit your data, you can click the button 'Continue to GTTN-TPPS' below!<br><br></div>";

  // Add the introductory text to the first form element.
  if (isset($form['accession'])) {
    $form['accession']['#prefix'] = $prefix_text;
  }
  else {
    $form['Next']['#prefix'] = $prefix_text;
  }

  return $form;
}
