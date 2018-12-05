<?php

function page_1_validate_form(&$form, &$form_state){
    
    if ($form_state['submitted'] == '1'){
        
        $form_values = $form_state['values'];
        $organism = $form_values['organism'];
        $organism_number = $form_values['organism']['number'];
        $data_type = $form_values['dataType'];
        
        for ($i = 1; $i <= $organism_number; $i++){
            $name = $organism[$i];
            
            if ($name == ''){
                form_set_error("organism[$i", "Tree Species $i: field is required.");
            }
            else{
                $name = explode(" ", $name);
                $genus = $name[0];
                $species = implode(" ", array_slice($name, 1));
                $name = implode(" ", $name);
                $empty_pattern = '/^ *$/';
                $correct_pattern = '/^[A-Z|a-z|.| ]+$/';
                if (!isset($genus) or !isset($species) or preg_match($empty_pattern, $genus) or preg_match($empty_pattern, $species) or !preg_match($correct_pattern, $genus) or !preg_match($correct_pattern, $species)){
                    form_set_error("organism[$i", check_plain("Tree Species $i: please provide both genus and species in the form \"<genus> <species>\"."));
                }
                else {
                    $and = db_and()
                        ->condition('genus', $genus)
                        ->condition('species', $species);
                    
                    $query = db_select('chado.organism', 'o')
                        ->fields('o', array('organism_id'))
                        ->condition($and)
                        ->execute();
                    if (empty($query->fetchObject())){
                        dpm("Species $i: $name will be added as a new organism when the form is submitted.");
                    }
                }
            }
        }
        
        if (!$data_type){
            form_set_error('dataType', 'Data Type: field is required.');
        }
    }
}
