<?php
class Fruit_invate_model extends MY_Model {

	public function table_name(){
		return 'fruit_invate';
	}

	//根据uid验证激活码
	public function getinvate($uid){
		$this->db->from('fruit_invate');
		$this->db->where('uid',$uid);
		$query = $this->db->get();
		$invateres = $query->row_array();
		return $invateres;
	}
}