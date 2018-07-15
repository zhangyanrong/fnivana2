<?php
/* create by tangcw */
class User_invite_model extends MY_Model {


	function User_invite_model() {
		parent::__construct();
	}

	function table_name() {
		return 'user_invite_new';
	}

	/**
	 * 获取用户邀请数
	 */
	function countInvite($uid) {
		$this->db->select('count(*) as count_invite');
		$this->db->where(array('invite_by'=>$uid,'is_finish_order'=>1,'ctime >' => '1453703430'));
		$this->db->from('user_invite_new');
		$result = $this->db->get()->result_array();
		return $result;
	}

	/**
	 * 获取用户老规则邀请数
	 */
	function countOldInvite($uid) {
	    $this->db->select('count(*) as count_invite');
	    $this->db->where(array('invite_by'=>$uid,'is_finish_order'=>1,'ctime <' => '1453703430'));
	    $this->db->from('user_invite_new');
	    $result = $this->db->get()->result_array();
	    return $result;
	}

	/**
	 * 获取用户今天加分次数
	 *   */
	function countAdd($uid){
	    $todayBegin = date('Y-m-d 00:00:00');
	    $todayEnd = date('Y-m-d 23:59:59');
	    $weekBegin = date('Y-m-d 00:00:00',(time()-((date('w')==0?7:date('w'))-1)*24*3600));
	    $weekEnd = date('Y-m-d 23:59:59',(time()+(7-(date('w')==0?7:date('w')))*24*3600));
	    $sql = "SELECT count(*) AS count_add FROM ttgy_user_invite_new2 where invite_by=".$uid." and is_finish_order=1 and finish_order_time >= unix_timestamp('".$todayBegin."') and finish_order_time <= unix_timestamp('".$todayEnd."')";
	    $query = $this->db->query($sql);
	    $result = $query->result_array();
	    return $result;
	}

	/**
	 * 获取用户2月7号新规则后完成订单数
	 *   */
	function countAddAfter0207($uid){
	    $dateBegin = date('2016-02-07 12:00:00');
	    $dateEnd = date('2016-12-31 23:59:59');
	    $sql = "SELECT count(*) AS count_add FROM ttgy_user_invite_new where invite_by=".$uid." and is_finish_order=1 and ctime >= unix_timestamp('".$dateBegin."') and ctime <= unix_timestamp('".$dateEnd."')";
	    $query = $this->db->query($sql);
	    $result = $query->result_array();
	    return $result;
	}

	/**
	 * 获取用户邀请列表
	 */
	function getInvite($uid) {
	    $this->db->select('invite.*,u.username,u.mobile,u.user_head');
	    $this->db->where(array('invite_by'=>$uid,'is_finish_order'=>1));
	    $this->db->from('user_invite_new invite');
	    $this->db->join('user u','u.id = invite.uid','left');
	    $result = $this->db->get()->result_array();
	    return $result;
	}

	function getInvite2($uid){
	    $weekBegin = date('Y-m-d 00:00:00',(time()-((date('w')==0?7:date('w'))-1)*24*3600));
	    $weekEnd = date('Y-m-d 23:59:59',(time()+(7-(date('w')==0?7:date('w')))*24*3600));
	    $sql = "SELECT invite.*,u.username,u.mobile,u.user_head FROM ttgy_user_invite_new2 as invite left join ttgy_user as u on u.id= invite.uid where invite_by=".$uid." and is_finish_order = 1 and ctime >= unix_timestamp('".$weekBegin."') and ctime <= unix_timestamp('".$weekEnd."') ";
	    $query = $this->db->query($sql);
	    $result = $query->result_array();
	    return $result;
	}

