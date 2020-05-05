<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_reference($type = NULL, $id = NULL) {
  $output = '';

  if (empty($type)) {
    $form = drupal_get_form('gttn_tpps_reference_search');
    $output .= render($form);
    $output .= "<div id='gttn-tpps-reference-table'>" . gttn_tpps_reference_top() . "</div>";
    drupal_add_css(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_CSS_PATH);
    drupal_add_js(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_JS_PATH);
    drupal_add_js(drupal_get_path('module', 'gttn_tpps') . '/js/reference.js');
    return $output;
  }

  global $base_url;

  $output = "<a href=\"$base_url/reference\">Return to Browse Reference Data</a>$output";
  $function = 'gttn_tpps_display_' . $type;
  if (function_exists($function)) {
    $output .= $function($id);
  }
  else {
    $output .= 'Coming Soon!';
  }

  drupal_add_css(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_CSS_PATH);
  drupal_add_js(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_JS_PATH);
  drupal_add_js(drupal_get_path('module', 'gttn_tpps') . '/js/reference.js');

  return $output;
}

/**
 *
 */
function gttn_tpps_reference_top_callback() {
  print(gttn_tpps_reference_top());
  return;
}

/**
 *
 */
function gttn_tpps_reference_top() {
  $params = drupal_get_query_parameters($_POST);
  $page = 0;
  if (empty($params)) {
    $params = drupal_get_query_parameters();
  }
  if (!empty($params['page'])) {
    $page = $params['page'];
  }
  $per_page = 20;
  $params['data_type'] = $params['data_type'] ?? 'submission';

  $items = gttn_tpps_get_reference_items($params);
  if (!empty($items) and !empty($params['attribute'])) {
    $function = 'gttn_tpps_reference_filter_' . $params['data_type'];
    if (function_exists($function)) {
      foreach ($items as $key => $item) {
        if (!$function($item, $params)) {
          unset($items[$key]);
        }
      }
    }
  }

  $headers = gttn_tpps_get_reference_headers($params);
  $total = count($items);
  $_GET['page'] = $page;
  $page = pager_default_initialize($total, $per_page);
  $start = $page * $per_page;
  $end = min($start + $per_page, $total);

  $items = array_slice($items, $start, $end - $start);
  $rows = array();
  foreach ($items as $item) {
    $info = gttn_tpps_reference_build_row($item, $params);
    if ($info['key']) {
      $rows[$info['key']] = $info['row'];
    }
    else {
      $rows[] = $info['row'];
    }
  }

  ksort($rows);

  $vars = array(
    'header' => $headers,
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );

  $output = theme_table($vars);
  $pager = theme('pager', array('quantity', $total));
  return $pager . $output;
}

/**
 *
 */
function gttn_tpps_reference_tab($type, $accession, $tab) {
  $function = "gttn_tpps_display_{$type}_tab";
  if (function_exists($function)) {
    $function($accession, $tab);
  }
  else {
    print '';
  }
  return;
}

/**
 *
 */
function gttn_tpps_get_reference_items($params) {
  $items = array();
  switch ($params['data_type']) {
    case 'submission':
      $conditions = array(
        'status' => 'Approved',
      );
      $items = gttn_tpps_load_submission_multiple($conditions);
      break;

    case 'sample':
      $conditions = array();
      $cvt = gttn_tpps_sample_cvterms();
      $query = db_select('chado.stock', 's')
        ->fields('s', array('stock_id', 'uniquename', 'organism_id'))
        ->condition('s.type_id', $cvt['sample'])
        ->execute();
    
      while (($result = $query->fetchObject())) {
        $items[] = $result;
      }
      break;

    default:
      drupal_set_message('Unrecognized data type');
      break;
  }
  return $items;
}

/**
 *
 */
function gttn_tpps_reference_filter_submission(&$item, $params) {
  $value = $params['value'] ?? NULL;
  switch ($params['attribute']) {
    case 'project_name':
      if (!empty($value) and !preg_match("/$value/i", $item['data']['project']['name'])) {
        return FALSE;
      }
      break;
    
    case 'submitting_org':
      $org_name = db_select('gttn_profile_organization', 'o')
        ->fields('o', array('name'))
        ->condition('o.organization_id', $item['data']['project']['props']['organization'])
        ->range(0, 1)
        ->execute()->fetchObject()->name;
      if (!empty($value) and !preg_match("/$value/i", $org_name)) {
        return FALSE;
      }
      break;

    case 'species':
      $parts = explode(' ', $value);
      foreach ($item['data']['organism'] as $info) {
        foreach ($parts as $part) {
          if (!empty($part) and (preg_match("/$part/i", $info['genus']) or preg_match("/$part/i", $info['species']))) {
            return TRUE;
          }
        }
      }
      return FALSE;

    case 'data_type':
      foreach ($item['saved_values'][GTTN_PAGE_1]['data_type'] as $type) {
        if (!empty($type) and preg_match("/$value/i", $type)) {
          return TRUE;
        }
      }
      return FALSE;

    default:
      break;
  }
  return TRUE;
}

/**
 *
 */
function gttn_tpps_reference_filter_sample(&$item, $params) {
  $value = $params['value'] ?? NULL;
  switch ($params['attribute']) {
    case 'sample_id':
      $id = substr($item->uniquename, 13);
      if (!empty($value) and !preg_match("/$value/i", $id)) {
        return FALSE;
      }
      break;

    case 'species':
      if (empty($value)) {
        break;
      }
      $parts = explode(' ', $value);
      $organism = db_select('chado.organism', 'o')
        ->fields('o', array('genus', 'species'))
        ->condition('o.organism_id', $item->organism_id)
        ->range(0, 1)
        ->execute()->fetchObject();

      foreach ($parts as $part) {
        if (!empty($part) and (preg_match("/$part/i", $organism->genus) or preg_match("/$part/i", $organism->species))) {
          return TRUE;
        }
      }
      return FALSE;

    default:
      break;
  }
  return TRUE;
}

