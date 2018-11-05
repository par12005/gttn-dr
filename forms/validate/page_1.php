<?php

function page_1_validate(&$form, &$form_state){
    
    if ($form_state['submitted'] == '1'){
        $species = $form_state['values']['species'];
        $species_number = $species['number'];
        
        for ($i = 1; $i <= $species_number; $i++){
            $current_species = $species[$i];
            $location_format = $current_species['spreadsheet']['location'];
            $metadata_file = $current_species['spreadsheet']['file'];
            
            if ($current_species['name'] == ''){
                form_set_error("species][$i][name", "Species $i name: field is required.");
            }
            
            if ($location_format === '0'){
                form_set_error("species][$i][spreadsheet][location", 'Spreadsheet Location Format: field is required.');
            }
            elseif ($metadata_file == ''){
                form_set_error("species][$i][spreadsheet][file", 'Spreadsheet file upload: field is required.');
            }
            elseif ($location_format === '5'){
                $file_type = file_load($metadata_file)->filemime;
                $file = file(file_load($metadata_file)->uri);

                if ($file_type == 'text/csv' or $file_type == 'text/plain'){
                    $columns = explode("\r", $file[0]);
                    $columns = ($file_type == 'text/plain') ? explode("\t", $columns[0]) : explode(",", $columns[0]);
                    $id_omitted = TRUE;
                    $location_omitted = TRUE;

                    foreach($columns as $key => $col){
                        $columns[$key] = trim($col);
                        if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER)$/', $columns[$key]) == 1){
                            $id_omitted = FALSE;
                        }
                        elseif (preg_match('/^(location|Location|LOCATION|country|Country|COUNTRY)$/', $columns[$key]) == 1){
                            $location_omitted = FALSE;
                        }
                    }

                    if ($id_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                    }
                    if ($location_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your Location column. Please resubmit your file with a column named "Location" or "Country", with the location of each tree.');
                    }
                }
                elseif ($file_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
                    $location = '/var/www/Drupal/sites/default/files/' . file_load($metadata_file)->filename;

                    $content = gttn_parse_xlsx($location);
                    $columns = $content['headers'];
                    $id_omitted = TRUE;
                    $location_omitted = TRUE;

                    foreach($columns as $key => $col){
                        $columns[$key] = trim($col);
                        if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER)$/', $columns[$key]) == 1){
                            $id_omitted = FALSE;
                        }
                        elseif (preg_match('/^(location|Location|LOCATION|country|Country|COUNTRY)$/', $columns[$key]) == 1){
                            $location_omitted = FALSE;
                        }
                    }

                    if ($id_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                    }
                    if ($location_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your "Location" column. Please resubmit your file with a column named "Location", with the location of each tree.');
                    }
                }

            }
            else{
                $file_type = file_load($metadata_file)->filemime;
                $file = file(file_load($metadata_file)->uri);

                if ($file_type == 'text/csv' or $file_type == 'text/plain'){
                    $columns = explode("\r", $file[0]);
                    $columns = ($file_type == 'text/plain') ? explode("\t", $columns[0]) : explode(",", $columns[0]);
                    $id_omitted = TRUE;
                    $lat_omitted = TRUE;
                    $long_omitted = TRUE;

                    foreach($columns as $key => $col){
                        $columns[$key] = trim($col);
                        if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER)$/', $columns[$key]) == 1){
                            $id_omitted = FALSE;
                        }
                        elseif (preg_match('/^(latitude|Latitude|LATITUDE|lat|Lat|LAT)$/', $columns[$key]) == 1){
                            $lat_omitted = FALSE;
                        }
                        elseif (preg_match('/^(longitude|Longitude|LONGITUDE|long|Long|LONG)$/', $columns[$key]) == 1){
                            $long_omitted = FALSE;
                        }
                    }

                    if ($id_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                    }
                    if ($lat_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your Latitude column. Please resubmit your file with a column named "Latitude", with the coordinate of each tree.');
                    }
                    if ($long_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your Longitude column. Please resubmit your file with a column named "Longitude", with the coordinate of each tree.');
                    }
                }
                elseif ($file_type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
                    $location = '/var/www/Drupal/sites/default/files/' . file_load($metadata_file)->filename;

                    $content = gttn_parse_xlsx($location);
                    $columns = $content['headers'];
                    $id_omitted = TRUE;
                    $lat_omitted = TRUE;
                    $long_omitted = TRUE;

                    foreach($columns as $key => $col){
                        $columns[$key] = trim($col);
                        if (preg_match('/^(id|ID|Id|Identifier|identifier|IDENTIFIER)$/', $columns[$key]) == 1){
                            $id_omitted = FALSE;
                        }
                        elseif (preg_match('/^(latitude|Latitude|LATITUDE|lat|Lat|LAT)$/', $columns[$key]) == 1){
                            $lat_omitted = FALSE;
                        }
                        elseif (preg_match('/^(longitude|Longitude|LONGITUDE|long|Long|LONG)$/', $columns[$key]) == 1){
                            $long_omitted = FALSE;
                        }
                    }

                    if ($id_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your "Identifier" column. Please resubmit your file with a column named "Identifier", with an identifier for each tree.');
                    }
                    if ($lat_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your Latitude column. Please resubmit your file with a column named "Latitude", with the coordinate of each tree.');
                    }
                    if ($long_omitted){
                        form_set_error("species][$i][spreadsheet][file", 'Tree Accession file: We were unable to find your Longitude column. Please resubmit your file with a column named "Longitude", with the coordinate of each tree.');
                    }
                }

            }
        }
        
    }
}
