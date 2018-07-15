<?php
class Fruit_worths_model extends MY_Model {
	public function __construct(){
		parent::__construct();
		$this->load->helper('public');
	}

	public function table_name(){
		return 'fruit_worths';
	}

	//根据用户id获取点赞数
	public function getWorthsNum($aids){
		if(!empty($aids)){
			$this->db->where_in('aid',$aids);
		}
		$where = array('type'=>0);
		$data = array();
		$this->db->select("count(*) c,aid");
		$this->db->from('fruit_worths');
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

	//获取用户点赞的文章数
	public function getUserWorthsAnum($uid){
		if(empty($uid)){
			return 0;
		}
		$where['fruit_worths.uid'] = $uid;
		$where['fruit_articles.state'] = 1;
		$where['fruit_worths.type'] = 0;
		$this->db->select("count(distinct aid) c");
		$this->db->where($where);
		$this->db->from("fruit_worths");
		$this->db->join('fruit_articles', 'fruit_worths.aid = fruit_articles.id','left');
		$result = $this->db->get()->row_array();
		return $result['c'];
	}

	/**
	 * [getUserWorthsAids 获取用户点赞文章id]
	 * @param  [int] $uid [description]
	 * @return [array]      [description]
	 */
	public function getUserWorthsAids($uid){
		if(empty($uid)){
			return array();
		}
		$where = array('uid'=>$uid,'type'=>0);//type:0点赞1非赞
		$this->db->select('aid');
		$this->db->from('fruit_worths');
		$this->db->where($where);		
		$result = $this->db->get()->result_array();
		if(!empty($result)){
			$result = array_column($result,'aid');
		}
		return $result;
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
		$this->db->from("fruit_worths");
		$result = $this->db->get()->result_array();
		$new_result = array();
		if(!empty($result)){
			foreach($result as $val){
				$new_result[$val['aid']] = $val['uid']; 
			}
		}
		return $new_result;
	}

	//验证用户是否点过赞
	public function checkUserWorth($uid,$aid,$type=0){
		if(empty($uid) || empty($aid)){
			return false;
		}
		$where = array('uid'=>$uid,'aid'=>$aid,'type'=>$type);
		$this->db->select("id");
		$this->db->from("fruit_worths");
		$this->db->where($where);
		$result = $this->db->get()->row_array();
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}

	public function upFruitWorth($aid, $uid, $type){
		$this->db->where(array('aid'=>$aid,'uid'=>$uid));
		$res = $this->db->update("fruit_worths",array('type'=>$type));	
		return $res;
	}

	public function insFruitWorth($data){
		if(empty($data['aid'])|| empty($data['uid'])){
			return false;
		}
		$res = $this->db->insert('fruit_worths',$data);
		if(empty($res)){
			return false;
		}else{
			$id = $this->db->insert_id();
			return $id;
		}
	}

	/**
	 * [delWorths description]
	 * @return [type] [description]
	 */
	public function delWorths(){
		if(empty($where['aid'])){
			return false;
		}
		$this->db->where($where);
		$res = $this->db->delete('fruit_worths');
		return $res;
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
}