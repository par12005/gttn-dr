<?php

/**
 * @file
 */

/**
 * 
 */
function gttn_tpps_validate(&$form, &$form_state, $values = NULL, array $parents = array()) {
  $skip_types = array(
    'token',
    'hidden',
    'submit',
    'button',
  );
  if (empty($values)) {
    $values = $form_state['values'];
  }

  foreach ($values as $key => $value) {
    $current_parents = $parents;
    array_push($current_parents, $key);
    $form_element = drupal_array_get_nested_value($form, $current_parents);
    if (empty($form_element) or gettype($form_element) != 'array' or in_array($form_element['#type'], $skip_types)) {
      continue;
    }

    if (gettype($value) == 'array') {
      gttn_tpps_validate($form, $form_state, $value, $current_parents);
    }

    if (!isset($form_element['#gttn_tpps_val']) or !empty($form_element['#gttn_tpps_val']['standard'])) {
      gttn_tpps_standard_errors($form, $form_state, $value, $current_parents);
    }

    if (!empty($form_element['#gttn_tpps_val'])) {
      if (array_key_exists('function', $form_element['#gttn_tpps_val'])) {
        $function = $form_element['#gttn_tpps_val']['function'];
        $function($form, $form_state, $value, $current_parents);
      }
    }
  }
}

/**
 * 
 */
function gttn_tpps_standard_errors(&$form, &$form_state, $value, $parents) {
  $form_element = drupal_array_get_nested_value($form, $parents);
  switch ($form_element['#type']) {
    case 'select':
    case 'textarea':
    case 'textfield':
    case 'radios':
      if (empty($value)) {
        $name = gttn_tpps_get_field_name($form_element['#title']);
        form_set_error(implode('][', $parents), "$name: field is required.");
      }
      break;

    case 'checkboxes':
      $concat = implode('', $value);
      if (preg_match('/^0+$/', $concat)) {
        $name = gttn_tpps_get_field_name($form_element['#title']);
        form_set_error(implode('][', $parents), "$name: field is required.");
      }
      break;

    default:
      break;
  }
}

/**
 * 
 */
function gttn_tpps_get_field_name($title) {
  preg_match('/(.*[^: *])([: *]*)$/', $title, $matches);
  return $matches[1];
}

/**
 * 
 */
function gttn_tpps_managed_file_validate(&$form, &$form_state, $value, $parents) {
  $element = drupal_array_get_nested_value($form, $parents);
  $page = $form_state['stage'];
  if (!empty($element['#gttn_tpps_val']['condition'])) {
    $function = $element['#gttn_tpps_val']['condition'];
    if (!$function($form, $form_state, $value, $parents)) {
      return;
    }
  }
  if (empty($value)) {
    preg_match('/^([^:]*):/', $element['#title'], $matches);
    $name = $matches[1];
    form_set_error(implode('][', $parents), "$name: field is required");
  }
  $required_groups = $element['#required_groups'];
  $groups = gttn_tpps_file_validate_columns($form_state, $required_groups, $element);
  $function = $element['#gttn_tpps_val']['additional_function'] ?? NULL;
  if (!empty($function) and function_exists($function)) {
    $function($form, $form_state, $value, $parents);
  }

  if (!form_get_errors()) {
    $file = file_load($value);
    file_usage_add($file, 'tpps', 'tpps_project', substr($form_state['accession'], 9));
    $form_state['file_info'][$page][$file->fid] = $element['#standard_name'];
  }

  if (form_get_errors()) {
    $form_state['rebuild'] = TRUE;
    $new_form = drupal_rebuild_form('gttn_tpps_main', $form_state, $form);
    $upload_parents = $column_parents = $parents;
    array_push($upload_parents, 'upload');
    array_push($column_parents, 'columns');
    $old_upload = drupal_array_get_nested_value($form, $upload_parents);
    $new_upload = drupal_array_get_nested_value($new_form, $upload_parents);
    if (isset($old_upload) and isset($new_upload)) {
      drupal_array_set_nested_value($form, $upload_parents, drupal_array_get_nested_value($new_form, $upload_parents));
      drupal_array_set_nested_value($form, $column_parents, drupal_array_get_nested_value($new_form, $column_parents));
      $upload_id = 'edit-' . implode('-', $upload_parents);
      $column_id = 'edit-' . implode('-', $column_parents);
      array_push($upload_parents, '#id');
      array_push($column_parents, '#id');
      drupal_array_set_nested_value($form, $upload_parents, $upload_id);
      drupal_array_set_nested_value($form, $column_parents, $column_id);
    }
  }
}

