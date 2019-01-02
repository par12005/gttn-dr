<?php

function submission_type_create_form(&$form, &$form_state){
    
    $form['type'] = array(
      '#type' => 'select',
      '#title' => t('Please select the submission type: *'),
      '#options' => array(
        0 => '- Select -',
        'New Trees' => 'New Trees',
        'Old Trees' => 'Old Trees',
        'Mixed new/old Trees' => 'Mixed new/old Trees'
      )
    );
    
    // Create the next button.
    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );
    
    return $form;
}
