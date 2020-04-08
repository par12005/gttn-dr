<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_flatten($state) {
  $data = $state['saved_values'] ?? NULL;

  if (!$data) {
    return array();
  }
  unset($data['frontpage']);
  unset($data['summarypage']);
  unset($data['form_build_id']);

  $type_page = &$data[GTTN_TYPE_PAGE];
  if (!empty($type_page['#cleared'])) {
    $data['#page_1_cleared'] = TRUE;
  }
  else {
    $data['#page_1_cleared'] = FALSE;
  }
  $project = $type_page['project'] ?? NULL;
  $data["Submission Name"] = $project['name'] ?? NULL;
  $data["Reason for Submission"] = $project['description'] ?? NULL;
  $props = $project['props'] ?? NULL;
  if (!empty($props['analysis_date'])) {
    $data["Analysis Date"] = date("jS F, Y", strtotime("{$props['analysis_date']['day']}-{$props['analysis_date']['month']}-{$props['analysis_date']['year']}"));
  }
  $data["Publication DOI"] = $props['pub_doi'] ?? NULL;
  $data["Data DOI"] = $props['data_doi'] ?? NULL;
  $data["Database URL"] = $props['db_url'] ?? NULL;
  $data["Project Name"] = $props['projecet_name'] ?? NULL;
  $data["Submission Type"] = $props['type'] ?? NULL;
  $access = array();
  foreach ($props['permissions'] as $org_id) {
    if (!empty($org_id)) {
      $name = db_select('gttn_profile_organization', 'o')
        ->fields('o', array('name'))
        ->condition('organization_id', $org_id)
        ->range(0, 1)
        ->execute()->fetchObject()->name;
      if (!empty($name)) {
        $access[] = $name;
      }
    }
  }
  if (!empty($access)) {
    $data["Labs with Access Permissions"] = implode(', ', $access);
  }
  unset($data[GTTN_TYPE_PAGE]);

  $page_1 = &$data[GTTN_PAGE_1];
  $org = &$page_1['organism'] ?? NULL;

  if (isset($page_1['#cleared']) and $page_1['#cleared']) {
    $data['#page_2_cleared'] = TRUE;
  }
  else {
    $data['#page_2_cleared'] = FALSE;
  }
  if (isset($org['number'])) {
    $data['#number_organisms'] = $org['number'];
    for ($i = 1; $i <= $data['#number_organisms']; $i++) {
      if (!empty($org[$i])) {
        $data["Species $i"] = $org[$i];
      }
    }
  }
  $data_types = array();
  foreach ($page_1['data_type'] as $type) {
    if (!empty($type)) {
      $data_types[] = $type;
    }
  }
  if (!empty($data_types)) {
    $data["Data Types"] = implode(', ', $data_types);
  }
  unset($data[GTTN_PAGE_1]);

  $page_3 = &$data[GTTN_PAGE_3];

  if (isset($page_3['#cleared']) and $page_3['#cleared']) {
    $data['#page_3_cleared'] = TRUE;
  }
  else {
    $data['#page_3_cleared'] = FALSE;
  }
  $page_3 = &$data[GTTN_PAGE_3];
  $tree_acc = $page_3['tree-accession'] ?? NULL;
  $samples = $page_3['samples'] ?? NULL;
  for ($i = 1; $i <= $data['#number_organisms']; $i++) {
    if (!empty($tree_acc["species-$i"]['file'])) {
      $organism_name = $data["Species $i"];
      $data["$organism_name Accession File"] = $tree_acc["species-$i"]['file'];
      if (!empty($tree_acc["species-$i"]['file-groups']['Location (latitude/longitude or country/state)']['#type']) and $tree_acc["species-$i"]['file-groups']['Location (latitude/longitude or country/state)']['#type'] == "gps") {
        $data["#{$organism_name}_accession_no_header"] = $tree_acc["species-$i"]['file-no-header'] ?? '0';
        $data["#{$organism_name}_accession_id_col"] = $tree_acc["species-$i"]['file-groups']['Tree Id'][1];
        $data["#{$organism_name}_accession_lat_col"] = $tree_acc["species-$i"]['file-groups']['Location (latitude/longitude or country/state)'][4];
        $data["#{$organism_name}_accession_long_col"] = $tree_acc["species-$i"]['file-groups']['Location (latitude/longitude or country/state)'][5];
      }
    }
  }
  $data["Number of Trees"] = $state['stats']['tree_count'] ?? NULL;
  $data["Samples File"] = !empty($samples['file']) ? $samples['file'] : NULL;
  unset($data[GTTN_PAGE_3]);

  $page_4 = &$data[GTTN_PAGE_4];

  if (isset($page_4['#cleared']) and $page_4['#cleared']) {
    $data['#page_4_cleared'] = TRUE;
  }
  else {
    $data['#page_4_cleared'] = FALSE;
  }
  $data["DART File"] = !empty($page_4['dart']['file']) ? $page_4['dart']['file'] : NULL;
  $data["Isotope File"] = !empty($page_4['isotope']['file']) ? $page_4['isotope']['file'] : NULL;
  // TODO

  /*for ($i = 1; $i <= $data['#number_organisms']; $i++) {
    if (isset($page_4["organism-$i"])) {
      $org = &$page_4["organism-$i"];
      $org_name = isset($data["Species $i"]) ? $data["Species $i"] : NULL;

      if (isset($org['phenotype'])) {
        $pheno = &$org['phenotype'];

        if (isset($pheno['file']) and $pheno['file']) {
          $data["$org_name Phenotype File"] = $pheno['file'];
        }
      }

      if (isset($org['genotype'])) {
        $geno = &$org['genotype'];
        $file_type = &$geno['file-type'];

        $marker_types = array();
        foreach ($geno['marker-type'] as $key => $val) {
          if ($val) {
            $marker_types[] = $key;
          }
        }
        $data["$org_name Genotype Marker Types"] = isset($marker_types) ? implode(', ', $marker_types) : NULL;

        if (isset($geno['marker-type']['SNPs']) and $geno['marker-type']['SNPs']) {
          $snp = $geno['SNPs'];
          if (isset($snp['genotyping-design']) and $snp['genotyping-design']) {
            $geno_design_options = array(
              1 => 'GBS',
              2 => 'Targeted Capture',
              3 => 'Whole Genome Resequencing',
              4 => 'RNA-Seq',
              5 => 'Genotyping Array',
            );
            if (isset($geno_design_options[$snp['genotyping-design']])) {
              $data["$org_name Genotyping Design"] = $geno_design_options[$snp['genotyping-design']];
            }

            if (isset($data["$org_name Genotyping Design"]) and $data["$org_name Genotyping Design"] == 'GBS' and isset($snp['GBS']) and $snp['GBS']) {
              $gbs_options = array(
                1 => 'RADSeq',
                2 => 'ddRAD-Seq',
                3 => 'NextRAD',
                4 => 'RAPTURE',
                5 => 'Other',
              );
              if (isset($gbs_options[$snp['GBS']])) {
                $data["$org_name GBS Type"] = $gbs_options[$snp['GBS']];
              }

              if (isset($data["$org_name GBS Type"]) and $data["$org_name GBS Type"] == 'Other' and isset($snp['GBS-other']) and $snp['GBS-other'] != '') {
                $data["$org_name Custom GBS Type"] = $snp['GBS-other'];
              }
            }

            if (isset($data["$org_name Genotyping Design"]) and $data["$org_name Genotyping Design"] == 'Targeted Capture' and isset($snp['targeted-capture']) and $snp['targeted-capture']) {
              $targeted_capture_options = array(
                1 => 'Exome Capture',
                2 => 'Other',
              );
              if (isset($targeted_capture_options[$snp['targeted-capture']])) {
                $data["$org_name Targeted Capture Type"] = $targeted_capture_options[$snp['targeted-capture']];
              }

              if (isset($data["$org_name Targeted Capture Type"]) and $data["$org_name Targeted Capture Type"] == 'Other' and isset($snp['targeted-capture-other']) and $snp['targeted-capture-other'] != '') {
                $data["$org_name Custom Targeted Capture Type"] = $snp['targeted-capture-other'];
              }
            }
          }
        }
        if (isset($geno['marker-type']['SSRs/cpSSRs']) and $geno['marker-type']['SSRs/cpSSRs'] and isset($geno['SSRs/cpSSRs']) and $geno['SSRs/cpSSRs'] != '') {
          $data["$org_name SSRs/cpSSRs Type"] = $geno['SSRs/cpSSRs'];
        }
        if (isset($geno['marker-type']['Other']) and $geno['marker-type']['Other'] and isset($geno['other-marker']) and $geno['other-marker'] != '') {
          $data["$org_name Other Marker Type"] = $geno['other-marker'];
        }

        if (isset($geno['ref-genome']) and $geno['ref-genome'] === 'bio' and isset($geno['BioProject-id']) and $geno['BioProject-id'] != '') {
          $data["$org_name BioProject Id"] = $geno['BioProject-id'];

          if (isset($geno['assembly-auto']) and gettype($geno['assembly-auto']) === 'array') {
            $count = 1;
            foreach ($geno['assembly-auto'] as $id => $check) {
              if (isset($check) and $check) {
                $data["$org_name Linked Accession $count"] = $id;
                $count++;
              }
            }
          }
        }
        elseif (isset($geno['ref-genome']) and ($geno['ref-genome'] === 'manual' or $geno['ref-genome'] === 'manual2') and isset($geno['tripal_fasta']['file']['file_upload']) and isset($geno['tripal_fasta']['file']['file_upload_existing']) and ($geno['tripal_fasta']['file']['file_upload'] or $geno['tripal_fasta']['file']['file_upload_existing'])) {
          if ($geno['tripal_fasta']['file']['file_upload']) {
            $data["$org_name Uploaded Reference File"] = $geno['tripal_fasta']['file']['file_upload'];
          }
          if ($geno['tripal_fasta']['file']['file_upload_existing']) {
            $data["$org_name Reference File"] = $geno['tripal_fasta']['file']['file_upload_existing'];
          }
        }
        elseif (isset($geno['ref-genome']) and $geno['ref-genome'] === 'url' and isset($geno['tripal_fasta']['file']['file_remote']) and $geno['tripal_fasta']['file']['file_remote']) {
          $data["$org_name Website of reference file"] = $geno['tripal_fasta']['file']['file_remote'];
        }
        elseif (isset($geno['ref-genome']) and $geno['ref-genome']) {
          $data["$org_name Reference Genome"] = $geno['ref-genome'];
        }

        if (isset($file_type['Genotype Assay']) and $file_type['Genotype Assay'] and isset($geno['file']) and $geno['file']) {
          $data["$org_name Genotype Assay File"] = $geno['file'];
        }

        if (isset($file_type['Assay Design']) and $file_type['Assay Design'] and isset($geno['assay-design']) and $geno['assay-design']) {
          $data["$org_name Genotype Assay Design File"] = $geno['assay-design'];
        }

        if (isset($file_type['VCF']) and $file_type['VCF'] and isset($geno['vcf']) and $geno['vcf']) {
          $data["$org_name Genotype VCF File"] = $geno['vcf'];
        }
      }
    }
  }*/
  unset($data[GTTN_PAGE_4]);

  $data['#dbxref_id'] = isset($state['dbxref_id']) ? $state['dbxref_id'] : NULL;
  $data['#accession'] = isset($state['accession']) ? $state['accession'] : NULL;
  $data['#status'] = isset($state['status']) ? $state['status'] : NULL;

  foreach ($data as $key => $val) {
    if (!isset($val) or $val === '') {
      unset($data[$key]);
    }
  }

  return $data;
}

