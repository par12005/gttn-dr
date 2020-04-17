<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_match_samples(&$state) {
  $num_matches = 0;
  $samples = &$state['data']['samples'];
  $org = $state['saved_values'][GTTN_TYPE_PAGE]['project']['props']['organization'];
  foreach ($samples as $id => $sample) {
    $samples[$id]['matches'] = gttn_tpps_match_sample($sample, $org);
    $num_matches += count($samples[$id]['matches']);
  }
  return $num_matches;
}

/**
 *
 */
function gttn_tpps_match_sample($sample, $org = NULL, $stock_id = NULL) {
  $results = array();
  $cvt = gttn_tpps_sample_cvterms();
  $org = $org ?? $sample['org'];
  $stock_id = $stock_id ?? ($sample['stock_id'] ?? NULL);

  $date = $sample['date'];
  if (is_array($date)) {
    $date = date("m/d/Y", strtotime($date['day'] . '-' . $date['month'] . '-' . $date['year']));
  }
  elseif (is_int($date)) {
    $date = gttn_tpps_xlsx_translate_date($date);
  }

  $query = db_select('chado.stock', 's1');
  $query->join('chado.stock_relationship', 'sr', 's1.stock_id = sr.subject_id');
  $query->join('chado.stock', 's2', 's2.stock_id = sr.object_id');
  $query->join('chado.stockprop', 'sp1', "s2.stock_id = sp1.stock_id and sp1.type_id = {$cvt['tissue']}");
  $query->join('chado.stockprop', 'sp2', "s2.stock_id = sp2.stock_id and sp2.type_id = {$cvt['date']}");
  $query->join('chado.stockprop', 'sp3', "s2.stock_id = sp3.stock_id and sp3.type_id = {$cvt['collector']}");
  $query->join('chado.stockprop', 'sp4', "s2.stock_id = sp4.stock_id and sp4.type_id = {$cvt['method']}");
  $query->join('chado.stockprop', 'sp5', "s2.stock_id = sp5.stock_id and sp5.type_id = {$cvt['type']}");
  $query->join('gttn_tpps_organization_inventory', 'oi', 'oi.sample_id = s2.stock_id');
  $query->fields('s2', array('stock_id'));
  $query->condition('sr.type_id', $cvt['has_part']);
  $query->condition('sp1.value', $sample['tissue'], 'ilike');
  $query->condition('sp2.value', $date);
  $query->condition('sp3.value', $sample['collector'], 'ilike');
  $query->condition('sp4.value', $sample['method']);
  $query->condition('sp5.value', $sample['type']);
  $query->condition('oi.organization_id', $org);
  $query->condition('s1.uniquename', '^GTTN-TGDR...-' . $sample['source'] . '$', '~*');
  $query->condition('s2.uniquename', '^GTTN-TGDR...-' . $sample['id'] . '$', '~*');
  $query->condition('s2.type_id', $cvt['sample']);
  $query = $query->execute();

  while (($result = $query->fetchObject())) {
    if (empty($stock_id) or $stock_id != $result->stock_id) {
      $results[] = $result->stock_id;
    }
  }
  return $results;
}

/**
 *
 */
