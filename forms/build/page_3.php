<?php

/**
 * @file
 */

require_once 'page_3_ajax.php';

/**
 * Populates the form element for the third page of the form.
 *
 * @param array $form
 *   The form element to be populated.
 * @param array $form_state
 *   The form state associated with the form element to be populated.
 *
 * @return array The populated form element.
 */
function page_3_create_form(&$form, &$form_state) {
  // Load saved values for the third page if they are available.
  $values = $form_state['saved_values'][GTTN_PAGE_3] ?? array();

  // Create the tree accession set of fields.
  $form['tree-accession'] = array(
    '#type' => 'fieldset',
    '#title' => t('Tree Accession Information'),
    '#tree' => TRUE,
    '#prefix' => '<div id="gttn_tpps_accession">',
    '#suffix' => '</div>',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $species_number = $form_state['stats']['species_count'];

  // If the user has more than one species, allow them to choose between uploading
  // one file with every species, or a separate file for each species.
  if ($species_number > 1) {
    // Create the single/multiple file checkbox.
    $form['tree-accession']['check'] = array(
      '#type' => 'checkbox',
      '#title' => t('I would like to upload a separate tree accession file for each species.'),
      '#ajax' => array(
        'wrapper' => 'gttn_tpps_accession',
        'callback' => 'gttn_tpps_accession_multi_file',
      ),
    );
  }

  // Field description for tree accesison file upload fields.
  $file_description = "Please upload a spreadsheet file containing tree population data. When your file is uploaded, you will be shown a table with your column header names, several drop-downs, and the first few rows of your file. You will be asked to define the data type for each column, using the drop-downs provided to you. If a column data type does not fit any of the options in the drop-down menu, you may omit that drop-down menu. Your file must contain columns with information about at least the Tree Identifier and the Location of the tree (either gps coordinates or country/state).";
  // Get the upload location from the GTTN-TPPS settings. If the settings, are
  // not available, then default to "public://gttn_tpps_accession".
  $file_upload_location = 'public://' . variable_get('gttn_tpps_accession_files_dir', 'gttn_tpps_accession');
  $sample_file_upload_location = 'public://' . variable_get('gttn_tpps_sample_files_dir', 'gttn_tpps_sample');

  if ($species_number > 1) {
    $file_description .= " If you are uploading a single file with multiple species, your file must also specify the genus and species of each tree.";
  }

  $image_path = drupal_get_path('module', 'gttn_tpps') . '/images/';
  $file_description .= "Please find an example of an accession file below.<figure><img src=\"/{$image_path}accession_example.png\"><figcaption>Example Accession File</figcaption></figure>";

  $check = $form_state['complete form']['tree-accession']['check']['#value'] ?? NULL;
  if (!isset($check)) {
    $check = $form_state['saved_values'][GTTN_PAGE_3]['tree-accession']['check'] ?? FALSE;
  }

  $required_groups = array(
    'Tree Id' => array(
      'id' => array(1),
    ),
    'Location (latitude/longitude or country/state or population group)' => array(
      'approx' => array(2, 3),
      'gps' => array(4, 5),
      'pop_group' => array(12),
      'forest_id' => array(13),
    ),
  );

  for ($i = 1; $i <= $species_number; $i++) {
    $name = $form_state['saved_values'][GTTN_PAGE_1]['organism']["$i"];
    $id_name = implode("_", explode(" ", $name));

    $column_options = array(
      '0' => 'N/A',
      '1' => 'Tree Identifier',
      '2' => 'Country',
      '3' => 'State',
      '4' => 'Latitude',
      '5' => 'Longitude',
      '8' => 'County',
      '9' => 'District',
      '12' => 'Population Group',
      '13' => 'Forest ID',
      '14' => 'Bar code',
      '15' => 'Species Identification Confidence Score',
    );

    $title = t("@name Accession File: *", array('@name' => $name)) . "<br>$file_description";
    if ($species_number > 1 and !$check) {
      $title = t('Tree Accession File: *') . "<br>$file_description";
      $column_options['6'] = 'Genus';
      $column_options['7'] = 'Species';
      $column_options['10'] = 'Genus + Species';
      $required_groups['Genus and Species'] = array(
        'separate' => array(6, 7),
        'combined' => array(10),
      );
    }

    $form['tree-accession']["species-$i"] = array(
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#states' => ($i > 1) ? array(
        'visible' => array(
          ':input[name="tree-accession[check]"]' => array('checked' => TRUE),
        ),
      ) : NULL,
    );

    $form['tree-accession']["species-$i"]['file'] = array(
      '#type' => 'managed_file',
      '#title' => $title,
      '#upload_location' => $file_upload_location,
      '#upload_validators' => array(
        'file_validate_extensions' => array('txt csv xlsx'),
      ),
      '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: txt csv xlsx</span>',
      '#suffix' => '<style>figure {}</style>',
      '#required_groups' => $required_groups,
      '#gttn_tpps_val' => array(
        'standard' => TRUE,
        'function' => 'gttn_tpps_managed_file_validate',
        'condition' => 'gttn_tpps_accession_conditional',
        'additional_function' => 'gttn_tpps_validate_accession',
      ),
      '#standard_name' => 'Tree_Accession',
      'empty' => array(
        '#default_value' => $values['tree-accession']["species-$i"]['file']['empty'] ?? 'NA',
      ),
      'columns' => array(
        '#description' => 'Please define which columns hold the required data: Tree Identifier and Location. If your trees are located based on a population group or a forest id, you can provide the population group column and a mapping of population group to location below.',
      ),
      'no-header' => array(),
      'columns-options' => array(
        '#type' => 'hidden',
        '#value' => $column_options,
      ),
    );

    $form['tree-accession']["species-$i"]['coord-format'] = array(
      '#type' => 'select',
      '#title' => t('Coordinate Projection'),
      '#options' => array(
        'WGS 84' => 'WGS 84',
        'NAD 83' => 'NAD 83',
        'ETRS 89' => 'ETRS 89',
        'Other Coordinate Projection' => 'Other Coordinate Projection',
        'My file does not use coordinates for tree locations' => 'My file does not use coordinates for tree locations',
      ),
      '#states' => $form['tree-accession']["species-$i"]['#states'] ?? NULL,
      '#suffix' => "<div id=\"{$id_name}_map_wrapper\"></div>"
        . "<input id=\"{$id_name}_map_button\" type=\"button\" value=\"Click here to view trees on map!\" class=\"btn btn-primary\"></input>"
        . "<div id=\"{$id_name}_species_number\" style=\"display:none;\">$i</div>"
        . "<script>jQuery('#{$id_name}_map_button').click(getCoordinates);</script>",
    );

    $form['tree-accession']["species-$i"]['pop-group'] = array(
      '#type' => 'hidden',
      '#title' => 'Population group mapping',
      '#prefix' => "<div id=\"population-mapping-species-$i\">",
      '#suffix' => '</div>',
      '#tree' => TRUE,
    );

    $pop_group_show = FALSE;

    $cols = $form_state['complete form']['tree-accession']["species-$i"]['file']['columns'] ?? NULL;
    if (!isset($cols)) {
      $cols = $form_state['saved_values'][GTTN_PAGE_3]['tree-accession']["species-$i"]['file-columns'] ?? NULL;
    }

    if (!empty($cols)) {
      foreach ($cols as $col_name => $data) {
        if ($col_name[0] == '#') {
          continue;
        }
        if (!empty($data['#value']) and ($data['#value'] == '12' or $data['#value'] == '13')) {
          $pop_group_show = TRUE;
          $pop_col = $col_name;
          $fid = $form_state['complete form']['tree-accession']["species-$i"]['file']['#value']['fid'];
          break;
        }
        if ($data == '12' or $data == '13') {
          $pop_group_show = TRUE;
          $pop_col = $col_name;
          $fid = $form_state['saved_values'][GTTN_PAGE_3]['tree-accession']["species-$i"]['file'];
          break;
        }
      }

      if (!empty($fid) and ($file = file_load($fid)) and $pop_group_show) {
        $form['tree-accession']["species-$i"]['pop-group']['#type'] = 'fieldset';
        $content = gttn_tpps_parse_file($fid);

        for ($j = 0; $j < count($content) - 1; $j++) {
          $pop_group = $content[$j][$pop_col];
          if (empty($form['tree-accession']["species-$i"]['pop-group'][$pop_group])) {
            $form['tree-accession']["species-$i"]['pop-group'][$pop_group] = array(
              '#type' => 'textfield',
              '#title' => "Location for $name trees from group $pop_group:",
            );
          }
        }
      }
    }

  }
  
  $map_api_key = variable_get('gttn_tpps_maps_api_key', NULL);
  if (!empty($map_api_key)) {
    $form['tree-accession']['#suffix'] .= '
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script><script src="https://maps.googleapis.com/maps/api/js?key=' . $map_api_key . '&callback=initMap"
    async defer></script>
    <style>
      #map_wrapper {
      height: 450px;
      }
    </style>';
  }

  if (!empty($form_state['saved_values'][GTTN_PAGE_1]['data_type']['Sample Data'])) {
    $form['samples'] = array(
      '#type' => 'fieldset',
      '#title' => t('Sample Information'),
      '#tree' => TRUE,
      '#prefix' => '<div id="gttn_tpps_samples">',
      '#suffix' => '</div>',
    );

    $form['samples']['type'] = array(
      '#type' => 'checkbox',
      '#title' => t('These samples are physical samples'),
      '#default_value' => $values['samples']['type'] ?? 1,
      '#ajax' => array(
        'callback' => 'gttn_tpps_samples_callback',
        'wrapper' => 'gttn_tpps_samples'
      ),
    );

    $type = gttn_tpps_get_ajax_value($form_state, array(
      'samples',
      'type',
    ), 1);

    $column_options = array(
      0 => 'N/A',
      1 => 'Internal Sample ID',
      2 => 'Xylarium ID',
      3 => 'Collection Date',
      4 => 'Sample Collector',
      5 => 'Tissue Type',
      6 => 'Sampling Method',
      7 => 'Dimensions',
      8 => 'Sample Source',
      9 => 'Storage Location',
      10 => 'Remaining Dimensions/Volume of Sample',
      11 => 'Sample has been analyzed',
    );

    $required_groups = array(
      'Sample Id' => array(
        'internal' => array(1),
        'xylarium' => array(2),
      ),
      'Sample Source' => array(
        'source' => array(8),
      ),
    );

    $share = gttn_tpps_get_ajax_value($form_state, array('samples', 'sharable'), NULL);
    if ($share) {
      if ($type) {
        $required_groups['Sample Dimensions'] = array(
          'dimension' => array(7),
        );
      }
      $required_groups['Remaining Volume of Sample'] = array(
        'volume' => array(10),
      );
    }

    $sample_file_description = "Please upload a spreadsheet file containing sample data. When your file is uploaded, you will be shown a table with your column header names, several drop-downs, and the first few rows of your file. You will be asked to define the data type for each column, using the drop-downs provided to you. If a column data type does not fit any of the options in the drop-down menu, you may omit that drop-down menu. Your file must contain columns with information about at least the Sample ID, Source, remaining volume, and dimensions. If your sample file does not contain information about the sample tissue type, collection date, field collector, sampling method, analysis state, and storage location, then you will be required to enter those manually below the file.";
    $sample_file_description .= "Please find an example of an accession file below.<figure><img width=\"100%\" src=\"/{$image_path}sample_example.png\"><figcaption>Example Sample Information File</figcaption></figure>";

    $form['samples']['file'] = array(
      '#type' => 'managed_file',
      '#title' => t("Sample Information File: *") . "<br>$sample_file_description",
      '#upload_location' => $sample_file_upload_location,
      '#upload_validators' => array(
        'file_validate_extensions' => array('txt csv xlsx'),
      ),
      '#field_prefix' => '<span style="width: 100%;display: block;text-align: right;padding-right: 2%;">Allowed file extensions: txt csv xlsx</span>',
      '#required_groups' => $required_groups,
      '#gttn_tpps_val' => array(
        'standard' => TRUE,
        'function' => 'gttn_tpps_managed_file_validate',
      ),
      '#standard_name' => 'Samples',
      'empty' => array(
        '#default_value' => $values['samples']['file']['empty'] ?? 'NA',
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

    $date_cols = gttn_tpps_get_file_columns($form_state, array(
      'samples',
      'file',
    ), 3);
    if (empty($date_cols)) {
      $form['samples']['date'] = array(
        '#type' => 'date',
        '#title' => t('Sample Collection Date: *'),
      );
    }

    $collector_cols = gttn_tpps_get_file_columns($form_state, array(
      'samples',
      'file',
    ), 4);
    if (empty($collector_cols)) {
      $form['samples']['collector'] = array(
        '#type' => 'textfield',
        '#title' => t('Sample Collector: *'),
        '#description' => t('The person who collected the samples.'),
      );
    }

    $tissue_cols = gttn_tpps_get_file_columns($form_state, array(
      'samples',
      'file',
    ), 5);
    if (empty($tissue_cols)) {
      $form['samples']['tissue'] = array(
        '#type' => 'select',
        '#title' => t('Tissue Type: *'),
        '#options' => array(
          '- Select -',
          'Wood' => 'Wood',
          'Bark' => 'Bark',
          'Leaf' => 'Leaf',
          'Cambium' => 'Cambium',
          'DNA' => 'DNA',
        ),
      );
    }

    $method_cols = gttn_tpps_get_file_columns($form_state, array(
      'samples',
      'file',
    ), 6);
    if (empty($method_cols)) {
      $form['samples']['method'] = array(
        '#type' => 'select',
        '#title' => t('Sampling Method: *'),
        '#options' => array(
          '- Select -',
          'Increment Core' => 'Increment Core',
          'Punch' => 'Punch',
          'Disc' => 'Disc',
          'Cube' => 'Cube',
        ),
      );
    }

    $analyzed_cols = gttn_tpps_get_file_columns($form_state, array(
      'samples',
      'file',
    ), 11);
    if (empty($analyzed_cols)) {
      $form['samples']['analyzed'] = array(
        '#type' => 'checkbox',
        '#title' => t('These samples have been analyzed'),
      );
    }

    $storage_cols = gttn_tpps_get_file_columns($form_state, array(
      'samples',
      'file',
    ), 9);
    if (empty($storage_cols)) {
      $options = array();
      $query = db_select('gttn_profile_organization', 'o')
        ->fields('o', array('organization_id', 'name'))
        ->execute();
      while (($result = $query->fetchObject())) {
        $options[$result->organization_id] = $result->name;
      }
      $options['other'] = 'Other';
      $form['samples']['storage'] = array(
        '#type' => 'select',
        '#title' => t('Storage location: *'),
        '#options' => $options,
        '#default_value' => $form_state['data']['project']['props']['organization'],
        '#ajax' => array(
          'callback' => 'gttn_tpps_samples_callback',
          'wrapper' => 'gttn_tpps_samples'
        ),
      );

      $storage = gttn_tpps_get_ajax_value($form_state, array('samples', 'storage'), NULL);
      if ($storage == 'other') {
        $form['samples']['storage-other'] = array(
          '#type' => 'textfield',
          '#title' => t('Other storage location'),
        );
      }
    }

    $form['samples']['tech_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Technician Name'),
      '#gttn_tpps_val' => array(),
    );

    $form['samples']['tech_email'] = array(
      '#type' => 'textfield',
      '#title' => t('Technician Email'),
      '#gttn_tpps_val' => array(),
    );

    $form['samples']['sharable'] = array(
      '#type' => 'checkbox',
      '#title' => t('These samples can be shared'),
      '#ajax' => array(
        'callback' => 'gttn_tpps_samples_callback',
        'wrapper' => 'gttn_tpps_samples'
      ),
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
  $form['Next'] = array(
    '#type' => 'submit',
    '#value' => t('Next'),
  );

  return $form;
}