/**
 *
 */
function gttn_tpps_table_display(array &$state) {
  $flat = gttn_tpps_flatten($state);
  $map_api_key = variable_get('gttn_tpps_maps_api_key', NULL);

  $rows = array();
  $content = "";
  foreach ($flat as $key => $val) {
    if ($key[0] != '#') {
      $key_str = filter_xss(check_plain($key));
      $val_str = filter_xss(check_plain($val));

      if (preg_match('/File/', $key) and ($file_name = file_load($val)->filename)) {
        // Create links to files.
        $file_url = check_plain(file_create_url(file_load($val)->uri));
        $val_str = "<a href='$file_url' target='blank'>$file_name</a>";
        $val_str .= "<br><input id=\"fid_$val\" class=\"preview_button\" type=\"button\" value=\"Preview file\"></input>";
        if (preg_match('/^(.*)Accession File/', $key, $matches)) {
          $name = "";
          if ($matches[1] != "Tree ") {
            $parts = explode(" ", $matches[1]);
            $name = implode("_", $parts);
          }

          $val_str .= "<div id=\"{$name}map_wrapper\"></div>"
            . "<input id=\"{$name}map_button\" type=\"button\" value=\"Click here to view trees on map!\"></input>"
            . "<div id=\"{$name}accession_fid\" style=\"display:none;\">$val</div>"
            . "<script>jQuery('#{$name}map_button').click(getCoordinates);</script>";
        }
      }
      elseif ($key == 'Publication DOI') {
        $link = preg_match('/https?:\/\//', $val_str) ? $val_str : "https://doi.org/$val_str";
        $val_str = "<a href=\"$link\" target=\"blank\">$val_str</a>";
      }

      $rows[] = array($key_str, $val_str);
    }
    elseif (preg_match('/^#(.*)accession_(.*)/', $key, $matches)) {
      $val_str = filter_xss(check_plain($val));
      $parts = explode(" ", substr($matches[0], 1));
      $item_id = implode("_", $parts);
      $content .= "<div id=\"$item_id\" style=\"display:none;\">$val_str</div>";
    }
  }
  if (!empty($map_api_key)) {
    $content .= "<script src=\"https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js\"></script><script src=\"https://maps.googleapis.com/maps/api/js?key=$map_api_key&callback=initMap\"
    async defer></script>";
  }

  $headers = array(
    'Field',
    'Value',
  );

  $vars = array(
    'header' => $headers,
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );

  $display = theme_table($vars) . $content;

  return $display;
}

