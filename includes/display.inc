<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_flatten($state) {
  $data = isset($state['saved_values']) ? $state['saved_values'] : NULL;

  if (!$data) {
    return;
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
  if (!empty($type_page['type'])) {
    $data["Submission Type"] = $type_page['type'];
  }
  unset($data[GTTN_TYPE_PAGE]);

  $page_1 = &$data[GTTN_PAGE_1];
  $org = &$page_1['organism'];

  if (isset($page_1['#cleared']) and $page_1['#cleared']) {
    $data['#page_2_cleared'] = TRUE;
  }
  else {
    $data['#page_2_cleared'] = FALSE;
  }
  if (isset($org['number'])) {
    $data['#number_organisms'] = $org['number'];
    for ($i = 1; $i <= $data['#number_organisms']; $i++) {
      if (isset($org["$i"]) and $org["$i"] != '') {
        $data["Species $i"] = $org["$i"];
      }
    }
  }
  unset($data[GTTN_PAGE_1]);

  $page_3 = &$data[GTTN_PAGE_3];

  if (isset($page_3['#cleared']) and $page_3['#cleared']) {
    $data['#page_3_cleared'] = TRUE;
  }
  else {
    $data['#page_3_cleared'] = FALSE;
  }
  $page_3 = &$data[GTTN_PAGE_3]['tree-accession'];
  if (isset($page_3['check']) and $page_3['check']) {
    for ($i = 1; $i <= $data['#number_organisms']; $i++) {
      if (isset($page_3["species-$i"]['file']) and $page_3["species-$i"]['file']) {
        $organism_name = $data["Species $i"];
        $data["$organism_name Accession File"] = $page_3["species-$i"]['file'];
        if (!empty($page_3["species-$i"]['file-groups']['Location (latitude/longitude or country/state)']['#type']) and $page_3["species-$i"]['file-groups']['Location (latitude/longitude or country/state)']['#type'] == "gps") {
          $data["#{$organism_name}_accession_no_header"] = isset($page_3["species-$i"]['file-no-header']) ? $page_3["species-$i"]['file-no-header'] : '0';
          $data["#{$organism_name}_accession_id_col"] = $page_3["species-$i"]['file-groups']['Tree Id'][1];
          $data["#{$organism_name}_accession_lat_col"] = $page_3["species-$i"]['file-groups']['Location (latitude/longitude or country/state)'][4];
          $data["#{$organism_name}_accession_long_col"] = $page_3["species-$i"]['file-groups']['Location (latitude/longitude or country/state)'][5];
        }
      }
    }
  }
  elseif (isset($page_3['file']) and $page_3['file']) {
    $data['Tree Accession File'] = $page_3['file'];
    if (!empty($page_3['file-groups']['Location (latitude/longitude or country/state)']['#type']) and $page_3['file-groups']['Location (latitude/longitude or country/state)']['#type'] == "gps") {
      $data["#accession_no_header"] = isset($page_3['file-no-header']) ? $page_3['file-no-header'] : '0';
      $data["#accession_id_col"] = $page_3['file-groups']['Tree Id'][1];
      $data["#accession_lat_col"] = $page_3['file-groups']['Location (latitude/longitude or country/state)'][4];
      $data["#accession_long_col"] = $page_3['file-groups']['Location (latitude/longitude or country/state)'][5];
    }
  }
  unset($data[GTTN_PAGE_3]);

  $page_4 = &$data[GTTN_PAGE_4];

  if (isset($page_4['#cleared']) and $page_4['#cleared']) {
    $data['#page_4_cleared'] = TRUE;
  }
  else {
    $data['#page_4_cleared'] = FALSE;
  }
  for ($i = 1; $i <= $data['#number_organisms']; $i++) {
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
  }
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
function gttn_tpps_table_display($state) {

  $accession = $state['saved_values'][GTTN_PAGE_4]['accession'];
  $display = "<div class='gttn_tpps_complete_submission'><table id='gttn_tpps_$accession' style='width:-webkit-fill-available' border='1'><thead>";
  $display .= "</thead><tbody>";

  $flat = gttn_tpps_flatten($state);

  foreach ($flat as $key => $val) {
    if ($key[0] !== '#') {
      $key_str = filter_xss(check_plain($key));
      $val_str = filter_xss(check_plain($val));

      if (preg_match('/File/', $key) and ($file_name = file_load($val)->filename)) {
        // Create links to files.
        $file_url = check_plain(file_create_url(file_load($val)->uri));
        $val_str = "<a href='$file_url' target='blank'>$file_name</a>";
      }

      $display .= "<tr><td>$key_str</td><td>$val_str</td></tr>";
    }
  }

  $display .= "</tbody></table></div><br>";

  return $display;
}

/**
 *
 */
function gttn_tpps_completed_display($form, $form_state) {

  global $user;
  global $base_url;
  $params = drupal_get_query_parameters();
  $accession = $params['id'];
  $link = "$base_url/gttn_tpps_details/accession/$accession";

  $old_form_state = variable_get('gttn_tpps_complete_' . $user->mail . $accession);
  dpm($old_form_state);
  $values = $old_form_state['saved_values'];

  $css = "
    .tgdr_complete_submission {
        background: #ffffff;
        border: 1px solid #cccccc;
        border-radius: 4px;
        padding: 0px 10px;
    }		
    ";
  drupal_add_css($css, 'inline');

  $display = gttn_tpps_table_display($old_form_state);

  $form['a'] = array(
    '#type' => 'hidden',
    '#suffix' => $display,
  );

  return $form;
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
      $acc = "<a href='$base_url/gttn-tpps/{$state['accession']}' style='text-decoration: underline;'>{$state['accession']}</a>";
    }

    $rows[] = array(
      isset($acc) ? $acc : "",
      isset($type_page['name']) ? $type_page['name'] : "",
      isset($page_1['organism']['number']) ? $page_1['organism']['number'] : "",
      isset($state['status']) ? $state['status'] : "",
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
