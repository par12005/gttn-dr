<?php

function results_form(&$form, $form_state){
    
    $info = $form_state['saved_values'];
    $first_page = $info['first_page'];
    $second_page = $info['second_page'];
    $species = $first_page['species'];
    $species_number = $species['number'];
    $public = $first_page['public'];
    $data_type = $second_page['data_types'];
    
    $year_arr = array();
    $year_arr[0] = '- Select -';
        for ($j = 1950; $j <= 2017; $j++) {
            $index = $j - 1949;
            $year_arr[$index] = $j;
        }
    
    $month_arr = array(
      0 => '- Select -',
      1 => 'January',
      2 => 'February',
      3 => 'March',
      4 => 'April',
      5 => 'May',
      6 => 'June',
      7 => 'July',
      8 => 'August',
      9 => 'September',
      10 => 'October',
      11 => 'November',
      12 => 'December'
    );
    
    print_r("<div id='results'>");
    
    if ($public){
        print_r("<div>This information WILL be published on the greater TreeGenes site.</div><br>");
    }
    else{
        print_r("<div>This information WILL NOT be published on the greater TreeGenes site.</div><br>");
    }
    
    for ($i = 1; $i <= $species_number; $i++){
        $current_species = $species[$i];
        $species_name = $current_species['name'];
        $species_file = $current_species['spreadsheet']['file'];
        $file_location = file_load($species_file)->uri;
        $sample_date = $second_page["species-$i"]['sample_date'];
        $analysis_date = $second_page["species-$i"]['analysis_date'];
        $genotype = $second_page['genotype'];
        $phenotype = $second_page['phenotype'];
        
        print_r("<div>Species $i = $species_name</div>");
        print_r("<div>$species_name file stored at $file_location</div>");
        
        if ($sample_date['year'] !== '0'){
            $month = $month_arr[$sample_date['month']];
            $year = $year_arr[$sample_date['year']];
            print_r("<div>$species_name was sampled in $month of $year</div>");
        }
        
        if ($analysis_date['year'] !== '0'){
            $month = $month_arr[$analysis_date['month']];
            $year = $year_arr[$analysis_date['year']];
            print_r("<div>$species_name was analyzed in $month of $year</div>");
        }
        
        print_r("<br>");
    }
    
    if ($data_type['Genotypic'] == 'Genotypic'){
        if ($genotype['type'] == '1' and $genotype['SNPs-type'] == '1'){
            $genotype_file = file_load($genotype['vcf'])->uri;
        }
        else{
            $genotype_file = file_load($genotype['spreadsheet'])->uri;
        }
        print_r("<div>This submission will include genotypic data. It is stored at $genotype_file.</div>");
    }
    
    print_r("<br>");
    
    if ($data_type['Phenotypic'] == 'Phenotypic'){
        if ($phenotype['type'] == '1'){
            $phenotype_file = file_load($phenotype['isotope'])->uri;
        }
        else{
            $phenotype_file = file_load($phenotype['other'])->uri;
        }
        
        print_r("<div>This submission will include phenotypic data.It is stored at $phenotype_file</div>");
    }
    
    print_r("</div>");
    
    $form['results'] = array(
      '#type' => 'textfield',
      '#title' => t('Submission summary:'),
      '#suffix' => '<div id="display_results"></div>'
    );
    
    
    $form['Back'] = array(
      '#type' => 'submit',
      '#value' => 'Back',
    );
    
    return $form;
}