function gttn_tpps_load_sample($stock_id) {

  $cvt = gttn_tpps_sample_cvterms();

  $query = db_select('chado.stock', 's');
  $query->join('chado.stock_relationship', 'sr', 'sr.object_id = s.stock_id');
  $query->join('chado.stock', 's2', 's2.stock_id = sr.subject_id');
  $query->join('gttn_tpps_organization_inventory', 'oi', 'oi.sample_id = s.stock_id');
  $query->leftJoin('chado.stockprop', 'sp1', 'sp1.stock_id = s.stock_id and sp1.type_id = ' . $cvt['tissue']);
  $query->leftJoin('chado.stockprop', 'sp2', 'sp2.stock_id = s.stock_id and sp2.type_id = ' . $cvt['dimension']);
  $query->leftJoin('chado.stockprop', 'sp3', 'sp3.stock_id = s.stock_id and sp3.type_id = ' . $cvt['date']);
  $query->leftJoin('chado.stockprop', 'sp4', 'sp4.stock_id = s.stock_id and sp4.type_id = ' . $cvt['collector']);
  $query->leftJoin('chado.stockprop', 'sp5', 'sp5.stock_id = s.stock_id and sp5.type_id = ' . $cvt['method']);
  $query->leftJoin('chado.stockprop', 'sp6', 'sp6.stock_id = s.stock_id and sp6.type_id = ' . $cvt['remaining']);
  $query->leftJoin('chado.stockprop', 'sp7', 'sp7.stock_id = s.stock_id and sp7.type_id = ' . $cvt['type']);
  $query->leftJoin('chado.stockprop', 'sp8', 'sp8.stock_id = s.stock_id and sp8.type_id = ' . $cvt['analyzed']);
  $query->leftJoin('chado.stockprop', 'sp10', 'sp10.stock_id = s.stock_id and sp10.type_id = ' . $cvt['share']);
  $query->leftJoin('chado.stockprop', 'sp11', 'sp11.stock_id = s.stock_id and sp11.type_id = ' . $cvt['storage']);
  $query->addExpression('s.stock_id', 'stock_id');
  $query->addExpression('substring(s.uniquename from 14)', 'id');
  $query->addExpression('oi.organization_id', 'org');
  $query->addExpression('sp1.value', 'tissue');
  $query->addExpression('substring(s2.uniquename from 14)', 'source');
  $query->addExpression('sp2.value', 'dimension');
  $query->addExpression('sp3.value', 'date');
  $query->addExpression('sp4.value', 'collector');
  $query->addExpression('sp5.value', 'method');
  $query->addExpression('sp6.value', 'remaining');
  $query->addExpression('sp7.value', 'type');
  $query->addExpression('sp8.value', 'analyzed');
  $query->addExpression('sp10.value', 'share');
  $query->addExpression('sp11.value', 'storage');
  $query->condition('s.stock_id', $stock_id);
  $query->condition('s.type_id', $cvt['sample']);
  $query->condition('sr.type_id', $cvt['has_part']);

  $query = $query->execute();
  $result = $query->fetchObject();
  if (!empty($result)) {
    $sample = array(
      'id' => $result->id,
      'stock_id' => $result->stock_id,
      'org' => $result->stock_id,
      'tissue' => $result->tissue,
      'source' => $result->source,
      'dimension' => $result->dimension,
      'date' => $result->date,
      'collector' => $result->collector,
      'method' => $result->method,
      'remaining' => $result->remaining,
      'type' => $result->type,
      'analyzed' => $result->analyzed,
      'share' => $result->share,
      'storage' => $result->storage,
    );
    $sample['matches'] = gttn_tpps_match_sample($sample);
    $sample['events'] = gttn_tpps_sample_events($sample);
    foreach ($sample['events'] as $event) {
      gttn_tpps_sample_update($sample, $event);
    }
  }
  return $sample;
}

/**
 *
 */
function gttn_tpps_sample_events($sample) {
  if (isset($sample['events'])) {
    return $sample['events'];
  }
  $stock_ids = $sample['matches'];
  $stock_ids[] = $sample['stock_id'];
  $query = db_select('gttn_tpps_sample_event', 'se');
  $query->join('chado.cvterm', 'cvt', 'cvt.cvterm_id = se.event_type_id');
  $query->join('chado.project_dbxref', 'pd', 'pd.project_id = se.project_id');
  $query->join('chado.dbxref', 'dbx', 'dbx.dbxref_id = pd.dbxref_id');
  $query->addExpression('cvt.name', 'event_type');
  $query->addExpression('dbx.accession', 'project');
  $query->fields('se', array('comment', 'timestamp'))
    ->condition('sample_id', $stock_ids, 'IN');
  $query = $query->execute();

  $results = array();
  while (($result = $query->fetchObject())) {
    $results[$result->timestamp] = array(
      'event_type' => $result->event_type,
      'associated_project' => $result->project,
      'timestamp' => $result->timestamp,
      'comment' => $result->comment,
    );
  }
  ksort($results);
  return $results;
}

/**
 *
 */
