<?php

/**
 * @file
 */

/**
 * Creates admin settings form for gttn-tpps.
 *
 * @param array $form
 *   The form to be populated.
 * @param array $form_state
 *   The state of the form to be populated.
 *
 * @return array The populated form.
 */
function gttn_tpps_admin_settings(array $form, array &$form_state) {
  // Get existing variable values.
  $authors = variable_get('gttn_tpps_author_files_dir', 'gttn_tpps_authors');
  $accession = variable_get('gttn_tpps_accession_files_dir', 'gttn_tpps_accession');
  $genotype = variable_get('gttn_tpps_genotype_files_dir', 'gttn_tpps_genotype');
  $phenotype = variable_get('gttn_tpps_phenotype_files_dir', 'gttn_tpps_phenotype');
  $dart = variable_get('gttn_tpps_dart_files_dir', 'gttn_tpps_dart');
  $isotope = variable_get('gttn_tpps_isotope_files_dir', 'gttn_tpps_isotope');

  $form['gttn_tpps_maps_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('GTTN-TPPS Google Maps API Key'),
    '#default_value' => variable_get('gttn_tpps_maps_api_key', NULL),
  );

  $form['gttn_tpps_ncbi_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('GTTN-TPPS NCBI EUtils API Key'),
    '#default_value' => variable_get('gttn_tpps_ncbi_api_key', NULL),
  );

  $form['gttn_tpps_geocode_api_key'] = array(
    '#type' => 'textfield',
    '#title' => t('GTTN-TPPS OpenCage Geocoding API Key'),
    '#default_value' => variable_get('gttn_tpps_geocode_api_key', NULL),
  );

  $form['gttn_tpps_gps_epsilon'] = array(
    '#type' => 'textfield',
    '#title' => t('GTTN-TPPS GPS Epsilon'),
    '#default_value' => variable_get('gttn_tpps_gps_epsilon', .001),
    '#description' => t('This is the amount of error GTTN-TPPS should allow for when trying to match trees. An epsilon value of 1 is around 100km, and an epsilon value of .001 is around 100 m.'),
  );

  // Create the admin email field.
  $form['gttn_tpps_admin_email'] = array(
    '#type' => 'textfield',
    '#title' => t('TPPS Admin Email Address'),
    '#default_value' => variable_get('gttn_tpps_admin_email', 'treegenesdb@gmail.com'),
  );

  // Create the max record group field.
  $form['gttn_tpps_record_group'] = array(
    '#type' => 'textfield',
    '#title' => t('GTTN-TPPS Record max group'),
    '#default_value' => variable_get('gttn_tpps_record_group', 10000),
    '#description' => 'Some files are very large. GTTN-TPPS tries to submit as many entries together as possible, in order to speed up the process of writing data to the database. However, very large size entries can cause errors within the Tripal Job daemon. This number is the maximum number of entries that may be submitted at once. Larger numbers will make the process faster, but are more likely to cause errors. Defaults to 10,000.',
  );

  // Create the Genome file directory field.
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
    '#description' => t("Currently points to @path.", array('@path' => drupal_realpath("public://$authors"))),
    '#prefix' => t('<h1>File Upload locations</h1>All file locations are relative to the "public://" file stream. Your current "public://" file stream points to "@path".<br><br>', array('@path' => drupal_realpath('public://'))),
  );

  // Create the Accession file directory field.
  $form['gttn_tpps_accession_files_dir'] = array(
    '#type' => 'textfield',
    '#title' => t('Tree Accession files:'),
    '#default_value' => $accession,
    '#description' => t("Currently points to @path.", array('@path' => drupal_realpath("public://$accession"))),
  );

  $form['gttn_tpps_dart_files_dir'] = array(
    '#type' => 'textfield',
    '#title' => t('DART files:'),
    '#default_value' => $dart,
    '#description' => t("Currently points to @path.", array('@path' => drupal_realpath("public://$dart"))),
  );

  $form['gttn_tpps_isotope_files_dir'] = array(
    '#type' => 'textfield',
    '#title' => t('Isotope files:'),
    '#default_value' => $isotope,
    '#description' => t("Currently points to @path.", array('@path' => drupal_realpath("public://$isotope"))),
  );

  // Create the genotype file directory field.
  $form['gttn_tpps_genotype_files_dir'] = array(
    '#type' => 'textfield',
    '#title' => t('Genotype files:'),
    '#default_value' => $genotype,
    '#description' => t("Currently points to @path.", array('@path' => drupal_realpath("public://$genotype"))),
  );

  // Create the phenotype file directory field.
  $form['gttn_tpps_phenotype_files_dir'] = array(
    '#type' => 'textfield',
    '#title' => t('Phenotype files:'),
    '#default_value' => $phenotype,
    '#description' => t("Currently points to @path.", array('@path' => drupal_realpath("public://$phenotype"))),
  );

  // Return the form as a system_settings form.
  return system_settings_form($form);
}

/**
 * Implements validation of the gttn_tpps_admin_settings form.
 *
 * @param $form
 *   array The form to be validated.
 * @param $form_state
 *   array The state of the form to be validated.
 */
function gttn_tpps_admin_settings_validate($form, &$form_state) {
  // Iterate through each of the form values.
  foreach ($form_state['values'] as $key => $value) {
    // If the field is a file directory, save the value as a proper file stream.
    if (substr($key, -10) == '_files_dir') {
      $location = "public://$value";
      // If the file stream is invalid or the user does not have permissions to access the path, throw an error.
      if (!file_prepare_directory($location, FILE_CREATE_DIRECTORY)) {
        form_set_error("$key", "Error: path must be valid and current user must have permissions to access that path.");
      }
    }
    // Check that the admin email is actually a valid email address.
    elseif ($key == 'gttn_tpps_admin_email') {
      if (!valid_email_address($value)) {
        form_set_error("$key", "Error: please enter a valid email address.");
      }
    }
  }
}
