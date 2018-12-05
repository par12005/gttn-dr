<?php

function gttn_tpps_refresh_landing_page(){
    
    $and = db_and()
        ->condition('schemaname', 'chado')
        ->condition('tablename', 'plusgeno_view')
        ->condition('indexname', 'gttn_tpps_cron');
    
    $query = db_select('pg_indexes', 'i')
        ->fields('i')
        ->condition($and)
        ->execute();
    
    $result = $query->fetchAssoc();
    
    if (!$result){
        db_query('create unique index gttn_tpps_cron on chado.plusgeno_view (project_id)');
    }
    
    db_query('refresh materialized view concurrently chado.plusgeno_view');
}