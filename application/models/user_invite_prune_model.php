<?php
/* create by tangcw */
class User_invite_prune_model extends MY_Model {


	function User_invite_prune_model() {
		parent::__construct();
	}

	function table_name() {
		return 'user_invite_prune';
	}

	/**
	 * 获取用户邀请成功数
	 */
	function countInvite($uid) {
		$this->db->select('count(*) as count_invite');
		$this->db->where(array('invite_by'=>$uid,'is_finish_order'=>1));
		$this->db->from('user_invite_prune');
		$result = $this->db->get()->result_array();
		return $result;
	}

	/**
	 * 获取用户邀请数
	 */
	function countInvite2($uid) {
	    $this->db->select('count(*) as count_invite');
	    $this->db->where(array('invite_by'=>$uid));
	    $this->db->from('user_invite_prune');
	    $result = $this->db->get()->result_array();
	    return $result;
	}

	function countAdd($uid){
	    $sql = "SELECT count(*) AS count_add FROM ttgy_user_invite_prune where invite_by=".$uid." and is_finish_order=1";
	    $query = $this->db->query($sql);
	    $result = $query->result_array();
	    return $result;
	}

	/**
	 * 获取总邀请数
	 */
	function countAll() {
	    $this->db->select('count(*) as count_invite');
	    $this->db->where(array('is_finish_order'=>1));
	    $this->db->from('user_invite_prune');
	    $result = $this->db->get()->result_array();
	    return $result;
	}


	/**
	 * 获取用户邀请列表
	 */
	function getInvite($uid) {
	    $this->db->select('invite.*,u.username,u.mobile,u.user_head');
	    $this->db->where(array('invite_by'=>$uid));
	    $this->db->from('user_invite_prune invite');
	    $this->db->join('user u','u.id = invite.uid','left');
	    $result = $this->db->get()->result_array();
	    return $result;
	}

	function rankList($limit = 10){
	    $sql = 'SELECT invite_by,uname,mobile,COUNT(uid) AS count_people FROM ttgy_user_invite_prune invite LEFT JOIN ttgy_user u ON u.id = invite.invite_by WHERE is_finish_order =1 AND (uname<>"" OR mobile <>"") GROUP BY invite_by having count(uid)<200 ORDER BY count_people desc LIMIT '.$limit;
	    //$sql = 'SELECT invite_by,uname,mobile,COUNT(uid) AS count_people FROM ttgy_user_invite_new invite LEFT JOIN ttgy_user u ON u.id = invite.invite_by WHERE is_finish_order =1 AND (uname<>"" OR mobile <>"") GROUP BY invite_by LIMIT '.$limit;
	    $query = $this->db->query($sql);
	    $result = $query->result_array();
	    return $result;
	}

	/* 查询 */
    function getList($field,$where='',$where_in='',$order='',$limits='',$like=''){
		$this->db->select($field);
		$this->db->from('user_invite_prune');
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($where_in)){
			foreach($where_in as $val){
				$this->db->where_in($val['key'],$val['value']);
			}
		}
		if(!empty($like)){
			$this->db->like($like);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'],($limits['curr_page']*$limits['page_size']));
		}
		if(!empty($order)){
			$this->db->order_by($order['key'],$order['value']);
		}

		$result = $this->db->get()->result_array();
		return $result;
	}

	/**
	 * 插入邀请记录
	 */
	function insertInvite($data){
	    $res = $this->db->insert($this->table_name(),$data);
	    if(empty($res)){
	        return false;
	    }else{
	        $id = $this->db->insert_id();
	        return $id;
	    }
	}

	function updateInvite($data){
	    $this->db->where(array('mobile_phone'=>$data['mobile_phone']));
	    $res = $this->db->update($this->table_name(),$data);
	    return $res;
	}


	private function rand_card_number($p_card_number = '') {
	    $a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
	    $a_array = explode(",", $a);
	    $tname = '';
	    for ($i = 1; $i <= 10; $i++) {
	        $tname.=$a_array[rand(0, 31)];
	    }
	    return $tname;
	}

	public function send_gift($uid, $tag) {
	    /* 赠品赠送start */
	    //        $reflect_arr = $this->get_region_reflect();
	    $now_data_time = date("Y-m-d H:i:s");
	    $this->db->select('a.*,b.product_name,b.thum_photo,b.template_id');
	    $this->db->from('gift_send a');
	    $this->db->join('product b', 'a.product_id = b.id');
	    $this->db->where(array('tag' => $tag, 'start <=' => $now_data_time, 'end >=' => $now_data_time));
	    $query = $this->db->get();
	    $user_gift_info = $query->row_array();

        // 获取产品模板图片
        if ($user_gift_info['template_id']) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($user_gift_info['template_id'], 'main');
            if (isset($templateImages['main'])) {
                $user_gift_info['thum_photo'] = $templateImages['main']['thumb'];
            }
        }

	    if (empty($user_gift_info)) {
	        return false;
	    } else {
	        $this->db->from("user_gifts");
	        $this->db->where(array("uid" => $uid, "active_id" => $user_gift_info['id'], "has_rec" => 0));
	        $count_isgifted = $this->db->count_all_results();
	        if ($count_isgifted)//已有赠品，跳出
	            return false;
	        $gift_send = $user_gift_info;
            if($gift_send['gift_valid_day'] && $gift_send['gift_valid_day']>0){
                $gift_start_time = date('Y-m-d');
                $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_send['gift_valid_day'])-1).' day'));
            }elseif($gift_send['gift_start_time'] && $gift_send['gift_end_time'] && $gift_send['gift_start_time'] != '0000-00-00' && $gift_send['gift_end_time'] != '0000-00-00'){
                $gift_start_time = $gift_send['gift_start_time'];
                $gift_end_time = $gift_send['gift_end_time'];
            }else{
                $gift_start_time = $gift_send['start'];
                $gift_end_time = $gift_send['end'];
            }
	        $user_gift_data = array(
	            'uid' => $uid,
	            'active_id' => $user_gift_info['id'],
	            'active_type' => '2',
	            'has_rec' => '0',
	            'start_time'=>$gift_start_time,
	            'end_time'=>$gift_end_time,
	        );
	        $this->db->trans_begin();
	        $this->db->insert('user_gifts', $user_gift_data);

	        if ($this->db->trans_status() === FALSE) {
	            $this->db->trans_rollback();
	            return false;
	        } else {
	            $this->db->trans_commit();
	        }

	        return $user_gift_info;
	    }
	    /* 赠品赠送end */
	}
}
