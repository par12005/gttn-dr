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

    // TODO.
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
 * DEPRECATED
 */
function gttn_tpps_submit_page_1(&$form_state, $project_id, &$file_rank) {

  $dbxref_id = $form_state['dbxref_id'];
  $firstpage = $form_state['saved_values'][GTTN_PAGE_1];

  gttn_tpps_create_record('project_dbxref', array(
    'project_id' => $project_id,
    'dbxref_id' => $dbxref_id,
  ));

  $organism_ids = array();
  $organism_number = $firstpage['organism']['number'];

  for ($i = 1; $i <= $organism_number; $i++) {
    $parts = explode(" ", $firstpage['organism'][$i]);
    $genus = $parts[0];
    $species = implode(" ", array_slice($parts, 1));
    if (isset($parts[2]) and ($parts[2] == 'var.' or $parts[2] == 'subsp.')) {
      $infra = implode(" ", array_slice($parts, 2));
    }
    else {
      $infra = NULL;
    }
    $organism_ids[$i] = gttn_tpps_create_record('organism', array(
      'genus' => $genus,
      'species' => $species,
      'infraspecific_name' => $infra,
    ));
    gttn_tpps_create_record('project_organism', array(
      'organism_id' => $organism_ids[$i],
      'project_id' => $project_id,
    ));
  }
  return $organism_ids;
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
