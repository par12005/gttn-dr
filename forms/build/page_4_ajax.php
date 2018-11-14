<?php

function ajax_bioproject_callback(&$form, $form_state){
    
    $ajax_id = $form_state['triggering_element']['#parents'][0];
    
    return $form[$ajax_id]['genotype']['assembly-auto'];
}

function snps_file_callback($form, $form_state){
    $id = $form_state['triggering_element']['#parents'][0];
    $commands = array();
    $commands[] = ajax_command_replace("#edit-$id-genotype-file-ajax-wrapper", drupal_render($form[$id]['genotype']['file']));
    if (!$form_state['complete form'][$id]['genotype']['file-type']['Genotype Assay']['#value']){
        $commands[] = ajax_command_invoke(".form-item-$id-genotype-file", 'hide');
    }
    else {
        $commands[] = ajax_command_invoke(".form-item-$id-genotype-file", 'show');
    }
    
    return array('#type' => 'ajax', '#commands' => $commands);
}
