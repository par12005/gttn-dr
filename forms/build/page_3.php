<?php

/**
 * Populates the form element for the third page of the form.
 * 
 * @param array $form The form element to be populated.
 * @param array $form_state The form state associated with the form element to be populated.
 * @return array The populated form element.
 */
function page_3_create_form(&$form, &$form_state){
    // Load saved values for the third page if they are available.
    if (isset($form_state['saved_values'][GTTN_PAGE_3])){
        $values = $form_state['saved_values'][GTTN_PAGE_3];
    }
    else{
        $values = array();
    }
    
    // Create the tree accession set of fields.
    $form['tree-accession'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
    );
    
    // Get the species number from the previous page of the form.
    $species_number = $form_state['saved_values'][GTTN_PAGE_1]['organism']['number'];
    // If the user has more than one species, allow them to choose between uploading
    // one file with every species, or a separate file for each species. 
    if ($species_number > 1){
        // Create the single/multiple file checkbox.
        $form['tree-accession']['check'] = array(
          '#type' => 'checkbox',
          '#title' => t('I would like to upload a separate tree accession file for each species.'),
        );
    }
    
    // Field description for tree accesison file upload fields.
    $file_description = "Please upload a spreadsheet file containing tree popula"
        . "tion data. When your file is uploaded, you will be shown a table with"
        . " your column header names, several drop-downs, and the first few rows"
        . " of your file. You will be asked to define the data type for each col"
        . "umn, using the drop-downs provided to you. If a column data type does"
        . " not fit any of the options in the drop-down menu, you may omit that "
        . "drop-down menu. Your file must contain columns with information about"
        . " at least the Tree Identifier and the Location of the tree (either gp"
        . "s coordinates or country/state).";
    // Get the upload location from the GTTN-TPPS settings. If the settings, are
    // not available, then default to "public://gttn_tpps_accession".
    $file_upload_location = 'public://' . variable_get('gttn_tpps_accession_files_dir', 'gttn_tpps_accession');
    
    // Create the single tree accession file upload.
    $form['tree-accession']['file'] = array(
      '#type' => 'managed_file',
      '#title' => t("Tree Accession File: please provide a spreadsheet with columns for the Tree ID and location of trees used in this study: *"),
      '#upload_location' => "$file_upload_location",
      '#upload_validators' => array(
        'file_validate_extensions' => array('txt csv xlsx'),
      ),
      '#description' => $file_description,
      // Only show the single-species upload if the user is only uploading one file.
      '#states' => ($species_number > 1) ? (array(
        'visible' => array(
          ':input[name="tree-accession[check]"]' => array('checked' => FALSE),
        )
      )) : NULL,
    );
    
    // If the user has more than one species, then let them know that they can 
    // choose between a single or multiple accession files.
    if ($species_number > 1){
        $form['tree-accession']['file']['#description'] .= " If you are uploading a single file with multiple species, your file must also specify the genus and species of each tree.";
    }
    
    // Initialize placeholder for empty value field and assign its default value.
    $form['tree-accession']['file']['empty'] = array(
      '#default_value' => isset($values['tree-accession']['file']['empty']) ? $values['tree-accession']['file']['empty'] : 'NA',
    );
    
    // Initialize placeholder for data definition section and assign its description.
    $form['tree-accession']['file']['columns'] = array(
      '#description' => 'Please define which columns hold the required data: Tree Identifier and Location',
    );
    
    // Possible options for the tree accession upload.
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
    
    // If the user has more than one species, then additional information will be
    // required in the single-species tree accession file.
    if ($species_number > 1){
        $column_options['6'] = 'Genus';
        $column_options['7'] = 'Species';
        $column_options['10'] = 'Genus + Species';
    }
    
    // Add the hidden column-options field so that it can be referenced by the 
    // gttn_tpps_managed_file_process() function.
    $form['tree-accession']['file']['columns-options'] = array(
      '#type' => 'hidden',
      '#value' => $column_options,
    );
    
    // Add a placeholder for the no-header field.
    $form['tree-accession']['file']['no-header'] = array();
    
    // Create the coordinates projection drop-down menu.
    $form['tree-accession']['coord-format'] = array(
      '#type' => 'select',
      '#title' => t('Coordinate Projection'),
      '#options' => array(
        'WGS 84',
        'NAD 83',
        'ETRS 89',
        'Other Coordinate Projection',
        'My file does not use coordinates for tree locations'
      ),
      '#states' => $form['tree-accession']['file']['#states'],
      // Add map button after coordinate format option.
      '#suffix' => "<div id=\"map_wrapper\"></div>"
        . "<input id=\"map_button\" type=\"button\" value=\"Click here to view trees on map!\"></input>"
    );
    
    // Add the google maps api call after the map button.
    $form['tree-accession']['coord-format']['#suffix'] .= '
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDkeQ6KN6HEBxrIoiSCrCHFhIbipycqouY&callback=initMap"
    async defer></script>
    <style>
      #map_wrapper {
        height: 450px;
      }
    </style>';
    
    if ($species_number > 1){

        // Create the additional file upload fields.
        for ($i = 1; $i <= $species_number; $i++){
            // Get the organism name from the previous page.
            $name = $form_state['saved_values'][GTTN_PAGE_1]['organism']["$i"];
            
            // Create the tree accession set of fields for organism $i.
            $form['tree-accession']["species-$i"] = array(
              '#type' => 'fieldset',
              '#title' => t("<div class=\"fieldset-title\">Tree Accession information for $name trees:</div>"),
              // Only show these fields if the user says they would like to upload
              // multiple tree accession files.
              '#states' => array(
                'visible' => array(
                  ':input[name="tree-accession[check]"]' => array('checked' => TRUE),
                )
              ),
              '#collapsible' => TRUE,
            );
            
            // Create the file uplaod for organism $i.
            $form['tree-accession']["species-$i"]['file'] = array(
              '#type' => 'managed_file',
              '#title' => t("$name Accession File: please provide a spreadsheet with columns for the Tree ID and location of the $name trees used in this study: *"),
              '#upload_location' => "$file_upload_location",
              '#upload_validators' => array(
                'file_validate_extensions' => array('txt csv xlsx'),
              ),
              '#description' => $file_description,
              '#tree' => TRUE
            );
            
            // Initialize placeholder for empty value field and assign its default value.
            $form['tree-accession']["species-$i"]['file']['empty'] = array(
              '#default_value' => isset($values['tree-accession']["species-$i"]['file']['empty']) ? $values['tree-accession']["species-$i"]['file']['empty'] : 'NA',
            );
            
            // Initialize placeholder for data definition section and assign its description.
            $form['tree-accession']["species-$i"]['file']['columns'] = array(
              '#description' => 'Please define which columns hold the required data: Tree Identifier and Location',
            );
            
            // Possible options for the tree accession upload.
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
            
            // Add the hidden column-options field so that it can be referenced by the 
            // gttn_tpps_managed_file_process() function.
            $form['tree-accession']["species-$i"]['file']['columns-options'] = array(
              '#type' => 'hidden',
              '#value' => $column_options,
            );
            
            // Add a placeholder for the no-header field.
            $form['tree-accession']["species-$i"]['file']['no-header'] = array();
            
            $parts = explode(" ", $name);
            $id_name = implode("_", $parts);
            $form['tree-accession']["species-$i"]['#suffix'] = "<div id=\"{$id_name}_map_wrapper\"></div>"
                . "<input id=\"{$id_name}_map_button\" type=\"button\" value=\"Click here to view $name trees on map!\"></input>"
                . "<div id=\"{$id_name}_species_number\" style=\"display:none;\">$i</div>";
        }
    }
    
    // Create the back button.
    $form['Back'] = array(
      '#type' => 'submit',
      '#value' => t('Back'),
      '#prefix' => '<div class="input-description">* : Required Field</div>',
    );
    
    // Create the save information button.
    $form['Save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    
    // Create the next button.
    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );
    
    return $form;
}
