<?php
class User_device_model extends MY_Model {

	public function table_name(){
		return 'user_device';
	}

    /*
     * 添加ip/设备号
     */
	public function add($data){
		if(empty($data)){
			return false;
		}
		$res = $this->db->insert('user_device',$data);
		return $res;
	}

}