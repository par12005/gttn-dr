<?php

function page_1_form(&$form, $form_state){

    if (isset($form_state['saved_values']['first_page'])){
        $values = $form_state['saved_values']['first_page'];
    }
    else{
        $values = array();
    }
    
    $form['species'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => t('Species information:'),
      '#description' => t('Up to 5 species per submission.'),
    );

    $form['species']['add'] = array(
      '#type' => 'button',
      '#title' => t('Add Species'),
      '#button_type' => 'button',
      '#value' => t('Add Species')
    );

    $form['species']['remove'] = array(
      '#type' => 'button',
      '#title' => t('Remove Species'),
      '#button_type' => 'button',
      '#value' => t('Remove Species')
    );

    $form['species']['number'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($values['species']['number']) ? $values['species']['number'] : '1',
    );

    for($i = 1; $i <= 5; $i++){

        $form['species']["$i"] = array(
          '#type' => 'fieldset',
          '#title' => t("Species $i:"),
        );

        $form['species']["$i"]['name'] = array(
          '#type' => 'textfield',
          '#title' => t('Name:'),
          '#autocomplete_path' => "gttn/species/autocomplete",
          '#default_value' => isset($values['species']["$i"]['name']) ? $values['species']["$i"]['name'] : NULL,
        );
        
        $form['species']["$i"]['spreadsheet'] = array(
          '#type' => 'fieldset',
          '#title' => t("Species $i spreadsheet:"),
        );
        
        $form['species']["$i"]['spreadsheet']['location'] = array(
          '#type' => 'select',
          '#title' => t('Location format:'),
          '#options' => array(
            0 => '- Select -',
            1 => 'Exact (WGS 84)',
            2 => 'Exact (NAD 83)',
            3 => 'Exact (ETRS 89)',
            4 => 'Custom Coordinates format',
            5 => 'Country/Region',
          ),
          '#default_value' => isset($values['species']["$i"]['spreadsheet']['location']) ? $values['species']["$i"]['spreadsheet']['location'] : 0,
        );
        
        $form['species']["$i"]['spreadsheet']['file'] = array(
          '#type' => 'managed_file',
          '#title' => t("Species $i file:"),
          '#upload_location' => 'public://',
          '#upload_validators' => array(
            'file_validate_extensions' => array('txt csv xlsx'),
          ),
          '#default_value' => isset($values['species']["$i"]['spreadsheet']['file']) ? $values['species']["$i"]['spreadsheet']['file'] : NULL,
          '#description' => 'Columns with information describing the Identifier of the tree and the location of the tree are required.'
        );
    }
    
    $form['public'] = array(
      '#type' => 'checkbox',
      '#title' => t('This information may be published to the greater TreeGenes site.'),
      '#default_value' => isset($values['public']) ? $values['public'] : NULL,
    );

    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => 'Next',
    );
    
    return $form;
}
