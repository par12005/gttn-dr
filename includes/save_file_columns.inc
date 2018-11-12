<?php
function gttn_save_file_columns(&$form, &$form_state){

    if ($form_state['stage'] == 'first_page'){
        $species_number = $form_state['values']['species']['number'];
        for($i = 1; $i <= $species_number; $i++){
        if (($file = file_load($form_state['values']['species']["$i"]['spreadsheet'])) and $form_state['values']['species']["$i"]['spreadsheet'] != 0){
                // NEED TO ADD $form_state['accession']
                file_usage_add($file, 'gttn_tpps', 'gttn_tpps_project', 0);// substr($form_state['accession'], 4));
            }
            $form_state['species']["$i"]['spreadsheet-columns'] = array();

            foreach ($form['species']["$i"]['spreadsheet-columns'] as $col => $val){
                if (isset($form['species']["$i"]['spreadsheet']['columns'][$col]['#value']) and $col[0] != '#'){
                    $form_state['values']['species']["$i"]['spreadsheet-columns'][$col] = $form['species']["$i"]['spreadsheet']['columns'][$col]['#value'];
                }
            }
        }
    }
    return;
}