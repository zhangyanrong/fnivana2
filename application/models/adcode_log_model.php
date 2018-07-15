<?php
class Adcode_log_model extends MY_Model {

    public function table_name(){
        return 'adcode_log';
    }

    /*
    * 插入日志
    */
    function add_adcode($data){
        $this->db->insert('adcode_log', $data);
        return $this->db->insert_id();
    }
}