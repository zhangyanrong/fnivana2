<?php
class Bake_collection_model extends MY_Model {
	public function __construct(){
		parent::__construct();
		$this->load->helper('public');
	}

	public function table_name(){
		return 'bake_collection';
	}

	//根据用户id获取点赞数
	public function getCollectionNum($aids){
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

	public function getUserCollectAnum($uid){
		if(empty($uid)){
			return 0;
		}
		$where['bake_collection.uid'] = $uid;
		$where['bake_articles.state'] = 1;
		$where['bake_collection.type'] = 0;
		$this->db->select("count(distinct aid) c");
		$this->db->where($where);
		$this->db->from("bake_collection");
		$this->db->join('bake_articles', 'bake_collection.aid = bake_articles.id','left');
		$result = $this->db->get()->row_array();
		return $result['c'];
	}

	//根据用户id获取特性类型的文章
	public function getUserCollection($uid,$type=0){
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
	public function getCurrUserCollect($uid, $aid){
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

	/**
	 * [getUserCollectAids 获取用户收藏文章id]
	 * @param  [int] $uid [description]
	 * @return [array]      [description]
	 */
	public function getUserCollectAids($uid){
		if(empty($uid)){
			return array();
		}
		$where = array('uid'=>$uid,'type'=>0);
		$this->db->select('aid');
		$this->db->from($this->table_name());
		$this->db->where($where);		
		$result = $this->db->get()->result_array();
		if(!empty($result)){
			$result = array_column($result,'aid');
		}
		return $result;
	}

	public function upCollect($aid, $uid, $type){
		$this->db->where(array('aid'=>$aid,'uid'=>$uid));
		$res = $this->db->update($this->table_name(),array('type'=>$type));	
		return $res;
	}
	public function insCollect($data){
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