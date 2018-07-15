<?php
class Group_model extends MY_Model {
	function Group_model() {
		parent::__construct();
		$this->db_master = $this->load->database('default_master', TRUE);
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
	}

	public function table_name(){
		return 'group';
	}

	function already_payed($uid,$tag){
		$result = $this->db->from('group_member')->where(array(
			'uid'=>$uid,
			'tag'=>$tag,
		))->where_in('status',array(2))->get()->row_array();  //,3

		if(!empty($result)){
			return true;
		}
	}

	function is_notpay($uid,$tag){
		$sql = "SELECT count(*) as num FROM ttgy_group_member g LEFT JOIN ttgy_order o ON g.order_id = o.id WHERE g.uid=$uid AND g.tag='{$tag}' AND o.operation_id !=5 AND o.pay_status != 1";
		$result = $this->db->query($sql)->row_array();

		return $result['num'];
	}

	function get_set_nearly_finish_group($tag,$config){
		$full_num = $config['full_num'];

		$atime = strtotime("-24 hours");

		$sql = "select * FROM ttgy_group_member gm join ttgy_group g on g.id=gm.`group_id` where gm.tag='{$tag}' and g.time>'{$atime}' and gm.`status` = 2 GROUP BY group_id HAVING gm.`status` = 2 AND COUNT(*)<$full_num ORDER BY COUNT(*) desc limit 2";
		$a = $this->db->query($sql)->result_array();
		return $a;
	}

	/*
	 *是否可以添加
	 */
	function check_can_add($group_id){
		$result = $this->db->from('group')->where(array(
			'id'=>$group_id
		))->get()->row_array();
		$tag = $result['tag'];

		if($tag != 'ddy5W1' && $tag != 'LBfhW1' && $tag != 'CZmiS2'){
		    if($result['status']==3){
		        return "该团已达人数上限，客观下次请赶早哦~";
		    }
			if($result['status']==1||$result['status']==0){
				return "团长正在付款中，请稍候。";
			}
		}

		//关闭结束以后还能进来订单的口子 新年天团除外
		if ($tag != 'ddy5W1' && $tag != 'LBfhW1' && $tag != 'CZmiS2'){
		    if($result['time']<(time()-86400)){
		        return "本团已经过期.";
		    }
		}


		$data = $this->getGroupConfigData($tag);
		$data['config'] = unserialize($data['config']);
		//新年天团
		if ($tag == 'ddy5W1' || $tag == 'CZmiS2'){
		    $ceil_num = 9999;
		} elseif($tag == 'LBfhW1'){
		    $ceil_num = 7;
		} else {
		    $ceil_num = $data['config']['full_num']?($data['config']['full_num']+5):10;
		}

		//新年天团 多了个成团的状态
        if ($tag == 'ddy5W1' || $tag == 'LBfhW1' || $tag = 'CZmiS2'){
            $sql = "SELECT count(*) as num FROM ttgy_group_member g LEFT JOIN ttgy_order o on o.id=g.`order_id` WHERE group_id={$group_id} AND status in(1,2,3) and `operation_id` !=5";
        } else {
            $sql = "SELECT count(*) as num FROM ttgy_group_member g LEFT JOIN ttgy_order o on o.id=g.`order_id` WHERE group_id={$group_id} AND status in(1,2) and `operation_id` !=5";
        }

//		$sql = "SELECT count(*) as num FROM ttgy_group_member WHERE group_id={$group_id} AND status in(1,2)";
		$result = $this->db->query($sql)->row_array();
		if($result['num']>=$ceil_num){
			return "该团已达人数上限，客观下次请赶早哦~";
		}

	}

	/*
	 * 是否有未取消未支付的订单
	 */
	function is_order_cancel($uid,$tag){
		$sql = "SELECT group_id,status FROM ttgy_group_member gm LEFT JOIN ttgy_order o ON o.id=gm.order_id WHERE gm.tag='{$tag}' AND gm.uid='{$uid}' AND gm.status=1 AND o.operation_id!=5 AND o.pay_status=0 AND o.order_status=1";
		$result = $this->db->query($sql)->row_array();
		if(!empty($result)){
			return $result;
		}
	}

