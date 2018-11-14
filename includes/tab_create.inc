<?php

function gttn_tpps_tab_create($type, $user, $name = NULL){
    
    global $base_url;
    $email = $user->mail;
    $state_name = 'gttn_tpps_incomplete_' . $email;
    $params = drupal_get_query_parameters();
    $final = "";
    $t1 = $t2 = FALSE;
    
    if (isset($params['accession']) and isset($params['action']) and $params['action'] === 'DELETE'){
        $delete_name = $state_name . $params['accession'];
        $results = db_select("public.variable", "variable")
            ->fields('variable')
            ->condition('name', db_like($delete_name), 'LIKE')
            ->execute()
            ->fetchAssoc();
        
        variable_del($results['name']);
        $and = db_and()
            ->condition('accession', $params['accession'])
            ->condition('db_id', 95);
        $results = db_delete('chado.dbxref')
            ->condition($and)
            ->execute();
    }
    
    $results = db_select("public.variable", "variable")
        ->fields('variable')
        ->condition('name', db_like($state_name) . '%', 'LIKE')
        ->execute();
    
    $style = 
    '<style>
        .gttn_tpps_profile_tab {
            background: #ffffff;
            border: 1px solid #cccccc;
            border-radius: 4px;
            padding: 0px 10px;
        }
    </style>';
    $display = "<label for='gttn_tpps_incomplete_table'>Incomplete Submissions</label><div class='gttn_tpps_profile_tab'><table id='gttn_tpps_incomplete_table' style='width:-webkit-fill-available' border='1'><thead>";
    $display .= "<tr><th>Accession Number</th><th>Number of Species</th><th></th></tr>";
    $display .= "</thead><tbody>";
    
    foreach ($results as $item){
        $name = $item->name;
        $state = variable_get($name, NULL);
        
        $display .= "<tr>";
        
        if (isset($state['accession'])){
            $accession = check_plain($state['accession']);
            $display .= "<td><a href='$base_url/gttn?accession=$accession' style='text-decoration: underline;'>$accession</a></td>";
        }
        else {
            $display .= "<td></td>";
        }
        
        if (isset($state['saved_values'][GTTN_PAGE_1]['organism']['number'])){
            $species_number = check_plain($state['saved_values'][GTTN_PAGE_1]['organism']['number']);
            $display .= "<td>$species_number</td>";
        }
        else {
            $display .= "<td></td>";
        }
        
        if (isset($state['accession'])){
            $display .= "<td style='text-align:center'><input type='submit' id='delete-{$state['accession']}' onclick='";
            $display .= "if (confirm(\"Attention! \\n\\nAre you sure you want to delete your submission {$state['accession']}?\\nThis action cannot be undone, and all of your data will be lost!\")){window.location.href = \"$base_url/user/{$user->uid}/TPPS?accession={$state['accession']}&action=DELETE\"}";
            $display .= "' name='op' class='form-button' value='Delete'></td>";
        }
        else {
            $display .= '<td></td>';
        }
        
        $display .= "</tr>";
    }
    
    if (!isset($name)){
        $display = $style;
    }
    else {
        $display .= "</tbody></table></div><br>";
        $t1 = TRUE;
    }
    
    $final .= $display;
    $state_name = 'gttn_tpps_complete_' . $email;
    $name = NULL;
    
    $results = db_select("public.variable", "variable")
        ->fields('variable')
        ->condition('name', db_like($state_name) . '%', 'LIKE')
        ->execute();
    
    $display = "<label for='gttn_tpps_complete_table'>Complete Submissions</label><div class='gttn_tpps_profile_tab'><table id='gttn_tpps_complete_table' style='width:-webkit-fill-available' border='1'><thead>";
    $display .= "<tr><th>Accession Number</th><th>Number of Species</th><th></th></tr>";
    $display .= "</thead><tbody>";
    
    foreach ($results as $item){
        $name = $item->name;
        $state = variable_get($name, NULL);
        
        $display .= "<tr>";
        
        if (isset($state['accession'])){
            $accession = check_plain($state['accession']);
            $display .= "<td><a href='$base_url/gttn-completed-submission?id=$accession' style='text-decoration: underline;'>$accession</a></td>";
        }
        else {
            $display .= "<td></td>";
        }
        
        if (isset($state['saved_values'][GTTN_PAGE_1]['organism']['number'])){
            $species_number = check_plain($state['saved_values'][GTTN_PAGE_1]['organism']['number']);
            $display .= "<td>$species_number</td>";
        }
        else {
            $display .= "<td></td>";
        }
        
        $display .= "<td>{$state['status']}</td></tr>";
    }
    
    if (!isset($name)){
        $display = "";
    }
    else {
        $display .= "</tbody></table></div><br>";
        $t2 = TRUE;
    }
    
    if (!$t1 and !$t2){
        $display = $style;
        $display .= "<h2>Could not find any Submissions</h2>";
        $display .= "<p>We could not find any incomplete GTTN-TPPS Submissions or GTTN-TPPS Submissions pending approval. If you would like to start a new TPPS Submission, you can start <a href=$base_url/gttn>here.</a></p>";
    }
    
    $final .= $display;
    
    return $final;
}
