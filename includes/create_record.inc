<?php

function gttn_tpps_create_record($table, $records){
    // If either the table name or the record data is missing, then fail.
    if (empty($table) or empty($records)){
        return FALSE;
    }
    
    // If records is not an array, then put it in one so we can iterate through it.
    if (gettype($records) !== 'array'){
        $records = array($records);
    }
    
    // Get the full list of columns, the list of columns that have a unique 
    // constraint, and the primary column.
    $columns = gttn_tpps_all_columns($table);
    $unique_cols = gttn_tpps_unique_columns($table);
    $primary = gttn_tpps_get_primary_key($table);
    
    foreach ($records as $record){
        // If the record already exists, then we are updating it.
        if (($existing_record = gttn_tpps_record_exists($table, $record, $primary, $unique_cols))){
            // We only want to update columns that are different between the 
            // two records and that the new record has actual data for.
            $values = array();
            foreach ($columns as $col){
                if (isset($record->$col) and $record->$col !== $existing_record->$col){
                    $values[$col] = $record->$col;
                }
            }
            
            // Skip update if there are no new values.
            if (!empty($values)){
                db_update($table)
                    ->fields($values)
                    ->condition($primary, $existing_record->$primary)
                    ->execute();
            }
            
            // Return the id of the existing record.
            $write_id = $existing_record->$primary;
        }
        // Otherwise, we are creating a new record.
        else {
            // Initialize defaults to all of the columns in the table.
            $defaults = $columns;
            $fields = array();
            
            // If the new record has data for a column, remove that column from 
            // defaults and add the column_name => data pair to fields.
            foreach ($columns as $index => $col){
                if (isset($record->$col)){
                    unset($defaults[$index]);
                    $fields[$col] = $record->$col;
                }
            }
            
            // Insert the new record.
            $write_id = db_insert($table)
                ->useDefaults($defaults)
                ->fields($fields)
                ->execute();
        }
    }
    
    // Return the primary key id of the last inserted or updated record.
    return $write_id;
}

function gttn_tpps_record_exists($table, $record, $primary, $unique_columns = NULL){
    
    if (!empty($record->$primary)){
        $query = db_select($table, 't')
            ->fields('t')
            ->condition($primary, $record->$primary)
            ->execute();
        if (($result = $query->fetchObject())){
            return $result;
        }
    }

    if (empty($unique_cols)){
        $unique_cols = gttn_tpps_unique_columns($table);
        if (empty($unique_cols)){
            return FALSE;
        }
    }
    
    $query = db_select("$table", 't')
        ->fields('t');
    
    $and = db_and();
    foreach ($unique_cols as $field){
        if (!empty($record->$field)){
            $and->condition($field, $record->$field);
        }
    }
    
    $results = $query->condition($and)
        ->execute()
        ->fetchObject();
    
    if ($results){
        return $results;
    }
    
    return FALSE;
}

function gttn_tpps_unique_columns($table){
    
    // Separate the table and schema names if possible.
    if (preg_match('/(.*)\.(.*)/', $table, $matches)){
        $table = $matches[2];
        $schema = $matches[1];
    }
    // Otherwise, the schema is probably public.
    else {
        $schema = 'public';
    }
    
    // Find the UNIQUE constraint for the table that is not associated with primary key.
    $indexdef = db_query("select indexdef from pg_indexes where tablename = '$table' and schemaname = '$schema' and indexdef LIKE '%UNIQUE%' and indexname NOT LIKE '%_pkey';")
        ->fetchObject();
    
    // If a UNIQUE constraint was found, turn it into an array of column names.
    if (!empty($indexdef)){
        // Parse the constraint declaration.
        return explode(', ', preg_split('/(\(|\))/',$indexdef->indexdef)[1]);
    }
    return NULL;
}

function gttn_tpps_get_primary_key($table){
    
    // Separate the table and schema names if possible.
    if (preg_match('/(.*)\.(.*)/', $table, $matches)){
        $table = $matches[2];
        $schema = $matches[1];
    }
    // Otherwise, the schema is probably public.
    else {
        $schema = 'public';
    }
    
    // Find the PRIMARY KEY constraint for the table.
    $indexdef = db_query("select indexdef from pg_indexes where tablename = '$table' and schemaname = '$schema' and indexdef LIKE '%UNIQUE%' and indexname LIKE '%_pkey';")
        ->fetchObject()->indexdef;
    
     // If a PRIMARY KEY constraint was found, return the name of the column.
    if (!empty($indexdef)){
        // Parse the constraint declaration.
        return preg_split('/(\(|\))/', $indexdef)[1];
    }
    return NULL;
}

function gttn_tpps_all_columns($table){
    
    // Separate the table and schema names if possible.
    if (preg_match('/(.*)\.(.*)/', $table, $matches)){
        $table = $matches[2];
        $schema = $matches[1];
    }
    // Otherwise, the schema is probably public.
    else {
        $schema = 'public';
    }
    
    $cols = array();
    
    // Add each column name to $cols array.
    $query = db_query("select column_name from INFORMATION_SCHEMA.columns where table_name='$table' and table_schema='$schema'");
    while (($row = $query->fetchObject())){
        $cols[] = $row->column_name;
    }
    
    return $cols;
}