	/*
	 * 是否有付完款的团购
	 */
	function is_createOrder($uid,$tag){
		$sql = "SELECT group_id,status FROM ttgy_group_member WHERE tag='{$tag}' AND uid='{$uid}' AND status IN (3,2)";
		$result = $this->db->query($sql)->row_array();
		if(!empty($result)){
			return $result;
		}
	}

	function getMobileByUid($uid){
		$this->db->select('mobile');
		$this->db->from('user');
		$this->db->where(array(
			'id'=>$uid
		));
		$result = $this->db->get()->row_array();
		return $result['mobile'];
	}

	/*
	 * 查询成团
	 */
	function check_group_num($tag){
		$sql = "SELECT count(*) as num FROM ttgy_group WHERE tag='{$tag}'";
		$result = $this->db->query($sql)->row_array();
		return $result['num'];
	}

	/*
     * 是否注册会员
     */
	function is_reg($mobile){
		$this->db->from("user");
		$this->db->where(
			array(
				"mobile" => $mobile
			)
		);
		$query = $this->db->get();
		$userInfo = $query->result_array();
		if(!empty($userInfo)){
			return $userInfo[0]['id'];
		}
	}

	/*
     * 活动时间限制
     */
	function lm_active_time($start_time,$end_time){
		$now_time = date("Y-m-d H:i:s");
		if($now_time<$start_time){
			$return_result = array("result"=>"error","msg"=>"该活动尚未开始，您可以直接下载天天果园app参与更多活动~");
			echo  json_encode($return_result);
			exit;
		}
		if($now_time>$end_time){
			$return_result = array("result"=>"error","msg"=>"该活动已结束，感谢您的参与~");
			echo  json_encode($return_result);
			exit;
		}
	}

	/*
     * 获取成团信息
     */
	function get_group_member_info($tag,$uid){
		$sql = "SELECT group_id,status FROM ttgy_group_member WHERE tag = {$tag} AND uid = {$uid} ORDER BY id DESC";
		$query = $this->db->query($sql);
		return $grouper_info = $query->row_array();
	}

	/*
     * 获取团信息
     */
	function get_group_info($group_id,$tag,$this_uid){
		$data = array();
		$this->db->from('group');
		$this->db->where(array(
			"id"=>$group_id,
			'tag'=>$tag
		));//->where_in("status",array(1,2))
		$query = $this->db->get();
		$group_info = $query->row_array();
		$data['tuan_status']=$group_info['status'];
		//新年天团
		if ($tag == 'ddy5W1' || $tag == 'LBfhW1'){
		    $data['end_time'] = date('Y/m/d H:i:s',strtotime("2016-01-17 23:59:59"));
		} elseif ($tag == 'CZmiS2'){
		    $data['end_time'] = date('Y/m/d H:i:s',strtotime("2016-01-26 23:59:59"));
		} else {
		    $data['end_time'] = date('Y/m/d H:i:s',strtotime("+24 hours",$group_info['time']));
		}

        $member_info = $this->db->from('group_member')->where(array(
			'tag'=>$tag,
			'group_id'=>$group_info['id'],
		))->where_in('status',array(2,3,9))->order_by('time', 'asc')->limit(5)->get()->result_array();
//var_dump($member_info);

		if($tag=='20151201'){
			$arr = array(
				'这么划算的事情能少了我？',
				'本宝宝一呼百应分分钟满团！',
				'这个好吃到爆，我会乱说？',
				'吓到宝宝了！差一点就没赶上…'
			);
		}else{
			$arr = array(
				'褚橙19元2斤，你不买是不是SA？',
//			'如果世界上有那个橙子出现过，我不愿错过',
				'你们城里人真会玩，请带上我！',
				'我不会告诉你橙子超甜哒',
				'世界一直在变，吃吃吃的心不变'
			);
		}

		if(!empty($member_info)){
			foreach($member_info as $v){
				if($v['uid']==$group_info['uid']){
					$data['master_mobile'] = $v['wx_name1']?base64_decode($v['wx_name1']):($v['wx_name']?$v['wx_name']:substr_replace($v['mobile'], "****", 3, 4));
					$is_master = true;
				}else{
					$is_master = false;
				}
				$data['member_info'][] = array(
					'mobile' => $v['wx_name1']?base64_decode($v['wx_name1']):($v['wx_name']?$v['wx_name']:substr_replace($v['mobile'], "****", 3, 4)),
					'wx_pic' => $v['wx_pic'],
					'time' => date('m-d H:i',$v['time']),
					'desc' => $is_master?($tag!='20151201'?"&nbsp;当上了团长,成为了高富帅":"速来拼团！见证友谊的时刻到啦！"):"&nbsp;".$arr[mt_rand(0,3)]
				);
			}
			$data['is_bought'] = false;
			foreach($member_info as $v){
				if($v['uid'] == $this_uid&&in_array($v['status'],array(2,3))){
					$data['is_bought'] = true;
					break;
				}
			}
		}
		return $data;
	}

