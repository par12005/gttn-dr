<?php

/**
 * @file
 */

/**
 * 
 */
function gttn_tpps_ols_search($query, $ontology = NULL) {
  $obo_id = NULL;
  if (count(explode('|', $query)) > 1) {
    $parts = explode('|', $query);
    $obo_id = $parts[1];
  }
  $args = array();
  $args[] = "q=" . urlencode((empty($obo_id) ? $query : $obo_id));
  $args[] = "exact=true";
  $args[] = empty($obo_id) ? "queryFields=label" : "queryFields=obo_id";
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
