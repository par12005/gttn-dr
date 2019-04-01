<?php

class Submission {
    private $last_access;
    private $author;
    private $id;
    private $stage;
    public $title;
    
    public function __construct(){
        
    }
    
    public function init($uid = 0){
        if ($uid === 0){
            global $user;
            $uid = $user->uid;
        }
        
        $this->author = $uid;
        $this->stage = 0;
        
        $record = new stdClass();
        $record->uid = $uid;
        $record->submission = serialize($this);
        $this->id = gttn_tpps_create_record('gttn_tpps_submissions', $record);
        $this->title = "GTTN-TPPS Submission #{$this->id}";
        $this->save();
    }
    
    public function get_id(){
        return $this->id;
    }
    
    public function get_stage(){
        return $this->stage;
    }
    
    public function load($load_id = 0){
        if ($load_id === 0){
            $load_id = $this->id;
        }
        
        if (empty($load_id)){
            return NULL;
        }
        
        $query = db_select('gttn_tpps_submissions', 's')
            ->fields('s', array('submission'))
            ->condition('submission_id', $load_id)
            ->execute();
        
        if (($result = $query->fetchObject())){
            foreach (unserialize($result->submission) as $key => $value){
                $this->$key = $value;
            }
            return $this;
        }
        
        return NULL;
    }
    
    public function save(){
        $this->last_access = time();
        $record = new stdClass();
        $record->submission_id = $this->id;
        $record->uid = $this->author;
        $record->submission = serialize($this);
        gttn_tpps_create_record('gttn_tpps_submissions', $record);
    }
}

function gttn_tpps_load_submission($load_id){
    $sub = new Submission();
    return $sub->load($load_id);
}
