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

  dpm($type);
  dpm($id);
  $output = 'Coming Soon!';

  /*
    function gttn_profile_reference_display() {
      global $user;

      $per_page = 20;

      $subs = db_select('variable', 'v')
        ->fields('v', array('name'))
        ->condition('name', db_like('gttn_tpps_complete_') . '%', 'LIKE')
        ->execute();

      $rows = array();
      while (($sub = $subs->fetchObject())) {
        preg_match('/gttn_tpps_complete_(.*)GTTN-TGDR[0-9]{3}/', $sub->name, $matches);
        $submit_user = user_load_by_mail($matches[1]);

        $state = variable_get($sub->name);
        $perms = $state['saved_values'][GTTN_TYPE_PAGE]['permissions'];
        $access = FALSE;
        foreach ($perms as $org_id => $val) {
          if (!empty($val)) {
            $and = db_and()
              ->condition('organization_id', $org_id)
              ->condition('uid', $user->uid)
              ->condition('status', 1);
            $query = db_select('gttn_profile_organization_members', 'm')
              ->fields('m', array('organization_id'))
              ->condition($and)
              ->execute();
            if (!empty($query->fetchObject()->organization_id)) {
              $access = TRUE;
              break;
            }
          }
        }

        if ($access) {
          $accession = $state['accession'];
          $type = $state['saved_values'][GTTN_TYPE_PAGE]['type'];
          $status = $state['status'];
          $perms = array();
          $species = array();
          foreach ($state['saved_values'][GTTN_TYPE_PAGE]['permissions'] as $org_id => $val) {
            if (!empty($val)) {
              $org_name = db_select('gttn_profile_organization', 'o')
                ->fields('o', array('name'))
                ->condition('organization_id', $org_id)
                ->execute()->fetchObject()->name;
              $perms[] = $org_name;
            }
          }
          $perms = implode(', ', $perms);
          $organism_number = $state['saved_values'][GTTN_PAGE_1]['organism']['number'];
          for ($i = 1; $i <= $organism_number; $i++) {
            $species[] = $state['saved_values'][GTTN_PAGE_1]['organism'][$i];
          }
          $species = implode(', ', $species);
          $data_type = $state['saved_values'][GTTN_PAGE_1]['dataType'];

          $row = array($accession, $type, $perms, $organism_number, $species, $data_type, $status);
          $rows[$accession] = $row;
        }
      }
      ksort($rows);

      $current_page = pager_default_initialize(count($rows), $per_page);
      $chunks = array_chunk($rows, $per_page, TRUE);
      $vars = array(
        'header' => array(
          'Accession number', 'Submission type', 'Organizations with access privileges', 'Number of species', 'Species studied', 'Data type', 'Approval status',
        ),
        'rows' => isset($chunks[$current_page]) ? $chunks[$current_page] : NULL,
        'attributes' => array(),
        'caption' => '',
        'colgroups' => NULL,
        'sticky' => FALSE,
        'empty' => '',
      );

      $output = theme_table($vars);

      $output = theme('pager', array('quantity', count($rows))) . $output;
      drupal_add_js(drupal_get_path('module', 'gttn_profile') . '/js/gttn_profile.js');
      drupal_add_js(drupal_get_path('module', 'gttn_profile') . '/js/block_elastic.js');
      return $output;
    }
  //*/

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
  //dpm($params);
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
  //dpm($rows);

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
          if (!empty($parts) and (preg_match("/$part/i", $info['genus']) or preg_match("/$part/i", $info['species']))) {
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
  // TODO
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
        'Data Types',
        'Submitting Organization'
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
      $query = db_select('gttn_profile_organization', 'o')
        ->fields('o', array('name'))
        ->condition('o.organization_id', $state['data']['project']['props']['organization'])
        ->range(0, 1)
        ->execute();
      $organization = $query->fetchObject()->name ?? NULL;
      $row = array(
        "<a href=\"$base_url/reference/submission/{$state['accession']}\">{$state['accession']}</a>",
        "<a href=\"$base_url/reference/submission/{$state['accession']}\">{$state['data']['project']['name']}</a>",
        implode('<br>', $species),
        implode('<br>', $data_types),
        $organization,
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
        'key' => $id,
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
  dpm($params);
  dpm(($form_state ?? ''));

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
