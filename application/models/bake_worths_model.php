<?php
class Bake_worths_model extends MY_Model {
	public function __construct(){
		parent::__construct();
		$this->load->helper('public');
	}

	public function table_name(){
		return 'bake_worths';
	}

	//根据用户id获取点赞数
	public function getWorthsNum($aids){
		if(!empty($aids)){
			$this->db->where_in('aid',$aids);
		}
		$where = array('type'=>0);
		$data = array();
		$this->db->select("count(*) c,aid");
		$this->db->from($this->table_name());
		$this->db->where($where);
		$this->db->group_by("aid");
		$result = $this->db->get()->result_array();
		if(!empty($result)){
			foreach($result as $val){
				$data[$val['aid']] = $val['c'];
			}
		}
		return $data;
	}

	//根据用户id获取特性类型的文章
	public function getUserWorth($uid,$type=0){
		if(empty($uid)){
			return 0;
		}
		$where['uid'] = $uid;
		$where['type'] = $type;
		$this->db->select("aid,uid");
		$this->db->where($where);
		$this->db->from($this->table_name());
		$result = $this->db->get()->result_array();
		$new_result = array();
		if(!empty($result)){
			foreach($result as $val){
				$new_result[$val['aid']] = $val['uid']; 
			}
		}
		return $new_result;
	}

	//获取当前用户点赞状态
	public function getCurrUserWorth($uid, $aid){
		if(empty($uid) || empty($aid)){
			return false;
		}
		$where = array(
			'uid'=>$uid,
			'aid'=>$aid,
		);
		$this->db->select("type");
		$this->db->where($where);
		$this->db->from($this->table_name());
		$result = $this->db->get()->row_array();
		return $result;
	}

	public function upWorth($aid, $uid, $type){
		$this->db->where(array('aid'=>$aid,'uid'=>$uid));
		$res = $this->db->update($this->table_name(),array('type'=>$type));	
		return $res;
	}
	public function insWorth($data){
		if(empty($data['aid'])|| empty($data['uid'])){
			return false;
		}
		$res = $this->db->insert($this->table_name(),$data);
		if(empty($res)){
			return false;
		}else{
			$id = $this->db->insert_id();
			return $id;
		}
	}
}