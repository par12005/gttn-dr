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
  $access_options = array(
    'public' => t('Public'),
    'gttn' => t('All registered GTTN organizations'),
    'law' => t('Law enforcement members only'),
    'org' => t('Select organizations'),
    'current' => t('Current organization only'),
  );
  $data["Data Access"] = $access_options[$props['permissions']] ?? NULL;
  if (!empty($props['permissions']) and $props['permissions'] == 'org') {
    $access = array();
    foreach ($props['permission-orgs'] as $org_id) {
      if (!empty($org_id)) {
        $name = db_select('gttn_profile_organization', 'o')
          ->fields('o', array('name'))
          ->condition('organization_id', $org_id)
          ->range(0, 1)
          ->execute()->fetchObject()->name ?? NULL;
        if (!empty($name)) {
          $access[] = $name;
        }
      }
    }
    if (!empty($access)) {
      $data["Labs with Access Permissions"] = implode(', ', $access);
    }
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
  $link = "$base_url/reference/submission/$accession";
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

/**
 *
 */
function gttn_tpps_display_submission($accession = NULL) {
  $output = '';

  global $user;
  global $base_url;
  $user = user_load($user->uid);

  $state = gttn_tpps_load_submission($accession);
  if (!empty($state)) {
    $map_api_key = variable_get('gttn_tpps_maps_api_key', '');
    $map_api_tools = "<script src=\"https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js\"></script><script src=\"https://maps.googleapis.com/maps/api/js?key=$map_api_key&callback=initMap\"
      async defer></script>"
      . "<div id=\"map_wrapper\"></div>";

    if (!empty($state['data']['trees'])) {
      $tree_info = array();
      foreach ($state['data']['trees'] as $id => $info) {
        if (!empty($info['lat']) and !empty($info['lng'])) {
          $tree_info[] = array(
            $id,
            $info['lat'],
            $info['lng'],
          );
        }
      }
      drupal_add_js(array('gttn_tpps' => array('tree_info' => $tree_info)), 'setting');
    }
    $output .= $map_api_tools;
    $title = $state['data']['project']['name'];
    $output .= "<h2>$title</h2>";
    $org = gttn_profile_organization_load($state['data']['project']['props']['organization']);
    $output .= "<h4>Submitted by {$user->chado_record->name} for {$org->name}</h4>";
    $output .= "<div id=\"reason\"><a href=\"#reason\">Click to show data collection reason</a></div>"
      . "<script>jQuery(document).ready(jQuery('#reason').on('click', function() {
          jQuery('#reason')[0].innerHTML = \"{$state['data']['project']['description']}\";
        }));</script>";

    // TODO: compressed files download.
    $output .= "<a href=\"$base_url/gttn-tpps/$accession/files\">Download compressed raw files</a><br>";

    $output .= gttn_tpps_display_submission_tabs($state);

  }

  return $output;
}

/**
 *
 */
function gttn_tpps_display_submission_tabs(array &$state) {
  $output = '<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" role="tab" data-toggle="tab" href="#species">Species</a></li>
    <li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#study">Study Details</a></li>
    <li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#trees">Trees</a></li>
    <li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#samples">Samples</a></li>';
  $dart_exist = !empty($state['saved_values'][GTTN_PAGE_4]['dart']);
  $isotope_exist = !empty($state['saved_values'][GTTN_PAGE_4]['isotope']);
  $genetic_exist = !empty($state['saved_values'][GTTN_PAGE_4]['genetic']);
  $anatomy_exist = !empty($state['saved_values'][GTTN_PAGE_4]['anatomy']);

  $output .= $dart_exist ? '<li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#dart">DART</a></li>' : "";
  $output .= $isotope_exist ? '<li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#isotope">Isotope</a></li>' : "";
  $output .= $genetic_exist ? '<li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#genetic">Genetic</a></li>' : "";
  $output .= $anatomy_exist ? '<li class="nav-item"><a class="nav-link" role="tab" data-toggle="tab" href="#anatomy">Anatomy</a></li>' : "";

  $output .= '</ul><div id="tab-content" class="tab-content">';

  $output .= "<div id=\"species\" role=\"tabpanel\" class=\"tab-pane fade in active\"></div>";
  $output .= "<div id=\"study\" role=\"tabpanel\" class=\"tab-pane fade\"></div>";
  $output .= "<div id=\"trees\" role=\"tabpanel\" class=\"tab-pane fade\"></div>";
  $output .= "<div id=\"samples\" role=\"tabpanel\" class=\"tab-pane fade\"></div>";

  $output .= $dart_exist ? "<div id=\"dart\" role=\"tabpanel\" class=\"tab-pane fade\"></div>" : "";
  $output .= $isotope_exist ? "<div id=\"isotope\" role=\"tabpanel\" class=\"tab-pane fade\"></div>" : "";
  $output .= $genetic_exist ? "<div id=\"genetic\" role=\"tabpanel\" class=\"tab-pane fade\"></div>" : "";
  $output .= $anatomy_exist ? "<div id=\"anatomy\" role=\"tabpanel\" class=\"tab-pane fade\"></div>" : "";

  $output .= '</div>';
  return $output;
}

