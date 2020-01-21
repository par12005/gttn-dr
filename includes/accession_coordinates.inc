<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_accession_coordinates() {
  $params = drupal_get_query_parameters($_POST);

  if (!empty($params['fid']) and file_load($params['fid'])) {
    $coordinates = array();
    $columns = array(
      'id_col' => $params['id_col'],
      'lat_col' => $params['lat_col'],
      'long_col' => $params['long_col'],
    );

    $options = array(
      'no_header' => $params['no-header'],
      'columns' => $columns,
      'coords' => &$coordinates,
    );
    gttn_tpps_file_iterator($params['fid'], 'gttn_tpps_get_coordinates', $options);

    drupal_json_output($coordinates);
  }

}

/**
 *
 */
function gttn_tpps_get_coordinates($row, &$options) {
  $id_col = $options['columns']['id_col'];
  $lat_col = $options['columns']['lat_col'];
  $long_col = $options['columns']['long_col'];
  $coordinates = &$options['coords'];

  // Only add coordinates to the standardized coordinates array if
  // we can successfully standardsize them.
  if (($coord = gttn_tpps_standard_coord("{$row[$lat_col]},{$row[$long_col]}"))) {
    $pair = explode(',', $coord);
    // Add the standardized coordinates.
    array_push($coordinates, array(
      "{$row[$id_col]}",
      $pair[0],
      $pair[1],
    ));
  }
}
