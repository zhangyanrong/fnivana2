<?php
class Fruit_comments_model extends MY_Model {

	public function table_name(){
		return 'fruit_comments';
	}

	//根据文章id获取评论数
	public function getCommentsNum($aids){
		if(!empty($aids)){
			$this->db->where_in('aid',$aids);
		}
		$data = array();
		$this->db->where(array('state'=>1));
		$this->db->select("count(*) c,aid");
		$this->db->from('fruit_comments');
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
		$where['fruit_comments.uid'] = $uid;
		$where['fruit_articles.state'] = 1;
		$this->db->select("count(distinct aid) c");
		$this->db->where($where);
		$this->db->from("fruit_comments");
		$this->db->join('fruit_articles', 'fruit_comments.aid = fruit_articles.id','left');
		$result = $this->db->get()->row_array();
		return $result['c'];
	}

	public function getCommentUid($id){
		$where = array('id'=>$id);
		$this->db->select('uid');
		$this->db->from('fruit_comments');
		$this->db->where($where);
		$result = $this->db->get()->row_array();
		return $result['uid'];
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
		$this->db->from('fruit_comments');
		$this->db->where($where);
		$this->db->limit($limit,$offset);
		$this->db->order_by('id','asc');
		$result = $this->db->get()->result_array();
		return $result;
	}

	public function getCommentListByGuan($aid, $limit, $page){
		$offset = ($page-1)*$limit;
		$where = array('state'=>1);
		if(!empty($aid)){
			$where['aid'] = $aid;
		}
		$where['uid'] = 0;
		$this->db->select('ctime,content,pid,remark,id,uid');
		$this->db->from('fruit_comments');
		$this->db->where($where);
		$this->db->limit($limit,$offset);
		$this->db->order_by('id','desc');
		$result = $this->db->get()->result_array();
		return $result;
	}

	public function getComment($id){
		if(empty($id)){
			return array();
		}
		$where['id'] = $id;
		$this->db->select('ctime,content,pid,remark,id,uid');
		$this->db->from('fruit_comments');
		$this->db->where($where);
		$result = $this->db->get()->row_array();
		return $result;
	}

	/**
	 * [getUserCommentsAids]
	 * @param  [int] $uid [description]
	 * @return [array]      [description]
	 */
	public function getUserCommentsAids($uid){
		if(empty($uid)){
			return array();
		}
		$where = array('uid'=>$uid);
		$this->db->select('aid');
		$this->db->from('fruit_comments');
		$this->db->where($where);		
		$result = $this->db->get()->result_array();
		if(!empty($result)){
			$result = array_column($result,'aid');
		}
		return $result;
	}

	public function insComment($data){
		if(empty($data)){
			return false;
		}

		$res = $this->db->insert('fruit_comments',$data);
		if(empty($res)){
			return false;
		}
		$id = $this->db->insert_id();
		return $id;
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
	 * [delCOmments 根据文章id删除评论]
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function delComments($where){
		if(empty($where['aid'])){
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
		$res = $this->db->delete('fruit_comments'); 
		return $res;
	}

	public function getLastCommentTime($uid){
		$where['fruit_comments.uid'] = $uid;
		$where['fruit_articles.state'] = 1;
        $this->db->select("max(ttgy_fruit_comments.ctime) as maxtime");
        $this->db->where($where);
		$this->db->from("fruit_comments");
		$this->db->join('fruit_articles', 'fruit_comments.aid = fruit_articles.id','left');
		$result = $this->db->get()->row_array();
		return $result['maxtime'];
	}
}