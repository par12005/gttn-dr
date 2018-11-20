<?php

/**
 * This function creates the admin panel which allows the administrator to manage all of the complete gttn-tpps submissions.
 *
 * @param $form array The form to be populated.
 * @param $form_state array The state of the form to be populated.
 * @return array The populated form.
 */
function gttn_tpps_admin_panel($form, &$form_state){
    
	// Load the global user object.
    global $user;
	// Load the base site url string.
    global $base_url;
    
	// Get query parameters from url.
    $params = drupal_get_query_parameters();
	// Set $accession variable if accession was included in the url query.
    $accession = isset($params['accession']) ? $params['accession'] : NULL;
    
	// This page can only be accessed by administrators.
    if (!isset($user->roles[3]) or $user->roles[3] !== 'administrator'){
        drupal_access_denied();
        return $form;
    }
	// If the accession query parameter was not set, then list all of the completed gttn-tpps submissions along with their accession number and status.
    elseif (empty($accession)){
        // Get all of the complete gttn-tpps submissions.
        $query = db_select('variable', 'v')
            ->fields('v', array('name'))
            ->condition('v.name', db_like("gttn_tpps_complete_") . '%', 'LIKE')
            ->execute();
        
		// Initialize display string with table tags and headers.
        $display = "<table style='width:-webkit-fill-available' border='1'><thead>";
        $display .= "<tr><th>Accession Number</th><th>Status</th></tr>";
        $display .= "</thead><tbody>";
        $data = array();
		// Iterate through each of the complete gttn-tpps submissions.
        while (($result = $query->fetchObject())){
			// Get the full name of the submission variable.
            $name = $result->name;
			// Load the state of that submission based on the name.
            $state = variable_get($name, NULL);
            if (!empty($state)){
                // Add the submission link, accession number, and status to the data array.
                $item = array(
                  'link' => l($state['accession'], "$base_url/gttn-admin-panel?accession={$state['accession']}"),
                  'accession' => $state['accession'],
                  'status' => $state['status'],
                );
				// If the submission is pending approval, shift it to the top of the table.
                if ($state['status'] == "Pending Approval"){
                    array_unshift($data, $item);
                }
				// Otherwise, add the submission to the end of the table.
                else {
                    $data[] = $item;
                }
            }
        }
        
		// Add each of the submissions to the display table
        foreach ($data as $item){
            $display .= "<tr><td>{$item['link']}</td><td>{$item['status']}</td></tr>";
        }
		// Close the table tags.
        $display .= "</tbody></table>";
        
		// Add the table to a hidden form field.
        $form['a'] = array(
          '#type' => 'hidden',
          '#suffix' => $display,
        );
		
		// If the admin panel is just displaying all of the completed gttn-tpps submissions, then there shouldn't be any actual visible form fields. The panel still needs to be called by drupal_get_form(), because of the form fields that are required when the admin panel is displaying a specific submission for approval.
    }
	// If the accession number was set in the url query, then display the detailed information about that submission, and the appropriate form fields if the submission is still pending approval.
    else {
		// Get the full name of the submission.
        $results = db_select('variable', 'v')
            ->fields('v', array('name'))
            ->condition('v.name', db_like("gttn_tpps_complete_") . '%' . db_like("$accession"), 'LIKE')
            ->execute()
            ->fetchAssoc();
        $var_name = $results['name'];
		// Load the state of the submission based on the full name.
        $submission_state = variable_get($var_name);
		// Get the status of the submission.
        $status = $submission_state['status'];
		// Link back to the overview of all completed submissions.
        $display = l("Back to GTTN-TPPS Admin Panel", "$base_url/gttn-admin-panel");
		// Display the detailed information about the specific submission.
        $display .= gttn_tpps_table_display($submission_state);
        
		// Add the overview link and detail table to the form.
        $form['form_table'] = array(
          '#type' => 'hidden',
          '#value' => $var_name,
          '#suffix' => $display
        );

		// If the submission is still pending approval, show the appropriate form fields.
        if ($status == "Pending Approval"){
			// Create the approval check field.
            $form['approve-check'] = array(
              '#type' => 'checkbox',
              '#title' => t('This submission has been reviewed and approved.')
            );

			// Create the rejection reason field.
            $form['reject-reason'] = array(
              '#type' => 'textarea',
              '#title' => t('Reason for rejection:'),
              '#states' => array(
                'invisible' => array(
                  ':input[name="approve-check"]' => array('checked' => TRUE)
                )
              )
            );

			// Create the reject button.
            $form['REJECT'] = array(
              '#type' => 'submit',
              '#value' => t('Reject'),
              '#states' => array(
                'invisible' => array(
                  ':input[name="approve-check"]' => array('checked' => TRUE)
                )
              )
            );

			// Create the approve button.
            $form['APPROVE'] = array(
              '#type' => 'submit',
              '#value' => t('Approve'),
              '#states' => array(
                'visible' => array(
                  ':input[name="approve-check"]' => array('checked' => TRUE)
                )
              )
            );
        }
    }
    return $form;
}