/**
 * Prints a display tab for a GTTN-TPPS submission.
 *
 * Relies on the existence of a helper function called
 * "gttn_tpps_display_submission_<tab>" where <tab> is the type of tab we want
 * to print.
 *
 * @param string $accession
 *   The accession of the GTTN-TPPS submission.
 * @param string $tab
 *   The type of tab to print.
 */
function gttn_tpps_display_submission_tab($accession, $tab) {
  $function = "gttn_tpps_display_submission_$tab";
  $state = gttn_tpps_load_submission($accession);
  if (function_exists($function)) {
    print($function($state));
  }
  else {
    print '';
  }
  return;
}

/**
 *
 */
function gttn_tpps_display_submission_species(array &$state) {
  $query = db_select('chado.organism', 'o')
    ->fields('o', array('organism_id', 'genus', 'species', 'common_name'))
    ->condition('o.organism_id', $state['ids']['organism_ids'], 'IN')
    ->execute();
  $rows = array();

  while (($result = $query->fetchObject())) {
    $id = $result->organism_id;
    $family = $order = "";

    if (gttn_tpps_chado_prop_exists('organism', $id, 'family')) {
      $family = db_select('chado.organismprop', 'o')
        ->fields('o', array('value'))
        ->condition('organism_id', $id)
        ->condition('type_id', chado_get_cvterm(array(
          'name' => 'family',
          'cv_id' => array(
            'name' => 'taxonomic_rank',
          ),
        ))->cvterm_id)
        ->execute()->fetchObject()->value;
    }

    if (gttn_tpps_chado_prop_exists('organism', $id, 'order')) {
      $order = db_select('chado.organismprop', 'o')
        ->fields('o', array('value'))
        ->condition('organism_id', $id)
        ->condition('type_id', chado_get_cvterm(array(
          'name' => 'order',
          'cv_id' => array(
            'name' => 'taxonomic_rank',
          ),
        ))->cvterm_id)
        ->execute()->fetchObject()->value;
    }

    $name = "{$result->genus} {$result->species}";
    $link = gttn_tpps_entity_link($id, $name, 'Organism');
    $rows[] = array(
      $order,
      $family,
      $link,
      $result->common_name ?? '',
    );
  }

  $vars = array(
    'header' => array(
      'Order',
      'Family',
      'Species',
      'Common Name',
    ),
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );
  return theme_table($vars);

}

/**
 *
 */
function gttn_tpps_display_submission_study(array &$state) {
  $rows = array();

  $species = array();
  foreach ($state['data']['organism'] as $key => $info) {
    $species_name = "{$info['genus']} {$info['species']}";
    $species[] = gttn_tpps_entity_link($state['ids']['organism_ids'][$key], $species_name, 'Organism');
  }

  $files = array();
  foreach ($state['file_info'] as $page => $page_files) {
    foreach ($page_files as $info) {
      $file = file_load($info['fid']);
      $file_url = check_plain(file_create_url($file->uri));
      $files[] = "<a href='$file_url' target='blank'>$file->filename</a>";
    }
  }

  $data_types = array();
  foreach ($state['saved_values'][GTTN_PAGE_1]['data_type'] as $type) {
    if (!empty($type)) {
      $data_types[] = $type;
    }
  }

  $rows[] = array('Accession', $state['accession']);
  $rows[] = array('Project Name', $state['data']['project']['name']);
  $rows[] = array('Submitter', user_load($state['owner_uid'])->chado_record->name);
  $rows[] = array('Species', implode('<br>', $species));
  $rows[] = array('Data Type', implode('<br>', $data_types));
  $rows[] = array('File Downloads', implode('<br>', $files));
  $rows[] = array('Number of Trees', count($state['data']['trees']));
  $rows[] = array('Number of Samples', count($state['data']['samples']));

  if (!empty($state['data']['dart'])) {
    $rows[] = array('Number of DART Reads', count($state['data']['dart']));
  }
  if (!empty($state['data']['isotope'])) {
    $rows[] = array('Number of Isotope Reads', count($state['data']['isotope']));
  }
  if (!empty($state['data']['genetic'])) {
    $rows[] = array('Number of Genotypes', count($state['data']['genetic']));
  }
  if (!empty($state['data']['anatomy'])) {
    $rows[] = array('Number of Microscope Slides', count($state['data']['anatomy']));
  }

  $vars = array(
    'header' => array(),
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );
  return theme_table($vars);

}