function gttn_tpps_sample_update(&$sample, $event) {
  $cvt = gttn_tpps_sample_cvterms();
  $update_id = $event['associated_project'] . '-' . $sample['id'];
  switch ($event['event_type']) {
    case 'moved event':
      $query = db_select('chado.stockprop', 'sp');
      $query->join('chado.stock', 's', 's.stock_id = sp.stock_id');
      $query->fields('sp', array('value'));
      $query->condition('s.uniquename', $update_id);
      $query->condition('sp.type_id', $cvt['storage']);
      $query = $query->execute();
      $sample['storage'] = $query->fetchObject()->value;
      break;

    case 'analyzed event':
      $sample['analyzed'] = TRUE;
      return;

    case 'dimension changed event':
      $query = db_select('chado.stockprop', 'sp');
      $query->join('chado.stock', 's', 's.stock_id = sp.stock_id');
      $query->fields('sp', array('value'));
      $query->condition('s.uniquename', $update_id);
      $query->condition('sp.type_id', $cvt['dimension']);
      $query = $query->execute();
      $sample['dimension'] = $query->fetchObject()->value;
      break;

    case 'remaining changed event':
      $query = db_select('chado.stockprop', 'sp');
      $query->join('chado.stock', 's', 's.stock_id = sp.stock_id');
      $query->fields('sp', array('value'));
      $query->condition('s.uniquename', $update_id);
      $query->condition('sp.type_id', $cvt['remaining']);
      $query = $query->execute();
      $sample['remaining'] = $query->fetchObject()->value;
      break;

    case 'split event':
      // TODO
      break;
  }
}

/**
 *
 */
function gttn_tpps_sample_diff($s1, $s2) {
  $result = array();
  $types = array(
    'analyzed',
    'storage',
    'dimension',
    'remaining',
  );

  foreach ($types as $type) {
    if ($s1[$type] != $s2[$type]) {
      $result[$type] = array(
        'start' => $s1[$type],
        'end' => $s2[$type],
      );
    }
  }

  return $result;
}

/**
 *
 */
function gttn_tpps_generate_events($diff) {
  $results = array();
  foreach ($diff as $type => $info) {
    switch ($type) {
      case 'analyzed':
        $results[] = array(
          'event_type' => 'analyzed event',
          'timestamp' => date('c'),
          'comment' => 'Sample was analyzed',
        );
        break;

      case 'storage':
        $results[] = array(
          'event_type' => 'moved event',
          'timestamp' => date('c'),
          'comment' => "Sample was moved from {$info['start']} to {$info['end']}",
        );
        break;

      case 'dimension':
        $results[] = array(
          'event_type' => 'dimension changed event',
          'timestamp' => date('c'),
          'comment' => "Dimension of the sample was changed from {$info['start']} to {$info['end']}",
        );
        break;

      case 'remaining':
        $results[] = array(
          'event_type' => 'remaining changed event',
          'timestamp' => date('c'),
          'comment' => "Remaining amount of the sample was changed from {$info['start']} to {$info['end']}",
        );
        break;
    }
  }
  return $results;
}

/**
 *
 */
function gttn_tpps_sample_cvterms() {
  return array(
    'sample' => chado_get_cvterm(array(
      'name' => 'biological sample',
      'cv_id' => array(
        'name' => 'sep',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'tissue' => chado_get_cvterm(array(
      'name' => 'Tissue',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'has_part' => chado_get_cvterm(array(
      'cv_id' => array(
        'name' => 'sequence',
      ),
      'name' => 'has_part',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'dimension' => chado_get_cvterm(array(
      'name' => 'Dimension',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'date' => chado_get_cvterm(array(
      'name' => 'Collection Date',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'collector' => chado_get_cvterm(array(
      'name' => 'specimen collector',
      'cv_id' => array(
        'name' => 'obi',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'method' => chado_get_cvterm(array(
      'name' => 'Biospecimen Collection Method',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'remaining' => chado_get_cvterm(array(
      'name' => 'Volume',
      'cv_id' => array(
        'name' => 'ncit',
      ),
      'is_obsolete' => 0,
    ))->cvterm_id,
    'type' => chado_get_cvterm(array(
      'name' => 'sample type',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'analyzed' => chado_get_cvterm(array(
      'name' => 'sample analyzed',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'share' => chado_get_cvterm(array(
      'name' => 'shareable',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'storage' => chado_get_cvterm(array(
      'name' => 'storage location',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'collected event' => chado_get_cvterm(array(
      'name' => 'collected event',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'recorded event' => chado_get_cvterm(array(
      'name' => 'recorded event',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'moved event' => chado_get_cvterm(array(
      'name' => 'moved event',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'split event' => chado_get_cvterm(array(
      'name' => 'split event',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'analyzed event' => chado_get_cvterm(array(
      'name' => 'analyzed event',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'dimension changed event' => chado_get_cvterm(array(
      'name' => 'dimension changed event',
      'is_obsolete' => 0,
    ))->cvterm_id,
    'remaining changed event' => chado_get_cvterm(array(
      'name' => 'remaining changed event',
      'is_obsolete' => 0,
    ))->cvterm_id,
  );
}
