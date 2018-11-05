<?php

/**
 * Populates the actual form element when the form is on the first page.
 * 
 * The form supports up to 5 different species, each with a spreadsheet
 * containing the Identifier and location of each tree of that species. The form
 * also includes an optional checkbox to indicate whether the data can be
 * published to the greater TreeGenes site.
 * 
 * @param array $form The unpopulated form element.
 * @param array $form_state The state of the form element.
 * @return array The populated form element.
 */
function page_1_form(&$form, &$form_state){

    // If the page was already visited and some of the fields were filled out,
    // then we need to load the old input to those fields.
    if (isset($form_state['saved_values']['first_page'])){
        $values = $form_state['saved_values']['first_page'];
    }
    // Otherwise, initialized $values as an empty array.
    else{
        $values = array();
    }
    
    // If the species number is already set and the "Add Species" button was
    // pressed, then increase the species number.
    if (isset($form_state['values']['species']['number']) and $form_state['triggering_element']['#name'] == "Add Species"){
        $form_state['values']['species']['number']++;
    }
    // If the species number is already set and the "Remove Species" button was
    // pressed and the species number is greater than 1, then decrease the 
    // species number.
    elseif (isset($form_state['values']['species']['number']) and $form_state['triggering_element']['#name'] == "Remove Species" and $form_state['values']['species']['number'] > 1){
        $form_state['values']['species']['number']--;
    }
    // If the species number is already set, assign it to $species_number.
    $species_number = isset($form_state['values']['species']['number']) ? $form_state['values']['species']['number'] : NULL;
    
    // If the species number is not already set, and there is a previously saved
    // species number, then use that one.
    if (!isset($species_number) and isset($values['species']['number'])){
        $species_number = $values['species']['number'];
    }
    // If the species number is still not set, then set it to the default.
    if (!isset($species_number)){
        $species_number = 1;
    }
    
    // The group of species fields.
    $form['species'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => t('Species information:'),
      '#prefix' => '<div id="species-items">',
      '#suffix' => '</div>'
    );

    // Button to add a new species.
    $form['species']['add'] = array(
      '#type' => 'button',
      '#name' => t('Add Species'),
      '#button_type' => 'button',
      '#value' => t('Add Species'),
      '#ajax' => array(
        'callback' => 'update_species',
        'wrapper' => "species-items"
      ),
    );

    // Button to remove the last species.
    $form['species']['remove'] = array(
      '#type' => 'button',
      '#name' => t('Remove Species'),
      '#button_type' => 'button',
      '#value' => t('Remove Species'),
      '#ajax' => array(
        'callback' => 'update_species',
        'wrapper' => "species-items"
      ),
    );
    
    // Hidden field with the number of species sections that should be shown.
    $form['species']['number'] = array(
      '#type' => 'hidden',
      '#value' => $species_number,
    );
    
    // Build 5 species sections. Not all of these sections will necessarily be shown.
    for($i = 1; $i <= $species_number; $i++){

        // The group of fields for Species $i.
        $form['species']["$i"] = array(
          '#type' => 'fieldset',
          '#title' => t("Species $i:"),
        );

        // Name of Species $i. 
        $form['species']["$i"]['name'] = array(
          '#type' => 'textfield',
          '#title' => t('Name:'),
          '#autocomplete_path' => "gttn/species/autocomplete",
          '#default_value' => isset($values['species']["$i"]['name']) ? $values['species']["$i"]['name'] : NULL,
        );
        
        // Spreadsheet fields for Species $i.
        $form['species']["$i"]['spreadsheet'] = array(
          '#type' => 'fieldset',
          '#title' => t("Species $i spreadsheet:"),
        );
        
        // Location format for the spreadsheet. If the user selects any of 
        // options 1-4, the module will try to find coordinates. Otherwise, the
        // module will look for a country/region combination.
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
        
        // The actual spreadsheet upload.
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
    
    // Check if the data should be published to the greater TreeGenes site.
    $form['public'] = array(
      '#type' => 'checkbox',
      '#title' => t('This information may be published to the greater TreeGenes site.'),
      '#default_value' => isset($values['public']) ? $values['public'] : NULL,
    );

    // Next button.
    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => 'Next',
    );
    
    return $form;
}

/**
 * Ajax callback for updating the species sections on the first page.
 * 
 * @param array $form The rebuilt form element.
 * @param array $form_state The state of the rebuilt form element.
 * @return array The part of the rebuilt form element that will be used to fill
 * the ajax wrapper specified in the triggering form element.
 */
function update_species($form, $form_state){
    
    return $form['species'];
}