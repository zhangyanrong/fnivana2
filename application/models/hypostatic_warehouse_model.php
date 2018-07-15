<?php
class Hypostatic_warehouse_model extends MY_Model
{
    public function __construct() {
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    public function table_name()
	{
		return 'hypostatic_warehouse';
	}

    public function insert_batch($insert_data){
        return $this->db->insert_batch('hypostatic_warehouse',$insert_data);
    }

    public function update_batch($update_batch,$index = 'code'){
        return $this->db->update_batch('hypostatic_warehouse',$update_batch,$index);
    }
}