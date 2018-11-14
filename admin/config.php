<?php

function gttn_tpps_admin_settings($form, &$form_state){
    
    $authors = variable_get('gttn_tpps_author_files_dir', 'gttn_tpps_authors');
    $accession = variable_get('gttn_tpps_accession_files_dir', 'gttn_tpps_accession');
    $genotype = variable_get('gttn_tpps_genotype_files_dir', 'gttn_tpps_genotype');
    $phenotype = variable_get('gttn_tpps_phenotype_files_dir', 'gttn_tpps_phenotype');
    $cartogratree_env = variable_get('gttn_tpps_cartogratree_env', FALSE);
    
    $form['gttn_tpps_admin_email'] = array(
      '#type' => 'textfield',
      '#title' => t('TPPS Admin Email Address'),
      '#default_value' => variable_get('gttn_tpps_admin_email', 'treegenesdb@gmail.com'),
    );
    
    $form['gttn_tpps_genotype_group'] = array(
      '#type' => 'textfield',
      '#title' => t('TPPS Genotype max group'),
      '#default_value' => variable_get('gttn_tpps_genotype_group', 10000),
      '#description' => 'Some genotype files are very large. GTNN-TPPS tries to submit as many genotype entries together as possible, in order to speed up the process of writing genotype data to the database. However, very large size entries can cause errors within the Tripal Job daemon. This number is the maximum number of genotype entries that may be submitted at once. Larger numbers will make the process faster, but are more likely to cause errors. Defaults to 10,000.',
    );
    
    $form['gttn_tpps_local_genome_dir'] = array(
      '#type' => 'textfield',
      '#title' => t('Reference Genome file directory:'),
      '#default_value' => variable_get('gttn_tpps_local_genome_dir', NULL),
      '#description' => 'The directory of local genome files on your web server. If left blank, gttn_tpps will skip the searching for local genomes step in the gttn_tpps genotype section. Local genome files should be organized according to the following structure: <br>[file directory]/[species code]/[version number]/[genome data] where: <br>&emsp;&emsp;[file directory] is the full path to the genome files provided above <br>&emsp;&emsp;[species code] is the 4-letter standard species code - this must match the species code entry in the "chado.organismprop" table<br>&emsp;&emsp;[version number] is the reference genome version, of the format "v#.#"<br>&emsp;&emsp;[genome data] is the actual reference genome files - these can be any format or structure<br>More information is available <a href="https://gttn-tpps.rtfd.io/en/latest/config.html" target="blank">here</a>.',
    );
    
    $form['gttn_tpps_author_files_dir'] = array(
      '#type' => 'textfield', 
      '#title' => t('Author files:'),
      '#default_value' => $authors,
      '#description' => t("Currently points to " . drupal_realpath("public://$authors") . '.'),
      '#prefix' => t('<h1>File Upload locations</h1>' . 'All file locations are relative to the "public://" file stream. Your current "public://" file stream points to "' . drupal_realpath('public://') . '".<br><br>')
    );
    
    $form['gttn_tpps_accession_files_dir'] = array(
      '#type' => 'textfield', 
      '#title' => t('Tree Accession files:'),
      '#default_value' => $accession,
      '#description' => t("Currently points to " . drupal_realpath("public://$accession") . '.')
    );
    
    $form['gttn_tpps_genotype_files_dir'] = array(
      '#type' => 'textfield', 
      '#title' => t('Genotype files:'),
      '#default_value' => $genotype,
      '#description' => t("Currently points to " . drupal_realpath("public://$genotype") . '.')
    );
    
    $form['gttn_tpps_phenotype_files_dir'] = array(
      '#type' => 'textfield',
      '#title' => t('Phenotype files:'),
      '#default_value' => $phenotype,
      '#description' => t("Currently points to " . drupal_realpath("public://$phenotype") . '.')
    );
    
    return system_settings_form($form);
}

function gttn_tpps_admin_settings_validate($form, &$form_state){
    foreach ($form_state['values'] as $key => $value){
        if (substr($key, -10) == '_files_dir'){
            $location = "public://$value";
            if (!file_prepare_directory($location, FILE_CREATE_DIRECTORY)){
                form_set_error("$key", "Error: path must be valid and current user must have permissions to access that path.");
            }
        }
        elseif ($key == 'gttn_tpps_admin_email'){
            if (!valid_email_address($value)){
                form_set_error("$key", "Error: please enter a valid email address.");
            }
        }
    }
}