/**
 * @param string $accession
 *   The accession of the submission we want to display.
 */
function gttn_tpps_completed_display(string $accession = '') {
  global $base_url;
  $display = "";

  $old_form_state = gttn_tpps_load_submission($accession);
  if (empty($old_form_state)) {
    throw new \Exception("Invalid submission accession: '$accession'");
  }
  $link = "$base_url/gttn_tpps_details/accession/$accession";
  $title = $old_form_state['data']['project']['name'];

  $css = "
  .tgdr_complete_submission {
    background: #ffffff;
    border: 1px solid #cccccc;
    border-radius: 4px;
    padding: 0px 10px;
  }		
  ";
  drupal_add_css($css, 'inline');

  $display .= "<label for='gttn_tpps_$accession'>$title</label><p>You can view the GTTN-TPPS Details page with completed and approved GTTN-TPPS data <a href=\"$link\">here</a></p>";

  $display .= gttn_tpps_table_display($old_form_state);

  drupal_add_js(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_JS_PATH);
  drupal_add_css(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_CSS_PATH);

  return render($display);
}

/**
 * 
 */
function gttn_tpps_tab_create($user) {

  global $base_url;

  $params = drupal_get_query_parameters();

  if (isset($params['accession']) and isset($params['action']) and $params['action'] === 'DELETE') {
    gttn_tpps_delete_submission($params['accession']);
  }

  $states = gttn_tpps_load_submission_multiple(array('status' => 'Incomplete', 'uid' => $user->uid));
  $rows = array();
  foreach ($states as $state) {
    $type_page = &$state['saved_values'][GTTN_TYPE_PAGE];
    $page_1 = &$state['saved_values'][GTTN_PAGE_1];
    $acc = $del_cell = NULL;

    if (isset($state['accession'])) {
      $acc = "<a href='$base_url/gttn-tpps/{$state['accession']}' style='text-decoration: underline;'>{$state['accession']}</a>";
      $del_cell = "<input type='submit' id='delete-{$state['accession']}' onclick='";
      $del_cell .= "if (confirm(\"Attention! \\n\\nAre you sure you want to delete your submission {$state['accession']}?\\nThis action cannot be undone, and all of your data will be lost!\")){window.location.href = \"$base_url/gttn-tpps-submission/{$state['accession']}/delete\"}";
      $del_cell .= "' name='op' class='form-button' value='Delete'></td>";
    }

    $rows[] = array(
      $acc ?? "",
      $type_page['project']['name'] ?? "",
      $page_1['organism']['number'] ?? "",
      $del_cell ?? "",
    );
  }

  $headers = array(
    'Accession Number',
    'Submission Name',
    'Number of Species',
  );

  $vars = array(
    'header' => $headers,
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_incomplete_table'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );

  $final = isset($state) ? "<div class='gttn_tpps_table'><label for='gttn_tpps_incomplete_table'>Incomplete Submissions</label>" . theme_table($vars) . '</div>' : "";
  $state = NULL;

  $states = gttn_tpps_load_submission_multiple(array(
    'status' => array('Pending Approval', 'Approved'),
    'uid' => $user->uid,
  ));

  $rows = array();
  foreach ($states as $state) {
    $type_page = &$state['saved_values'][GTTN_TYPE_PAGE];
    $page_1 = &$state['saved_values'][GTTN_PAGE_1];
    $acc = NULL;

    if (isset($state['accession'])) {
      $acc = "<a href='$base_url/gttn-completed-submission/{$state['accession']}' style='text-decoration: underline;'>{$state['accession']}</a>";
    }

    $rows[] = array(
      $acc ?? "",
      $type_page['project']['name'] ?? "",
      $page_1['organism']['number'] ?? "",
      $state['status'] ?? "",
    );
  }

  $headers[] = 'Status';
  $vars['header'] = $headers;
  $vars['rows'] = $rows;
  $vars['attributes']['id'] = 'gttn_tpps_complete_table';

  $final .= isset($state) ? "<div class='gttn_tpps_table'><label for='gttn_tpps_complete_table'>Complete Submissions</label>" . theme_table($vars) . '</div>' : "";

  if (empty($final)) {
    $final .= "<h2>Could not find any Submissions</h2>";
    $final .= "<p>We could not find any complete or incomplete GTTN-TPPS Submissions. If you would like to start a new GTTN-TPPS Submission, you can start <a href=$base_url/gttn-tpps>here.</a></p>";
  }

  drupal_add_css(drupal_get_path('module', 'gttn_tpps') . GTTN_TPPS_CSS_PATH);
  return $final;
}

/**
 * Creates and prints an HTML table of the first 3 lines of a file.
 *
 * This function techinically does not return a value, but will print() the
 * rendered table of preview values to the page.
 */
function gttn_tpps_preview_file() {
  $params = drupal_get_query_parameters($_POST);
  if (!empty($params['fid'])) {
    $fid = $params['fid'];
    $no_header = FALSE;
  }
  else {
    return;
  }

  if ((file_load($fid))) {
    $content = gttn_tpps_parse_file($fid, 3, !empty($no_header));
    $rows = array();
    for ($i = 0; $i < count($content) - 1; $i++) {
      $rows[] = $content[$i];
    }
    $vars = array(
      'header' => $content['headers'],
      'rows' => $rows,
      'attributes' => array(
        'class' => array(
          'view',
          "preview_$fid",
        ),
        'id' => 'gttn_tpps_table_display',
      ),
      'caption' => '',
      'colgroups' => NULL,
      'sticky' => FALSE,
      'empty' => '',
    );
    $display = render(theme_table($vars));
    print($display);
    return;
  }
}
