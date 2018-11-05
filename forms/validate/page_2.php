<?php

function page_2_validate(&$form, &$form_state){
    //validate input for page 2
    
    if ($form_state['submitted'] == '1'){
        
        function vcf($vcf_file, $form_state){
            $file = file(file_load($vcf_file)->uri);
            $content = array();
            $species_ids = get_species_ids($form_state);
            $id_from_genotype = array();
            
            foreach ($file as $row){
                if ($row[0] != '#'){
                    $row = explode("\t", $row);
                    $len = count($row);
                    for ($i = 0; $i < $len; $i++){
                        if ($row[$i] === ''){
                            unset($row[$i]);
                        }
                    }
                    array_values($row);
                    array_push($id_from_genotype, $row[2]);
                }
            }
            
            foreach($id_from_genotype as $id){
                if (!in_array($id, $species_ids)){
                    form_set_error('genotype][spreadsheet', "Identifier $id was not found in the sampling metadata files. Please re-upload those files with location information of $id, or re-upload the genotype VCF file without data for $id.");
                }
            }
        }
        
        function spreadsheet($spreadsheet, $form_state){
            $file = file(file_load($spreadsheet)->uri);
            $file_type = file_load($spreadsheet)->filemime;
            $compare_ids = FALSE;
            
            if ($file_type == 'text/csv' or $file_type == 'text/plain'){
                $content = explode("\r", $file[0]);
                $columns = ($file_type == 'text/plain') ? explode("\t", $content[0]) : explode(",", $content[0]);
                $id_omitted = TRUE;
                $seq_1_omitted = TRUE;
                $seq_2_omitted = TRUE;
                $allele_omitted = TRUE;

                foreach($columns as $key => $col){
                    $columns[$key] = trim($col);
                    if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER|)$/', $columns[$key]) == 1){
                        $id_key = $key;
                        $id_omitted = FALSE;
                    }
                    elseif (preg_match('/^(sequence 1|sequence_1|Sequence_1|Sequence 1|SEQUENCE 1|SEQUENCE_1)$/', $columns[$key]) == 1){
                        $seq_1_omitted = FALSE;
                    }
                    elseif (preg_match('/^(sequence 2|sequence_2|Sequence_2|Sequence 2|SEQUENCE 2|SEQUENCE_2)$/', $columns[$key]) == 1){
                        $seq_2_omitted = FALSE;
                    }
                    elseif (preg_match('/^(allele|Allele|ALLELE)$/', $columns[$key]) == 1){
                        $allele_omitted = FALSE;
                    }
                }

                if ($id_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                }
                elseif ($seq_1_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your Sequence 1 column. Please resubmit your file with a column named "Sequence 1", with probe sequence 1 of each tree.');
                }
                elseif ($seq_2_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your Sequence 2 column. Please resubmit your file with a column named "Sequence 2", with probe sequence 2 of each tree.');
                }
                elseif ($allele_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your Allele column. Please resubmit your file with a column named "Allele", with the allele of each tree.');
                }
                else{
                    $compare_ids = TRUE;
                }
            }
            elseif ($file_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
                $location = '/var/www/Drupal/sites/default/files/' . file_load($spreadsheet)->filename;

                $content = gttn_parse_xlsx($location);
                $columns = $content['headers'];
                $id_omitted = TRUE;
                $seq_1_omitted = TRUE;
                $seq_2_omitted = TRUE;
                $allele_omitted = TRUE;

                foreach($columns as $key => $col){
                    $columns[$key] = trim($col);
                    if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER|)$/', $columns[$key]) == 1){
                        $id_key = $key;
                        $id_omitted = FALSE;
                    }
                    elseif (preg_match('/^(sequence 1|sequence_1|Sequence_1|Sequence 1|SEQUENCE 1|SEQUENCE_1)$/', $columns[$key]) == 1){
                        $seq_1_omitted = FALSE;
                    }
                    elseif (preg_match('/^(sequence 2|sequence_2|Sequence_2|Sequence 2|SEQUENCE 2|SEQUENCE_2)$/', $columns[$key]) == 1){
                        $seq_2_omitted = FALSE;
                    }
                    elseif (preg_match('/^(allele|Allele|ALLELE)$/', $columns[$key]) == 1){
                        $allele_omitted = FALSE;
                    }
                }

                if ($id_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                }
                elseif ($seq_1_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your Sequence 1 column. Please resubmit your file with a column named "Sequence 1", with probe sequence 1 of each tree.');
                }
                elseif ($seq_2_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your Sequence 2 column. Please resubmit your file with a column named "Sequence 2", with probe sequence 2 of each tree.');
                }
                elseif ($allele_omitted){
                    form_set_error("genotype][spreadsheet", 'Genotype Spreadsheet: We were unable to find your Allele column. Please resubmit your file with a column named "Allele", with the allele of each tree.');
                }
                else{
                    $compare_ids = TRUE;
                }
            }
            
            if ($compare_ids){
                $id_from_genotype = array();
                
                if ($file_type == 'text/csv' or $file_type == 'text/plain'){
                    $content = array_slice($content, 1);
                    $len = count($content);
                    for ($i = 0; $i < $len; $i++){
                        $content[$i] = ($file_type == 'text/plain') ? explode("\t", $content[$i]) : explode(",", $content[$i]);
                        array_push($id_from_genotype, $content[$i][$id_key]);
                    }
                }
                else{
                    $id_key = $content['headers'][$id_key];
                    $len = count($content) - 1;
                    
                    for ($i = 0; $i < $len; $i++){
                        array_push($id_from_genotype, $content[$i][$id_key]);
                    }
                }
                
                $species_ids = get_species_ids($form_state);
                
                foreach($id_from_genotype as $id){
                    if (!in_array($id, $species_ids)){
                        form_set_error('genotype][spreadsheet', "Identifier $id was not found in the sampling metadata files. Please re-upload those files with location information of $id");
                    }
                }
                
            }
        }
        
        function isotope($isotope_file, $form_state){
            $file = file(file_load($isotope_file)->uri);
            $file_type = file_load($isotope_file)->filemime;
            $location = '/var/www/Drupal/sites/default/files/' . file_load($isotope_file)->filename;
            
            if ($file_type == 'text/csv' or $file_type == 'text/plain'){
                $content = explode("\r", $file[0]);
                $columns = ($file_type == 'text/plain') ? explode("\t", $content[0]) : explode(",", $content[0]);
                $id_omitted = TRUE;

                foreach($columns as $key => $col){
                    $columns[$key] = trim($col);
                    if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER|)$/', $columns[$key]) == 1){
                        $id_key = $key;
                        $id_omitted = FALSE;
                    }
                }

                if ($id_omitted){
                    form_set_error("phenotype][isotope", 'Isotope File: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                }
                else{
                    $compare_ids = TRUE;
                }
            }
            elseif ($file_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
                
                $content = gttn_parse_xlsx($location);
                $columns = $content['headers'];
                $id_omitted = TRUE;

                foreach($columns as $key => $col){
                    $columns[$key] = trim($col);
                    if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER|)$/', $columns[$key]) == 1){
                        $id_key = $key;
                        $id_omitted = FALSE;
                    }
                }

                if ($id_omitted){
                    form_set_error("phenotype][isotope", 'Isotope File: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                }
                else{
                    $compare_ids = TRUE;
                }
            }
            
            if ($compare_ids){
                $id_from_phenotype = array();
                
                if ($file_type == 'text/csv' or $file_type == 'text/plain'){
                    $content = array_slice($content, 1);
                    $len = count($content);
                    for ($i = 0; $i < $len; $i++){
                        $content[$i] = ($file_type == 'text/plain') ? explode("\t", $content[$i]) : explode(",", $content[$i]);
                        array_push($id_from_phenotype, $content[$i][$id_key]);
                    }
                }
                else{
                    $id_key = $content['headers'][$id_key];
                    $len = count($content) - 1;
                    
                    for ($i = 0; $i < $len; $i++){
                        array_push($id_from_phenotype, $content[$i][$id_key]);
                    }
                }
                
                $species_ids = get_species_ids($form_state);
                
                foreach($id_from_phenotype as $id){
                    if (!in_array($id, $species_ids)){
                        form_set_error('phenotype][isotope', "Identifier $id was not found in the sampling metadata files. Please re-upload those files with location information of $id");
                    }
                }
                
            }
        }
        
        $genotypic = $form_state['values']['data_types']['Genotypic'];
        $phenotypic = $form_state['values']['data_types']['Phenotypic'];
        
        if ($genotypic === 'Genotypic'){
            $genotype = $form_state['values']['genotype'];
            $type = $genotype['type'];
            
            if ($type === '0'){
                form_set_error('genotype][type', 'Genotype Marker Type: field is required.');
            }
            elseif ($type === '1'){
                $snps_type = $genotype['SNPs-type'];
                if ($snps_type === '0'){
                    form_set_error('genotype][SNPs-type', 'SNPs type: field is required.');
                }
                elseif ($snps_type === '1'){
                    $snp_file = $genotype['vcf'];
                    if ($snp_file == ''){
                        form_set_error('genotype][vcf', 'SNPs file: field is required.');
                    }
                    else{
                        vcf($snp_file, $form_state);
                    }
                }
                else {
                    $snp_file = $genotype['spreadsheet'];
                    if ($snp_file == ''){
                        form_set_error('genotype][spreadsheet', 'SNPs file: field is required.');
                    }
                    else{
                        spreadsheet($snp_file, $form_state);
                    }
                }
            }
            else {
                $ssrs_file = $genotype['spreadsheet'];
                if ($ssrs_file == ''){
                    form_set_error('genotype][spreadsheet', 'SSRs file: field is required.');
                }
                else{
                    spreadsheet($ssrs_file, $form_state);
                }
            }
            
            
        }
        if ($phenotypic === 'Phenotypic'){
            $phenotype = $form_state['values']['phenotype'];
            $type = $phenotype['type'];
            
            if ($type == '0'){
                form_set_error('phenotype][type', 'Phenotype Type: field is required.');
            }
            elseif ($type == '1'){
                $isotope = $phenotype['isotope'];
                if ($isotope == ''){
                    form_set_error('phenotype][isotope', 'Isotope File: field is required.');
                }
                else{
                    isotope($isotope, $form_state);
                }
            }
            elseif ($phenotype['other'] == ''){
                form_set_error('phenotype][other', 'Phenotype File: field is required.');
            }
            
            
        }
    }
}
