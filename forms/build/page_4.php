<?php

/**
 * @file
 * Load the page 4 ajax functions.
 */

require_once 'page_4_ajax.php';

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

    $file_description = "Please upload a spreadsheet file containing DART Reference metadata. When your file is uploaded, you will be shown a table with your column header names, several drop-downs, and the first few rows of your file. You will be asked to define the data type for each column, using the drop-downs provided to you. If a column data type does not fit any of the options in the drop-down menu, you may omit that drop-down menu. Your file must contain columns with information about at least the lab that performed the DART analysis, the lab spectra ID, the sample ID, and the parameter settings of the DART machine.";
    $raw_file_description = "Please upload a .zip, .gz, or .tar archive of your DART Reference Raw Data. This should be a compressed archive containing text files with names that match sample id's that you have provided on the previous page. Each text file should contain the raw DART measurements for the sample it is named after.";

    $image_path = drupal_get_path('module', 'gttn_tpps') . '/images/';
    $file_description .= " Please find an example of a DART Reference metadata file below.<figure id=\"edit-dart-top\"><img width=\"100%\" src=\"/{$image_path}example_dart_top.png\"><figcaption>Example DART Reference Metadata File</figcaption></figure>";
    $raw_file_description .= " Please find an example of a DART Raw Data archive structure and a DART Reference Raw Data file below. If you need more information, please consult the <a target=\"blank\" href=\"https://gttn-tpps.readthedocs.io/en/latest/user/page_3.html#top-level-dart-data-file\">user documentation</a>.<br><figure id=\"gttn-tpps-dart-folder\"><img src=\"/{$image_path}example_dart_folder.png\"><figcaption>Example DART Raw Data Archive</figcaption></figure><figure id=\"gttn-tpps-dart-raw\"><img src=\"/{$image_path}example_dart_raw.png\"><figcaption>Example DART Reference Raw Data File</figcaption></figure>";

    $title = t("DART Reference Metadata File: *") . "<br>$file_description";
    $raw_title = t("DART Reference Raw Data File: *") . "<br>$raw_file_description";

    $form['dart'] = array(
      '#type' => 'fieldset',
      '#title' => t('DART Reference Data'),
      'meta_only' => array(
        '#type' => 'checkbox',
        '#title' => t('I am not providing DART Reference data for this submission.') . '<br><strong>NOTE: </strong>' . t('Selecting this option will classify your submission as an incomplete reference submission. Metadata you upload will still be visible in accordance with your data access settings.'),
        '#ajax' => array(
          'callback' => 'gttn_tpps_dart_callback',
          'wrapper' => 'gttn-tpps-dart',
        ),
      ),
      '#prefix' => '<div id="gttn-tpps-dart">',
      '#suffix' => '</div>',
      '#collapsible' => TRUE,
    );

    $meta_only = gttn_tpps_get_ajax_value($form_state, array('dart', 'meta_only'), FALSE);

    if (!$meta_only) {
      $form['dart']['file'] = array(
        '#type' => 'managed_file',
        '#title' => $title,
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
      );

      $form['dart']['raw'] = array(
        '#type' => 'managed_file',
        '#title' => $raw_title,
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
      );
    }
  }

  if (!empty($types['Isotope Reference Data'])) {
    $isotope_file_upload_location = 'public://' . variable_get('gttn_tpps_iso_files_dir', 'gttn_tpps_isotope');

    $file_description = "Please upload a spreadsheet file containing Isotope Reference data. When your file is uploaded, you will be shown a table with your column header names, several drop-downs, and the first few rows of your file. You will be asked to define the data type for each column, using the drop-downs provided to you. If a column data type does not fit any of the options in the drop-down menu, you may omit that drop-down menu.";

    $image_path = drupal_get_path('module', 'gttn_tpps') . '/images/';

    $title = t("Isotope Reference Data File: *") . "<br>$file_description";

    $form['isotope'] = array(
      '#type' => 'fieldset',
      '#title' => t('Isotope Reference Data information'),
      'meta_only' => array(
        '#type' => 'checkbox',
        '#title' => t('I am not providing Isotope Reference data for this submission.') . '<br><strong>NOTE: </strong>' . t('Selecting this option will classify your submission as an incomplete reference submission. Metadata you upload will still be visible in accordance with your data access settings.'),
        '#ajax' => array(
          'callback' => 'gttn_tpps_isotope_callback',
          'wrapper' => 'gttn-tpps-isotope',
        ),
      ),
      '#prefix' => '<div id="gttn-tpps-isotope">',
      '#suffix' => '</div>',
      '#collapsible' => TRUE,
    );

    $meta_only = gttn_tpps_get_ajax_value($form_state, array('isotope', 'meta_only'), FALSE);

    if (!$meta_only) {
      $form['isotope']['used_core'] = array(
        '#type' => 'checkbox',
        '#title' => t('Increment core was used for sampling'),
        '#ajax' => array(
          'callback' => 'gttn_tpps_isotope_callback',
          'wrapper' => 'gttn-tpps-isotope',
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
          'wrapper' => 'gttn-tpps-isotope',
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

      $form['isotope']['format'] = array(
        '#type' => 'radios',
        '#title' => t('Isotope file format'),
        '#options' => array(
          'type_1' => t('Type 1'),
          'type_2' => t('Type 2'),
        ),
        '#default_value' => $form_state['saved_values'][GTTN_PAGE_4]['isotope']['format'] ?? 'type_1',
        '#description' => t('Please select a file format type from the listed options. Below please see examples of each format type.'),
        '#ajax' => array(
          'callback' => 'gttn_tpps_isotope_callback',
          'wrapper' => 'gttn-tpps-isotope',
        ),
      );

      $form['isotope']['format']['type_1']['#prefix'] = "<figure><img src=\"/{$image_path}example_isotope_1.png\"><figcaption>";
      $form['isotope']['format']['type_1']['#suffix'] = "</figcaption></figure>";
      $form['isotope']['format']['type_2']['#prefix'] = "<figure><img src=\"/{$image_path}example_isotope_2.png\"><figcaption>";
      $form['isotope']['format']['type_2']['#suffix'] = "</figcaption></figure>";

      $format = gttn_tpps_get_ajax_value($form_state, array(
        'isotope',
        'format',
      ), 'type_1');

      if ($format == 'type_1') {
        $title .= " Your file must contain columns with information about at least the sample ID, the Isotope being measured, and the measurement value.";
      }
      if ($format == 'type_2') {
        $column_options = array(
          'N/A',
          'Sample ID',
          'Isotope',
          'Value',
        );
        $required_groups = array(
          'Sample ID' => array(
            'id' => array(1),
          ),
          'Isotope' => array(
            'isotope' => array(2),
          ),
          'Value' => array(
            'value' => array(3),
          ),
        );
        $title .= " Your file must contain columns with information about at least the sample ID and each Isotope you indicated above.";
      }

      $form['isotope']['file'] = array(
        '#type' => 'managed_file',
        '#title' => $title,
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
  }

  if (!empty($types['Genetic Reference Data'])) {
    $genotype_upload_location = 'public://' . variable_get('gttn_tpps_genotype_files_dir', 'gttn_tpps_genotype');

    $form['genetic'] = array(
      '#type' => 'fieldset',
      '#title' => t('Genetic Reference Data information'),
      'meta_only' => array(
        '#type' => 'checkbox',
        '#title' => t('I am not providing Genetic Reference data for this submission.') . '<br><strong>NOTE: </strong>' . t('Selecting this option will classify your submission as an incomplete reference submission. Metadata you upload will still be visible in accordance with your data access settings.'),
        '#ajax' => array(
          'callback' => 'gttn_tpps_genetic_callback',
          'wrapper' => 'gttn-tpps-genetic',
        ),
      ),
      '#prefix' => '<div id="gttn-tpps-genetic">',
      '#suffix' => '</div>',
      '#collapsible' => TRUE,
    );

    $meta_only = gttn_tpps_get_ajax_value($form_state, array('genetic', 'meta_only'), FALSE);

    if (!$meta_only) {
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
        '#description' => t('Please indicate the genetic marker types that your analysis used'),
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

          $form['genetic']['gbs_machine'] = array(
            '#type' => 'textfield',
            '#title' => t('GBS Sequencing Instrument: *'),
          );

          $options = array(
            'key' => 'filename',
            'recurse' => FALSE,
          );

          $genome_dir = variable_get('gttn_tpps_local_genome_dir', NULL);
          $ref_genome_arr = array();
          $ref_genome_arr[0] = '- Select -';

          if ($genome_dir) {
            $results = file_scan_directory($genome_dir, '/^([A-Z][a-z]{3})$/', $options);
            $code_cvterm = chado_get_cvterm(array(
              'name' => 'organism 4 letter code',
              'is_obsolete' => 0,
            ))->cvterm_id;
            foreach ($results as $key => $value) {
              $org_id_query = chado_select_record('organismprop', array('organism_id'), array(
                'value' => $key,
                'type_id' => $code_cvterm,
              ));
              if (!empty($org_id_query)) {
                $org_query = chado_select_record('organism', array('genus', 'species'), array(
                  'organism_id' => current($org_id_query)->organism_id,
                ));
                $result = current($org_query);

                $versions = file_scan_directory("$genome_dir/$key", '/^v([0-9]|.)+$/', $options);
                foreach ($versions as $item) {
                  $opt_string = $result->genus . " " . $result->species . " " . $item->filename;
                  $ref_genome_arr[$opt_string] = $opt_string;
                }
              }
            }
          }

          if (count($ref_genome_arr) > 1) {
            $ref_genome_arr['manual'] = t('I would like to upload my own reference file.');
            $form['genetic']['gbs_reference'] = array(
              '#type' => 'select',
              '#title' => t('GBS Intermediate Reference File: *'),
              '#options' => $ref_genome_arr,
            );
          }

          $form['genetic']['manual_reference'] = array(
            '#type' => 'managed_file',
            '#title' => t('Intermediate Reference File: *'),
            '#upload_location' => $genotype_upload_location,
            '#upload_validators' => array(
              'file_validate_extensions' => array(),
            ),
            '#standard_name' => 'GBS_Reference',
          );

          if (count($ref_genome_arr) > 1) {
            $form['genetic']['manual_reference']['#states'] = array(
              'visible' => array(
                ':input[name="genetic[gbs_reference]"]' => array('value' => 'manual'),
              ),
            );
          }

          $form['genetic']['gbs_align'] = array(
            '#type' => 'managed_file',
            '#title' => t('GBS Alignment File: *'),
            '#upload_location' => $genotype_upload_location,
            '#upload_validators' => array(
              'file_validate_extensions' => array('sam bam'),
            ),
            '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: sam bam</span>',
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
          $form['genetic']['assay_source'] = array(
            '#type' => 'select',
            '#title' => t('Assay Source: *'),
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
          '#title' => t('SSR Sequencing Instrument: *'),
        );

        $form['genetic']['ploidy'] = array(
          '#type' => 'select',
          '#title' => t('Ploidy'),
          '#options' => array(
            0 => '- Select -',
            'Haploid' => 'Haploid',
            'Diploid' => 'Diploid',
            'Polyploid' => 'Polyploid',
          ),
          '#ajax' => array(
            'callback' => 'gttn_tpps_genetic_callback',
            'wrapper' => 'gttn-tpps-genetic',
          ),
        );

        $ploidy = gttn_tpps_get_ajax_value($form_state, array(
          'genetic',
          'ploidy',
        ));

        $form['genetic']['ssr_spreadsheet'] = array(
          '#type' => 'managed_file',
          '#title' => t('SSRs/cpSSRs Spreadsheet: *'),
          '#upload_location' => "$genotype_upload_location",
          '#upload_validators' => array(
            'file_validate_extensions' => array('csv tsv xlsx'),
          ),
          '#description' => t('Please upload a spreadsheet containing your SSRs/cpSSRs data. The format of this file is very important! GTTN-TPPS will parse your file based on the ploidy you have selected above. For any ploidy, GTTN-TPPS will assume that the first column of your file is the column that holds the Sample Identifier that matches your sample file.'),
          '#tree' => TRUE,
        );

        switch ($ploidy) {
          case 'Haploid':
            $form['genetic']['ssr_spreadsheet']['#description'] .= ' For haploid, GTTN-TPPS assumes that each remaining column in the spreadsheet is a marker.';
            break;

          case 'Diploid':
            $form['genetic']['ssr_spreadsheet']['#description'] .= ' For diploid, GTTN-TPPS will assume that pairs of columns together are describing an individual marker, so the second and third columns would be the first marker, the fourth and fifth columns would be the second marker, etc.';
            break;

          case 'Polyploid':
            $form['genetic']['ssr_spreadsheet']['#description'] .= ' For polyploid, GTTN-TPPS will read columns until it arrives at a non-empty column with a different name from the last.';
            break;

          default:
            break;
        }
      }

      $form['genetic']['quality'] = array(
        '#type' => 'textfield',
        '#title' => t('DNA Quality'),
        '#gttn_tpps_val' => array(),
      );

      $form['genetic']['extraction'] = array(
        '#type' => 'textfield',
        '#title' => t('DNA Extraction Method'),
        '#gttn_tpps_val' => array(),
      );
    }
  }

  if (!empty($types['Anatomical Reference Data'])) {
    $slides_upload_location = 'public://' . variable_get('gttn_tpps_anatomy_files_dir', 'gttn_tpps_anatomy');
    $form['anatomy'] = array(
      '#type' => 'fieldset',
      '#title' => t('Anatomical Reference Data information'),
      'meta_only' => array(
        '#type' => 'checkbox',
        '#title' => t('I am not providing Anatomical Reference data for this submission.') . '<br><strong>NOTE: </strong>' . t('Selecting this option will classify your submission as an incomplete reference submission. Metadata you upload will still be visible in accordance with your data access settings.'),
        '#ajax' => array(
          'callback' => 'gttn_tpps_anatomy_callback',
          'wrapper' => 'gttn-tpps-anatomy',
        ),
      ),
      '#prefix' => '<div id="gttn-tpps-anatomy">',
      '#suffix' => '</div>',
      '#collapsible' => TRUE,
    );

    $meta_only = gttn_tpps_get_ajax_value($form_state, array('anatomy', 'meta_only'), FALSE);

    if (!$meta_only) {
      $form['anatomy']['metadata'] = array(
        '#type' => 'fieldset',
        '#title' => t('Anatomical Characteristics'),
        '#tree' => TRUE,
        '#collapsible' => TRUE,
        '#description' => t('Please provide descriptions of the anatomical characteristics of each of the species you have indicated.'),
      );

      $anatomy_types = array(
        'nomenclature' => t('Nomenclature'),
        'general' => t('General'),
        'vessels' => t('Vessels'),
        'tracheids_fibres' => t('Tracheids and Fibres'),
        'axial_parenchyma' => t('Axial Parenchyma'),
        'rays' => t('Rays'),
        'storied_structures' => t('Storied Structures'),
        'mineral_inclusions' => t('Mineral Inclusions'),
        'physical_chemical' => t('Physical and Chemical Tests'),
      );

      foreach ($form_state['data']['organism'] as $info) {
        $species_name = "{$info['genus']} {$info['species']}";
        $form['anatomy']['metadata'][$species_name] = array(
          '#type' => 'fieldset',
          '#title' => t("$species_name Anatomy Information"),
          '#tree' => TRUE,
          '#collapsible' => TRUE,
        );
        foreach ($anatomy_types as $id => $type) {
          $form['anatomy']['metadata'][$species_name][$id] = array(
            '#type' => 'textarea',
            '#title' => $type . ':',
            '#gttn_tpps_val' => array(),
          );
        }
      }

      $field = array(
        '#type' => 'fieldset',
        'image' => array(
          '#type' => 'managed_file',
          '#upload_validators' => array(
            'file_validate_extensions' => array('img jpg jpeg png svg'),
          ),
          '#upload_location' => $slides_upload_location,
          '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: img jpg jpeg png svg</span>',
          '#standard_name' => 'Slide_Image_!num',
        ),
        'description' => array(
          '#type' => 'textfield',
          '#title' => 'Slide image !num description: *',
          '#description' => t('Please provide a brief description of the provided image'),
        ),
        'credit' => array(
          '#type' => 'textfield',
          '#title' => 'Slide image !num photo credit:',
          '#gttn_tpps_val' => array(),
        ),
      );

      gttn_tpps_dynamic_list($form, $form_state, 'slides', $field, array(
        'parents' => array('anatomy'),
        'title' => t('Microscope slides:'),
        'label' => t('Image'),
        'default' => 1,
        'substitute_fields' => array(
          array('image', '#standard_name'),
          array('description', '#title'),
          array('credit', '#title'),
        ),
      ));

      for ($i = 1; $i <= 10; $i++) {
        if (empty($form['anatomy']['slides'][$i])) {
          $form['anatomy']['slides'][$i] = array(
            '#type' => 'fieldset',
            '#prefix' => '<div class="hide-me">',
            '#suffix' => '</div>',
            'image' => array(
              '#type' => 'managed_file',
              '#upload_validators' => array(
                'file_validate_extensions' => array('img jpg jpeg png svg'),
              ),
              '#upload_location' => $slides_upload_location,
              '#gttn_tpps_val' => array(),
            ),
          );
        }
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
    '#value' => t('Review Information and Submit'),
  );

  return $form;
}
