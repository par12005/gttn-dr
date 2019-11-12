<?php

/**
 * @file
 */

/**
 * 
 */
function gttn_tpps_load_submission($accession, $state = TRUE) {
  $result = db_select('gttn_tpps_submission', 's')
    ->fields('s')
    ->condition('accession', $accession)
    ->range(0, 1)
    ->execute()->fetchObject();
  if ($result) {
    if ($state) {
      return unserialize($result->submission_state);
    }
    return $result;
  }
  return NULL;
}

/**
 * 
 */
function gttn_tpps_load_submission_multiple(array $conditions = array(), $state = TRUE) {
  $submissions = array();
  $query = db_select('gttn_tpps_submission', 's')
    ->fields('s', array('accession'));

  if (!empty($conditions)) {
    foreach ($conditions as $key => $cond) {
      if (!empty($cond)) {
        if (!is_array($cond)) {
          $query->condition($key, $cond);
        }
        else {
          $or = db_or();
          foreach ($cond as $item) {
            $or->condition($key, $item);
          }
          $query->condition($or);
        }
      }
    }
  }

  $results = $query->execute()->fetchAll();
  foreach ($results as $result) {
    $submissions[] = gttn_tpps_load_submission($result->accession, $state);
  }
  return $submissions;
}

/**
 * 
 */
function gttn_tpps_create_submission(array $state, $uid) {
  $values = array(
    'uid' => $uid,
    'status' => 'Incomplete',
    'accession' => $state['accession'],
    'dbxref_id' => $state['dbxref_id'],
    'submission_state' => serialize($state),
  );

  db_insert('gttn_tpps_submission')
    ->fields($values)
    ->execute();
}

/**
 * 
 */
function gttn_tpps_update_submission(array $state, array $options = array()) {
  if (empty($options['status']) and !empty($state['status'])) {
    $options['status'] = $state['status'];
  }
  $options['submission_state'] = serialize($state);

  db_update('gttn_tpps_submission')
    ->fields($options)
    ->condition('accession', $state['accession'])
    ->execute();
}

/**
 * 
 */
function gttn_tpps_delete_submission($accession, $redirect = TRUE) {
  global $user;
  $submission = gttn_tpps_load_submission($accession, FALSE);
  $dbxref_id = $submission->dbxref_id;
  $state = unserialize($submission->submission_state);
  db_delete('gttn_tpps_submission')
    ->condition('accession', $accession)
    ->execute();
  if ($redirect) {
    drupal_goto("user/{$user->uid}/gttn-tpps");
  }
}

/**
 * 
 */
function gttn_tpps_submission_rename_files($accession) {
  $state = gttn_tpps_load_submission($accession);
  foreach ($state['file_info'] as $page_files) {
    foreach ($page_files as $fid => $name) {
      $file = gttn_tpps_rename_file($fid, "{$accession}_{$name}");
      $file->status = FILE_STATUS_PERMANENT;
      file_save($file);
    }
  }
}

/**
 * 
 */
function gttn_tpps_submission_clear_db($accession) {
  $state = gttn_tpps_load_submission($accession);
  $state['file_rank'] = 0;
  $project_id = $state['ids']['project_id'] ?? NULL;
  if (empty($project_id)) {
    return FALSE;
  }

  $stocks_sql = "SELECT stock_id FROM chado.project_stock WHERE project_id = $project_id";
  chado_query("DELETE FROM chado.project_organism WHERE project_id = $project_id");
  chado_query("DELETE FROM chado.projectprop WHERE project_id = $project_id");
  chado_query("DELETE FROM chado.phenotype_cvterm WHERE phenotype_id IN (SELECT phenotype_id from chado.stock_phenotype WHERE stock_id in ($stocks_sql))");
  chado_query("DELETE FROM chado.phenotype WHERE phenotype_id IN (SELECT phenotype_id FROM chado.stock_phenotype WHERE stock_id IN ($stocks_sql))");
  chado_query("DELETE FROM chado.genotype_call WHERE project_id = $project_id");
  chado_query("DELETE FROM chado.stock_genotype WHERE stock_id IN ($stocks_sql)");
  chado_query("DELETE FROM chado.stock_phenotype WHERE stock_id IN ($stocks_sql)");
  chado_query("DELETE FROM chado.stock WHERE stock_id IN ($stocks_sql)");

  $state['ids'] = array('project_id' => $project_id);
  gttn_tpps_update_submission($state);
  return TRUE;
}