	/*
     * 获取团内所有成员信息
     */
	function get_group_member($group_id){
		$this->db->from('group_member');
		$this->db->where("group_id",$group_id);
		$query = $this->db->get();
		return $grouper_info = $query->row_array();
	}

	/*
     * 获取成团数目
     */
	function get_group_num($tag){
		$sql = "SELECT count(*) as num from ttgy_group WHERE tag='{$tag}' AND status=1";
		$query = $this->db->query($sql);
		$result = $query->row_array();
		return $result['num'];
	}

	/*
	 * createorder 后操作
	 */
	function afterCreateOrder($order_id,$uid,$tag){
		$result = $this->db->select('group_id')->from('group_member')->where(array(
			'uid'=>$uid,
			'tag'=>$tag,
			'status'=>0
		))->get()->row_array();
		if(empty($result)){
			return '出错啦，请重新按正常流程尝试参团.';
		}

		$now_time = time();

		$result2 = $this->db->from('group')->where(array(
			'id'=>$result['group_id'],
			'uid'=>$uid,
			'tag'=>$tag,
			'status'=>0
		))->get()->row_array();

		if(!empty($result2)){
			$this->db->update('group',array(
				'status'=>1,
				'time'=>$now_time
			),array(
				'id'=>$result['group_id'],
				'status'=>0
			));
		}

		$this->db->update('group_member',array(
			'order_id'=>$order_id,
			'status'=>1,
			'time'=>$now_time
		),array(
			'uid'=>$uid,
			'tag'=>$tag,
			'status'=>0
		));
	}

