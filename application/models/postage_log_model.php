<?php
class Postage_log_model extends MY_Model {
	public function table_name(){
		return 'postage_log';
	}

    /*
	 * 新增使用记录
	 */
    public function addPostLog($data)
    {
        $this->db->insert("postage_log", $data);
        $id = $this->db->insert_id();
        return $id;
    }

}