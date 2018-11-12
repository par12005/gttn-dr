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
    if (isset($form_state['values']['species']['number']) and $form_state['triggering_element']['#name'] == "Add Species" and $form_state['values']['species']['number'] < 10){
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
    
    // String for description field for species location file uploads.
    $file_description = "Please upload a spreadsheet file containing tree "
        . "population data. When your file is uploaded, you will be shown a "
        . "table with your column header names, several drop-downs, and the "
        . "first few rows of your file. You will be asked to define the data "
        . "type for each column, using the drop-downs provided to you. If a "
        . "column data type does not fit any of the options in the drop-down "
        . "menu, you may omit that drop-down menu. Your file must contain "
        . "columns with information about at least the Tree Identifier and the "
        . "Location of the tree (either gps coordinates or country/state).";
    
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
    
    // Build 10 species sections. Not all of these sections will necessarily be shown.
    for($i = 1; $i <= 10; $i++){

        // The group of fields for Species $i.
        $form['species']["$i"] = array(
          '#type' => 'fieldset',
          '#title' => t("Species $i:"),
          '#tree' => TRUE,
          '#attributes' => array(
            'name' => array($i),
          ),
        );

        // Name of Species $i. 
        $form['species']["$i"]['name'] = array(
          '#type' => 'textfield',
          '#title' => t('Name:'),
          '#autocomplete_path' => "gttn/species/autocomplete",
          '#default_value' => isset($values['species']["$i"]['name']) ? $values['species']["$i"]['name'] : NULL,
        );
        
        // Spreadsheet upload for Species $i.
        $form['species']["$i"]['spreadsheet'] = array(
          '#type' => 'managed_file',
          '#title' => t("Species $i Spreadsheet: please provide a spreadsheet with columns for the Tree ID and location of trees used in this study: *"),
          '#upload_location' => "public://gttn_tpps_accession",
          '#upload_validators' => array(
            'file_validate_extensions' => array('txt csv xlsx'),
          ),
          '#description' => $file_description,
          '#default_value' => isset($values['species']["$i"]['spreadsheet']) ? $values['species']["$i"]['spreadsheet'] : NULL,
        );
        
        // Empty field specification.
        $form['species']["$i"]['spreadsheet']['empty'] = array(
          '#default_value' => isset($values['species']["$i"]['spreadsheet']['empty']) ? $values['species']["$i"]['spreadsheet']['empty'] : 'NA',
        );
        
        // "Define Data" section.
        $form['species']["$i"]['spreadsheet']['columns'] = array(
          '#description' => 'Please define which columns hold the required data: Tree Identifier and Location',
        );
        
        // We want to give users the option to use either lat/long coordinates,
        // or country/state/county/district locations.
        $column_options = array(
          '0' => 'N/A',
          '1' => 'Tree Identifier',
          '2' => 'Country',
          '3' => 'State',
          '8' => 'County',
          '9' => 'District',
          '4' => 'Latitude',
          '5' => 'Longitude',
        );
        
        // This field gets checked later by gttn_tpps_managed_file_process().
        $form['species']["$i"]['spreadsheet']['columns-options'] = array(
          '#type' => 'hidden',
          '#value' => $column_options,
        );
        
        // Placeholder for no-header option.
        $form['species']["$i"]['spreadsheet']['no-header'] = array();

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