/**
 *
 */
function gttn_tpps_get_reference_headers($params) {
  switch ($params['data_type']) {
    case 'submission':
      return array(
        'Accession',
        'Project Name',
        'Species',
        'Tissue Types',
        'Data Types',
        'Submitting Organization',
        'Analysis Date',
      );

    case 'sample':
      return array(
        'Internal Sample ID',
        'Species',
        'Tissue Type',
        'Source Material',
      );

    default:
      break;
  }
  return array();
}

/**
 *
 */
function gttn_tpps_reference_build_row($item, $params) {
  global $base_url;
  switch ($params['data_type']) {
    case 'submission':
      $state = $item;
      $species = array();
      foreach ($state['data']['organism'] as $org) {
        $species[] = "{$org['genus']} {$org['species']}";
      }
      $data_types = array();
      foreach ($state['saved_values'][GTTN_PAGE_1]['data_type'] as $type) {
        if (!empty($type)) {
          $data_types[] = $type;
        }
      }
      $tissues = array();
      foreach ($state['data']['samples'] as $info) {
        $tissues[$info['tissue']] = $info['tissue'];
      }
      $tissues = implode('<br>', $tissues);
      $query = db_select('gttn_profile_organization', 'o')
        ->fields('o', array('name'))
        ->condition('o.organization_id', $state['data']['project']['props']['organization'])
        ->range(0, 1)
        ->execute();
      $organization = $query->fetchObject()->name ?? NULL;
      $date = current($state['data']['samples'])['date'] ?? '';
      if (is_array($date)) {
        $date = date("jS F, Y", strtotime("{$date['day']}-{$date['month']}-{$date['year']}"));
      }

      $row = array(
        "<a href=\"$base_url/reference/submission/{$state['accession']}\">{$state['accession']}</a>",
        "<a href=\"$base_url/reference/submission/{$state['accession']}\">{$state['data']['project']['name']}</a>",
        implode('<br>', $species),
        $tissues,
        implode('<br>', $data_types),
        $organization,
        $date,
      );
      return array(
        'key' => $state['accession'],
        'row' => $row,
      );

    case 'sample':
      $id = substr($item->uniquename, 13);
      $cvt = gttn_tpps_sample_cvterms();

      $organism = db_select('chado.organism', 'o')
        ->fields('o', array('genus', 'species'))
        ->condition('o.organism_id', $item->organism_id)
        ->range(0, 1)
        ->execute()->fetchObject();
      $species = "{$organism->genus} {$organism->species}";

      $tissue = db_select('chado.stockprop', 'sp')
        ->fields('sp', array('value'))
        ->condition('sp.type_id', $cvt['tissue'])
        ->range(0, 1)
        ->execute()->fetchObject()->value;

      $query = db_select('chado.stock_relationship', 'sr');
      $query->join('chado.stock', 's', 's.stock_id = sr.subject_id');
      $query->fields('s', array('uniquename'));
      $query->condition('sr.object_id', $item->stock_id);
      $query->range(0, 1);
      $query = $query->execute();
      $source = substr($query->fetchObject()->uniquename, 13);
      
      $row = array(
        "<a href=\"$base_url/reference/sample/{$item->stock_id}\">$id</a>",
        $species,
        $tissue,
        $source,
      );
      return array(
        'key' => $item->stock_id,
        'row' => $row,
      );

    default:
      break;
  }
  return NULL;
}

/**
 *
 */
function gttn_tpps_reference_search(array $form, array $form_state) {

  $params = drupal_get_query_parameters();
  $form['data_type'] = array(
    '#type' => 'select',
    '#options' => array(
      'submission' => 'Reference Data Submission',
      'sample' => 'Sample',
    ),
    '#ajax' => array(
      'wrapper' => 'gttn-tpps-reference-search-wrapper',
      'callback' => 'gttn_tpps_reference_search_callback',
    ),
    '#prefix' => '<div id="gttn-tpps-reference-search-wrapper">',
    '#default_value' => $params['data_type'] ?? 'submission',
  );

  $data_type = $form_state['values']['data_type'] ?? ($params['data_type'] ?? 'submission');

  if (!empty($data_type)) {
    switch($data_type) {
      case 'submission':
        $options = array(
          'project_name' => 'Project Name',
          'submitting_org' => 'Submitting Organization',
          'species' => 'Species',
          'data_type' => 'Data Type',
          // TODO
        );
        break;

      case 'sample':
        $options = array(
          'sample_id' => 'Internal Sample ID',
          'species' => 'Species',
          // TODO
        );
        break;

      default:
        break;
    }
  }

  $form['attribute'] = array(
    '#type' => 'select',
    '#default_value' => $params['attribute'] ?? NULL,
    '#options' => $options,
  );

  $form['value'] = array(
    '#type' => 'textfield',
    '#default_value' => $params['value'] ?? NULL,
    '#suffix' => '</div>',
  );

  $form['search'] = array(
    '#type' => 'button',
    '#value' => t('Search'),
  );

  $form['#attributes'] = array(
    'style' => 'text-align:center',
  );

  return $form;
}

/**
 *
 */
function gttn_tpps_reference_search_callback($form, $form_state) {
  return array(
    'data_type' => $form['data_type'],
    'attribute' => $form['attribute'],
    'value' => $form['value'],
  );
}
