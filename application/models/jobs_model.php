<?php

class Jobs_model extends CI_model {

    function Jobs_model()
    {
        parent::__construct();
    }

    function route($type)
    {
        switch($type) {
            case "sms";
                return 0;
            case "email";
                return 1;
            case "sms_and_email";
                return 2;
        }
    }

    function add($job, $type)
    {
        if( !$job['text'] )
            return;

        $job = serialize($job);
        $type = $this->route($type);
        $this->db->insert("joblist",array("job"=>$job, "type"=>$type));
    }

    function get_job() {
        $this->db->from("joblist");
        $this->db->where("is_success","0");
        $this->db->limit(40);
        $query = $this->db->get();
        return $query->result();
    }


    function excute() {
        $fails = array();
        $job = array();
        $joblist = $this->get_job();
        foreach($joblist as $val) {
            $job = unserialize($val->job);
            if($val->type == 0) {
                if($job['mobile']){
                    $this->send_sms($job['mobile'],$job['text']);
                }
            }else if($val->type == 1){
                if($job['email']){
                    $this->send_email($job['email'],$job['title'],$job['text']);
                }
            }else{
                if($job['mobile']){
                    $this->send_sms($job['mobile'],$job['text']);
                }
                if($job['email']){
                    $this->send_email($job['email'],$job['title'],$job['text']);
                }
            }
            $this->db->where("id",$val->id);
            $this->db->update("joblist",array("is_success"=>1));
        }
    }




}