	/*
     * master
     */
	function set_up($group_data,$member_data){
		$tag = $group_data['tag'];
		$uid = $group_data['uid'];
		$this->db->trans_begin();
		$sql1 = "SELECT id FROM ttgy_group WHERE tag='{$tag}' AND uid='{$uid}' AND status = 0";
		$is_pre_group = $this->db->query($sql1)->row_array();
		$is_update_group = false;
		if(!empty($is_pre_group)){
			$is_update_group = true;
			$this->db->update('group',$group_data,array('id'=>$is_pre_group['id']));
		}

		$sql = "SELECT id FROM ttgy_group_member WHERE tag='{$tag}' AND uid='{$uid}' AND status = 0";
		$is_pre_member = $this->db->query($sql)->row_array();
		$is_update_member = false;
		if(!empty($is_pre_member)){
			$is_update_member = true;
			$group_member_id = $is_pre_member['id'];
		}

		if($is_update_group){
			$group_id = $is_pre_group['id'];
		}else{
			$this->db->insert('group',$group_data);
			$group_id = $this->db->insert_id();
		}
		$member_data['group_id'] = $group_id;
		if($is_update_member){
			$this->db->update('group_member',$member_data,array('id'=>$group_member_id));
		}else{
			$this->db->insert('group_member',$member_data);
		}

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return false;
		} else {
			$this->db->trans_commit();
			return $group_id;
		}
	}

	function set_up_member($member_data){
		$uid = $member_data['uid'];
		$tag = $member_data['tag'];
		$sql = "SELECT id,group_id FROM ttgy_group_member WHERE tag='{$tag}' AND uid='{$uid}' AND status = 0";
		$is_pre_member = $this->db->query($sql)->row_array();
		$is_update = false;
		if(!empty($is_pre_member)){
			$is_update = true;
			$group_member_id = $is_pre_member['id'];
		}
		if($is_update){
			$this->db->update('group_member',$member_data,array('id'=>$group_member_id));
		}else{
			$this->db->insert('group_member',$member_data);
		}
		return $is_pre_member['group_id'];
	}

	/*
     * member
     */
	function payed($order_id){
		$this->db->trans_begin();
		$this->db->from('group_member');
		$this->db->where(array(
			'order_id'=>$order_id,
			'status'=>2
		));
		$result = $this->db->get()->row_array();

		$member_data = $this->db->from('group_member')->where(array(
			'order_id'=>$order_id
		))->get()->row_array();
		$num = count($result);
		if(4==$num){
			$this->db->update('group_member',array('status'=>2),array('order_id'=>$order_id));
			$this->db->update('group',array('status'=>3),"id={$member_data['group_id']}");
			$this->db->update('group_member',array('status'=>1),"group_id={$member_data['group_id']}");
			$this->lm_sms_queue($result,$member_data['mobile']);
		}elseif($num>4&&$num<10){
			$member_data['status'] = 1;
			$this->db->update('group_member',array('status'=>2),array('order_id'=>$order_id));
			$this->lm_sms_queue($member_data['mobile']);
		}else{
			$this->db->update('group_member',array('status'=>2),array('order_id'=>$order_id));
		}
//		return array("result" => "succ", "msg" => "参团成功，快去邀请好友来参加吧!","group_id"=>$member_data['group_id']);
	}

	function lm_sms_queue($mobiles,$mobile=''){//短信插入队列
		$sms_content = "您参加的团购活动已成团！http://t.cn/Rht9Kn8";
		if(is_array($mobiles)){
			if(!empty($mobiles)){
				$arr = array();
				foreach($mobiles as $v){
					$arr[]=array("job"=>serialize(array('mobile'=>$v["mobile"],'text'=>$sms_content)), "type"=>0);
				}
				if(!empty($mobile))
					$arr[]=array("job"=>serialize(array('mobile'=>$mobile,'text'=>$sms_content)), "type"=>0);
				$this->db->insert_batch("joblist",$arr);
			}
		}else{
			$this->db->insert("joblist",array("job"=>serialize(array('mobile'=>$mobiles,'text'=>$sms_content)), "type"=>0));
		}
	}

	function get_finish_orders(){
		$ftime = date('Y-m-d H:i:s',(time()-864000));
		$this->db->select('o.order_name');
		$this->db->from('order as o');
		$this->db->join('group_member as g','g.order_id=o.id');
		$this->db->where(array('o.order_type'=>'7','o.order_status'=>1,'o.pay_status'=>1,'o.sync_status'=>0,'o.time >'=>$ftime));
		$this->db->where_in('g.status',array(3,9));
		$this->db->limit(200);
		$result = $this->db->get()->result_array();
		return $result;
	}



	//upgrade v1
	public function getGroupList(){
//		$cache_key = 'groupList';
//		$rows = $this->memcached->get($cache_key);
//		if(empty($rows)){
			$now = time();
			$rows = $this->db->from('group_sales')->where(array(
				'begin_time <='=>$now,
				'end_time >='=>$now,
			    'is_show ='=>1
			))->order_by('sort','asc')->get()->result_array();
			if(!empty($rows))
				return $rows;
//				$this->memcached->set($cache_key,$rows,108000);
//		}
	}

	public function getGroupConfigData($tag){
		$cache_key = 'group'.$tag;

		$data = $this->memcached->get($cache_key);

		if(empty($data)){
//			$now = time();
			$data = $this->db->from('group_sales')->where(array(
				'active_tag'=>$tag,
//				'begin_time <='=>$now,
//				'end_time >='=>$now
			))->get()->row_array();
			if(!empty($data))
				$this->memcached->set($cache_key,$data,6400);
		}
		return $data;
	}

	function getProductInfo($product_id){
		$data = $this->db->select('product_name,discription,')->from('product')->where(array('id'=>$product_id))->get()->row_array();
		return $data;
	}

	/*
     * 获取团信息
     */
	function get_group_info_v1($group_id,$this_uid,$cuid){
		$data = array();
		$this->db->from('group');
		$this->db->where(array(
			"id"=>$group_id
		));//->where_in("status",array(1,2))
		$query = $this->db->get();
		$group_info = $query->row_array();

		$tag = $group_info['tag'];
		$configData = $this->getGroupConfigData($tag);
		$configData['config'] = unserialize($configData['config']);
		$tails = $configData['config']['tails'];
		$end = count($tails)-1;
		$full_num = $configData['config']['full_num'];

//		echo '<pre>';
//		print_r($configData['config']);
		//升值团处理
		if($configData['config']['config_type'] == 3){
			if($group_info['tuan_price']>0){
				$arr = (explode('元',$configData['config']['promotion_desc']));
				$arr[0] = $group_info['tuan_price'];
				$configData['config']['promotion_desc'] = implode("元",$arr);
			}
		}

		$data['tuan_status']=$group_info['status'];
		if ($tag == 'ddy5W1' || $tag == 'LBfhW1'){
		    $data['end_time'] = date('Y/m/d H:i:s',strtotime("2016-01-17 23:59:59"));
		} elseif($tag == 'CZmiS2') {
		    $data['end_time'] = date('Y/m/d H:i:s',strtotime("2016-01-26 23:59:59"));
		} else{
		    $data['end_time'] = date('Y/m/d H:i:s',strtotime("+24 hours",$group_info['time']));
		}

		$member_info = $this->db->from('group_member')->where(array(
			'group_id'=>$group_info['id']
		))->where_in('status',array(1,2,3,9))->order_by('time', 'asc')->limit($full_num)->get()->result_array();

		$data['configData'] = $configData;
		if(!empty($member_info)){
			foreach($member_info as $v){
				if($v['uid']==$group_info['uid']){
					$data['master_mobile'] = $v['wx_name1']?base64_decode($v['wx_name1']):($v['wx_name']?$v['wx_name']:substr_replace($v['mobile'], "****", 3, 4));
					$is_master = true;
					$data['member_info'][] = array(
						'mobile' => $v['wx_name1']?base64_decode($v['wx_name1']):($v['wx_name']?$v['wx_name']:substr_replace($v['mobile'], "****", 3, 4)),
						'wx_pic' => $v['wx_pic'],
						'time' => date('m-d H:i',$v['time']),
						'desc' => $is_master?"&nbsp;".$tails[0]:"&nbsp;".$tails[mt_rand(1,$end)]
					);
				}elseif($cuid == $v['uid']){
					$is_master = false;
					$data['member_info'][] = array(
						'mobile' => $v['wx_name1']?base64_decode($v['wx_name1']):($v['wx_name']?$v['wx_name']:substr_replace($v['mobile'], "****", 3, 4)),
						'wx_pic' => $v['wx_pic'],
						'time' => date('m-d H:i',$v['time']),
						'desc' => $is_master?"&nbsp;".$tails[0]:"&nbsp;".$tails[mt_rand(1,$end)]
					);
				}else{
					$is_master = false;
					if(in_array($v['status'],array(2,3,9))){
						$data['member_info'][] = array(
							'mobile' => $v['wx_name1']?base64_decode($v['wx_name1']):($v['wx_name']?$v['wx_name']:substr_replace($v['mobile'], "****", 3, 4)),
							'wx_pic' => $v['wx_pic'],
							'time' => date('m-d H:i',$v['time']),
							'desc' => $is_master?"&nbsp;".$tails[0]:"&nbsp;".$tails[mt_rand(1,$end)]
						);
					}
				}
			}

			$data['is_bought'] = false;
			foreach($member_info as $v){
				if($v['uid'] == $this_uid&&in_array($v['status'],array(2,3))){
					$data['is_bought'] = true;
					break;
				}
			}
		}
		$data['uptoNum'] = $full_num-count($data['member_info']);
		return $data;
	}

	function getOrderId($order_name){
		$result = $this->db->select('id,order_type,channel')->from("order")->where(array(
			'order_name'=>$order_name
		))->get()->row_array();
		return $result;
	}

	function setMemberInfoByPay($order_id,$member_data){
		return $this->db->update('group_member',$member_data,array('order_id'=>$order_id));
	}

	function getMemberInfo($order_id){
		return $this->db->from('group_member')->where(array('order_id'=>$order_id))->get()->row_array();
	}

	//是否新用户
	function is_new($uid){
		$result = $this->db->from('order')->where(array(
			'uid'=>$uid,
			'operation_id !='=>5,
			'order_status'=>1,
			'pay_status !='=>0
		))->get()->row_array();
		if(empty($result)){
			return true;
		}else{
			return false;
		}
	}

	function get_price_by_productid($pid){
		$result  =  $this->db->select('price')->from('product_price')->where(array(
			'product_id'=>$pid
		))->get()->row_array();
		if(!empty($result)){
			return $result['price'];
		}else{
			return false;
		}
	}

	//根据团的类型获取团的价格
	function get_tuan_price($promotion_pid,$config_type,$tag){
		$tuan_price = $this->get_price_by_productid($promotion_pid);
		if($tuan_price == false){
			return false;
		}
		if($config_type==3){		//处理
			$group_num = $this->check_group_num_flash($tag);
			$level1 = 300;//300;
			$level2 = 800;//800;
			if($group_num<=$level1){
				$tuan_price = round($tuan_price*0.3,1);
			}elseif($level1<$group_num&&$group_num<=$level2){
				$tuan_price = round($tuan_price*0.4,1);
			}elseif($group_num>$level2){
				$tuan_price = round($tuan_price*0.5,1);
			}
		}
		return $tuan_price;
	}

	 /*
	 * 查询闪购团的价格浮动区间
	 */
	function check_group_num_flash($tag){
		$begin_time = strtotime(date('Ymd'));
		$sql = "SELECT count(*) as num FROM ttgy_group WHERE tag='{$tag}' AND time>='{$begin_time}' AND status in (2,3)";
		$result = $this->db->query($sql)->row_array();
		return $result['num'];
	}

	//获取团内设置的价格
	function get_tuan_price_by_groupid($group_id){
		$result = $this->db->select('tuan_price')->from('group')->where(array(
			'id'=>$group_id
		))->get()->row_array();
		return $result['tuan_price'];
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
	            'start_time' => $gift_start_time,
	            'end_time' => $gift_end_time,
	        );
	        $this->db->insert('user_gifts', $user_gift_data);

	        return $user_gift_info;
	    }
	    /* 赠品赠送end */
	}

	function get_group_count($group_id,$tag){
		$member_info = $this->db->from('group_member')->where(array(
			'tag'=>$tag,
			'group_id'=>$group_id,
			'status'=>2
		))->get()->result_array();
		return count($member_info);
	}

	//支付中心 团购订单付款完成后的处理
    function checkGroupStatus($orderInfo){
        $order_id = $orderInfo['id'];
        $this->db->trans_begin();
        $member_data = $this->db->from('group_member')->where(array(
            'order_id'=>$order_id
        ))->get()->row_array();

        $tag = $member_data['tag'];

        $conf = $this->redis->get( 'group'.$tag );

        if(empty($conf)){
            $data = $this->db->select('config')->from('group_sales')->where(array(
                'active_tag'=>$tag,
            ))->get()->row_array();
            $config = unserialize($data['config']);
            $conf = json_encode($config);
            $this->redis->set('group'.$tag,$conf);
        }

        $confData = json_decode($conf);
        $full_num = $confData->full_num-1;
        $ceil_num = $confData->full_num+6;

        $this->db->from('group_member');
        $this->db->where(array(
            'group_id'=>$member_data['group_id']
        ))->where_in('status',array(2,3));
        $result = $this->db->get()->result_array();
        $num = count($result);

        if($full_num==$num){
            $this->db->update('group_member',array('status'=>3),array('order_id'=>$order_id));
            $this->db->update('group',array('status'=>3),array('id'=>$member_data['group_id']));
            $this->db->update('group_member',array('status'=>3),array("group_id"=>$member_data['group_id'],"status"=>2));

            //todo 发短信的操作
            $this->lm_sms_queue($result,$member_data['tag'],$member_data['mobile']);
        }elseif($num>$full_num){
            $member_data['status'] = 1;
            $this->db->update('group_member',array('status'=>3),array('order_id'=>$order_id));

            //todo 发短信的操作
            $this->lm_sms_queue($member_data['mobile'],$member_data['tag']);
        }else{
            $group = $this->db->select('status')->from('group')->where(array(
                'id'=>$member_data['group_id']
            ))->get()->row_array();
            if($group['status']==1){
                $this->db->update('group',array('status'=>2),array('id'=>$member_data['group_id']));
            }
            $this->db->update('group_member',array('status'=>2),array('order_id'=>$order_id));
        }
        $this->db->trans_commit();

        return true;
    }
}
