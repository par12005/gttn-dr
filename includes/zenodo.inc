<?php

/**
 * @file
 */

/**
 * Creates a Zenodo DOI and loads metadata from a GTTN-TPPS Submission.
 *
 * @param string $accession
 *   The accession number of the GTTN-TPPS Submission.
 *
 * @global string $base_url
 *   The base URL of the site.
 *
 * @return object
 *   The Zenodo DOI Deposition object.
 */
function gttn_tpps_generate_doi($accession) {
  global $base_url;
  $state = gttn_tpps_load_submission($accession);

  $id = gttn_tpps_doi_create();
  $creators = array(
    (object) array(
      'name' => user_load($state['owner_uid'])->chado_record->name,
      'affiliation' => gttn_profile_organization_load($state['data']['project']['props']['organization'])->name,
    ),
  );

  $keywords = array();
  foreach ($state['data']['organism'] as $org) {
    $keywords[] = "{$org['genus']} {$org['species']}";
  }

  $metadata = (object) array(
    'title' => $state['data']['project']['name'],
    'upload_type' => 'dataset',
    'description' => $state['data']['project']['description'],
    'creators' => $creators,
    'keywords' => $keywords,
    'notes' => $state['saved_values']['summarypage']['comments'] ?? '',
    'related_identifiers' => array(
      (object) array(
        'relation' => 'isAlternateIdentifier',
        'identifier' => "$base_url/gttn-tpps/reference/submission/$accession",
      ),
    ),
  );

  gttn_tpps_doi_upload_files($id, $state);

  $upload = gttn_tpps_doi_metadata($id, $metadata);

  return $upload;
}

/**
 * Creates a blank Zenodo DOI Deposition. Returns the Zenodo id number.
 *
 * @return int
 *   The Zenodo Deposition id number.
 */
function gttn_tpps_doi_create() {
  $token = variable_get('gttn_tpps_zenodo_api_key', NULL);
  $prefix = variable_get('gttn_tpps_zenodo_prefix', '');
  $ch = curl_init("https://{$prefix}zenodo.org/api/deposit/depositions?" . http_build_query(array('access_token' => $token)));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');

  $out = json_decode(curl_exec($ch));
  curl_close($ch);
  return $out->id;
}

/**
 * Adds metadata to a Zenodo DOI Deposition. Returns the Deposition object.
 *
 * @param int $id
 *   The Deposition identifier.
 * @param object $metadata
 *   The metadata object describing the deposition.
 *
 * @return object
 *   The updated Deposition object.
 */
function gttn_tpps_doi_metadata($id, $metadata) {

  $data = new stdClass();
  $data->metadata = $metadata;
  $data_json = json_encode($data);

  $token = variable_get('gttn_tpps_zenodo_api_key', NULL);
  $prefix = variable_get('gttn_tpps_zenodo_prefix', '');
  $ch = curl_init("https://{$prefix}zenodo.org/api/deposit/depositions/$id?" . http_build_query(array('access_token' => $token)));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_json)));
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

  $out = json_decode(curl_exec($ch));
  curl_close($ch);
  return $out;
}

/**
 * Adds files to a Deposition from the state of a GTTN-TPPS Submission form.
 *
 * @param int $id
 *   The identifier for the Deposition.
 * @param array $state
 *   The form_state of the GTTN-TPPS Submission form.
 */
function gttn_tpps_doi_upload_files($id, array &$state) {
  $token = variable_get('gttn_tpps_zenodo_api_key', NULL);
  $prefix = variable_get('gttn_tpps_zenodo_prefix', '');
  $files = array();

  foreach ($state['file_info'] as $page_files) {
    foreach ($page_files as $file) {
      $files[] = $file['fid'];
    }
  }

  foreach ($files as $file) {
    $file = file_load($file);
    if ($file->filesize < 20000000000) {
      $path = drupal_realpath($file->uri);
      $data = array(
        'file' => curl_file_create($path, $file->filemime, $file->filename),
      );

      $ch = curl_init("https://{$prefix}zenodo.org/api/deposit/depositions/$id/files?" . http_build_query(array('access_token' => $token)));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_exec($ch);
      curl_close($ch);
    }
  }
}

/**
 * Publishes a completed Zenodo Deposition. Returns the Deposition object.
 *
 * @param int $id
 *   The identifier for the Deposition.
 *
 * @return object
 *   The updated Deposition object.
 */
function gttn_tpps_doi_publish($id) {
  $token = variable_get('gttn_tpps_zenodo_api_key', NULL);
  $prefix = variable_get('gttn_tpps_zenodo_prefix', '');
  $ch = curl_init("https://{$prefix}zenodo.org/api/deposit/depositions/$id/actions/publish?" . http_build_query(array('access_token' => $token)));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, '');

  $out = json_decode(curl_exec($ch));
  curl_close($ch);
  return $out;
}

/**
 * Loads and returns the Deposition object for an existing Zenodo Deposition.
 *
 * @param int $id
 *   The identifier for the Deposition.
 *
 * @return object
 *   The loaded Deposition object.
 */
function gttn_tpps_doi_load($id) {
  $token = variable_get('gttn_tpps_zenodo_api_key', NULL);
  $prefix = variable_get('gttn_tpps_zenodo_prefix', '');
  $ch = curl_init("https://{$prefix}zenodo.org/api/deposit/depositions/$id?" . http_build_query(array('access_token' => $token)));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $out = json_decode(curl_exec($ch));
  curl_close($ch);
  return $out;
}

/**
 * Formats an author name for Zenodo metadata fields.
 *
 * @param string $name
 *   The name to format.
 *
 * @return string
 *   The formatted name.
 */
function gttn_tpps_doi_format_name($name) {
  $parts = explode(' ', $name);
  $first = $parts[0];
  $last = implode(' ', array_slice($parts, 1));
  return "$last, $first";
}
