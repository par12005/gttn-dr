<?php

/**
 * @file
 * Load the page 4 ajax functions.
 */

require_once 'page_4_ajax.php';
// Load the page 4 helper functions.

/**
 * Populates the form element for the fourth page of the form.
 *
 * @param array $form
 *   The form element to be populated.
 * @param array $form_state
 *   The form state associated with the form element to be populated.
 *
 * @return array The populated form element.
 */
function page_4_create_form(&$form, &$form_state) {
  // Load saved values for the fourth page if they are available.
  $values = $form_state['saved_values'][GTTN_PAGE_4] ?? array();

  $types = $form_state['saved_values'][GTTN_PAGE_1]['data_type'];

  if (!empty($types['DART Reference Data'])) {
    $dart_file_upload_location = 'public://' . variable_get('gttn_tpps_dart_files_dir', 'gttn_tpps_dart');
    $dart_raw_upload_location = 'public://' . variable_get('gttn_tpps_dart_raw_dir', 'gttn_tpps_dart_raw');
    $column_options = array(
      0 => 'N/A',
      1 => 'Analysis lab Name',
      2 => 'Analysis lab Spectra ID',
      3 => 'Internal Sample ID',
      4 => 'Xylarium ID',
      5 => 'Spectra Gatherer',
      6 => 'Type of DART TOFMS',
      7 => 'Parameter Settings',
      8 => 'Calibration Type',
    );

    $required_groups = array(
      'Lab Name' => array(
        'name' => array(1),
      ),
      'Spectra Id' => array(
        'id' => array(2),
      ),
      'Sample Id' => array(
        'internal' => array(3),
        'xylarium' => array(4),
      ),
      'Parameter Settings' => array(
        'settings' => array(7),
      ),
    );

    $form['dart'] = array(
      '#type' => 'fieldset',
      'file' => array(
        '#type' => 'managed_file',
        '#title' => t('DART Reference Metadata File: *'),
        '#upload_location' => $dart_file_upload_location,
        '#upload_validators' => array(
          'file_validate_extensions' => array('txt csv xlsx'),
        ),
        '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: txt csv xlsx</span>',
        '#required_groups' => $required_groups,
        '#gttn_tpps_val' => array(
          'standard' => TRUE,
          'function' => 'gttn_tpps_managed_file_validate',
        ),
        '#standard_name' => 'DART',
        'empty' => array(
          '#default_value' => $values['dart']['file']['empty'] ?? 'NA',
        ),
        'columns' => array(
          '#description' => 'Please define which columns hold the required data',
        ),
        'no-header' => array(),
        'columns-options' => array(
          '#type' => 'hidden',
          '#value' => $column_options,
        ),
      ),
      'raw' => array(
        '#type' => 'managed_file',
        '#title' => t('DART Reference Raw Data File: *'),
        '#upload_location' => $dart_raw_upload_location,
        '#upload_validators' => array(
          'file_validate_extensions' => array('zip gz tar'),
        ),
        '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: zip gz tar</span>',
        '#standard_name' => 'Raw_DART',
        '#gttn_tpps_val' => array(
          'standard' => TRUE,
          'function' => 'gttn_tpps_validate_dart',
        ),
      ),
    );
  }

  if (!empty($types['Isotope Reference Data'])) {
    $isotope_file_upload_location = 'public://' . variable_get('gttn_tpps_iso_files_dir', 'gttn_tpps_isotope');

    $form['isotope'] = array(
      '#type' => 'fieldset',
      '#title' => t('Isotope Reference Data information'),
      '#prefix' => '<div id="gttn_tpps_isotope">',
      '#suffix' => '</div>',
    );

    $form['isotope']['used_core'] = array(
      '#type' => 'checkbox',
      '#title' => t('Increment core was used for sampling'),
      '#ajax' => array(
        'callback' => 'gttn_tpps_isotope_callback',
        'wrapper' => 'gttn_tpps_isotope'
      ),
    );

    $core_used = gttn_tpps_get_ajax_value($form_state, array('isotope', 'used_core'));
    if ($core_used) {
      $form['isotope']['core_len'] = array(
        '#type' => 'textfield',
        '#title' => t('Length of Increment core'),
      );
    }

    $isotopes = array(
      '13C' => '13C',
      '18O' => '18O',
      '15N' => '15N',
      '34S' => '34S',
      '87Sr' => '87Sr',
      'DH' => 'DH',
    );

    $form['isotope']['used'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Isotope(s) used'),
      '#options' => $isotopes,
      '#ajax' => array(
        'callback' => 'gttn_tpps_isotope_callback',
        'wrapper' => 'gttn_tpps_isotope'
      ),
    );

    $column_options = array(
      0 => 'N/A',
      1 => 'Sample ID',
    );

    $col_index = 2;

    $required_groups = array(
      'Sample ID' => array(
        'id' => array(1),
      ),
    );

    foreach ($isotopes as $iso) {
      $iso_used = gttn_tpps_get_ajax_value($form_state, array(
        'isotope',
        'used',
        $iso,
      ));
      
      if (!empty($iso_used)) {
        $form['isotope'][$iso] = array(
          '#type' => 'fieldset',
          'standard' => array(
            '#type' => 'textfield',
            '#title' => t('@iso Isotope standard', array('@iso' => $iso)),
          ),
          'type' => array(
            '#type' => 'select',
            '#title' => t('@iso Isotope type', array('@iso' => $iso)),
            '#options' => array(
              1 => 'Whole Wood',
              2 => 'Cellulose',
            ),
          ),
        );
        $column_options[$col_index] = $iso;
        $required_groups[$iso] = array(
          $iso => array($col_index),
        );
        $col_index++;
      }
    }

    $form['isotope']['file'] = array(
      '#type' => 'managed_file',
      '#title' => t('Isotope Reference Data File: *'),
      '#upload_location' => $isotope_file_upload_location,
      '#upload_validators' => array(
        'file_validate_extensions' => array('txt csv xlsx'),
      ),
      '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: txt csv xlsx</span>',
      '#standard_name' => 'Isotope',
      'empty' => array(
        '#default_value' => $values['isotope']['file']['empty'] ?? 'NA',
      ),

      '#required_groups' => $required_groups,
      '#gttn_tpps_val' => array(
        'standard' => TRUE,
        'function' => 'gttn_tpps_managed_file_validate',
      ),
      'columns' => array(
        '#description' => 'Please define which columns hold the required data',
      ),
      'no-header' => array(),
      'columns-options' => array(
        '#type' => 'hidden',
        '#value' => $column_options,
      ),
    );
  }

  if (!empty($types['Genetic Reference Data'])) {
    $genotype_upload_location = 'public://' . variable_get('gttn_tpps_genotype_files_dir', 'gttn_tpps_genotype');

    $form['genetic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Genetic Reference Data information'),
      '#prefix' => '<div id="gttn-tpps-genetic">',
      '#suffix' => '</div>',
    );

    $marker_types = array(
      'SNPs' => 'SNPs',
      'SSRs/cpSSRs' => 'SSRs/cpSSRs',
      'Other' => 'Other',
    );

    $form['genetic']['marker'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Marker type(s): *'),
      '#options' => $marker_types,
      '#ajax' => array(
        'callback' => 'gttn_tpps_genetic_callback',
        'wrapper' => 'gttn-tpps-genetic',
      ),
    );

    $markers = gttn_tpps_get_ajax_value($form_state, array('genetic', 'marker'));
    if (!empty($markers['Other'])) {
      $form['genetic']['other-marker'] = array(
        '#type' => 'textfield',
        '#title' => t('Other marker type: *'),
      );
    }

    if (!empty($markers['SNPs'])) {
      $snps_source = array(
        0 => '- Select -',
        'GBS' => 'GBS',
        'Reference Genome' => 'Reference Genome',
        'Transcriptome' => 'Transcriptome',
        'Assay' => 'Assay',
      );

      $form['genetic']['snps_source'] = array(
        '#type' => 'select',
        '#title' => t('Source of SNPs: *'),
        '#options' => $snps_source,
        '#ajax' => array(
          'callback' => 'gttn_tpps_genetic_callback',
          'wrapper' => 'gttn-tpps-genetic',
        ),
      );

      $snps_source_val = gttn_tpps_get_ajax_value($form_state, array('genetic', 'snps_source'));
      if ($snps_source_val == 'GBS') {
        $form['genetic']['gbs_type'] = array(
          '#type' => 'select',
          '#title' => t('GBS Type: *'),
          '#options' => array(
            0 => '- Select -',
            'ddRAD' => 'ddRad',
            'RAD' => 'RAD',
            'NextRad' => 'NextRad',
            'Other' => 'Other',
          ),
          '#ajax' => array(
            'callback' => 'gttn_tpps_genetic_callback',
            'wrapper' => 'gttn-tpps-genetic',
          ),
        );
        
        $gbs_type = gttn_tpps_get_ajax_value($form_state, array('genetic', 'gbs_type'));
        if ($gbs_type == 'Other') {
          $form['genetic']['other_gbs'] = array(
            '#type' => 'textfield',
            '#title' => t('Other GBS Type: *'),
          );
        }

        $form['genetic']['gbs_reference'] = array(
          '#type' => 'managed_file',
          '#title' => t('Intermediate Reference File: *'),
          '#upload_location' => $genotype_upload_location,
          '#upload_validators' => array(
            'file_validate_extensions' => array(),
          ),
          '#standard_name' => 'GBS_Reference',
        );

        $form['genetic']['gbs_align'] = array(
          '#type' => 'managed_file',
          '#title' => t('GBS Alignment File: *'),
          '#upload_location' => $genotype_upload_location,
          '#upload_validators' => array(
            'file_validate_extensions' => array('txt csv xlsx'),
          ),
          '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: txt csv xlsx</span>',
          '#standard_name' => 'GBS_Alignment',
        );

        $form['genetic']['vcf'] = array(
          '#type' => 'managed_file',
          '#title' => t('VCF File: *'),
          '#upload_location' => $genotype_upload_location,
          '#upload_validators' => array(
            'file_validate_extensions' => array('vcf'),
          ),
          '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: vcf</span>',
          '#standard_name' => 'VCF',
        );
      }

      if ($snps_source_val == 'Assay') {
        $form['genetic']['assay_type'] = array(
          '#type' => 'select',
          '#title' => t('Assay Type: *'),
          '#options' => array(
            0 => '- Select -',
            'MassArray' => 'MassArray',
            'Illumina' => 'Illumina',
            'Thermo' => 'Thermo',
          ),
        );

        $form['genetic']['assay_design_file'] = array(
          '#type' => 'managed_file',
          '#title' => t('Assay Design File: *'),
          '#upload_location' => $genotype_upload_location,
          '#upload_validators' => array(
            'file_validate_extensions' => array('txt csv xlsx'),
          ),
          '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: txt csv xlsx</span>',
          '#standard_name' => 'Assay_Design',
        );

        $form['genetic']['assay_genotype_table'] = array(
          '#type' => 'managed_file',
          '#title' => t('Assay Genotype Table: *'),
          '#upload_location' => $genotype_upload_location,
          '#upload_validators' => array(
            'file_validate_extensions' => array('txt csv xlsx'),
          ),
          '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: txt csv xlsx</span>',
          '#standard_name' => 'Assay_Genotype_Table',
        );
      }
    }

    if (!empty($markers['SSRs/cpSSRs'])) {
      $form['genetic']['ssr_machine'] = array(
        '#type' => 'textfield',
        '#title' => t('SSR Machine: *'),
      );
    }

    $form['genetic']['quality'] = array(
      '#type' => 'textfield',
      '#title' => t('DNA Quality'),
      '#gttn_tpps_val' => array(),
    );

    $form['genetic']['location'] = array(
      '#type' => 'textfield',
      '#title' => t('DNA Storage Location'),
      '#gttn_tpps_val' => array(),
    );
  }

  if (!empty($types['Anatomical Reference Data'])) {
    $form['anatomic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Anatomical Reference Data information'),
      // TODO
    );
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
    '#value' => t('Review Information and Submit'),
  );

  return $form;
}
