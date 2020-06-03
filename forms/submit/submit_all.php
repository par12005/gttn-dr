<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_submit_all($accession) {
  $form_state = gttn_tpps_load_submission($accession);
  $form_state['status'] = 'Submission Job Running';
  gttn_tpps_update_submission($form_state, array('status' => 'Submission Job Running'));
  gttn_tpps_submission_clear_db($accession);
  $transaction = db_transaction();

  try {
    $form_state = gttn_tpps_load_submission($accession);
    $form_state['file_rank'] = 0;
    $form_state['ids'] = array();

    gttn_tpps_submit_project($form_state);

    gttn_tpps_submit_organism($form_state);

    gttn_tpps_submit_trees($form_state);

    if (!empty($form_state['saved_values'][GTTN_PAGE_4]['dart'])) {
      $ref_data_provided = TRUE;
      if (!$form_state['saved_values'][GTTN_PAGE_4]['dart']['meta_only']) {
        gttn_tpps_submit_dart($form_state);
      }
      else {
        $ref_data_provided = FALSE;
      }
    }

    if (!empty($form_state['saved_values'][GTTN_PAGE_4]['isotope'])) {
      $ref_data_provided = $ref_data_provided ?? TRUE;
      if (!$form_state['saved_values'][GTTN_PAGE_4]['isotope']['meta_only']) {
        gttn_tpps_submit_isotope($form_state);
      }
      else {
        $ref_data_provided = FALSE;
      }
    }

    if (!empty($form_state['saved_values'][GTTN_PAGE_4]['genetic'])) {
      $ref_data_provided = $ref_data_provided ?? TRUE;
      if (!$form_state['saved_values'][GTTN_PAGE_4]['genetic']['meta_only']) {
        gttn_tpps_submit_genetic($form_state);
      }
      else {
        $ref_data_provided = FALSE;
      }
    }

    if (!empty($form_state['saved_values'][GTTN_PAGE_4]['anatomy'])) {
      $ref_data_provided = $ref_data_provided ?? TRUE;
      if (!$form_state['saved_values'][GTTN_PAGE_4]['anatomy']['meta_only']) {
        // TODO.
      }
      else {
        $ref_data_provided = FALSE;
      }
    }

    $form_state['data']['reference_provided'] = $ref_data_provided ?? FALSE;
    $form_state['data']['samples_sharable'] = $form_state['saved_values'][GTTN_PAGE_3]['samples']['sharable'];

    //throw new Exception('Submission Completed');
    $form_state['status'] = 'Approved';
    gttn_tpps_update_submission($form_state);
  }
  catch (\Exception $e) {
    $transaction->rollback();
    $form_state = gttn_tpps_load_submission($accession);
    $form_state['status'] = 'Pending Approval';
    gttn_tpps_update_submission($form_state, array('status' => 'Pending Approval'));
    watchdog_exception('gttn_tpps', $e);
  }
}

/**
 *
 */
function gttn_tpps_submit_project(&$state) {
  $project = &$state['data']['project'];
  $project_id = gttn_tpps_chado_insert_record('project', array(
    'name' => $project['name'],
    'description' => $project['description'],
  ));
  $state['ids']['project_id'] = $project_id;
  $project['id'] = $project_id;
  $props = $project['props'];

  db_insert('gttn_tpps_organization_project')
    ->fields(array(
      'project_id' => $project_id,
      'organization_id' => $state['saved_values'][GTTN_TYPE_PAGE]['project']['props']['organization'],
    ))
    ->execute();

  gttn_tpps_insert_prop('project', $project_id, 'analysis_date', array(
    $project['props']['analysis_date'],
  ));

  $dois = array();
  foreach ($props['pub_doi'] as $doi) {
    if (!empty($doi)) {
      $dois[] = $doi;
    }
  }
  if (!empty($props['data_doi'])) {
    $dois[] = $props['data_doi'];
  }
  if (!empty($dois)) {
    gttn_tpps_insert_prop('project', $project_id, 'DOI', $dois);
  }

  if (!empty($props['db_url'])) {
    gttn_tpps_insert_prop('project', $project_id, 'URL', $props['db_url'], array(
      'cv' => 'tripal_pub',
    ));
  }

  if (!empty($props['project_name'])) {
    gttn_tpps_insert_prop('project', $project_id, 'project name', $props['project_name']);
  }

  if (!empty($props['type'])) {
    gttn_tpps_insert_prop('project', $project_id, 'Project Type', $props['type']);
  }

  $types = array();
  foreach ($props['data_type'] as $type) {
    if (!empty($type)) {
      $types[] = $type;
    }
  }
  gttn_tpps_insert_prop('project', $project_id, 'Data Type', $types, array(
    'cv' => 'ncit',
  ));

  gttn_tpps_chado_insert_record('project_dbxref', array(
    'project_id' => $project_id,
    'dbxref_id' => $state['dbxref_id'],
  ));

  $user_name = user_load($state['owner_uid'])->chado_record->name;
  $pyear = $state['saved_values'][GTTN_TYPE_PAGE]['project']['props']['analysis_date']['year'];
  $pub_uniquename = "{$project['name']}, $user_name, $pyear";

  $pub_id = gttn_tpps_chado_insert_record('pub', array(
    'title' => $project['name'],
    'pyear' => $pyear,
    'uniquename' => $pub_uniquename,
    'type_id' => array(
      'name' => 'null',
    ),
  ));

  $state['ids']['pub_id'] = $pub_id;

  $parts = explode(' ', $user_name);
  $surname = end($parts);
  $givennames = array_slice($parts, 0, -1);

  gttn_tpps_chado_insert_record('pubauthor', array(
    'pub_id' => $pub_id,
    'rank' => 0,
    'surname' => $surname,
    'givennames' => $givennames,
  ));

  // TODO: permissions should go in a db table.
}