/**
 * Validates the admin panel form.
 *
 * @param $form array The form to be validated.
 * @param $form_state array The state of the form to be validated.
 */
function gttn_tpps_admin_panel_validate($form, &$form_state){
    if ($form_state['submitted'] == '1'){
		// If the submission is being rejected, the admin must provide a rejection reason.
        if ($form_state['values']['reject-reason'] == '' and $form_state['triggering_element']['#value'] == 'Reject'){
            form_set_error('reject-reason', 'Please explain why the submission was rejected.');
        }
    }
}

/**
 * If the admin panel was validated, make changes to the form state and notify the user accordingly.
 *
 * @param $form array The form to be submitted.
 * @param $form_state array The state of the form to be submitted.
 */
function gttn_tpps_admin_panel_submit($form, &$form_state){
    
	// Get the base site url string.
    global $base_url;

	// Get the full name of the submission.
    $var_name = $form_state['values']['form_table'];
    // Save the email and accession number of the form in case it needs to be rejected.
	$suffix = substr($var_name, 14);
    // Get the user email to be notified.
	$to = substr($var_name, 19, -12);
	// Get the state of the submission based on the full name.
    $state = variable_get($var_name);
    $params = array();

	// Get the admin email address.
    $from = variable_get('site_mail', '');
	// Initialize parameters for user notification emails.
    $params['subject'] = "GTTN-TPPS Submission Rejected: {$state['accession']}";
    $params['uid'] = user_load_by_name($to)->uid;
    $params['reject-reason'] = $form_state['values']['reject-reason'];
    $params['base_url'] = $base_url;
    $params['accession'] = $state['accession'];
    $params['body'] = '';

	// Add headers for user notification email.
    $params['headers'][] = 'MIME-Version: 1.0';
    $params['headers'][] = 'Content-type: text/html; charset=iso-8859-1';
    
	// If the reject button was pressed, then reject the submission, notify the user, and change the submission back to an incomplete one.	
    if ($form_state['triggering_element']['#value'] == 'Reject'){
        // Send user notification email.
        drupal_mail('gttn_tpps', 'user_rejected', $to, user_preferred_language(user_load_by_name($to)), $params, $from, TRUE);
		// Remove the complete submission variable.
        variable_del($var_name);
		// Remove the status property from the submission.
        unset($state['status']);
		// Set the incomplete submission variable
        variable_set('gttn_tpps_incomplete_' . $suffix, $state);
		// Let the admin know that the submission was successfully rejected.
        dpm('Submission Rejected. Message has been sent to user.');
        drupal_goto('<front>');
    }
	// If the reject button was not pressed, then the submission is approved.
    else{
		// Load the gttn-tpps submission functions.
        module_load_include('php', 'gttn_tpps', 'forms/submit/submit_all');
		// Get the user object of the admin.
        global $user;
		// Get the admin user id.
        $uid = $user->uid;
        $includes = array();
		// Include the gttn-tpps module in the file parsing tripal job.
        $includes[] = module_load_include('module', 'gttn_tpps');
        
		// Change the subject of the user notification email and provide the submission accession number.
        $params['subject'] = "GTTN-TPPS Submission Approved: {$state['accession']}";
        $params['accession'] = $state['accession'];
        
		// Change the status of the form state and save it.
        $state['status'] = 'Approved';
        variable_set($var_name, $state);
		// Immediately submit the non-file values from the submission to the database.
        gttn_tpps_submit_all($state);
		// Provide the arguments to the file parsing tripal job and submit it.
        $args = array($state);
        $jid = tripal_add_job("GTTN-TPPS File Parsing - {$state['accession']}", 'gttn_tpps', 'gttn_tpps_file_parsing', $args, $uid, 10, $includes, TRUE);
        $state['job_id'] = $jid;
        
		// Let the admin know that the submission has been approved and the user was notified.
        dpm('Submission Approved! Message has been sent to user.');
		// Send the approval notification email to the user.
        drupal_mail('gttn_tpps', 'user_approved', $to, user_preferred_language(user_load_by_name($to)), $params, $from, TRUE);
    }
}
