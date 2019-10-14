<?php

/**
 * @file
 */

/**
 *
 */
function genotype(&$form, &$form_state, $values, $id, $genotype_upload_location) {

  $fields = array(
    '#type' => 'fieldset',
    '#title' => t('<div class="fieldset-title">Genotype Information:</div>'),
    '#collapsible' => TRUE,
  );

  page_4_marker_info($fields, $values, $id);

  $fields['marker-type']['SNPs']['#ajax'] = array(
    'callback' => 'snps_file_callback',
    'wrapper' => "edit-$id-genotype-file-ajax-wrapper",
  );

  page_4_ref($fields, $form_state, $values, $id, $genotype_upload_location);

  $fields['file-type'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Genotype File Types (select all that apply): *'),
    '#options' => array(
      'Genotype Assay' => 'Genotype Spreadsheet/Assay',
      'Assay Design' => 'Assay Design',
      'VCF' => 'VCF',
    ),
  );

  $fields['file-type']['Assay Design']['#states'] = array(
    'visible' => array(
      ':input[name="' . $id . '[genotype][marker-type][SNPs]"]' => array('checked' => TRUE),
    ),
  );

  $fields['file'] = array(
    '#type' => 'managed_file',
    '#title' => t('Genotype Spreadsheet File: please provide a spreadsheet with columns for the Tree ID of genotypes used in this study: *'),
    '#upload_location' => "$genotype_upload_location",
    '#upload_validators' => array(
      'file_validate_extensions' => array('xlsx'),
    ),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][file-type][Genotype Assay]"]' => array('checked' => TRUE),
      ),
    ),
    '#description' => 0,
    '#tree' => TRUE,
  );

  $assay_desc = "Please upload a spreadsheet file containing Genotype Assay data. When your file is uploaded, you will be shown a table with your column header names, several drop-downs, and the first few rows of your file. You will be asked to define the data type for each column, using the drop-downs provided to you. If a column data type does not fit any of the options in the drop-down menu, you may set that drop-down menu to \"N/A\". Your file must contain one column with the Tree Identifier, and one column for each SNP data associated with the study. Column data types will default to \"SNP Data\", so please leave any columns with SNP data as the default.";
  $spreadsheet_desc = "Please upload a spreadsheet file containing Genotype data. When your file is uploaded, you will be shown a table with your column header names, several drop-downs, and the first few rows of your file. You will be asked to define the data type for each column, using the drop-downs provided to you. If a column data type does not fit any of the options in the drop-down menu, you may set that drop-down menu to \"N/A\". Your file must contain one column with the Tree Identifier.";
  if (isset($form_state['complete form'][$id]['genotype']['marker-type']['SNPs']['#value']) and $form_state['complete form'][$id]['genotype']['marker-type']['SNPs']['#value']) {
    $fields['file']['#description'] = $assay_desc;
  }
  if (!$fields['file']['#description'] and !isset($form_state['complete form'][$id]['genotype']['marker-type']['SNPs']['#value']) and isset($values[$id]['genotype']['marker-type']['SNPs']) and $values[$id]['genotype']['marker-type']['SNPs']) {
    $fields['file']['#description'] = $assay_desc;
  }
  if (!$fields['file']['#description']) {
    $fields['file']['#description'] = $spreadsheet_desc;
  }

  $fields['file']['empty'] = array(
    '#default_value' => isset($values[$id]['genotype']['file']['empty']) ? $values[$id]['genotype']['file']['empty'] : 'NA',
  );

  $fields['file']['columns'] = array(
    '#description' => 'Please define which columns hold the required data: Tree Identifier, SNP Data',
  );

  $column_options = array(
    'N/A',
    'Tree Id',
    'SNP Data',
  );

  if (isset($form_state['complete form'][$id]['genotype']['marker-type']['SNPs']['#value']) and !$form_state['complete form'][$id]['genotype']['marker-type']['SNPs']['#value']) {
    $column_options[2] = 'Genotype Data';
  }
  elseif (!isset($form_state['complete form'][$id]['genotype']['marker-type']['SNPs']['#value']) and isset($values[$id]['genotype']['marker-type']['SNPs']) and !$values[$id]['genotype']['marker-type']['SNPs']) {
    $column_options[2] = 'Genotype Data';
  }

  $fields['file']['columns-options'] = array(
    '#type' => 'hidden',
    '#value' => $column_options,
  );

  $fields['file']['no-header'] = array();

  $fields['assay-design'] = array(
    '#type' => 'managed_file',
    '#title' => 'Genotype Assay Design File: *',
    '#upload_location' => "$genotype_upload_location",
    '#upload_validators' => array(
      'file_validate_extensions' => array('xlsx'),
    ),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][file-type][Assay Design]"]' => array('checked' => TRUE),
        ':input[name="' . $id . '[genotype][marker-type][SNPs]"]' => array('checked' => TRUE),
      ),
    ),
    '#tree' => TRUE,
  );

  if (isset($fields['assay-design']['#value'])) {
    $fields['assay-design']['#default_value'] = $fields['assay-design']['#value'];
  }
  if (isset($fields['assay-design']['#default_value']) and $fields['assay-design']['#default_value'] and ($file = file_load($fields['assay-design']['#default_value']))) {
    // Stop using the file so it can be deleted if the user clicks 'remove'.
    file_usage_delete($file, 'gttn_tpps', 'gttn_tpps_project', substr($form_state['accession'], 4));
  }

  $fields['vcf'] = array(
    '#type' => 'managed_file',
    '#title' => t('Genotype VCF File: *'),
    '#upload_location' => "$genotype_upload_location",
    '#upload_validators' => array(
      'file_validate_extensions' => array('vcf'),
    ),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][file-type][VCF]"]' => array('checked' => TRUE),
      ),
    ),
    '#tree' => TRUE,
  );

  if (isset($fields['vcf']['#value'])) {
    $fields['vcf']['#default_value'] = $fields['vcf']['#value'];
  }
  if (isset($fields['vcf']['#default_value']) and $fields['vcf']['#default_value'] and ($file = file_load($fields['vcf']['#default_value']))) {
    // Stop using the file so it can be deleted if the user clicks 'remove'.
    file_usage_delete($file, 'gttn_tpps', 'gttn_tpps_project', substr($form_state['accession'], 4));
  }

  return $fields;
}

