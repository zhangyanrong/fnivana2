<?php
//用户登录统计
class Ltg extends CI_model
{
	private $field = array('uid','type','channel');
	public function Ltg(){
		parent::__construct();
	}
	
    public function insLtg($data){
		$checkres = array_intersect_key(array_flip($this->field),$data);
		if(count($checkres)==count($this->field)){
			$data['time'] = date("Y-m-d H:i:s");
			$this->db->insert('user_login_log',$data);
		}
	}
}