/**
 * 
 */
function gttn_tpps_validate_organism(&$form, &$form_state, $value, $parents) {
  if (empty($value)) {
    return;
  }
  $num = end($parents);

  $parts = explode(" ", $value);
  $genus = $parts[0];
  $species = implode(" ", array_slice($parts, 1));
  $empty_pattern = '/^ *$/';
  $correct_pattern = '/^[A-Z|a-z|.| ]+$/';
  if (!isset($genus) or !isset($species) or preg_match($empty_pattern, $genus) or preg_match($empty_pattern, $species) or !preg_match($correct_pattern, $genus) or !preg_match($correct_pattern, $species)) {
    form_set_error("organism[$num", check_plain("Tree Species $num: please provide both genus and species in the form \"<genus> <species>\"."));
  }
  else {
    $query = db_select('chado.organism', 'o')
      ->fields('o', array('organism_id'))
      ->condition('genus', $genus)
      ->condition('species', $species)
      ->execute();
    if (empty($query->fetchObject())) {
      drupal_set_message("Species $num: $value will be added as a new organism when the form is submitted.", 'status');
    }
  }
}

/**
 * 
 */
function gttn_tpps_validate_accession(&$form, &$form_state, $value, $parents) {
  if (!form_get_errors()) {
    $meta_parents = $parents;
    array_pop($meta_parents);
    $values = drupal_array_get_nested_value($form_state['values'], $meta_parents);
    $element = drupal_array_get_nested_value($form, $parents);
    $required_groups = $element['#required_groups'];
    $field_path = implode('][', $parents);

    $groups = $values['file-groups'];
    $id_name = $groups['Tree Id'][1];
    $content = gttn_tpps_parse_file($value, 0, !empty($values['file-no-header']));
    $empty = $values['file-empty'];
    $location_options = $required_groups['Location (latitude/longitude or country/state or population group)'];
    $location_columns = $groups['Location (latitude/longitude or country/state or population group)'];
    $location_types = $location_columns['#type'];

    if (gettype($location_types) !== 'array') {
      $location_types = array($location_types);
    }

    foreach ($content as $row => $vals) {
      if ($row !== 'headers' and !empty($vals[$id_name])) {
        $valid_row = FALSE;
        foreach ($location_types as $type) {
          $valid_combination = TRUE;
          foreach ($location_options[$type] as $column) {
            if (empty($vals[$location_columns[$column]]) or $vals[$location_columns[$column]] == $empty) {
              $valid_combination = FALSE;
            }
          }
          if ($valid_combination) {
            $valid_row = TRUE;
            break;
          }
        }
        if (!$valid_row) {
          form_set_error($field_path, "Tree Accession file: Some location information is missing for tree \"{$vals[$id_name]}\".");
        }
      }
    }
  }
}

/**
 * 
 */
function gttn_tpps_accession_conditional(&$form, &$form_state, $value, $parents) {
  preg_match('/-(.*)$/', $parents[1], $matches);
  $number = (int) $matches[1];
  if ($number == 1) {
    return TRUE;
  }
  if (empty($form_state['values']['tree-accession']['check'])) {
    return FALSE;
  }
  if ($number <= $form_state['stats']['species_count']) {
    return TRUE;
  }
  return FALSE;
}

/**
 *
 */
function gttn_tpps_validate_dart(&$form, &$form_state, $value, $parents) {
  if (empty($value)) {
    form_set_error('dart][raw', 'DART Raw Data File: field is required.');
    return;
  }
  if (!($file = file_load($value))) {
    form_set_error('dart][raw', 'DART Raw Data File: error loading file.');
    return;
  }

  $loc = drupal_realpath($file->uri);
  $ext = gttn_tpps_get_path_extension($loc);
  $unzip_dir = dirname($loc) . '/tmp';
  if (is_dir($unzip_dir) or mkdir($unzip_dir)) {
    switch ($ext) {
      case 'zip':
        $zip = new \ZipArchive();
        $zip->open($loc);
        break;

      case 'gz':
        $zip = new \PharData($loc);
        $zip->decompress();
        break;

      case 'tar':
        $zip = new \PharData($loc);
        break;

      default:
        return;
    }
    $zip->extractTo($unzip_dir);

    $dir = $unzip_dir;
    $files = scandir($dir);
    if ($files and count($files) == 3 and is_dir($dir . '/' . $files[2])) {
      $dir .= '/' . $files[2];
      $files = scandir($dir);
    }

    if ($files) {
      $form_state['data']['dart'] = gttn_tpps_parse_dart_dir($dir, $files);
      foreach ($form_state['data']['dart'] as $sample => $info) {
        if (empty($form_state['data']['samples'][$sample])) {
          form_set_error('dart][raw', "DART Raw Data File: Sample data is missing for the DART file you provided: $sample.txt. We expected to see a sample called '$sample'.");
        }
      }
    }
  }
  gttn_tpps_rmdir($unzip_dir);
}

