<?php

function submission_type_create_form(&$form, &$form_state){
    
    global $user;
    
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
    
    $form['permissions'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Data permissions'),
      '#description' => t('Please select the organizations which are allowed to view or browse this data'),
      '#options' => array(),
    );
    
    $query = db_select('gttn_profile_organization', 'o')
        ->fields('o', array('organization_id', 'name'))
        ->execute();
    
    while (($org = $query->fetchObject())){
        $form['permissions']['#options'][$org->organization_id] = $org->name;
        $and = db_and()
            ->condition('uid', $user->uid)
            ->condition('organization_id', $org->organization_id);
        $member_query = db_select('gttn_profile_organization_members', 'm')
            ->fields('m', array('organization_id'))
            ->condition($and)
            ->execute();
        if (!empty($member_query->fetchObject()->organization_id)){
            $form['permissions'][$org->organization_id]['#default_value'] = $org->organization_id;
        }
    }
    
    // Create the next button.
    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );
    
    return $form;
}
