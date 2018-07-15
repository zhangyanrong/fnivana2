<?php
class Package_model extends MY_Model {
    function Package_model() {
        parent::__construct();
        $this->load->helper('public');
        $this->db_master = $this->load->database('default_master', TRUE);
    }

    public function table_name(){
        return 'b2o_package';
    }
}
