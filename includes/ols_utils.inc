<?php

/**
 * @file
 */

/**
 * 
 */
function gttn_tpps_ols_search($query, $ontology = NULL) {
  $args = array();
  $args[] = "q=" . urlencode($query);
  $args[] = "exact=true";
  $args[] = "queryFields=label";
  if (!empty($ontology)) {
    $args[] = "ontology=$ontology";
  }
  return gttn_tpps_ols_call('search', $args);
}

/**
 * 
 */
function gttn_tpps_ols_call($type, array $args = array()) {
  $url = gttn_tpps_ols_url($type, $args);
  $data = file_get_contents($url);
  return json_decode($data);
}

/**
 * 
 */
function gttn_tpps_ols_url($types, array $args = array()) {
  $url = GTTN_TPPS_OLS_BASE;

  if (!is_array($types)) {
    $url .= "$types/";
  }
  else {
    foreach ($types as $type) {
      $url .= "$type/";
    }
  }

  if (!empty($args)) {
    $url .= "?" . implode('&', $args);
  }

  return $url;
}