/**
 *
 */
function gttn_tpps_parse_dart_dir($dir, $files) {
  $results = array();
  foreach ($files as $file_name) {
    if ($file_name[0] != '.') {
      $sample = basename($file_name, '.txt');
      $results[$sample] = array();
      $handle = fopen($dir . '/' . $file_name, 'r');

      while (!feof($handle)) {
        $line = preg_split('/\s+/', fgets($handle));
        if (count($line) == 3 and $line[2] === '') {
          $results[$sample][] = array(
            'measure' => (float)$line[0],
            'value' => (float)$line[1],
          );
        }
      }
    }
  }
  return $results;
}

/**
 * 
 */
function gttn_tpps_update_stats(&$form, &$form_state) {
  switch ($form_state['stage']) {
    case GTTN_PAGE_1:
      $form_state['stats']['species_count'] = $form_state['values']['organism']['number'];
      break;

    case GTTN_PAGE_3:
      $form_state['stats']['tree_count'] = 0;
      for ($i = 1; $i <= $form_state['stats']['species_count']; $i++) {
        $fid = $form_state['values']['tree-accession']["species-$i"]['file'];
        $no_header = !empty($form_state['values']['tree-accession']["species-$i"]['file-no-header']);
        $form_state['stats']['tree_count'] += gttn_tpps_file_len($fid) + !empty($no_header);
        if (empty($form_state['values']['tree-accession']['check'])) {
          break;
        }
      }
      break;

    default:
      break;
  }
}

/**
 * 
 */