/**
 *
 */
function gttn_tpps_submit_organism(&$state) {
  $organisms = $state['data']['organism'];
  $org_type = array(
    'name' => 'organism',
    'is_obsolete' => 0,
    'cv_id' => array(
      'name' => 'obi',
    ),
  );

  $state['ids']['organism_ids'] = array();
  foreach ($organisms as $id => $info) {
    $genus = $info['genus'];
    $species = $info['species'];

    $state['ids']['organism_ids'][$id] = db_select('chado.organism', 'o')
      ->fields('o', array('organism_id'))
      ->condition('genus', $genus)
      ->condition('species', $species)
      ->condition('type_id', chado_get_cvterm($org_type)->cvterm_id)
      ->range(0, 1)
      ->execute()->fetchObject()->organism_id ?? NULL;

    // We promised to add species on First page, if we do not have it
    // now it's time to do ;)
    if (empty($state['ids']['organism_ids'][$id])) {
      $state['ids']['organism_ids'][$id] = gttn_tpps_chado_insert_record('organism', array(
        'genus' => $genus,
        'species' => $species,
        'type_id' => chado_get_cvterm($org_type)->cvterm_id,
      ));
    }

    $code_exists = gttn_tpps_chado_prop_exists('organism', $state['ids']['organism_ids'][$id], 'organism 4 letter code');

    if (!$code_exists) {
      $new_code = gttn_tpps_create_organism_code($genus, $species);

      gttn_tpps_chado_insert_record('organismprop', array(
        'organism_id' => $state['ids']['organism_ids'][$id],
        'type_id' => chado_get_cvterm(array('name' => 'organism 4 letter code'))->cvterm_id,
        'value' => $new_code,
      ));
    }

    gttn_tpps_chado_insert_record('project_organism', array(
      'organism_id' => $state['ids']['organism_ids'][$id],
      'project_id' => $state['ids']['project_id'],
    ));

    gttn_tpps_chado_insert_record('pub_organism', array(
      'organism_id' => $state['ids']['organism_ids'][$id],
      'pub_id' => $state['ids']['pub_id'],
    ));

    gttn_tpps_tripal_entity_publish('Organism', array("$genus $species", $state['ids']['organism_ids'][$id]));
  }
}

/**
 *
 */
function gttn_tpps_create_organism_code($genus, $species = "") {
  $genus = ucfirst($genus);
  $species = strtolower($species);
  for ($g_idx_1 = 0; $g_idx_1 < strlen($genus) - 1; $g_idx_1++) {
    for ($g_idx_2 = $g_idx_1 + 1; $g_idx_2 < strlen($genus); $g_idx_2++) {
      $sp = !empty($species) ? $species : substr($genus, $g_idx_2 + 1);
      for ($s_idx_1 = 0; $s_idx_1 < strlen($sp) - 1; $s_idx_1++) {
        for ($s_idx_2 = $s_idx_1 + 1; $s_idx_2 < strlen($sp); $s_idx_2++) {
          $trial_code = "{$genus[$g_idx_1]}{$genus[$g_idx_2]}{$sp[$s_idx_1]}{$sp[$s_idx_2]}";
          $new_code_query = chado_select_record('organismprop', array('value'), array(
            'type_id' => array(
              'name' => 'organism 4 letter code',
            ),
            'value' => $trial_code,
          ));
          if (empty($new_code_query)) {
            return $trial_code;
          }
        }
      }
    }
  }
  throw new Exception("GTTN-TPPS was unable to create a 4 letter species code for the species '$genus $species'.");
}

/**
 *
 */
