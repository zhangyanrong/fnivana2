<?php
class Fruit_notify_model extends MY_Model {

	public function table_name(){
		return 'fruit_notify';
	}

	public function getNotifyList($field, $where, $limits){
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'],(($limits['curr_page']-1)*$limits['page_size']));
		}
		if(!empty($field)){
			$this->db->select($field);
		}
		$this->db->order_by('id','desc');
		$this->db->from('fruit_notify');
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	public function getNotifyCount($uid){
		if(empty($uid)){
			return 0;
		}
		$where = array('uid'=>$uid,'state'=>0);
		$this->db->select("count(id) c");
		$this->db->where($where);
		$this->db->from("fruit_notify");
		$result = $this->db->get()->row_array();
		return $result['c'];
	}

	//新增消息通知
	public function addNotify($params){
		if(empty($params['aid'])){
			return false;
		}
		
		$remark['uid'] = $params['notify_uid'];
		$remark['username'] = $params['notify_username'];
		$remark['content'] = $params['notify_content'];
		$data['ctime'] = $params['ctime'];
		$data['type'] = $params['type'];
		$data['aid'] = $params['aid'];	
		$data['uid'] = $params['uid'];
		$data['remark'] = serialize($remark);
		$data['pid'] = $params['pid'];
        $data['state'] = 0;
		if($remark['uid'] == $data['uid']){
			return false;
		}

		$res = $this->db->insert('fruit_notify',$data);
		if($res){
			return true;
		}else{
			return false;
		}
	}

	public function upNotify($where){
		if(empty($where['uid'])){
			return false;
		}
		$this->db->where($where);
		$res = $this->db->update('fruit_notify',array('state'=>1,'utime'=>time()));
		return $res;
	}

	/**
	 * [delNotify 删除消息]
	 * @param  [type] $where [pid必填]
	 * @return [boolen]        [description]
	 */
	public function delNotify($where){
		if(empty($where['pid'])){
			return false;
		}
		$res = $this->_delNot($where);
		return $res; 
	}

	/**
	 * [delNotify  批量删除消息]
	 * @param  [array] $where [aid必填]
	 * @return [boolen]        [description]
	 */
	public function delNotifys($where){
		if(empty($where['aid'])){
			return false;
		}
		$res = $this->_delNot($where);
		return $res; 
	}

	private function _delNot($where){
		if(empty($where)){
			return false;
		}
		if(empty($where['type'])){
			$where['type !='] = 4; 
			unset($where['type']);
		}
		$this->db->where($where);
		$res = $this->db->delete('fruit_notify');
		return $res;
	}
}