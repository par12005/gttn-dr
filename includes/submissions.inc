<?php

/**
 * @file
 */

/**
 * 
 */
function gttn_tpps_load_submission($accession, $state = TRUE) {
  if (empty($accession)) {
    return NULL;
  }
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
  $state['owner_uid'] = $uid;
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
    foreach ($page_files as $info) {
      $fid = $info['fid'];
      $name = $info['name'];
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
  $transaction = db_transaction();

  try {
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
    chado_query("DELETE FROM chado.project WHERE project_id = $project_id");

    $state['ids'] = array('project_id' => $project_id);
    gttn_tpps_update_submission($state);
    return TRUE;
  }
  catch (\Exception $e) {
    $transaction->rollback();
    throw new \Exception("There was an error clearing the database for GTTN-TPPS submission number $accession: " . $e->getMessage());
    return FALSE;
  }
}

/**
 *
 */
function gttn_tpps_submission_permissions($state) {
  if (!is_array($state) and is_string($state)) {
    $state = gttn_tpps_load_submission($state);
  }

  $perms = $state['data']['project']['props']['permissions'] ?? array();
  $result = array();
  foreach ($perms as $perm) {
    if (!empty($perm)) {
      $result[] = $perm;
    }
  }
  return $result;
}

/**
 *
 */
function gttn_tpps_submission_access($state, $uid = NULL) {
  global $user;
  if (!is_array($state) and is_string($state)) {
    $state = gttn_tpps_load_submission($state) ?? NULL;
  }
  if (isset($uid) and is_int($uid)) {
    $user = user_load($uid);
  }

  if (empty($user) or empty($state)) {
    return FALSE;
  }

  $permissions = $state['data']['project']['props']['permissions'] ?? NULL;
  switch ($permissions) {
    case 'public':
      return TRUE;

    case 'gttn':
      return (bool) $uid;

    case 'law':
      $law = array_search('law enforcement', $user->roles);
      return !($law === FALSE);

    case 'org':
      $orgs = $state['data']['project']['props']['permission-orgs'] ?? array();
      foreach ($orgs as $org) {
        if (!empty($org)) {
          $members = gttn_profile_organization_get_members($org);
          foreach ($members as $member) {
            if ($user->uid == $member->uid) {
              return TRUE;
            }
          }
        }
      }
      return FALSE;

    case 'current':
      $members = gttn_profile_organization_get_members($state['data']['project']['props']['organization']);
      foreach ($members as $member) {
        if ($user->uid == $member->uid) {
          return TRUE;
        }
      }
      return FALSE;

    default:
      return FALSE;
  }
  return FALSE;
}

/**
 *
 */
function gttn_tpps_submission_approval_access($uid = NULL, $accession = NULL) {
  global $user;
  if (isset($uid) and is_int($uid)) {
    $user = user_load($uid);
  }

  if (empty($user)) {
    return FALSE;
  }

  if (gttn_tpps_access('administer gttn-tpps module')) {
    return TRUE;
  }

  // If accession was specified, check that user has approval access for that
  // submission.
  if (!empty($accession) and is_string($accession)) {
    $state = gttn_tpps_load_submission($accession);
    if (empty($state)) {
      return FALSE;
    }

    $org_id = $state['data']['project']['props']['organization'] ?? NULL;
    if (!empty($org_id)) {
      $contacts = gttn_profile_organization_primary_contact($org_id);
      foreach ($contacts as $contact) {
        if ($user->uid == $contact->uid) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  // Otherwise, check that user is a primary contact for at least one GTTN
  // organization.
  $user = user_load($user->uid);
  foreach ($user->organizations as $org_id) {
    $contacts = gttn_profile_organization_primary_contact($org_id);
    foreach ($contacts as $contact) {
      if ($user->uid == $contact->uid) {
        return TRUE;
      }
    }
  }

  return FALSE;
}

/**
 *
 */
function gttn_tpps_submission_data_indicators($state) {
  global $base_url;

  $data_indicators = '';
  if (isset($state['data']['reference_provided']) and isset($state['data']['samples_sharable'])) {
    $ref_indicator = $state['data']['reference_provided'] ? "$base_url/sites/all/modules/gttn-dr/images/unlock.png" : "$base_url/sites/all/modules/gttn-dr/images/lock.png";
    $ref_label = $state['data']['reference_provided'] ? "Both the metadata and the data for this submission are stored in the database" : "The metadata for this submission is stored in the database";
    $share_indicator = $state['data']['samples_sharable'] ? "$base_url/sites/all/modules/gttn-dr/images/sharable_small.png" : "$base_url/sites/all/modules/gttn-dr/images/non_sharable_small.png";
    $share_label = $state['data']['samples_sharable'] ? "Samples used in this submission can be requested/shared" : "Samples used in this submission cannot be requested/shared";
    $data_indicators = "<img src=\"$ref_indicator\" title=\"$ref_label\"> <img src=\"$share_indicator\" title=\"$share_label\">";
  }
  return $data_indicators;
}

/**
 *
 */
function gttn_tpps_submission_files_download($accession = NULL, $file = NULL) {
  $state = gttn_tpps_load_submission($accession);
  if (empty($state)) {
    throw new Exception('Invalid state accession number');
  }
  ob_end_clean();

  if (empty($file)) {
    $zip_name = "{$accession}_files.zip";
    if (!file_exists(drupal_realpath(GTTN_TPPS_TEMP_ZIP))) {
      mkdir(drupal_realpath(GTTN_TPPS_TEMP_ZIP));
    }
    $zip_loc = drupal_realpath(GTTN_TPPS_TEMP_ZIP) . '/' . $zip_name;

    foreach ($state['file_info'] as $files) {
      foreach ($files as $info) {
        $fid = $info['fid'];
        $file_str[] = str_replace(' ', '\\ ', drupal_realpath(file_load($fid)->uri));
      }
    }

    if (file_exists(drupal_realpath($zip_loc))) {
      unlink($zip_loc);
    }

    $file_str = implode(" ", $file_str);
    $cmd = "zip -j $zip_loc $file_str";
    exec($cmd);
    drupal_add_http_header('Content-Type', 'application/zip');
    drupal_add_http_header('Content-Disposition', 'attachment; filename="' . $zip_name . '"');
    drupal_add_http_header('Content-length', filesize($zip_loc));
    drupal_add_http_header('Pragma', "no-cache");
    drupal_add_http_header('Expires', '0');
    readfile("$zip_loc");
    exit;
  }

  if (preg_match('/^dart:(.*)$/', $file, $matches)) {
    $file_name = $matches[1];
    $dart_zip = file_load($state['saved_values'][GTTN_PAGE_4]['dart']['raw']);
    if ($dart_zip) {
      $files = gttn_tpps_get_archive_files($dart_zip);
      $dir = dirname($files[0]);
      foreach ($files as $file) {
        if (preg_match('/.*' . $file_name . '$/', $file)) {
          drupal_add_http_header('Content-Type', 'text/plain');
          drupal_add_http_header('Content-Disposition', 'attachment; filename="' . $file_name . '"');
          drupal_add_http_header('Content-length', filesize($file));
          drupal_add_http_header('Pragma', "no-cache");
          drupal_add_http_header('Expires', '0');
          readfile("$file");
          gttn_tpps_rmdir($dir);
          exit;
        }
      }
    }
  }

}

/**
 *
 */
function gttn_tpps_load_old_form_state(&$new_form_state, $old_form_state) {
  $new_form_state['saved_values'] = $old_form_state['saved_values'];
  $new_form_state['stage'] = $old_form_state['stage'];
  $new_form_state['accession'] = $old_form_state['accession'];
  $new_form_state['dbxref_id'] = $old_form_state['dbxref_id'];
  $new_form_state['stats'] = $old_form_state['stats'] ?? NULL;
  $new_form_state['data'] = $old_form_state['data'] ?? NULL;
  $new_form_state['file_info'] = $old_form_state['file_info'] ?? NULL;
  $new_form_state['owner_uid'] = $old_form_state['owner_uid'] ?? NULL;
  $new_form_state['ids'] = $old_form_state['ids'] ?? NULL;
}