/**
 *
 */
function page_4_ref(&$fields, &$form_state, $values, $id, $genotype_upload_location) {
  global $user;
  $uid = $user->uid;

  $options = array(
    'key' => 'filename',
    'recurse' => FALSE,
  );

  $genome_dir = variable_get('gttn_tpps_local_genome_dir', NULL);
  $ref_genome_arr = array();
  $ref_genome_arr[0] = '- Select -';

  if ($genome_dir) {
    $results = file_scan_directory($genome_dir, '/^([A-Z][a-z]{3})$/', $options);
    foreach ($results as $key => $value) {
      $query = db_select('chado.organismprop', 'organismprop')
        ->fields('organismprop', array('organism_id'))
        ->condition('value', $key)
        ->execute()
        ->fetchAssoc();
      $query = db_select('chado.organism', 'organism')
        ->fields('organism', array('genus', 'species'))
        ->condition('organism_id', $query['organism_id'])
        ->execute()
        ->fetchAssoc();

      $versions = file_scan_directory("$genome_dir/$key", '/^v([0-9]|.)+$/', $options);
      foreach ($versions as $item) {
        $opt_string = $query['genus'] . " " . $query['species'] . " " . $item->filename;
        $ref_genome_arr[$opt_string] = $opt_string;
      }
    }
  }

  $ref_genome_arr["url"] = 'I can provide a URL to the website of my reference file(s)';
  $ref_genome_arr["bio"] = 'I can provide a GenBank accession number (BioProject, WGS, TSA) and select assembly file(s) from a list';
  $ref_genome_arr["manual"] = 'I can upload my own reference genome file';
  $ref_genome_arr["manual2"] = 'I can upload my own reference transcriptome file';
  $ref_genome_arr["none"] = 'I am unable to provide a reference assembly';

  $fields['ref-genome'] = array(
    '#type' => 'select',
    '#title' => t('Reference Assembly used: *'),
    '#options' => $ref_genome_arr,
  );

  $fields['BioProject-id'] = array(
    '#type' => 'textfield',
    '#title' => t('BioProject Accession Number: *'),
    '#ajax' => array(
      'callback' => 'ajax_bioproject_callback',
      'wrapper' => "$id-assembly-auto",
    ),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][ref-genome]"]' => array('value' => 'bio'),
      ),
    ),
  );

  $fields['assembly-auto'] = array(
    '#type' => 'fieldset',
    '#title' => t('Waiting for BioProject accession number...'),
    '#tree' => TRUE,
    '#prefix' => "<div id='$id-assembly-auto'>",
    '#suffix' => '</div>',
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][ref-genome]"]' => array('value' => 'bio'),
      ),
    ),
  );

  if (isset($form_state['values'][$id]['genotype']['BioProject-id']) and $form_state['values'][$id]['genotype']['BioProject-id'] != '') {
    $bio_id = $form_state['values']["$id"]['genotype']['BioProject-id'];
    $form_state['saved_values'][GTTN_PAGE_4][$id]['genotype']['BioProject-id'] = $form_state['values'][$id]['genotype']['BioProject-id'];
  }
  elseif (isset($form_state['saved_values'][GTTN_PAGE_4][$id]['genotype']['BioProject-id']) and $form_state['saved_values'][GTTN_PAGE_4][$id]['genotype']['BioProject-id'] != '') {
    $bio_id = $form_state['saved_values'][GTTN_PAGE_4][$id]['genotype']['BioProject-id'];
  }
  elseif (isset($form_state['complete form']['organism-1']['genotype']['BioProject-id']['#value']) and $form_state['complete form']['organism-1']['genotype']['BioProject-id']['#value'] != '') {
    $bio_id = $form_state['complete form']['organism-1']['genotype']['BioProject-id']['#value'];
  }

  if (isset($bio_id) and $bio_id != '') {

    if (strlen($bio_id) > 5) {
      $bio_id = substr($bio_id, 5);
    }

    $options = array();
    $url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/elink.fcgi?dbfrom=bioproject&db=nuccore&id=" . $bio_id;
    $response_xml_data = file_get_contents($url);
    $link_types = simplexml_load_string($response_xml_data)->children()->children()->LinkSetDb;

    if (preg_match('/<LinkSetDb>/', $response_xml_data)) {

      foreach ($link_types as $type_xml) {
        $type = $type_xml->LinkName->__tostring();

        switch ($type) {
          case 'bioproject_nuccore_tsamaster':
            $suffix = 'TSA';
            break;

          case 'bioproject_nuccore_wgsmaster':
            $suffix = 'WGS';
            break;

          default:
            continue 2;
        }

        foreach ($type_xml->Link as $link) {
          $options[$link->Id->__tostring()] = $suffix;
        }
      }

      $fields['assembly-auto']['#title'] = '<div class="fieldset-title">Select all that apply: *</div>';
      $fields['assembly-auto']['#collapsible'] = TRUE;

      foreach ($options as $item => $suffix) {
        $fields['assembly-auto']["$item"] = array(
          '#type' => 'checkbox',
          '#title' => "$item ($suffix) <a href=\"https://www.ncbi.nlm.nih.gov/nuccore/$item\" target=\"blank\">View on NCBI</a>",
        );
      }
    }
    else {
      $fields['assembly-auto']['#description'] = t('We could not find any assembly files related to that BioProject. Please ensure your accession number is of the format "PRJNA#"');
    }
  }

  require_once drupal_get_path('module', 'tripal') . '/includes/tripal.importer.inc';
  $class = 'FASTAImporter';
  tripal_load_include_importer_class($class);
  $tripal_upload_location = "public://tripal/users/$uid";

  $fasta = tripal_get_importer_form(array(), $form_state, $class);
  // dpm($fasta);
  $fasta['#type'] = 'fieldset';
  $fasta['#title'] = 'Tripal FASTA Loader';
  $fasta['#states'] = array(
    'visible' => array(
        array(
          array(':input[name="' . $id . '[genotype][ref-genome]"]' => array('value' => 'url')),
          'or',
          array(':input[name="' . $id . '[genotype][ref-genome]"]' => array('value' => 'manual')),
          'or',
          array(':input[name="' . $id . '[genotype][ref-genome]"]' => array('value' => 'manual2')),
        ),
    ),
  );

  unset($fasta['file']['file_local']);
  unset($fasta['organism_id']);
  unset($fasta['method']);
  unset($fasta['match_type']);
  $db = $fasta['additional']['db'];
  unset($fasta['additional']);
  $fasta['db'] = $db;
  $fasta['db']['#collapsible'] = TRUE;
  unset($fasta['button']);

  $upload = array(
    '#type' => 'managed_file',
    '#title' => '',
    '#description' => 'Remember to click the "Upload" button below to send your file to the server.  This interface is capable of uploading very large files.  If you are disconnected you can return, reload the file and it will resume where it left off.  Once the file is uploaded the "Upload Progress" will indicate "Complete".  If the file is already present on the server then the status will quickly update to "Complete".',
    '#upload_validators' => array(
      'file_validate_extensions' => array(implode(' ', $class::$file_types)),
    ),
    '#upload_location' => $tripal_upload_location,
  );

  $fasta['file']['file_upload'] = $upload;
  $fasta['analysis_id']['#required'] = $fasta['seqtype']['#required'] = FALSE;

  $fields['tripal_fasta'] = $fasta;
  // dpm($fasta);
  return $fields;
}

