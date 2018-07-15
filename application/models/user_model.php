<?php

class User_model extends MY_Model {

	private $can_comment_period = "3 months";

	function User_model() {
		parent::__construct();
		// $this->load->library("session");
		$this->db_master = $this->load->database('default_master', TRUE);
	}

	function table_name() {
		return 'user';
	}

	/**
	 * 获取用户信息
	 */
	function selectUsers($field, $where = '', $where_in = '', $order = '', $limit = '') {
		$this->db->select($field);
		$this->db->from('user');
		if (!empty($where)) {
			$this->db->where($where);
		}
		if (!empty($where_in)) {
			$this->db->where_in($where_in['key'], $where_in['value']);
		}
		if (!empty($order)) {
			$this->db->order_by($order);
		}
		if (!empty($limit)) {
			$this->db->limit($limit['page_size'], ($limit['curr_page'] * $limit['page_size']));
		}
		$result = $this->db->get()->result_array();
		return $result;
	}

	/*
	 * 用户信息
	 */

	function getUser($uid = "", $condition = "", $field = "") {
		if (empty($field)) {
			$field = "id,email,username,money,mobile,mobile_status,reg_time,last_login_time,
            sex,birthday,user_head,enter_id,user_rank,is_pic_tmp,msgsetting,how_know as can_set_password,http_user_agent,how_to_know, invite_code, reg_from";
		}
		if ($condition) {
			$where = $condition;
		} else {
			$where = array('id' => $uid);
		}
		$user = $this->selectUser($field, $where);

		if (empty($user)) {
			$return_result = array('code' => '300', 'msg' => '用户名或密码错误');
			return $return_result;
		}
		if (empty($user['username'])) {
			$user['username'] = empty($user['mobile']) ? $user['email'] : $user['mobile'];
		}
		//set userface
		$user_head = unserialize($user['user_head']);
		$userface = $user_head['middle'];
		if ($user['is_pic_tmp'] == 1) {
			if (strstr($userface, "http")) {
				$user['userface'] = $userface;
			} else {
				$user['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL_TMP . $userface;
			}
		} else {
			if (strstr($userface, "http")) {
				$user['userface'] = $userface;
			} else {
				$user['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL . $userface;
			}
		}
		unset($user['user_head'], $user['is_pic_tmp']);
		//set user_rank
		$user['user_rank'] = empty($user['user_rank']) ? 0 : $user['user_rank'];
        $user['birthday'] = date("Y-m-d", $user['birthday']);

		if ($user['enter_id'] > 0) {
			$user['is_enterprise'] = 1;
			$this->db->select('company_name');
			$this->db->from('enterprise');
			$this->db->where('id', $user['enter_id']);
			$enterprise_name = $this->db->get()->row_array();
			$user['enterprise_name'] = $enterprise_name['company_name'];
		} else {
			$user['is_enterprise'] = 0;
			$user['enterprise_name'] = '';
		}
		return $user;
	}


	/**
	 * [getFruitUser 批量获取用户信息]
	 * @param  [array] $uids [description]
	 * @return [array]       [description]
	 */
	public function getUsers($uids) {
		if (empty($uids)) {
			return array();
		}
		$field = "id,username,user_head,mobile,email,id,is_pic_tmp,user_rank";
		$where_in = array('key' => 'id', 'value' => $uids);
		$res = $this->selectUsers($field, '', $where_in);
		if (empty($res)) {
			return array();
		}
		foreach ($res as &$val) {
			if (empty($val['username'])) {
				$val['username'] = empty($val['mobile']) ? $val['email'] : $val['mobile'];
			}
			//set userface
			$user_head = unserialize($val['user_head']);
			$userface = $user_head['middle'];
			if ($val['is_pic_tmp'] == 1) {
				$val['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL_TMP . $userface;
			} else {
				$val['userface'] = empty($userface) ? PIC_URL . "up_images/default_userpic.png" : PIC_URL . $userface;
			}
			$val['userrank'] = empty($val['user_rank']) ? 0 : $val['user_rank'];
			unset($val['user_head'], $val['is_pic_tmp'], $val['mobile'], $val['email'], $val['user_rank']);
		}
		return $res;
	}

	/*
	 * 积分信息
	 */

	function getUserScore($uid) {
		// $this->db->select('jf');
		// $this->db->from("user");
		// $this->db->where("id", $uid);
		// $query = $this->db->get();
		// $jf = $query->row_array();
  //       if($jf && is_numeric($jf['jf'])){
  //       	return $jf;
  //       }
        $jf = array();
        $jf['jf'] = $this->checkUserJf($uid);
		return $jf;
	}

    function getUserScoreNew($uid){
    	$this->db->select("sum(jf) as jf");
		$this->db->from("user_jf");
		$this->db->where("uid", $uid);
		$query = $this->db->get();
		$jf = $query->row_array();
		if (empty($jf['jf'])) {
			$jf['jf'] = 0;
		}
		$jf['jf'] = floor($jf['jf']);
		return $jf;
    }

    function getUserJf($uid){
    	$this->db->select("jf");
    	$this->db->from('user');
    	$this->db->where("id", $uid);
    	$query = $this->db->get();
		$jf = $query->row_array();
		if(is_numeric($jf['jf'])){
			return $jf['jf'];
		}
		return 0;
    }

	/*
	 * 优惠券数量
	 */

	function getCouponNum($uid, $used = 0) {
		$condition['uid'] = $uid;
		$condition['is_used'] = $used;
		$condition['to_date >='] = date("Y-m-d");
		$condition['is_sent'] = 1;
		return$this->db->from("card")->where($condition)->count_all_results();
	}

	/*
	 * 验证用户是否存在
	 */

	function check_user_exist($mobile) {
		$this->db->select('id');
		$this->db->from('user');
		$this->db->where('mobile', $mobile);
		$user_query = $this->db->get();
		if ($user_query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	/*
	 * 验证用户ip
	 */

	function check_user_ip($ip) {
		$this->db->select('id');
		$this->db->from('user_register_ip');
		$this->db->where('ip', $ip);
		$user_query = $this->db->get();
		if ($user_query->num_rows() > 500) {
			return true;
		} else {
			return false;
		}
	}

	function getLoginErrorNum($uid) {
		$this->db->select('id,num');
		$this->db->from('login_error');
		$this->db->where('uid', $uid);
		$res = $this->db->get()->row_array();
		return empty($res) ? -1 : $res['num'];
	}

	function setLoginErrorNum($uid, $reset = 0) {
		$num = $this->getLoginErrorNum($uid);
		$curr_num = $num + 1;
		if ($num == -1) {
			$this->db->insert('login_error', array('num' => $curr_num, 'uid' => $uid));
		} else {
			if ($reset == 1) {
				$curr_num = 0;
			}

			// if ($curr_num >= 8) {
			// 	if (!$this->check_user_black($uid)) {
			// 		$this->db->insert("user_black", array('uid' => $uid,'type'=>'0'));
			// 	}
			// } else {
				$this->db->where(array('uid' => $uid));
				$this->db->update('login_error', array('num' => $curr_num));
			// }
		}
		return $curr_num;
	}

	/*
	 * 获取用户信息
	 */

	function selectUser($field, $where) {
		$this->db_master->select($field);
		$this->db_master->from('user');
		$this->db_master->where($where);
		$result = $this->db_master->get()->row_array();
		return $result;
	}

	/*
	 * 新增用户
	 */

	function addUser($user_insert_data) {
		$this->db->insert("user", $user_insert_data);
		$id = $this->db->insert_id();
		return $id;
	}

	/*
	 * 修改用户
	 */

	function updateUser($where, $user_update_data) {
		$this->db->where($where);
		$this->db->update("user", $user_update_data);
		return $this->db->affected_rows();
	}

	/* 验证用户是否绑定过手机 */

	function check_has_bind($uid) {
		$this->db->select('mobile_status');
		$this->db->from('user');
		$this->db->where('id', $uid);
		$mobile_status_result = $this->db->get()->row_array();
		if ($mobile_status_result['mobile_status'] == '1') {
			return false;
		}
		return true;
	}

	// 会员积分规则
	// 蔡昀辰20150827优化 去除web_config配置文件直接把配置放入函数
	// 这个函数应该已经没有再引用
	function score_rule($type) {

		if (!$type) {
			return; // todo add log
		}

		$template = array(
			"user_score" => array(
			    "register" => array(
			        "jf" => 500,
			        "reason" => "绑定手机获得积分",
			    ),
			    "put_birthday"=>array(
			        "jf" => 500,
			        "reason" => "完善生日信息获得积分",
			        )
			),
			"user_sms" => array(
			    "register" => array(
			        "message" => "感谢您注册天天果园会员，赠送您500积分，下单即可使用。",
			    ),
			    "changepwd" => array(
			        "message" => "用户您好，您已经成功重置，新的密码为{changepwd}。",
			    ),
			),
			"web_var" => array(
			    "{username}" => "user.username",
			    "{mobile}" => "user.mobile",
			    "{register_verification_code}" => "session.register_verification_code",
			    "{order_num}" => "order.order_num",
			    "{changepwd}" => "changepwd.password",
			),
		);


		if ($template['user_score'][$type] =="register" ) {
			return $template['user_score'][$type];
		}

		return;
	}

	/*
	 * 添加积分
	 */

	function add_score($uid, $score) {
		if (!$uid || !$score)//todo add log
			return;
		$score['uid'] = $uid;
		$this->db->insert("user_jf", $score);
		return $this->db->insert_id();
	}

	/*
	 * 获取会员等级
	 */

	function get_rank($user_rank) {
		$rank_list = $this->config->item('user_rank');

		$rank = $rank_list['level'][$user_rank];

		if ($rank) {
			$rank['icon'] = 'http://www.fruitday.com/assets/images/bigpic/' . $rank['icon'];
			$rank['bigicon'] = 'http://www.fruitday.com/assets/images/bigpic/' . $rank['bigicon'];
		}


		return $rank ? $rank : array();
	}

	/*
	 * 获取等级计算周期
	 */

	function get_cycle() {
		return $this->config->item('cycle', 'user_rank');
	}

	/*
	 * 获取用户积分信息
	 */

	function get_user_jf_list($uid, $limit, $offset) {
		$this->db->select('jf,reason,time');
		$this->db->from('user_jf');
		$this->db->where('uid', $uid);
		$this->db->limit($limit, $offset);
		$this->db->order_by('time', 'desc');
		$result = $this->db->get()->result_array();
		return $result;
	}

	/*
	 * 获取用户充值信息
	 */

	function get_user_trade_list($uid, $limit, $offset,$params = null) {
		$this->db->select('time,money,trade_number,status,bonus');
		$this->db->from('trade');
		$this->db->where('uid', $uid);
		if( $params['has_deal'] ){
			$this->db->where(array("has_deal"=>$params['has_deal']));
		}
		$this->db->limit($limit, $offset);
		$this->db->order_by('time', 'desc');
		$result = $this->db->get()->result_array();
		return $result;
	}

	/*
	 * 获取用户余额信息
	 */

	function get_user_money($uid) {
		$this->db->select("count(*) as count, sum(money+bonus) as amount");
		$allres = $this->db->from("trade")->where(array("uid" => $uid, "has_deal" => 1))->get()->result();
		return $allres;
	}

    //withdraw 获取可体现金额   过滤充值赠送金额
    function get_user_real_money($uid){
        $this->db->select("sum(money+bonus) as amount,sum(money) as withdraw");
		$allres = $this->db->from("trade")->where(array("uid" => $uid, "has_deal" => 1))->get()->row_array();
		return $allres;
    }

	/*
	 * 获取充值或者使用的成交的单子
	 */
	function get_user_money_group_by_type($uid){
		$this->db->select('sum(money+bonus) as money,type');
		$this->db->from('trade');
		$this->db->where(array(
			'uid'=> $uid,
			'has_deal'=> 1
		))->group_by('type');
		$result = $this->db->get()->result_array();
		return $result;
	}


	/*
	 * 获取优惠券信息
	 */

	function get_user_coupon_list($condition, $limit, $offset) {
		$this->db->select('id,card_number,card_money,is_used,remarks,time,to_date,product_id,order_money_limit,maketing,direction,is_sent,channel,max_use_times,uid');
		$this->db->from("card");
		$this->_filter($condition);
		$this->db->order_by('id', 'desc');
		$this->db->limit($limit, $offset);
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	/*
	 * 获取默认收货地址
	 */

	function get_user_default_address($uid, $user_address_id = '') {
		$address_id = '';
		$this->db->select('id');
		$this->db->from('user_address');
		$this->db->where(array('uid' => $uid, 'is_default' => '1'));
		$default_addr = $this->db->get()->row_array();
		if (!empty($default_addr)) {
			$address_id = $default_addr['id'];
		} else {
			if (!empty($user_address_id)) {
				$this->db->select('uid');
				$this->db->from('user_address');
				$this->db->where('id', $user_address_id);
				$query = $this->db->get();
				$result = $query->row_array();
				if (!empty($result['uid']) && $result['uid'] == $uid) {
					$address_id = $user_address_id;
				} else {
					$this->db->select_max('id');
					$this->db->from('user_address');
					$this->db->where('uid', $uid);
					$user_address_query = $this->db->get();
					$user_address_result = $user_address_query->row_array();
					if (!empty($user_address_result['id'])) {
						$address_id = $user_address_result['id'];
					}
				}
			}
		}
		return $address_id;
	}

	/*
	 * 获取默认收货地址(产品部)
	 */

	function get_user_default_address_by_pm($uid, $user_address_id = '') {
		$address_id = '';
		$this->db->select('id');
		$this->db->from('user_address');
		$this->db->where(array('uid' => $uid, 'is_default' => '1'));
		$default_addr = $this->db->get()->row_array();
		if (!empty($default_addr)) {
			$address_id = $default_addr['id'];
		} else {
			$this->load->model('order_model');
			$order_info = $this->order_model->preOrderInfo($uid);
			$user_address_id = $order_info['address_id'];
			if (!empty($user_address_id)) {
				$this->db->select('uid');
				$this->db->from('user_address');
				$this->db->where('id', $user_address_id);
				$query = $this->db->get();
				$result = $query->row_array();
				if (!empty($result['uid']) && $result['uid'] == $uid) {
					$address_id = $user_address_id;
				} else {
					$this->db->select_max('id');
					$this->db->from('user_address');
					$this->db->where('uid', $uid);
					$user_address_query = $this->db->get();
					$user_address_result = $user_address_query->row_array();
					if (!empty($user_address_result['id'])) {
						$address_id = $user_address_result['id'];
					}
				}
			}else{
				return '';
			}
		}
		return $address_id;
	}

	/*
	 * 查询用户收货地址
	 */

	function selectAddressInfo($fields, $where) {
		$this->db->select($fields);
		$this->db->from('user_address');
		$this->db->where($where);
		$area_info = $this->db->get()->row_array();
		return $area_info;
	}

	/*
	 * 获取用户收货地址
	 */

	function geta_user_address($uid, $address_id = '', $use_case = '', $source = 'wap') {
		$is_enterprise = false;
		 if($source=='app'){
			if ($use_case == 'order') {
				$this->load->model('order_model');
				if ($enterprise_result = $this->order_model->check_enterprise($uid)) {
					$enter_uid = $enterprise_result['uid'];
					$is_enterprise = true;
				}
			}
		 }
		$this->db->select('uid,province_name,province_adcode,province,name,mobile,lonlat,id,flag,city_name,city_adcode,city,area_name,area_adcode,area,address_name,address,is_default as isDefault');
		$this->db->from('user_address');
		if ($is_enterprise) {
			$this->db->where('uid', $enter_uid);
		} else {
			$this->db->where('uid', $uid);
		}
		if ($address_id) {
			$this->db->where('id', $address_id);
		}
		$query = $this->db->get();
		$result = $query->result_array();
		if(empty($result)){
			return $result;
		}


		$area_ids_tmp = array();
		foreach ($result as $key => $value) {
			$area_ids_tmp[$value['province']] = $value['province'];
			$area_ids_tmp[$value['city']] = $value['city'];
			$area_ids_tmp[$value['area']] = $value['area'];
		}

		$this->db->select('id,name');
		$this->db->from('area');
		$this->db->where_in('id', $area_ids_tmp);
		$area_query = $this->db->get();
		$area_result = $area_query->result_array();
		$area_result_tmp = array();
		foreach ($area_result as $key => $value) {
			$area_result_tmp[$value['id']]['id'] = $value['id'];
			$area_result_tmp[$value['id']]['name'] = $value['name'];
		}

		foreach ($result as $key => $value) {
			$result[$key]['province'] = $area_result_tmp[$value['province']];
			$result[$key]['city'] = $area_result_tmp[$value['city']];
			$result[$key]['area'] = $area_result_tmp[$value['area']];
			$result[$key]['flag'] = empty($value['flag']) ? '' : $value['flag'];
		}

		$result_tmp = array();
		foreach ($result as $key => $value) {
			$result_tmp[] = $value;
		}
		$result = $result_tmp;




		if ($source == 'app') {
			if ($is_enterprise) {
				$this->db->select('enterprise_name,enterprise_mobile');
				$this->db->from('user');
				$this->db->where('id', $uid);
				$user_enter_info = $this->db->get()->row_array();
				foreach ($result as $key => $value) {
					$result[$key]['name'] = $user_enter_info['enterprise_name'];
					$result[$key]['mobile'] = $user_enter_info['enterprise_mobile'];
				}
			}
		}
		// echo '<pre>';print_r($result);exit;
		return $result;
	}

	/*
	 * 黑名单验证
	 */

	function check_user_black($uid) {
		return false;
		$this->db->from('user_black');
		$this->db->where('uid', $uid);
		$result = $this->db->get()->row_array();
		if (!empty($result)) {
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * 根据会员等级获取多倍积分
	 *
	 * @return void
	 * @author
	 * */
	public function cal_rank_score($score, $user_rank = 1, &$msg,$jf=0) {
		$level = $this->get_rank($user_rank);
		$mul = substr($level['pmt']['score'], 0, -1);
		$mul = is_numeric($mul) ? $mul : 1;

        //fix 积分比例
        if($jf >= 0)
        {
            $jf_p = floatval($jf/100);
            $score = $score * $mul * $jf_p;
        }
        else
        {
            $score = $score * $mul;
        }
        $score = round($score);

		return $score;
	}

	/*
	 * 订单提交获取地址信息
	 */

	function address_info($uid, $address_id, $is_enterprise = 0) {
		$this->db->from("user_address");
		if ($is_enterprise) {
			$this->db->where(array('id' => $address_id));
		} else {
			$this->db->where(array('uid' => $uid, 'id' => $address_id));
		}
		$query = $this->db->get();
		$result = $query->row_array();
		if (empty($result)) {
			return array("code" => "300", "msg" => '请选择并保存收货地址');
		}
		if(empty($result['name'])){
			return array("code" => "300", "msg" => '请填写收货人姓名');
		}
		if ($is_enterprise) {
			$this->db->select('enterprise_name,enterprise_mobile');
			$this->db->from('user');
			$this->db->where('id', $uid);
			$user_enter_info = $this->db->get()->row_array();
			$result['name'] = $user_enter_info['enterprise_name'];
			$result['mobile'] = $user_enter_info['enterprise_mobile'];
		}
		return $result;
	}

	/*
	 * 帐户余额扣除
	 */

	function cut_user_money($uid, $money, $order_name,$is_repair=false) {
		if($money==0){
			return true;
		}
		$this->db->query("UPDATE ttgy_user set money = money - $money where id = $uid");
		if (!$this->db->affected_rows()) {
			return false;
		}
		$reason = '';
		if($is_repair){
			$reason = "(订单恢复)";
		}
		//交易记录
		$this->load->model('order_model');
		$this->order_model->generate_order("trade", array(
			"trade_number" => "",
			"uid" => $uid,
			"money" => "-" . $money,
			"payment" => "账户余额支付",
			"status" => "支出涉及订单号" . $order_name.$reason,
			"time" => date('Y-m-d H:i:s'),
			"has_deal" => 1,
			"order_name" => $order_name
		));
		if($is_repair){
			return true;
		}
		$this->db->query("UPDATE ttgy_order set pay_status = 1 where order_name = '" . $order_name . "'");
		if (!$this->db->affected_rows()) {
			return false;
		}
		return true;
	}

	/*
	 * 积分使用
	 */

	function cut_uses_jf($uid, $score, $order_name,$is_repair=false,$type='消费') {
		$jf = "-" . $score;
		$reason = "订单" . $order_name . "消费积分" . $score . "抵扣" . ($score / 100) . "元";
		if($is_repair === true){
			$reason .= "(订单恢复)";
		}
		$data = array(
			'uid' => $uid,
			'time' => date("Y-m-d H:i:s"),
			'jf' => $jf,
			'reason' => $reason,
			'type'=>$type,
		);
		$this->db->insert('user_jf', $data);
		if (!$this->db->affected_rows()) {
			return false;
		}
		$this->updateJf($uid,$jf,2);
		return true;
	}

	/*
	 * 设备验证标记插入
	 */

	function add_device_limit($device_product_id, $device_code, $order_id) {
		foreach ($device_product_id as $device_pid) {
			$this->db->from('device_limit');
			$this->db->where(array('device_code' => $device_code, 'product_id' => $device_pid));
			if ($this->db->get()->num_rows() > 0) {
				$this->db->where(array('device_code' => $device_code, 'product_id' => $device_pid));
				$update_data = array('order_id' => $order_id);
				$this->db->update('device_limit', $update_data);
			} else {
				$insert_data = array(
					'device_code' => $device_code,
					'product_id' => $device_pid,
					'order_id' => $order_id
				);
				$this->db->insert('device_limit', $insert_data);
			}
		}
	}

	/**
	 * 预存款充值
	 *
	 * @return void
	 * @author
	 * */
	public function deposit_recharge($uid, $money) {
		// $rs = $this->db->update('user',array('money'=>'money+'.$money),array('id'=>$uid));
		$rs = $this->db->set('money', 'money+' . $money, false)->where('id', $uid)->update('user', null, null, 1);

		return $rs ? $this->db->affected_rows() : 0;
	}

	/*
	 * 注册成功活动事件
	 */

	function wqbaby_active($mobile, $uid) {
		$active_begin = "2014-11-17 00:00:00"; //2014-07-08 00:00:00
		$active_end = "2015-11-12 00:00:00"; //2014-07-23 00:00:00
		$now_time = date("Y-m-d H:i:s");
		if ($now_time >= $active_begin) {// && $now_time<=$active_end
			$this->db->from('wqbaby_active');
			$this->db->where('mobile', $mobile);
			//$this->db->where_in('active_tag', array('shuyou_2015_03', 'open_20_card_0319', 'year_cele_2015_03', 'yingtao_2015_618', 'o2o_2015_526','tmall_member_06_08'));
			$result = $this->db->get()->result();
			if (!empty($result)) {
				foreach ($result as $v) {
					if ($v->active_type == 1) {
						$card_update = array(
							'uid' => $uid,
							'is_sent' => '1'
						);
						$this->db->where('card_number', $v->card_number);
						$this->db->update('card', $card_update);
					} elseif ($v->active_type == 2) {
						$gift_update = array(
							'uid' => $uid,
						);
						$this->db->where('id', $v->card_number);
						$this->db->update('user_gifts', $gift_update);
					} elseif ($v->active_type == 4){
						$score = array(
							'jf' => $v->card_money,   //积分
							"reason" => $v->description,  //积分备注
							'time' => date("Y-m-d H:i:s"),
							'uid' => $uid
						);
						$this->db->insert("user_jf", $score);
                                        }elseif($v->active_type == 5){
                                                $seed_update = array(
							'uid' => $uid
                                                );
                                                $this->db->where('id', $v->card_number);
                                                $this->db->update('farm_user_seed', $seed_update);
                                        }elseif($v->active_type == 6){
                                                $seed_update = array(
							'uid' => $uid
                                                );
                                                $this->db->where('id', $v->card_number);
                                                $this->db->update('fc_tuan_members', $seed_update);
                                        }
				}
			}
		}
	}

	/**
	 * 注册送赠品
	 */
	public function giveGift($uid, $active_id) {
		if (empty($active_id)) {
			return FALSE;
		}
		if (is_array($active_id)) {
			$user_gift_arr = array();
			foreach ($active_id as $aid) {
				$gift_send = $this->db->select('*')->from('gift_send')->where('id', $aid)->get()->row_array();
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
					'active_id' => $aid,
					'active_type' => '2',
					'has_rec' => '0',
					'start_time'=>$gift_start_time,
					'end_time'=>$gift_end_time,
				);
				array_push($user_gift_arr, $user_gift_data);
			}
			$flag = $this->db->insert_batch('user_gifts', $user_gift_arr);
		} else {
			$gift_send = $this->db->select('*')->from('gift_send')->where('id', $active_id)->get()->row_array();
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
				'active_id' => $active_id,
				'active_type' => '2',
				'has_rec' => '0',
				'start_time'=>$gift_start_time,
				'end_time'=>$gift_end_time,
			);
			$flag = $this->db->insert('user_gifts', $user_gift_data);
		}
		if (!$flag) {
			$this->load->library('fdaylog');
			$db_log = $this->load->database('db_log', TRUE);
			$this->fdaylog->add($db_log, 'zyr_reg_gift', $uid . ':' . date('Y-m-d H:i:s'));
		}
	}

	/**
	 * 注册送优惠券
	 */
	public function sendCard($uid, $cardList) {
		$share_p_card_number = 'register';
		foreach ($cardList as $val) {
			$card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
			$card_data = array(
				'uid' => $uid,
				'sendtime' => date("Y-m-d"),
				'card_number' => $card_number,
				'card_money' => $val['cardMoney'],
				'product_id' => '',
				'maketing' => 0,
				'is_sent' => 1,
				'restr_good' => '0',
				'remarks' => $val['remarks'],
				'time' => $val['startTime'],
				'to_date' => $val['endTime'],
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'order_money_limit' => $val['moneyLimit'],
				'return_jf' => 0,
				// 'black_user_list'=>'',
				'channel' => ''
			);
			$flag = $this->db->insert('card', $card_data);
			if (!$flag) {
				$this->load->library('fdaylog');
				$db_log = $this->load->database('db_log', TRUE);
				$this->fdaylog->add($db_log, 'zyr_register', $uid . ':' . $val['card_money']);
			}
		}
	}

	/**
	 * 获取推荐人
	 * @param  $mobile
	 * @return $uid
	 */
	public function getInvite($mobile) {
		$result = $this->db->select('invite_by')->from('user_invite_new2')->where('mobile_phone', $mobile)->get()->row_array();
		return $result['invite_by'] ? $result['invite_by'] : 0;
	}

	/*
	 * 获取企业信息
	 */

	function get_enter_info($enterprise_tag) {
		$this->db->select('id');
		$this->db->from('enterprise');
		$this->db->where('tag', $enterprise_tag);
		$enter_result = $this->db->get()->row_array();
		return $enter_result;
	}

	/*
	 * 获取企业商品信息
	 */

	function get_enter_product($id) {
		$this->db->select('product_id');
		$this->db->from('enterprise');
		$this->db->where('id', $id);
		$enter_result = $this->db->get()->row_array();
		return $enter_result;
	}

	/*
	 * 是否是摇一摇黑名单
	 */

	function is_shake_black($uid) {
		$this->db->from('shake_black');
		$this->db->where('uid', $uid);
		$result = $this->db->get()->num_rows();
		if ($result > 0) {
			return true;
		}
		return false;
	}

	/*
	 * 获取额外的摇一摇次数
	 */

	function get_extra_shake_num($uid,$date){
		$this->db->from('shake_exchange');
		$this->db->where('uid', $uid);
		$this->db->where('time',$date);
		$num = $this->db->get()->num_rows();
		return $num?$num:0;
	}

	/*
	 * 当日摇一摇次数
	 */

	function shake_record_count($uid, $date, $gift_level = '') {
		$this->db->from('shake_records');
		$where = array('uid' => $uid, 'time' => $date);
		if (!empty($gift_level)) {
			$where['gift_level !='] = $gift_level;
		}
		$this->db->where($where);
		return $this->db->count_all_results();
	}

	/*
	 * 摇一摇历史记录获取
	 */
	public function get_shake_history($field, $limits, $uid){
		if(!empty($where)){
			$this->db->where($where);
		}
		if(!empty($field)){
			$this->db->select($field);
		}
		if(!empty($limits)){
			$this->db->limit($limits['page_size'],(($limits['curr_page']-1)*$limits['page_size']));
		}
		$this->db->order_by('time','desc');
		$this->db->from('shake_records');
		$this->db->where(array('gift_level !='=>9,'uid'=>$uid));
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	/*
	 * 摇一摇积分换次数
	 */
	function exchange_score($uid,$score2change){
		$date = date("Y-m-d");
		$user_score = $this->getUserScore($uid);
		$jf = $user_score['jf'];
		if($jf<$score2change){
			return array('code' => '300', 'msg' => "你的积分不够。");
		}

		$score = array(
			'jf' => '-'.$score2change, //设置摇一摇赠送的积分
			"reason" => "摇一摇积分换抽奖次数",
			'time' => date("Y-m-d H:i:s"),
		);
		$this->add_score($uid,$score);

		/*
		 * exchange操作*/
		$data = array(
			'uid'=>$uid,
			'time'=>$date
		);
		$this->db->insert("shake_exchange",$data);

		return array('code' => '200', 'msg' => "置换成功。");
	}


	/*
	 * 摇一摇奖品
	 */

	function search_gift_left($date, $column = '') {
		$this->db->from('shake_daily_left_gifts');
		if (empty($column))
			$column = '*';
		$this->db->select($column);
		$where = array('time' => $date);
		$this->db->where($where);
		return $this->db->get()->row_array();
	}

	function get_cache_shake_config() {//获取摇一摇配置信息
		// $this->load->library("memcached");
		// $config = $this->memcached->get('shake_config');
		// if(empty($config)){
		$now = date("Y-m-d H:i:s");
		$this->db->select("*");
		$this->db->from("shake_config");
		$this->db->where(array('begin <=' => $now,'end >=' => $now));
		$this->db->order_by('id', 'asc');
		$this->db->limit(8);
		$query = $this->db->get();
		$config = $query->result_array();
		if(!empty($config)){
			foreach($config as $k=>$v){
				$config[$k]['id'] = $k+1;
			}
		}
		// $this->memcached->set('shake_config',$config,60*60*24*30);
		// }
		return $config;
	}

	function create_gift_left_V155($date) {
		$config = $this->get_cache_shake_config();
		if (empty($config)) {
			return array('code' => '300', 'msg' => "摇一摇信息尚未配置哦。");
		}
		$data = array(
			'time' => $date,
			'level1_num_left' => $config[0]['num_limit'],
			'level2_num_left' => $config[1]['num_limit'],
			'level3_num_left' => $config[2]['num_limit'],
			'level4_num_left' => $config[3]['num_limit'],
			'level5_num_left' => $config[4]['num_limit'],
			'level6_num_left' => $config[5]['num_limit'],
			'level7_num_left' => $config[6]['num_limit'],
			'level8_num_left' => $config[7]['num_limit']
		);
		$this->db->insert(
				'shake_daily_left_gifts', $data
		);
	}

	function get_shake_records($date) {
		$this->db->select('a.*,b.uname');
		$this->db->from('shake_records a');
		$this->db->join('user b', 'a.uid = b.id');
		$this->db->where(array('gift_level' => 4, 'time' => date("Y-m-d", strtotime($date) - 24 * 60 * 60)));
		$query = $this->db->get();
		$shake_records = $query->row_array();
		return $shake_records;
	}

	function get_shake_level_num($uid, $date, $gift_level) {
		$this->db->from('shake_records');
		$this->db->where(array('uid' => $uid, 'time' => $date));
		if (is_array($gift_level)) {
			$this->db->where_in('gift_level', $gift_level);
		} else {
			$this->db->where('gift_level', $gift_level);
		}

		$level_num = $this->db->count_all_results();
		return $level_num;
	}

	public function add_shake_record($uid, $gift_level = 9, $product_name = "", $extra_config = '') {
		return $this->db->insert('shake_records', array(
					'uid' => $uid,
					'time' => date('Y-m-d'),
					'gift_level' => $gift_level,
					'gift_name' => $product_name,
					'gift_type' => $extra_config['type'],
					'gift_price_id' => $extra_config['gift_price_id'],
					'gift_product_id' => $extra_config['gift_product_id'],
					'gift_activity_url' => $extra_config['gift_activity_url']
		));
	}

	/**
	 * @param $uid
	 * @return bool
	 *       赠卡
	 */
	public function send_card($uid, $date, $tag, $useful_day = 3) {
		/* 抵扣码赠送start */
		$this->db->from('mobile_card');
		$this->db->where(array('card_tag' => $tag, 'card_type' => '9'));
		$query = $this->db->get();
		$mobilecard_info = $query->row_array();
		if (empty($mobilecard_info)) {
			return false;
		} else {
//            //优惠券有未过期的就过滤
//            $this->db->from('card');
//            $this->db->where(array('uid'=>$uid,"remarks"=>$mobilecard_info['card_desc']));
//            $this->db->where('to_date >=',date('Y-m-d'));
//            $result = $this->db->get()->row_array();
//            if(!empty($result)){
//                return false;
//            }
			$card_number = $mobilecard_info['p_card_number'] . $this->rand_card_number($mobilecard_info['p_card_number']);
			//$to_date = ($mobilecard_info['card_to_date']!='0000-00-00')?$mobilecard_info['card_to_date']:date("Y-m-d",time()+2592000);
			$today = date('Y-m-d');
			if ($today > '2015-02-13' && $today < '2015-02-26') {
				$to_date = '2015-02-28';
			} else {
				$to_date = date("Y-m-d", strtotime("+{$useful_day}day"));
			}
			$card_data = array(
				'uid' => $uid,
				'sendtime' => date("Y-m-d", time()),
				'card_number' => $card_number,
				'card_money' => $mobilecard_info['card_money'],
				'product_id' => $mobilecard_info['product_id'],
				'maketing' => '0',
				'is_sent' => '1',
				'restr_good' => $mobilecard_info['restr_good'],
				'remarks' => $mobilecard_info['card_desc'],
				'time' => date("Y-m-d"),
				'to_date' => $to_date, //赠卡有效期3天
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => $mobilecard_info['can_sales_more_times'],
				'card_discount' => 1,
				'order_money_limit' => $mobilecard_info['order_money_limit'],
				'channel' => $mobilecard_info['channel'],
				'direction' => $mobilecard_info['direction'],
			);
			$this->db->insert('card', $card_data);
			return array($mobilecard_info['card_money'], $mobilecard_info['card_desc']);
		}
		/* 抵扣码赠送end */
	}

	private function rand_card_number($p_card_number = '') {
		$a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
		$a_array = explode(",", $a);
		for ($i = 1; $i <= 10; $i++) {
			$tname.=$a_array[rand(0, 31)];
		}
		if ($this->checkCardNum($p_card_number . $tname)) {
			$tname = $this->rand_card_number($p_card_number);
		}
		return $tname;
	}

	private function checkCardNum($card_number) {
		$this->db->from('card');
		$this->db->where('card_number', $card_number);
		$query = $this->db->get();
		$num = $query->num_rows();
		if ($num > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 添加ruby活动优惠券
	 */
	public function set_ruby_card($uid) {
		/*
		 * 提交到card
		 */
		$share_p_card_number = 'ruby';
		$share_remarks = "ruby活动优惠券";
		$cardNumber = $share_p_card_number . $this->rand_card_number($share_p_card_number);
		$date = date("Y-m-d", time());
		$channel = '';
		$this->db->from('card');
		$this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
		$result = $this->db->get()->row_array();
		if (empty($result)) {
			$cardData = array(
				'uid' => $uid,
				'sendtime' => $date,
				'card_number' => $cardNumber,
				'card_money' => "20",
				'product_id' => '2631',
				'maketing' => '0',
				'is_sent' => 1,
				'restr_good' => '1',
				'remarks' => $share_remarks,
				'time' => $date,
				'to_date' => '2015-06-15', //date("Y-m-d",strtotime("+3day")),
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'return_jf' => '',
				'black_user_list' => '',
				'channel' => $channel
			);
			$this->db->insert('card', $cardData);
			return TRUE;
		}
		return FALSE;
	}

	public function get_special_pro($id) {
		$this->load->helper('public');
		$product = array();
		$this->db->select("id,product_name,sweet,summary,tips,photo,thum_photo,bphoto,thum_min_photo,order_id,app_online as online,offline,send_region,free,yd,lack,types,maxgifts,parent_id,gift_photo,use_store,template_id");
		$this->db->from("product");
		$this->db->where("id", $id);
		$this->db->order_by(' order_id desc,id desc');
		$query = $this->db->get();
		$result = $query->row_array();

		if (empty($result)) {
			return json_encode(array('code' => '300', 'msg' => '该商品已售罄'));
		} else {
            // 获取产品模板图片
            if ($result['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($result['template_id']);
                if (isset($templateImages['main'])) {
                    $result['bphoto'] = $templateImages['main']['big_image'];
                    $result['photo'] = $templateImages['main']['image'];
                    $result['middle_photo'] = $templateImages['main']['middle_image'];
                    $result['thum_photo'] = $templateImages['main']['thumb'];
                    $result['thum_min_photo'] = $templateImages['main']['small_thumb'];
                }
            }

			$result['thum_photo'] = PIC_URL . $result['thum_photo'];
			$result['photo'] = PIC_URL . $result['photo'];
			$result['bphoto'] = PIC_URL . $result['bphoto'];
			$result['thum_min_photo'] = PIC_URL . $result['thum_min_photo'];
			$region_arr = array_flip($this->config->item('str_area_refelect'));
			foreach (unserialize($result['send_region']) as $key => $value) {
				$result['region'] .= $region_arr[$value] . ',';
			}
			$result['region'] = trim($result['region'], ',');
			unset($result['send_region']);
			$product['product'] = $result;
			unset($result);
		}


		$this->db->from("product_price");
		$this->db->where("product_id", $id);
		$this->db->order_by(' order_id asc,id desc');
		$query = $this->db->get();
		$price_result = $query->result_array();
		foreach ($price_result as $key => $value) {
			if ($value['mobile_price'] > 0) {
				$price_result[$key]['pc_price'] = $value['price'];
				$price_result[$key]['price'] = $value['mobile_price'];
			} else {
				$price_result[$key]['pc_price'] = "0";
			}
		}
		$product['items'] = $price_result;
		$this->db->select('id,product_id,thum_photo,photo,bphoto,thum_min_photo,order_id');
		$this->db->from("product_photo");
		$this->db->where("product_id", $id);
		$this->db->order_by(' order_id desc,id desc');
		$query = $this->db->get();
		$photo_arr = $query->result_array();

        // 获取产品模板图片
        if ($result['template_id']) {
            if (isset($templateImages['detail'])) {
                foreach ($templateImages['detail'] as $key => $value) {
                    $photo_arr[$key]['thum_photo'] = PIC_URL . $value['thumb'];
                    $photo_arr[$key]['photo'] = PIC_URL . $value['image'];
                    $photo_arr[$key]['bphoto'] = PIC_URL . $value['big_image'];
                    $photo_arr[$key]['thum_min_photo'] = PIC_URL . $value['small_thumb'];
                }
            }
        } else {
            foreach ($photo_arr as $key => $value) {
                $photo_arr[$key]['thum_photo'] = PIC_URL . $value['thum_photo'];
                $photo_arr[$key]['photo'] = PIC_URL . $value['photo'];
                $photo_arr[$key]['bphoto'] = PIC_URL . $value['bphoto'];
                $photo_arr[$key]['thum_min_photo'] = PIC_URL . $value['thum_min_photo'];
            }
        }
		$product['photo'] = $photo_arr;
		$product['share_url'] = "http://m.fruitday.com/detail/index/" . $id;
		return $product;
	}

	public function send_gift_V155($uid, $tag) {
		/* 赠品赠送start */
//        $reflect_arr = $this->get_region_reflect();
		$now_data_time = date("Y-m-d H:i:s");
		$this->db->select('a.*,b.product_name');
		$this->db->from('gift_send a');
		$this->db->join('product b', 'a.product_id = b.id');
		$this->db->where(array('tag' => $tag, 'start <=' => $now_data_time, 'end >=' => $now_data_time));
		$query = $this->db->get();
		$user_gift_info = $query->row_array();
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
			return $user_gift_info["product_name"];
		}
		/* 赠品赠送end */
	}

	function update_gift_left($level, $date) {
		$sql = "UPDATE `ttgy_shake_daily_left_gifts` SET `{$level}` = `{$level}`-1 WHERE `time` = '{$date}'";
		$this->db->query($sql);
//        $data = array(
//            $level => "(".$level."-1)"
//        );
//
//        $this->db->where(array(
//            'uid'=>$uid,
//            'time'=>$date
//        ));
//        $this->db->update('shake_daily_left_gifts', $data);
	}

	/*
	 * 获取充值卡信息
	 */

	function get_gift_card_info($card_password) {
		$this->db_master->select("is_used,to_date,activation,is_expire,is_freeze");
		$this->db_master->from("gift_cards");
		$this->db_master->where(array("card_pass" => $card_password));
		$card = $this->db_master->get()->result();
		return $card;
	}

	function via_acount($charge_code, $uid, $region_id = '106092') {
		$this->load->library("PassMd5");
		$card_password = $this->passmd5->md5Pass($charge_code);

		$this->db->from("gift_cards");
		$this->db->where(array("card_pass" => $card_password));
		$card = $this->db->get()->result();

		$content = "客户使用{$charge_code}操作充值。";
		$time = date("Y-m-d H:i:s");
		$this->db->trans_begin();

        $data = $this->db->query("select card_money,is_used from ttgy_gift_cards where card_pass = '$card_password'");
        $dataResult = $data->result();
        if ($dataResult[0]->card_money == 0 || $dataResult[0]->is_used == 1) {
            // $this->db->trans_rollback();
            return array('code' => '300', 'msg' => '充值失败');
        }

		$this->db->query("UPDATE ttgy_gift_cards set card_money = 0,is_used=1 where card_pass = '$card_password'");
		$this->db->query("INSERT ttgy_gift_cards_use (username,card_number,content,time)VALUES('$uid','{$card[0]->card_number}','$content','$time')");
		$c_region = '1';
		$area_refelect = $this->config->item("area_refelect");
		$site_list = $this->config->item("site_list");
        $region_id = isset($site_list[$region_id]) ? $site_list[$region_id] : $region_id;
		foreach ($area_refelect as $key => $value) {
			if (in_array($region_id, $value)) {
				$c_region = $key;
				break;
			}
		}

		$this->load->model('order_model');
		$trade_number = $this->order_model->generate_order("trade", array(
			"trade_number" => "",
			"uid" => $uid,
			"money" => $card[0]->card_money,
			"card_number" => $card[0]->card_number,
			"payment" => "天天果园充值卡",
			"time" => $time,
			"status" => "已充值",
			"has_deal" => 1,
			"region" => $c_region,
		));

		$this->db->query("UPDATE ttgy_user set money = money + {$card[0]->card_money} where id = $uid");

		$this->db->query("INSERT ttgy_gift_cards_unique (card_number,time)VALUES('{$card[0]->card_number}','$time')");

		// $this->db->trans_complete();
		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return array('code' => '300', 'msg' => '充值失败!');
		} else {
			$this->db->trans_commit();

			$this->load->bll('pool/recharge');
			$this->bll_pool_recharge->pushone(array('id' => $trade_number));
		}
		return array('code' => '200', 'msg' => '充值成功');
	}

	function via_alipay($money, $uid, $region_id = '106092') {
		$c_region = '1';
		$area_refelect = $this->config->item("area_refelect");
		$site_list = $this->config->item("site_list");
        $region_id = isset($site_list[$region_id]) ? $site_list[$region_id] : $region_id;
		foreach ($area_refelect as $key => $value) {
			if (in_array($region_id, $value)) {
				$c_region = $key;
				break;
			}
		}

		$this->load->model('order_model');
		$trade_id = $this->order_model->generate_order("trade", array(
			"trade_number" => "",
			"uid" => $uid,
			"money" => $money,
			"payment" => "支付宝",
			"time" => date("Y-m-d H:i:s"),
			"status" => "等待支付",
			"region" => $c_region,
			'order_name'=>''
		));
		// $this->db->select('trade_number');
		// $this->db->from('trade');
		// $this->db->where('id', $trade_id);
		// $result = $this->db->get()->row_array();
		$trade_number = $this->order_model->generate_order_name;
		return array('code' => '200', 'msg' => $trade_number);
	}

	function via_weixin($money, $uid, $region_id = '106092') {
		$c_region = '1';
		$area_refelect = $this->config->item("area_refelect");
		$site_list = $this->config->item("site_list");
        $region_id = isset($site_list[$region_id]) ? $site_list[$region_id] : $region_id;
		foreach ($area_refelect as $key => $value) {
			if (in_array($region_id, $value)) {
				$c_region = $key;
				break;
			}
		}

		$this->load->model('order_model');
		$trade_id = $this->order_model->generate_order("trade", array(
			"trade_number" => "",
			"uid" => $uid,
			"money" => $money,
			"payment" => "微信支付",
			"time" => date("Y-m-d H:i:s"),
			"status" => "等待支付",
			"region" => $c_region,
			'order_name'=>''
		));
		// $this->db->select('trade_number');
		// $this->db->from('trade');
		// $this->db->where('id', $trade_id);
		// $result = $this->db->get()->row_array();
		$trade_number = $this->order_model->generate_order_name;
		return array('code' => '200', 'msg' => $trade_number);
	}

	function get_app_share_log($uid, $channel) {
		$this->db->from('app_share_log');
		$this->db->where(array('uid' => $uid, 'channel' => $channel));
		$result = $this->db->get()->result_array();
		return $result;
	}

	function add_app_share_log($insert_data) {
		$this->db->insert("app_share_log", $insert_data);
		$id = $this->db->insert_id();
		return $id;
	}

	function check_juice_active($uid) {
		$this->db->select('user_rank');
		$this->db->from('user');
		$this->db->where('id', $uid);
		$user_info = $this->db->get()->row_array();
		$user_rand_setting = $this->config->item('user_rank');
		$active_setting = $user_rand_setting['level'][$user_info['user_rank']]['juice'];
		if ($active_setting['day_num'] > 0 || $active_setting['week_num'] > 0) {
			if ($active_setting['day_num'] > 0) {
				$active_desc = '每天可享受' . $active_setting['day_money'] . '元果汁(果杯)' . $active_setting['day_num'] . '杯';
			}
			if ($active_setting['week_num'] > 0) {
				$active_desc = '每周可享受免费果汁(果杯)' . $active_setting['week_num'] . '杯';
			}
			return array('active_desc' => $active_desc, 'user_rank' => $user_info);
		}
		return false;
	}

	function has_packet($uid) {
//		$this->db->from("tuan");
//		$this->db->where("uid", $uid);
//		$this->db->where("product_id", 20150420);
//		$query = $this->db->get();
//		$result = $query->result_array();
//		$tuan_id = $result[0]['id'];
        $order_info = $this->db->select("id,money")->from('order')->where(array("uid"=>$uid,'pay_parent_id !='=>4,'time >='=>'2015-11-01', 'operation_id <>'=>'5','channel'=>6))->order_by('time', 'desc')->limit(1)->get()->row_array();

        if(empty($order_info)){
            return false;
        }
        $uid = $order_info['uid'];
        $money = $order_info['money'];
        $order_id = $order_info['id'];

        $now_data = date("Y-m-d");
        /*active begin*/
        $now_time = date("Y-m-d H:i:s");
        $active_tag = 'cherry_2015_7_10';
        $active_start_time = "2015-07-10 00:00:00";
        $active_end_time = "2015-07-21 00:00:00";
//        if(!in_array($uid,array("332208","613870","894584","2011111"))){
//            return false;
//        }

        /*优惠券新逻辑 start*/
//        if($money>0){
        $is_send = $this->db->from("red_packets")->where(array(
            'order_id'=>$order_id
        ))->get()->row_array();
        if(!empty($is_send)){
            return $is_send['link_tag'];
        }

        $packet_id = tag_code('hb2015'.microtime().rand(10000, 99999));
        $packet_money = $money;//生成优惠券总额范围
        if($packet_money<15){
            $packet_money = 15;
        }
        $now_time = date('Y-m-d H:i:s');
        $this->db->insert('red_packets',array(
            'uid'=>$uid,
            'total_money'=>$packet_money,
            'left_money'=>$packet_money,
            'time'=>$now_time,
            'order_id'=>$order_id,
            'status'=>1,
            'link_tag'=>$packet_id
        ));
        $this->db->insert_id();
        return $packet_id;
	}

	function add_welfare($insert_data) {
		$this->db->insert("welfare_purchase", $insert_data);
		$id = $this->db->insert_id();
		return $id;
	}

	function user_rank_order_info($uid, $start_time, $end_time) {
		$data = array();
		$data['ordernum'] = 0;
		$data['ordermoney'] = 0;
		//return $this->db->query("SELECT COUNT(1) AS ordernum, SUM(money+use_money_deduction) AS ordermoney FROM ttgy_order WHERE uid={$uid} AND time >= '{$start_time}' AND time <= '{$end_time}' AND operation_id='3' ")->row_array();
		$sql = "select o.id,(o.money+o.use_money_deduction) AS ordermoney,f.final_money as f_money from ttgy_order o left join ttgy_finish_order f on o.id=f.order_id WHERE o.uid={$uid} AND o.time >= '{$start_time}' AND o.time <= '{$end_time}' AND o.operation_id='3'";
		$result = $this->db_master->query($sql)->result_array();
        if($result){
        	$data['ordernum'] = count($result);
        	foreach ($result as $key => $value) {
        		$order_money = 0;
        		if(!is_null($value['f_money'])){
        			$order_money = $value['f_money'];
        		}else{
        			$order_money = $value['ordermoney'];
        		}
        		$data['ordermoney'] = bcadd($data['ordermoney'], $order_money,2);
        	}
        }
        return $data;
	}

	/*
	 * 自动推荐的商品
	 */

	function user_recommend($uid) {
		$this->db->select('product_tags');
		$this->db->from('user_recommend');
		$this->db->where('user_id', $uid);
		$pro_rec_query = $this->db->get();
		$pro_rec_result = $pro_rec_query->row_array();
		return $pro_rec_result;
	}

	/*
	 * 未支付订单数量
	 */

	function show_pay_order_num($uid) {
		$this->db->from('order');
		$this->db->where(array('uid' => $uid, 'order_status' => '1', 'pay_status' => '0', 'operation_id !=' => '5'));
		$query = $this->db->get();
		$pay_num = $query->num_rows();
		return $pay_num;
	}

	/*
	 * 可评论订单数量
	 */

	function can_comment_order_num($uid) {
		$this->db->from('order');
		$this->db->where(array('uid' => $uid, 'order_status' => '1', 'had_comment' => '0', 'time >' => date("Y-m-d", strtotime('-' . $this->can_comment_period))));
		$this->db->where_in('operation_id', array('3', '9'));
		$query = $this->db->get();
		$comment_num = $query->num_rows();
		return $comment_num;
	}

	/*
	 * 所有帐户赠品
	 */

	function getUserGift($uid, $is_rec = false, $region_id = '106092') {
		$this->db->select('active_id,active_type,has_rec,start_time,end_time');
		$this->db->from('user_gifts');
		if ($is_rec) {
			$this->db->where(array('uid' => $uid, 'has_rec' => '0'));
		} else {
			$this->db->where(array('uid' => $uid));
		}
		$query = $this->db->get();
		$result = $query->result_array();
		$trade_gifts = array();
		$market_send_gifts = array();
		foreach ($result as $key => $value) {
			switch ($value['active_type']) {
				case '1':
					$trade_gifts[$value['active_id']]['active_id'] = $value['active_id'];
					$trade_gifts[$value['active_id']]['has_rec'] = $value['has_rec'];
					$trade_gifts[$value['active_id']]['start_time'] = $value['start_time'];
					$trade_gifts[$value['active_id']]['end_time'] = $value['end_time'];
					break;
				case '2':
					$market_send_gifts[$value['active_id']]['active_id'] = $value['active_id'];
					$market_send_gifts[$value['active_id']]['has_rec'] = $value['has_rec'];
					$market_send_gifts[$value['active_id']]['start_time'] = $value['start_time'];
					$market_send_gifts[$value['active_id']]['end_time'] = $value['end_time'];
					break;
				default://todo
					# code...
					break;
			}
		}

		$user_gift_arr = array();
		$product_id_arr = array();
		$i = 0;
		/* 获取充值赠品信息start */
		if (!empty($trade_gifts)) {
			$trade_ids = array();
			foreach ($trade_gifts as $key => $value) {
				$trade_ids[] = $value['active_id'];
			}
			$this->db->select('id,trade_number,money');
			$this->db->from('trade');
			$this->db->where_in('id', $trade_ids);
			$trade_query = $this->db->get();
			$trade_result = $trade_query->result_array();
			$this->load->model('user_gifts_model');
			foreach ($trade_result as $key => $value) {
				$products_array = $this->user_gifts_model->rules_to_charge_data($value['money'], $value['trade_number'], $region_id);
				if (empty($products_array)) {
					continue;
				}
				$product_id_arr[] = $products_array[0];
				$user_gift_arr[$i]['product_id'] = $products_array[0];
				$user_gift_arr[$i]['qty'] = (string) $products_array[1];
				$user_gift_arr[$i]['active_type'] = 1;
				$user_gift_arr[$i]['active_id'] = $value['id'];
				$user_gift_arr[$i]['gift_source'] = '帐户余额充值赠品';
				$user_gift_arr[$i]['has_rec'] = $trade_gifts[$value['id']]['has_rec'];
				$i++;
			}
		}

		/* 获取充值赠品信息end */
		if (!empty($market_send_gifts)) {
			$send_ids = array();
			foreach ($market_send_gifts as $key => $value) {
				$send_ids[] = $value['active_id'];
			}
			$now_time = date("Y-m-d H:i:s");
			$this->db->select('id,product_id,qty,remarks,start,end');
			$this->db->from('gift_send');
			$this->db->where_in('id', $send_ids);
			$this->db->where(array('start <' => $now_time));
			$send_query = $this->db->get();
			$send_result = $send_query->result_array();
			if (!empty($send_result)) {
				foreach ($send_result as $key => $value) {
					$product_id_arr[] = $value['product_id'];
					$user_gift_arr[$i]['product_id'] = $value['product_id'];
					$user_gift_arr[$i]['qty'] = $value['qty'];
					$user_gift_arr[$i]['active_type'] = 2;
					$user_gift_arr[$i]['active_id'] = $value['id'];
					$user_gift_arr[$i]['gift_source'] = $value['remarks'];
					$user_gift_arr[$i]['start_time'] = $market_send_gifts[$value['id']]['start_time'];
					$user_gift_arr[$i]['end_time'] = $market_send_gifts[$value['id']]['end_time'];
					$user_gift_arr[$i]['has_rec'] = $market_send_gifts[$value['id']]['has_rec'];
					$i++;
				}
			}
		}
		/* 获取营销发放赠品信息start */

		/* 获取营销发放赠品信息end */

		if (empty($user_gift_arr) || empty($product_id_arr)) {
			return array();
		}

		/* 组织赠品信息start */
		$data = array();
		$this->db->select('product.id,product_price.id as price_id,product_price.product_no,product_price.volume,product_price.price,product_price.unit,product.product_name,product.thum_photo,product.template_id');
		$this->db->from('product_price');
		$this->db->join('product', 'product.id = product_price.product_id');
		$this->db->where_in('product.id', $product_id_arr);
		$query = $this->db->get();
		$prices_tmp = $query->result_array();
		$prices = array();
		foreach ($prices_tmp as $key => $value) {
			if (!isset($prices[$value['id']])) {
                // 获取产品模板图片
                if ($value['template_id']) {
                    $this->load->model('b2o_product_template_image_model');
                    $templateImages = $this->b2o_product_template_image_model->getTemplateImage($value['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $value['thum_photo'] = $templateImages['main']['thumb'];
                    }
                }

				$prices[$value['id']] = $value;
			}
		}
		foreach ($user_gift_arr as $key => $value) {
			$user_gift_arr[$key]['price_id'] = $prices[$value['product_id']]['price_id'];
			$user_gift_arr[$key]['product_name'] = $prices[$value['product_id']]['product_name'];
			$user_gift_arr[$key]['product_no'] = $prices[$value['product_id']]['product_no'];
			$user_gift_arr[$key]['price'] = $prices[$value['product_id']]['price'];
			$user_gift_arr[$key]['photo'] = PIC_URL . $prices[$value['product_id']]['thum_photo'];
			$user_gift_arr[$key]['gg_name'] = $prices[$value['product_id']]['volume'] . '/' . $prices[$value['product_id']]['unit'];
		}
		/* 组织赠品信息end */
		return $user_gift_arr;
	}

	/*
	 * 用户试吃申请
	 */

	function foretaste_apply($uid) {
		$this->db->from('foretaste_apply');
		$this->db->where(array('uid' => $uid, 'status' => '1', 'has_comment' => '0'));
		$query = $this->db->get();
		$foretaste_num = $query->num_rows();
		return $foretaste_num;
	}

	function getUserBirthday($uid) {
		$this->db->select("birthday");
		$this->db->from("user");
		$this->db->where("id", $uid);
		$query = $this->db->get();
		$birthday = $query->row_array();
		return $birthday['birthday'];
	}

	function setUserEditBirthdayLog($uid, $last_birthday) {
		$insertData = array('uid' => $uid, 'birthday' => $last_birthday, 'time' => time());
		$this->db->insert('user_birthday_log', $insertData);
	}

	function dill_score_new($money, $uid) {
		$base_score = $money;
		$user = $this->db->select('user_rank')->from('user')->where('id', $uid)->get()->row_array();
		$rank_score = $this->cal_rank_score($base_score, $user['user_rank'], $msg);
		$firstbuy_score = $this->checkfirstbuy($base_score, $uid);
		$birthday_score = $this->checkbirthday($base_score, $uid);
		$weekend_score = $this->checkWeekend($base_score, $uid);
		$score = $rank_score + $firstbuy_score + $birthday_score + $weekend_score;
		$score = round($score);
		return $score;
	}

	function checkfirstbuy($base_score, $uid) {
		$sql = "select id from ttgy_order where operation_id<>5 and order_status=1 and uid=" . $uid;
		$query = $this->db->query($sql);
		$result = $query->row_array();
		$firstbuy_score = 0;
		if (empty($result)) {
			$firstbuy_score = $base_score * 2;
		}
		return $firstbuy_score;
	}

	function checkbirthday($base_score, $uid) {
		$sql = "SELECT birthday FROM ttgy_user_birthday_log WHERE uid=" . $uid . " and YEAR(FROM_UNIXTIME(time))=" . date('Y', time()) . " order by time limit 1";
		$query = $this->db->query($sql);
		$result = $query->row_array();
		$birthday_score = 0;
		if (empty($result)) {
			$sql = "SELECT birthday FROM ttgy_user WHERE id=" . $uid;
			$query = $this->db->query($sql);
			$result = $query->row_array();
		}
		if ($result['birthday']) {
			$result['birthday'] = date('m',$result['birthday']);
			if (date('m', time()) == $result['birthday']) {
				$birthday_score = $base_score * 2;
			}
		}
		return $birthday_score;
	}

	function checkWeekend($base_score,$uid){
		return 0; //积分翻倍活动没有达到预期效果，取消
		$weekend_score = 0;
		if((date('w') == 6 || date('w') == 0) && date('d') != 22){
			$now = date('H');
            if( ($now>=10 && $now<12) || ($now>=14 && $now<16) || ($now>=17 && $now<19) || ($now>=21 && $now<23) ){
            	$weekend_score = $base_score * 2;
            }
		}
		return $weekend_score;
	}

	//会员等级升级
	function upgrade_rank($uid,$user_check=false) {
		$sql = "select user_rank,mobile from ttgy_user where id=" . intval($uid);
		$query = $this->db->query($sql);
		$result = $query->row_array();
		$last_rank = $result['user_rank'];
		$mobile = $result['mobile'];

		//$cycle = $this->get_cycle();
		//$cycle += 1;
		$offset = 0;
		$limit = 5000;
		$s_time = date('Y-m-d', strtotime("- 12 month"));
		$to_time = date('Y-m-d H:i:s');
		//$row = $this->db_master->query('SELECT COUNT(1) AS ordernum,SUM(money+use_money_deduction) AS ordermoney FROM ttgy_order WHERE order_status="1" AND operation_id="3" AND time <= "' . $to_time . '" and time >= "' . $s_time . '" AND uid=' . $uid)->row_array();
		$row = $this->user_rank_order_info($uid, $s_time, $to_time);
		$user_rank = $this->upgrade_user_rank($row);

		$this->load->model('subscription_model');
        $rank = $this->subscription_model->userRank($uid);

        if($rank > $user_rank['level_id']){
        	$user_rank['level_id'] = $rank;
        }
		if ($user_rank && $user_rank['level_id'] > $last_rank) {
			$this->db->update('user', array('user_rank' => $user_rank['level_id']), array('id' => $uid, 'user_rank <' => $user_rank['level_id']));
			$data = array();
			$data['uid'] = $uid;
			$data['from_rank'] = $last_rank;
			$data['to_rank'] = $user_rank['level_id'];
			$this->add_rank_log($data);
			if($user_check === true){
				$this->load->library('fdaylog');
				$db_log = $this->load->database('db_log', TRUE);
				$this->fdaylog->add($db_log, 'user_rank_check', $uid . ':' . date('Y-m-d H:i:s'));
			}
			//升級完成發送短信給用戶      add by dengjm  2015-08-27
            switch (intval($user_rank['level_id'])) {
                case 2:
                    $sms_content = "恭喜您升级为V1会员,点击查看会员特权";
                    break;
                case 3:
                    $sms_content = "恭喜您升级为V2会员,点击查看会员特权";
                    break;
                case 4:
                    $sms_content = "恭喜您升级为V3会员,点击查看会员特权";
                    break;
                case 5:
                    $sms_content = "恭喜您升级为V4会员,点击查看会员特权";
                    break;
                case 6:
                    $sms_content = "恭喜您升级为V5会员,谁也阻止不了你继续买水果啦！";
                    break;
                default:
                    break;
            }
            // $this->db->insert("joblist",array("job"=>serialize(array('mobile'=>$mobile,'text'=>$sms_content)), "type"=>0));
            //调用通知start
				$this->load->library("notify");
				$type    = ["sms","app"];
				$target  = [
					["mobile"=>$mobile,"uid"=>$uid]
				];
				$message = ["title"=>"会员等级升级通知", "body"=>$sms_content];

				$params = [
					"source"  => "api",
					"mode"    => "group",
					"type"    => json_encode($type),
					"target"  => json_encode($target),
					"message" => json_encode($message),
				];

				// $this->notify->send($params);
				//调用通知end
            //消息中心
            $this->load->model('msg_model');
            $msg_rank = intval((int)$user_rank['level_id'] - 1);
            $this->msg_model->addMsgAccount($uid,$msg_rank,1);
		}
	}

	function add_rank_log($data) {
		if (empty($data['uid']) || empty($data['from_rank']) || empty($data['to_rank'])) {
			return;
		}
		$insert_data = array();
		$insert_data['uid'] = $data['uid'];
		$insert_data['from_rank'] = $data['from_rank'];
		$insert_data['to_rank'] = $data['to_rank'];
		$insert_data['expire_date'] = date('Y-m-d', strtotime("+ 3 MONTH"));
		$insert_data['time'] = time();
		return $this->db->insert('user_rank_log', $insert_data);
	}

	public function upgrade_user_rank($filter) {
		$rank_list = $this->config->item('user_rank');
		foreach ($rank_list['level'] as $value) {
            $ranklist[$value['level_id']] = $value;
        }
        krsort($ranklist);
		$user_rank = array();
		foreach ($ranklist as $value) {
			if (bccomp($filter['ordernum'], $value['ordernum'],3) != -1  && bccomp($filter['ordermoney'], $value['ordermoney']) != -1) {
				$user_rank = $value;
				break;
			}
		}
		return $user_rank;
	}

	/*
	 * 618樱桃发券卡
	 */

	function cherry_618_mobile($uid) {
		$now_time = date("Y-m-d H:i:s");
		$now_date = date("Y-m-d");
		/* 活动配置start */
		$active_tag = 'yingtao_2015_706';
		$active_product_id = '4070,4797'; //2660

		$ip = $this->getIP();
		$area = $this->getCity($ip);
		switch ($area['area']) {
			case '华南':
				$ruby_card_setting_new=array(
		            '6000'=> array(
		                'card_money' => 10,
		                'card_num' => 10000
		            ),
		            '10000' => array(
		                'card_money' => 20,
		                'card_num' => 3600000
		            ),
		        );

		        $ruby_card_setting_old=array(
		            // '2000' => array(
		            //     'card_money' => 10,
		            //     'card_num' => 3600000
		            // ),
		            // '8900' => array(
		            //     'card_money' => 20,
		            //     'card_num' => 3600000
		            // ),
		            '6000' => array(
		                'card_money' => 10,
		                'card_num' => 1200000
		            ),
		            '10000' => array(
		                'card_money' => 28,
		                'card_num' => 1200000
		            ),
		        );
				break;
			case '华北':
				$ruby_card_setting_new=array(
		            '9500'=> array(
		                'card_money' => 20,
		                'card_num' => 10000
		            ),
		            '9999' => array(
		                'card_money' => 30,
		                'card_num' => 3600000
		            ),
		            '10000' => array(
		                'card_money' => 48,
		                'card_num' => 1200000
		            ),
		        );

		        $ruby_card_setting_old=array(
		            '1500' => array(
		                'card_money' => 10,
		                'card_num' => 3600000
		            ),
		            '9400' => array(
		                'card_money' => 20,
		                'card_num' => 3600000
		            ),
		            '9900' => array(
		                'card_money' => 30,
		                'card_num' => 1200000
		            ),
		            '10000' => array(
		                'card_money' => 48,
		                'card_num' => 1200000
		            ),
		        );
				break;
			default:
				$ruby_card_setting_new=array(
		            '9500'=> array(
		                'card_money' => 20,
		                'card_num' => 10000
		            ),
		            '9999' => array(
		                'card_money' => 30,
		                'card_num' => 3600000
		            ),
		            '10000' => array(
		                'card_money' => 48,
		                'card_num' => 1200000
		            ),
		        );

		        $ruby_card_setting_old=array(
		            '1500' => array(
		                'card_money' => 10,
		                'card_num' => 3600000
		            ),
		            '9400' => array(
		                'card_money' => 20,
		                'card_num' => 3600000
		            ),
		            '9900' => array(
		                'card_money' => 30,
		                'card_num' => 1200000
		            ),
		            '10000' => array(
		                'card_money' => 48,
		                'card_num' => 1200000
		            ),
		        );

				break;
		}


        	// $ruby_card_setting_new=array(
		       //      '9500'=> array(
		       //          'card_money' => 20,
		       //          'card_num' => 10000
		       //      ),
		       //      '9999' => array(
		       //          'card_money' => 30,
		       //          'card_num' => 3600000
		       //      ),
		       //      '10000' => array(
		       //          'card_money' => 48,
		       //          'card_num' => 1200000
		       //      ),
		       //  );

		       //  $ruby_card_setting_old=array(
		       //      '1500' => array(
		       //          'card_money' => 10,
		       //          'card_num' => 3600000
		       //      ),
		       //      '9400' => array(
		       //          'card_money' => 20,
		       //          'card_num' => 3600000
		       //      ),
		       //      '9900' => array(
		       //          'card_money' => 30,
		       //          'card_num' => 1200000
		       //      ),
		       //      '10000' => array(
		       //          'card_money' => 48,
		       //          'card_num' => 1200000
		       //      ),
		       //  );

		$send_card = false;
		$share_remarks = "仅限app购买美国西北樱桃一斤装使用";
		$this->db->trans_begin();
		/* 判断是否含有有效优惠券start */
		$card_number_arr = array();
		$this->db->from('card');
		$this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
		$this->db->order_by('id', 'DESC');
		$card_limit_arr = $this->db->get()->result_array();
		$card_limit_num = count($card_limit_arr);
		if ($card_limit_num > 0) {
//            if($uid == 613870){
//			$card_number_reject = $card_limit_arr[0]['card_number'];
//			$this->db->from('wqbaby_active');
//			$this->db->where(array(
//				'card_number' => $card_number_reject,
//				'is_add' => 0
//			));
//			$is_wqbaby = $this->db->get()->row_array();
//			$lucky = mt_rand(1, 100);
//			$card_money_old = $card_limit_arr[0]['card_money'];
//			if ($card_money_old <= 10) {//!empty($is_wqbaby)&&
//				$this->db->from('order');
//				$this->db->where(array(
//					'order_status' => 1,
//					'operation_id !=' => 5,
//					'uid' => $uid
//				));
//				$is_old = $this->db->count_all_results();
//				$user_status = $is_old > 0 ? 'old' : 'new';
//				$range = $card_money_old <= 5 ? 5 : 10;
//				$arr_confi_card_range = $cherry_card_reject[$user_status][$range];
//				if (!empty($arr_confi_card_range)) {
//					foreach ($arr_confi_card_range as $k => $v) {
//						if ($lucky <= $k) {
//							$card_add = $v;
//							break;
//						}
//					}
//					$card_reject = array('card_money' => $card_money_old + $card_add);
//					$this->db->update("wqbaby_active", array('card_money' => $card_money_old + $card_add, 'is_add' => 1), array('card_number' => $card_number_reject));
//					$this->db->update("card", $card_reject, array('card_number' => $card_number_reject));
//					$this->db->trans_commit();
//					return array('card_add' => $card_add, 'card_money_old' => $card_money_old);
//				} else {
//					return false;
//				}
//			} else {
//				return false;
//			}
////            }else
                return false;
		}
		$send_card = true;
		/* 判断是否含有有效优惠券end */

//        $rd1 = mt_rand(1,10);
////        $this->db->from('card');
////        $this->db->where(array('sendtime' => date("Y-m-d"), 'remarks'=>$share_remarks, 'product_id'=>'4435'));
////        $today_ruby_num = $this->db->get()->num_rows();
//        if($rd1<=7){//&&$today_ruby_num<=33576
            // $active_product_id = '4435';
//        }else{
//            $active_product_id = '4070'; //2660
//        }

		if ($send_card) {
			$share_p_card_number = 'ch706';
			$share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);

//            if($active_product_id == 4435){
//                $send_limit_tag = $this->tag_code($active_product_id.date('Y-m-d'));
//                $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
//                $send_limit_num = $this->db->query($sql)->row_array();
//                if($send_limit_num['num']>33576){
//                    $active_product_id = 4070;
//                }
//                $send_limit_tag_data = array(
//                    'active_tag' => $send_limit_tag
//                );
//                $this->db->insert('card_send_limit', $send_limit_tag_data);
//            }

//            $this->db->from('card');
//            $this->db->where(array('sendtime' => date("Y-m-d"), 'remarks'=>$share_remarks ,'card_money'=>48));
//            $today_48_num = $this->db->get()->num_rows();
			$bingo = rand(1, 1000);

            /* 获取对应金额的优惠券start */
			// $sql = "select count(distinct o.id) as num from ttgy_order as o join ttgy_order_product as p on p.order_id=o.id where o.order_status=1 and o.operation_id!=5 and p.product_id=4070 and o.uid=" . $uid;
			// $buyed_num = $this->db->query($sql)->row_array();
			// $get_times = $buyed_num['num'] % 5 + 1;

			$this->db->select('user_rank');
			$this->db->from('user');
			$this->db->where('id', $uid);
			$user_result = $this->db->get()->row_array();

			$user_rank = (int) $user_result['user_rank'];

//			$this->db->from('card');
//			$this->db->where(array('uid' => $uid, 'card_money' => 48));
//			$card_48_num = $this->db->get()->num_rows();

              foreach ($ruby_card_setting_old as $key => $value) {
                            if ($bingo <= $key) {
                            	if($user_rank<2 && $value['card_money']==48){
                            		$card_money = 20;
                            		$card_num = $value['card_num'];
                            		break;
                            	}
                            	else{
	                                $card_money = $value['card_money'];
	                                $card_num = $value['card_num'];
	                                break;
                                }
                            }
                        }

			/* 获取对应金额的优惠券end */

			/* 优惠券数量保护start */
			$send_limit_tag = $this->tag_code($card_money . $card_active_start_time . $card_active_end_time);
//            $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
//            $send_limit_num = $this->db->query($sql)->row_array();
//            if($send_limit_num['num']>=$card_num){
//                $card_money = 5;
//            }

            // if($card_money == 48){
            //     $send_limit_tag = $this->tag_code($card_money.date('Y-m-d'));
            //     $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
            //     $send_limit_num = $this->db->query($sql)->row_array();
            //     if($send_limit_num['num']>100){
            //         $card_money = 5;
            //     }
            //     $send_limit_tag_data = array(
            //         'active_tag' => $send_limit_tag
            //     );
            //     $this->db->insert('card_send_limit', $send_limit_tag_data);
            // }

//			if ($card_money == 48 && !empty($user_result) && $user_result['user_rank'] < 3) {
//				$card_money = 5;
//			}
//			if ($card_money == 48 && $card_48_num > 0) {
//				$card_money = 5;
//			}
//            if ($card_money ==48 && $today_48_num>100){
//                $card_money = 5;
//            }
			/* 优惠券数量保护end */

			/* 有效期计算start */

				$this->db->select_max('sendtime');
				$this->db->from('card');
				$this->db->where(array('uid' => $uid, 'remarks' =>$share_remarks));
				$max_time = $this->db->get()->row_array();

				if (!empty($max_time['sendtime'])) {
					if ($max_time['sendtime'] >= $now_date) {
						$card_time = date("Y-m-d", strtotime("+1 day"));
						$card_to_date = date("Y-m-d", strtotime("+1 day"));
					} else {
						$card_time = $now_date;
						$card_to_date = $now_date;
					}
				} else {
					$card_time = $now_date;
					$card_to_date = $now_date;
				}

			/* 有效期计算end */

//            /* 指定范围客户优惠券面额变更 begin*/
//            $once_log_tag  = 'cherry_share_20';
//            $this->db->from('wqbaby_active');
//            $this->db->where(array('mobile' => $uid, 'active_tag'=>$once_log_tag));
//            $is_sent = $this->db->get()->num_rows();
//            $uids = require APPPATH.'config/uids.php';
//
//            if(empty($is_sent)&&in_array($uid,$uids)){
//                $card_money = 20;
//                $once_log_data = array(
//                    'mobile' => $uid,
//                    'active_tag' => $once_log_tag,
//                    'card_number' => $share_card_number,
//                    'card_money' => $card_money
//                );
//                $this->db->insert('wqbaby_active',$once_log_data);
//            }
//            /* 指定范围客户优惠券面额变更 end*/


			$channel = serialize(array("2"));
			$card_data = array(
				'uid' => $uid,
				'sendtime' => date("Y-m-d"),
				'card_number' => $share_card_number,
				'card_money' => $card_money,
				'product_id' => $active_product_id,
				'maketing' => '0',
				'is_sent' => '1',
				'restr_good' => '1',
				'remarks' => $share_remarks,
				'time' => $card_time,
				'to_date' => $card_time,//$card_to_date,
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'order_money_limit' => 0,
				'return_jf' => 0,
				// 'black_user_list'=>'',
				'channel' => $channel
			);
			/* 发优惠券start */
			$this->db->insert('card', $card_data);
			/* 发优惠券end */

			/* 活动记录start */
			$send_limit_tag_data = array(
				'active_tag' => $send_limit_tag
			);
			$this->db->insert('card_send_limit', $send_limit_tag_data);
			/* 活动记录end */

			$this->db->from('card');
			$this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
			$card_limit_num2 = $this->db->get()->num_rows();
			if ($card_limit_num2 > 1) {
				$this->db->trans_rollback();
				return false;
			}
			if ($this->db->trans_status() === FALSE) {
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
			}
		}
		return array("card_money"=>$card_money,"yt_name"=>'西北');
	}

	/* 生成唯一短标示码 */

	function tag_code($str) {
		$str = crc32($str);
		$x = sprintf("%u", $str);
		$show = '';
		while ($x > 0) {
			$s = $x % 62;
			if ($s > 35) {
				$s = chr($s + 61);
			} elseif ($s > 9 && $s <= 35) {
				$s = chr($s + 55);
			}
			$show .= $s;
			$x = floor($x / 62);
		}
		return $show;
	}

	function addIP($ip, $uid) {
		$data = array(
			'ip' => $ip,
			'uid' => $uid
		);
		$this->db->insert('cherry_ip', $data);
	}

	function addUserRegIP($ip, $uid, $mobile) {
		$data = array(
			'ip' => $ip,
			'uid' => $uid,
			'mobile' => $mobile
		);
		$this->db->insert('user_register_ip', $data);
	}

	function levelLog($uid, $limit, $offset) {
		$this->db->select('user_rank');
		$this->db->from('user');
		$this->db->where(array('id' => $uid));
		$now_rank = $this->db->get()->row_array();
		$now_rank = $now_rank['user_rank'];
		//$now_rank = $this->get_rank($now_rank);
		$this->db->from('user_rank_log');
		$this->db->where(array('uid' => $uid));
		$this->db->limit($limit, $offset);
		$this->db->order_by('time', 'desc');
		$result = $this->db->get()->result_array();
		$data = array();
		$data['now_rank'] = $now_rank;
		if (empty($result)) {
			$log = array();
			$log['time'] = "2015-05-01";
			$log['expire_date'] = '';
			$type = '2';
			$log['type'] = $type;
			$log['to_rank'] = $now_rank;
			$to_rank = $this->get_rank($now_rank);
			$to_rank_name = $to_rank['name'];
			$log['reason'] = "由于您的订单数量以及订单金额已经满足“" . $to_rank_name . "”的要求，会员等级升级为“" . $to_rank_name . '”。';
			$data['logs'][] = $log;
		} else {
			foreach ($result as $logs) {
				$log = array();
				$log['time'] = date("Y-m-d", $logs['time']);
				$log['expire_date'] = $logs['expire_date'];
				if ($logs['from_rank'] > $logs['to_rank']) {
					$type = '1';  //降级
				} else {
					$type = '2';
				}
				$log['type'] = $type;
				// $from_rank = $logs['from_rank'];
				// $log['from_rank'] = $from_rank;
				$to_rank = $logs['to_rank'];
				$log['to_rank'] = $to_rank;

				$from_rank = $this->get_rank($logs['from_rank']);
				$to_rank = $this->get_rank($logs['to_rank']);
				$from_rank_name = $from_rank['name'];
				$to_rank_name = $to_rank['name'];
				if ($type == 1) {
					$log['reason'] = "由于您的订单数量以及订单金额未满足“" . $from_rank_name . "”的要求，会员等级由“" . $from_rank_name . "”降为“" . $to_rank_name . '”。';
				} else {
					$log['reason'] = "由于您的订单数量以及订单金额已经满足“" . $to_rank_name . "”的要求，会员等级由“" . $from_rank_name . "”升级为“" . $to_rank_name . '”。';
				}
				$data['logs'][] = $log;
			}
		}
		return $data;
	}

	public function checkRedIndicator($uid) {
		$new_guoshi = $this->checkNewGuoshi($uid);
		$new_ucenter = $this->checkNewUCenter($uid);
		return array("user_center" => $new_ucenter, "guoshi" => 0);
	}

	private function checkNewGuoshi($uid) {
		return 0;//todo by lusc
		$this->db->select('last_guoshi_time');
		$this->db->from('user_newinfo');
		$this->db->where('uid', $uid);
		$result = $this->db->get()->row_array();
		$last_guoshi_time = $result['last_guoshi_time'];
		$this->load->model('fruit_articles_model');
		$this->load->model('fruit_comments_model');

		$last_acticles_time = $this->fruit_articles_model->getLastArticleTime();
		$last_acticles_time = date('Y-m-d H:i:s', $last_acticles_time);
		$last_comments_time = $this->fruit_comments_model->getLastCommentTime($uid);
		$last_comments_time = date('Y-m-d H:i:s', $last_comments_time);
		if (($last_acticles_time > $last_guoshi_time ) || ($last_comments_time > $last_guoshi_time )) {
			return 1;
		}
		return 0;
	}

	private function checkNewUCenter($uid) {
		$this->load->model('card_model');
		$newest = $this->card_model->get_user_last_card($uid);
		$this->load->model('user_gifts_model');
		$newest_gift = $this->user_gifts_model->get_user_last_gift($uid);
		$this->db->select('last_ucenter_time');
		$this->db->where('uid', $uid);
		$this->db->from('user_newinfo');
		$result = $this->db->get()->row_array();
		$last_ucenter_time = $result['last_ucenter_time'];
		if ($newest > $last_ucenter_time || $newest_gift > $last_ucenter_time) {
			return 1;
		}
		return 0;
	}

    public function checkNewCard($uid){
    	$this->load->model('card_model');
		$newest = $this->card_model->get_user_last_card($uid);
		$this->db->select('last_card_time');
		$this->db->where('uid', $uid);
		$this->db->from('user_newinfo');
		$result = $this->db->get()->row_array();
		$last_card_time = $result['last_card_time'];
		if($newest > $last_card_time){
			return 1;
		}
        return 0;
    }

    public function checkNewGift($uid){
    	$this->load->model('user_gifts_model');
		$newest_gift = $this->user_gifts_model->get_user_last_gift($uid);
		$this->db->select('last_gift_time');
		$this->db->where('uid', $uid);
		$this->db->from('user_newinfo');
		$result = $this->db->get()->row_array();
		$last_gift_time = $result['last_gift_time'];
		if($newest_gift > $last_gift_time){
			return 1;
		}
        return 0;
    }

    public function checkNewJf($uid){
        $this->load->model('user_jf_model');
        $newest_jf = $this->user_jf_model->get_user_last_jf($uid);
        $this->db->select('last_jf_time');
        $this->db->where('uid', $uid);
        $this->db->from('user_newinfo');
        $result = $this->db->get()->row_array();
        $last_jf_time = $result['last_jf_time'];
        if($newest_jf > $last_jf_time){
            return 1;
        }
        return 0;
    }

	public function cancelRedIndicator($uid, $type) {
		$this->db->from('user_newinfo');
		$this->db->where(array('uid' => $uid));
		$result = $this->db->get()->row_array();
		switch ($type) {
			case 0:  //u_center
				$filed = 'last_ucenter_time';
				break;
			case 1:
				$filed = 'last_guoshi_time';
				break;
			case 2:
			    $filed = 'last_card_time';
			    break;
			case 3:
			    $filed = 'last_gift_time';
			    break;
            case 4:
                $filed = 'last_jf_time';
                break;
			default:
				return false;
				break;
		}
		if (empty($result)) {

			$insert_data['uid'] = $uid;
			$insert_data[$filed] = date('Y-m-d H:i:s');
			$row = $this->db->insert('user_newinfo', $insert_data);
		} else {
			$up_data[$filed] = date('Y-m-d H:i:s');
			$this->db->where(array('uid' => $uid));
			$this->db->update("user_newinfo", $up_data);
			$row = $this->db->affected_rows();
		}
		if ($row) {
			return true;
		}
		return false;
	}

	/*
	*iOS bug connectid regionid 插入
	*/
	function add_connectid_region_id($connect_id,$region_id){
		if(!empty($connect_id) && !empty($region_id)){
			$this->db->from('connectid_regionid');
			$this->db->where('connect_id',$connect_id);
			if($this->db->get()->num_rows()>0){
				$connectid_regionid_data = array(
					'region_id'=>$region_id
				);
				$this->db->where('connect_id',$connect_id);
				$this->db->update('connectid_regionid',$connectid_regionid_data);
			}else{
				$connectid_regionid_data = array(
					'connect_id'=>$connect_id,
					'region_id'=>$region_id
				);
				$this->db->insert('connectid_regionid',$connectid_regionid_data);
			}
		}
	}


    function set_o2o_card($uid) {
        /* 活动配置start */
        $now_time = date("Y-m-d H:i:s");
        $now_date = date('Y-m-d');
        $share_remarks = "仅限天天到家购买越南白心火龙果使用";
        $channel = serialize(array("2"));
        $send_card = false;
        $to_date = date("Y-m-d", strtotime("+7 day"));
        /* 活动配置end */

        if (!empty($uid)) {
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            if ($this->db->get()->num_rows() > 0) {
                return false;
            }else{
                $send_card = true;
            }
        }

        $this->db->trans_begin();

        if ($send_card) {
            $share_p_card_number = 'dr713';

            /* 获取对应金额的优惠券end */
            /* 有效期计算start */
            $pro_arr = array(
                array(
                    'card_money'=>7,
                    'product_id'=>'4771',
                    'restr_good' => '1',
                    'maketing'=>1,
                    'remarks'=>'仅限天天到家购买越南白心火龙果使用',
                    'time'=>$now_date,
                    'to_date'=>$to_date,
                    'channel'=>$channel
                ),
                array(
                    'card_money'=>3,
                    'product_id'=>'',
                    'restr_good' => '0',
                    'maketing'=>1,
                    'remarks'=>'天天到家通用优惠券',
                    'time'=>$now_date,
                    'to_date'=>$to_date,
                    'channel'=>$channel
                ),
            );

            foreach($pro_arr as $v){
                $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
                $card_data = array(
                    'uid' => '',
                    'sendtime' => date("Y-m-d"),
                    'card_number' => $share_card_number,
                    'card_money' => $v['card_money'],
                    'product_id' => $v['product_id'],
                    'maketing' => $v['maketing'], //o2o优惠券
                    'is_sent' => '',
                    'restr_good' => $v['restr_good'],
                    'remarks' => $v['remarks'],
                    'time' => $v['time'],
                    'to_date' => $v['to_date'],
                    'can_use_onemore_time' => 'false',
                    'can_sales_more_times' => 'false',
                    'card_discount' => 1,
                    'order_money_limit' => 0,
                    'return_jf' => 0,
                    // 'black_user_list'=>'',
                    'channel' => $v['channel']
                );
                /* 发优惠券start */
                if (!empty($uid)) {
                    $card_data['uid'] = $uid;
                    $card_data['is_sent'] = '1';
                }
                $this->db->insert('card', $card_data);
                /* 发优惠券end */
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }

    function checkUserFreeze($mobile){
    	$this->db->select('id');
    	$this->db->from('user_freeze');
    	$this->db->where('mobile',$mobile);
    	$result = $this->db->get()->row_array();
    	if(empty($result)){
    		return false;
    	}else{
    		return true;
    	}
    }

	public function set_banana1_card($uid, $device_id) {
		/*
		 * 提交到card
		 */
		$deviceTag = 'o2o_new76';
		$share_p_card_number = 'o2o';
		$share_remarks = "天天到家-专属优惠券";
		$cardNumber = $share_p_card_number . $this->rand_card_number($share_p_card_number);
		$date = date("Y-m-d", time());
		$channel = '';
//		$this->db->from('card');
//		$this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
//		$result = $this->db->get()->row_array();
		/* 查询active_limit 判断该设备是否领取过  start */
		$deviceCount = $this->db->from('active_limit')->where("(uid='{$uid}' or device_code='{$device_id}') and active_tag='{$deviceTag}'")->count_all_results();
		/* 查询active_limit 判断该设备是否领取过  end */
//		if (empty($result) && $deviceCount == 0) {
		if ($deviceCount == 0) {
			/* 添加设备号   start */
			$deviceData = array(
				'uid' => $uid,
				'device_code' => $device_id,
				'active_tag' => $deviceTag
			);
			$this->db->insert("active_limit", $deviceData);
			/* 添加设备号   start */
			/* 添加优惠券   start */
			$cardData = array(
				'uid' => $uid,
				'sendtime' => $date,
				'card_number' => $cardNumber,
				'card_money' => "3",
				'product_id' => '',
				'maketing' => '0',
				'is_sent' => 1,
				'restr_good' => '0',
				'remarks' => $share_remarks,
				'time' => $date,
				'to_date' => date("Y-m-d", strtotime("+7 day")),
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'return_jf' => '',
				'black_user_list' => '',
				'channel' => $channel
			);
			$this->db->insert('card', $cardData);
			/* 添加优惠券   start */
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 获得userShareActive参数type
	 */
	public function getUserType($order_name) {
		$where = array('order_name' => $order_name, 'operation_id <>' => '5');
		$order = $this->db->select('order_type,id,uid')->from('order')->where($where)->get()->row_array();
		$order_type = $order['order_type'];

		$orderProductWhere = array('order_id' => $order['id'], 'product_id' => '4582'); // 4136
		$order_product = $this->db->from('order_product')->where($orderProductWhere)->count_all_results();
		if ($order_product > 0 && ($order_type == 3 || $order_type == 4)) {
			$share_p_card_number = 'o2o';
			$share_remarks = "10元香蕉优惠券（用于购买38元/10根菲律宾香蕉）";
			$cardNumber = $share_p_card_number . $this->rand_card_number($share_p_card_number);
			$date = date("Y-m-d", time());
			$channel = '';
			$cardData = array(
				'uid' => $order['uid'],
				'sendtime' => $date,
				'card_number' => $cardNumber,
				'card_money' => "10",
				'product_id' => '3770',
				'maketing' => '0',
				'is_sent' => 1,
				'restr_good' => '1',
				'remarks' => $share_remarks,
				'time' => $date,
				'to_date' => '2015-07-15', //date("Y-m-d",strtotime("+3day")),
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'return_jf' => '',
				'black_user_list' => '',
				'channel' => $channel
			);
			$this->db->insert('card', $cardData);
			return 4;
		}
		return 1;
	}

	private  function getIP(){
	    static $realip;
	    if (isset($_SERVER)){
	        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
	            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
	            $realip = $_SERVER["HTTP_CLIENT_IP"];
	        } else {
	            $realip = $_SERVER["REMOTE_ADDR"];
	        }
	    } else {
	        if (getenv("HTTP_X_FORWARDED_FOR")){
	            $realip = getenv("HTTP_X_FORWARDED_FOR");
	        } else if (getenv("HTTP_CLIENT_IP")) {
	            $realip = getenv("HTTP_CLIENT_IP");
	        } else {
	            $realip = getenv("REMOTE_ADDR");
	        }
	    }

	 if (false !== strpos($realip, ',')){
	     $realip = reset(explode(',', $realip));
	     return $realip;
	    }
	    return $realip;
	}

	function getCity($ip){
		$url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
		$ip=json_decode(file_get_contents($url));
		if((string)$ip->code=='1'){
		  return false;
		  }
		  $data = (array)$ip->data;
		return $data;
	}

    /*w
     * 获取下单优惠券
     */
    public function getRedPacket($order_name){
        $order_info = $this->db->select("id,uid,money")->from('order')->where(array('order_name'=>$order_name, 'order_type'=>1, 'operation_id <>'=>'5'))->get()->row_array();
		if(empty($order_info)){
			return false;
		}
        $uid = $order_info['uid'];
        $money = $order_info['money'];
        $order_id = $order_info['id'];
        $now_data = date("Y-m-d");
        /*active begin*/
        $now_time = date("Y-m-d H:i:s");
        $active_tag = 'cherry_2015_7_10';
        $active_start_time = "2015-07-10 00:00:00";
        $active_end_time = "2015-07-21 00:00:00";
//        if(!in_array($uid,array("332208","613870","894584","2011111","119653"))){
//            return 1;
//        }

        /*优惠券新逻辑 start*/
//        if($money>0){
            $is_send = $this->db->from("red_packets")->where(array(
                'order_id'=>$order_id
            ))->get()->row_array();
            if(!empty($is_send)){
                return false;
            }

            $packet_id = tag_code('hb2015'.microtime().rand(10000, 99999));
            $packet_money = $money;//生成优惠券总额范围
            if($packet_money<15){
                $packet_money = 15;
            }
            $now_time = date('Y-m-d H:i:s');
            $this->db->insert('red_packets',array(
                'uid'=>$uid,
                'total_money'=>$packet_money,
                'left_money'=>$packet_money,
                'time'=>$now_time,
                'order_id'=>$order_id,
                'status'=>1,
                'link_tag'=>$packet_id
            ));
            $this->db->insert_id();
            return $packet_id;
//            $black_list ='';// $active_product_id;
//            $p_card_number = 'hb';
//            $card_number = $p_card_number.$this->rand_card_number($p_card_number);
//            $time = date("Y-m-d");
//            $to_date = date("Y-m-d",strtotime("+7day"));
//            /*优惠券优惠券生成设置end*/
//            $card_data = array(
//                'uid'=>$uid,
//                'sendtime'=>date("Y-m-d",time()),
//                'card_number'=>$card_number,
//                'card_money'=>$my_packet,
//                'product_id'=>'',
//                'maketing'=>'0',
//                'is_sent'=>'1',
//                'restr_good'=>'0',
//                'remarks'=>"天天果园优惠券",
//                'time'=>$time,
//                'to_date'=>$to_date,
//                'can_use_onemore_time'=>'false',
//                'can_sales_more_times'=>'false',
//                'card_discount'=>1,
//                'order_money_limit'=>'',//100
//                'black_list'=>$black_list,
//                'channel'=>serialize(array("2"))//serialize(array("2"))
//            );
//            $this->db->insert('card',$card_data);
//
//            $this->db->insert('red_packets_log',array(
//                'uid'=>$uid,
//                'money'=>$my_packet,
//                'time'=>$now_time,
//                'packet_id'=>$packet_id,
//                'card_number'=>$card_number,
//                'mobile'=>''
//            ));
//        }else{
//            return 1;
//        }
        /*优惠券新逻辑 end*/

    }

    public function redPacketLink($order_name){
        $order_info = $this->db->select("id,uid,money")->from('order')->where(array('order_name'=>$order_name, 'order_type'=>1, 'operation_id <>'=>'5'))->get()->row_array();
        if(empty($order_info)){
                return false;
        }

        $uid = $order_info['uid'];
        $money = $order_info['money'];
        $order_id = $order_info['id'];

        /*优惠券新逻辑 start*/
        $is_send = $this->db->from("red_packets")->where(array(
            'order_id'=>$order_id
        ))->get()->row_array();
        if(!empty($is_send)){
            return false;
        }

        $packet_id = tag_code('hb201611'.microtime().rand(10000, 99999));
//        $packet_money = $money;//生成优惠券总额范围
//        if($packet_money<15){
//            $packet_money = 15;
//        }
        $now_time = date('Y-m-d H:i:s');
        $this->db->insert('red_packets',array(
            'uid'=>$uid,
            'total_money'=>$money,
            'left_money'=>0,
            'time'=>$now_time,
            'order_id'=>$order_id,
            'status'=>1,
            'link_tag'=>$packet_id
        ));
        $this->db->insert_id();
        return $packet_id;

    }


	//发优惠券
	public function sendRedPacket($uid){
		$this->db->select('total_money,left_money,link_tag');
		$this->db->from('red_packets');
		$this->db->where(array(
			"uid"=>$uid,
			"status"=>1
		))->order_by('id','desc');
		$result = $this->db->get()->row_array();

		$link_tag = $result['link_tag'];
		if($result['left_money']=='0'||empty($result)){
			return false;
		}
		$this->db->trans_begin();
		$this->db->select('mobile,user_rank');
		$this->db->from('user');
		$this->db->where('id', $uid);
		$user_result = $this->db->get()->row_array();
		$remarks = "全民派送优惠券（天天到家商品除外）";
		$active_tag = "hb1026";
		$now_date = date("Y-m-d");

		$this->db->from('card');
		$this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $remarks));
		if ($this->db->get()->num_rows() > 0) {
			return false;
		}

		$is_sent = 1;
		$is_new = 'old';
		$card_money_arr = array(
			'new'=>array(
				'25'=>array(
					'money'=>10,
					'limit'=>0
				),
				'55'=>array(
					'money'=>15,
					'limit'=>108
				),
				'100'=>array(
					'money'=>20,
					'limit'=>138
				)
			),
			'old'=>array(
				'40'=>array(
					'money'=>15,
					'limit'=>158
				),
				'100'=>array(
					'money'=>20,
					'limit'=>208
				)
			),
		);

		$card_limit_arr = array(
			'new'=>array(
				'10'=>array(
					'limit'=>0
				),
				'15'=>array(
					'limit'=>108
				),
				'20'=>array(
					'limit'=>138
				),
			),
			'old'=>array(
				'10'=>array(
					'limit'=>0
				),
				'15'=>array(
					'limit'=>158
				),
				'20'=>array(
					'limit'=>208
				),
			),
		);
		$bingo = mt_rand(0,100);
		foreach ($card_money_arr[$is_new] as $key => $value) {
			if ($bingo <= $key) {
				$card_money = $value['money'];
				$card_limit = $value['limit'];
				break;
			}
		}

		if($result['left_money']<$card_money){
			$card_money = $result['left_money'];
			foreach ($card_limit_arr[$is_new] as $key => $value) {
				if ($card_money <= $key) {
					$card_limit = $value['limit'];
					break;
				}
			}
		}

		$black_list ='';// $active_product_id;
		$p_card_number = 'hb';
		$card_number = $p_card_number.$this->rand_card_number($p_card_number);
		$time = date("Y-m-d");
		$to_date = date("Y-m-d",strtotime("+5day"));
		/*优惠券优惠券生成设置end*/
		$card_data = array(
			'uid'=>$uid,
			'sendtime'=>date("Y-m-d",time()),
			'card_number'=>$card_number,
			'card_money'=>$card_money,
			'product_id'=>'',
			'maketing'=>'0',
			'is_sent'=>$is_sent,
			'restr_good'=>'0',
			'remarks'=>$remarks,
			'time'=>$time,
			'to_date'=>$to_date,
			'can_use_onemore_time'=>'false',
			'can_sales_more_times'=>'false',
			'card_discount'=>1,
			'order_money_limit'=>$card_limit,//100
			'black_list'=>$black_list,
			'channel'=>serialize(array("2"))//serialize(array("2"))
		);
		$this->db->insert('card',$card_data);

		$this->db->update('red_packets',array("left_money"=>($result['left_money']-$card_money)),"link_tag = '".$link_tag."'");

		$active_data = array(
			'mobile' => $user_result['mobile'],
			'card_number' => $card_number,
			'active_tag' => $active_tag,
			'link_tag' => $link_tag,
			'description' => '',
			'card_money' => $card_money
		);
		$this->db->insert('wqbaby_active', $active_data);

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return false;
			exit;
		} else {
			$this->db->trans_commit();
			return array("result"=>"succ","card_money"=>$card_money);
		}
	}

    function getFpz717($uid){
        $active_tag='717fanfan';
        $date = date('Y-m-d');
        $gift_config = $this->randGift($date);
        if($gift_config == false){
            return false;
        }
        $active_ids = $activeId2productId = array();
        foreach($gift_config as $value){
            $active_ids[] = $value['active_id'];
            $activeId2productId[$value['active_id']] = $value['product_id'];
        }

        $this->db->from('user_gifts')->where(array(
            'uid'=>$uid,
            'has_rec'=>0,
            'active_type' => 2,
        ))->where_in('active_id',$active_ids);
        $today_can_get = $this->db->get()->row_array();
//var_dump($today_can_get);
//        echo $this->db->last_query();exit;

        $userinfo = $this->db->from('user')->where('id',$uid)->get()->row_array();

        if(empty($today_can_get)) {
            $is_have = $this->db->from('wqbaby_active')->where(array(
                'mobile' => $userinfo['mobile'],
                'active_tag' => $active_tag,
                'active_type' => 3,
                'is_add' => 0
            ))->where_in('card_number', $active_ids)->order_by('id', 'desc')->get()->row_array();

            if (!empty($is_have)) {
                $this->db->trans_begin();
                $gift_send = $this->db->select('*')->from('gift_send')->where('id', $is_have['card_number'])->get()->row_array();
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
                /* 优惠券设定start */
                $share_card_data = array(
                    'uid' => $uid,
                    'active_id' => $is_have['card_number'],
                    'active_type' => 2,
                    'has_rec' => 0,
                    'start_time'=>$gift_start_time,
	                'end_time'=>$gift_end_time,
                );
                /* 优惠券设定end */
                $this->db->insert('user_gifts', $share_card_data);
                $this->db->update('wqbaby_active',array(
                    'is_add'=>1
                ),array("id"=>$is_have['id']));
                $user_gift_id = $this->db->insert_id();
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    $retuan_arr['msg'] = '果园君小憩一会儿~';
                } else {
                    $this->db->trans_commit();
                    $product_id = $activeId2productId[$is_have['card_number']];
                    $product_info = $this->db->select('product_name')->from("product")->where('id', $product_id)->get()->row_array();
                    $gift_product_name = $product_info['product_name'];
                    $retuan_arr['msg'] = $gift_product_name . '已放入您的账户咯，当天内领取有效，当赠品被领取后还可以继续翻牌领赠品';
                }
            }else{
                $retuan_arr['msg'] = '果园君小憩一会儿~';
            }
        }else{
            $retuan_arr['msg'] = '您的账户内已经存在1件礼品咯，偷偷告诉你下单后还可以再来翻哦~';
        }
        return $retuan_arr;
    }

    private function randGift($date){
        if($date<'2015-07-19')
            $date = '2015-07-19';
        switch($date){
            case '2015-07-19':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'b477h3',
                        'active_id'=>750
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'3EqKb2',
                        'active_id'=>751
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'ebhbn3',
                        'active_id'=>752
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'wpnOk',
                        'active_id'=>753
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'8i7eK2',
                        'active_id'=>754
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'wZJnz',
                        'active_id'=>755
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-20':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'1GbMt2',
                        'active_id'=>757
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'W381u1',
                        'active_id'=>758
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'Wp0G51',
                        'active_id'=>759
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'b66ek2',
                        'active_id'=>760
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'j6XOd4',
                        'active_id'=>761
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'Vzuk7',
                        'active_id'=>762
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-21':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'kBjNo',
                        'active_id'=>763
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'ZQn3H',
                        'active_id'=>764
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'m7rvb4',
                        'active_id'=>765
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'NVUaD3',
                        'active_id'=>766
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'5b07N',
                        'active_id'=>767
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'BO5NZ4',
                        'active_id'=>768
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-22':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'eUZ0U',
                        'active_id'=>769
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'N2mf72',
                        'active_id'=>770
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'nfXwm1',
                        'active_id'=>771
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'mLH8G2',
                        'active_id'=>772
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'dZf7P3',
                        'active_id'=>773
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'IHCYi2',
                        'active_id'=>775
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-23':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'8wRS',
                        'active_id'=>776
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'w2Bam',
                        'active_id'=>777
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'Agn1b',
                        'active_id'=>778
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'HDEbH2',
                        'active_id'=>779
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'TVcFW4',
                        'active_id'=>780
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'1hvIN3',
                        'active_id'=>781
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-24':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'XA7ZG1',
                        'active_id'=>782
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'8WoXW4',
                        'active_id'=>783
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'q5JQH',
                        'active_id'=>784
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'cgl7C4',
                        'active_id'=>785
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'4UUJB',
                        'active_id'=>786
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'bvJ2T4',
                        'active_id'=>787
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-25':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'I20W11',
                        'active_id'=>788
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'4lyET3',
                        'active_id'=>789
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'YAEif4',
                        'active_id'=>790
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'DxQjH4',
                        'active_id'=>791
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'KQ6BI',
                        'active_id'=>792
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'QVjW73',
                        'active_id'=>793
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-26':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'SbVgw2',
                        'active_id'=>794
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'3Bqav',
                        'active_id'=>795
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'e2ZwB4',
                        'active_id'=>796
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'7ENsC1',
                        'active_id'=>797
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'CyPIg4',
                        'active_id'=>798
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'h5D7L4',
                        'active_id'=>799
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-27':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'MhJPX4',
                        'active_id'=>800
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'A0jQo',
                        'active_id'=>801
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'UpyRn1',
                        'active_id'=>802
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'jcKPj',
                        'active_id'=>803
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'mX7lY',
                        'active_id'=>804
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'cieHS4',
                        'active_id'=>805
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-28':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'aQMEU1',
                        'active_id'=>806
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'g7OPz1',
                        'active_id'=>807
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'RbJTY1',
                        'active_id'=>808
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'3Q1b62',
                        'active_id'=>809
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'tx81u3',
                        'active_id'=>810
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'uYQhf4',
                        'active_id'=>811
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-29':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'DkIXH1',
                        'active_id'=>812
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'y5fYA',
                        'active_id'=>813
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'wM2zQ1',
                        'active_id'=>814
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'wpEfo2',
                        'active_id'=>815
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'GgAWN3',
                        'active_id'=>816
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'xxpW31',
                        'active_id'=>817
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-30':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'1MAUK2',
                        'active_id'=>818
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'I8ZNx',
                        'active_id'=>819
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'C7pqk1',
                        'active_id'=>820
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'yexBU4',
                        'active_id'=>821
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'cL9R23',
                        'active_id'=>822
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'elrXp3',
                        'active_id'=>823
                    )
                );
                return $gift_arr;
                break;
            case '2015-07-31':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'4820',
                        'tag'=>'86HUU3',
                        'active_id'=>824
                    ),
                    '40'=>array(
                        'product_id'=>'4171',
                        'tag'=>'YwXuN4',
                        'active_id'=>825
                    ),
                    '55'=>array(
                        'product_id'=>'4821',
                        'tag'=>'6Jqop3',
                        'active_id'=>826
                    ),
                    '70'=>array(
                        'product_id'=>'4822',
                        'tag'=>'Chw823',
                        'active_id'=>827
                    ),
                    '85'=>array(
                        'product_id'=>'4742',
                        'tag'=>'Ld9QZ4',
                        'active_id'=>828
                    ),
                    '100'=>array(
                        'product_id'=>'4368',
                        'tag'=>'VaYeP1',
                        'active_id'=>829
                    )
                );
                return $gift_arr;
                break;

            default:return false;
        }
    }

//	private function randGift801($date){
//		if($date<'2015-08-01')
//			$date = '2015-08-01';
//		switch($date){
//			case '2015-08-01':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'c6pWB1',
//						'active_id'=>856
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'Q7kIx2',
//						'active_id'=>857
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'2lZeQ3',
//						'active_id'=>858
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'XgNyD3',
//						'active_id'=>859
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'jJeWb3',
//						'active_id'=>860
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'MvOwF',
//						'active_id'=>861
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-02':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'yPJMX',
//						'active_id'=>862
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'irhpk1',
//						'active_id'=>863
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'hSzgk',
//						'active_id'=>864
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'bFnID2',
//						'active_id'=>865
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'Fj0cR2',
//						'active_id'=>866
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'difTy2',
//						'active_id'=>867
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-03':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'lnI4I4',
//						'active_id'=>868
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'zPx6X3',
//						'active_id'=>869
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'2rfHE1',
//						'active_id'=>870
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'zOqu7',
//						'active_id'=>871
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'ojUCe4',
//						'active_id'=>872
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'tO5UH4',
//						'active_id'=>873
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-04':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'15KRr',
//						'active_id'=>874
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'upQRE2',
//						'active_id'=>875
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'O7la01',
//						'active_id'=>876
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'LteOP3',
//						'active_id'=>877
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'5rfeI4',
//						'active_id'=>878
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'FAK2c2',
//						'active_id'=>879
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-05':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'JZjWY1',
//						'active_id'=>880
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'WPsLW4',
//						'active_id'=>881
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'8lVOH2',
//						'active_id'=>882
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'tVT3w2',
//						'active_id'=>883
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'4ajOG1',
//						'active_id'=>884
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'ByrsU3',
//						'active_id'=>885
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-06':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'hn8hL3',
//						'active_id'=>886
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'xx5GD4',
//						'active_id'=>887
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'TtsCV',
//						'active_id'=>888
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'76JHi2',
//						'active_id'=>889
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'IBGtA',
//						'active_id'=>890
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'diKVh1',
//						'active_id'=>891
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-07':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'JqzB21',
//						'active_id'=>892
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'xxTSI',
//						'active_id'=>893
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'ItRBB2',
//						'active_id'=>894
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'VmV9b4',
//						'active_id'=>895
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'YteRl2',
//						'active_id'=>896
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'Ap6iG3',
//						'active_id'=>897
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-08':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'fjz2N',
//						'active_id'=>898
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'ynmXI3',
//						'active_id'=>899
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'eeV1X3',
//						'active_id'=>900
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'YQeYZ2',
//						'active_id'=>901
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'X8Rqj',
//						'active_id'=>902
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'RLjwx2',
//						'active_id'=>903
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-09':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'rv6BY',
//						'active_id'=>904
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'Od3S62',
//						'active_id'=>905
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'ANcat3',
//						'active_id'=>906
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'FiV0Y4',
//						'active_id'=>907
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'70Kt32',
//						'active_id'=>908
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'Mdf8g2',
//						'active_id'=>909
//					)
//				);
//				return $gift_arr;
//				break;
//			case '2015-08-10':
//				$gift_arr = array(
//					'20'=>array(
//						'product_id'=>'5021',
//						'tag'=>'rJzc9',
//						'active_id'=>910
//					),
//					'40'=>array(
//						'product_id'=>'5024',
//						'tag'=>'UME961',
//						'active_id'=>911
//					),
//					'55'=>array(
//						'product_id'=>'5025',
//						'tag'=>'DCysI',
//						'active_id'=>912
//					),
//					'70'=>array(
//						'product_id'=>'5006',
//						'tag'=>'K0b5U3',
//						'active_id'=>913
//					),
//					'85'=>array(
//						'product_id'=>'5007',
//						'tag'=>'njgWZ2',
//						'active_id'=>914
//					),
//					'100'=>array(
//						'product_id'=>'5008',
//						'tag'=>'MIARe4',
//						'active_id'=>915
//					)
//				);
//				return $gift_arr;
//				break;
//
//
//			default:return false;
//		}
//	}
	private function randGift801($date){
        if($date<'2015-12-31')
            $date = '2015-12-31';
        switch($date){
            case '2015-12-31':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'TIOke3',
                        'active_id'=>1450
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'0PVvs3',
                        'active_id'=>1451
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'14UXL1',
                        'active_id'=>1452
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'rbTEJ',
                        'active_id'=>1453
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'BdV5M',
                        'active_id'=>1454
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-01':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'T9Mw52',
                        'active_id'=>1455
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'9HYzV1',
                        'active_id'=>1456
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'Ejfck',
                        'active_id'=>1457
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'KmbHW1',
                        'active_id'=>1458
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'lIn6n1',
                        'active_id'=>1459
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-02':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'Nk3V34',
                        'active_id'=>1460
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'NFdcr1',
                        'active_id'=>1461
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'U83Pk1',
                        'active_id'=>1462
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'7rAuo',
                        'active_id'=>1463
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'3hXUt2',
                        'active_id'=>1464
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-03':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'MtQCD1',
                        'active_id'=>1466
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'Bh509',
                        'active_id'=>1467
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'ETwP22',
                        'active_id'=>1468
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'GLg3H2',
                        'active_id'=>1469
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'3YhQc3',
                        'active_id'=>1470
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-04':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'E69nM3',
                        'active_id'=>1471
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'cj6IQ3',
                        'active_id'=>1472
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'CiPpa2',
                        'active_id'=>1473
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'UnSAJ2',
                        'active_id'=>1474
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'05mUV1',
                        'active_id'=>1475
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-05':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'tiwxS1',
                        'active_id'=>1476
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'hRJ0Z3',
                        'active_id'=>1477
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'J3wM33',
                        'active_id'=>1478
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'NcHrk1',
                        'active_id'=>1479
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'zyFdt',
                        'active_id'=>1480
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-06':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'rkJ0e2',
                        'active_id'=>1481
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'Pkynf1',
                        'active_id'=>1482
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'Tc6lF1',
                        'active_id'=>1483
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'sz7jg3',
                        'active_id'=>1484
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'obdae4',
                        'active_id'=>1485
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-07':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'PmZ7E3',
                        'active_id'=>1486
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'Xk8qH3',
                        'active_id'=>1487
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'twQhW2',
                        'active_id'=>1488
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'0dC08',
                        'active_id'=>1489
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'AIYy83',
                        'active_id'=>1490
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-08':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'FjJsX2',
                        'active_id'=>1491
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'eO4Hn',
                        'active_id'=>1492
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'AQzd11',
                        'active_id'=>1493
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'5OTp01',
                        'active_id'=>1494
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'9VC9f',
                        'active_id'=>1495
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-09':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'VsKJ74',
                        'active_id'=>1496
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'osyK04',
                        'active_id'=>1497
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'d40bb1',
                        'active_id'=>1498
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'ePJcc4',
                        'active_id'=>1499
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'wsHNM4',
                        'active_id'=>1500
                    )
                );
                return $gift_arr;
                break;
            case '2016-01-10':
                $gift_arr = array(
                    '20'=>array(
                        'product_id'=>'7953',
                        'tag'=>'lDwPq2',
                        'active_id'=>1501
                    ),
                    '40'=>array(
                        'product_id'=>'7954',
                        'tag'=>'b9rSU2',
                        'active_id'=>1502
                    ),
                    '60'=>array(
                        'product_id'=>'7955',
                        'tag'=>'2CRIA4',
                        'active_id'=>1503
                    ),
                    '80'=>array(
                        'product_id'=>'7956',
                        'tag'=>'URxaO4',
                        'active_id'=>1504
                    ),
                    '100'=>array(
                        'product_id'=>'7957',
                        'tag'=>'A3Kw03',
                        'active_id'=>1505
                    )
                );
                return $gift_arr;
                break;

			default:return false;
		}
	}

	function getFpz801($uid){
		$active_tag='101guagua';
		$date = date('Y-m-d');
		$gift_config = $this->randGift801($date);
		if($gift_config == false){
			return false;
		}
		$active_ids = $activeId2productId = array();
		foreach($gift_config as $value){
			$active_ids[] = $value['active_id'];
			$activeId2productId[$value['active_id']] = $value['product_id'];
		}

		$this->db->from('user_gifts')->where(array(
			'uid'=>$uid,
			'has_rec'=>0,
			'active_type' => 2,
		))->where_in('active_id',$active_ids);
		$today_can_get = $this->db->get()->row_array();
		$userinfo = $this->db->from('user')->where('id',$uid)->get()->row_array();

		if(empty($today_can_get)) {
			$is_have = $this->db->from('wqbaby_active')->where(array(
				'mobile' => $userinfo['mobile'],
				'active_tag' => $active_tag,
				'active_type' => 3,
				'is_add' => 0
			))->where_in('card_number', $active_ids)->order_by('id', 'desc')->get()->row_array();

			if (!empty($is_have)) {
				$this->db->trans_begin();
				$gift_send = $this->db->select('*')->from('gift_send')->where('id', $is_have['card_number'])->get()->row_array();
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
				/* 优惠券设定start */
				$share_card_data = array(
					'uid' => $uid,
					'active_id' => $is_have['card_number'],
					'active_type' => 2,
					'has_rec' => 0,
					'start_time'=>$gift_start_time,
	                'end_time'=>$gift_end_time,
				);
				/* 优惠券设定end */
				$this->db->insert('user_gifts', $share_card_data);
				$this->db->update('wqbaby_active',array(
					'is_add'=>1
				),array("id"=>$is_have['id']));
				$user_gift_id = $this->db->insert_id();
				if ($this->db->trans_status() === FALSE) {
					$this->db->trans_rollback();
					$retuan_arr['msg'] = '果园君小憩一会儿~';
				} else {
					$this->db->trans_commit();
					$product_id = $activeId2productId[$is_have['card_number']];
					$product_info = $this->db->select('product_name')->from("product")->where('id', $product_id)->get()->row_array();
					$gift_product_name = $product_info['product_name'];
					$retuan_arr['msg'] =  '太棒辣！免费水果'.$gift_product_name.'已经到您的账户中，点击我的果园—>我的赠品看看吧~';
				}
			}else{
				$retuan_arr['msg'] = '果园君小憩一会儿~';
			}
		}else{
			$retuan_arr['msg'] = '您的账户中已经存在一款免费水果，偷偷告诉你，下单带走后就能再来刮啦~';
		}
		return $retuan_arr;
	}

    /*
	 * 618樱桃发券卡
	 */

    function kiwi_728_mobile($uid) {
        $now_time = date("Y-m-d H:i:s");
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_tag = 'apple_2015_91';
        $active_product_id = '5861'; //2660

        $ip = $this->getIP();
        $area = $this->getCity($ip);
        switch ($area['area']) {
            default:
				$ruby_card_setting_new=array(
					'60000'=> array(
						'card_money' => 20,
						'card_num' => 10000
					),
					'100000'=> array(
						'card_money' => 30,
						'card_num' => 10000
					),
				);

				$ruby_card_setting_old=array(
					'84500' => array(
						'card_money' => 20,
						'card_num' => 3600000
					),
					'99500' => array(
						'card_money' => 30,
						'card_num' => 1200000
					),
					'100000' => array(
						'card_money' => 48,
						'card_num' => 1200000
					),
				);

                break;
        }

        $send_card = false;
        $share_remarks = "仅限购买佳沛新西兰绿奇异果（巨无霸）6个装";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $card_number_arr = array();
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
//            if($uid == 613870){
//			$card_number_reject = $card_limit_arr[0]['card_number'];
//			$this->db->from('wqbaby_active');
//			$this->db->where(array(
//				'card_number' => $card_number_reject,
//				'is_add' => 0
//			));
//			$is_wqbaby = $this->db->get()->row_array();
//			$lucky = mt_rand(1, 100);
//			$card_money_old = $card_limit_arr[0]['card_money'];
//			if ($card_money_old <= 10) {//!empty($is_wqbaby)&&
//				$this->db->from('order');
//				$this->db->where(array(
//					'order_status' => 1,
//					'operation_id !=' => 5,
//					'uid' => $uid
//				));
//				$is_old = $this->db->count_all_results();
//				$user_status = $is_old > 0 ? 'old' : 'new';
//				$range = $card_money_old <= 5 ? 5 : 10;
//				$arr_confi_card_range = $cherry_card_reject[$user_status][$range];
//				if (!empty($arr_confi_card_range)) {
//					foreach ($arr_confi_card_range as $k => $v) {
//						if ($lucky <= $k) {
//							$card_add = $v;
//							break;
//						}
//					}
//					$card_reject = array('card_money' => $card_money_old + $card_add);
//					$this->db->update("wqbaby_active", array('card_money' => $card_money_old + $card_add, 'is_add' => 1), array('card_number' => $card_number_reject));
//					$this->db->update("card", $card_reject, array('card_number' => $card_number_reject));
//					$this->db->trans_commit();
//					return array('card_add' => $card_add, 'card_money_old' => $card_money_old);
//				} else {
//					return false;
//				}
//			} else {
//				return false;
//			}
////            }else
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */

//        $rd1 = mt_rand(1,10);
////        $this->db->from('card');
////        $this->db->where(array('sendtime' => date("Y-m-d"), 'remarks'=>$share_remarks, 'product_id'=>'4435'));
////        $today_ruby_num = $this->db->get()->num_rows();
//        if($rd1<=7){//&&$today_ruby_num<=33576
        // $active_product_id = '4435';
//        }else{
//            $active_product_id = '4070'; //2660
//        }

        if ($send_card) {
            $share_p_card_number = 'kiwi918';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);

//            if($active_product_id == 4435){
//                $send_limit_tag = $this->tag_code($active_product_id.date('Y-m-d'));
//                $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
//                $send_limit_num = $this->db->query($sql)->row_array();
//                if($send_limit_num['num']>33576){
//                    $active_product_id = 4070;
//                }
//                $send_limit_tag_data = array(
//                    'active_tag' => $send_limit_tag
//                );
//                $this->db->insert('card_send_limit', $send_limit_tag_data);
//            }

//            $this->db->from('card');
//            $this->db->where(array('sendtime' => date("Y-m-d"), 'remarks'=>$share_remarks ,'card_money'=>48));
//            $today_48_num = $this->db->get()->num_rows();
            $bingo = rand(1, 100000);

            /* 获取对应金额的优惠券start */
            // $sql = "select count(distinct o.id) as num from ttgy_order as o join ttgy_order_product as p on p.order_id=o.id where o.order_status=1 and o.operation_id!=5 and p.product_id=4070 and o.uid=" . $uid;
            // $buyed_num = $this->db->query($sql)->row_array();
            // $get_times = $buyed_num['num'] % 5 + 1;

            $this->db->select('user_rank');
            $this->db->from('user');
            $this->db->where('id', $uid);
            $user_result = $this->db->get()->row_array();

            $user_rank = (int) $user_result['user_rank'];

			switch($user_rank){
				case 1:
					$ruby_card_setting_old=array(
						'75000' => array(
							'card_money' => 20,
							'card_num' => 3600000
						),
						'100000' => array(
							'card_money' => 30,
							'card_num' => 1200000
						),
//						'100000' => array(
//							'card_money' => 48,
//							'card_num' => 1200000
//						),
					);
					break;
				case 2:
				case 3:
					$ruby_card_setting_old=array(
						'64500' => array(
							'card_money' => 20,
							'card_num' => 3600000
						),
						'99500' => array(
							'card_money' => 30,
							'card_num' => 1200000
						),
						'100000' => array(
							'card_money' => 48,
							'card_num' => 1200000
						),
					);
					break;
				case 4:
				case 5:
				case 6:
					$ruby_card_setting_old=array(
						'85000' => array(
							'card_money' => 20,
							'card_num' => 3600000
						),
						'100000' => array(
							'card_money' => 30,
							'card_num' => 1200000
						),
//					'100000' => array(
//						'card_money' => 48,
//						'card_num' => 1200000
//					),
					);
					break;
				default:$ruby_card_setting_old=array(
					'84500' => array(
						'card_money' => 20,
						'card_num' => 3600000
					),
					'99500' => array(
						'card_money' => 30,
						'card_num' => 1200000
					),
					'100000' => array(
						'card_money' => 48,
						'card_num' => 1200000
					),
				);
			}

			if($area['region'] == '广东省'){
				$ruby_card_setting_new = array(
					'50000' => array(
						'card_money' => 30,
						'card_num' => 1200000
					),
					'100000' => array(
						'card_money' => 20,
						'card_num' => 1200000
					),
				);
				$ruby_card_setting_old=array(
					'64050' => array(
						'card_money' => 20,
						'card_num' => 3600000
					),
					'99950' => array(
						'card_money' => 30,
						'card_num' => 1200000
					),
					'100000' => array(
						'card_money' => 48,
						'card_num' => 1200000
					),
				);
			}

//			$this->db->from('card');
//			$this->db->where(array('uid' => $uid, 'card_money' => 48));
//			$card_48_num = $this->db->get()->num_rows();

            foreach ($ruby_card_setting_old as $key => $value) {
                if ($bingo <= $key) {
                    if($user_rank<2 && $value['card_money']==48){
                        $card_money = 20;
                        $card_num = $value['card_num'];
                        break;
                    }
                    else{
                        $card_money = $value['card_money'];
                        $card_num = $value['card_num'];
                        break;
                    }
                }
            }

            /* 获取对应金额的优惠券end */

            /* 优惠券数量保护start */
//            $send_limit_tag = $this->tag_code($card_money . $card_active_start_time . $card_active_end_time);
//            $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
//            $send_limit_num = $this->db->query($sql)->row_array();
//            if($send_limit_num['num']>=$card_num){
//                $card_money = 5;
//            }

            // if($card_money == 48){
            //     $send_limit_tag = $this->tag_code($card_money.date('Y-m-d'));
            //     $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
            //     $send_limit_num = $this->db->query($sql)->row_array();
            //     if($send_limit_num['num']>100){
            //         $card_money = 5;
            //     }
            //     $send_limit_tag_data = array(
            //         'active_tag' => $send_limit_tag
            //     );
            //     $this->db->insert('card_send_limit', $send_limit_tag_data);
            // }

//			if ($card_money == 48 && !empty($user_result) && $user_result['user_rank'] < 3) {
//				$card_money = 5;
//			}
//			if ($card_money == 48 && $card_48_num > 0) {
//				$card_money = 5;
//			}
//            if ($card_money ==48 && $today_48_num>100){
//                $card_money = 5;
//            }
            /* 优惠券数量保护end */

            /* 有效期计算start */

            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' =>$share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
                    $card_time = date("Y-m-d", strtotime("+1 day"));
                    $card_to_date = date("Y-m-d", strtotime("+1 day"));
                } else {
                    $card_time = $now_date;
                    $card_to_date = $now_date;
                }
            } else {
                $card_time = $now_date;
                $card_to_date = $now_date;
            }

