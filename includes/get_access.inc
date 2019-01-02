<?php
/**
 * Decides whether the user has permissions to access the gttn form and 
 * (eventually) which submissions they should be able to access.
 * 
 * @param stdObject $user The user being checked.
 * @return array The array of permissions.
 */
function gttn_tpps_get_access($user){
    
    // Array of permissions information.
    $access = array(
      // Does the user have access to the gttn form? (default FALSE)
      'gttn' => FALSE,
      // Which indivudual labs does the user have access to view information 
      // from? (default none)
      'labs' => array(),
      // Which indivudual users does the user have access to view information 
      // from? (default none)
      'users' => array(),
      // Which individual submissions does the user have access to view 
      // information from? (default none)
      'submissions' => array()
    );
    
    if (!empty($user->roles) and array_search('gttn', $user->roles)){
        $access['gttn'] = TRUE;
    }
    
    return $access;
}