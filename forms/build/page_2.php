<?php

function page_2_form(&$form, $form_state){
    
    if (isset($form_state['saved_values']['second_page'])){
        $values = $form_state['saved_values']['second_page'];
    }
    else{
        $values = array();
    }
    
    $species_number = $form_state['saved_values']['first_page']['species']['number'];
    
    $form['data_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t("Data Types: (select all that apply)"),
      '#options' => drupal_map_assoc(array(
        t('Genotypic'),
        t('Phenotypic')
      )),
    );
    
    $form['data_types']['Genotypic']['#default_value'] = isset($values['data_types']['Genotypic']) ? $values ['data_types']['Genotypic'] : NULL;
    $form['data_types']['Phenotypic']['#default_value'] = isset($values['data_types']['Phenotypic']) ? $values ['data_types']['Phenotypic'] : NULL;
    
    for ($i = 1; $i <= $species_number; $i++){
        
        $species_name = $form_state['saved_values']['first_page']['species'][$i]['name'];
        
        $form["species-$i"] = array(
          '#type' => 'fieldset',
          '#title' => t("$species_name Data:"),
          '#tree' => TRUE,
        );
        
        $form["species-$i"]['sample_date'] = array(
          '#type' => 'fieldset',
          '#title' => t("Sampling Date:"),
          '#tree' => TRUE,
        );
        
        $yearArr = array();
        $yearArr[0] = '- Select -';
        for ($j = 1950; $j <= 2017; $j++) {
            $index = $j - 1949;
            $yearArr[$index] = $j;
        }
        
        $form["species-$i"]['sample_date']['year'] = array(
          '#type' => 'select',
          '#title' => t('Year:'),
          '#options' => $yearArr,
          '#default_value' => isset($values["species-$i"]['sample_date']['year']) ? $values["species-$i"]['sample_date']['year'] : 0,
          '#prefix' => '<div class="container-inline">',
        );
        
        $form["species-$i"]['sample_date']['month'] = array(
          '#type' => 'select',
          '#title' => t('Month:'),
          '#options' => array(
            0 => '- Select -',
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
          ),
          '#default_value' => isset($values["species-$i"]['sample_date']['month']) ? $values["species-$i"]['sample_date']['month'] : 0,
          '#suffix' => '</div>',
          '#states' => array(
            'invisible' => array(
              ':input[name="species-' . $i . '[sample_date][year]"]' => array('value' => '0')
            )
          )
        );
        
        $form["species-$i"]['analysis_date'] = array(
          '#type' => 'fieldset',
          '#title' => t("Analysis Date:"),
          '#tree' => TRUE,
        );
        
        $form["species-$i"]['analysis_date']['year'] = array(
          '#type' => 'select',
          '#title' => t('Year:'),
          '#options' => $yearArr,
          '#default_value' => isset($values["species-$i"]['analysis_date']['year']) ? $values["species-$i"]['analysis_date']['year'] : 0,
          '#prefix' => '<div class="container-inline">',
        );
        
        $form["species-$i"]['analysis_date']['month'] = array(
          '#type' => 'select',
          '#title' => t('Month:'),
          '#options' => array(
            0 => '- Select -',
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
          ),
          '#default_value' => isset($values["species-$i"]['analysis_date']['month']) ? $values["species-$i"]['analysis_date']['month'] : 0,
          '#suffix' => '</div>',
          '#states' => array(
            'invisible' => array(
              ':input[name="species-' . $i . '[analysis_date][year]"]' => array('value' => '0')
            )
          )
        );
    }
    
    $form['genotype'] = array(
      '#type' => 'fieldset',
      '#title' => t('Genotypic Information:'),
      '#tree' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="data_types[Genotypic]"]' => array('checked' => TRUE)
        )
      )
    );
    
    $form['genotype']['type'] = array(
      '#type' => 'select',
      '#title' => t('Genotype Marker Type:'),
      '#options' => array(
        0 => '- Select -',
        1 => 'SNPs',
        2 => 'SSRs',
      ),
      '#default_value' => isset($values['genotype']['type']) ? $values['genotype']['type'] : 0,
    );
    
    $form['genotype']['SNPs-type'] = array(
      '#type' => 'select',
      '#title' => t('SNPs type:'),
      '#options' => array(
        0 => '- Select -',
        1 => 'Resequencing',
        2 => 'Assay/Array'
      ),
      '#default_value' => isset($values['genotype']['SNPs-type']) ? $values['genotype']['SNPs-type'] : 0,
      '#states' => array(
        'visible' => array(
          ':input[name="genotype[type]"]' => array('value' => '1')
        )
      )
    );
    
    $form['genotype']['spreadsheet'] = array(
      '#type' => 'managed_file',
      '#title' => t('Genotype Spreadsheet:'),
      '#upload_location' => 'public://',
      '#upload_validators' => array(
        'file_validate_extensions' => array('txt csv xlsx'),
      ),
      '#default_value' => isset($values['genotype']['spreadsheet']) ? $values['genotype']['spreadsheet'] : NULL,
      '#description' => 'Columns with Identifier, probe sequence 1, probe sequence 2, and alleles are required.',
      '#states' => array(
        'invisible' => array(
          ':input[name="genotype[SNPs-type]"]' => array('value' => '1'),
          ':input[name="genotype[type]"]' => array('value' => '1')
        )
      )
    );
    
    $form['genotype']['vcf'] = array(
      '#type' => 'managed_file',
      '#title' => t('Genotype VCF File:'),
      '#upload_location' => 'public://',
      '#upload_validators' => array(
        'file_validate_extensions' => array('vcf'),
      ),
      '#default_value' => isset($values['genotype']['vcf']) ? $values['genotype']['vcf'] : NULL,
      '#states' => array(
        'visible' => array(
          ':input[name="genotype[SNPs-type]"]' => array('value' => '1'),
          ':input[name="genotype[type]"]' => array('value' => '1')
        )
      )
    );
    
    $form['phenotype'] = array(
      '#type' => 'fieldset',
      '#title' => t('Phenotypic Information:'),
      '#tree' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="data_types[Phenotypic]"]' => array('checked' => TRUE)
        )
      )
    );
    
    $form['phenotype']['type'] = array(
      '#type' => 'select',
      '#title' => t('Phenotype type:'),
      '#options' => array(
        0 => '- Select -',
        1 => 'Isotopes',
        2 => 'Other'
      ),
      '#default_value' => isset($values['phenotype']['type']) ? $values['phenotype']['type'] : 0,
    );
    
    $form['phenotype']['isotope'] = array(
      '#type' => 'managed_file',
      '#title' => t('Isotopes File:'),
      '#upload_location' => 'public://',
      '#upload_validators' => array(
        'file_validate_extensions' => array('txt csv xlsx'),
      ),
      '#default_value' => isset($values['phenotype']['isotope']) ? $values['phenotype']['isotope'] : NULL,
      '#desription' => t('Please provide a file with the TreeID and the names of the isotopes as the columns.'),
      '#states' => array(
        'visible' => array(
          ':input[name="phenotype[type]"]' => array('value' => '1')
        )
      )
    );
    
    $form['phenotype']['other'] = array(
      '#type' => 'managed_file',
      '#title' => t('Phenotype File:'),
      '#upload_location' => 'public://',
      '#default_value' => isset($values['phenotype']['other']) ? $values['phenotype']['other'] : NULL,
      '#states' => array(
        'visible' => array(
          ':input[name="phenotype[type]"]' => array('value' => '2')
        )
      )
    );
    
    $form['Back'] = array(
      '#type' => 'submit',
      '#value' => 'Back',
    );
    
    $form['Next'] = array(
      '#type' => 'submit',
      '#value' => 'Next',
    );
    
    return $form;
}
