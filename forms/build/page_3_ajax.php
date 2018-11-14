<?php

/**
 * When the map button on page 3 is clicked, this function calls the appropriate
 * JavaScript functions from js/gttn_tpps.js in order to render the map and the
 * locations of each of the trees specified in the tree accession file.
 * 
 * @param array $form The form with the map button that is calling this function.
 * @param array $form_state The state of the form that is calling this function.
 * @return array The response to the ajax callback, in the form of ajax commands.
 */
function page_3_multi_map($form, $form_state){
    
    // Only try to render the map if there is actually a tree accession file to load.
    if (!empty($form['tree-accession']['file']['#value']['fid'])){
        // Get the id of the tree accession file.
        $file = $form_state['values']['tree-accession']['file']['fid'];
        // Get the information for the columns of the tree accession file. This
        // needs to be done without the easy to use file-groups arrays because
        // this page of the form has not been validated yet.
        $columns = $form_state['values']['tree-accession']['file']['columns'];
        
        // Get the column ids for the tree id, latitude coordinate, and longitude coordinate.
        foreach ($columns as $key => $val){
            if ($val == '1'){
                $id_col = $key;
            }
            if ($val == '4'){
                $lat_col = $key;
            }
            if ($val == '5'){
                $long_col = $key;
            }
        }
        
        // If any of the column options for tree id, latitude coordinate, or
        // longitude coordinate are not set, then we don't have enough information
        // to render the map correctly, so just hide the map wrapper and return.
        if (!isset($lat_col) or !isset($long_col) or !isset($id_col)){
            // Add the hide ajax command.
            $commands[] = ajax_command_invoke('#map_wrapper', 'hide');
            // Return with the ajax commands.
            return array('#type' => 'ajax', '#commands' => $commands);
        }
        
        // Initialize the standardized coordinates array.
        $standards = array();
        
        // If the file can actually be loaded, then try to display the map.
        if (($file = file_load($file))){
            // Get the file uri.
            $file_name = $file->uri;

            // Load the full file uri.
            $location = drupal_realpath("$file_name");
            // Parse the content of the file from its location.
            $content = gttn_tpps_parse_xlsx($location);

            // If the user indicated that their file has no header, then we need
            // to adjust the content accordingly.
            if (isset($form_state['values']['tree-accession']['no-header']) and $form_state['values']['tree-accession']['no-header'] == 1){
                gttn_tpps_content_no_header($content);
            }

            // Each line of the content represents a tree and its gps coordinates.
            // we needs to standardize those coordinates so that the ajax commands
            // and JavaScript function can handle them appropriately.
            for ($i = 0; $i < count($content) - 1; $i++){
                // Only add coordinates to the standardized coordinates array if
                // we can successfully standardsize them.
                if (($coord = gttn_tpps_standard_coord("{$content[$i][$lat_col]},{$content[$i][$long_col]}"))){
                    $pair = explode(',', $coord);
                    // Add the standardized coordinates.
                    array_push($standards, array("{$content[$i][$id_col]}", $pair[0], $pair[1]));
                }
            }
        }
        
        // Call the updateMap function from js/gttn_tpps.js and pass it the array
        // of standardized coordinates as an argument, then put the results in
        // the element with the '#map_wrapper' id.
        $commands[] = ajax_command_invoke('#map_wrapper', 'updateMap', array($standards));
        
        // Return with the ajax commands.
        return array('#type' => 'ajax', '#commands' => $commands);
    }
    // If there is no tree accession file to load, then just hide the map and
    // return with the ajax commands.
    else {
        $commands[] = ajax_command_invoke('#map_wrapper', 'hide');
        return array('#type' => 'ajax', '#commands' => $commands);
    }
}
