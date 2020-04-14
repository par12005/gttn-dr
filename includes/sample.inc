<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_match_samples(&$state) {
  $samples = &$state['data']['samples'];
  $org = $state['saved_values'][GTTN_TYPE_PAGE]['project']['props']['organization'];
  $results = array();

  $has_part_cvt = chado_get_cvterm(array(
    'cv_id' => array(
      'name' => 'sequence',
    ),
    'name' => 'has_part',
    'is_obsolete' => 0,
  ))->cvterm_id;

  $sample_cvt = chado_get_cvterm(array(
    'name' => 'biological sample',
    'cv_id' => array(
      'name' => 'sep',
    ),
    'is_obsolete' => 0,
  ))->cvterm_id;

  $tissue_cvt = chado_get_cvterm(array(
    'name' => 'Tissue',
    'cv_id' => array(
      'name' => 'ncit',
    ),
    'is_obsolete' => 0,
  ))->cvterm_id;

  foreach ($samples as $id => $sample) {
    $query = db_select('chado.stock', 's1');
    $query->join('chado.stock_relationship', 'sr', 's1.stock_id = sr.subject_id');
    $query->join('chado.stock', 's2', 's2.stock_id = sr.object_id');
    $query->join('chado.stockprop', 'sp', 's2.stock_id = sp.stock_id');
    $query->join('gttn_tpps_organization_inventory', 'oi', 'oi.sample_id = s2.stock_id');
    $query->fields('s2', array('stock_id'));
    $query->condition('sr.type_id', $has_part_cvt);
    $query->condition('sp.type_id', $tissue_cvt);
    $query->condition('sp.value', $sample['tissue'], 'ilike');
    $query->condition('oi.organization_id', $org);
    $query->condition('s1.uniquename', '^GTTN-TGDR...-' . $sample['source'] . '$', '~*');
    $query->condition('s2.uniquename', '^GTTN-TGDR...-' . $id . '$', '~*');
    $query->condition('s2.type_id', $sample_cvt);
    $query = $query->execute();
    while (($result = $query->fetchObject())) {
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
  $query->leftJoin('chado.stockprop', 'sp1', 'sp1.stock_id = s.stock_id and sp1.type_id = ' . $cvt['tissue']);
  $query->leftJoin('chado.stockprop', 'sp2', 'sp2.stock_id = s.stock_id and sp2.type_id = ' . $cvt['dimension']);
  $query->leftJoin('chado.stockprop', 'sp3', 'sp3.stock_id = s.stock_id and sp3.type_id = ' . $cvt['date']);
  $query->leftJoin('chado.stockprop', 'sp4', 'sp4.stock_id = s.stock_id and sp4.type_id = ' . $cvt['collector']);
  $query->leftJoin('chado.stockprop', 'sp5', 'sp5.stock_id = s.stock_id and sp5.type_id = ' . $cvt['method']);
  $query->leftJoin('chado.stockprop', 'sp6', 'sp6.stock_id = s.stock_id and sp6.type_id = ' . $cvt['remaining']);
  $query->leftJoin('chado.stockprop', 'sp7', 'sp7.stock_id = s.stock_id and sp7.type_id = ' . $cvt['type']);
  $query->leftJoin('chado.stockprop', 'sp8', 'sp8.stock_id = s.stock_id and sp8.type_id = ' . $cvt['analyzed']);
  $query->leftJoin('chado.stockprop', 'sp9', 'sp9.stock_id = s.stock_id and sp9.type_id = ' . $cvt['legal']);
  $query->leftJoin('chado.stockprop', 'sp10', 'sp10.stock_id = s.stock_id and sp10.type_id = ' . $cvt['share']);
  $query->leftJoin('chado.stockprop', 'sp11', 'sp11.stock_id = s.stock_id and sp11.type_id = ' . $cvt['storage']);
  $query->addExpression('sp1.value', 'tissue');
  $query->addExpression('substring(s2.uniquename from 14)', 'source');
  $query->addExpression('sp2.value', 'dimension');
  $query->addExpression('sp3.value', 'collection_date');
  $query->addExpression('sp4.value', 'collector');
  $query->addExpression('sp5.value', 'method');
  $query->addExpression('sp6.value', 'remaining');
  $query->addExpression('sp7.value', 'type');
  $query->addExpression('sp8.value', 'analyzed');
  $query->addExpression('sp9.value', 'legal');
  $query->addExpression('sp10.value', 'share');
  $query->addExpression('sp11.value', 'storage');
  $query->condition('s.stock_id', $stock_id);
  $query->condition('s.type_id', $cvt['sample']);
  $query->condition('sr.type_id', $cvt['has_part']);

  $query = $query->execute();
  $sample = $query->fetchObject();
  if (!empty($sample)) {
    $sample->legal = $sample->legal ? 'TRUE' : 'FALSE';
    $sample->share = $sample->share ? 'TRUE' : 'FALSE';
  }
  return $sample;
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
    'legal' => chado_get_cvterm(array(
      'name' => 'Legal',
      'cv_id' => array(
        'name' => 'ncit',
      ),
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
  );
}