function gttn_tpps_submit_trees(&$state) {
  $accession = $state['accession'];
  $thirdpage = $state['saved_values'][GTTN_PAGE_3];
  $stock_count = 0;
  $record_group = variable_get('gttn_tpps_record_group', 10000);

  $cvterms = array(
    'org' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'obi',
      ),
      'name' => 'organism',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'has_part' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'sequence',
      ),
      'name' => 'has_part',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'lat' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'sio',
      ),
      'name' => 'latitude',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'lng' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'sio',
      ),
      'name' => 'longitude',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'country' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'tripal_contact',
      ),
      'name' => 'Country',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'state' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'tripal_contact',
      ),
      'name' => 'State',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'county' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'name' => 'County',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'district' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'name' => 'Locality',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'loc' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'nd_geolocation_property',
      ),
      'name' => 'Location',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'bar_code' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'name' => 'Barcode',
      'is_obsolete' => 0,
    ))->cvterm_id,
  );

  $records = array(
    'stock' => array(),
    'stockprop' => array(),
    'stock_relationship' => array(),
    'project_stock' => array(),
  );
  $overrides = array(
    'stock_relationship' => array(
      'subject' => array(
        'table' => 'stock',
        'columns' => array(
          'subject_id' => 'stock_id',
        ),
      ),
      'object' => array(
        'table' => 'stock',
        'columns' => array(
          'object_id' => 'stock_id',
        ),
      ),
    ),
  );

  $multi_insert_options = array(
    'fk_overrides' => $overrides,
    'fks' => 'stock',
    'entities' => array(
      'label' => 'Stock',
      'table' => 'stock',
      'prefix' => $state['accession'] . '-',
    ),
  );

  $project_id = $state['ids']['project_id'];
  $org_ids = $state['ids']['organism_ids'];
  $trees = &$state['data']['trees'];

  foreach ($trees as $tree) {
    $id = $tree['id'];
    $org_id = $org_ids[$tree['organism_number']];
    $records['stock'][$id] = array(
      'uniquename' => "$accession-$id",
      'type_id' => $cvterms['org'],
      'organism_id' => $org_id,
    );
    $trees[$id]['organism_id'] = $org_id;

    $records['project_stock'][$id] = array(
      'project_id' => $project_id,
      '#fk' => array(
        'stock' => $id,
      ),
    );

    if (!empty($tree['lat']) and !empty($tree['lng'])) {
      $records['stockprop']["$id-lat"] = array(
        'type_id' => $cvterms['lat'],
        'value' => $tree['lat'],
        '#fk' => array(
          'stock' => $id,
        ),
      );

      $records['stockprop']["$id-long"] = array(
        'type_id' => $cvterms['lng'],
        'value' => $tree['lng'],
        '#fk' => array(
          'stock' => $id,
        ),
      );
    }

    if (!empty($tree['state']) and !empty($tree['country'])) {
      $records['stockprop']["$id-country"] = array(
        'type_id' => $cvterms['country'],
        'value' => $tree['country'],
        '#fk' => array(
          'stock' => $id,
        ),
      );

      $records['stockprop']["$id-state"] = array(
        'type_id' => $cvterms['state'],
        'value' => $tree['state'],
        '#fk' => array(
          'stock' => $id,
        ),
      );

      if (!empty($tree['county'])) {
        $records['stockprop']["$id-county"] = array(
          'type_id' => $cvterms['county'],
          'value' => $tree['county'],
          '#fk' => array(
            'stock' => $id,
          ),
        );
      }

      if (!empty($tree['district'])) {
        $records['stockprop']["$id-district"] = array(
          'type_id' => $cvterms['district'],
          'value' => $tree['district'],
          '#fk' => array(
            'stock' => $id,
          ),
        );
      }
    }
    elseif (!empty($tree['location'])) {
      $records['stockprop']["$id-location"] = array(
        'type_id' => $cvterms['loc'],
        'value' => $tree['location'],
        '#fk' => array(
          'stock' => $id,
        ),
      );
    }

    if (!empty($tree['bar_code'])) {
      $records['stockprop']["$id-bar_code"] = array(
        'type_id' => $cvterms['bar_code'],
        'value' => $tree['bar_code'],
        '#fk' => array(
          'stock' => $id,
        ),
      );
    }

    $stock_count++;
    if ($stock_count >= $record_group) {
      $new_ids = gttn_tpps_chado_insert_multi($records, $multi_insert_options);
      foreach ($new_ids as $t_id => $stock_id) {
        $trees[$t_id]['stock_id'] = $stock_id;
      }

      $records = array(
        'stock' => array(),
        'stockprop' => array(),
        'stock_relationship' => array(),
        'project_stock' => array(),
      );
      $stock_count = 0;
    }
  }

  if ($stock_count) {
    $new_ids = gttn_tpps_chado_insert_multi($records, $multi_insert_options);
    foreach ($new_ids as $t_id => $stock_id) {
      $trees[$t_id]['stock_id'] = $stock_id;
    }
  }
  unset($records);

  if ($state['data']['project']['props']['type'] != 'New Trees') {
    gttn_tpps_matching_trees($state['ids']['project_id']);
  }

  // Submit samples.
  if (!empty($thirdpage['samples'])) {
    $samples = $thirdpage['samples'];
    $sample_count = 0;
    $record_group = variable_get('gttn_tpps_record_group', 10000);
    gttn_tpps_add_project_file($state, $samples['file']);
  
    $records = array(
      'stock' => array(),
      'stockprop' => array(),
      'project_stock' => array(),
    );

    $cvt = gttn_tpps_sample_cvterms();

    foreach ($state['data']['samples'] as $sample) {
      $sample_id = $sample['id'];
      $records['stock'][$sample_id] = array(
        'uniquename' => "$accession-$sample_id",
        'type_id' => $cvt['sample'],
        'organism_id' => gttn_tpps_source_get_organism($sample['source'], $state),
      );

      $date = $sample['date'];
      if (is_array($date)) {
        $sample['date'] = date("m/d/Y", strtotime($date['day'] . '-' . $date['month'] . '-' . $date['year']));
      }
      elseif (is_int($date)) {
        $sample['date'] = gttn_tpps_xlsx_translate_date($date);
      }
      $source = $sample['source'];

      foreach ($sample as $prop => $value) {
        if (isset($value) and !empty($cvt[$prop])) {
          $records['stockprop']["$sample_id-$prop"] = array(
            'type_id' => $cvt[$prop],
            'value' => $value,
            '#fk' => array(
              'stock' => $sample_id,
            ),
          );
        }
      }

      $records['stock_relationship'][$sample_id] = array(
        'type_id' => $cvt['has_part'],
        '#fk' => array(
          'object' => $sample_id,
        ),
      );
      if (!empty($trees[$source]['stock_id'])) {
        // Don't need to use #fk since the tree stock record has already been
        // created.
        $records['stock_relationship'][$sample_id]['subject_id'] = $trees[$source]['stock_id'];
      }
      else {
        // Need to use #fk since the sample stock record doesn't exist yet.
        $records['stock_relationship'][$sample_id]['#fk']['subject'] = $state['data']['samples'][$source]['id'];
      }

      $sample_count++;
      if ($sample_count >= $record_group) {
        $new_ids = gttn_tpps_chado_insert_multi($records, $multi_insert_options);
        foreach ($new_ids as $s_id => $stock_id) {
          $state['data']['samples'][$s_id]['stock_id'] = $stock_id;
        }

        $records = array(
          'stock' => array(),
          'stockprop' => array(),
          'stock_relationship' => array(),
          'project_stock' => array(),
        );
        $sample_count = 0;
      }
    }

    if ($sample_count > 0) {
      $new_ids = gttn_tpps_chado_insert_multi($records, $multi_insert_options);
      foreach ($new_ids as $s_id => $stock_id) {
        $state['data']['samples'][$s_id]['stock_id'] = $stock_id;
      }
      $sample_count = 0;
    }

    foreach ($state['data']['samples'] as $sample) {
      gttn_tpps_chado_insert_record('project_stock', array(
        'stock_id' => $sample['stock_id'],
        'project_id' => $project_id,
      ));

      db_insert('gttn_tpps_organization_inventory')
        ->fields(array(
          'sample_id' => $sample['stock_id'],
          'organization_id' => $state['saved_values'][GTTN_TYPE_PAGE]['project']['props']['organization'],
        ))
        ->execute();

      if (empty($sample['new_events'])) {
        db_insert('gttn_tpps_sample_event')
          ->fields(array(
            'sample_id' => $sample['stock_id'],
            'event_type_id' => $cvt['recorded event'],
            'timestamp' => date('c'),
            'project_id' => $project_id,
          ))
          ->execute();

        $date = $sample['date'];
        if (is_array($date)) {
          $date = date("m/d/Y", strtotime($date['day'] . '-' . $date['month'] . '-' . $date['year']));
        }
        elseif (is_int($date)) {
          $date = gttn_tpps_xlsx_translate_date($date);
        }

        db_insert('gttn_tpps_sample_event')
          ->fields(array(
            'sample_id' => $sample['stock_id'],
            'event_type_id' => $cvt['collected event'],
            'timestamp' => date('c', strtotime($date)),
            'project_id' => $project_id,
          ))
          ->execute();
      }
      else {
        foreach ($sample['new_events'] as $event) {
          $event['sample_id'] = $sample['stock_id'];
          $event['project_id'] = $project_id;
          $event['event_type_id'] = chado_get_cvterm(array(
            'name' => $event['event_type'],
            'is_obsolete' => 0,
          ))->cvterm_id;
          unset($event['event_type']);
          db_insert('gttn_tpps_sample_event')
            ->fields($event)
            ->execute();
        }
      }
    }
  }
}

