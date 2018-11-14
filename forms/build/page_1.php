<?php
// Load page 1 helper functions.
require_once 'page_1_helper.php';

/**
 * Populates the form element for the first page of the form. This page and any 
 * subsequent pages are accessible to only authenticated users.
 * 
 * @param array $form The form element to be populated.
 * @param array $form_state The form state associated with the form to be populated.
 * @return array The populated form element.
 */
function page_1_create_form(&$form, $form_state){
    
    // Load saved values for the first page if they are available.
    if (isset($form_state['saved_values'][GTTN_PAGE_1])){
        $values = $form_state['saved_values'][GTTN_PAGE_1];
    }
    else{
        $values = array();
    }
    
    // Create the organism field with the organism helper function.
    organism($form, $values);
    
    // Create the data type drop-down menu.
    $form['dataType'] = array(
      '#type' => 'select',
      '#title' => t('Data Type: *'),
      '#options' => array(
        0 => '- Select -',
        'Genotype' => 'Genotype',
        'Phenotype' => 'Phenotype',
        'Genotype x Phenotype' => 'Genotype x Phenotype',
      ),
    );
    
    // Create the save information button.
    $form['Save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#prefix' => '<div class="input-description">* : Required Field</div>',
    );
    
    // Create the next button.
    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );
    
    return $form;
}
