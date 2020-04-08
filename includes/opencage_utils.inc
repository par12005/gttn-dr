<?php

/**
 * @file
 * OpenCage API interaction functions.
 */

/**
 * Make a query to the OpenCage API.
 *
 * @param string $query
 *   The query we are searching for
 *
 * @return stdClass
 *   A response object from the OpenCage API.
 */
function gttn_tpps_opencage_query($query) {
  $geo_api_key = variable_get('gttn_tpps_geocode_api_key', NULL);
  if (empty($geo_api_key)) {
    return FALSE;
  }

  $query = urlencode($query);
  $url = "https://api.opencagedata.com/geocode/v1/json?q=$query&key=$geo_api_key";
  $response = json_decode(file_get_contents($url));

  return $response;
}

/**
 * Use OpenCage to find the GPS coordinates of a location string.
 *
 * @param string $query
 *   The location we need the coordinates of.
 *
 * @return array|FALSE
 *   A list of results returned by the OpenCage API, or FALSE on failure.
 */
function gttn_tpps_opencage_coords($query) {
  $response = gttn_tpps_opencage_query($query);
  if (!empty($response) and $response->total_results) {
    $results = array();
    foreach ($response->results as $result) {
      $results[] = array(
        'type' => $result->components->_type,
        'lat' => $result->geometry->lat,
        'lng' => $result->geometry->lng,
      );
    }
    return $results;
  }

  return FALSE;
}