/**
 *
 */
function gttn_tpps_display_submission_trees(array &$state) {
  $params = drupal_get_query_parameters($_POST);
  $page = 0;
  if (!empty($params['page'])) {
    $page = $params['page'];
  }
  $rows = array();
  $per_page = 20;

  $tree_info = $state['data']['trees'];

  $_GET['page'] = $page;
  pager_default_initialize(count($tree_info), $per_page);
  $chunks = array_chunk($tree_info, $per_page, TRUE);

  foreach ($chunks[$page] as $name => $info) {
    $org_id = $info['organism_id'];
    $species_name = $state['saved_values'][GTTN_PAGE_1]['organism'][$info['organism_number']];

    $rows[] = array(
      gttn_tpps_entity_link($info['stock_id'], $name, 'Stock'),
      gttn_tpps_entity_link($org_id, $species_name, 'Organism'),
      $info['lat'],
      $info['lng'],
    );
  }

  $vars = array(
    'header' => array(
      'Identifier',
      'Species',
      'Latitude',
      'Longitude',
    ),
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );
  $table = theme_table($vars);
  $pager = theme('pager', array('quantity', count($tree_info)));
  return $pager . $table;
}

/**
 *
 */
function gttn_tpps_display_submission_samples(array &$state) {
  $params = drupal_get_query_parameters($_POST);
  $page = 0;
  if (!empty($params['page'])) {
    $page = $params['page'];
  }
  $rows = array();
  $per_page = 20;

  $tree_info = $state['data']['trees'];
  $sample_info = $state['data']['samples'];

  $_GET['page'] = $page;
  pager_default_initialize(count($sample_info), $per_page);
  $chunks = array_chunk($sample_info, $per_page, TRUE);

  foreach ($chunks[$page] as $id => $info) {
    $org_id = gttn_tpps_source_get_organism($info['source'], $state);
    $org_number = array_search($org_id, $state['ids']['organism_ids']);
    $species_name = $state['saved_values'][GTTN_PAGE_1]['organism'][$org_number];
    $source = $tree_info[$info['source']] ?? ($sample_info[$info['source']] ?? NULL);

    $rows[] = array(
      gttn_tpps_entity_link($info['stock_id'], $id, 'Stock'),
      !empty($source) ? gttn_tpps_entity_link($source['stock_id'], $source['id'], 'Stock') : '',
      gttn_tpps_entity_link($org_id, $species_name, 'Organism'),
      $info['tissue'],
      $info['dimension'],
      "{$info['date']['month']}-{$info['date']['day']}-{$info['date']['year']}",
      $info['collector'],
      $info['remaining'],
      $info['storage'],
    );
  }

  $vars = array(
    'header' => array(
      'Identifier',
      'Source',
      'Species',
      'Tissue',
      'Dimensions',
      'Collection Date',
      'Collector',
      'Volume Remaining',
      'Storage Location',
    ),
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );
  $table = theme_table($vars);
  $pager = theme('pager', array('quantity', count($sample_info)));
  return $pager . $table;
}

/**
 *
 */
function gttn_tpps_display_submission_dart(array &$state) {
  global $base_url;
  $params = drupal_get_query_parameters($_POST);
  $rows = array();

  $dart_zip = file_load($state['saved_values'][GTTN_PAGE_4]['dart']['raw']);
  $files = gttn_tpps_get_archive_files($dart_zip);
  $dir = dirname($files[0]);
  gttn_tpps_rmdir($dir);
  foreach ($files as $file) {
    $file_name = basename($file);
    if ($file_name[0] != '.') {
      $sample_id = basename($file, ".txt");
      $file_link = "<a href=\"$base_url/gttn-tpps/{$state['accession']}/files/dart:$file_name\">$file_name</a>";
      $rows[] = array(
        $sample_id,
        $file_link,
      );
    }
  }

  $vars = array(
    'header' => array(
      'Sample ID',
      'DART Raw Data File',
    ),
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );
  $table = theme_table($vars);
  return $table;
}