/**
 *
 */
function gttn_tpps_submit_dart(&$state) {
  $dart = $state['saved_values'][GTTN_PAGE_4]['dart'];
  gttn_tpps_add_project_file($state, $dart['file']);
  $phenotype_ids = array();
  $spectra_ids = array();
  $record_group = variable_get('gttn_tpps_record_group', 10000);

  $records = array(
    'phenotype' => array(),
    'stock_phenotype' => array(),
    'phenotypeprop' => array(),
  );

  $cvterms = array(
    'dart' => chado_get_cvterm(array(
      'name' => 'direct analysis in real time',
      'cv_id' => array(
        'name' => 'chmo',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'collector' => chado_get_cvterm(array(
      'name' => 'specimen collector',
      'cv_id' => array(
        'name' => 'obi',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'dart_type' => chado_get_cvterm(array(
      'name' => 'type of DART',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'settings' => chado_get_cvterm(array(
      'name' => 'Device Parameters',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'lab' => chado_get_cvterm(array(
      'name' => 'Laboratory Vendor Name',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'cal_type' => chado_get_cvterm(array(
      'name' => 'calibration type',
      'is_obsolete' => 0,
    ))->cvterm_id,
  );

  $options = array(
    'accession' => $state['accession'],
    'no_header' => $dart['file-no-header'],
    'groups' => $dart['file-groups'],
    'cols' => $dart['file-columns'],
    'samples' => $state['data']['samples'],
    'records' => &$records,
    'suffix' => 0,
    'cvterms' => $cvterms,
    'record_count' => 0,
    'phenotype_ids' => &$phenotype_ids,
    'spectra_ids' => &$spectra_ids,
  );

  gttn_tpps_file_iterator($dart['file'], 'gttn_tpps_process_dart', $options);
  if ($options['record_count'] > 0) {
    $phenotype_ids += gttn_tpps_chado_insert_multi($records, array(
      'fks' => 'phenotype',
    ));
    $options['record_count'] = 0;
  }

  gttn_tpps_add_project_file($state, $dart['raw']);
  $measure_cvt = chado_get_cvterm(array(
    'name' => 'DART measure',
    'is_obsolete' => 0,
  ))->cvterm_id;

  $records = array(
    'phenotypeprop' => array(),
  );

  $record_count = 0;
  foreach ($state['data']['dart'] as $sample_id => $measurements) {
    $spectra_id = $spectra_ids[$sample_id];
    $phenotype_id = $phenotype_ids["{$state['accession']}-$sample_id-$spectra_id"];
    $rank = 0;
    foreach ($measurements as $info) {
      $val = "{$info['measure']}: {$info['value']}";
      $records['phenotypeprop'][] = array(
        'phenotype_id' => $phenotype_id,
        'type_id' => $measure_cvt,
        'value' => $val,
        'rank' => $rank++,
      );
      $record_count++;

      if ($record_count >= $record_group) {
        gttn_tpps_chado_insert_multi($records);
        $records = array(
          'phenotypeprop' => array(),
        );
        $record_count = 0;
      }
    }
  }

  if ($record_count > 0) {
    $fks = gttn_tpps_chado_insert_multi($records);
    $record_count = 0;
    unset($records);
  }
}

/**
 *
 */
function gttn_tpps_submit_isotope(&$state) {
  $iso = $state['saved_values'][GTTN_PAGE_4]['isotope'];

  gttn_tpps_add_project_file($state, $iso['file']);

  $cvterms = array(
    'isotope' => chado_get_cvterm(array(
      'name' => 'Isotope',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'std' => chado_get_cvterm(array(
      'name' => 'isotope standard',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'borer' => chado_get_cvterm(array(
      'name' => 'increment borer',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'type' => chado_get_cvterm(array(
      'name' => 'isotope type',
      'is_obsolete' => 0,
    ))->cvterm_id,
  );

  $records = array(
    'phenotype' => array(),
    'stock_phenotype' => array(),
    'phenotypeprop' => array(),
  );

  $core_len = FALSE;
  if ($iso['used_core']) {
    $core_len = $iso['core_len'];
  }

  $standards = array();
  $types = array();
  foreach ($iso['used'] as $name) {
    if (!empty($name)) {
      $standards[$name] = $iso[$name]['standard'];
      $types[$name] = ($iso[$name]['type'] == 1) ? 'Whole Wood' : 'Cellulose';
    }
  }

  $options = array(
    'records' => &$records,
    'accession' => $state['accession'],
    'core_len' => $core_len,
    'standards' => $standards,
    'types' => $types,
    'groups' => $iso['file-groups'],
    'record_count' => 0,
    'suffix' => 0,
    'cvterms' => $cvterms,
    'samples' => $state['data']['samples'],
    'format' => $iso['format'],
  );

  gttn_tpps_file_iterator($iso['file'], 'gttn_tpps_process_isotope', $options);
  if ($options['record_count'] > 0) {
    gttn_tpps_chado_insert_multi($records);
    $options['record_count'] = 0;
  }
}

/**
 *
 */
function gttn_tpps_submit_genetic(&$state) {
  $project_id = $state['ids']['project_id'];
  $genetic = $state['saved_values'][GTTN_PAGE_4]['genetic'];
  $markers = $genetic['marker'];
  $insert_markers = array();
  $genotype_count = 0;
  $genotype_total = 0;
  $seq_var_cvterm = chado_get_cvterm(array(
    'name' => 'sequence_variant',
    'cv_id' => array(
      'name' => 'sequence',
    ),
    'is_obsolete' => 0,
  ))->cvterm_id;

  foreach ($state['data']['organism'] as $num => $info) {
    // Get species codes.
    $id = $state['ids']['organism_ids'][$num];
    $species_codes[$id] = current(chado_select_record('organismprop', array('value'), array(
      'type_id' => chado_get_cvterm(array(
        'name' => 'organism 4 letter code',
        'is_obsolete' => 0,
      ))->cvterm_id,
      'organism_id' => $id,
    ), array(
      'limit' => 1,
    )))->value;
  }

  $overrides = array(
    'genotype_call' => array(
      'variant' => array(
        'table' => 'feature',
        'columns' => array(
          'variant_id' => 'feature_id',
        ),
      ),
      'marker' => array(
        'table' => 'feature',
        'columns' => array(
          'marker_id' => 'feature_id',
        ),
      ),
    ),
  );

  $records = array(
    'feature' => array(),
    'genotype' => array(),
    'genotype_call' => array(),
    'stock_genotype' => array(),
  );

  $multi_insert_options = array(
    'fk_overrides' => $overrides,
  );

  $options = array(
    'records' => $records,
    'tree_info' => $state['data']['trees'],
    'species_codes' => $species_codes,
    'genotype_count' => $genotype_count,
    'genotype_total' => &$genotype_total,
    'project_id' => $project_id,
    'seq_var_cvterm' => $seq_var_cvterm,
    'overrides' => $overrides,
    'multi_insert' => $multi_insert_options,
  );

  if (!empty($markers['SNPs'])) {
    $insert_markers[] = 'SNP';
    $source = $genetic['snps_source'];

    gttn_tpps_insert_prop('project', $project_id, 'SNPs source', $source);

    if ($source === 'GBS') {
      $type = $genetic['gbs_type'];
      if ($type === 'Other') {
        $type = $genetic['other_gbs'];
      }

      gttn_tpps_insert_prop('project', $project_id, 'GBS type', $type);

      gttn_tpps_insert_prop('project', $project_id, 'GBS Machine', $genetic['gbs_machine']);

      if (isset($genetic['gbs_reference']) and $genetic['gbs_reference'] != 'manual') {
        gttn_tpps_insert_prop('project', $project_id, 'GBS Reference', $genetic['gbs_reference']);
      }
      else {
        gttn_tpps_add_project_file($state, $genetic['manual_reference']);
      }

      gttn_tpps_add_project_file($state, $genetic['gbs_align']);

      gttn_tpps_add_project_file($state, $genetic['vcf']);
    }

    if ($source === 'Assay') {
      gttn_tpps_insert_prop('project', $project_id, 'Assay source', $genetic['assay_source']);

      gttn_tpps_add_project_file($state, $genetic['assay_design_file']);

      gttn_tpps_add_project_file($state, $genetic['assay_genotype_table']);
      // TODO.
    }
  }

  if (!empty($markers['SSRs/cpSSRs'])) {
    $insert_markers[] = 'SSR';
    gttn_tpps_insert_prop('project', $project_id, 'SSR Machine', $genetic['ssr_machine']);

    $ssr_fid = $genetic['ssr_spreadsheet'];
    gttn_tpps_add_project_file($state, $ssr_fid);

    $options['type'] = 'ssrs';
    $options['headers'] = gttn_tpps_ssrs_headers($ssr_fid, $genetic['ploidy']);
    $options['marker'] = 'SSR';
    $options['type_cvterm'] = chado_get_cvterm(array(
      'name' => 'microsatellite',
      'cv_id' => array(
        'name' => 'sequence',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    gttn_tpps_file_iterator($ssr_fid, 'gttn_tpps_process_ssr_spreadsheet', $options);

    gttn_tpps_chado_insert_multi($options['records'], $multi_insert_options);
    unset($options['records']);
    $genotype_count = 0;
  }

  if (!empty($markers['Other'])) {
    $insert_markers[] = $genetic['other-marker'];
  }

  gttn_tpps_insert_prop('project', $project_id, 'Genetic Marker', $insert_markers, array(
    'cv' => 'ncit',
  ));

  gttn_tpps_insert_prop('project', $project_id, 'quality_value', $genetic['quality'], array(
    'cv' => 'sequence',
  ));
}

/**
 * DEPRECATED
 */
function gttn_tpps_submit_page_4(&$form_state, $project_id, &$file_rank, $organism_ids) {
  $fourthpage = $form_state['saved_values'][GTTN_PAGE_4];
  $organism_number = $form_state['saved_values'][GTTN_PAGE_1]['organism']['number'];

  for ($i = 1; $i <= $organism_number; $i++) {
    if (isset($fourthpage["organism-$i"]['genotype'])) {
      $ref_genome = $fourthpage["organism-$i"]['genotype']['ref-genome'];

      if ($ref_genome === 'url' or $ref_genome === 'manual' or $ref_genome === 'manual2') {
        // Create job for tripal fasta importer.
        $class = 'FASTAImporter';
        tripal_load_include_importer_class($class);

        $fasta = $fourthpage["organism-$i"]['genotype']['tripal_fasta'];

        $file_upload = isset($fasta['file']['file_upload']) ? trim($fasta['file']['file_upload']) : 0;
        $file_existing = isset($fasta['file']['file_upload_existing']) ? trim($fasta['file']['file_upload_existing']) : 0;
        $file_remote = isset($fasta['file']['file_remote']) ? trim($fasta['file']['file_remote']) : 0;
        $analysis_id = $fasta['analysis_id'];
        $seqtype = $fasta['seqtype'];
        $organism_id = $organism_ids[$i];
        $re_accession = $fasta['db']['re_accession'];
        $db_id = $fasta['db']['db_id'];

        $run_args = array(
          'importer_class' => $class,
          'file_remote' => $file_remote,
          'analysis_id' => $analysis_id,
          'seqtype' => $seqtype,
          'organism_id' => $organism_id,
          'method' => '2',
          'match_type' => '0',
          're_name' => '',
          're_uname' => '',
          're_accession' => $re_accession,
          'db_id' => $db_id,
          'rel_type' => '',
          're_subject' => '',
          'parent_type' => '',
        );

        $file_details = array();

        if ($file_existing) {
          $file_details['fid'] = $file_existing;
        }
        elseif ($file_upload) {
          $file_details['fid'] = $file_upload;
        }
        elseif ($file_remote) {
          $file_details['file_remote'] = $file_remote;
        }

        try {
          $importer = new $class();
          $form = array();
          $importer->formSubmit($form, $form_state);

          $importer->create($run_args, $file_details);

          $importer->submitJob();

        }
        catch (Exception $ex) {
          drupal_set_message('Cannot submit import: ' . $ex->getMessage(), 'error');
        }
      }
    }
  }
}

/**
 * This function formats headers for a microsatellite spreadsheet.
 *
 * SSR/cpSSR spreadsheets will often have blank or duplicate headers, depending
 * on the ploidy of the organism they are meant for. This file standardizes the
 * headers for the spreadsheet so that they can be used with the
 * gttn_tpps_process_ssr_spreadsheet() function.
 *
 * @param int $fid
 *   The Drupal managed file id of the file.
 * @param string $ploidy
 *   The ploidy of the organism, as indicated by the user.
 *
 * @return array
 *   The array of standardized headers for the spreadsheet.
 */
function gttn_tpps_ssrs_headers($fid, $ploidy) {
  $headers = gttn_tpps_file_headers($fid);
  if ($ploidy == 'Haploid') {
    return $headers;
  }
  $row_len = count($headers);
  $results = $headers;

  while (($k = array_search(NULL, $results))) {
    unset($results[$k]);
  }

  $marker_num = 0;
  $first = TRUE;
  reset($headers);
  $num_headers = count($results);
  $num_unique_headers = count(array_unique($results));

  foreach ($headers as $key => $val) {
    next($headers);
    $next_key = key($headers);
    if ($first) {
      $first = FALSE;
      continue;
    }

    switch ($ploidy) {
      case 'Diploid':
        if ($num_headers == ($row_len + 1) / 2) {
          // Every other marker column name is left blank.
          if (array_key_exists($key, $results)) {
            $last = $results[$key];
            $results[$key] .= "_A";
            break;
          }
          $results[$key] = $last . "_B";
          break;
        }

        if ($num_headers == $row_len) {
          // All of the marker column names are filled out.
          if ($num_headers != $num_unique_headers) {
            // The marker column names are duplicates, need to append
            // _A and _B.
            if ($results[$key] == $results[$next_key]) {
              $results[$key] .= "_A";
              break;
            }
            $results[$key] .= "_B";
          }
        }
        break;

      case 'Polyploid':
        if ($num_headers == $row_len) {
          // All of the marker column names are filled out.
          if ($num_unique_headers != $num_headers) {
            // The marker column names are duplicates, need to append
            // _1, _2, up to X ploidy.
            // The total number of headers divided by the number of
            // unique headers should be equal to the ploidy.
            $ploidy_suffix = ($marker_num % ($num_headers - 1 / $num_unique_headers - 1)) + 1;
            $results[$key] .= "_$ploidy_suffix";
          }
          $marker_num++;
          break;
        }
        $ploidy_suffix = ($marker_num % ($row_len - 1 / $num_headers - 1)) + 1;
        if (array_key_exists($key, $results)) {
          $last = $results[$key];
          $results[$key] .= "_$ploidy_suffix";
        }
        else {
          $results[$key] = "{$last}_$ploidy_suffix";
        }
        $marker_num++;
        break;

      default:
        break;
    }
  }

  return $results;

}

/**
 * This function processes a single row of an ssr spreadsheet.
 *
 * @param mixed $row
 *   The item yielded by the GTTN-TPPS file generator.
 * @param array $options
 *   Additional options set when calling gttn_tpps_file_iterator().
 */
function gttn_tpps_process_ssr_spreadsheet($row, &$options) {
  $type = $options['type'];
  $records = &$options['records'];
  $headers = $options['headers'];
  $tree_info = &$options['tree_info'];
  $species_codes = $options['species_codes'];
  $genotype_count = &$options['genotype_count'];
  $genotype_total = &$options['genotype_total'];
  $project_id = $options['project_id'];
  $marker = $options['marker'];
  $type_cvterm = $options['type_cvterm'];
  $seq_var_cvterm = $options['seq_var_cvterm'];
  $multi_insert_options = $options['multi_insert'];
  $record_group = variable_get('gttn_tpps_record_group', 10000);
  $stock_id = NULL;
  if ($type == 'other') {
    $val = $row[$options['tree_id']];
    $stock_id = $tree_info[trim($val)]['stock_id'];
    $current_id = $tree_info[trim($val)]['organism_id'];
    $species_code = $species_codes[$current_id];
  }
  foreach ($row as $key => $val) {
    if (empty($headers[$key])) {
      continue;
    }

    if (!isset($stock_id)) {
      $stock_id = $tree_info[trim($val)]['stock_id'];
      $current_id = $tree_info[trim($val)]['organism_id'];
      $species_code = $species_codes[$current_id];
      continue;
    }
    $genotype_count++;

    if ($type == 'ssrs' and ($val === 0 or $val === "0")) {
      $val = "NA";
    }

    $variant_name = $headers[$key];
    $marker_name = $variant_name . $marker;
    $genotype_name = "$marker-$variant_name-$species_code-$val";

    $records['feature'][$marker_name] = array(
      'organism_id' => $current_id,
      'uniquename' => $marker_name,
      'type_id' => $seq_var_cvterm,
    );

    $records['feature'][$variant_name] = array(
      'organism_id' => $current_id,
      'uniquename' => $variant_name,
      'type_id' => $seq_var_cvterm,
    );

    $records['genotype'][$genotype_name] = array(
      'name' => $genotype_name,
      'uniquename' => $genotype_name,
      'description' => $val,
      'type_id' => $type_cvterm,
    );

    $records['genotype_call']["$stock_id-$genotype_name"] = array(
      'project_id' => $project_id,
      'stock_id' => $stock_id,
      '#fk' => array(
        'genotype' => $genotype_name,
        'variant' => $variant_name,
        'marker' => $marker_name,
      ),
    );

    $records['stock_genotype']["$stock_id-$genotype_name"] = array(
      'stock_id' => $stock_id,
      '#fk' => array(
        'genotype' => $genotype_name,
      ),
    );

    if ($genotype_count >= $record_group) {
      gttn_tpps_chado_insert_multi($records, $multi_insert_options);
      $records = array(
        'feature' => array(),
        'genotype' => array(),
        'genotype_call' => array(),
        'stock_genotype' => array(),
      );
      $genotype_total += $genotype_count;
      $genotype_count = 0;
    }
  }

}

/**
 * Processes one row of a supplied DART file.
 *
 * @param mixed $row
 *   The value yielded by tpps_file_generator().
 * @param array $options
 *   The supplied options to tpps_file_iterator().
 */
function gttn_tpps_process_dart($row, array &$options) {
  $accession = $options['accession'];
  $records = &$options['records'];
  $samples = $options['samples'];
  $cols = $options['cols'];
  $groups = $options['groups'];
  $suffix = &$options['suffix'];
  $cvterms = $options['cvterms'];
  $record_count = &$options['record_count'];
  $record_group = variable_get('gttn_tpps_record_group', 10000);

  $lab_col = $groups['Lab Name']['1'];
  $spectra_col = $groups['Spectra Id']['2'];
  $sample_col = ($groups['Sample Id']['#type'] == 'internal') ? $groups['Sample Id']['3'] : $groups['Sample Id']['4'];
  $settings_col = $groups['Parameter Settings']['7'];
  $collector_col = array_search('5', $cols);
  $type_col = array_search('6', $cols);
  $cal_type_col = array_search('8', $cols);

  $spectra_id = $row[$spectra_col];
  $sample_id = $row[$sample_col];
  $dart_name = "$accession-$sample_id-$spectra_id";
  $options['spectra_ids'][$sample_id] = $spectra_id;

  if (!array_key_exists($sample_id, $samples)) {
    throw new Exception("Error: Sample ID $sample_id not found in sample data.");
  }

  $records['phenotype'][$dart_name] = array(
    'uniquename' => $dart_name,
    'name' => 'DART',
    'attr_id' => $cvterms['dart'],
    'value' => $spectra_id,
  );

  $records['stock_phenotype'][$dart_name] = array(
    'stock_id' => $samples[$sample_id]['stock_id'],
    '#fk' => array(
      'phenotype' => $dart_name,
    ),
  );

  $records['phenotypeprop']["$dart_name-settings"] = array(
    'type_id' => $cvterms['settings'],
    'value' => $row[$settings_col],
    '#fk' => array(
      'phenotype' => $dart_name,
    ),
  );

  $records['phenotypeprop']["$dart_name-lab"] = array(
    'type_id' => $cvterms['lab'],
    'value' => $row[$lab_col],
    '#fk' => array(
      'phenotype' => $dart_name,
    ),
  );

  if (!empty($collector_col) and !empty($row[$collector_col])) {
    $records['phenotypeprop']["$dart_name-gatherer"] = array(
      'type_id' => $cvterms['collector'],
      'value' => $row[$collector_col],
      '#fk' => array(
        'phenotype' => $dart_name,
      ),
    );
  }

  if (!empty($type_col) and !empty($row[$type_col])) {
    $records['phenotypeprop']["$dart_name-type"] = array(
      'type_id' => $cvterms['dart_type'],
      'value' => $row[$type_col],
      '#fk' => array(
        'phenotype' => $dart_name,
      ),
    );
  }

  if (!empty($cal_type_col) and !empty($row[$cal_type_col])) {
    $records['phenotypeprop']["$dart_name-calibration_type"] = array(
      'type_id' => $cvterms['cal_type'],
      'value' => $row[$cal_type_col],
      '#fk' => array(
        'phenotype' => $dart_name,
      ),
    );
  }

  $record_count++;
  if ($record_count >= $record_group) {
    $options['phenotype_ids'] += gttn_tpps_chado_insert_multi($records, array(
      'fks' => 'phenotype',
    ));
    $records = array(
      'phenotype' => array(),
      'phenotypeprop' => array(),
      'stock_phenotype' => array(),
    );
    $record_count = 0;
  }
  $suffix++;
}

/**
 *
 */
function gttn_tpps_process_isotope($row, array &$options) {
  $records = &$options['records'];
  $accession = $options['accession'];
  $groups = $options['groups'];
  $samples = $options['samples'];
  $standards = $options['standards'];
  $types = $options['types'];
  $suffix = &$options['suffix'];
  $count = &$options['record_count'];
  $cvterms = $options['cvterms'];
  $record_group = variable_get('gttn_tpps_record_group', 10000);

  $sample_id = $row[$groups['Sample ID']['1']];
  if ($options['format'] == 'type_1') {
    unset($groups['Sample ID']);
  }
  if ($options['format'] == 'type_2') {
    $iso_col = $groups['Isotope']['2'];
    $val_col = $groups['Value']['3'];
    $iso_name = $row[$iso_col];
    $groups = array(
      $iso_name => array(
        '#type' => $iso_name,
        $val_col,
      ),
    );
  }
  foreach ($groups as $name => $col_info) {
    $isotope = $col_info['#type'];
    unset($col_info['#type']);
    $col_id = current($col_info);
    $isotope_name = "$accession-$sample_id-$name-$suffix";
    $records['phenotype'][$isotope_name] = array(
      'uniquename' => $isotope_name,
      'name' => $name,
      'value' => $row[$col_id],
      'attr_id' => $cvterms['isotope'],
    );

    $records['stock_phenotype'][$isotope_name] = array(
      'stock_id' => $samples[$sample_id]['stock_id'],
      '#fk' => array(
        'phenotype' => $isotope_name,
      ),
    );

    if ($options['core_len'] !== FALSE) {
      $records['phenotypeprop']["$isotope_name-core_len"] = array(
        'type_id' => $cvterms['borer'],
        'value' => $options['core_len'],
        '#fk' => array(
          'phenotype' => $isotope_name,
        ),
      );
    }

    $records['phenotypeprop']["$isotope_name-standard"] = array(
      'type_id' => $cvterms['std'],
      'value' => $standards[$isotope],
      '#fk' => array(
        'phenotype' => $isotope_name,
      ),
    );

    $records['phenotypeprop']["$isotope_name-type"] = array(
      'type_id' => $cvterms['type'],
      'value' => $types[$isotope],
      '#fk' => array(
        'phenotype' => $isotope_name,
      ),
    );

    // TODO.
    $count++;
    if ($count >= $record_group) {
      gttn_tpps_chado_insert_multi($records);
      $records = array(
        'phenotype' => array(),
        'phenotypeprop' => array(),
        'stock_phenotype' => array(),
      );
      $count = 0;
    }
    $suffix++;
  }
}
