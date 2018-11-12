<?php

/**
 * Validation function for the page_1 form.
 * 
 * @param type $form The form being validated.
 * @param boolean $form_state The state of the form being validated.
 */
function page_1_validate(&$form, &$form_state){
    
    // Only validate the page if it was actually submitted.
    if ($form_state['submitted'] == '1'){
        $species = $form_state['values']['species'];
        $species_number = $species['number'];
        
        for ($i = 1; $i <= $species_number; $i++){
            $current_species = $species[$i];
            
            // If the name field was omitted, throw an error.
            if ($current_species['name'] == ''){
                form_set_error("species][$i][name", "Species $i name: field is required.");
            }
            // If the spreadsheet field was completed, validate the file and the
            // column values.
            if ($current_species['spreadsheet'] != ""){
                $required_groups = array(
                  'Tree Id' => array(
                    'id' => array(1),
                  ),
                  'Location (latitude/longitude or country/state)' => array(
                    'approx' => array(2, 3),
                    'gps' => array(4, 5),
                  ),
                );
                
                $file_element = $form['species']["$i"]['spreadsheet'];
                $groups = gttn_tpps_file_validate_columns($form_state, $required_groups, $file_element);
                
                if (!form_get_errors()){
                    $id_name = $groups['Tree Id']['1'];
                    $col_names = $form_state['values']['species']["$i"]['spreadsheet-columns'];
                    $fid = $form_state['values']['species']["$i"]['spreadsheet'];
                    $file = file_load($fid);
                    $file_name = $file->uri;
                    $location = drupal_realpath($file_name);
                    $content = gttn_tpps_parse_xlsx($location);
                    
                    if (!empty($form_state['values']['species']["$i"]['spreadsheet-no-header'])){
                        gttn_tpps_content_no_header($content);
                    }
                    
                    $empty = $form_state['values']['species']["$i"]['spreadsheet-empty'];
                    foreach ($content as $row => $vals){
                        if ($row !== 'headers' and isset($vals[$id_name]) and $vals[$id_name] !== ""){
                            foreach ($col_names as $item => $val){
                                if ((!isset($vals[$item]) or $vals[$item] === $empty) and $val){
                                    $field = $file_element['columns'][$item]['#options'][$val];
                                    form_set_error("species][$i][spreadsheet][columns][{$vals[$id_name]}", "Species Spreadsheet $i: the required field $field is empty for tree \"{$vals[$id_name]}\".");
                                }
                            }
                        }
                    }
                }
                if (!form_get_errors()){
                    //preserve file if it is valid
                    $file = file_load($form_state['values']['species']["$i"]['spreadsheet']);
                    file_usage_add($file, 'gttn_tpps', 'gttn_tpps_project', 0);//substr($form_state['accession'], 4));
                }
            }
            // If the spreadsheet field was omitted, throw an error.
            else {
                form_set_error("species][$i][spreadsheet", "Species Spreadsheet $i: field is required.");
            }
        }
        
        if (form_get_errors()){
            // If there were no errors, rebuild the form.
            $form_state['rebuild'] = TRUE;
            $new_form = drupal_rebuild_form('gttn_tpps_form', $form_state, $form);
            for ($i = 1; $i < $species_number; $i++){
                // Make sure the upload button and "Define Data" section both have
                // the correct form element and id attribute.
                $form['species']["$i"]['spreadsheet']['upload'] = $new_form['species']["$i"]['spreadsheet']['upload'];
                $form['species']["$i"]['spreadsheet']['columns'] = $new_form['species']["$i"]['spreadsheet']['columns'];
                $form['species']["$i"]['spreadsheet']['upload']['#id'] = "edit-species-$i-spreadsheet-upload";
                $form['species']["$i"]['spreadsheet']['columns']['#id'] = "edit-species-$i-spreadsheet-columns";
            }
        }
        
    }
}