	function rankList($limit = 10){
	    $weekBegin = date('Y-m-d 00:00:00',(time()-((date('w')==0?7:date('w'))-1)*24*3600));
	    $weekBeginTime = strtotime($weekBegin);
	    $weekEnd = date('Y-m-d 23:59:59',(time()+(7-(date('w')==0?7:date('w')))*24*3600));
	    $weekEndTime = strtotime($weekEnd);
	    $sql = "SELECT invite_by,uname,mobile,COUNT(uid) AS count_people FROM ttgy_user_invite_new2 invite LEFT JOIN ttgy_user u ON u.id = invite.invite_by WHERE is_finish_order =1 and finish_order_time>'".$weekBeginTime."' and finish_order_time < '".$weekEndTime."' AND (uname<>'' OR mobile <>'') GROUP BY invite_by having count(uid)<200 ORDER BY count_people desc LIMIT ".$limit;
	    //$sql = 'SELECT invite_by,uname,mobile,COUNT(uid) AS count_people FROM ttgy_user_invite_new invite LEFT JOIN ttgy_user u ON u.id = invite.invite_by WHERE is_finish_order =1 AND (uname<>"" OR mobile <>"") GROUP BY invite_by LIMIT '.$limit;
	    $query = $this->db->query($sql);
	    $result = $query->result_array();
	    return $result;
	}

	/* 查询 */
    function getList($field,$where='',$where_in='',$order='',$limits='',$like=''){
		$this->db->select($field);
		$this->db->from('user_invite_new');
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

	function addCard($uid='',$mobile=''){
	    //领取优惠券1
	    $black_list ='';// $active_product_id;
	    $p_card_number = 'invite1';
	    $card_number = $p_card_number.$this->rand_card_number($p_card_number);
	    $time = date("Y-m-d");
	    $d=strtotime("+1 year");
	    $to_date = date("Y-m-d H:i:s",$d);
	    $remarks = '新客通用优惠券';
	    /*优惠券优惠券生成设置end*/
	    $card_data = array(
	        'uid'=>$uid,
	        'sendtime'=>$to_date,
	        'card_number'=>$card_number,
	        'card_money'=>10,
	        'product_id'=>'',
	        'maketing'=>0,
	        'is_sent'=>1,
	        'restr_good'=>0,
	        'remarks'=>$remarks,
	        'time'=>$time,
	        'to_date'=>$to_date,
	        'can_use_onemore_time'=>'false',
	        'can_sales_more_times'=>'false',
	        'card_discount'=>1,
	        'order_money_limit'=>30,//100
	        'black_list'=>$black_list,
	        'channel'=>serialize(array("2"))//serialize(array("2"))
	    );
	    //$this->db->insert('card',$card_data);

	    $active_tag = 'invite1';
	    $link_tag = '';
	    $active_data = array(
	        'mobile' => $mobile,
	        'card_number' => $card_number,
	        'active_tag' => $active_tag,
	        'link_tag' => $link_tag,
	        'description' => '',
	        'card_money' => 10
	    );
	    //$this->db->insert('wqbaby_active', $active_data);

	    //领取优惠券2
	    $black_list ='';// $active_product_id;
	    $p_card_number = 'invite2';
	    $card_number = $p_card_number.$this->rand_card_number($p_card_number);
	    $time = date("Y-m-d");
	    $d=strtotime("+1 year");
	    $to_date = date("Y-m-d H:i:s",$d);
	    $remarks = '新客包邮券';
	    /*优惠券优惠券生成设置end*/
	    $card_data = array(
	        'uid'=>$uid,
	        'sendtime'=>$time,
	        'card_number'=>$card_number,
	        'card_money'=>10,
	        'product_id'=>'',
	        'maketing'=>0,
	        'is_sent'=>1,
	        'restr_good'=>0,
	        'remarks'=>$remarks,
	        'time'=>$time,
	        'to_date'=>$to_date,
	        'can_use_onemore_time'=>'false',
	        'can_sales_more_times'=>'false',
	        'card_discount'=>1,
	        'order_money_limit'=>0,//100
	        'black_list'=>$black_list,
	        'channel'=>serialize(array("2"))//serialize(array("2"))
	    );
	    $this->db->insert('card',$card_data);

	    $active_tag = 'invite2';
	    $link_tag = '';
	    $active_data = array(
	        'mobile' => $mobile,
	        'card_number' => $card_number,
	        'active_tag' => $active_tag,
	        'link_tag' => $link_tag,
	        'description' => '',
	        'card_money' => 10
	    );
	    $this->db->insert('wqbaby_active', $active_data);
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
	        $this->db->where(array("uid" => $uid, "active_id" => $user_gift_info['id']));
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
	        $this->db->insert('user_gifts', $user_gift_data);

	        return $user_gift_info;
	    }
	    /* 赠品赠送end */
	}
}