            /* 有效期计算end */

//            /* 指定范围客户优惠券面额变更 begin*/
//            $once_log_tag  = 'cherry_share_20';
//            $this->db->from('wqbaby_active');
//            $this->db->where(array('mobile' => $uid, 'active_tag'=>$once_log_tag));
//            $is_sent = $this->db->get()->num_rows();
//            $uids = require APPPATH.'config/uids.php';
//
//            if(empty($is_sent)&&in_array($uid,$uids)){
//                $card_money = 20;
//                $once_log_data = array(
//                    'mobile' => $uid,
//                    'active_tag' => $once_log_tag,
//                    'card_number' => $share_card_number,
//                    'card_money' => $card_money
//                );
//                $this->db->insert('wqbaby_active',$once_log_data);
//            }
//            /* 指定范围客户优惠券面额变更 end*/


            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                'channel' => $channel
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */

            /* 活动记录start */
//            $send_limit_tag_data = array(
//                'active_tag' => $send_limit_tag
//            );
//            $this->db->insert('card_send_limit', $send_limit_tag_data);
            /* 活动记录end */

            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money"=>$card_money,"yt_name"=>'');
    }

	function getLink_tag(){
		$link_tag = md5(tag_code(microtime() . rand(10000, 99999)));
		$data = array(
			'active_tag' => 'kiwi_2015_728',
			'link_tag' => $link_tag
		);
		$this->db->insert('share_link', $data);
		return $link_tag;
	}

	public function getKiwiCard($order_name){
		$o2oorder_info = $this->db->select("o.uid")->from('order o')->where(array('o.order_name'=>$order_name, 'o.operation_id <>'=>'5'))->where_in('order_type', array(3,4))->get()->row_array();
		$share_p_card_number = 'kiwi1111';
		$share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
		if(!empty($o2oorder_info)){
			$is_send = $this->db->from('card')->where(array('remarks'=>'佳沛绿果逆天神券','uid'=>$o2oorder_info['uid']))->get()->row_array();
			if($is_send){
				return false;
			}else{
				$card_data = array(
					'uid' => $o2oorder_info['uid'],
					'sendtime' => date("Y-m-d"),
					'card_number' => $share_card_number,
					'card_money' => '30',
					'product_id' => '6275',
					'maketing' => '0',
					'is_sent' => '1',
					'restr_good' => '1',
					'remarks' => '佳沛绿果逆天神券',
					'time' => '2015-11-11',
					'to_date' => '2015-11-11',//$card_to_date,
					'can_use_onemore_time' => 'false',
					'can_sales_more_times' => 'false',
					'card_discount' => 1,
					'order_money_limit' => 0,
					'return_jf' => 0,
					// 'black_user_list'=>'',
					'channel' => serialize(array("2"))
				);
				$this->db->insert('card', $card_data);
				return true;
			}
		}else{
			return false;
		}
	}

	/*
	 * 完成订单后判断订单的类型并返回uid和订单类型
	 */
	function getOrderType($order_name){
		$order_info = $this->db->select("o.uid,o.order_type")->from('order o')->where(array('o.order_name'=>$order_name, 'o.operation_id <>'=>'5'))->get()->row_array();
		return $order_info;
	}

	/*
	 * o2o 每日购买的第一单插一条记录
	 */
	function o2o_today_buy_add($uid,$order_name,$connect_id){
		$date = date('Y-m-d');
		$result = $this->db->from("o2o_daily_buy_log")->where(array('buy_date'=>$date,'uid'=>$uid,'times'=>1))->get()->row_array();
		if(empty($result)){
			if($date>='2016-03-05'){   //in_array($uid,array('613870','3024069','6032638','4663207','3939346','327884','92749','6287253','3024069','6032638','4663207','141047'))
				$data = array(
					'uid'=>$uid,
					'buy_date'=>$date,
					'times'=>1,
					'order_name'=>$order_name
				);
				$this->db->insert("o2o_daily_buy_log",$data);

				$return_result = array(
					"type" => 2,
					"page_url" => "http://huodong.fruitday.com/sale/o2o_calendar160415/app.html?auto=1&connect_id=".$connect_id,
					"share_alert" => "您有一份幸运赠品即将产生，不去看看吗？"
				);
				$return_result['code'] = '200';
				return $return_result;
//				$return_result = array(
//					"code"=> '200',
//					"type" => 4,
//					"page_url" => "http://huodong.fruitday.com/sale/o2o_calendar/app.html?auto=1&connect_id=".$connect_id,
//				);
//				return $return_result;
			}else{
				return array();
			}
		}else{
			return array();
		}
	}

	public function getBlow($order_name){
//		$order_info = $this->db->select("o.uid")->from('order o')->join('o2o_order_extra oo','o.id = oo.order_id')->join('o2o_store os','oo.store_id = os.id')->where(array('o.order_name'=>$order_name, 'o.operation_id <>'=>'5','os.city_id'=>'143950'))->get()->row_array();
//		if(!empty($order_info)){
//			return 2;
//		}else{
			$b2corder_info = $this->db->select("o.uid")->from('order o')->where(array('o.order_name'=>$order_name, 'o.operation_id <>'=>'5', 'order_type'=>1))->get()->row_array();
			if(!empty($b2corder_info)){//&&in_array($b2corder_info['uid'],array("332208","613870","894584","2011111","119653","324872","5008202"))
				return 3;
			}else{
//				$other_order_info = $this->db->select("o.uid,os.city_id")->from('order o')->join('o2o_order_extra oo','o.id = oo.order_id')->join('o2o_store os','oo.store_id = os.id')->where(array('o.order_name'=>$order_name, 'o.operation_id <>'=>'5','os.city_id !='=>'143950'))->get()->row_array();
//				if(!empty($other_order_info)){
////					if(in_array($other_order_info['uid'],array('311125','141047','290773','4268730'))){
//						if($other_order_info['city_id']=='106093'){//上海
//							return 5;
//						}else{
//							return 6;
//						}
////					}else
////						return 4;
//				}else{
					return 1;
//				}
			}
//		}
	}

	function apple_915_mobile($uid) {
		$now_time = date("Y-m-d H:i:s");
		$now_date = date("Y-m-d");
		/* 活动配置start */
		$active_tag = 'pear_2015_1008';
		$active_product_id = '6035'; //2660
		$send_card = false;
		$share_remarks = "仅限购买精选香梨4斤装";
		$this->db->trans_begin();
		/* 判断是否含有有效优惠券start */
		$card_number_arr = array();
		$this->db->from('card');
		$this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
		$this->db->order_by('id', 'DESC');
		$card_limit_arr = $this->db->get()->result_array();
		$card_limit_num = count($card_limit_arr);
		if ($card_limit_num > 0) {
			return false;
		}
		$send_card = true;
		/* 判断是否含有有效优惠券end */
		if ($send_card) {
			$share_p_card_number = 'pear1008';
			$share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);


//            $this->db->from('card');
//            $this->db->where(array('sendtime' => date("Y-m-d"), 'remarks'=>$share_remarks ,'card_money'=>48));
//            $today_48_num = $this->db->get()->num_rows();
			$bingo = rand(1, 100000);

			$sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = {$active_product_id} and o.operation_id !=5 and o.order_type=1";
			$bought_num = $this->db->query($sql)->row_array();
			switch($bought_num['bought']){
				case 1:
					$card_setting=array(
						'95000' => array(
							'card_money' => 20,
						),
						'100000' => array(
							'card_money' => 30,
						),
					);
					break;
				case 2:
					$card_setting=array(
						'40000' => array(
							'card_money' => 10,
						),
						'100000' => array(
							'card_money' => 20,
						),
//                        '100000' => array(
//                            'card_money' => 30,
//                        ),
					);
					break;
				case 3:
					$card_setting=array(
						'70000' => array(
							'card_money' => 10,
						),
						'100000' => array(
							'card_money' => 20,
						),
					);
					break;
				default:
					$card_setting=array(
						'69900' => array(
							'card_money' => 20,
						),
						'99900' => array(
							'card_money' => 30,
						),
						'100000' => array(
							'card_money' => 48,
						),
					);
					break;
			}

			$this->db->select('user_rank');
			$this->db->from('user');
			$this->db->where('id', $uid);
			$user_result = $this->db->get()->row_array();

			$user_rank = (int) $user_result['user_rank'];

//			$this->db->from('card');
//			$this->db->where(array('uid' => $uid, 'card_money' => 48));
//			$card_48_num = $this->db->get()->num_rows();

			foreach ($card_setting as $key => $value) {
				if ($bingo <= $key) {
					if($user_rank<2 && $value['card_money']==58){
						$card_money = 20;
						$card_num = $value['card_num'];
						break;
					}
					else{
						$card_money = $value['card_money'];
						$card_num = $value['card_num'];
						break;
					}
				}
			}

			/* 获取对应金额的优惠券end */

			/* 优惠券数量保护start */
//            $send_limit_tag = $this->tag_code($card_money . $card_active_start_time . $card_active_end_time);
//            $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
//            $send_limit_num = $this->db->query($sql)->row_array();
//            if($send_limit_num['num']>=$card_num){
//                $card_money = 5;
//            }

			// if($card_money == 48){
			//     $send_limit_tag = $this->tag_code($card_money.date('Y-m-d'));
			//     $sql = "select count(id) as num from ttgy_card_send_limit where active_tag='".$send_limit_tag."'";
			//     $send_limit_num = $this->db->query($sql)->row_array();
			//     if($send_limit_num['num']>100){
			//         $card_money = 5;
			//     }
			//     $send_limit_tag_data = array(
			//         'active_tag' => $send_limit_tag
			//     );
			//     $this->db->insert('card_send_limit', $send_limit_tag_data);
			// }

//			if ($card_money == 48 && !empty($user_result) && $user_result['user_rank'] < 3) {
//				$card_money = 5;
//			}
//			if ($card_money == 48 && $card_48_num > 0) {
//				$card_money = 5;
//			}
//            if ($card_money ==48 && $today_48_num>100){
//                $card_money = 5;
//            }
			/* 优惠券数量保护end */

			/* 有效期计算start */

			$this->db->select_max('sendtime');
			$this->db->from('card');
			$this->db->where(array('uid' => $uid, 'remarks' =>$share_remarks));
			$max_time = $this->db->get()->row_array();

			if (!empty($max_time['sendtime'])) {
				if ($max_time['sendtime'] >= $now_date) {
					$card_time = date("Y-m-d", strtotime("+1 day"));
					$card_to_date = date("Y-m-d", strtotime("+1 day"));
				} else {
					$card_time = $now_date;
					$card_to_date = $now_date;
				}
			} else {
				$card_time = $now_date;
				$card_to_date = $now_date;
			}

			/* 有效期计算end */

//            /* 指定范围客户优惠券面额变更 begin*/
//            $once_log_tag  = 'cherry_share_20';
//            $this->db->from('wqbaby_active');
//            $this->db->where(array('mobile' => $uid, 'active_tag'=>$once_log_tag));
//            $is_sent = $this->db->get()->num_rows();
//            $uids = require APPPATH.'config/uids.php';
//
//            if(empty($is_sent)&&in_array($uid,$uids)){
//                $card_money = 20;
//                $once_log_data = array(
//                    'mobile' => $uid,
//                    'active_tag' => $once_log_tag,
//                    'card_number' => $share_card_number,
//                    'card_money' => $card_money
//                );
//                $this->db->insert('wqbaby_active',$once_log_data);
//            }
//            /* 指定范围客户优惠券面额变更 end*/


			$channel = serialize(array("2"));
			$card_data = array(
				'uid' => $uid,
				'sendtime' => date("Y-m-d"),
				'card_number' => $share_card_number,
				'card_money' => $card_money,
				'product_id' => $active_product_id,
				'maketing' => '0',
				'is_sent' => '1',
				'restr_good' => '1',
				'remarks' => $share_remarks,
				'time' => $card_time,
				'to_date' => $card_time,//$card_to_date,
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'order_money_limit' => 0,
				'return_jf' => 0,
				// 'black_user_list'=>'',
				'channel' => $channel
			);
			/* 发优惠券start */
			$this->db->insert('card', $card_data);
			/* 发优惠券end */

			/* 活动记录start */
//            $send_limit_tag_data = array(
//                'active_tag' => $send_limit_tag
//            );
//            $this->db->insert('card_send_limit', $send_limit_tag_data);
			/* 活动记录end */

			$this->db->from('card');
			$this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
			$card_limit_num2 = $this->db->get()->num_rows();
			if ($card_limit_num2 > 1) {
				$this->db->trans_rollback();
				return false;
			}
			if ($this->db->trans_status() === FALSE) {
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
			}
		}
		return array("card_money"=>$card_money,"yt_name"=>'');
	}


    /*琯溪蜜柚优惠券*/
    function grapefruit_1016_mobile($uid)
    {
        $now_time = date("Y-m-d H:i:s");
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_tag = 'grapefruit_2015_1016';
        $active_product_id = '6141'; //2660
        $send_card = false;
        $share_remarks = "仅限购买琯溪蜜柚2个装";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $card_number_arr = array();
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = 'grape1016';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);

            $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = {$active_product_id} and o.operation_id !=5 and o.order_type=1";
            $bought_num = $this->db->query($sql)->row_array();
            $ip = $this->getIP();
            $cityInfo = $this->getCity($ip);
            if(1!=1)
            {
                switch ($bought_num['bought']) {
                    case 0:
                        $card_setting = array(
                            '19900' => array(
                                'card_money' => 20,
                            ),
                            '99900' => array(
                                'card_money' => 30,
                            ),
                            '100000' => array(
                                'card_money' => 48,
                            ),
                        );
                        break;
                    case 1:
                        $card_setting = array(
                            '35000' => array(
                                'card_money' => 20,
                            ),
                            '100000' => array(
                                'card_money' => 30,
                            ),
                        );
                        break;
                    case 2:
                        $card_setting = array(
                            '15000' => array(
                                'card_money' => 10,
                            ),
                            '50000' => array(
                                'card_money' => 20,
                            ),
                            '100000' => array(
                                'card_money' => 30,
                            )
                        );
                        break;

                    default:
                        $card_setting = array(
                            '50000' => array(
                                'card_money' => 10,
                            ),
                            '100000' => array(
                                'card_money' => 20,
                            ),
                        );
                        break;
                }

            }else{

                switch($bought_num['bought']){
                    case 0:
                        $card_setting=array(
                            '4900' => array(
                                'card_money' => 20,
                            ),
                            '89900' => array(
                                'card_money' => 30,
                            ),
                            '99900' => array(
                                'card_money' => 40,
                            ),
                            '100000' => array(
                                'card_money' => 48,
                            ),
                        );
                        break;
                    case 1:
                        $card_setting=array(
                            '20000' => array(
                                'card_money' => 20,
                            ),
                            '100000' => array(
                                'card_money' => 30,
                            ),
                        );
                        break;
                    case 2:
                        $card_setting=array(
                            '40000' => array(
                                'card_money' => 20,
                            ),
                            '100000' => array(
                                'card_money' => 30,
                            ),
                        );
                        break;
                    default:
                        $card_setting=array(
                            '70000' => array(
                                'card_money' => 20,
                            ),
                            '100000' => array(
                                'card_money' => 30,
                            ),
                        );
                        break;
                }

            }



            $this->db->select('user_rank');
            $this->db->from('user');
            $this->db->where('id', $uid);
            $user_result = $this->db->get()->row_array();

            $user_rank = (int)$user_result['user_rank'];

            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    if ($user_rank < 2 && $value['card_money'] == 58) {
                        $card_money = 20;
                        $card_num = $value['card_num'];
                        break;
                    } else {
                        $card_money = $value['card_money'];
                        $card_num = $value['card_num'];
                        break;
                    }
                }
            }

            /* 获取对应金额的优惠券end */


            /* 有效期计算start */

            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
                    $card_time = date("Y-m-d", strtotime("+1 day"));
                    $card_to_date = date("Y-m-d", strtotime("+1 day"));
                } else {
                    $card_time = $now_date;
                    $card_to_date = $now_date;
                }
            } else {
                $card_time = $now_date;
                $card_to_date = $now_date;
            }

            /* 有效期计算end */

            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                'channel' => $channel
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */


            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

    function getTransactionBy($condition=array(), $where_in=array(),$limit = 10,$offset=0)
    {
        if( ! empty($condition) )
        {
            if( empty($where_in) )
            {
                return  $this->db->from("trade")
                    ->where($condition)
                    ->order_by('time','desc')
					 ->limit($limit, $offset)
                    ->get()->result();

            } else {
                return  $this->db->from("trade")
                    ->where($condition)
                    ->where_in($where_in[0], $where_in[1])
                    ->order_by('time','desc')
					->limit($limit, $offset)
                    ->get()->result();
            }
        }

    }

    /*佳沛绿果优惠券*/
    function active_1022_mobile($uid)
    {
		$begin_time = '2015-11-10 23:00:00';
		$end_time = '2015-11-12 00:00:00';
		$now_time = date('Y-m-d H:i:s');

		if($now_time>=$begin_time&&$now_time<$end_time)
			$is_1111 = true;
		else
			$is_1111 = false;
//		if(in_array($uid,array("332208","613870","894584","2011111"))){
//			$is_1111 = true;
//		}

        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6275'; //2660
        $share_remarks = "仅限购买佳沛绿果10个装";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = '6275_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);

            $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = {$active_product_id} and o.operation_id !=5 and o.order_type=1";
            $bought_num = $this->db->query($sql)->row_array();
            //$ip = $this->getIP();
            //$cityInfo = $this->getCity($ip);
            $config = array(
                0 => array(
                    '79900' => 20,
                    '99900' => 30,
                    '100000'=> 48
                ),
                1 => array(
                    '90000' => 20,
                    '100000' => 30
                ),
                2 => array(
                    '35000' =>  10,
                    '95000' =>  20,
                    '100000' =>  30
                ),
                3 => array(
                    '70000' =>  10,
                    '100000' =>  20
                )
            );
            $card_setting = $bought_num['bought']>3 ? $config[3] : $config[$bought_num['bought']];

            $this->db->select('user_rank');
            $this->db->from('user');
            $this->db->where('id', $uid);
            $user_result = $this->db->get()->row_array();

            $user_rank = (int)$user_result['user_rank'];

            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    if ($user_rank < 2 && $value == 58) {
                        $card_money = 20;
                        //$card_num = $value['card_num'];
                        break;
                    } else {
                        $card_money = $value;
                        //$card_num = $value['card_num'];
                        break;
                    }
                }
            }
            /* 获取对应金额的优惠券end */

            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
					if($is_1111){
						/*
                         * 双十一判断begin
                         */
						return 2;
						/*
                         * 双十一判断end
                         */
					}else {
						$card_time = date("Y-m-d", strtotime("+1 day"));
						//$card_to_date = date("Y-m-d", strtotime("+
					}
                } else {
                    $card_time = $now_date;
                    //$card_to_date = $now_date;
                }
            } else {
                $card_time = $now_date;
                //$card_to_date = $now_date;
            }
            /* 有效期计算end */
			/*
			 * 双十一判断begin
			 */
			if($is_1111) //当天优惠券面额强制改成30元
				$card_money = 30;
			/*
             * 双十一判断end
             */

            $channel = serialize(array("0"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                'channel' => ''
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '','is_1111'=>$is_1111);
    }

    /**
     * 更改推送信息设置
     *
     * @param Int $uid 会员ID
     * @param Int $msgsetting 配置
     * @return Boolean
     * @author chenping
     **/
    public function update_msgsetting($uid,$msgsetting)
    {
        $rs = $this->db->update('user',array('msgsetting'=>$msgsetting),array('id'=>$uid),1);

        return $rs;
    }

    /*菲律宾优惠券*/
    function active_1109_mobile($uid)
    {
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6573'; //2660
        $share_remarks = "天天到家菲律宾香蕉优惠券";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid,  'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = '6573_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);
            $config = array(
                6573 => array(//北京菲律宾香蕉
                    9999 => array(
                        '70000' => 15,
                        '100000' => 18
                    ),
                    0 => array(
                        '10000' => 10,
                        '100000' => 15
                    ),
                    1 => array(
                        '20000' => 10,
                        '100000' => 15
                    ),
                    2 => array(
                        '30000' => 10,
                        '100000' => 15
                    ),
                    3 => array(
                        '40000' => 10,
                        '100000' => 15
                    )
                ),
                '6573_1' => array(//成都菲律宾香蕉
                    9999 => array(
                        '40000' => 15,
                        '100000' => 18
                    ),
                    0 => array(
                        '40000' => 15,
                        '100000' => 18
                    ),
                    1 => array(
                        '50000' => 13,
                        '100000' => 15
                    ),
                    2 => array(
                        '70000' => 13,
                        '100000' => 15
                    ),
                    3 => array(
                        '90000' => 10,
                        '100000' => 15
                    )
                )
            );
            $bought_num = $this->getOrderNumByO2O($uid);
            if($bought_num==0){
                $bought_num = 9999;//从未在oto下过单的设为 新用户
            }else{
                $bought_num = $this->getOrderNumByO2OProduct($uid, $active_product_id);//当前商品购买次数
            }
            $ip = $this->getIP();
            $cityInfo = $this->getCity($ip);
            if($cityInfo['city'] == '成都市'){
                $active_product_id_tmp = $active_product_id.'_1';
            }else{
                $active_product_id_tmp = $active_product_id;
            }
            $card_setting = ($bought_num>3 && $bought_num< 9999)?$config[$active_product_id_tmp][3] : $config[$active_product_id_tmp][$bought_num];
            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    $card_money = $value;
                    break;
                }
            }
            /* 获取对应金额的优惠券end */

            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
                    $card_time = date("Y-m-d", strtotime("+1 day"));
                } else {
                    $card_time = $now_date;
                }
            } else {
                $card_time = $now_date;
            }
            /* 有效期计算end */
            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '1',//0: 天天果园  1:天天到家
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                'channel' => $channel
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }


	function active_1112_mobile($uid){//上海
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6705'; //2660
        $share_remarks = "天天到家进口香蕉优惠券";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid,  'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = '6705_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);
            $config = array(
                6705 => array(//北京菲律宾香蕉
                    0 => array(
						'1000' => 18,
						'100000' => 15
					),
					1 => array(
						'40000' => 15,
						'100000' => 10
					),
					2 => array(
						'30000' => 15,
						'100000' => 10
					),
					3 => array(
						'20000' => 15,
						'100000' => 10
					),
					9999 => array(//未在b2c和o2o下过单的
						'10000' => 18,
						'100000' => 15
					)
				)
            );
            $bought_num = $this->getOrderNumByO2O($uid);
			if ($bought_num == 0) {
				$b2cNum = $this->getOrderNumB2c($uid);
				if ($b2cNum == 0) {
					$bought_num = 9999;
				}
			}
            $ip = $this->getIP();
            $cityInfo = $this->getCity($ip);
            $active_product_id_tmp = $active_product_id;
            $card_setting = ($bought_num>3 && $bought_num< 9999)?$config[$active_product_id_tmp][3] : $config[$active_product_id_tmp][$bought_num];
            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    $card_money = $value;
                    break;
                }
            }
            /* 获取对应金额的优惠券end */

            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
                    $card_time = date("Y-m-d", strtotime("+1 day"));
                } else {
                    $card_time = $now_date;
                }
            } else {
                $card_time = $now_date;
            }
            /* 有效期计算end */
            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '1',//0: 天天果园  1:天天到家
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                'channel' => $channel
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

	function active_1106_mobile($uid) {//北京北京褚橙
		$now_date = date("Y-m-d");
		/* 活动配置start */
		$active_product_id = '6743';
		$share_remarks = "天天到家北京褚橙领优惠券";
		$this->db->trans_begin();
		/* 判断是否含有有效优惠券start */
		$this->db->from('card');
		$this->db->where(array('uid' => $uid, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
		$this->db->order_by('id', 'DESC');
		$card_limit_arr = $this->db->get()->result_array();
		$card_limit_num = count($card_limit_arr);
		if ($card_limit_num > 0) {
			return false;
		}
		$send_card = true;
		/* 判断是否含有有效优惠券end */
		if ($send_card) {
			$share_p_card_number = '6743_';
			$share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
			$bingo = rand(1, 100000);
			$config = array(
				6743 => array(//北京褚橙
					9999 => array(//未o2o下过单的
						'100000' => 10,
					),
					0 => array(//在o2o下过但未参加过活动
						'100' => 18,
						'80100' => 9,
						'100000' => 5
					),
					1 => array(//在020下过单，本活动只参与了1次
						'70000' => 9,
						'100000' => 5
					),
					2 => array(//在020下过单，本活动只参与了2次
						'70000' => 9,
						'100000' => 5
					),
					3 => array(//在020下过单，本活动只参与了3次及以上
						'50000' => 9,
						'100000' => 5
					)
				)
			);
			$bought_num = $this->getOrderNumByO2O($uid);
			if ($bought_num == 0) {
				$bought_num = $this->getOrderNumByO2O($uid); //o2o没订单  则是新客设为9999
				if ($bought_num == 0) {
					$bought_num = 9999;
				} else {
					$this->getOrderNumByO2OProduct($uid, $active_product_id);
				}
			}
			$ip = $this->getIP();
			$cityInfo = $this->getCity($ip);
			$active_product_id_tmp = $active_product_id;
			$card_setting = ($bought_num > 3 && $bought_num < 9999) ? $config[$active_product_id_tmp][3] : $config[$active_product_id_tmp][$bought_num];
			foreach ($card_setting as $key => $value) {
				if ($bingo <= $key) {
					$card_money = $value;
					break;
				}
			}
			/* 获取对应金额的优惠券end */

			/* 有效期计算start */
			$this->db->select_max('sendtime');
			$this->db->from('card');
			$this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
			$max_time = $this->db->get()->row_array();

			if (!empty($max_time['sendtime'])) {
				if ($max_time['sendtime'] >= $now_date) {
					$card_time = date("Y-m-d", strtotime("+1 day"));
				} else {
					$card_time = $now_date;
				}
			} else {
				$card_time = $now_date;
			}
			/* 有效期计算end */
			$channel = serialize(array("2"));
			$card_data = array(
				'uid' => $uid,
				'sendtime' => date("Y-m-d"),
				'card_number' => $share_card_number,
				'card_money' => $card_money,
				'product_id' => $active_product_id,
				'maketing' => '1', //0: 天天果园  1:天天到家
				'is_sent' => '1',
				'restr_good' => '1',
				'remarks' => $share_remarks,
				'time' => $card_time,
				'to_date' => $card_time, //$card_to_date,
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'order_money_limit' => 0,
				'return_jf' => 0,
				// 'black_user_list'=>'',
				'channel' => $channel
			);
			/* 发优惠券start */
			$this->db->insert('card', $card_data);
			/* 发优惠券end */
			$this->db->from('card');
			$this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
			$card_limit_num2 = $this->db->get()->num_rows();
			if ($card_limit_num2 > 1) {
				$this->db->trans_rollback();
				return false;
			}
			if ($this->db->trans_status() === FALSE) {
				$this->db->trans_rollback();
				return false;
			} else {
				$this->db->trans_commit();
			}
		}
		return array("card_money" => $card_money, "yt_name" => '褚橙');
	}

	function active_1112gd_mobile($uid){//广东
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6706';
        $share_remarks = "天天到家进口香蕉优惠券";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid,  'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = '6706_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);
            $config = array(
                6706 => array(//广东
					0 => array(
						'1000' => 18,
						'100000' => 15
					),
					1 => array(
						'70000' => 15,
						'100000' => 10
					),
					2 => array(
						'60000' => 15,
						'100000' => 10
					),
					3 => array(
						'30000' => 15,
						'100000' => 10
					),
					9999 => array(//未在b2c和o2o下过单的
						'10000' => 18,
						'100000' => 15
					)
				)
            );
			$bought_num = $this->getOrderNumByO2O($uid);
			if ($bought_num == 0) {
				$b2cNum = $this->getOrderNumB2c($uid);
				if ($b2cNum == 0) {
					$bought_num = 9999;
				}
			}
            $ip = $this->getIP();
            $cityInfo = $this->getCity($ip);
			$active_product_id_tmp = $active_product_id;
            $card_setting = ($bought_num>3 && $bought_num< 9999)?$config[$active_product_id_tmp][3] : $config[$active_product_id_tmp][$bought_num];
            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    $card_money = $value;
                    break;
                }
            }
            /* 获取对应金额的优惠券end */

            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
                    $card_time = date("Y-m-d", strtotime("+1 day"));
                } else {
                    $card_time = $now_date;
                }
            } else {
                $card_time = $now_date;
            }
            /* 有效期计算end */
            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '1',//0: 天天果园  1:天天到家
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                'channel' => $channel
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

	function active_1112cd_mobile($uid){//成都
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6704'; //2660
        $share_remarks = "天天到家进口香蕉优惠券";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid,  'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = '6704_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);
            $config = array(
                6704 => array(//成都
					0 => array(//未购买过该产品
						'80000' => 15,
						'100000' => 13
					),
					1 => array(
						'60000' => 15,
						'100000' => 13
					),
					2 => array(
						'40000' => 15,
						'100000' => 13
					),
					3 => array(
						'10000' => 15,
						'10000' => 10
					),
					9999 => array(//未在o2o下过单的
						'70000' => 18,
						'100000' => 15
					)
				)
            );
			$bought_num = $this->getOrderNumByO2O($uid);
			if ($bought_num == 0) {
				$bought_num = 9999; //从未在oto下过单的设为 新用户
			} else {
				$bought_num = $this->getOrderNumByO2OProduct($uid, $active_product_id); //当前商品购买次数
			}
            $ip = $this->getIP();
            $cityInfo = $this->getCity($ip);
			$active_product_id_tmp = $active_product_id;
            $card_setting = ($bought_num>3 && $bought_num< 9999)?$config[$active_product_id_tmp][3] : $config[$active_product_id_tmp][$bought_num];
            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    $card_money = $value;
                    break;
                }
            }
            /* 获取对应金额的优惠券end */

            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
                    $card_time = date("Y-m-d", strtotime("+1 day"));
                } else {
                    $card_time = $now_date;
                }
            } else {
                $card_time = $now_date;
            }
            /* 有效期计算end */
            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '1',//0: 天天果园  1:天天到家
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                'channel' => $channel
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

    /*
   * @desc 天天到家购买次数
   */
    public function getOrderNumByO2O($uid){
        if(!$uid) return 0;
        $sql = "select count(*) as bought from ttgy_order o where o.uid={$uid} and o.pay_status = 1 and o.operation_id !=5 and o.order_type in (3,4)";
        $bought_num = $this->db->query($sql)->row_array();
        return $bought_num['bought'];
    }

    /*
     * @desc 某个商品 天天到家购买次数
     * operation_id = 5 取消
     * order_status ＝ 1 提交过表单
     * pay_status ＝ 1 付过款
     * */
    public function getOrderNumByO2OProduct($uid, $active_product_id){
        if(!$uid) return 0;
        $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = {$active_product_id} and o.operation_id !=5 AND o.pay_status = 1 and o.order_type in (3,4)";
        $bought_num = $this->db->query($sql)->row_array();
        return $bought_num['bought'];
    }


    /*天天果园云南冰糖橙领优惠券*/
    function active_card_mobile_1111($uid)
    {
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6650'; //2660
        $share_remarks = "仅限购买云南冰糖橙";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = '6650_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);

            $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = {$active_product_id} and o.operation_id !=5 and o.order_type=1";
            $bought_num = $this->db->query($sql)->row_array();
            //$ip = $this->getIP();
            //$cityInfo = $this->getCity($ip);
            $config = array(
                0 => array(
                    '10000' => 10,
                    '95000' => 20,
                    '100000' => 30,
                ),
                1 => array(
                    '90000' => 20,
                    '100000' => 30
                ),
                2 => array(
                    '40000' =>  10,
                    '100000' =>  20
                ),
                3 => array(
                    '70000' =>  10,
                    '100000' =>  20
                )
            );
            $card_setting = $bought_num['bought']>3 ? $config[3] : $config[$bought_num['bought']];

            $this->db->select('user_rank');
            $this->db->from('user');
            $this->db->where('id', $uid);
            $user_result = $this->db->get()->row_array();

            $user_rank = (int)$user_result['user_rank'];

            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    if ($user_rank < 2 && $value == 58) {
                        $card_money = 20;
                        //$card_num = $value['card_num'];
                        break;
                    } else {
                        $card_money = $value;
                        //$card_num = $value['card_num'];
                        break;
                    }
                }
            }
            /* 获取对应金额的优惠券end */

            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {

                    $card_time = date("Y-m-d", strtotime("+1 day"));
                    //$card_to_date = date("Y-m-d", strtotime("+

                } else {
                    $card_time = $now_date;
                    //$card_to_date = $now_date;
                }
            } else {
                $card_time = $now_date;
                //$card_to_date = $now_date;
            }
            /* 有效期计算end */

            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                //全站使用
                'channel' => ''
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

    /*天天果园云南冰糖橙领优惠券*/
    function active_card_mobile_1122($uid)
    {
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6650'; //2660
        $share_remarks = "仅限购买云南冰糖橙";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => '2015-11-22', 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {
            $share_p_card_number = '6650_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $card_money = 30;
            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => '2015-11-22',
                'to_date' => '2015-11-22',//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                'channel' => ''
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

    //11月会员日赠品发放
    function active_gift($uid,$card_money)
    {
    	//会员赠品开始

        $userArr = $this->db->select('user_rank')->from('user')->where(array('id' => $uid))->get()->row_array();
        $user_rank = $userArr['user_rank'];
        if($user_rank>=1&&$user_rank<=3){
            $all_num = 20000;
            $active_id = 1272;
        }else if($user_rank>=4&&$user_rank<=6){
            $all_num = 15000;
            $active_id = 1271;
        }else{
        	$all_num = 20000;
            $active_id = 1272;
        }

        $this->db->from("user_gifts");
		$this->db->where(array("active_id" => $active_id));
		$gift_num = $this->db->get()->num_rows();


		if ($gift_num > $all_num) {
                    $shar_desc = $card_money."元冰糖橙（12个装）单品优惠券已至您账户中，下单立减".$card_money."元，包邮~";
                }else{
			$this->db->from("user_gifts");
			$this->db->where(array("uid" => $uid, "active_id" => $active_id));
			$result = $this->db->get()->row_array();
			if (empty($result)) {
                $gift_send = $this->db->select('*')->from('gift_send')->where('id', $active_id)->get()->row_array();
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
					'active_id' => $active_id,
					'active_type' => '2',
					'has_rec' => '0',
					'start_time'=>$gift_start_time,
	                'end_time'=>$gift_end_time,
				);
				$result = $this->db_master->insert('user_gifts', $user_gift_data);
                                if($card_money!=99999){
                                    $shar_desc = '小伙伴速度赞赞哒！恭喜您抢到了免费福利！1斤装橙先生请至“我的赠品”中直接免费领取，另送您一张'.$card_money.'元冰糖橙（12个装）优惠券哦！';
                                }else{
                                    $shar_desc = '小伙伴速度赞赞哒！恭喜您抢到了免费福利！1斤装橙先生请至“我的赠品”中直接免费领取';
                                }
			} else {
				$shar_desc = "果园君掐指一算，您的账户里已经存在分享福利啦~~.";
			}
		}
//        }else{
//
//        }
		return $shar_desc;
    }

	function answer_lichee($uid) {
//		$active_id = 2571;
		$active_id = array(
			1 => array('20160601' => 2571, '20160602' => 2580, '20160603' => 2581, '20160604' => 2582, '20160605' => 2583, '20160606' => 2584, '20160607' => 2585, '20160608' => 2586,),
			2 => array('20160606' => 2596, '20160607' => 2597, '20160608' => 2598,),
		);
		/*
		 * 判断新老客户 start
		 */
		$this->db->from('order');
		$this->db->where(array('order.uid' => $uid, 'order.order_status' => '1', 'order.operation_id !=' => '5', 'order.pay_status !=' => '0'));
		$type = ($this->db->get()->num_rows() == 0) ? 1 : 2;
		/*
		 * 判断新老客户 end
		 */
		$dd = date('Ymd');
		$this->db->from("user_gifts");
		$this->db->where(array("uid" => $uid, "active_id" => $active_id[$type][$dd]));
		$result = $this->db->get()->row_array();
		if (empty($result)) {
			$gift_send = $this->db->select('*')->from('gift_send')->where('id', $active_id[$type][$dd])->get()->row_array();
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
				'active_id' => $active_id[$type][$dd],
				'active_type' => '2',
				'has_rec' => '0',
				'start_time'=>$gift_start_time,
	            'end_time'=>$gift_end_time,
			);
			$result = $this->db_master->insert('user_gifts', $user_gift_data);
			$shar_desc = "恭喜您抢到了免费福利1斤荔枝,请至“我的赠品”中直接免费领取";
		} else {
			$shar_desc = "果园君掐指一算，已有1斤荔枝在您账户中，整装待发，明日再来吧!";
		}
		return $shar_desc;
	}

	function answer_teacher($uid) {
        $card_tag = '2x6w5j5';
        $now_date = date("Y-m-d");
        $mobileCardList = $this->get_cardInfo_by_tag($card_tag);
        $mobileCardRes = array();
        $remarksArr = array();
        if (empty($mobileCardList)) {
            $shar_desc = '领取失败，请联系客服！';
            return $shar_desc;
        }
        foreach ($mobileCardList as $val) {
            $aCard = array();
            $aCard['uid'] = $uid;
            $aCard['sendtime'] = $now_date;
            $aCard['card_number'] = $val['p_card_number'] . $this->rand_card_number($val['p_card_number']);
            $aCard['card_money'] = $val['card_money'];
            $aCard['product_id'] = $val['product_id'];
            $aCard['maketing'] = 0;
            $aCard['is_sent'] = 1;
            $aCard['restr_good'] = empty($val['product_id']) ? 0 : 1;
            $aCard['remarks'] = $val['remarks'];
            $aCard['time'] = $val['time'];
            $aCard['to_date'] = $val['card_to_date'];
            $aCard['can_use_onemore_time'] = 'false';
            $aCard['can_sales_more_times'] = 'false';
            $aCard['card_discount'] = 1;
            $aCard['order_money_limit'] = $val['order_money_limit'];
            $aCard['return_jf'] = 0;
            $aCard['channel'] = '';
            array_push($remarksArr, $val['remarks']);
            array_push($mobileCardRes, $aCard);
        }
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'to_date >=' => $now_date));
        $this->db->where_in('remarks', $remarksArr);
        $result = $this->db->get()->result_array();

        if (!empty($result)) {
            $shar_desc = '果园君掐指一算,已有优惠券在您账户中!';
            return $shar_desc;
        }
        $this->db->insert_batch('card', $mobileCardRes);
        $shar_desc = '恭喜您获得5元券，详情请至“我的果园-优惠券”查看。';
        return $shar_desc;
    }

	function invite_prune($uid) {
	    //		$active_id = 2571;
	    $active_id = array(
	        1 => array('20160831' =>3577, '20160901' => 3577, '20160902' => 3579, '20160903' => 3581, '20160904' => 3583, '20160905' => 3585, '20160906' => 3587, '20160907' => 3589, '20160908' => 3591, '20160909' => 3593, '20160910' => 3595, '20160911' => 3597, '20160912' => 3599, '20160913' => 3601), //新客赠品
	        2 => array('20160831' =>3578, '20160901' => 3578, '20160902' => 3580, '20160903' => 3582, '20160904' => 3584, '20160905' => 3586, '20160906' => 3588, '20160907' => 3590, '20160908' => 3592, '20160909' => 3594, '20160910' => 3596, '20160911' => 3598, '20160912' => 3600, '20160913' => 3602), //老客赠品
	    );
	    /*
	     * 判断新老客户 start
	    */
	    $this->db->from('order');
	    $this->db->where(array('order.uid' => $uid, 'order.order_status' => '1', 'order.operation_id !=' => '5', 'order.pay_status !=' => '0'));
	    $type = ($this->db->get()->num_rows() == 0) ? 1 : 2;
	    /*
	     * 判断新老客户 end
	     */
	    $dd = date('Ymd');
	    $this->db->from("user_gifts");
	    $this->db->where(array("uid" => $uid, "active_id" => $active_id[$type][$dd]));
	    $result = $this->db->get()->row_array();
	    if (empty($result)) {
	    	$gift_send = $this->db->select('*')->from('gift_send')->where('id', $active_id[$type][$dd])->get()->row_array();
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
	            'active_id' => $active_id[$type][$dd],
	            'active_type' => '2',
	            'has_rec' => '0',
	            'start_time'=>$gift_start_time,
	            'end_time'=>$gift_end_time,
	        );
	        $result = $this->db_master->insert('user_gifts', $user_gift_data);
			$shar_desc = "恭喜您抢到了西梅1斤,请至“我的赠品”中直接免费领取";
	    } else {
			$shar_desc = "果园君掐指一算，已有1斤西梅在您账户中，整装待发，明日再来吧！";
	    }
	    return $shar_desc;
	}

	public function turntable160614($uid) {
		$mobile_card_tag = array('new' => array('7i8m4e3', '7z8y9p4', '3k4u8z6'), 'old' => array('3f3g9d7', '4x3h4q4', '4m3q6e7'));
		$now_date = date("Y-m-d");
		/*
		 * 判断新老客户 start
		 */
		$this->db->from('order');
		$this->db->where(array('order.uid' => $uid, 'order.order_status' => '1', 'order.operation_id !=' => '5', 'order.pay_status !=' => '0'));
		$type = ($this->db->get()->num_rows() == 0) ? 'new' : 'old';
		/*
		 * 判断新老客户 end
		 */
		$mobileCardList = $this->get_cardInfo_by_tag($mobile_card_tag[$type]);
		$mobileCardRes = array();
		$remarksArr = array();
		if (empty($mobileCardList)) {
			$shar_desc = '领取失败，请联系客服！';
			return $shar_desc;
		}
		foreach ($mobileCardList as $val) {
			$aCard = array();
			$aCard['uid'] = $uid;
			$aCard['sendtime'] = $now_date;
			$aCard['card_number'] = $val['p_card_number'] . $this->rand_card_number($val['p_card_number']);
			$aCard['card_money'] = $val['card_money'];
			$aCard['product_id'] = $val['product_id'];
			$aCard['maketing'] = 0;
			$aCard['is_sent'] = 1;
			$aCard['restr_good'] = empty($val['product_id']) ? 0 : 1;
			$aCard['remarks'] = $val['remarks'];
			$aCard['time'] = $val['time'];
			$aCard['to_date'] = $val['card_to_date'];
			$aCard['can_use_onemore_time'] = 'false';
			$aCard['can_sales_more_times'] = 'false';
			$aCard['card_discount'] = 1;
			$aCard['order_money_limit'] = $val['order_money_limit'];
			$aCard['return_jf'] = 0;
			$aCard['channel'] = '';
			array_push($remarksArr, $val['remarks']);
			array_push($mobileCardRes, $aCard);
		}
		$this->db->from('card');
		$this->db->where(array('uid' => $uid, 'to_date >=' => $now_date));
		$this->db->where_in('remarks', $remarksArr);
		$result = $this->db->get()->result_array();
		if (!empty($result)) {
			$shar_desc = '您的账户内已有60元优惠券套餐，不能更多啦！';
			return $shar_desc;
		}
		$this->db->insert_batch('card', $mobileCardRes);
		$shar_desc = '恭喜您获得60元套券，券使用日期6月17日00:00-6月20日23:59，详情请至“我的果园-优惠券”查看。';
		return $shar_desc;
	}

	//根据tag获取Card的详细信息
	public function get_cardInfo_by_tag($card_tag) {
		if (empty($card_tag)) {
            return array();
        }
        $this->db->select('*');
        $this->db->from('mobile_card');
        if (is_array($card_tag)) {
            $this->db->where_in('card_tag', $card_tag);
        } else {
            $this->db->where(array("card_tag" => $card_tag));
        }
        return $this->db->get()->result_array();
    }

    function save($user, $from="")
    {
        if( ! $this->user_id )
            return array("error"=>1,"msg"=>"无会员ID");


		if (isset($user['nickname'])){
            $toUpdate['username'] = $user['nickname'];
        }

		if (isset($user['sex'])){
			$toUpdate['sex'] = $user['sex'];
		}

		if (isset($user['email'])){
			$toUpdate['email'] = $user['email'];
		}
        $last_birthday = '';
		if ( !empty($user['birthday_y'])&&!empty($user['birthday_m'])&&!empty($user['birthday_d'])){
			$toUpdate['birthday'] = strtotime($user['birthday_y']."-".$user['birthday_m']."-".$user['birthday_d']);
            //$toUpdate['birthday_status'] = 1;
            $user_birthday = $this->db->select('birthday')->from('user')->where('id',$this->user_id)->get()->row_array();
            $last_birthday = $user_birthday['birthday'];
		}

		try {
            $this->db->where("id", $this->user_id);
			if( $this->db->update(
				"user",
                $toUpdate
			) ) {
                if($last_birthday && $toUpdate['birthday'] && $toUpdate['birthday']!=$last_birthday){
                    $insertData = array('uid'=>$this->user_id,'birthday'=>$last_birthday,'time'=>time());
                    $this->db->insert('user_birthday_log',$insertData);
                }
				return array("error"=>0,"msg"=>"修改成功");
            }
		} catch ( Exception $e) {
            log_message('error', $e->getMessage());
			return array("error"=>1,"msg"=>$e->getMessage());
		}

    }


    /*
	 * 获取用户充值信息
	 */
    function get_user_trade_info($uid,$order_id) {
        $this->db->select('time,money,trade_number,status');
        $this->db->from('trade');
        $this->db->where('uid', $uid);
        $this->db->where('trade_number', $order_id);
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*阿克苏冰糖心苹果*/
    function active_1123_mobile($uid)
    {
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6782'; //2660
        $share_remarks = "天天果园冰糖心苹果优惠券";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        //会员等级
        $userArr = $this->db->select('user_rank')->from('user')->where(array('id' => $uid))->get()->row_array();
        $user_rank = (int)$userArr['user_rank'];
        if($user_rank>=2){
            $arr = array(
                '20000'=>30,
                '99900'=>20,
                '100000'=>48,
            );
        }else{
            $arr = array(
                '80000'=>20,
                '100000'=>30
            );
        }
        /* 判断是否含有有效优惠券end */
        if ($send_card) {

            $share_p_card_number = '6782_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $bingo = rand(1, 100000);

            $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = {$active_product_id} and o.operation_id !=5 and o.order_type=1";
            $bought_num = $this->db->query($sql)->row_array();
//            print_r($bought_num);exit;
            $config = array(
                    9999 => array(
                        '20000' => 20,
                        '100000' => 30
                    ),
                    0=>array(
                        '50000' => 20,
                        '100000' => 30
                    ),
                    1 => array(
                            '80000' => 20,
                            '100000' => 30
                    ),
                    2 => array(
                        '30000' => 10,
                        '100000' => 20
                    ),
                    3 => array(
                        '70000' => 10,
                        '100000' => 20
                    )
            );
            $card_setting = $bought_num['bought']>3 ? $config[3] : $config[$bought_num['bought']];
            foreach ($card_setting as $key => $value) {
                if ($bingo <= $key) {
                    if ($user_rank < 2 && $value == 58) {
                        $card_money = 20;
                        break;
                    } else {
                        $card_money = $value;
                        break;
                    }
                }
            }
            /* 获取对应金额的优惠券end */

            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();

            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {

                    $card_time = date("Y-m-d", strtotime("+1 day"));
                    //$card_to_date = date("Y-m-d", strtotime("+

                } else {
                    $card_time = $now_date;
                    //$card_to_date = $now_date;
                }
            } else {
                $card_time = $now_date;
                //$card_to_date = $now_date;
            }
            /* 有效期计算end */

            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                //全站使用
                'channel' => ''
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

    //获取用户未使用的优惠券张数
    public function get_user_card_num($uid, $now_date, $share_remarks){
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
        return $this->db->get()->num_rows();
    }

    //b2cCard 后台配置的领券活动
    function active_card_by_b2c($uid, $activeId, $maketing=0)
    {
        $userInfo = $this->getUser($uid, null, 'id,mobile,user_rank');

        $user_mobile = trim($userInfo['mobile']);
        if($user_mobile){
            $this->load->library('ebuckler');
            $refresh = false;
            $params = array(
                'mobile' => $user_mobile,
                'from' => 'app'
            );
            $result = $this->ebuckler->score( $params, $refresh );
            $rating = $result['data'][$user_mobile]['rating'] ;
        }else{
            $rating = "H";//高危手机号
        }
        $now_date = date("Y-m-d");
        //获取活动详情
        $this->db->select('*');
        $this->db->from('active_base');
        $this->db->where('id', $activeId);
        $activeInfo = $this->db->get()->row_array();
        if(time() < strtotime($activeInfo["startTime"]) ){
            return  array('code' => '200', 'msg' =>"别着急，优惠券于".$activeInfo["startTime"]."开抢", 'needAlert'=>'1');
        }elseif(time() > strtotime($activeInfo["endTime"])){
            return  array('code' => '200', 'msg' =>"来晚啦，优惠券已被抢光了，去看看其他活动", 'needAlert'=>'1');
        }

        /* 活动配置start */
        $share_p_card_number = $activeInfo['product_id'].'-';//优惠券前缀
        $share_remarks = $activeInfo['remarks']?$activeInfo['remarks']:"仅限购买".$activeInfo['name'];

        /* 判断是否含有有效优惠券start */
        $card_limit_num = $this->get_user_card_num($uid, $now_date, $share_remarks);
        if ($card_limit_num > 0) {
            return  array('code' => '200', 'msg' =>"果园君掐指一算，您的账户里已存在1张".$share_remarks."优惠券，优惠券被使用之后再来分享就能领啦！", 'needAlert'=>'1');
        }
        $card_money = 0;
        $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);

//        if($activeInfo['product_id'] == 11244 || $activeInfo['product_id'] == 11243 ){
//            $card_money = $this->yingtao_card($uid, $activeId, $rating, $userInfo['user_rank']);
//            $share_card_number = 'A'.$share_card_number;
//        }
        if($card_money == 0){
            /* 判断是否含有有效优惠券end */
            if($activeInfo['cardRule'] == 1){
                //获取购买次数
                $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = {$activeInfo["product_id"]} and o.operation_id !=5 and o.order_type=1";
                $bought_num = $this->db->query($sql)->row_array();
                $number = $bought_num['bought'] >3 ? 3 : $bought_num['bought'];
            }elseif($activeInfo['cardRule'] == 2){
                //vip等级
                $this->db->select('id,user_rank');
                $this->db->from('user');
                $this->db->where('id', $uid);
                $userInfo = $this->db->get()->row_array();
                $userInfo['user_rank'] = $userInfo['user_rank']>1?$userInfo['user_rank']:1;
                $number = $userInfo['user_rank']>6?5:$userInfo['user_rank']-1;
                if($number ==0 ){//判断是否有过购买纪录
                    $sql = "select count(*) as bought from  ttgy_order o  where o.uid={$uid} and o.operation_id !=5 and o.order_type=1 and o.pay_status=1 and o.order_status=1";
                    $bought_num = $this->db->query($sql)->row_array();
                    if($bought_num['bought'] ==0){
                        $number = 98;//注册未购买
                    }
                }
            }

            //获取优惠券比例
            $this->db->select('*');
            $this->db->from('active_proportion');
            $this->db->order_by('card_money', 'asc');
            $this->db->where(array('activeId'=>$activeId, "number" => $number, 'proportion >'=>0));
            $rule = $this->db->get()->result_array();
            if($rating == 'M' && count($rule)>1){
                array_pop($rule);//中等风险的用户不能领取最高的券
            }
            $ruleArr = array();
            $i=0;
            foreach($rule as $k=>$v){
//            if($v['proportion']==0){
//                continue;
//            }
                $i += $v['proportion']*1000;
                $ruleArr[$i] = $v['card_money'];
            }
            $bingo = rand(1, $i);
            /* 获取对应金额的优惠券start */
            foreach ($ruleArr as $key => $value) {
                if ($bingo <= $key) {
                    //判断次面额优惠券是否还有
                    if($this->check_card_num($activeId, $value)){
                        $card_money = $value;
                    }else{
                        $card_money = reset($ruleArr);//返回第一个
                    }
                    break;
                }
            }
            if( $rating == "H" ){//高危手机号， 给最低的券
                $card_money = reset($ruleArr);
            }
        }

        if( !$card_money ){//$ruleArr高并发可能为空
            return  array('code' => '200', 'msg' =>"果园君掐指一算，您不在此活动的范围内，可以浏览其他活动", 'needAlert'=>'1');
        }
        /* 获取对应金额的优惠券end */

        /* 有效期计算start */
        $this->db->select_max('sendtime');
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
        $max_time = $this->db->get()->row_array();
        if (!empty($max_time['sendtime'])) {
            if ($max_time['sendtime'] >= $now_date) {
                $card_time = date("Y-m-d", strtotime("+1 day"));
            } else {
                $card_time = $now_date;
            }
        } else {
            $card_time = $now_date;
        }
        if($activeId==409 && $card_time=='2016-11-12'){
            return  array('code' => '200', 'msg' =>"你已经领过三个蜜柚组合双十一优惠券了");
        }
        $this->db->trans_begin();
        if($maketing == 0 && $activeInfo['relation_product_id']){
            $card_product_id = $activeInfo["product_id"].','.$activeInfo['relation_product_id'];
        }else{
            $card_product_id = $activeInfo["product_id"];
        }
            /* 有效期计算end */
        $card_data = array(
            'uid' => $uid,
            'sendtime' => date("Y-m-d"),
            'card_number' => $share_card_number,
            'card_money' => $card_money,
            'product_id' => $card_product_id,
            'maketing' => $maketing,
            'is_sent' => '1',
            'restr_good' => '1',
            'remarks' => $share_remarks,
            'time' => $card_time,
            'to_date' => $card_time,//$card_to_date,
            'can_use_onemore_time' => 'false',
            'can_sales_more_times' => 'false',
            'card_discount' => 1,
            'order_money_limit' => 0,
            'return_jf' => 0,
            'direction' => $share_remarks,
            //全站使用
            'channel' => ''
        );
        /* 发优惠券start */
        $this->db->insert('card', $card_data);
        if($activeId == 453){//发两张
            $share_card_number2 = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $card_data['card_number'] = $share_card_number2;
            $this->db->insert('card', $card_data);
            $this -> addCardType($share_card_number2, '商品专用券',0,'B2C运营部');
        }
        /*数据统计start*/
        $view_data = array(
            'type' => $activeInfo["product_id"].'_share',
            'tag' => $uid,
            'time' => date("Y-m-d H:i:s")
        );
        $log_num_data = array(
            'active_tag' => 'active_'.$activeId,
            'uid' => $uid,
            'time' => date("Y-m-d H:i:s")
        );
        $this->db->insert('cherry_view', $view_data);
        $this->db->insert('share_num',$log_num_data);
        $param = array(
            'uid' => $uid ,
            'rating' => $rating ,
            'card_money' => $card_money,
            'addtime' => date("Y-m-d H:i:s"),
            'active_id' => $activeId
        );
        $this->db->insert('active_card_log', $param);//记录领券log
        $this -> addCardType($share_card_number, '商品专用券',0,'B2C运营部');
        /*数据统计end*/
        /* 发优惠券end */
//        $card_limit_num2 = $this->get_user_card_num($uid, $now_date, $share_remarks);
//        if ($card_limit_num2 > 1) {
//            $this->db->trans_rollback();
//            return  array('code' => '200', 'msg' =>"果园君掐指一算，您的账户里已存在1张".$share_remarks."优惠券，优惠券被使用之后再来分享就能领啦！");
//        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return  array('code' => '200', 'msg' =>"果园君掐指一算，您的账户里已存在1张".$share_remarks."优惠券，优惠券被使用之后再来分享就能领啦！", 'needAlert'=>'1');
        } else {
            $this->db->trans_commit();
        }
        $shar_desc = '恭喜您获得' . $card_money . '元'.$share_remarks.'优惠券，优惠券仅限当日有效，请尽快使用哦！';

        return array('code' => '200', 'msg' =>$shar_desc, 'needAlert'=>'1');
    }

    //判断此活动优惠券是否有库存
    public function check_card_num($activeId, $card_money){
        $this->db->select('*');
        $this->db->from('active_card_num');
        $this->db->where(array("activeId" => $activeId, "card_money" => $card_money));
        $tmp = $this->db->get()->row_array();
        if(empty($tmp)){
            return true;//为空表示不限制数量
        }
        if($tmp['card_max_num']>0){
            $sql ="UPDATE `ttgy_active_card_num` SET `card_max_num` = ".intval($tmp['card_max_num']-1)." WHERE `id` = ".$tmp['id'];
            return $this->db->query($sql);
        }else{
            return false;
        }
    }

    /*橙先生投票*/
    function active_1202_card($uid)
    {
        $now_date = date("Y-m-d");
        /* 活动配置start */
        $active_product_id = '6952'; //2660
        $share_remarks = "橙先生10元优惠券";
        $this->db->trans_begin();
        /* 判断是否含有有效优惠券start */
        $this->db->from('card');
        $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
        $this->db->order_by('id', 'DESC');
        $card_limit_arr = $this->db->get()->result_array();
        $card_limit_num = count($card_limit_arr);
        if ($card_limit_num > 0) {
            return false;
        }
        $send_card = true;
        /* 判断是否含有有效优惠券end */
        if ($send_card) {

            $share_p_card_number = '6952_';
            $share_card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $card_money = 10;
            /* 有效期计算start */
            $this->db->select_max('sendtime');
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'remarks' => $share_remarks));
            $max_time = $this->db->get()->row_array();
            if (!empty($max_time['sendtime'])) {
                if ($max_time['sendtime'] >= $now_date) {
                    $card_time = date("Y-m-d", strtotime("+1 day"));
                } else {
                    $card_time = $now_date;
                }
            } else {
                $card_time = $now_date;
            }
            /* 有效期计算end */

            $channel = serialize(array("2"));
            $card_data = array(
                'uid' => $uid,
                'sendtime' => date("Y-m-d"),
                'card_number' => $share_card_number,
                'card_money' => $card_money,
                'product_id' => $active_product_id,
                'maketing' => '0',
                'is_sent' => '1',
                'restr_good' => '1',
                'remarks' => $share_remarks,
                'time' => $card_time,
                'to_date' => $card_time,//$card_to_date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'order_money_limit' => 0,
                'return_jf' => 0,
                // 'black_user_list'=>'',
                //全站使用
                'channel' => ''
            );
            /* 发优惠券start */
            $this->db->insert('card', $card_data);
            /* 发优惠券end */
            $this->db->from('card');
            $this->db->where(array('uid' => $uid, 'is_used' => 0, 'to_date >=' => $now_date, 'remarks' => $share_remarks));
            $card_limit_num2 = $this->db->get()->num_rows();
            if ($card_limit_num2 > 1) {
                $this->db->trans_rollback();
                return false;
            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
            }
        }
        return array("card_money" => $card_money, "yt_name" => '');
    }

    function active_1202_vote($uid,$type){
            $this->db->select('id,vote,user_list');
            $this->db->from('vote');
            $this->db->where(array('vote_type' => $type, 'product_id' => 6952));
            $result_vote = $this->db->get()->row_array();
            if(empty($result_vote)){
                //票数新增
                $voteData = array(
                    'vote_type'=>$type,
                    'product_id'=>6952,
                    'vote'=>1,
                    'user_list'=>$uid,
                    'date'=>date("Y-m-d H:i:s"),
                    'edit_date'=>date("Y-m-d H:i:s")
                );
                $this->db_master->insert('vote',$voteData);
                $vote = 1;
            }else{

                $new = $result_vote['vote']+1;
                $sql ="UPDATE `ttgy_vote` SET `vote` = ".$new." WHERE `vote_type` = ".$type." AND `product_id` = 6952";
                $res = $this->db_master->query($sql);
                if ($res) {
                    $vote = $result_vote['vote']+1;
                } else {
                    $vote = 'none';
                }
            }
        return array('vote' => $vote);
    }

    function active_o2o_xmas($uid){
        $nowDate = date("Y-m-d",time());
        $this->db->select('id');
        $this->db->from('o2o_xmas_share');
        $this->db->where(array('uid' => $uid, 'share_date' => $nowDate));
        $result_share = $this->db->get()->row_array();
        //var_dump($result_share);exit;
        if(empty($result_share)){
            //分享新增
            $shareData = array(
                'uid'=> $uid,
                'ctime'=> time(),
                'share_date'=> $nowDate,
            );
            $this->db_master->insert('o2o_xmas_share',$shareData);
        }else{
            return false;
        }
        return true;
    }

	function setUniqueCode()
    {
        $this->uniquecode = $this->doUniqueCode();
    }

    private function doUniqueCode()
    {
        $rand = $this->randstr();
        $user = $this->getUserBy( array("randcode"=>$rand ) );

        if( empty($user) )
            return $rand;
        else
            $this->doUniqueCode();
    }

	function getUserBy($condition)
    {
        if( empty( $condition ) )
            return array("error"=>1,"msg"=>"缺少条件");

        return $this->db->get_where(
            "user",
            $condition
        )->result();
    }

	function randstr($len = 8)
	{
		$rand = "";
		$pool = array("1","2","3","4","5","6","7","8","9","0",
			"a","b","c","d","e","f","g",
			"h","i","j","k","l","m","n",
			"o","p","q","r","s","t","u",
			"v","w","x","y","z");

		for($i=0; $i < $len; $i++)
			$rand .= $pool[array_rand($pool)];

		return $rand;
	}

	function saveItem($uid,$email)
    {
        $items = array();
        $this->email = $email;
		$this->user_id = $uid;
        $user = $this->getUser($uid);

        if( $this->nickname )
            $items['username'] = $this->nickname;

        if( $this->email ) {
            $items['email'] = $this->email;

        }

        if(  $this->emailStatus )
            $items['email_status'] = 1;

        if( $this->mobile ) {
            $items['mobile'] = $this->mobile;

            if( ! $user->mobile_status )
                $items['mobile_status'] = 1;
        }

        if( $this->operation == "unbind_mobile" )
            $items['mobile'] = "";

        if( $this->uniquecode && $this->email && $user->randcode == "")
            $items['randcode'] = $this->uniquecode;

        if( $this->avatar ){
            $items['user_head'] = serialize($this->avatar);
            $items['is_pic_tmp'] = 0;
        }

        if( $this->password )
            $items['password'] = md5($this->password);

        if( count($items) > 0 ) {

            try {
                $this->db->where("id", $this->user_id);
                if( $this->db->update(
                    "user",
                    $items
                ) ) {
                    return array("error"=>0,"msg"=>"修改成功");
                }

            } catch ( Exception $e) {
                log_message('error', $e->getMessage());
                return array("error"=>1,"msg"=>$e->getMessage());
            }

        } else
            return array("error"=>1,"msg"=>"无信息需要保存");
    }

    /*
    *绑定uid-registration_id
    */
    function bind_registration($uid,$device_id){
    	$items['uid'] = $uid;
    	$this->db->where("device_id", $device_id);
        $this->db->update("user_mobile_data",$items);
    }

    /**
     * 拜年礼活动
     */
    function gift_wqb_active($uid)
    {
//        echo $uid;exit;
        $this->db->select('mobile');
        $this->db->from('user');
        $this->db->where('id',$uid);
        $user = $this->db->get()->row_array();
        $mobile = $user['mobile'];
        $time = date("Y-m-d");
        $cardActive_sql = "SELECT count(*) as cnt FROM `ttgy_wqbaby_active` WHERE `mobile` = '".$mobile."' and description='".$time."拜年礼'";
        $cardActive = $this->db->query($cardActive_sql)->row_array();
//        if($cardActive){
        if($cardActive['cnt']==1){ //第一次领取赠品
            $wq_gift_sql = "select active_tag from ttgy_wqbaby_active where active_type = 2 and `mobile` = '".$mobile."' and description='".$time."拜年礼'";
            $wq_gift = $this->db->query($wq_gift_sql)->row_array();
            $tag = $wq_gift['active_tag'];
            $gift_sql = "select * from ttgy_gift_send where tag = '".$tag."'";
            $gift = $this->db->query($gift_sql)->row_array();
            $giftId = $gift['product_id'];
            $userGift = '';
            if($giftId==8332){
                $userGift = '【拜年礼物】法国姬娜3个（满188元随单领取）';
            }else if($giftId==8333){
                $userGift = '【拜年礼物】越南火龙果2个（满188元随单领取）';
            }else if($giftId==8334){
                $userGift = '【拜年礼物】阿克苏苹果1斤（满188元随单领取）';
            }else if($giftId==8335){
                $userGift = '【拜年礼物】新疆库尔勒香梨8个（满188元随单领取）';
            }else{
                $userGift = '【拜年礼物】赣南脐橙2斤（满188元随单领取）';
            }
            $user_gift_sql = "select count(*) as cnt from ttgy_user_gifts where uid=".$uid." and active_id = ".$giftId." and time >= '".date('Y-m-d 00:00:00')."' and time <= '".date('Y-m-d 23:59:59')."'";
            $user_gift = $this->db->query($user_gift_sql)->row_array();
            if($user_gift['cnt'] <= 0){
            	$gift_send = $gift;
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
                    'active_id' => $gift['id'],
                    'active_type' => '2',
                    'has_rec' => '0',
                    'start_time'=>$gift_start_time,
	                'end_time'=>$gift_end_time,
                );
                $this->db->insert('user_gifts', $user_gift_data);
                $desc = array("error"=>0,"msg"=>"太棒啦！".$userGift."已经绑定您的账户，可能会有几分钟的延迟，请稍后查看哦～");
            }else{
                $desc = array("error"=>1,"msg"=>"礼品已经领取过了哦");
            }
        }else if($cardActive['cnt']>=2){
            $time = date("Y-m-d");
            $start = date('Y-m-d 00:00:00');
            $end = date("Y-m-d 23:59:59");
            $wq_sql = "select b.* FROM `ttgy_wqbaby_active` a LEFT JOIN `ttgy_gift_send` b on a.`active_tag`= b.`tag` WHERE a.`active_type`= 2 and a.`mobile`= '".$mobile."' and a.`description` = '".$time."拜年礼'";
            $wq_gift = $this->db->query($wq_sql)->row_array();
            $wq_giftId = $wq_gift['product_id'];
            $gift = array('uid'=>$uid,'active_id'=>$wq_gift['id'], 'time >=' => $start, 'time <' => $end);
            $giftCount = $this->db->from('user_gifts')->where($gift)->count_all_results();
            if($giftCount>0){ //大于0时 已领取赠品 则查看card是否领取
                $card_sql = "select a.card_number,a.card_money from ttgy_wqbaby_active a left join ttgy_card b on a.card_number=b.card_number where a.active_type = 1 and a.`mobile` = '".$mobile."' and  description='".$time."拜年礼'";
                $card = $this->db->query($card_sql)->row_array();
                $card_where = array('uid'=>$uid,'card_number'=>$card['card_number'], 'time >=' => $start, 'time <' => $end);
                $cardCount = $this->db->from('card')->where($card_where)->count_all_results();
                if($cardCount>0){
                    $desc = array("error"=>1,"msg"=>"已经领过优惠券了哦");
                }else{
                    //券的信息
                    $date = date("Y-m-d", time());
                    $channel = '';
                    if($card['card_money']==5){
                        $order_money_limit = 0;
                        $product_id = '';
                        $cardDesc = '5元优惠券';
                        $share_remarks='拜年礼物—5元无门槛（不包含天天到家）';
                    }elseif($card['card_money']==10){
                        $order_money_limit = 100;
                        $product_id = '';
                        $share_remarks='拜年礼物—10元优惠券（满100使用，不包含天天到家）';
                        $cardDesc = '10元优惠券';
                    }elseif ($card['card_money']==20) {
                        $order_money_limit = 188;
                        $product_id = '7913,7912,2215,4478,643,3571,1775,3852,2052,837,2256,2258,2147,2259,1633,565,8186,8185,8184,8183,8182,8181,7914,7893,7673,7672,7671,7670,7667,7665,7664,7663,7662,7661,7660,7659,7658,7657,7656,7655,7654,7653,7652,7651,7650,7649,7648,7647,7645,7644,5441,5247,5243,5239,5234,5230,5227,5224';
                        $share_remarks='拜年礼物—礼盒券（仅限礼盒，满188使用）';
                        $cardDesc = '20元礼盒券';
                    }
                    $cardData = array(
                        'uid' => $uid,
                        'sendtime' => $date,
                        'card_number' => $card['card_number'],
                        'card_money' => $card['card_money'],
                        'product_id' => $product_id,
                        'maketing' => '0', //天天到家优惠券1  0 普通券
                        'is_sent' => 1,
                        'restr_good' => '0', //是否指定商品id  1为指定,需要给定上面的product_id。。0为不指定，product_id为空
                        'remarks' => $share_remarks,
                        'time' => $date,
                        'to_date' =>$date,//只能当天使用 //date("Y-m-d", strtotime("+1day")),
                        'can_use_onemore_time' => 'false',
                        'can_sales_more_times' => 'false',
                        'card_discount' => 1,
                        'order_money_limit' => $order_money_limit, //满减可以使用
                        'return_jf' => 0,
                        'channel' => $channel
                    );
                    $this->db->insert('card', $cardData);
                    $desc = array("error"=>0,"msg"=>"太棒啦！".$cardDesc."已经绑定您的账户，可能会有几分钟的延迟，请稍后查看哦～");
                }
            }else{
                //赠品的信息
                $userGift = '';
                if($wq_giftId==8332){
                    $userGift = '【拜年礼物】法国姬娜3个（满188元随单领取）';
                }else if($wq_giftId==8333){
                    $userGift = '【拜年礼物】越南火龙果2个（满188元随单领取）';
                }else if($wq_giftId==8334){
                    $userGift = '【拜年礼物】阿克苏苹果1斤（满188元随单领取）';
                }else if($wq_giftId==8335){
                    $userGift = '【拜年礼物】新疆库尔勒香梨8个（满188元随单领取）';
                }else{
                    $userGift = '【拜年礼物】赣南脐橙2斤（满188元随单领取）';
                }
                $gift_send = $wq_gift;
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
                    'active_id' => $wq_gift['id'],
                    'active_type' => '2',
                    'has_rec' => '0',
                    'start_time'=>$gift_start_time,
	                'end_time'=>$gift_end_time,
                );
                $this->db->insert('user_gifts', $user_gift_data);
                $desc = array("error"=>0,"msg"=>"太棒啦！".$userGift."已经绑定您的账户，可能会有几分钟的延迟，请稍后查看哦～");
            }
        }else{
            //错误
            $desc = array("error"=>1,"msg"=>"没有参加抢年礼活动");
        }
        return $desc;
    }

	/**
	 * 获取后台配置
	 * @param $inviteUid
	 * @return
	 */
	public function getPrizeById($inviteUid = 0) {
		$today = date('Y-m-d H:i:s');
		$where = array('reg_type' => '自己注册', 'is_open' => '是', 'start_time <=' => $today, 'end_time >' => $today);
		if ($inviteUid > 0) {
			$where['reg_type'] = '邀请注册'; //邀请注册条件
			$openCount = $this->db->from('newuser_prize')->where($where)->count_all_results(); //后台是否开启邀请注册
			if ($openCount == 0) {//没有开启默认为自己注册
				$where['reg_type'] = '自己注册';
			}
		}
		$prizeRow = $this->db->select('id,message')->from('newuser_prize')->where($where)->order_by('start_time', 'desc')->get()->row_array();
		return $prizeRow;
	}

	/**
	 * 获取后台券卡信息
	 * @param $prizeId
	 * @return
	 */
	public function getPrizeDetailByPrizeid($prizeId) {
		$prizeDetail = $this->db->from('newuser_prize_detail')->where('prize_id', $prizeId)->get()->result_array();
		return $prizeDetail;
	}

	/**
	 * 获取后台赠品信息
	 * @param $activeTagArr
	 * @return array
	 */
	public function getGiftSend($activeTagArr) {
		if (empty($activeTagArr)) {
			return array();
		}
		$result = $this->db->select('id')->from('gift_send')->where_in('tag', $activeTagArr)->get()->result_array();
		$active = array();
		foreach ($result as $val) {
			array_push($active, $val['id']);
		}
		return $active;
	}

	/**
	 * 获得后台优惠券信息
	 * @param $cardTagArr
	 * @return array
	 */
	public function getMobileCard($cardTagArr) {
		if (count($cardTagArr) <= 0) {
			return array();
		}
		$today = date('Y-m-d');
		$card_arr = array();
		foreach ($cardTagArr as $val) {
//			$where = array('card_tag' => $val['tag'], 'time <=' => $today, 'to_date >=' => $today);
			$where = array('card_tag' => $val['tag']);
			$mobile_card = $this->db->select('card_money,product_id,restr_good,remarks,card_to_date,order_money_limit,p_card_number,channel,department,card_tag,card_m_type,promotion_type,direction')->from('mobile_card')->where($where)->get()->row_array();
			if (empty($mobile_card)) {
				continue;
			}
			if ($val['use_end'] > 0) {
				$mobile_card['use_start'] = $val['use_start'];
				$mobile_card['use_end'] = $val['use_end'];
			}
			array_push($card_arr, $mobile_card);
		}
		return $card_arr;
	}

	/**
	 * 注册送优惠券
	 */
	public function newSendCard($uid, $cardList) {
		if (empty($cardList)) {
			return FALSE;
		}
		$cardArr = array();
		foreach ($cardList as $val) {
			$share_p_card_number = empty($val['p_card_number']) ? 'register' : $val['p_card_number'];
			$card_number = $share_p_card_number . $this->rand_card_number($share_p_card_number);
			$product_id = isset($val['product_id']) ? $val['product_id'] : '';
			$restr_good = isset($val['restr_good']) ? $val['restr_good'] : '0';
			$order_money_limit = isset($val['order_money_limit']) ? $val['order_money_limit'] : $val['moneyLimit'];
			$channel = isset($val['channel']) ? $val['channel'] : '';
			$time = date('Y-m-d');
			$to_date = $val['card_to_date'];
			if (isset($val['use_end'])) {
				$time = date('Y-m-d', strtotime(($val['use_start'] - 1) . ' days'));
				$to_date = date('Y-m-d', strtotime(($val['use_end'] - 1) . ' days'));
			}
			$card_data = array(
				'uid' => $uid,
				'sendtime' => date("Y-m-d"),
				'card_number' => $card_number,
				'card_money' => $val['card_money'],
				'product_id' => $product_id,
				'maketing' => 0,
				'is_sent' => 1,
				'restr_good' => $restr_good,
				'remarks' => $val['remarks'],
				'time' => $time,
				'to_date' => $to_date,
				'can_use_onemore_time' => 'false',
				'can_sales_more_times' => 'false',
				'card_discount' => 1,
				'order_money_limit' => $order_money_limit,
				'return_jf' => 0,
				// 'black_user_list'=>'',
				'channel' => $channel,
				'promotion_type' => $val['promotion_type'],
                'direction' => $val['direction'],
			);
			array_push($cardArr, $card_data);
			$this->addCardType($card_number,$val['card_m_type'],0,$val['department'],$val['card_tag']);
		}
		$flag = $this->db->insert_batch('card', $cardArr);
		if (!$flag) {
			$this->load->library('fdaylog');
			$db_log = $this->load->database('db_log', TRUE);
			$this->fdaylog->add($db_log, 'zyr_reg_card', $uid . ':' . date('Y-m-d H:i:s'));
		}
	}

	public function sendJf($jf, $uid) {
		if ($jf === 0) {
			return FALSE;
		}
		$data = array(
			'jf' => $jf,
			'reason' => '新客注册礼',
			'time' => date('Y-m-d H:i:s'),
			'uid' => $uid
		);
		$this->db->insert('user_jf', $data);
		$this->updateJf($uid,$jf,1);
	}

    function check_money_identical($uid=0){
        $sql = "select money from ttgy_user where id = $uid";
        $query = $this->db->query($sql);
        $result = $query->result();
        $money = $result[0]->money;

        $sql = "select sum(money+bonus) as money from ttgy_trade where uid = $uid and has_deal=1";
        $query = $this->db->query($sql);
        $result = $query->result();
        $sum_money = $result[0]->money;
        if(!$sum_money) {
            $sum_money = 0.00;
        }

        if($money == $sum_money) {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    function freeze_user($uid=0) {
        $time = date("Y-m-d H:i:s");
        $this->db->query("UPDATE ttgy_user set freeze = 1,freeze_time='{$time}' where id = $uid");
    }



    /**
     * 余额支付扣款
     *
     * @return void
     * @author
     * */
    public function deposit_charge($uid, $money)
    {
        // $rs = $this->db->update('user',array('money'=>'money+'.$money),array('id'=>$uid));
        $rs = $this->db->set('money', 'money-' . $money, false)->where('id', $uid)->update('user', null, null, 1);

        return $rs ? $this->db->affected_rows() : 0;
    }

    //分享领赠品
    function getGifts308($uid, $tag){

        $data = date("Y-m-d");
        switch($data){
            case '2016-02-29':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'S2wKA1',
                        'active_id'=>2062
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'Ei0zQ1',
                        'active_id'=>2063
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'aHsul',
                        'active_id'=>2064
                    )
                );
                break;
            case '2016-03-01':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'S2wKA1',
                        'active_id'=>2062
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'Ei0zQ1',
                        'active_id'=>2063
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'aHsul',
                        'active_id'=>2064
                    )
                );
                break;
            case '2016-03-02':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'cILPX',
                        'active_id'=>2065
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'zGADu2',
                        'active_id'=>2066
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'KFYFa3',
                        'active_id'=>2067
                    )
                );
                break;
            case '2016-03-03':
                $gift_config =  array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'XpjfD',
                        'active_id'=>2068
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'Ulzdy',
                        'active_id'=>2069
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'BVpsJ1',
                        'active_id'=>2070
                    )
                );
                break;
            case '2016-03-04':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'AvadY2',
                        'active_id'=>2071
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'NTOGm1',
                        'active_id'=>2072
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'R3PVl3',
                        'active_id'=>2073
                    )
                );
                break;
            case '2016-03-05':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'NfDEO1',
                        'active_id'=>2074
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'ZjzMP',
                        'active_id'=>2075
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'W8qE92',
                        'active_id'=>2076
                    )
                );
                break;
            case '2016-03-06':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'tuVGJ',
                        'active_id'=>2077
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'dL564',
                        'active_id'=>2078
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'GNrcr',
                        'active_id'=>2079
                    )
                );
                break;
            case '2016-03-07':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'HtB0T2',
                        'active_id'=>2080
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'Vvwa73',
                        'active_id'=>2081
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'DM1Na4',
                        'active_id'=>2082
                    )
                );
                break;
            case '2016-03-08':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'0g8dn',
                        'active_id'=>2083
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'RACCs1',
                        'active_id'=>2084
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'GFonU',
                        'active_id'=>2085
                    )
                );
                break;
            case '2016-03-09':
                $gift_config = array(
                    0=>array(
                        'product_id'=>'9260',
                        'tag'=>'Iq9g02',
                        'active_id'=>2099
                    ),
                    1=>array(
                        'product_id'=>'8844',
                        'tag'=>'bE7Wp3',
                        'active_id'=>2100
                    ),
                    2=>array(
                        'product_id'=>'7954',
                        'tag'=>'NbYUz2',
                        'active_id'=>2101
                    )
                );
                break;
            default :
                $gift_config = array();
                break;
        }
        if(empty($gift_config)){
            return array('msg'=>'来晚啦，活动已结束，请参加其他活动');
        }
        $active_ids = $activeId2productId = array();
        foreach($gift_config as $value){
            $active_ids[] = $value['active_id'];
            $activeIdtag[$value['tag']] = $value;
        }
        if(!$activeIdtag[$tag]){
            return array('msg'=>'果园君小憩一会儿~');
        }
        $this->db->from('user_gifts')->where(array(
            'uid'=>$uid,
            'has_rec'=>0,
            'active_type' => 2,
        ))->where_in('active_id',$active_ids);
        $today_can_get = $this->db->get()->row_array();
        if(empty($today_can_get)){
            $gift_send = $this->db->select('*')->from('gift_send')->where('id', $activeIdtag[$tag]['active_id'])->get()->row_array();
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
            /* 优惠券设定start */
            $share_card_data = array(
                'uid' => $uid,
                'active_id' => $activeIdtag[$tag]['active_id'],
                'active_type' => 2,
                'has_rec' => 0,
                'start_time'=>$gift_start_time,
	            'end_time'=>$gift_end_time,
            );
            /* 优惠券设定end */
            $this->db->insert('user_gifts', $share_card_data);
            $product_info = $this->db->select('product_name')->from("product")->where('id', $activeIdtag[$tag]['product_id'])->get()->row_array();
            $gift_product_name = $product_info['product_name'];
            $retuan_arr['msg'] =  '太棒辣！'.$gift_product_name.'已经在您的赠品中，据说马上下单带走会有好运哦';
        }else{
            $retuan_arr['msg'] = '小主已经拿到赠品啦，下单后可以再次分享领取哦~~';
        }
        return $retuan_arr;
    }


	/*
     *获取发票抬头列表
     */
     function get_invoice_title_list($uid){
        $this->db->distinct();
        $this->db->select('name');
        $this->db->from('trade_invoice');
        $this->db->where(array('uid'=>$uid,'name !='=>'','name !='=>'个人'));
        $result = $this->db->get()->result_array();
        return $result;
     }

	/*
	 *摇一摇
	 */
	function add_shake_exchange_v3($uid){
		$date = date('Y-m-d');
		$result = $this->db->select("count(*) as num")->from("shake_exchange")->where(array(
			"time"=>$date,
			"uid"=>$uid
		))->get()->row_array();
		if($result["num"]<3){
			$data = array(
				'uid'=>$uid,
				'time'=>$date
			);
			$a = $this->db->insert("shake_exchange",$data);
			return $a;
		}
	}

     /*
     *获取发票抬头列表
     */
     function getTradeInvoice($params){
        if( empty($params['uid']) || empty($params['trade_no']) ){
            return false;
        }
        $this->db->from('trade_invoice');
        $this->db->where(array('uid'=>$params['uid'],'invoice_content like'=>'%'.$params['trade_no'].'%'));
        $result = $this->db->get()->result_array();
        return $result;
     }


     /*获取大客户收款列表*/
     public function getComplany($uid){
     	$this->db->select('out_trade_number,money,pay_status,uid');
     	$this->db->from('complany_service');
     	$this->db->where('uid',$uid);
     	$this->db->order_by('id','desc');
     	$list = $this->db->get()->result_array();
     	return $list;
     }

     public function getOneComplany($out_trade_number){
     	$this->db->select('out_trade_number,money,pay_status,uid');
     	$this->db->from('complany_service');
     	$this->db->where('out_trade_number', $out_trade_number);
     	$this->db->limit(1);
     	$list = $this->db->get()->row_array();
     	return $list;
     }

     public function updateComplanyStauts($uid,$out_trade_number){
     	$this->db->select('pay_status,id');
     	$this->db->from('complany_service');
     	$this->db->where('out_trade_number', $out_trade_number);
     	$this->db->limit(1);
     	$list = $this->db->get()->row_array();

     	if (empty($list)) {
     		return array('code'=>300,'msg'=>'传入信息错误');
     	} elseif($list['pay_status']==1){
     		return array('code'=>200,'msg'=>'支付成功');
     	} elseif($list['pay_status']==0) {
     		$data = array('pay_status'=>2);
     		$this->db->update('complany_service',$data,array('uid'=>$uid,'out_trade_number'=>$out_trade_number));
     		$num = $this->db->affected_rows();
     		if ($num==1) {
     			return array('code'=>200,'msg'=>'更新成功');
     		} else {
     			return array('code'=>300,'msg'=>'更新失败');
     		}
     	} else {
     		//todo...
     	}
     }

    public function get_mother_gift($uid){
        $data = date("Y-m-d");
        if($data == '2016-05-06'){
            $config = array(
                'product_id'=>'10527',
                'tag'=>'nhWNB2',
                'active_id'=>2495
            );
        }elseif($data == '2016-05-07'){
            $config = array(
                'product_id'=>'10527',
                'tag'=>'5ENRL3',
                'active_id'=>2496
            );
        }elseif($data == '2016-05-08'){
            $config= array(
                'product_id'=>'10527',
                'tag'=>'V17US4',
                'active_id'=>2497
            );
        }elseif($data == '2016-05-09'){
            $config= array(
                'product_id'=>'10527',
                'tag'=>'JhFOO1',
                'active_id'=>2506
            );
        }elseif($data == '2016-05-10'){
            $config = array(
                'product_id'=>'10527',
                'tag'=>'PqkKR3',
                'active_id'=>2507
            );
        }elseif($data == '2016-05-11'){
            $config = array(
                'product_id'=>'10527',
                'tag'=>'pRKxq1',
                'active_id'=>2508
            );
        }else{
            $config = array(
                'product_id'=>'10527',
                'tag'=>'yCJLn',
                'active_id'=>2435
            );
        }

        $this->db->from('user_gifts')->where(array(
            'uid'=>$uid,
            'has_rec'=>0,
            'active_type' => 2,
        ))->where('active_id',$config['active_id']);
        $today_can_get = $this->db->get()->row_array();
        if(empty($today_can_get)){
        	$gift_send = $this->db->select('*')->from('gift_send')->where('id', $config['active_id'])->get()->row_array();
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
            /* 优惠券设定start */
            $share_card_data = array(
                'uid' => $uid,
                'active_id' => $config['active_id'],
                'active_type' => 2,
                'has_rec' => 0,
                'start_time'=>$gift_start_time,
	            'end_time'=>$gift_end_time,
            );
            /* 优惠券设定end */
            $this->db->insert('user_gifts', $share_card_data);
            $retuan_arr['msg'] =  '2瓶装莫斯利安2果3蔬酸奶已经绑定您的账户啦，据说马上下单带走会有好运哦';
        }else{
            $retuan_arr['msg'] = '您的账户中已经存在2瓶酸奶喽，下单带走后就能再来领啦。';
        }
        return $retuan_arr;
    }


    //樱桃活动优惠券概率
    public function yingtao_card($uid, $activeId, $rating='L', $user_rank=''){
        $uid =','.$uid.',';
        $card_money = 0;
        $ruby = array();
        include_once('application/config/ruby.php');
        foreach($ruby as $k=>$v){
            if(strpos($v, $uid)>0){
                $card_money = $k;
            }
        }
        return $card_money;

        $config = array(
            'A' => array(
                '5000'  => 30,
                '10000' => 40,
                '25000' => 50,
                '40000' => 55,
                '100000'=> 60
            ),
            'B' => array(
                '10000' => 40,
                '60000' => 50,
                '80000' => 55,
                '100000'=> 60
            ),
            'C' => array(
                '30000'  => 30,
                '70000' => 40,
                '80000' => 50,
                '90000' => 55,
                '100000'=> 60
            ),
            'D' => array(
                '40000'  => 30,
                '90000' => 40,
                '100000' => 50
            )
        );
        if(!$uid){
            $card_config_key = 'A';
        }else{
            $sql = "select (SUM(card_money)+SUM(pay_discount))/sum(goods_money) as btl, AVG(money) as money FROM ttgy_order o  where o.uid= ".$uid." and o.operation_id!= 5 and o.order_status= 1 and o.time>'2015-05-03'";
            $order_history = $this->db->query($sql)->row_array();
            $sql = "select count(DISTINCT(o.id)) as num FROM ttgy_order_product op join ttgy_order o on op.order_id= o.id where op.product_id in (2660, 3399, 3389, 3388, 3521, 4070, 4435, 4490, 4498, 4439, 4500, 4148, 4797, 8037, 8043, 10595, 10596, 7143) and o.uid= ".$uid." and o.operation_id!= 5 and o.order_status= 1";
            $order_num_arr     = $this->db->query($sql)->row_array();
            $order_num = intval($order_num_arr['num']);
            if(!$user_rank){
                $userInfo = $this->getUser($uid, null, 'id,user_rank');
                $user_rank = $userInfo['user_rank'];
            }
            $sql = "select time from ttgy_order where uid= ".$uid." and operation_id!= 5 and order_status= 1 order by id desc limit 1 ";
            $order_last    = $this->db->query($sql)->row_array();
            if($order_last){
                $last_time = strtotime($order_last['time']);//最后一次订单的时间
            }else{
                $last_time = strtotime('-91 days');
            }
            $time30 = strtotime('-30 days');
            $time90 = strtotime('-90 days');

            if($order_history['btl']<0.1 && $order_num>=4 && intval($order_history['money'])>=140 && $last_time>=$time30){
                $card_config_key = 'C';
            }elseif($order_history['btl']<0.1 && $order_num>=4 && intval($order_history['money'])>=140 && $last_time<=$time90){
                $card_config_key = 'A';
            }elseif($order_history['btl']<0.1 && $order_num>=4 && intval($order_history['money'])<=99 && $last_time>=$time90){
                $card_config_key = 'C';
            }elseif($order_history['btl']<0.1 && $order_num>=1 && $order_num <= 3 && intval($order_history['money'])>=140 && $last_time>=$time30){
                $card_config_key = 'C';
            }elseif($order_history['btl']>0.4 && $order_history['btl']<1 && $order_num>=1 && $order_num <= 3 && intval($order_history['money'])>=140 && $last_time<$time30){
                $card_config_key = 'A';
            }elseif($order_history['btl']>0.4 && $order_history['btl']<1 && $order_num>=1 && $order_num <= 3 && intval($order_history['money'])<=99 && $last_time<$time30){
                $card_config_key = 'D';
            }elseif($order_num==0 && intval($order_history['money'])>=140 && $last_time>=$time30){
                $card_config_key = 'C';
            }elseif($order_num==0 && intval($order_history['money'])>=140 && $last_time<=$time90){
                $card_config_key = 'A';
            }elseif($order_num==0 && $user_rank >=4 && intval($order_history['money'])>=160 && $last_time>=$time30){
                $card_config_key = 'C';
            }elseif($order_num==0 && $user_rank >=1 && $user_rank <=3 && intval($order_history['money'])<=99  && $last_time>=$time90){
                $card_config_key = 'C';
            }elseif($order_num==0 && $user_rank >=1 && $user_rank <=3 && intval($order_history['money'])<=99  && $last_time<=$time90){
                $card_config_key = 'D';
            }else{
                $card_config_key = 'B';
            }
        }
        $ruleArr = $config[$card_config_key];
        if($rating == "H"){
            return reset($ruleArr);//高危手机，返回最低的券
        }
        $bingo = rand(1, 100000);
        /* 获取对应金额的优惠券start */
        foreach ($ruleArr as $key => $value) {
            if ($bingo <= $key) {
                //判断次面额优惠券是否还有
                if($this->check_card_num($activeId, $value)){
                    return $value;
                }else{
                    return reset($ruleArr);//返回第一个
                }
            }
        }
        return reset($ruleArr);
    }


    /**
     * 根据手机获取用户信息
     * @param $mobile
     * @return
     */
    public function getUserInfoByMobile($mobile){
        $this->db->select('id,user_rank');
        $this->db->from('user');
        $this->db->where('mobile', $mobile);
        return $this->db->get()->row_array();
    }

    public function checkUserJf($uid){
    	$user_score = $this->getUserScoreNew($uid);
    	$real_jf = $user_score['jf'];
    	$jf = $this->db->select('jf')->from('user')->where(array('id'=>$uid))->get()->row_array();
    	$jf = $jf['jf']?$jf['jf']:0;
    	if(bccomp($real_jf,$jf,3) != 0){
    		$this->db->where(array('id'=>$uid));
		    $this->db->update("user", array('jf'=>$real_jf));
    	}
    	return $real_jf;
    }

    public function updateJf($uid,$jf,$type=1){ //1增加 2减少
    	$jf = abs($jf);
        if($type == 2){
            $jf_data = 'jf-'.$jf;
        }else{
        	$jf_data = 'jf+'.$jf;
        }
        $this->db->set('jf', $jf_data, false)->where('id', $uid)->update('user');
        $this->checkUserJf($uid);
    }
    /*
	 * 获取用户中心 － 订单统计数量
	 */
    public function showOrderNum($uid,$type)
    {
        if($type == 1)  //待付款
        {
            //order
            $this->db->from('order');
            $this->db->where(array('uid' => $uid, 'order_status' => '1', 'pay_status' => '0', 'pay_parent_id !=' => '4','show_status'=>'1'));
            $this->db->where_in('operation_id', array('0'));
            $this->db->where_in('order_type',array('1','2','3','4','5','7','13','9','10','14'));
            $query = $this->db->get();
            $order_num = $query->num_rows();

            //b2o
            $this->db->from('b2o_parent_order');
            $this->db->where(array('uid' => $uid, 'order_status' => '1', 'pay_status' => '0', 'pay_parent_id !=' => '4','show_status'=>'1','p_order_id'=>'0'));
            $this->db->where_in('operation_id', array('0'));
            $this->db->where_in('order_type',array('1','2','3','4','5','7','13','9','10','14'));
            $query = $this->db->get();
            $b2o_num = $query->num_rows();

            $num = $order_num + $b2o_num;
        }
        else if($type == 2)  //待发货
        {
            $this->db->from('order');
            $this->db->where(array('uid' => $uid, 'order_status' => '1','pay_status' => '1','show_status'=>'1'));
            $this->db->where_in('operation_id', array('0','1','4'));
            $this->db->where_in('order_type',array('1','2','3','4','5','7','13','9','14'));
            $on_query = $this->db->get();
            $on_num = $on_query->num_rows();

            $this->db->from('order');
            $this->db->where(array('uid' => $uid, 'order_status' => '1','pay_parent_id' => '4','show_status'=>'1'));
            $this->db->where_in('operation_id', array('0','1','4'));
            $this->db->where_in('order_type',array('1','2','3','4','5','7','13','9','14'));
            $off_query = $this->db->get();
            $off_num = $off_query->num_rows();

            $num = $on_num + $off_num;
        }
        else if($type == 3)   //待收货
        {
            $this->db->from('order');
            $this->db->where(array('uid' => $uid, 'order_status' => '1', 'pay_status' => '1','show_status'=>'1'));
            $this->db->where_in('operation_id', array('2'));
            $this->db->where_in('order_type',array('1','2','3','4','5','7','13','9','14'));
            $query = $this->db->get();
            $num = $query->num_rows();
        }
        else if($type == 4)  //待评价
        {
            $this->db->from('order');
            $this->db->where(array(
                'uid' => $uid,
                'order_status' => '1',
                //'order_type !=' => 8,
                'pay_status' => '1',
                'had_comment' => '0',
                'show_status'=> '1',
                'time >' => date("Y-m-d", strtotime('-' . $this->can_comment_period)),
            ));
            $this->db->where_in('operation_id', array('3','6','9'));
            $this->db->where_in('order_type',array('1','2','3','4','5','7','13','14'));
            $query = $this->db->get();
            $num = $query->num_rows();
        }
        else if($type == 5)   //申诉进行中-退款
        {
            $this->db->from('quality_complaints');
            $this->db->where(array('uid' => $uid, 'status' => '0'));
            $query = $this->db->get();
            $num = $query->num_rows();
        }
        else
        {
            $this->db->from('order');
            $this->db->where(array('uid' => $uid, 'order_status' => '1','show_status'=>'1'));

            $query = $this->db->get();
            $num = $query->num_rows();
        }

        return $num;
        }

    /*
     * 积分兑换 分享领赠品
     * */
    public function get_gifts_share($uid, $cms_id, $tag){
        if(!$cms_id || !$tag){
            return array('code' => '200', 'msg' => "很遗憾，商品已经兑换完了，去看看其他可兑换的商品.");
        }
        $key = $tag . '_' . $cms_id;
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        if($redis->exists("gifts:".$key)){
            $num = $redis->get("gifts:".$key);
        }else{
            return array('code' => '200', 'msg' => "很遗憾，商品已经兑换完了，去看看其他可兑换的商品~");
        }
        if($num<=0){
            return array('code' => '200', 'msg' => "很遗憾，商品已经兑换完了，去看看其他可兑换的商品!");
        }
        //判断赠品信息
        $this->db->select('*');
        $this->db->from('gift_send');
        $this->db->where('tag', $tag);
        $gifts_info =  $this->db->get()->row_array();
        if(empty($gifts_info)){
            return array('code' => '200', 'msg' => "很遗憾，商品已经兑换完了，去看看其他可兑换的商品！");
        }
        //判断用户是否有未使用的赠品
        $this->db->from("user_gifts");
        $this->db->where(array("uid" => $uid, "active_id" => $gifts_info['id']));
        $is_has_gifts = $this->db->get()->row_array();
        if($is_has_gifts){
            return array('code' => '200', 'msg' => '您已经兑换过了');
        }
        $this->db->trans_begin();
        $gift_send = $gifts_info;
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
            'active_id' => $gifts_info['id'],
            'active_type' => '2',
            'has_rec' => '0',
            'start_time'=>$gift_start_time,
	        'end_time'=>$gift_end_time,
        );
        $this->db->insert('user_gifts', $user_gift_data);

        if ($this->db->trans_status() === FALSE || $this->setRedisGifts($tag, $cms_id) === FALSE) {
            $this->db->trans_rollback();
            return array('code' => '200', 'msg' => '很遗憾，商品已经兑换完了，去看看其他可兑换的商品');
        } else {
            $this->db->trans_commit();
            return array('code' => '200', 'msg' => '兑换成功，请在我的赠品查看');
        }

    }

    //设置redis里面的赠品库存
    function setRedisGifts($tag, $cms_id){
        $key = $tag . '_' . $cms_id;
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        if($redis->exists("gifts:".$key)){
            $num = $redis->get("gifts:".$key);
        }else{
            return false;
        }
        if(!$num){
            return false;
        }
        return $redis->set("gifts:".$key, $num-1);
    }

    //分享成功后获得赠品
    function happySend($uid,$tage){
        $time = date("Y-m-d 00:00:00");
        $end = date("Y-m-d 23:59:59");
        $tag = '';
        if($tage=='hnxtn'){
            $tag = "小台农";
        }else if($tage=='fzxlz'){
            $tag = "妃子笑荔枝";
        }else if($tage=='hnmg'){
            $tag = "海南木瓜";
        }
        //查询当日赠品ID
        $gift_all_sql = "SELECT id,product_id,tag,remarks  FROM `ttgy_gift_send` WHERE `start` >= '".$time."' AND `end` <= '".$end."' AND `remarks` LIKE '%【任性三选一】老客%' ORDER BY `id` ASC ";
        $giftAll = $this->db->query($gift_all_sql)->result_array();
        if(empty($giftAll)){
            return array('code' => '200', 'msg' => '很遗憾，您选择的商品库存已空，去看看其他可选择的商品~');
        }
        $IdArr = array();
        foreach($giftAll as $key=>$val){
            $IdArr[] = $val['id'];
        }
        $idStr = implode(',', $IdArr);
        $uGift_sql = "SELECT count(*) as cnt  FROM `ttgy_user_gifts` WHERE `uid`=".$uid." AND `active_id` IN(".$idStr.") AND `time` >= '".$time."' AND `time` <= '".$end."' ";
        $uGift  = $this->db->query($uGift_sql)->row_array();

        if($uGift['cnt']>0){
            return array('code' => '200', 'msg' => '一天只能领取一次奖励哟，明天再约么么哒~');
        }
        $gift_sql = "SELECT id,product_id,tag,remarks  FROM `ttgy_gift_send` WHERE `start` >= '".$time."' AND `end` <= '".$end."' AND `remarks` LIKE '%".$tag."【任性三选一】老客%' ORDER BY `id` ASC ";
        $giftArr = $this->db->query($gift_sql)->row_array();

        if(empty($giftArr)){
            return array('code' => '200', 'msg' => '很遗憾，您选择的商品库存已空，去看看其他可选择的商品~');
        }
        $giftId = $giftArr['id'];
        $this->db->trans_begin();
        $gift_send = $this->db->select('*')->from('gift_send')->where('id', $giftId)->get()->row_array();
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
        $giftData = array(
            'uid' => $uid,
            'active_id' => $giftId,
            'active_type' => '2',
            'has_rec' => '0',
            'start_time'=>$gift_start_time,
	        'end_time'=>$gift_end_time,
        );
        $this->db->insert('user_gifts', $giftData);

        if ($this->db->trans_status() === FALSE ) {
            $this->db->trans_rollback();
            return array('code' => '200', 'msg' => '很遗憾，您选择的商品库存已空，去看看其他可选择的商品~');
        } else {
            $this->db->trans_commit();
            return array('code' => '200', 'msg' => '兑换成功，请在我的赠品查看');
        }
    }

    /**
     * 券卡打标
     * @param  $card_numbers array
     * @param  $type   string
     * @param  $op_id  int
     * @return boolean
     * $card_number=array('card_number1'=>'type1','card_number2'=>'type2')可同时处理多张不同type的优惠券
     * $card_number=array('card_number1','card_number2')可同时处理多张相同type的优惠券
     */
    function addCardType($card_numbers,$type = '全场通用券',$op_id = 0, $department = '',$tag = ''){
        if(!is_array($card_numbers)) $card_numbers = array($card_numbers);
        if(empty($card_numbers)) return true;
        $insert_data = array();
        foreach ($card_numbers as $key => $value) {
            $data = array();
            if (is_numeric($key)) {
                $data['card_number'] = $value;
                $data['type'] = $type;
                $data['op_id'] = $op_id;
                $data['department'] = $department;
                $data['tag'] = $tag;
            } else {
                $data['card_number'] = $key;
                $data['type'] = $value;
                $data['op_id'] = $op_id;
                $data['department'] = $department;
                $data['tag'] = $tag;
            }
            $insert_data[] = $data;
        }
        $res = $this->db->insert_batch("card_type",$insert_data);
        if(!$res){
            return false;
        }
        return true;
    }

    /*
 *  赠送赠品
 * */
    public function sendUserGifts($uid, $gifts_id, $wqbaby_id=0){
        $this->db->trans_begin();
        $this->db->from("user_gifts");
        $this->db->where(array("uid" => $uid, "active_id" => $gifts_id));
        $result = $this->db->get()->row_array();
        if(!empty($result)){
            return FALSE;
        }
        $gift_send = $this->db->select('*')->from('gift_send')->where('id', $gifts_id)->get()->row_array();
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
            'active_id' => $gifts_id,
            'active_type' => '2',
            'has_rec' => '0',
            'start_time'=>$gift_start_time,
	        'end_time'=>$gift_end_time,
        );
        $this->db->insert('user_gifts', $user_gift_data);
        if($wqbaby_id){
            $this->db->update('wqbaby_active',array('is_add'=>1),array("id"=>$wqbaby_id));
        }
        if ($this->db->trans_status() === FALSE ) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }

    }

    /*获取wqbaby*/
    public function get_wqbaby_active($uid, $active_tag, $link_tag='', $card_number='', $description=''){
        $where = array('mobile'=>$uid, 'active_tag' => $active_tag);
        if($link_tag){
            $where['link_tag'] = $link_tag;
        }
        if($card_number){
            $where['card_number'] = $card_number;
        }
        if($description){
            $where['description'] = $description;
        }
        $this->db->from('wqbaby_active');
        $this->db->where($where);
        $this->db->order_by('id','desc');
        return $this->db->get()->row_array();
    }

    /*懒人节 card*/
    public function lazyCard($uid){
        $card_sql = "select count(*) as cnt from ttgy_card where uid='".$uid."' and remarks like '%懒人节红包大礼'";
        $cardCount = $this->db->query($card_sql)->row_array();
        if($cardCount['cnt']>0){
            return array('code' => '200', 'msg' => '掐指一算，您已经领取过懒人节红包大礼啦，在我的果园-优惠券中查看~');
        }
        //老客
        $moneyArr = array(
            10=>100,
            15=>158,
            20=>208
        );
        $cardArr = array();
        foreach($moneyArr as $k=>$v){
            if($k==10){
                $time = date("Y-m-d");
                $remarks = $v."-".$k."懒人节红包大礼";
            }else{
                $time = "2016-07-11";
                $remarks = $v."-".$k."(本券生效日期为7月11日)懒人节红包大礼";
            }
//                        懒人节红包大礼

            $cardArr[] = $this->zsCard($uid, $k, 'testCard', $remarks,$v,0,$time,'2016-07-17',"活动");
        }

        $res = $this->db->insert_batch("card",$cardArr);
        if(!$res){
//            return false;
            return array('code' => '200', 'msg' => '掐指一算，您已经领取过懒人节红包大礼啦，在我的果园-优惠券中查看');
        }else{
            return array('code' => '200', 'msg' => '恭喜你，获得懒人节45元红包大礼，请到我的果园-优惠券中查看！');
        }
//        return true;

    }

    //卡
    public function zsCard($uid,$cardMoney,$shareNumber,$remarks,$order_money_limit,$marketing,$start,$date,$type,$product_id=''){
        $share_p_card_number = $shareNumber;
        $cardNumber = $share_p_card_number . $this->rand_card_number($share_p_card_number);
        $channel = '';
        $cardArr = array(
            'uid' => $uid,
            'sendtime' => date("Y-m-d"),
            'card_number' => $cardNumber,
            'card_money' => $cardMoney,
            'product_id' => $product_id,
            'maketing' => $marketing, //天天到家优惠券1  0 普通券
            'is_sent' => 1,
            'restr_good' => '0', //是否指定商品id  1为指定,需要给定上面的product_id。。0为不指定，product_id为空
            'remarks' => $remarks,
            'time' => $start,
            'to_date' =>$date,//date("Y-m-d", strtotime("+2day")),//只能当天使用 //date("Y-m-d", strtotime("+1day")),
            'can_use_onemore_time' => 'false',
            'can_sales_more_times' => 'false',
            'card_discount' => 1,
            'order_money_limit' => $order_money_limit, //满减可以使用
            'return_jf' => 0,
            'channel' => $channel
        );
        $r = $this->addCardType($cardNumber,$type);
        return $cardArr;
    }

    public function o2oSendRedPacket($uid, $order_name)
    {
        $return_result = array(
            "type" => 1,
            "share_url" => "http://awshuodong.fruitday.com/sale/o2oRedPacket/tel.html?tag=" . $order_name,
            "share_title" => "【天天果园-闪电送】送你一张无门槛优惠券，快来认领！",
            "share_desc" => "【天天果园-闪电送】送你一张无门槛优惠券，快来认领！",
            "share_photo" => "http://activecdnws.fruitday.com/sale/o2oRedPacket/images/share.jpg",
            "share_alert" => "您有十个红包可以分享给好友！",
            "code" => 200
        );
        //只保留:平塘前置仓和洋泾前置仓
        $order = $this->db->query('select store_id from ttgy_o2o_child_order where order_name = ?', array($order_name))->row_array();
        if (empty($order['store_id']) || !in_array($order['store_id'], array(99, 129))) {
            return array();
        }

        $existed = $this->db->query('select id from ttgy_o2o_active_redpacket where order_name = ?', array($order_name))->row_array();
        if(!empty($existed)){
            return $return_result;
        }

        $rs = $this->db->insert('ttgy_o2o_active_redpacket', array(
            'uid' => $uid,
            'order_name' => $order_name,
            'created_at' => date('Y-m-d H:i:s')
        ));
        return $rs ? $return_result : array();
    }

	//是否有未使用、未过期的优惠券
	function check_card_used($remarks,$uid,$now_date){
		$this->db->from('card');
		$this->db->where(array('remarks'=>$remarks, 'uid'=>$uid, 'is_used' => 0, 'to_date >=' => $now_date));
		if ($this->db->get()->num_rows() > 0) {
			return false;
		}else{
			return true;
		}
	}

	/*懒人节通用券 card*/
	public function lazyCardToAll($uid){
		$share_remarks = '懒人节10元优惠券（满100元使用）';
		$now_date = date("Y-m-d");
		$share_p_card_number = 'lrj';
		$can_send = $this->check_card_used($share_remarks,$uid,$now_date);
		if($can_send){
			$share_card_number = $share_p_card_number.$this->rand_card_number($share_p_card_number);
			$card_data = array(
				'uid'=>'',
				'sendtime'=>$now_date,
				'card_number'=>$share_card_number,
				'card_money'=>10,
				'product_id'=>'', //不能为0 注意看下
				'maketing'=>'0',
				'is_sent'=>'',
				'restr_good'=>'0',
				'remarks'=>$share_remarks,
				'time'=>$now_date,
				'to_date'=>date("Y-m-d",strtotime('+3 day')),//券的有效期
				'can_use_onemore_time'=>'false',
				'can_sales_more_times'=>'false',
				'card_discount'=>1,
				'order_money_limit'=>'100',
				'return_jf'=>'',
				'black_user_list'=>'',
				'channel'=>''
			);

			if (!empty($uid)) {
				$card_data['uid'] = $uid;
				$card_data['is_sent'] = '1';
			}
			$rs = $this->db->insert('card',$card_data);
			if(!$rs){
				return array('code' => '300', 'msg' => '果园君有点忙坏啦，请稍后再尝试领取~');
			}else{
				return array('code' => '200', 'msg' => '恭喜获得10元优惠券（满100使用），请至“我的果园”—>“优惠券”中查看哦');
			}
		}else{
			return array('code' => '200', 'msg' => '果园君掐指一算，您的账户中已经存在一张懒人节优惠券啦，使用后可以再来领哟么么哒');
		}

	}

    /*
     * 解冻账号
     */
    function thaw_user($uid=0) {
        $this->db->query("UPDATE ttgy_user set freeze = 0 where id = $uid");
    }

    /*
     * 获取IP地址
     */
    function getIpAddr()
    {
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $realip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }

        if (false !== strpos($realip, ',')){
            $realip = reset(explode(',', $realip));
            return $realip;
        }
        return $realip;
    }

    public function getWithdraw($uid,$n_money){
    	$withdraw = 0; //可提现金额
    	$bonusMoney = 0; //须额外扣除金额（赠送金额）（按照最大可提现金额计算，实用性？）
        $this->db->select('money,bonus,card_number');
		$this->db->from('trade');
	    $this->db->where(array('uid'=>$uid,"has_deal"=>1,'type'=>'income','status'=>'已充值'));
		$this->db->order_by('time', 'desc');
		$result = $this->db->get()->result_array();
		$money = 0;//充值金额
		$bonus = 0;//赠送金额
		$this->db->select('sum(money+bonus) as sum_money');
		$this->db->from('trade');
	    $this->db->where(array('uid'=>$uid,"has_deal"=>1,'type'=>'income','status'=>'已充值'));
	    $sum_money = $this->db->get()->row_array();
	    $sum_money = $sum_money['sum_money'];//所有入账金额
	    $fee_money = bcsub($sum_money, $n_money,2); //所有以消费金额
	    if($fee_money<0) $fee_money = 0;
	    $sql = "select sum(money) as card_money from ttgy_trade where uid={$uid} and has_deal=1 and type='income' and (card_number <> '' or card_number is not null)";
	    $card_money = $this->db->query($sql)->row_array();
	    $card_money = $card_money['card_money']; //充值卡金额
	    $cut_money = bcsub($card_money,$fee_money,2);
	    $cut_money<0 and $cut_money = 0;
		foreach ($result as $key => $value) {
			if(bccomp($n_money, bcadd($value['money'], $value['bonus'],2),2) >= 0){
                $withdraw = bcadd($withdraw, $value['money'],2);
                $bonusMoney = bcadd($bonusMoney, $value['bonus'],2);
                $n_money = bcsub($n_money, bcadd($value['money'], $value['bonus'],2),2);
			}else{
				if($value['bonus'] == 0){
					$money = min($value['money'],$n_money);
					$bonus = 0;
				}else{
					$diff_money = bcsub($n_money, $value['bonus'],2);
					$money = ($diff_money>0)?$diff_money:0;
				    $bonus = ($diff_money>0)?$value['bonus']:0;
				}
				$withdraw = bcadd($withdraw, $money,2);
                $bonusMoney = bcadd($bonusMoney, $bonus,2);
                break;
			}
		}
        $data = array();
        $w_money = bcsub($withdraw, $cut_money , 2);
        $data['withdraw'] = ($w_money>0)?$w_money:0;
        $data['bonusMoney'] = $bonusMoney;
		return $data;
    }

    //农场分享获取赠品
    public function farmGift($uid){
        //查看最新的记录
        $farm_log_sql = "select * from ttgy_farm_log where uid = ".$uid." order by id desc limit 1";
        $farm_log = $this->db->query($farm_log_sql)->row_array();
//        print_r($farm_log);exit;
        if(!empty($farm_log)){

            $seed_sql = "select a.*,a.status,b.content,b.active_id from ttgy_farm_user_seed a left join ttgy_farm_seed b on a.sid = b.id where a.id = ".$farm_log['usid'];
            $seed = $this->db->query($seed_sql)->row_array();
            $content = json_decode($seed['content'],true);
            $count = count($content);
            //超时
            $today = date("Y-m-d");
            $dC = ceil((strtotime($today)-strtotime($farm_log['date']))/86400);
            if($farm_log['days']<$count &&$farm_log['status']==1){ //种植过程没有完成
                return array('code' => '300', 'msg' => '果园君掐指一算，你的果实还没成熟，请耐心等待~');
            }else if($farm_log['status']==2){ //已领取
                return array('code' => '300', 'msg' => '果园君掐指一算，您已经领取过了，下单获得种子后可再次种植奥~');
            }else if($farm_log['status']==3){ //已清零

                return array('code' => '300', 'msg' => '您已超过3天没有呵护小树苗了，这棵小树苗已失效咯~');
            }else if($dC>3){
                $this->db->where('id',$farm_log['usid']);
                $this->db->update("farm_user_seed",array('status'=>3,'last_remarks'=>'超时，赠品作废','last_time'=>date("Y-m-d H:i:s")));

                $this->db->limit($farm_log['days']);
                $this->db->order_by('id', 'desc');
                $this->db->where(array('uid'=>$uid,'status'=>1));
                $this->db->update("farm_log",array('status'=>2,'last_time'=>date("Y-m-d H:i:s")));
                $return_result = array("result" => "error","msg" => "您已经超过3天没有过来，失去领取赠品的资格了，赶紧再去播种吧~");

                return array('code' => '300', 'msg' => '您已经超过3天没有过来，失去领取赠品的资格了，赶紧再去播种吧~');
            }else{
            	$gift_send = $this->db->select('*')->from('gift_send')->where('id', $seed['active_id'])->get()->row_array();
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
                $giftData = array(
                    'uid' => $uid,
                    'active_id' => $seed['active_id'],
                    'active_type' => '2',
                    'has_rec' => '0',
                    'start_time'=>$gift_start_time,
	                'end_time'=>$gift_end_time,
                );
                $gift_sql = "select b.product_name from ttgy_gift_send a left join `ttgy_product` b on a.product_id = b.id where a.id=".$seed['active_id'];
                $gift = $this->db->query($gift_sql)->row_array();
//                print_r($gift);exit;
                if(empty($gift)){
                    return array('code' => '300', 'msg' => '系统没有检测到赠品，如有疑问请联系客服~');
                    exit;
                }else{
                    $result1 = $this->db->insert('user_gifts', $giftData);
                    $this->db->where('id',$farm_log['usid']);
                    $result2 = $this->db->update("farm_user_seed",array('status'=>3,'last_remarks'=>'获得赠品','last_time'=>date("Y-m-d H:i:s")));

//                    $this->db->limit($farm_log['days']);
                    $this->db->order_by('id', 'desc');
                    $this->db->where(array('uid'=>$uid,'status'=>1,'usid'=>$farm_log['usid']));
                    $result3 = $this->db->update("farm_log",array('status'=>2,'last_time'=>date("Y-m-d H:i:s")));
                    if($result1&&$result2&&$result3){

                        return array('code' => '200', 'msg' => $gift['product_name'].' 已经在您的账户中咯，请去 “我的果园”->“我的赠品” 中领取');
                    }else{
                        return array('code' => '300', 'msg' => '领取失败，请联系客服');
                    }
                }
            }

        }else{
            return array('code' => '300', 'msg' => '果园君掐指一算，您没有种植记录哦，下单获得种子即可种植~');
        }
    }

    public function stockAll() {
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        $rKey = 'rbac:stockup:all';
        $stockupResult = $redis->get($rKey);
        if (empty($stockupResult)) {
            $nowDate = date('Y-m-d H:i:s');
            $listWhere = array('is_open' => '是', 'start_time <=' => $nowDate, 'gift_end_time >=' => $nowDate);
            $list = $this->db->from('hd_stockup')->where($listWhere)->get()->result_array();
            foreach ($list as $key => $val) {
                $sill_list = unserialize($val['sill_list']);
                $list[$key]['sill_img1'] = $sill_list['sill1']['sill_img'];
                $list[$key]['limit_money1'] = $sill_list['sill1']['limit_money'];
                $list[$key]['gift_tag1'] = $sill_list['sill1']['gift_tag'];
                $list[$key]['pro_desc1'] = $sill_list['sill1']['pro_desc'];
                $list[$key]['sill_img2'] = $sill_list['sill2']['sill_img'];
                $list[$key]['limit_money2'] = $sill_list['sill2']['limit_money'];
                $list[$key]['gift_tag2'] = $sill_list['sill2']['gift_tag'];
                $list[$key]['pro_desc2'] = $sill_list['sill2']['pro_desc'];
                unset($list[$key]['sill_list']);
            }
            $stime = strtotime(date('Y-m-d H:00:00', strtotime('+1 hour'))) - strtotime(date('Y-m-d H:i:s'));
            $stockupResult = serialize($list);
            $redis->setex($rKey, $stime, $stockupResult);
        }

        $list = unserialize($stockupResult);
        return $list;
    }

    public function addBlackList($data){
    	if(empty($data) || empty($data[0])) return;
        $fields_array = array_keys($data[0]);
        $fields = implode(',', $fields_array);
        $values = '';

        foreach ($data as $key => $value) {
            $values .= "(";
            foreach ($fields_array as $field_name) {
                $values .= "'".addslashes($value[$field_name])."',";
            }
            $values = rtrim($values,',');
            $values .= "),";
        }
        $values = rtrim($values,',');
        $updates = '';
        foreach ($fields_array as $field_name) {
            $updates .= $field_name ."= VALUES(".$field_name."),";
        }
        $updates = rtrim($updates,',');
	    $sql = "INSERT INTO `ttgy_user_black_list` (".$fields.") VALUES ".$values." ON DUPLICATE KEY UPDATE ".$updates;
        return $this->db->query($sql);
    }

    public function removeBlackList($uids){
    	if(empty($uids)) return true;
    	$this->db->where_in('uid', $uids);
    	return $this->db->delete('user_black_list');
    }

    public function checkUserBlackList($uids){
    	$data = array();
    	if(empty($uids)) return $data;
    	if(!is_array($uids)) $uids = array($uids);
    	foreach ($uids as $uid) {
    		$data[$uid] = 0;
    	}
    	$this->db->select('uid,credit_rank');
    	$this->db->from('ttgy_user_black_list');
    	$this->db->where_in('uid',$uids);
    	$result = $this->db->get()->result_array();
    	$h_uids = array();
    	foreach ($result as $key => $value) {
    		$data[$value['uid']] = $value['credit_rank'];
    	}
    	return $data;
    }

    /*赠送香梨*/
    public function pear_20160914($uid){

        $param = array(
            '20160917'=>array(
                0=>3732,//GIkI34
                1=>3733 //GA52n2
            ),
            '20160918'=>array(
                0=>3760,//GIkI34
                1=>3761 //GA52n2
            ),
            '20160919'=>array(
                0=>3734,//SZOXB
                1=>3735 //O1biM3
            ),
            '20160920'=>array(
                0=>3736,//ei8NK
                1=>3737 //esLZW1
            ),
            '20160921'=>array(
                0=>3738,//nkRzq3
                1=>3739 //RjSPn1
            ),
            '20160922'=>array(
                0=>3740,//eYlAj
                1=>3741 //joX5C2
            ),
            '20160923'=>array(
                0=>3742,//SMpaB4
                1=>3743 //QR9Sx2
            ),
            '20160924'=>array(
                0=>3744,//DDY6f3
                1=>3745 //TImw92
            ),
            '20160925'=>array(
                0=>3746,//yUVJL4
                1=>3747 //9nYsU1
            ),
            '20160926'=>array(
                0=>3748,//s0lmQ3
                1=>3749 //3LTSe1
            ),
            '20160927'=>array(
                0=>3750,//hVth14
                1=>3751 //lHKYN
            ),
            '20160928'=>array(
                0=>3752,//O0lQU1
                1=>3753 //WtSEE
            ),
            '20160929'=>array(
                0=>3754,//y5QHn2
                1=>3755 //yuHm73
            ),
            '20160930'=>array(
                0=>3756,//3wcc83
                1=>3757 //zsODz
            ),
            '20161001'=>array(
                0=>3758,//tKvCw1
                1=>3759 //xU9ja1
            ),
            '20161002'=>array(
                0=>3844,//QFQTQ1
                1=>3845 //vQTwR2
            ),
            '20161003'=>array(
                0=>3846,//2JckQ1
                1=>3847 //j7afB3
            ),
            '20161004'=>array(
                0=>3848,//usZq81
                1=>3849 //ixxMS2
            ),
            '20161005'=>array(
                0=>3850,//fQAu9
                1=>3851 //ftHU41
            ),
            '20161006'=>array(
                0=>3852,//lipjj1
                1=>3853 //IU6pw3
            ),
            '20161007'=>array(
                0=>3854,//O2Wdc1
                1=>3855 //CEzVE2
            ),
            '20161008'=>array(
                0=>3856,//2Z06H3
                1=>3857 //Wnt7J4
            )
        );
        $today = date('Ymd');
        if($today<'20160918' || $today>'20161008'){
            return '不在活动时间内';
        }
        //购买订单数
        $this->db->from('order');
        $this->db->where(array('uid' => $uid, 'operation_id !=' => 5, 'pay_status' => 1, 'order_status' => 1));
        $this->db->order_by('id desc');
        $order_count =  $this->db->get()->num_rows() ;

        //查看昨天 是否有未使用的赠品
        $yestday = date("Ymd" , strtotime('-1 day'));
        $yestday_gift_id = ($order_count>0) ? $param[$yestday][1]:$param[$yestday][0];
        $this->db->from("user_gifts");
        $this->db->where(array("uid" => $uid, "active_id" => $yestday_gift_id, 'has_rec' => 0));
        $yestday_gift_info = $this->db->get()->row_array();
        if($yestday_gift_info){
            return '果园君掐指一算，您的账户中已有香梨2斤啦，快去下单带走吧！';
        }

        //查看今天是否领取赠品
        $today_gift_id = ($order_count>0) ? $param[$today][1]:$param[$today][0];
        $this->db->from("user_gifts");
        $this->db->where(array("uid" => $uid, "active_id" => $today_gift_id));
        $today_gift_info = $this->db->get()->row_array();
        if($today_gift_info){
            return '果园君掐指一算，您的账户中已有香梨2斤啦，快去下单带走吧!！';
        }
        $gift_send = $this->db->select('*')->from('gift_send')->where('id', $today_gift_id)->get()->row_array();
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
            'active_id' => $today_gift_id,
            'active_type' => '2',
            'has_rec' => '0',
            'start_time'=>$gift_start_time,
	        'end_time'=>$gift_end_time,
        );
        $this->db->insert('user_gifts', $user_gift_data);
        if($this->db->insert_id()){
            return '运气棒棒哒！获得香梨2斤,快去购物带走吧';
        }
        return '果园君小憩一会';
    }

    /*
	*获取所有ios热修复版本
    */
    function get_hotfix_version(){
    	$sql = 'select version,max(fix_version) as fix_version from ttgy_ios_hotfix group by version';
    	return $this->db->query($sql)->result_array();
    }
    //下单后获取优惠券
    public function add_order_card($order_id){
        if(empty($order_id)) return true;
        $time = date("Y-m-d H:i:s");
        $start = "2016-11-28 00:00:00";
        $end = "2016-12-12 00:00:00";
        $orderMoney = $this->db->select('money,uid')->from('order')->where('id', $order_id)->get()->row_array();
        if($time>=$start&&$time<$end&&$orderMoney['money']>=100){
//            if($orderMoney['money']>=100){
                $this->db->select("uid");
                $this->db->from("order");
                $this->db->where(array("id" =>$order_id));//
                $order = $this->db->get()->row_array();
                $uid = $order['uid'];
                $tagArr = array('6b2d9p3','3s8q5e9','2c6f7u5');
                $this->db->trans_begin();
                foreach($tagArr as $tag){
                    /* 抵扣码赠送start */
                    $this->db->from('mobile_card');
                    $this->db->where(array('card_tag' => $tag));
                    $query = $this->db->get();
                    $mobilecard_info = $query->row_array();
                    if (empty($mobilecard_info)) {
        //                $this->showJson(array('result' => 'fail', 'msg'=>'该卡券不存在！'));
                        return '该卡券不存在！';
                    } else {
                        //优惠券有未过期的就过滤
                        $this->db->from('card');
//                        $this->db->where(array('uid'=>$uid,"remarks"=>$mobilecard_info['card_desc'],'to_date >= '=>date('Y-m-d')));
                        $this->db->where(array('uid'=>$uid,"card_money"=>$mobilecard_info['card_money'],"remarks"=>$mobilecard_info['card_desc'],'to_date >= '=>date('Y-m-d')));
        //                $this->db->where('to_date >=',date('Y-m-d'));
                        $result = $this->db->get()->row_array();
                        if(!empty($result)){
        //                    $this->showJson(array('result' => 'fail', 'msg'=>'您已经领过啦！快去shopping一下吧~'));
                            return '您已经领过啦！快去shopping一下吧~';
                        }
                        $card_number = $mobilecard_info['p_card_number'] . $this->rand_card_number($mobilecard_info['p_card_number']);
                        $to_date = ($mobilecard_info['card_to_date']!='0000-00-00')?$mobilecard_info['card_to_date']:date("Y-m-d", strtotime("+{$useful_day}day"));//赠卡有效期默认3天
                        $today = date('Y-m-d');
                        $card_data = array(
                            'uid' => $uid,
                            'sendtime' => date("Y-m-d", time()),
                            'card_number' => $card_number,
                            'card_money' => $mobilecard_info['card_money'],
                            'product_id' => $mobilecard_info['product_id'],
                            'maketing' => '0',
                            'is_sent' => '1',
                            'restr_good' => $mobilecard_info['restr_good'],
                            'remarks' => $mobilecard_info['card_desc'],
                            'time' => "2016-12-12",
                            'to_date' => "2016-12-12",
                            'can_use_onemore_time' => 'false',
                            'can_sales_more_times' => $mobilecard_info['can_sales_more_times'],
                            'card_discount' => 1,
                            'order_money_limit' => $mobilecard_info['order_money_limit'],
                            'channel' => $mobilecard_info['channel'],
                            'direction' => $mobilecard_info['direction'],
                        );
                        $this->db->insert('card', $card_data);

                        //发优惠券打标
                        $this->load->model('card_model');
                        $r = $this->card_model->addCardType($card_number,$mobilecard_info['card_m_type'],0,$mobilecard_info['card_tag'],$mobilecard_info['department']);
                    }
                }
                if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
        //            $this->showJson(array("result" => "fail", "msg" => "领取失败，请刷新页面重试."));
                    return "领取失败，请刷新页面重试.";
                } else {
                    $this->db->trans_commit();
                }
        //        $this->showJson(array('result' => 'succ', 'msg' => '太棒啦！一次领到3张优惠券。可至“我的优惠券”中查看。'));
                return '太棒啦！一次领到3张优惠券。可至“我的优惠券”中查看。';
//            }
        }
        return true;
    }


    function getOrderMoney($order_name){
            $order_info = $this->db->select("o.money,o.uid")->from('order o')->where(array('o.order_name'=>$order_name))->get()->row_array();
            $sql = "select count(*) as cnt from ttgy_card where uid = ".$order_info['uid']." and remarks = '双12满减券，仅限12.12使用' and to_date>='".date('Y-m-d')."'";
            $count = $this->db->query($sql)->row_array();
            if($count['cnt']>0){
                $type=1;
            }else{
                $type=2;
            }
            $order_info['type'] = $type;
            return $order_info;
    }

    public function getUserRankLog($uid){
    	$data = array();
    	$this->db->select('user_rank');
		$this->db->from('user');
		$this->db->where(array('id' => $uid));
		$now_rank = $this->db->get()->row_array();
		$now_rank = $now_rank['user_rank'];
		$now_rank = $this->get_rank($now_rank);
        $data['now_rank'] = $now_rank['name'];

    	$this->db->from('user_rank_log');
		$this->db->where(array('uid' => $uid));
		$this->db->order_by('time', 'desc');
		$result = $this->db->get()->result_array();
		foreach ($result as $key => $value) {
			$log = array();
			if ($value['from_rank'] > $value['to_rank']) {
				$log['type'] = '1';  //降级
			} else {
				$log['type'] = '2';
			}
			$from_rank = $this->get_rank($value['from_rank']);
			$log['from_rank'] = $from_rank['name'];
			$to_rank = $this->get_rank($value['to_rank']);
			$log['to_rank'] = $to_rank['name'];
			$log['expire_date'] = $value['expire_date'];
			$log['time'] = date('Y-m-d H:i:s',$value['time']);
			$data['logs'][] = $log;
		}
		return $data;
    }

    public function get_user_invite($uid){
    	$sql = "select mobile_phone,from_unixtime(ctime) as ctime_datetime,from_unixtime(finish_order_time) as finish_order_time_datetime from ttgy_user_invite_new2 where invite_by = {$uid} order by `id` desc";
        $res = $this->db->query($sql)->result_array();
        return $res;
    }

    public function get_Withdraw_White_User($uid){
    	$sql = "select * from ttgy_withdraw_whitelist where uid=".intval($uid)." and has_withdraw = 0";
    	$res = $this->db->query($sql)->row_array();
    	return $res;
    }

    public function setWithdrawUser($uid){
        $this->db->where(array('uid' => $uid));
		$this->db->update('withdraw_whitelist', array('has_withdraw' => 1));
    }


    public function redBagTag($order_id)
    {
        $data = $this->db->from("red_packets")->where(array(
            'order_id'=>$order_id
        ))->get()->row_array();

        return $data;
    }
}
