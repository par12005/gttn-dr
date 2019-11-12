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
        $form_state['stats']['tree_count'] += gttn_tpps_file_len($fid) - 1 + !empty($no_header);
        if (empty($form_state['values']['tree-accession']['check'])) {
          break;
        }
      }
      break;

    default:
      break;
  }
}
