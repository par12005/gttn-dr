<?php

/**
 * @file
 * OpenCage API interaction functions.
 */

/**
 * Use OpenCage to find the gps coordinates of a location string.
 *
 * @param string $query
 *   The location we need the coordinates of.
 *
 * @return array
 *   A list of results returned by the OpenCage API.
 */
function gttn_tpps_opencage_query($query) {
  $geo_api_key = variable_get('gttn_tpps_geocode_api_key', NULL);
  if (empty($geo_api_key)) {
    return FALSE;
  }

  $query = urlencode($query);
  $url = "https://api.opencagedata.com/geocode/v1/json?q=$query&key=$geo_api_key";
  $response = json_decode(file_get_contents($url));

  if ($response->total_results) {
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