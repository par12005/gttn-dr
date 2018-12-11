<?php

// Load the page 4 ajax functions.
require_once 'page_4_ajax.php';
// Load the page 4 helper functions.
require_once 'page_4_helper.php';

/**
 * Populates the form element for the fourth page of the form.
 * 
 * @param array $form The form element to be populated.
 * @param array $form_state The form state associated with the form element to be populated.
 * @return array The populated form element.
 */
function page_4_create_form(&$form, &$form_state){
    // Load saved values for the fourth page if they are available.
    if (isset($form_state['saved_values'][GTTN_PAGE_4])){
        $values = $form_state['saved_values'][GTTN_PAGE_4];
    }
    else{
        $values = array();
    }
    
    // Load the upload locations for genotype and phenotype files from the
    // GTTN-TPPS admin settings in the database.
    $genotype_upload_location = 'public://' . variable_get('gttn_tpps_genotype_files_dir', 'gttn_tpps_genotype');
    $phenotype_upload_location = 'public://' . variable_get('gttn_tpps_phenotype_files_dir', 'gttn_tpps_phenotype');
    
    // Ensure that the whole form allows collections of elements.
    $form['#tree'] = TRUE;
    
    // Get the number of species from the first page.
    $organism_number = $form_state['saved_values'][GTTN_PAGE_1]['organism']['number'];
    // Get the submission data types from the first pages.
    $data_type = $form_state['saved_values'][GTTN_PAGE_1]['dataType'];
    // Iterate through each organism.
    for ($i = 1; $i <= $organism_number; $i++){
        
        // Get the organism name from the first page.
        $name = $form_state['saved_values'][GTTN_PAGE_1]['organism']["$i"];
        
        // Create the set of fields for genotype and phenotype information about
        // organism $i.
        $form["organism-$i"] = array(
          '#type' => 'fieldset',
          '#title' => t("<div class=\"fieldset-title\">$name:</div>"),
          '#tree' => TRUE,
          '#collapsible' => TRUE
        );
        
        // If the selected data type contains 'P', then one of the options including
        // phenotype was seleceted, and the phenotype fields need to be created.
        if (preg_match('/P/', $data_type)){
            // If we are not on the first organism, then the user can choose to
            // reuse the phenotype information from the last organism.
            if ($i > 1){
                // Create reuse phenotype information section.
                $form["organism-$i"]['phenotype-repeat-check'] = array(
                  '#type' => 'checkbox',
                  '#title' => "Phenotype information for $name is the same as phenotype information for {$form_state['saved_values'][GTTN_PAGE_1]['organism'][$i - 1]}.",
                  '#default_value' => isset($values["organism-$i"]['phenotype-repeat-check']) ? $values["organism-$i"]['phenotype-repeat-check'] : 1,
                );
            }
            
            // Create the phenotype information set of fields.
            $form["organism-$i"]['phenotype'] = array(
              '#type' => 'fieldset',
              '#title' => t('<div class="fieldset-title">Phenotype Information:</div>'),
              '#tree' => TRUE,
              '#prefix' => "<div id=\"phenotypes-organism-$i\">",
              '#suffix' => '</div>',
              '#collapsible' => TRUE,
            );
            
            // If we are not on the first organism, the phenotype fields should
            // only be shown if the information is not being reused from the
            // previous organism.
            if ($i > 1){
                $form["organism-$i"]['phenotype']['#states'] = array(
                  'invisible' => array(
                    ":input[name=\"organism-$i\[phenotype-repeat-check]\"]" => array('checked' => TRUE)
                  )
                );
            }
            
            $form["organism-$i"]['phenotype']['type'] = array(
              '#type' => 'select',
              '#title' => t('Phenotype type: Please select the correct type of phenotype data you are submitting: *'),
              '#options' => array(
                0 => '- Select -',
                1 => 'Mass Pyrolysis',
                2 => 'Isotope'
              )
            );
            
            // Create the phenotype file upload field.
            $form["organism-$i"]['phenotype']['file'] = array(
              '#type' => 'managed_file',
              '#title' => t('Phenotype file: Please upload a file containing columns for Tree Identifier and all of your isotope data: *'),
              '#upload_location' => "$phenotype_upload_location",
              '#upload_validators' => array(
                'file_validate_extensions' => array('csv tsv xlsx')
              ),
              '#tree' => TRUE,
            );
            
            // Initialize placeholder for empty value field and assign its default value.
            $form["organism-$i"]['phenotype']['file']['empty'] = array(
              '#default_value' => isset($values["organism-$i"]['phenotype']['file']['empty']) ? $values["organism-$i"]['phenotype']['file']['empty'] : 'NA'
            );

            // Initialize placeholder for data definition section and assign its description.
            $form["organism-$i"]['phenotype']['file']['columns'] = array(
              '#description' => 'Please define which columns hold the required data: Tree Identifier and Isotope Data',
            );

            // Possible column options for the phenotype file upload.
            $column_options = array(
              'Isotope',
              'Tree Identifier',
              'N/A',
            );

            // Add the hidden column-options field so that it can be referenced by the 
            // gttn_tpps_managed_file_process() function.
            $form["organism-$i"]['phenotype']['file']['columns-options'] = array(
              '#type' => 'hidden',
              '#value' => $column_options,
            );
            
            // Add a placeholder for the no-header field.
            $form["organism-$i"]['phenotype']['file']['no-header'] = array();
        }
        
        // If the selected data type contains 'G', then one of the options including
        // genotype was seleceted, and the genotype fields need to be created.
        if (preg_match('/G/', $data_type)){
            // If we are not on the first organism, then the user can choose to
            // reuse the genotype information from the last organism.
            if ($i > 1){
                $form["organism-$i"]['genotype-repeat-check'] = array(
                  '#type' => 'checkbox',
                  '#title' => "Genotype information for $name is the same as genotype information for {$form_state['saved_values'][GTTN_PAGE_1]['organism'][$i - 1]}.",
                  '#default_value' => isset($values["organism-$i"]['genotype-repeat-check']) ? $values["organism-$i"]['genotype-repeat-check'] : 1,
                );
            }
            
            // Create the genotype fields using the page 4 genotype helper function.
            $form["organism-$i"]['genotype'] = genotype($form, $form_state, $values, "organism-$i", $genotype_upload_location);
            
            // If we are not on the first organism, the genotype fields should
            // only be shown if the information is not being reused from the
            // previous organism.
            if ($i > 1){
                $form["organism-$i"]['genotype']['#states'] = array(
                  'invisible' => array(
                    ":input[name=\"organism-$i\[genotype-repeat-check]\"]" => array('checked' => TRUE)
                  )
                );
            }
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
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Review Information and Submit')
    );
    
    return $form;
}
