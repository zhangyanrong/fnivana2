<?php
namespace bll;
class Wx
{
	private $openid;
	var $session_expiretime = 1209600;
	public function __construct($params = array()){
		$this->ci = &get_instance();
		$this->ci->load->helper('public');
		$this->openid = $params['openid'];
		$this->ci->load->model('wx_model');
	}

	/*
	 *同步更新用户微信帐号信息
	 */
	public function set_user_info($params){
		$required_fields = array(
			'openid' => array('required' => array('code' => '300', 'msg' => 'openid不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		$this->ci->load->model('wx_model');
		$sms = $this->ci->wx_model->set_wx_user_info($this->openid,$params);
//		if($sms['code'] == 300){
			return $sms;
//		}
	}

	/*
	 *  wap站绑定操作流程
	 */
	public function bind($params) {
		$required_fields = array(
			'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
			'password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
			'openid' => array('required' => array('code' => '300', 'msg' => 'openid不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}

		if (!preg_match('/@/', $params['mobile'])) {
			$where = array(
				"mobile" => $params['mobile'],
			);
		} else {
			$where = array(
				"email" => $params['mobile'],
			);
		}



		/*登录次数验证start*/
		$this->ci->load->model('user_model');
		$users = $this->ci->user_model->selectUsers("id,chkpass", $where);
		if (empty($users)) {
			return array('code' => '300', 'msg' => '用户名错误');
		}
		$uid = $users[0]['id'];
		if($this->ci->user_model->setLoginErrorNum($uid)>=5){
			return array("code"=>"300","msg"=>"重试登录次数过多，请操作找回密码");
		}
		/*登录次数验证end*/
		$this->ci->load->library("PassMd5");
		if($users[0]['chkpass']=='1'){
			$userPassWord = $this->ci->passmd5->userPwd($params['password']);
		}else{
			$userPassWord = $params['password'];
			$newPwd = $this->ci->passmd5->userPwd($params['password']);
		}
		

		$where['password'] = $userPassWord;
		$user = $this->ci->user_model->getUser("", $where);
		if (isset($user['code'])) {
			return $user;
		}

		//重置登陆错误
		$this->ci->user_model->setLoginErrorNum($user['id'], 1);	

		//更新密码
		if($users[0]['chkpass']!='1'){
			$update_where = array(
				'id' => $uid
			);
			$update_data = array(
				'chkpass' => '1',
				'password' => $newPwd,
			);
			$this->ci->user_model->updateUser($update_where, $update_data);
		}

		//黑名单验证
		// if($user_black = $this->ci->user_model->check_user_black($user['id'])){
		// 	if($user_black['type']==1){
		// 		$this->ci->load->library('fdaylog');
		// 		$db_log = $this->ci->load->database('db_log', TRUE);
		// 		$this->ci->fdaylog->add($db_log,'user_cherry_black',$params['mobile']);
		// 		return array("code"=>"300","msg"=>"果园君发现您的账号为无效手机号，为保证您的购物体验请用有效手机号注册，敬请谅解。");
		// 	}else{
		// 		return array("code"=>"300","msg"=>"您的帐号可能存在安全隐患，暂时冻结，请联系客服处理!");
		// 	}
		// }

		//绑定操作
		$this->ci->load->model('wx_model');
		$is_bind = $this->ci->wx_model->bind($this->openid,$user['id']);

		if($is_bind['code'] == 300){
			return $is_bind;
		}

		//重置登陆错误
		$this->ci->user_model->setLoginErrorNum($user['id'], 1);

		$this->ci->session->sess_expiration = $this->session_expiretime;
		$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
		$session_id = $this->ci->session->set_userdata($user);
		session_id($session_id);
        session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
        $_SESSION['user_detail'] = $user;
        session_write_close();
		//cart:未登陆客户端购物车物品
		$cartcount = 0;
		$this->ci->load->bll('cart', array('session_id' => $session_id));
		if ($carttmp = @json_decode($params['carttmp'], true)) {
			$this->ci->bll_cart->after_signin_regist($carttmp);
		}
		$cartcount = $this->ci->bll_cart->get_cart_count();
		$this->ci->user_model->add_connectid_region_id($session_id,$params['region_id']);
		return array('connect_id' => $session_id, 'cartcount' => $cartcount);
	}

	/*
	 * 解绑
	 */
	public function unbind($params){
		$required_fields = array(
			'openid' => array('required' => array('code' => '300', 'msg' => 'openid不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		$this->ci->load->model('wx_model');
		$is_bind = $this->ci->wx_model->unbind($this->openid);
		if($is_bind){
			return array('code'=>200, 'msg'=>'解绑成功!');
		}
	}

	/*
	 * 用户账户信息接口
	 */
	public function getUserInfo($params){
		$required_fields = array(
			'user_id' => array('required' => array('code' => '300', 'msg' => 'user_id不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}

		$uid = $params['user_id'];

		$this->ci->load->model('user_model');
		$userinfo = $this->ci->user_model->getUser($uid, '', 'id,mobile,user_rank');
		unset($userinfo["userface"],$userinfo["birthday"],$userinfo["is_enterprise"],$userinfo["enterprise_name"]);

		$arr = $this->ci->config->item('user_rank');
		$userinfo['user_rank'] =$arr['level'][$userinfo['user_rank']]['name'];
		$this->ci->load->model('user_model');
		$allres = $this->ci->user_model->get_user_money($uid);
		$userinfo['amount'] = sprintf("%.2f", $allres[0]->amount);

		$trade_money = $this->ci->user_model->get_user_money_group_by_type($uid);
		foreach($trade_money as $v){
			if($v['type']=='income'){
				$userinfo['income'] = sprintf("%.2f", $v['money']);
			}elseif($v['type']=='outlay'){
				$userinfo['outpay'] = sprintf("%.2f", $v['money']);
			}
		}
		return array('code' => '200', 'userinfo' => $userinfo);
	}

//	/*
//	 * 用户订单信息接口
//	 */
//	public function getOrderInfo($params){
//		$required_fields = array(
//			'openid' => array('required' => array('code' => '300', 'msg' => 'openid不能为空')),
//		);
//		if ($alert_msg = check_required($params, $required_fields)) {
//			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
//		}
//		$wxUserInfo = $this->ci->wx_model->getUidByOpenid($this->openid);
//		$uid = $wxUserInfo['uid'];
//
//
//	}

	/*
	 * 订单的物流信息接口
	 */
	public function getShipingInfo($params){
		$required_fields = array(
			'user_id' => array('required' => array('code' => '300', 'msg' => 'user_id不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}

		$uid = $params['user_id'];

		$orderinfo = $this->ci->wx_model->getLastSendOrderId($uid);

		$product_info = $this->ci->wx_model->getOrderProInfo($orderinfo['id']);
		$this->ci->load->model('order_model');
		$logistic_info = $this->ci->order_model->getLogisticInfo($orderinfo['order_name'],$uid);
		if(empty($logistic_info)){
			return array('code'=>'300','msg'=>'抱歉您的订单尚未产生物流信息哦~');
		}else{
			$result = array();
			if(in_array($logistic_info['deliver_method'], array('京东快递','顺丰'))){
				switch ($logistic_info['deliver_method']) {
					case '京东快递':
						$logistic_logo = 'http://cdn.fruitday.com/assets/logistic/ic_jd@2x.png';
						$logistic_tag = "jd";
						break;
					case '顺丰':
						$logistic_logo = 'http://cdn.fruitday.com/assets/logistic/ic_sf@2x.png';
						$logistic_tag = "sf";
						break;
					default:
						$logistic_logo = 'http://cdn.fruitday.com/assets/logistic/ic_fruitday@2x.png';
						break;
				}

				$result['type'] = 1;
				$result['driver_name'] = '';
				$result['driver_phone'] = '';
				$result['logistic_company'] = $logistic_info['deliver_method'];
				$result['logistic_logo'] = $logistic_logo;
				$result['logistic_order'] = $logistic_info['logi_no'];

				// $apiUrl = "http://trac.fday.co/api/baoguo/luyou/".$logistic_tag."/".$logistic_info['logi_no'];
				$apiUrl = POOL_LOGISTICTRACE_URL."?code=".$logistic_info['logi_no']."&accs_key=d5473645-e614-4d0f-bc0c-38b0b3ccbfc8";
				$ch = curl_init($apiUrl);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/x-www-form-urlencoded'
					)
				);
				$api_result = curl_exec($ch);
				$result_json = urldecode($api_result);
				$return = json_decode($result_json,1);
				if($return['result']!=1){
					$logistic_trace[] = array(
						'trace_desc'=>'物流公司已收件',
						'trace_time'=>date("Y-m-d H:i:s",$logistic_info['delivertime'])
					);
				}else{
					if(empty($return['list'])){
						$logistic_trace[] = array(
							'trace_desc'=>'物流公司已收件',
							'trace_time'=>date("Y-m-d H:i:s",$logistic_info['delivertime'])
						);
					}else{
						foreach ($return['list'] as $key => $value) {
							$logistic_trace[$key]['trace_desc'] = $value['remark']?$value['remark']:'';
							$logistic_trace[$key]['trace_time'] = $value['acceptTime'];
						}
					}
				}
				$result['logistic_trace'] = $logistic_trace;
			}else{
				$result['type'] = 0;
				$result['driver_name'] = $logistic_info['deliver_name'];
				$result['driver_phone'] = $logistic_info['deliver_mobile'];
				$result['logistic_company'] = $logistic_info['deliver_method'];
				$result['logistic_logo'] = 'http://cdn.fruitday.com/assets/logistic/ic_fruitday@2x.png';
				$result['logistic_order'] = $logistic_info['logi_no'];
				$result['logistic_trace'] = array(
					array(
						'trace_desc'=>'【已发货】送货员：'.$logistic_info['deliver_name'].'，手机：'.$logistic_info['deliver_mobile'],
						'trace_time'=>date("Y-m-d H:i:s",$logistic_info['delivertime'])
					)
				);
			}
			$result['product_name'] = $product_info['product_name'];
			$result['product_num'] = $product_info['product_num'];
			$result['reciever_name'] = $orderinfo['name'];
			$result['address'] = $orderinfo['address'];
			return $result;
		}
	}

	/*
	 * 获取优惠券信息
	 */

	public function getCards($params){
		$required_fields = array(
			'user_id' => array('required' => array('code' => '300', 'msg' => 'user_id不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}

		$uid = $params['user_id'];

		$cards = $this->ci->wx_model->getAvailableCardNum($uid);

		if(0 == $cards){
			return array('code'=>'300','msg'=>'您还没有优惠券哦，了解最新优惠~','redirect_type'=>'default');
		}else{
			return array('code'=>'200','msg'=>'您的账户中有'.$cards.'张可使用的优惠券,点击了解详情~','redirect_type'=>'coupon');
		}
	}

	/*
	 * 获取赠品信息
	 */

	public function getUserGifts($params){
		$required_fields = array(
			'user_id' => array('required' => array('code' => '300', 'msg' => 'user_id不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}

		$uid = $params['user_id'];

		$this->ci->load->model('user_gifts_model');
		$gifts = $this->ci->user_gifts_model->gift_count($uid);

		if(0 == $gifts){
			return array('code'=>'300','msg'=>'暂时没赠品呢，了解最新优惠','redirect_type'=>'default');
		}else{
			return array('code'=>'200','msg'=>'您的账户中有'.$gifts.'个可领取的赠品,点击了解详情~','redirect_type'=>'giftsget');
		}
	}

	/*
	 * 获取积分信息
	 */

	public function getScore($params){
		$required_fields = array(
			'user_id' => array('required' => array('code' => '300', 'msg' => 'user_id不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}

		$uid = $params['user_id'];

		$this->ci->load->model('user_model');
		$user_score = $this->ci->user_model->getUserScore($uid);
		$user_score = $user_score?$user_score['jf']:0;
		return array('code'=>'200','msg'=>'您目前的积分为：'.$user_score);
	}


	//免登录分发
	public function checkLogin($params){
		$required_fields = array(
			'user_id' => array('required' => array('code' => '300', 'msg' => 'user_id不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		switch($params['redirect_type']){
			case 'giftsget': $redirect_url = 'http://m.fruitday.com/user/giftsget';break;
			case 'coupon': $redirect_url = 'http://m.fruitday.com/user/coupon';break;
			default: $redirect_url = 'http://m.fruitday.com';
		}

		$this->ci->load->model('user_model');
		$user = $this->ci->user_model->getUser($params['user_id']);
		$this->ci->session->sess_expiration = $this->session_expiretime;
		$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
		$session_id = $this->ci->session->set_userdata($user);
		session_id($session_id);
        session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
        $_SESSION['user_detail'] = $user;
        session_write_close();
		$cartcount = 0;
		$this->ci->load->bll('cart', array('session_id' => $session_id));
		$cartcount = $this->ci->bll_cart->get_cart_count();
		return array('connect_id' => $session_id, 'cartcount' => $cartcount, 'redirect_url'=>$redirect_url);
	}
}
