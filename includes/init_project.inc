<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_init_project(array &$form_state) {
  $values = array(
    'accession' => 'GTTN-TGDR.*',
  );

  $options = array(
    'order_by' => array(
      'accession' => 'DESC',
    ),
    'limit' => '1',
    'regex_columns' => array('accession'),
  );

  $result = chado_select_record('dbxref', array('accession'), $values, $options);

  if (empty($result)) {
    $accession = 'GTTN-TGDR001';
  }
  else {
    $accession = substr($result[0]->accession, 9) + 1;
    while (strlen($accession) < 3) {
      $accession = "0$accession";
    }
    $accession = "GTTN-TGDR$accession";
  }

  $dbxref_id = chado_insert_record('dbxref', array(
    'db_id' => variable_get('gttn_tpps_local_db')->db_id,
    'accession' => $accession,
  ));

  $form_state['dbxref_id'] = $dbxref_id['dbxref_id'];
  $form_state['accession'] = $accession;
  $form_state['saved_values']['frontpage']['accession'] = $accession;
  $form_state['status'] = 'Incomplete';

  return $dbxref_id;
}
