<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_file_parsing($form_state) {

  $fourthpage = $form_state['saved_values'][GTTN_PAGE_4];
  $firstpage = $form_state['saved_values'][GTTN_PAGE_1];
  $organism_number = $firstpage['organism']['number'];
  $organism_ids = array();
  $file_rank = $form_state['file_rank'];
  $genotype_group = variable_get('gttn_tpps_genotype_group', 10000);

  $project_id = gttn_tpps_create_record('project', array(
    'name' => $form_state['accession'],
  ));

  for ($i = 1; $i <= $organism_number; $i++) {
    $parts = explode(" ", $firstpage['organism'][$i]);
    $genus = $parts[0];
    $species = implode(" ", array_slice($parts, 1));

    if (isset($parts[2]) and ($parts[2] == 'var.' or $parts[2] == 'subsp.')) {
      $infra = implode(" ", array_slice($parts, 2));
    }
    else {
      $infra = NULL;
    }

    $organism_ids[$i] = gttn_tpps_create_record('organism', array(
      'genus' => $genus,
      'species' => $species,
      'infraspecific_name' => $infra,
    ));

    if (isset($fourthpage["organism-$i"]['phenotype'])) {
      $phenotype = $fourthpage["organism-$i"]['phenotype'];
      $phenotype_ids = array();
      $phenotypeprop_ids = array();

      $phenotype_file_id = gttn_tpps_create_record('projectprop', array(
        'project_id' => $project_id,
        'type_id' => '2836',
        'value' => file_create_url(file_load($phenotype['file'])->uri),
        'rank' => $file_rank,
      ));
      $phenotype_file = file_load($phenotype['file']);
      $file_rank++;

      $location = drupal_realpath($phenotype_file->uri);
      $content = gttn_tpps_parse_xlsx($location);
      $groups = $phenotype['file-groups'];

      $tree_id_col = $groups['Tree Identifier']['1'];
      $isotope_columns = $groups['Isotope Data']['0'];

      for ($j = 0; $j < count($content) - 1; $j++) {
        $stock_unique = $content[$j][$tree_id_col];
        foreach ($isotope_columns as $isotope_name) {
          $value = $content[$j][$isotope_name];
          $name = strtolower($isotope_name);
          $phenotype_ids[] = gttn_tpps_create_record('phenotype', array(
            'uniquename' => "$stock_unique-$name",
            'name' => $name,
            'attr_id' => '54491',
            'value' => $value,
          ));
        }
      }

      $phenotype_file->status = FILE_STATUS_PERMANENT;
      $phenotype_file = file_save($phenotype_file);
    }

    if (isset($fourthpage["organism-$i"]['genotype'])) {
      $genotype = $fourthpage["organism-$i"]['genotype'];
      $genotype_count = 0;

      if ($genotype['ref-genome'] == 'bio') {

        $bioproject_id = gttn_tpps_create_record('dbxref', array(
          'db_id' => '176',
          'accession' => $genotype['BioProject-id'],
        ));

        $project_dbxref_id = gttn_tpps_create_record('project_dbxref', array(
          'project_id' => $project_id,
          'dbxref_id' => $bioproject_id,
        ));

        $bioproject_assembly_file_ids = array();
        foreach ($genotype['assembly-auto'] as $key => $val) {
          if ($val == '1') {
            array_push($bioproject_assembly_file_ids, gttn_tpps_create_record('projectprop', array(
              'project_id' => $project_id,
              'type_id' => '2836',
              'value' => "https://www.ncbi.nlm.nih.gov/nuccore/$key",
              'rank' => $file_rank,
            )));
            $file_rank++;
          }
        }
      }
      elseif ($genotype['ref-genome'] == 'manual' or $genotype['ref-genome'] == 'manual2' or $genotype['ref-genome'] == 'url') {
        if ($genotype['tripal_fasta']['file_upload']) {
          // Uploaded new file.
          $assembly_user = $genotype['tripal_fasta']['file_upload'];
          $assembly_user_id = gttn_tpps_create_record('projectprop', array(
            'project_id' => $project_id,
            'type_id' => '2836',
            'value' => file_create_url(file_load($assembly_user)->uri),
            'rank' => $file_rank,
          ));
          $file = file_load($assembly_user);
          $file->status = FILE_STATUS_PERMANENT;
          $file = file_save($file);
          $file_rank++;
        }
        if ($genotype['tripal_fasta']['file_upload_existing']) {
          // Uploaded existing file.
          $assembly_user = $genotype['tripal_fasta']['file_upload_existing'];
          $assembly_user_id = gttn_tpps_create_record('projectprop', array(
            'project_id' => $project_id,
            'type_id' => '2836',
            'value' => file_create_url(file_load($assembly_user)->uri),
            'rank' => $file_rank,
          ));
          $file = file_load($assembly_user);
          $file->status = FILE_STATUS_PERMANENT;
          $file = file_save($file);
          $file_rank++;
        }
        if ($genotype['tripal_fasta']['file_remote']) {
          // Provided url to file.
          $assembly_user = $genotype['tripal_fasta']['file_remote'];
          $assembly_user_id = gttn_tpps_create_record('projectprop', array(
            'project_id' => $project_id,
            'type_id' => '2836',
            'value' => $assembly_user,
            'rank' => $file_rank,
          ));
          $file_rank++;
        }
      }
      elseif ($genotype['ref-genome'] != 'none') {
        $reference_genome_id = gttn_tpps_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '1912',
          'value' => $genotype['ref-genome'],
        ));
      }

      if ($genotype['file-type']['Genotype Assay']) {
        $genotype_file_id = gttn_tpps_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '2836',
          'value' => file_create_url(file_load($genotype['file'])->uri),
          'rank' => $file_rank,
        ));
        $file_rank++;

        $file = file_load($genotype['file']);
        $location = drupal_realpath($file->uri);
        $content = gttn_tpps_parse_xlsx($location);
        $groups = $genotype['file-groups'];
        $tree_id_col = $groups['Tree Id']['1'];

        // Process genotype spreadsheet/assay.
        if ($genotype['marker-type']['SNPs']) {
          // If SNPs marker, the file is a Genotype Assay.
          $genotype_cols = $groups['SNP Data']['2'];
        }
        else {
          // If not SNPs marker, the file is a Genotype spreadsheet, not assay.
          $genotype_cols = $groups['Genotype Data']['2'];
          if (gettype($genotype_cols) != 'array') {
            $genotype_cols = array($genotype_cols);
          }
          $marker = ($genotype['marker-type']['SSRs/cpSSRs']) ? $genotype['SSRs/cpSSRs'] : $genotype['other-marker'];
        }

        foreach ($content as $key => $row) {
          if ($key != 'headers') {
            $tree_id = $row[$tree_id_col];

            $query = db_select('chado.stock', 's')
              ->fields('s', array('organism_id'))
              ->condition('uniquename', $tree_id)
              ->execute()
              ->fetchAssoc();
            $organism_id = $query['organism_id'];
            if (!isset($last_org_id) or $last_org_id !== $organism_id) {
              // If the last organism id is not present or is different from this one, get a new species code. otherwise, keep the last one to avoid some redundant queries.
              $and = db_and()
                ->condition('type_id', '52307')
                ->condition('organism_id', "$organism_id");
              $species_code = db_select('chado.organismprop', 'o')
                ->fields('o', array('value'))
                ->condition($and)
                ->execute()
                ->fetchAssoc();
              $species_code = $species_code['value'];
            }

            $genotype_vals = array();
            foreach ($genotype_cols as $marker_id) {
              $genotype_count++;
              $marker_value = $row[$marker_id];

              if ($genotype_count > $genotype_group) {
                $genotype_count = 0;
                gttn_tpps_create_record('genotype', $genotype_vals);
                $genotype_vals = array();
              }

              if ($genotype['marker-type']['SNPs']) {
                $marker_type_id = 1205;
              }
              elseif ($genotype['marker-type']['SSRs/cpSSRs']) {
                if ($genotype['SSRs/cpSSRs'] == 'cpSSR') {
                  $marker_type_id = 54739;
                }
                else {
                  $marker_type_id = 54733;
                }
              }
              else {
                // type_id for "other genotype marker" should just be 'genotype'.
                $marker_type_id = 1464;
              }

              $genotype_vals[] = array(
                'name' => "$marker_id-$species_code-$marker_value",
                'uniquename' => "$marker_id-$species_code-$marker_value",
                'description' => $marker_value,
                'type_id' => "$marker_type_id",
              );
            }

            // Save the organism id for the next row.
            $last_org_id = $organism_id;
          }
        }
        gttn_tpps_create_record('genotype', $genotype_vals);
        unset($genotype_vals);
        $genotype_count = 0;

        $file->status = FILE_STATUS_PERMANENT;
        $file = file_save($file);
      }

      if ($genotype['file-type']['Assay Design'] and $genotype['marker-type']['SNPs']) {
        $genotype_file_id = gttn_tpps_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '2836',
          'value' => file_create_url(file_load($genotype['assay-design'])->uri),
          'rank' => $file_rank,
        ));
        $file = file_load($genotype['assay-design']);
        $file->status = FILE_STATUS_PERMANENT;
        $file = file_save($file);
        $file_rank++;
      }

      if ($genotype['file-type']['VCF']) {
        $genotype_vcf_id = gttn_tpps_create_record('projectprop', array(
          'project_id' => $project_id,
          'type_id' => '2836',
          'value' => file_create_url(file_load($genotype['vcf'])->uri),
          'rank' => $file_rank,
        ));
        $file_rank++;

        $current_id = $organism_ids[$i];
        $and = db_and()
          ->condition('type_id', '52307')
          ->condition('organism_id', "$current_id");
        $species_code = db_select('chado.organismprop', 'o')
          ->fields('o', array('value'))
          ->condition($and)
          ->execute()
          ->fetchAssoc();
        $species_code = $species_code['value'];
        $marker = 'SNP';

        $vcf_file = file_load($genotype['vcf']);
        $location = drupal_realpath($vcf_file->uri);
        $vcf_content = fopen($location, 'r');

        // Inserts are WAY faster when done as a group.
        $genotype_vals = array();
        $genotypeprop_vals = array();
        $format = "";
        $first = TRUE;

        // dpm('start: ' . date('r'));.
        while (($vcf_line = fgets($vcf_content)) !== FALSE) {
          if ($vcf_line[0] != '#') {
            $genotype_count++;
            $vcf_line = explode("\t", $vcf_line);
            $scaffold_id = &$vcf_line[0];
            $position = &$vcf_line[1];
            $ref = &$vcf_line[3];
            $alt = &$vcf_line[4];
            $qual = &$vcf_line[5];
            $filter = &$vcf_line[6];
            $info = &$vcf_line[7];

            $description = "$ref:$alt";

            // Tripal Job has issues when all submissions are made at the same time, so break them up into groups of 10,000 genotypes along with their relevant genotypeprops.
            if ($genotype_count > $genotype_group) {
              $genotype_count = 0;
              gttn_tpps_create_record('genotype', $genotype_vals);
              $genotype_vals = array();
              gttn_tpps_create_record('genotypeprop', $genotypeprop_vals);
              $genotypeprop_vals = array();
            }

            $genotype_vals[] = array(
              'name' => "$marker-$species_code-$scaffold_id-$position",
              'uniquename' => "$marker-$species_code-$scaffold_id-$position-$description",
              'description' => $description,
              'type_id' => '1205',
            );

            if ($first) {
              $first = FALSE;
              $genotype_id = gttn_tpps_create_record('genotype', $genotype_vals[0]);
              $genotype_vals = array();
            }

            if ($format != "") {
              $genotypeprop_vals[] = array(
                'genotype_id' => $genotype_id,
                'type_id' => '52197',
                'value' => $format,
              );
            }

            // Quality score.
            $genotypeprop_vals[] = array(
              'genotype_id' => $genotype_id,
              'type_id' => '2079',
              'value' => $qual,
            );

            // filter: pass/fail.
            $genotypeprop_vals[] = array(
              'genotype_id' => $genotype_id,
              'type_id' => '52008',
              'value' => ($filter == '.') ? "P" : "NP",
            );

            // Break up info column.
            $info_vals = explode(";", $info);
            foreach ($info_vals as $key => $val) {
              $parts = explode("=", $val);
              unset($info_vals[$key]);
              $info_vals[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
            }

            // Allele frequency, assuming that the info code for allele frequency is 'AF'.
            if (isset($info_vals['AF']) and $info_vals['AF'] != '') {
              $genotypeprop_vals[] = array(
                'genotype_id' => $genotype_id,
                'type_id' => '2496',
                'value' => $info_vals['AF'],
              );
            }

            // Depth coverage, assuming that the info code for depth coverage is 'DP'.
            if (isset($info_vals['DP']) and $info_vals['DP'] != '') {
              $genotypeprop_vals[] = array(
                'genotype_id' => $genotype_id,
                'type_id' => '52046',
                'value' => $info_vals['DP'],
              );
            }

            // Number of samples, assuming that the info code for number of samples is 'NS'.
            if (isset($info_vals['NS']) and $info_vals['NS'] != '') {
              $genotypeprop_vals[] = array(
                'genotype_id' => $genotype_id,
                'type_id' => '128971',
                'value' => $info_vals['NS'],
              );
            }
            $genotype_id++;
          }
          elseif (preg_match('/##FORMAT=/', $vcf_line)) {
            $format .= substr($vcf_line, 9, -1);
          }
        }
        // Insert the last set of values.
        gttn_tpps_create_record('genotype', $genotype_vals);
        gttn_tpps_create_record('genotypeprop', $genotypeprop_vals);
        unset($genotype_vals);
        unset($genotypeprop_vals);
        // dpm('done: ' . date('r'));.
        $vcf_file->status = FILE_STATUS_PERMANENT;
        $vcf_file = file_save($vcf_file);
      }
    }

    if (isset($fourthpage["organism-$i"]['environment'])) {
      $environment = $fourthpage["organism-$i"]['environment'];
      $env_layers_check = $environment['use_layers'];
      $env_layers = $environment['env_layers'];
      $env_number = $environment['number'];

      if ($env_layers_check) {
        foreach ($env_layers as $layer => $response) {
          if (gettype($response) == 'array') {
            $params = $response;
          }
          else {
            $params = FALSE;
          }

          $query = db_select('cartogratree_layers', 'l')
            ->fields('l', array('layer_id'))
            ->condition('l.title', $layer)
            ->range(0, 1)
            ->execute();

          $result = $query->fetchObject();
          $layer_id = $result->layer_id;

          foreach ($params as $param_id => $param_response) {
            if ($param_response) {
              $type = variable_get("gttn_tpps_param_{$param_id}_type", NULL);

            }
          }

          // Record layer.
        }
      }

      for ($j = 1; $j <= $env_number; $j++) {
        $current_env = $environment['env_manual'][$j];
        $name = $current_env['name'];
        $desc = $current_env['description'];
        $unit = $current_env['units'];
        $val = $current_env['value'];

        $env_id = gttn_tpps_create_record('phenotype', array(
          'uniquename' => "$name-{$form_state['accession']}-$j",
          'name' => $name,
          'value' => $val,
        ));

        gttn_tpps_create_record('phenotypeprop', array(
          'phenotype_id' => $env_id,
          'type_id' => '2871',
          'value' => $desc,
        ));

        gttn_tpps_create_record('phenotypeprop', array(
          'phenotype_id' => $env_id,
          'type_id' => '2883',
          'value' => $unit,
        ));
      }
    }
  }

  return;
}