/**
 *
 */
function page_4_marker_info(&$fields, $values, $id) {

  $fields['marker-type'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Marker Type (select all that apply): *'),
    '#options' => drupal_map_assoc(array(
      t('SNPs'),
      t('SSRs/cpSSRs'),
      t('Other'),
    )),
  );

  $fields['SNPs'] = array(
    '#type' => 'fieldset',
    '#title' => t('<div class="fieldset-title">SNPs Information:</div>'),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][marker-type][SNPs]"]' => array('checked' => TRUE),
      ),
    ),
    '#collapsible' => TRUE,
  );

  $fields['SNPs']['genotyping-design'] = array(
    '#type' => 'select',
    '#title' => t('Define Experimental Design: *'),
    '#options' => array(
      0 => '- Select -',
      1 => 'GBS',
      2 => 'Targeted Capture',
      3 => 'Whole Genome Resequencing',
      4 => 'RNA-Seq',
      5 => 'Genotyping Array',
    ),
  );

  $fields['SNPs']['GBS'] = array(
    '#type' => 'select',
    '#title' => t('GBS Type: *'),
    '#options' => array(
      0 => '- Select -',
      1 => 'RADSeq',
      2 => 'ddRAD-Seq',
      3 => 'NextRAD',
      4 => 'RAPTURE',
      5 => 'Other',
    ),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][SNPs][genotyping-design]"]' => array('value' => '1'),
      ),
    ),
  );

  $fields['SNPs']['GBS-other'] = array(
    '#type' => 'textfield',
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][SNPs][GBS]"]' => array('value' => '5'),
        ':input[name="' . $id . '[genotype][SNPs][genotyping-design]"]' => array('value' => '1'),
      ),
    ),
  );

  $fields['SNPs']['targeted-capture'] = array(
    '#type' => 'select',
    '#title' => t('Targeted Capture Type: *'),
    '#options' => array(
      0 => '- Select -',
      1 => 'Exome Capture',
      2 => 'Other',
    ),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][SNPs][genotyping-design]"]' => array('value' => '2'),
      ),
    ),
  );

  $fields['SNPs']['targeted-capture-other'] = array(
    '#type' => 'textfield',
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][SNPs][targeted-capture]"]' => array('value' => '2'),
        ':input[name="' . $id . '[genotype][SNPs][genotyping-design]"]' => array('value' => '2'),
      ),
    ),
  );

  $fields['SSRs/cpSSRs'] = array(
    '#type' => 'textfield',
    '#title' => t('Define SSRs/cpSSRs Type: *'),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][marker-type][SSRs/cpSSRs]"]' => array('checked' => TRUE),
      ),
    ),
  );

  $fields['other-marker'] = array(
    '#type' => 'textfield',
    '#title' => t('Define Other Marker Type: *'),
    '#states' => array(
      'visible' => array(
        ':input[name="' . $id . '[genotype][marker-type][Other]"]' => array('checked' => TRUE),
      ),
    ),
  );

  return $fields;
}
