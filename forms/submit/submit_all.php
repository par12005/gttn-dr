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
  $project_id = $form_state['ids']['project_id'] ?? NULL;
  $transaction = db_transaction();

  try {
    $form_state = gttn_tpps_load_submission($accession);
    $values = $form_state['saved_values'];
    $form_state['file_rank'] = 0;
    $form_state['ids'] = array();

    gttn_tpps_submit_project($form_state);

    gttn_tpps_submit_organism($form_state);

    gttn_tpps_submit_trees($form_state);

    // TODO.
    throw new Exception('Submission Completed');
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

  gttn_tpps_insert_prop('project', $project_id, 'analysis_date', array(
    $project['props']['analysis_date'],
  ));

  $dois = array();
  if (!empty($props['pub_doi'])) {
    $dois[] = $props['pub_doi'];
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

  $user_name = gttn_profile_load_user($state['owner_uid'])->chado_record->name;
  $pyear = $state['saved_values'][GTTN_TYPE_PAGE]['project']['props']['analysis_date']['year'];
  $pub_uniquename = "{$project['name']}, $user_name, $pyear";
  if (!empty($props['pub_doi'])) {
    $pub_uniquename .= " {$props['pub_doi']}";
  }

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
      ->condition('type_id', tripal_get_cvterm($org_type)->cvterm_id)
      ->range(0, 1)
      ->execute()->fetchObject()->organism_id ?? NULL;

    $code_exists = gttn_tpps_chado_prop_exists('organism', $state['ids']['organism_ids'][$id], 'organism 4 letter code');

    if (!$code_exists) {
      $g_offset = 0;
      $s_offset = 0;
      do {
        if (isset($trial_code)) {
          if ($s_offset < strlen($species) - 2) {
            $s_offset++;
          }
          elseif ($g_offset < strlen($genus) - 2) {
            $s_offset = 0;
            $g_offset++;
          }
          else {
            throw new Exception("GTTN-TPPS was unable to create a 4 letter species code for the species '$genus $species'.");
          }
        }
        $trial_code = substr($genus, $g_offset, 2) . substr($species, $s_offset, 2);
        $new_code_query = chado_select_record('organismprop', array('value'), array(
          'type_id' => array(
            'name' => 'organism 4 letter code',
          ),
          'value' => $trial_code,
        ));
      } while (!empty($new_code_query));

      gttn_tpps_chado_insert_record('organismprop', array(
        'organism_id' => $state['ids']['organism_ids'][$id],
        'type_id' => chado_get_cvterm(array('name' => 'organism 4 letter code'))->cvterm_id,
        'value' => $trial_code,
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
function gttn_tpps_submit_trees(&$state) {
  $accession = $state['accession'];
  $firstpage = $state['saved_values'][GTTN_PAGE_1];
  $thirdpage = $state['saved_values'][GTTN_PAGE_3];
  $organism_number = $state['stats']['species_count'];
  $stock_count = 0;
  $loc_name = 'Location (latitude/longitude or country/state or population group)';

  $cvterms = array(
    'org' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'obi',
      ),
      'name' => 'organism',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'clone' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'sequence',
      ),
      'name' => 'clone',
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

  $options = array(
    'cvterms' => $cvterms,
    'records' => $records,
    'overrides' => $overrides,
    'locations' => &$state['locations'],
    'accession' => $state['accession'],
    'single_file' => empty($thirdpage['tree-accession']['check']),
    'org_names' => $firstpage['organism'],
    'saved_ids' => &$state['ids'],
    'stock_count' => &$stock_count,
    'multi_insert' => $multi_insert_options,
    'trees' => &$state['data']['trees'],
  );

  for ($i = 1; $i <= $organism_number; $i++) {
    $tree_accession = $thirdpage['tree-accession']["species-$i"];

    gttn_tpps_chado_insert_record('projectprop', array(
      'project_id' => $state['ids']['project_id'],
      'type_id' => array(
        'cv_id' => array(
          'name' => 'schema',
        ),
        'name' => 'url',
        'is_obsolete' => 0,
      ),
      'value' => file_create_url(file_load($tree_accession['file'])->uri),
      'rank' => $state['file_rank'],
    ));
    $state['file_rank']++;

    $column_vals = $tree_accession['file-columns'];
    $groups = $tree_accession['file-groups'];

    $options['org_num'] = $i;
    $options['no_header'] = !empty($tree_accession['file-no-header']);
    $options['empty'] = $tree_accession['file-empty'];
    $options['pop_group'] = $tree_accession['pop-group'];
    $county = array_search('8', $column_vals);
    $district = array_search('9', $column_vals);
    $clone = array_search('13', $column_vals);
    $options['column_ids'] = array(
      'id' => $groups['Tree Id']['1'],
      'lat' => $groups[$loc_name]['4'] ?? NULL,
      'lng' => $groups[$loc_name]['5'] ?? NULL,
      'country' => $groups[$loc_name]['2'] ?? NULL,
      'state' => $groups[$loc_name]['3'] ?? NULL,
      'county' => ($county !== FALSE) ? $county : NULL,
      'district' => ($district !== FALSE) ? $district : NULL,
      'clone' => ($clone !== FALSE) ? $clone : NULL,
      'pop_group' => $groups[$loc_name]['12'] ?? NULL,
    );

    if ($organism_number != 1 and empty($thirdpage['tree-accession']['check'])) {
      if ($groups['Genus and Species']['#type'] == 'separate') {
        $options['column_ids']['genus'] = $groups['Genus and Species']['6'];
        $options['column_ids']['species'] = $groups['Genus and Species']['7'];
      }
      else {
        $options['column_ids']['org'] = $groups['Genus and Species']['10'];
      }
    }

    gttn_tpps_file_iterator($tree_accession['file'], 'gttn_tpps_process_accession', $options);

    $new_ids = gttn_tpps_chado_insert_multi($options['records'], $multi_insert_options);
    foreach ($new_ids as $t_id => $stock_id) {
      $state['data']['trees'][$t_id]['stock_id'] = $stock_id;
    }
    unset($options['records']);
    $stock_count = 0;
    if (empty($thirdpage['tree-accession']['check'])) {
      break;
    }
  }

  if ($state['data']['project']['props']['type'] != 'New Trees') {
    gttn_tpps_matching_trees($state['ids']['project_id']);
  }

  // Submit samples.
  if (!empty($thirdpage['samples'])) {
    $samples = $thirdpage['samples'];
    $sample_count = 0;
    $record_group = variable_get('gttn_tpps_record_group', 10000);
    gttn_tpps_chado_insert_record('projectprop', array(
      'project_id' => $state['ids']['project_id'],
      'type_id' => array(
        'cv_id' => array(
          'name' => 'schema',
        ),
        'name' => 'url',
        'is_obsolete' => 0,
      ),
      'value' => file_create_url(file_load($samples['file'])->uri),
      'rank' => $state['file_rank'],
    ));
    $state['file_rank']++;
  
    $records = array(
      'stock' => array(),
      'stockprop' => array(),
      'project_stock' => array(),
    );

    $sample_cvt = tripal_get_cvterm(array(
      'name' => 'biological sample',
      'cv_id' => array(
        'name' => 'sep',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    $tissue_cvt = tripal_get_cvterm(array(
      'name' => 'Tissue',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    $dim_cvt = tripal_get_cvterm(array(
      'name' => 'Dimension',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    $date_cvt = tripal_get_cvterm(array(
      'name' => 'Collection Date',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    $collector_cvt = tripal_get_cvterm(array(
      'name' => 'specimen collector',
      'cv_id' => array(
        'name' => 'obi',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    $method_cvt = tripal_get_cvterm(array(
      'name' => 'Biospecimen Collection Method',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    $legal_cvt = tripal_get_cvterm(array(
      'name' => 'Legal',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id;

    $share_cvt = tripal_get_cvterm(array(
      'name' => 'shareable',
      'is_obsolete' => 0,
    ))->cvterm_id;

    foreach ($state['data']['samples'] as $sample) {
      $sample_id = $sample['id'];
      $records['stock'][$sample_id] = array(
        'uniquename' => "$accession-$sample_id",
        'type_id' => $sample_cvt,
        'organism_id' => gttn_tpps_source_get_organism($sample['source'], $state),
      );

      $records['stockprop']["$sample_id-tissue"] = array(
        'type_id' => $tissue_cvt,
        'value' => $sample['tissue'],
        '#fk' => array(
          'stock' => $sample_id,
        ),
      );

      $records['stockprop']["$sample_id-dim"] = array(
        'type_id' => $dim_cvt,
        'value' => $sample['dimension'],
        '#fk' => array(
          'stock' => $sample_id,
        ),
      );

      $records['stockprop']["$sample_id-date"] = array(
        'type_id' => $date_cvt,
        'value' => $sample['date'],
        '#fk' => array(
          'stock' => $sample_id,
        ),
      );

      $records['stockprop']["$sample_id-collector"] = array(
        'type_id' => $collector_cvt,
        'value' => $sample['collector'],
        '#fk' => array(
          'stock' => $sample_id,
        ),
      );

      $records['stockprop']["$sample_id-method"] = array(
        'type_id' => $method_cvt,
        'value' => $sample['method'],
        '#fk' => array(
          'stock' => $sample_id,
        ),
      );

      $records['stockprop']["$sample_id-legal"] = array(
        'type_id' => $legal_cvt,
        'value' => $sample['legal'],
        '#fk' => array(
          'stock' => $sample_id,
        ),
      );

      $records['stockprop']["$sample_id-share"] = array(
        'type_id' => $share_cvt,
        'value' => $sample['share'],
        '#fk' => array(
          'stock' => $sample_id,
        ),
      );

      $records['stock_relationship'][$sample_id] = array(
        'type_id' => $cvterms['has_part'],
        '#fk' => array(
          'object' => $sample_id,
        ),
      );
      if (!empty($state['data']['trees'][$sample['source']]['stock_id'])) {
        // Don't need to use #fk since the tree stock record has already been
        // created.
        $records['stock_relationship'][$sample_id]['subject_id'] = $state['data']['trees'][$sample['source']]['stock_id'];
      }
      else {
        // Need to use #fk since the sample stock record doesn't exist yet.
        $records['stock_relationship'][$sample_id]['#fk']['subject'] = $state['data']['samples'][$sample['source']]['id'];
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
  }
}

/**
 * DEPRECATED
 */
function gttn_tpps_submit_page_3(&$form_state, $project_id, &$file_rank, $organism_ids) {

  $firstpage = $form_state['saved_values'][GTTN_PAGE_1];
  $thirdpage = $form_state['saved_values'][GTTN_PAGE_3];
  $organism_number = $firstpage['organism']['number'];

  $stock_ids = array();
  $org_term_id = chado_get_cvterm(array(
    'name' => 'organism',
    'cv_id' => array(
      'name' => 'obi',
    ),
  ))->cvterm_id;
  $url_id = chado_get_cvterm(array(
    'name' => 'url',
    'cv_id' => array(
      'name' => 'schema',
    ),
  ))->cvterm_id;

  if ($organism_number == '1' or $thirdpage['tree-accession']['check'] == 0) {
    // Single file.
    gttn_tpps_create_record('projectprop', array(
      'project_id' => $project_id,
      'type_id' => $url_id,
      'value' => file_create_url(file_load($thirdpage['tree-accession']['file'])->uri),
      'rank' => $file_rank,
    ));

    $file = file_load($thirdpage['tree-accession']['file']);
    $location = drupal_realpath($file->uri);
    $content = gttn_tpps_parse_xlsx($location);
    $column_vals = $thirdpage['tree-accession']['file-columns'];
    $groups = $thirdpage['tree-accession']['file-groups'];

    foreach ($column_vals as $col => $val) {
      if ($val == '8') {
        $county_col_name = $col;
      }
      if ($val == '9') {
        $district_col_name = $col;
      }
    }

    $id_col_accession_name = $groups['Tree Id']['1'];

    if ($organism_number == '1') {
      // Only one species.
      for ($i = 0; $i < count($content) - 1; $i++) {
        $tree_id = $content[$i][$id_col_accession_name];
        $stock_ids[$tree_id] = gttn_tpps_create_record('stock', array(
          'uniquename' => t($tree_id),
          'type_id' => $org_term_id,
          'organism_id' => $organism_ids[1],
        ));
      }
    }
    else {
      // Multiple species in one tree accession file -> users must define species and genus columns
      // get genus/species column.
      if ($groups['Genus and Species']['#type'] == 'separate') {
        $genus_col_name = $groups['Genus and Species']['6'];
        $species_col_name = $groups['Genus and Species']['7'];
      }
      else {
        $org_col_name = $groups['Genus and Species']['10'];
      }

      // Parse file.
      for ($i = 0; $i < count($content) - 1; $i++) {
        $tree_id = $content[$i][$id_col_accession_name];
        for ($j = 1; $j <= $organism_number; $j++) {
          // Match genus and species to genus and species given on page 1.
          if ($groups['Genus and Species']['#type'] == 'separate') {
            $genus_full_name = "{$content[$i][$genus_col_name]} {$content[$i][$species_col_name]}";
          }
          else {
            $genus_full_name = "{$content[$i][$org_col_name]}";
          }

          if ($firstpage['organism'][$j] == $genus_full_name) {
            // Obtain organism id from matching species.
            $id = $organism_ids[$j];
            break;
          }
        }

        // Create record with the new id.
        $stock_ids[$tree_id] = gttn_tpps_create_record('stock', array(
          'uniquename' => t($tree_id),
          'type_id' => $org_term_id,
          'organism_id' => $id,
        ));
      }
    }

    if ($groups['Location (latitude/longitude or country/state)']['#type'] == 'gps') {
      $lat_name = $groups['Location (latitude/longitude or country/state)']['4'];
      $long_name = $groups['Location (latitude/longitude or country/state)']['5'];

      for ($i = 0; $i < count($content) - 1; $i++) {
        $tree_id = $content[$i][$id_col_accession_name];
        $stock_id = $stock_ids[$tree_id];

        gttn_tpps_create_record('stockprop', array(
          'stock_id' => $stock_id,
          'type_id' => '54718',
          'value' => $content[$i][$lat_name],
        ));

        gttn_tpps_create_record('stockprop', array(
          'stock_id' => $stock_id,
          'type_id' => '54717',
          'value' => $content[$i][$long_name],
        ));
      }
    }
    else {
      $country_col_name = $groups['Location (latitude/longitude or country/state)']['2'];
      $state_col_name = $groups['Location (latitude/longitude or country/state)']['3'];

      for ($i = 0; $i < count($content) - 1; $i++) {
        $tree_id = $content[$i][$id_col_accession_name];
        $stock_id = $stock_ids[$tree_id];

        gttn_tpps_create_record('stockprop', array(
          'stock_id' => $stock_id,
          'type_id' => '128162',
          'value' => $content[$i][$country_col_name],
        ));

        gttn_tpps_create_record('stockprop', array(
          'stock_id' => $stock_id,
          'type_id' => '128947',
          'value' => $content[$i][$state_col_name],
        ));

        if (isset($county_col_name)) {
          gttn_tpps_create_record('stockprop', array(
            'stock_id' => $stock_id,
            'type_id' => '128946',
            'value' => $content[$i][$county_col_name],
          ));
        }

        if (isset($district_col_name)) {
          gttn_tpps_create_record('stockprop', array(
            'stock_id' => $stock_id,
            'type_id' => '128945',
            'value' => $content[$i][$district_col_name],
          ));
        }
      }
    }

    $file->status = FILE_STATUS_PERMANENT;
    $file = file_save($file);
    $file_rank++;
  }
  else {
    // Multiple files, sorted by species.
    for ($i = 1; $i <= $organism_number; $i++) {
      gttn_tpps_create_record('projectprop', array(
        'project_id' => $project_id,
        'type_id' => $url_term_id,
        'value' => drupal_realpath(file_load($thirdpage['tree-accession']["species-$i"]['file'])->uri),
        'rank' => $file_rank,
      ));

      $file = file_load($thirdpage['tree-accession']["species-$i"]['file']);
      $location = drupal_realpath($file->uri);
      $content = gttn_tpps_parse_xlsx($location);
      $column_vals = $thirdpage['tree-accession']["species-$i"]['file-columns'];
      $groups = $thirdpage['tree-accession']["species-$i"]['file-groups'];

      $id_col_accession_name = $groups['Tree Id']['1'];

      foreach ($column_vals as $col => $val) {
        if ($val == '8') {
          $county_col_name = $col;
        }
        if ($val == '9') {
          $district_col_name = $col;
        }
      }

      for ($j = 0; $j < count($content) - 1; $j++) {
        $tree_id = $content[$j][$id_col_accession_name];
        $stock_ids[$tree_id] = gttn_tpps_create_record('stock', array(
          'uniquename' => t($tree_id),
          'type_id' => $org_term_id,
          'organism_id' => $organism_ids[$i],
        ));

        if ($groups['Location (latitude/longitude or country/state)']['#type'] == 'gps') {
          $lat_name = $groups['Location (latitude/longitude or country/state)']['4'];
          $long_name = $groups['Location (latitude/longitude or country/state)']['5'];

          gttn_tpps_create_record('stockprop', array(
            'stock_id' => $stock_ids[$tree_id],
            'type_id' => '54718',
            'value' => $content[$j][$lat_name],
          ));

          gttn_tpps_create_record('stockprop', array(
            'stock_id' => $stock_ids[$tree_id],
            'type_id' => '54717',
            'value' => $content[$j][$long_name],
          ));
        }
        else {
          $country_col_name = $groups['Location (latitude/longitude or country/state)']['2'];
          $state_col_name = $groups['Location (latitude/longitude or country/state)']['3'];

          gttn_tpps_create_record('stockprop', array(
            'stock_id' => $stock_id,
            'type_id' => '128162',
            'value' => $content[$j][$country_col_name],
          ));

          gttn_tpps_create_record('stockprop', array(
            'stock_id' => $stock_id,
            'type_id' => '128947',
            'value' => $content[$j][$state_col_name],
          ));

          if (isset($county_col_name)) {
            gttn_tpps_create_record('stockprop', array(
              'stock_id' => $stock_id,
              'type_id' => '128946',
              'value' => $content[$j][$county_col_name],
            ));
          }

          if (isset($district_col_name)) {
            gttn_tpps_create_record('stockprop', array(
              'stock_id' => $stock_id,
              'type_id' => '128945',
              'value' => $content[$j][$district_col_name],
            ));
          }
        }
      }

      $file->status = FILE_STATUS_PERMANENT;
      $file = file_save($file);
      $file_rank++;
    }
  }

  foreach ($stock_ids as $tree_id => $stock_id) {
    gttn_tpps_create_record('project_stock', array(
      'stock_id' => $stock_id,
      'project_id' => $project_id,
    ));
  }

  $form_state['file_rank'] = $file_rank;

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
 * 
 */
function gttn_tpps_process_accession($row, array &$options) {
  $cvterm = $options['cvterms'];
  $records = &$options['records'];
  $accession = $options['accession'];
  $cols = $options['column_ids'];
  $saved_ids = &$options['saved_ids'];
  $stock_count = &$options['stock_count'];
  $multi_insert_options = $options['multi_insert'];
  $trees = &$options['trees'];
  $record_group = variable_get('gttn_tpps_record_group', 10000);
  $geo_api_key = variable_get('gttn_tpps_geocode_api_key', NULL);

  $tree_id = $row[$cols['id']];
  $id = $saved_ids['organism_ids'][$options['org_num']];
  if ($options['org_names']['number'] != 1 and $options['single_file']) {
    $org_full_name = $row[$cols['org']] ?? "{$row[$cols['genus']]} {$row[$cols['species']]}";
    $id = $saved_ids['organism_ids'][array_search($org_full_name, $options['org_names'])];
  }

  $records['stock'][$tree_id] = array(
    'uniquename' => "$accession-$tree_id",
    'type_id' => $cvterm['org'],
    'organism_id' => $id,
  );
  $trees[$tree_id]['organism_id'] = $id;

  $records['project_stock'][$tree_id] = array(
    'project_id' => $saved_ids['project_id'],
    '#fk' => array(
      'stock' => $tree_id,
    ),
  );

  if (isset($row[$cols['clone']]) and $row[$cols['clone']] !== $options['empty']) {
    $clone_name = $tree_id . '-' . $row[$cols['clone']];

    $records['stock'][$clone_name] = array(
      'uniquename' => $accession . '-' . $clone_name,
      'type_id' => $cvterm['clone'],
      'organism_id' => $id,
    );
    $trees[$clone_name]['organism_id'] = $id;

    $records['project_stock'][$clone_name] = array(
      'project_id' => $saved_ids['project_id'],
      '#fk' => array(
        'stock' => $clone_name,
      ),
    );

    $records['stock_relationship'][$clone_name] = array(
      'type_id' => $cvterm['has_part'],
      '#fk' => array(
        'subject' => $tree_id,
        'object' => $clone_name,
      ),
    );

    $tree_id = $clone_name;
  }

  if (!empty($row[$cols['lat']]) and !empty($row[$cols['lng']])) {
    $raw_coord = $row[$cols['lat']] . ',' . $row[$cols['lng']];
    $standard_coord = explode(',', gttn_tpps_standard_coord($raw_coord));
    $lat = $standard_coord[0];
    $lng = $standard_coord[1];
  }
  elseif (!empty($row[$cols['state']]) and !empty($row[$cols['country']])) {
    $records['stockprop']["$tree_id-country"] = array(
      'type_id' => $cvterm['country'],
      'value' => $row[$cols['country']],
      '#fk' => array(
        'stock' => $tree_id,
      ),
    );

    $records['stockprop']["$tree_id-state"] = array(
      'type_id' => $cvterm['state'],
      'value' => $row[$cols['state']],
      '#fk' => array(
        'stock' => $tree_id,
      ),
    );

    $location = "{$row[$cols['state']]}, {$row[$cols['country']]}";

    if (!empty($row[$cols['county']])) {
      $records['stockprop']["$tree_id-county"] = array(
        'type_id' => $cvterm['county'],
        'value' => $row[$cols['county']],
        '#fk' => array(
          'stock' => $tree_id,
        ),
      );
      $location = "{$row[$cols['county']]}, $location";
    }

    if (!empty($row[$cols['district']])) {
      $records['stockprop']["$tree_id-district"] = array(
        'type_id' => $cvterm['district'],
        'value' => $row[$cols['district']],
        '#fk' => array(
          'stock' => $tree_id,
        ),
      );
      $location = "{$row[$cols['district']]}, $location";
    }

    $trees[$tree_id]['location'] = $location;

    if (isset($geo_api_key)) {
      if (!array_key_exists($location, $options['locations'])) {
        $query = urlencode($location);
        $url = "https://api.opencagedata.com/geocode/v1/json?q=$query&key=$geo_api_key";
        $response = json_decode(file_get_contents($url));

        if ($response->total_results) {
          $results = $response->results;
          $result = $results[0]->geometry;
          if ($response->total_results > 1 and !isset($cols['district']) and !isset($cols['county'])) {
            foreach ($results as $item) {
              if ($item->components->_type == 'state') {
                $result = $item->geometry;
                break;
              }
            }
          }
        }
        $options['locations'][$location] = $result ?? NULL;
      }
      else {
        $result = $options['locations'][$location];
      }

      if (!empty($result)) {
        $lat = $result->lat;
        $lng = $result->lng;
      }
    }
  }
  else {
    $location = $options['pop_group'][$row[$cols['pop_group']]];
    $coord = gttn_tpps_standard_coord($location);

    if ($coord) {
      $parts = explode(',', $coord);
      $lat = $parts[0];
      $lng = $parts[1];
    }
    else {
      $records['stockprop']["$tree_id-location"] = array(
        'type_id' => $cvterm['loc'],
        'value' => $location,
        '#fk' => array(
          'stock' => $tree_id,
        ),
      );

      $trees[$tree_id]['location'] = $location;

      if (isset($geo_api_key)) {
        if (!array_key_exists($location, $options['locations'])) {
          $query = urlencode($location);
          $url = "https://api.opencagedata.com/geocode/v1/json?q=$query&key=$geo_api_key";
          $response = json_decode(file_get_contents($url));
          $result = ($response->total_results) ? $response->results[0]->geometry : NULL;
          $options['locations'][$location] = $result;
        }
        else {
          $result = $options['locations'][$location];
        }

        if (!empty($result)) {
          $lat = $result->lat;
          $lng = $result->lng;
        }
      }
    }
  }

  if (!empty($lat) and !empty($lng)) {
    $records['stockprop']["$tree_id-lat"] = array(
      'type_id' => $cvterm['lat'],
      'value' => $lat,
      '#fk' => array(
        'stock' => $tree_id,
      ),
    );

    $records['stockprop']["$tree_id-long"] = array(
      'type_id' => $cvterm['lng'],
      'value' => $lng,
      '#fk' => array(
        'stock' => $tree_id,
      ),
    );
    $trees[$tree_id]['lat'] = $lat;
    $trees[$tree_id]['lng'] = $lng;
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

/**
 *
 */
function gttn_tpps_source_get_organism($id, $state) {
  if (!empty($state['data']['trees'][$id]['organism_id'])) {
    return $state['data']['trees'][$id]['organism_id'];
  }
  if (!empty($state['data']['samples'][$id]['source'])) {
    return gttn_tpps_source_get_organism($state['data']['samples'][$id]['source'], $state);
  }
  return NULL;
}