/**
 *
 */
function gttn_tpps_display_submission_isotope(array &$state) {
  // TODO
}

/**
 *
 */
function gttn_tpps_display_submission_genetic(array &$state) {
  $params = drupal_get_query_parameters($_POST);
  $page = 0;
  if (!empty($params['page'])) {
    $page = $params['page'];
  }
  $rows = array();
  $per_page = 20;
  $start = $page * $per_page;

  $query = db_select('chado.genotype', 'g');
  $query->join('chado.stock_genotype', 'sg', 'sg.genotype_id = g.genotype_id');
  $query->join('chado.project_stock', 'ps', 'ps.stock_id = sg.stock_id');
  $query->addExpression('count(g.genotype_id)', 'count');
  $query->condition('ps.project_id', $state['ids']['project_id']);
  $query = $query->execute();
  $total = $query->fetchObject()->count;

  $_GET['page'] = $page;
  pager_default_initialize($total, $per_page);

  $query = db_select('chado.genotype', 'g');
  $query->join('chado.stock_genotype', 'sg', 'sg.genotype_id = g.genotype_id');
  $query->join('chado.project_stock', 'ps', 'ps.stock_id = sg.stock_id');
  $query->join('chado.stock', 's', 's.stock_id = sg.stock_id');
  $query->fields('g', array('genotype_id', 'name', 'uniquename', 'description', 'type_id'));
  $query->fields('s', array('uniquename', 'stock_id'));
  $query->condition('ps.project_id', $state['ids']['project_id']);
  $query->range($start, $per_page);
  $query = $query->execute();

  while (($result = $query->fetchObject())) {
    preg_match('/^GTTN-TGDR...-(.*)$/', $result->s_uniquename, $matches);
    $tree = $matches[1];
    $type = chado_get_cvterm(array(
      'cvterm_id' => $result->type_id,
    ))->name ?? "";
    $rows[] = array(
      gttn_tpps_entity_link($result->stock_id, $tree, 'Stock'),
      gttn_tpps_entity_link($result->genotype_id, $result->uniquename, 'Genotype'),
      $result->description,
      $type,
    );
  }

  $vars = array(
    'header' => array(
      'Tree',
      'Name',
      'Description',
      'Marker Type',
    ),
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );
  $table = theme_table($vars);
  $pager = theme('pager', array('quantity', $total));
  return $pager . $table;
}

/**
 *
 */
function gttn_tpps_display_submission_anatomy(array &$state) {
  $slides = $state['saved_values'][GTTN_PAGE_4]['anatomy']['slides'];
  $rows = array();
  foreach ($slides as $info) {
    if (is_array($info) and !empty($info['image'])) {
      $path = check_plain(file_create_url(file_load($info['image'])->uri));
      $image = "<img src=\"$path\">";
      $rows[] = array(
        $image,
        $info['description'],
      );
    }
  }

  $vars = array(
    'header' => array(
      'Image',
      'Description',
    ),
    'rows' => $rows,
    'attributes' => array('class' => array('view'), 'id' => 'gttn_tpps_table_display'),
    'caption' => '',
    'colgroups' => NULL,
    'sticky' => FALSE,
    'empty' => '',
  );

  return theme_table($vars);
}

/**
 * Returns an entity link based on an id and a name.
 *
 * @param int $id
 *   The record id in CHADO.
 * @param string $name
 *   The display text of the link.
 * @param string $label
 *   The label of the bundle.
 *
 * @return string
 *   The constructed link, or the provided name if no entity is found.
 */
function gttn_tpps_entity_link(int $id, $name, $label) {
  global $base_url;
  $bundle = tripal_load_bundle_entity(array('label' => $label));
  if (empty($bundle)) {
    return $name;
  }
  $entity_id = chado_get_record_entity_by_bundle($bundle, $id);
  if (!empty($entity_id)) {
    return "<a href=\"$base_url/bio_data/$entity_id\">$name</a>";
  }
  return $name;
}
