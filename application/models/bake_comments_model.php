<?php
class Bake_comments_model extends MY_Model {

	public function table_name(){
		return 'bake_comments';
	}

	//根据文章id获取评论数
	public function getCommentsNum($aids){
		if(!empty($aids)){
			$this->db->where_in('aid',$aids);
		}
		$data = array();
		$this->db->where(array('state'=>1));
		$this->db->select("count(*) c,aid");
		$this->db->from($this->table_name());
		$this->db->group_by("aid");
		$result = $this->db->get()->result_array();
		if(!empty($result)){
			foreach($result as $val){
				$data[$val['aid']] = $val['c'];
			}
		}
		return $data;
	}
	//获取用户评论的文章数
	public function getUserCommentsAnum($uid){
		if(empty($uid)){
			return 0;
		}
		$where['bake_comments.uid'] = $uid;
		$where['bake_articles.state'] = 1;
		$this->db->select("count(distinct aid) c");
		$this->db->where($where);
		$this->db->from("bake_comments");
		$this->db->join('bake_articles', 'bake_comments.aid = bake_articles.id','left');
		$result = $this->db->get()->row_array();
		return $result['c'];
	}

	public function getCommentList($aid, $uid, $limit, $page){
		$offset = ($page-1)*$limit;
		$where = array('state'=>1);
		if(!empty($aid)){
			$where['aid'] = $aid;
		}
		if(!empty($uid)){
			$where['uid'] = $uid;
		}
		$this->db->select('ctime,content,pid,remark,id,uid');
		$this->db->from($this->table_name());
		$this->db->where($where);
		$this->db->limit($limit,$offset);
		$this->db->order_by('id','asc');
		$result = $this->db->get()->result_array();
		return $result;
	}

	public function insComment($data){
		if(empty($data)){
			return false;
		}

		$res = $this->db->insert($this->table_name(),$data);
		if(empty($res)){
			return false;
		}
		$id = $this->db->insert_id();
		return $id;
	}

	public function getComment($id){
		if(empty($id)){
			return array();
		}
		$where['id'] = $id;
		$this->db->select('ctime,content,pid,remark,id,uid');
		$this->db->from($this->table_name());
		$this->db->where($where);
		$result = $this->db->get()->row_array();
		return $result;
	}
	/**
	 * [delComment  删除评论]
	 * @param  [array] $where [id uid必填]
	 * @return [type]        [description]
	 */
	public function delComment($where){
		if(empty($where['id']) || empty($where['uid'])){
			return false;
		}
		$res = $this->_delCom($where);
		return $res;
	}

	/**
	 * [_delCom 根据where删除评论]
	 * @param  [array] $where [description]
	 * @return [boolen]        [description]
	 */
	private function _delCom($where){
		if(empty($where)){
			return false;
		}
		$this->db->where($where);
		$res = $this->db->delete($this->table_name()); 
		return $res;
	}
}