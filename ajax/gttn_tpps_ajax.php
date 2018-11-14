<?php

function gttn_tpps_species_autocomplete($string){
    $matches = array();
    
    $parts = explode(" ", $string);
    if (!isset($parts[1])){
        $parts[1] = "";
    }
    //var_dump($parts);
    
    $result = db_select('chado.organism', 'organism')
        ->fields('organism', array('genus', 'species'))
        ->condition('genus', db_like($parts[0]) . '%', 'LIKE')
        ->condition('species', db_like($parts[1]) . '%', 'LIKE')
        ->orderBy('genus')
        ->orderBy('species')
        ->execute();
    
    foreach($result as $row){
        $matches[$row->genus . " " . $row->species] = check_plain($row->genus . " " . $row->species);
    }
    
    drupal_json_output($matches);
}

function gttn_tpps_no_header_callback($form, &$form_state){
    
    $parents = $form_state['triggering_element']['#parents'];
    array_pop($parents);
    
    $element = drupal_array_get_nested_value($form, $parents);
    return $element;
}
