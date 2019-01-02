<?php

function submission_type_validate_form(&$form, &$form_state){
    if ($form_state['submitted'] == '1'){
        if (empty($form_state['values']['type'])){
            form_set_error("type", "Submission Type: field is required.");
        }
    }
}