function gttn_tpps_update_data(&$form, &$form_state) {
  switch ($form_state['stage']) {
    case GTTN_TYPE_PAGE:
      $form_state['data']['project'] = $form_state['values']['project'];
      $date = $form_state['data']['project']['props']['analysis_date'];
      $form_state['data']['project']['props']['analysis_date'] = "{$date['day']}-{$date['month']}-{$date['year']}";
      break;

    case GTTN_PAGE_1:
      $form_state['data']['organism'] = array();
      for ($i = 1; $i <= $form_state['values']['organism']['number']; $i++) {
        $parts = explode(' ', $form_state['values']['organism'][$i]);
        $genus = $parts[0];
        $species = implode(' ', array_slice($parts, 1));
        $form_state['data']['organism'][$i] = array(
          'genus' => $genus,
          'species' => $species,
        );
      }

      $form_state['data']['project']['props']['data_type'] = array();
      foreach ($form_state['values']['data_type'] as $data_type) {
        if (!empty($data_type)) {
          $form_state['data']['project']['props']['data_type'][] = $data_type;
        }
      }
      break;

    case GTTN_PAGE_3:
      $form_state['data']['trees'] = array();
      $form_state['data']['samples'] = array();
      $form_state['file_info'][GTTN_PAGE_3] = array();
      for ($i = 1; $i <= $form_state['saved_values'][GTTN_PAGE_1]['organism']['number']; $i++) {
        $fid = $form_state['values']['tree-accession']["species-$i"]['file'] ?? NULL;
        if (!empty($fid) and file_load($fid)) {
          $current_field = $form_state['values']['tree-accession']["species-$i"];
          $no_header = $current_field['file-no-header'];
          $form_state['file_info'][GTTN_PAGE_3][] = array(
            'fid' => $fid,
            'name' => 'Tree_Accession',
            'columns' => $current_field['file-columns'],
            'groups' => $current_field['file-groups'],
          );

          $id_col = $current_field['file-groups']['Tree Id'][1];
          $content = gttn_tpps_parse_file($fid, 0, $no_header, array($id_col));

          for ($j = 0; $j < count($content) - 1; $j++) {
            $form_state['data']['trees'][$content[$j][$id_col]] = array(
              'id' => $content[$j][$id_col],
            );
          }
        }
      }

      $fid = $form_state['values']['samples']['file'] ?? NULL;
      if (!empty($fid) and file_load($fid)) {
        $samples_type = $form_state['values']['samples']['type'];
        $columns = $form_state['values']['samples']['file-columns'];
        $groups = $form_state['values']['samples']['file-groups'];
        $form_state['file_info'][GTTN_PAGE_3][] = array(
          'fid' => $fid,
          'name' => 'Sample_Accession',
          'columns' => $columns,
          'groups' => $groups,
        );

        $content = gttn_tpps_parse_file($fid, 0, $form_state['values']['samples']['file-no-header']);
        $id_col = $groups['Sample Id'][1] ?? $groups['Sample Id'][2];
        $source_col = $groups['Sample Source'][8];
        $dim_col = $groups['Sample Dimensions'][7];
        $xylarium_col = $groups['Sample Id'][2] ?? FALSE;
        $remaining_col = $groups['Remaining Volume of Sample'][10];

        $date = $form_state['values']['samples']['date'] ?? NULL;
        if (empty($date)) {
          $date_col = array_search(3, $columns);
        }
        $collector = $form_state['values']['samples']['collector'] ?? NULL;
        if (empty($collector)) {
          $collector_col = array_search(4, $columns);
        }
        $tissue = $form_state['values']['samples']['tissue'] ?? NULL;
        if (empty($tissue)) {
          $tissue_col = array_search(5, $columns);
        }
        $method = $form_state['values']['samples']['method'] ?? NULL;
        if (empty($method)) {
          $method_col = array_search(6, $columns);
        }
        if (!$samples_type) {
          $storage = $form_state['values']['samples']['storage'] ?? NULL;
          if (empty($storage)) {
            $storage_col = array_search(9, $columns);
          }
        }
        $analyzed = $form_state['values']['samples']['analyzed'] ?? NULL;
        if (empty($analyzed)) {
          $analyzed_col = array_search(11, $columns);
        }

        $legal = $form_state['values']['samples']['legal'] ? TRUE : FALSE;
        $share = $form_state['values']['samples']['sharable'] ? TRUE : FALSE;

        for ($j = 0; $j < count($content) - 1; $j++) {
          $form_state['data']['samples'][$content[$j][$id_col]] = array(
            'id' => $content[$j][$id_col],
            'xylarium' => $xylarium_col ? $content[$j][$xylarium_col] : NULL,
            'source' => $content[$j][$source_col],
            'tissue' => $tissue ?? ($content[$j][$tissue_col] ?? NULL),
            'dimension' => $content[$j][$dim_col],
            'date' => $date ?? ($content[$j][$date_col] ?? NULL),
            'collector' => $collector ?? ($content[$j][$collector_col] ?? NULL),
            'method' => $method ?? ($content[$j][$method_col] ?? NULL),
            'remaining' => $content[$j][$remaining_col],
            'type' => $samples_type ? 'Physical' : 'DNA',
            'analyzed' => $analyzed ?? ($content[$j][$analyzed_col] ?? NULL),
            'legal' => $legal ?? NULL,
            'share' => $share ?? NULL,
          );
          if (!$samples_type) {
            $form_state['data']['samples'][$content[$j][$id_col]]['storage'] = $storage ?? ($content[$j][$storage_col] ?? NULL);
          }
        }
      }
      // TODO
      break;

    case GTTN_PAGE_4:
      $types = $form_state['saved_values'][GTTN_PAGE_1]['data_type'];
      $form_state['file_info'][GTTN_PAGE_4] = array();

      if (!empty($types['DART Reference Data'])) {
        $fid = $form_state['values']['dart']['file'];
        $columns = $form_state['values']['dart']['file-columns'];
        $groups = $form_state['values']['dart']['file-groups'];
        $form_state['file_info'][GTTN_PAGE_4][] = array(
          'fid' => $fid,
          'name' => 'DART',
          'columns' => $columns,
          'groups' => $groups,
        );
      }

      if (!empty($types['Isotope Reference Data'])) {
        $fid = $form_state['values']['isotope']['file'];
        $columns = $form_state['values']['isotope']['file-columns'] ?? NULL;
        $groups = $form_state['values']['isotope']['file-groups'] ?? NULL;
        $form_state['file_info'][GTTN_PAGE_4][] = array(
          'fid' => $fid,
          'name' => 'Isotope',
          'columns' => $columns,
          'groups' => $groups,
        );
        // TODO
      }

      if (!empty($types['Genetic Reference Data'])) {
        // TODO
      }

      if (!empty($types['Anatomical Reference Data'])) {
        // TODO
      }
      break;

    default:
      break;
  }
}
