<?php
class Spitslot_model extends MY_Model {

	public function table_name(){
		return 'spitslot';
	}

	public function add($data){
		if(empty($data)){
			return false;
		}
		$res = $this->db->insert('spitslot',$data);
		return $res;
	}

}