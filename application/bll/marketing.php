<?php
namespace bll;
class Marketing{

	public function __construct(){
		$this->ci = &get_instance();
        $this->ci->load->helper('public');
	}
	/*
	*首页广告位置
	*/
	public function banner($params){

                $o2o_region_id = $params['region_id'];
		$region_id = $this->get_region_id($params['region_id']);
		$params['region_id'] = $region_id;
		$this->ci->load->model('banner_model');
		$banner_list = $this->ci->banner_model->get_banner_list($params);
		if($region_id==106092 || $region_id==143949){
			$banner_list['is_store_exist'] = 1;
		}
		if($o2o_region_id==144422 || $o2o_region_id==144444){
            if(time() < strtotime(date("2016-04-01 17:00:00"))){
                $banner_list['is_store_exist'] = 1;
			}
		}
		// if($region_id==143949){
		// 	$banner_list['is_o2o_initial'] = 1;
		// }
		if(strcmp($params['version'],'3.0.0')>=0){
			$icon_list = $this->ci->banner_model->getIcon();
			if(!empty($icon_list)){
				$icon_result = array();
				foreach ($icon_list as $key => $value) {
					$icon_result[$value['type']] = PIC_URL.$value['url'];
				}
				$banner_list['chennel_icons'] = $icon_result;
			}
		}
		return $banner_list;
	}

	public function baseSetting(){
		$this->ci->load->model('setting_model');
		$res = $this->ci->setting_model->dump(array('id'=>1), 'setting');
		return $res;
	}

	public function indexProduct($params){
		$region_id = $this->get_region_id($params['region_id']);
		$this->ci->load->model('indexproduct_model');
		$where =array(
			'send_region like'=>'%\"'.$region_id.'\"%',
			'position'=>array(6,7,8,9),
		);
		$product_list = $this->ci->indexproduct_model->getList('position,product_id,show_num,is_adv,adv_photo,adv_product_id,keyword', $where);
		foreach($product_list as $key=>$val){
			if(!empty($val['adv_photo'])){
				$product_list[$key]['adv_photo'] = PIC_URL.$val['adv_photo'];
			}
		}
		return $product_list;
	}

	private function xsh($region_id){
       $current_time = Date('Y-m-d H:i:s');
	   $this->db->select("ttgy_product.id as target_id,thum_photo as photo");
	   $this->db->select("(select price from ttgy_product_price where ttgy_product_price.product_id=ttgy_product.id order by ttgy_product_price.order_id desc  limit 1 )as price");
    //    $this->db->select("(select volume from ttgy_product_price where ttgy_product_price.product_id=ttgy_product.id order by ttgy_product_price.order_id desc  limit 1 )as volume");
	   // $this->db->select("(select old_price from ttgy_product_price where ttgy_product_price.product_id=ttgy_product.id order by ttgy_product_price.order_id desc  limit 1 )as old_price");
	   // $this->db->select("(select id from ttgy_product_price where ttgy_product_price.product_id=ttgy_product.id order by ttgy_product_price.order_id desc  limit 1 )as price_id");
	   $this->db->select("(select stock from ttgy_product_price where ttgy_product_price.product_id=ttgy_product.id order by ttgy_product_price.order_id desc  limit 1 )as stock");
	   $this->db->select("(select over_time from ttgy_product_price where ttgy_product_price.product_id=ttgy_product.id order by ttgy_product_price.order_id desc  limit 1 )as over_time");
	   $this->db->select("(select start_time from ttgy_product_price where ttgy_product_price.product_id=ttgy_product.id order by ttgy_product_price.order_id desc  limit 1 )as start_time");
	   // $this->db->select("(select count(product_id) from ttgy_order_product where ttgy_order_product.product_id=ttgy_product.id group by product_id)as buy_num");
	   $this->db->from("product");
	   $this->db->where(array("app_online"=>1));
	   $this->db->where(array("xsh"=>1,"xsh_display"=>0));
	   if (!empty($region_id)){
			$like['send_region'] = '"'.$region_id.'"';
			$this->db->like($like);
		}
       $this->db->join('product_price', 'product.id=product_price.product_id');
	   $this->db->order_by(" product_price.start_time asc,ttgy_product.order_id desc, ttgy_product.id desc");
	   $query  = $this->db->get();
	   $xsh_array = $query->result_array();
	   $xsh_count = count($xsh_array);
	   $nowtime = date("Y-m-d H:i:m");
	   $xsh_tmp = array();
		foreach ($xsh_array as $key => $value) {
			if($value['start_time']<=$nowtime && $value['over_time']>=$nowtime && $value['stock']>0){
				$countkey = $xsh_count*1+$key;
				$xsh_tmp[$countkey] = $value;
			}elseif($value['start_time']>$nowtime){
				$countkey = $xsh_count*2+$key;
				$xsh_tmp[$countkey] = $value;
			}elseif($value['stock']<=0 && $value['stock']!=-1){
				$countkey = $xsh_count*3+$key;
				$xsh_tmp[$countkey] = $value;
			}else{
				$countkey = $xsh_count*4+$key;
				$xsh_tmp[$countkey] = $value;
			}
		}
		ksort($xsh_tmp);
		foreach ($xsh_tmp as $key => $value) {
			$xsh_result_tmp[] = $value;
		}
	   if(!empty($xsh_result_tmp)){
	   		$xsh_result_tmp[0]['photo'] = PIC_URL.$xsh_result_tmp[0]['photo'];
	   	    $xsh_result_tmp[0]['title'] = '每日特惠';
	   	    $xsh_result_tmp[0]['type'] = '2';
	   	    $xsh_result_tmp[0]['description'] = "天天果园每日特惠";
	   }
	   return $xsh_result_tmp;

	}

	/*
	*获取地区标识
	*/
	private function get_region_id($region_id=106092){
		$region_id = empty($region_id) ? 106092 : $region_id;
		$site_list = $this->ci->config->item('site_list');
		if(isset($site_list[$region_id])){
			$region_result = $site_list[$region_id];
		}else{
			$region_result = 106092;
		}
		return $region_result;
	}

	function chargeRules($params){
//		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
//            if(!$this->ci->memcached){
//                $this->ci->load->library('memcached');
//            }
//            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id'];
//            $money_upto_charge = $this->ci->memcached->get($mem_key);
//            if($money_upto_charge){
//                return $money_upto_charge;
//            }
//        }

		$region_id = $params['region_id'];
		$region = empty($region_id) ? 106092 : $region_id;
		$region_id = $this->get_region_id($region);
		$this->ci->load->model('banner_model');
		$money_upto_charge = $this->ci->banner_model->get_charge_money_upto(1,$region_id);
		if(strcmp($params['version'],'2.3.0')>0){
			$money_upto_charge_tmp['charge_gifts'] = $money_upto_charge;

            $show_time = time();
            if($show_time > 1481558400)
            {
                $money_upto_charge_tmp['charge_rules'] = array(
                    '1.全仓单笔订单充值满1000元赠佳沛新西兰绿奇异果礼盒-20个礼盒装，价值108元。',
                    '2.全仓单笔订单充值满2000元赠美国华盛顿甜脆红地厘蛇果-20个+佳沛新西兰绿奇异果礼盒-20个礼盒装，价值207元。',
                    '3.使用充值卡充值不参与充值送赠品活动。',
                    '4.余额提现时，若参与过充值活动，实际提现金额将扣除赠品价值。',
                    '5.账户余额不支持购买券卡。',
                    '6.如需申请充值发票，只支持申请开具3个月内充值的发票。',
                    '7.活动区域：上海、上海崇明、江苏省、浙江省、安徽省、北京、天津、河北省、广东省',
                    '8.充值卡充值无法开具发票',
                );
            }
            else
            {
                $money_upto_charge_tmp['charge_rules'] = array(
                    '1.全仓单笔订单充值满512元赠新疆寒富苹果-4斤，价值59.9元。',
                    '2.全仓单笔订单充值满1000元赠佳沛新西兰绿奇异果礼盒-20个礼盒装，价值108元。',
                    '3.全仓单笔订单充值满1212元赠新疆寒富苹果4斤+智利爱阁鸡翅中-1000g，价值119元。',
                    '4.全仓单笔订单充值满2000元赠美国华盛顿甜脆红地厘蛇果-20个+佳沛新西兰绿奇异果礼盒-20个礼盒装，价值207元。',
                    '5.使用充值卡充值不参与充值送赠品活动。',
                    '6.余额提现时，若参与过充值活动，实际提现金额将扣除赠品价值。',
                    '7.账户余额不支持购买券卡。',
                    '8.如需申请充值发票，只支持申请开具3个月内充值的发票。',
                    '9.活动区域：上海、上海崇明、江苏省、浙江省、安徽省、北京、天津、河北省 、广东省。',
                    '10.充值卡充值无法开具发票',
                );
            }
			$money_upto_charge = $money_upto_charge_tmp;
		}
//		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
//            if(!$this->ci->memcached){
//                $this->ci->load->library('memcached');
//            }
//            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id'];
//            $this->ci->memcached->set($mem_key,$money_upto_charge,600);
//        }
		return $money_upto_charge;
	}

	function userActive($uid,$sign){
		header("Content-Type: text/html;charset=utf-8");
		$msg = '';
		if($sign!=$this->create_sign($uid)){
			$msg = '用户信息验证错误';
			$data['msg'] = $msg;
			$this->load->view('offline_active',$data);
		}else{
			$this->load->model('appuser');
			$user_info = $this->appuser->get_user_info($uid);
			$user_rank = $user_info['user_rank'];
			$badge = unserialize($user_info['badge']);
	        $user_badge = 0;
	        if(is_array($badge)){
	            $user_badge = $badge[0];
	        }
	        if($this->input->cookie('ttgyofflinestaff')){
	        	$erp_tag = $this->input->cookie('ttgyofflinestaff');
		        $this->load->model('appuser');
		        if(!$this->appuser->check_staff_sign_in($erp_tag)){
		        	echo '签到的活动已过期，请重新签到';
					exit;
		        }
		        $result = $this->appuser->offline_active($uid);
		        if(isset($result['code']) && $result['code']=='300'){
		        	$data['msg'] = $result['msg'];
					$this->load->view('offline_active',$data);
		        }else{
		        	if(strstr($erp_tag, 'fdayjuice')){
		        		$juice_active = array();
		        		$msg = '';
		        		foreach ($result as $key => $value) {
		        			if($value['active_type']=='3'){
		        				$juice_active['active_id'] = $value['id'];
		        				$now_date = date("Y-m-d");
		        				if($value['start_time']>$now_date || $value['end_time']<$now_date){
		        					$juice_active['msg'] = "当前没有进行中的活动";
		        				}
		        			}
		        		}
						$juice_active['uid'] = $uid;
						$juice_active['erp_tag'] = $erp_tag;
						$this->load->view('juice_active_detail',$juice_active);
		        	}else{
		        		foreach ($result as $key => $value) {
		        			if($value['active_type']=='3'){
		        				unset($result[$key]);
		        			}
		        		}
			        	$data['active_list'] = $result;
			        	$data['uid'] = $uid;
			        	$data['sign'] = $sign;
			        	$this->load->view('offline_active',$data);
		        	}
		        }
	        }else{
	        	$user_info['badge'] = ($user_badge==0)?'普通会员':'鲜果达人';
	        	$user_rank_config = $this->config->item('user_rank');
	        	$user_info['user_rank'] = $user_rank_config['level'][$user_rank]['name'];
	        	$data['user_info'] = $user_info;
				$this->load->view('user_info',$data);
	        }
		}

	}

	function offline_active_detail($uid,$sign,$active_id){
		$msg = '';
		if($sign!=$this->create_sign($uid)){
			$msg = '用户信息验证错误';
			$data['msg'] = $msg;
			$this->load->view('offline_active_detail',$data);
		}else{
			$data['operation_config'] = $this->config->item('operation');
			$this->db->select('product_id');
			$this->db->from('offline_badge_active');
			$this->db->where('id',$active_id);
			$result = $this->db->get()->row_array();
			if(!empty($result)){
				$product_ids = $result['product_id'];
				$sql = "select o.id,o.order_name,o.time,o.pay_name,o.money,o.pay_status,o.operation_id,o.pay_parent_id from ttgy_order as o join ttgy_order_product as p on p.order_id=o.id where o.order_status=1 and o.operation_id!=5 and p.product_id in(".trim($product_ids,',').") and o.uid=".$uid;
				$query = $this->db->query($sql);
				$order_result = $query->row_array();
				if(empty($order_result)){
					$msg = "未找到相关订单";
					$data['msg'] = $msg;
				}else{
					$this->db->select('product_id,product_name,gg_name,price,qty');
					$this->db->from('order_product');
					$this->db->where('order_id',$order_result['id']);
					$product_result = $this->db->get()->result_array();
					$order_result['order_product'] = $product_result;
					$data['order_info'] = $order_result;
					$product_id_arr = explode(',', $product_ids);
					foreach ($product_result as $key => $value) {
						if(in_array($value['product_id'], $product_id_arr)){
							unset($product_result[$key]);
						}
					}

					if($order_result['pay_status']=='0' && $order_result['pay_parent_id']!='4'){
						$msg = "订单未支付";
						$data['msg'] = $msg;
					}

					if(count($product_result)!=0){
						$msg = '该订单含有其它商品';
						$data['msg'] = $msg;
					}

					if($order_result['operation_id']!='0'){
						$msg = "订单状态是".$data['operation_config'][$order_result['operation_id']]."，不能操作发货";
						$data['msg'] = $msg;
					}
				}
			}else{
				$msg = '当前没有进行的活动';
				$data['msg'] = $msg;
			}
			$data['active_id'] = $active_id;
			$data['uid'] = $uid;
			$data['pay_config'] = $this->config->item('pay');
			// echo '<pre>';print_r($data['order_info']);exit;
			$this->load->view('offline_active_detail',$data);
		}
	}

	function accept_offline_active(){
		foreach ($_POST as $key => $value) {
			if(empty($value)){
				echo json_encode(array('result'=>'error','msg'=>$key.'不能为空'));
				exit;
			}
		}
		$uid = $_POST['uid'];
		$active_id = $_POST['id'];
		if($this->input->cookie('ttgyofflinestaff')){
			$this->load->model('appuser');
			$result = $this->appuser->accept_offline_active($uid,$active_id);
			if($result['code']=='300'){
				echo json_encode(array('result'=>'error','msg'=>$result['msg']));
				exit;
			}elseif($result['code']=='200'){
				if(isset($_POST['order_name']) && $_POST['order_name']!=''){
					$order_update_date = array(
						"sync_erp"=>'0',
						"operation_id"=>'2',
						"erp_active_tag"=>$this->input->cookie('ttgyofflinestaff')
					);
					$this->db->where('order_name',$_POST['order_name']);
					$this->db->update('order',$order_update_date);
				}
				echo json_encode(array('result'=>'succ','msg'=>$result['msg']));
				exit;
			}
		}else{
			echo json_encode(array('result'=>'error','msg'=>'请先登陆员工帐号'));
			exit;
		}

	}

	function accept_juice_active(){
		foreach ($_POST as $key => $value) {
			if(empty($value)){
				echo json_encode(array('result'=>'error','msg'=>$key.'不能为空'));
				exit;
			}
		}
		$uid = $_POST['uid'];
		$active_type = $_POST['active_type'];
		$erp_tag = $_POST['erp_tag'];
		$product_type = $_POST['product_type'];
		if(!in_array($active_type, array('day','week'))){
			echo json_encode(array('result'=>'error','msg'=>'活动类型错误'));
			exit;
		}
		if($this->input->cookie('ttgyofflinestaff')){
			$this->db->select('user_rank');
			$this->db->from('user');
			$this->db->where('id',$uid);
			$user_info = $this->db->get()->row_array();
			$user_rand_setting = $this->config->item('user_rank');
			$active_setting = $user_rand_setting['level'][$user_info['user_rank']]['juice'];

			if($active_type=='day'){
				$timd_range = date("Y-m-d 00:00:00");
			}elseif ($active_type=='week') {
				$timd_range = date("Y-m-d 00:00:00",strtotime("last Week"));
			}
			$this->db->select('time');
			$this->db->from('juice_active_log');
			$this->db->where(array('uid'=>$uid,'active_type'=>$active_type,'time >='=>$timd_range));
			$active_log = $this->db->get()->result_array();
			if($active_setting[$active_type.'_num']==0){
				echo json_encode(array('result'=>'error','msg'=>'该用户没有权限参加该活动'));
				exit;
			}
			if(!empty($active_log)){
				$date_tmp = array('day'=>'天','week'=>'周');
				if(count($active_log)>=$active_setting[$active_type.'_num']){
					echo json_encode(array('result'=>'error','msg'=>'无法参与该活动，该活动每'.$date_tmp[$active_type].'只能参加'.$active_setting[$active_type.'_num'].'次'));
					exit;
				}else{
					$insert_data = array(
					'uid'=>$uid,
					'active_type'=>$active_type,
					'time'=>date("Y-m-d H:i:s"),
					'money'=>$active_setting[$active_type.'_money'],
					'product_type'=>$product_type,
					'erp_tag'=>$erp_tag
					);
					$this->db->insert('juice_active_log',$insert_data);
					echo json_encode(array('result'=>'succ','msg'=>'验证成功，请收取'.$active_setting[$active_type.'_money'].'元。'));
					exit;
				}
			}else{
				$insert_data = array(
					'uid'=>$uid,
					'active_type'=>$active_type,
					'time'=>date("Y-m-d H:i:s"),
					'money'=>$active_setting[$active_type.'_money'],
					'product_type'=>$product_type,
					'erp_tag'=>$erp_tag
				);
				$this->db->insert('juice_active_log',$insert_data);
				echo json_encode(array('result'=>'succ','msg'=>'验证成功，请收取'.$active_setting[$active_type.'_money'].'元。'));
				exit;
			}
		}else{
			echo json_encode(array('result'=>'error','msg'=>'请先登陆员工帐号'));
			exit;
		}

	}

	function staff_login($id,$erp_tag,$sign){
		header("Content-Type: text/html;charset=utf-8");
		if(empty($id) || empty($erp_tag) || empty($sign)){
			echo '签到错误';exit;
		}
		$check_sign = md5(substr(md5($id.$erp_tag), 0,-1).'s');
		if($check_sign!=$sign){
			echo '签名错误';exit;
		}
		$this->db->select('start_time,end_time');
		$this->db->from('staff_sign_in');
		$this->db->where('id',$id);
		$result = $this->db->get()->row_array();
		$now_data = date("Y-m-d");
		if($result['start_time']>$now_data && $result['end_time']<$now_data){
			echo '签到超时';exit;
		}
		$exp_time = strtotime($result['end_time'])-strtotime($result['start_time']);
		$ttgy_tglog_key = $erp_tag;
		$this->input->set_cookie('ttgyofflinestaff',$ttgy_tglog_key,$exp_time);
		echo '签到成功';
	}

	function get_base_url(){
		$base_url = $this->config->item('base_url');
		if(empty($base_url)){
			$base_url = 'http://wapi.fruitday.com';
		}else{
			$base_url = $this->config->item('domain_url');
		}
		$res = array('base_url'=>$base_url);
		echo json_encode($res);
	}
        /**
         * 获取会员
         *
         * @return void
         * @author
         **/
        public function get_userid()
        {
	    $this->ci->load->library('login');
	    $this->ci->login->init($this->_sessid);

  	    return $this->ci->login->get_uid();
        }

	private function create_sign($str){
        $sign = md5(substr(md5($str.API_SECRET), 0,-1).'s');
        return $sign;
    }

    /**
     * 新版 App 首页广告 3.9.0
     */
    public function mobileHomepage($params)
    {
        // 仅用于 app、wap
        if ($params['source'] == 'pc') {
            return ['code' => '400', 'msg' => 'pc 获取数据失败'];
        }

        // 检查参数
        $required = [
            'region_id' => ['required' => ['code' => '500', 'msg' => 'region id can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
		}

        // 参数处理
        $this->ci->load->bll('deliver');
        $warehouseInfo = $this->ci->bll_deliver->getTmsCode($params);
//        $isUseCang = $this->ci->config->item('is_enable_cang');
        $isUseCang = 1;
        $areaId = 0;
        $whereArea = '';
        if ($isUseCang) { // warehouse
            $areaId = $warehouseInfo['cang_id'];
            $whereArea = '(cang_id LIKE \'' . $areaId . ',%\' OR cang_id LIKE \'%,' . $areaId . ',%\' OR cang_id LIKE \'%,' . $areaId . '\' OR cang_id = \'' . $areaId . '\')';
        } else { // region
            $areaId = $this->get_region_id($params['region_id']);
            $whereArea = 'send_region LIKE \'%"' . $areaId . '"%\'';
        }
        $defaultChannle = $this->ci->config->item('default_channle');
        $channel = empty($params['channel']) ? ['portal'] : ['portal', $params['channel']];
		if (in_array($channel, $defaultChannle)) {
			$channel = ['portal'];
		}
        $dateTime = date("Y-m-d H:i:s", time());
        $hour = date("H");
        $userType = $this->getUserType($params['device_id'], $params['connect_id']);

        // 组建 memcache key 值
        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version']
                        . "_" . $areaId . "_" . implode('_', $channel) . "_" . $userType;

        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$data = $this->ci->memcached->get($memKey);
			if ($data) {
				return ['code' => '200', 'msg' => 'succ', 'data' => $data];
			}
		}

        // 获取广告公共条件
        $where = [
            'is_show' => 1,
            'channel' => $channel,
            $whereArea => null,
            'user_type like \'%' . $userType . '%\'' => null,
        ];
//        if ($params['version'] >= '4.2.0') {
//            $where["((start_time IS NULL AND end_time IS NULL) OR (end_time >= '" . $dateTime . "'))"] = null;
//        } else {
            $where["((start_time IS NULL AND end_time IS NULL) OR (start_time <= '" . $dateTime . "' AND end_time >= '" . $dateTime . "'))"] = null;
//        }

        switch ($params['source']) {
            case 'app':
				$where['app'] = 1;
				break;
			case 'wap':
				$where['wap'] = 1;
				break;
            case 'pc':
                $where['pc'] = 1;
                break;
			default:
				break;
        }

        // 获取分组信息，sort 正序排列
        $this->ci->load->model('bgroup_model');
        $groups = $this->ci->bgroup_model->getList('*', ['is_show' => 1], 0, -1, 'sort');
        if (!$groups) {
            return ['code' => 300, 'msg' => '没有分组数据'];
        }

        // 获取数据
        $data = [];
        $this->ci->load->model('banner_model');
        $iconGroupKey = 0;

        // app-首页-icon背景图
        $iconBackground = $this->ci->banner_model->getList('*', array_merge($where, ['position' => 64]), 0, -1, 'sort DESC');

        foreach ($groups as $group) {
            // 附加条件
            $additional = [
                'position' => 63,
                'group_id' => $group['id'],
            ];

            switch ($group['type']) {
                case 1: // app轮播
                    $type = 'rotationBanner'; break;
                case 2: // 天天快报
                    $type = 'newsBanner'; break;
                case 3: // icon列表
                    $type = 'iconBanner'; break;
                case 4: // 文字标题
                    $type = 'titleBanner'; break;
                case 5: // 横排单个商品
                    $type = 'singleProBanner'; break;
                case 6: // 横排多个商品-标签在图下
                    $type = 'proBannerTagUnderImage'; break;
                case 7: // 1+2广告位
                    $type = '1plus2nBanner'; break;
                case 8: // 横排多个商品-大图滑动
                    $type = 'proBannerBigImage'; break;
                case 9: // 1+n广告位
                    $type = '1plusnBanner'; break;
                case 10: // 普通大图广告位
                    $type = 'bigImageBanner'; break;
                case 11: // 抢鲜集市
                    $type = 'countdownBanner';
                    $additional['morning_or_night'] = $hour <= 14 ? 1 : 2;
                    break;
                case 12: // 横排多个商品-标签在图左上
                    $type = 'proBannerTagTopLeft'; break;
                case 13: // 单品广告位
                    $type = 'normalBanner'; break;
                case 14: // 横排2个广告位
                    $type = '2nBanner'; break;
            }

            // 获取分组广告信息，sort 倒序排列
            $banners = $this->ci->banner_model->getList('*', array_merge($where, $additional), 0, -1, 'sort DESC, id DESC');
            if (count($banners) == 0) { continue; }

            $content = [];
            foreach ($banners as $banner) {
                // App首页根据版本号筛选发现广告位
                if (
                        ($params['source'] == 'app' && $params['version'] >= '4.3.0' && in_array($banner['type'], [10, 11, 15, 16]))
                        || ($params['source'] == 'app' && $params['version'] < '4.3.0' && in_array($banner['type'], [30, 31, 32, 33]))
                ) { continue; }

                $temp = $this->formatBanner($banner, $group['type'], $warehouseInfo['store_id'],$warehouseInfo['cang_id'], $params['source']);

                if ($temp) {
                    $content[] = $temp;
                }
            }
            if (count($content) > 0) {
                $groupInfo = [
                    'group_type' => $type,
                    // 用户数据分析
                    'group_id' => $group['id'],
                    'content' => $content,
                ];

                // icon 背景图
                if ($type == 'iconBanner' && isset($iconBackground[$iconGroupKey])) {
                    $groupInfo['background'] = PIC_URL . $iconBackground[$iconGroupKey]['photo'];
                    $iconGroupKey++;
                }

                $data['mainBanners'][] = $groupInfo;
            }
        }

        // 浮动广告位，最新的一个
        $floatBanner = $this->ci->banner_model->getList('*', array_merge($where, ['position' => 90]), 0, 1, 'sort DESC');
        if ($floatBanner) {
            $temp = $this->formatBanner($floatBanner[0],0,$warehouseInfo['store_id'],$warehouseInfo['cang_id']);
            if ($temp) {
                $data['floatBanner'] = $temp;
            }
        }

        // h5动画，最新的一个
        $h5FlashBanner = $this->ci->banner_model->getList('*', array_merge($where, ['position' => 65]), 0, 1, 'sort DESC');
        if ($h5FlashBanner && $h5FlashBanner[0]['page_url']) {
            $data['h5Flash'] = $h5FlashBanner[0]['page_url'];
        }

        // 底部tab可换图
        $this->ci->load->model('banner_icon_model');
        $bottomTabs = $this->ci->banner_icon_model->getList('type, img, img_pitch', ['state' => 1]);
        if ($bottomTabs) {
            foreach ($bottomTabs as &$tab) {
                $tab['img'] = PIC_URL . $tab['img'];
                $tab['img_pitch'] = PIC_URL . $tab['img_pitch'];
            }
            $data['bottomTabs'] = $bottomTabs;
        }

        // 公共配置项
        $this->ci->load->model('setting_model');
        $promotionTagColor = $this->ci->setting_model->dump([
            'type' => 'pro_promotion_tag_color',
        ], 'setting');
        $data['promotion_tag_color'] = $promotionTagColor['setting'];

        // 没有数据
        if (!$data) {
            return ['code' => 300, 'msg' => '没有分组数据'];
        }

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$this->ci->memcached->set($memKey, $data, 600);
		}
        return ['code' => 200, 'msg' => 'succ', 'data' => $data];
    }

    /**
     * 获取用户类型
     *
     * @param string $deviceId 设备号
     * @param string $connectId 登录标识
     * @return string [ unregistered | v0 | register_no_buy | v1 | v2 | v3 | v4 | v5 | ... ]
     */
    protected function getUserType($deviceId, $connectId)
    {
        $userType = '';
        $uid = 0;

        // 设备是否绑定过会员号
        $isBindUser = 0;
        if ($deviceId) {
            $this->ci->load->model('user_mobile_data_model');
            $isBindUser = $this->ci->user_mobile_data_model->count([
                'device_id' => $deviceId,
                'uid !=' => '0',
            ]);
        }

        // 未登录
        if (empty($connectId)) {
            if (!$isBindUser) {
                // 未注册用户
                $userType = 'unregistered';
            } else {
                // 获取设备最后绑定的用户ID
                $uids = $this->ci->user_mobile_data_model->getList('uid', ['device_id' => $deviceId], 0, 1, 'id DESC');
                $uid = $uids[0]['uid'];
            }
        }
        // 已经登陆
        else {
            // 根据 connect id 获取用户ID
            $this->ci->load->library('session', ['session_id' => $connectId]);
            $session = $this->ci->session->userdata;
            $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
            $uid = $userdata['id'];
        }

        // 根据用户ID获取用户类型
        $this->ci->load->model('user_model');
        if ($uid) {
            $userInfo = $this->ci->user_model->dump(['id' => $uid], 'user_rank');
            // 已注册用户
            $userType = 'v' . ($userInfo['user_rank'] - 1);
        }

        // 判断 v0 用户订单情况
        if ($userType == 'v0') {
            $this->ci->load->model('order_model');
            $orderCount = $this->ci->order_model->count([
                'order_status' => '1',
                'pay_status' => '1',
                'operation_id !=' => '5',
                'uid' => $uid,
            ]);
            if ($orderCount == 0) {
                // 注册未购买用户
                $userType = 'register_no_buy';
            }
        }

        return $userType;
    }

    /**
     * 格式化广告数据结构
     *
     * @param array $banner
     * @param int $type
     * @param int $storeId
     * @param string $source
     * @return array
     */

    protected function formatBanner($banner, $type = 0, $storeId = '',$ware_id=0, $source = 'app')

    {
        $this->ci->load->model('product_model');
        $this->ci->load->model('product_price_model');
        $this->ci->load->model('cat_model');

        // 格式化数据
        $photo = $banner['photo'];
        $title = $banner['title'];
        $desc = $banner['description'];

        // 商品信息
        $productInfo = $priceInfo = $stockInfo = [];
        $isSale = 1;
        $offline = 0;
        if (in_array($type, [5, 6, 8, 9, 11, 12, 13])) {
            // 读取商品信息（目标类型：商品详情、链接、O2O商品详情）
            if ($banner['target_id'] && in_array($banner['type'], [2, 6, 13])) {
                $proWhere = ['id' => $banner['target_id']];
                // 商品详情的广告，限制条件：app 上架、wap 上架
                if (in_array($banner['type'], [2, 6])) {
                    $onlineField = '';
                    switch ($source) {
                        case 'app':
                            $onlineField = 'app_online';
                            break;
                        case 'wap':
                            $onlineField = 'mobile_online';
                            break;
                    }
                    $proWhere[$onlineField] = 1;
                    $offline = 1;
                }
                $product = $this->ci->product_model->selectProducts('product_name, product_desc, promotion_photo, cart_tag, promotion_tag, tag_begin_time, tag_end_time, template_id', $proWhere);
                // 广告位的图片、标题、副标题已广告位配置为主，商品信息为辅
                if ($product) {
                    $productInfo = $product[0];

                    // 获取产品模板图片
                    if ($productInfo['template_id']) {
                        $this->ci->load->model('b2o_product_template_image_model');
                        $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($productInfo['template_id'], 'whitebg');
                        if (isset($templateImages['whitebg'])) {
                            $productInfo['promotion_photo'] = $templateImages['whitebg']['image'];
                        }
                    }

                    $photo = empty($photo) ? $productInfo['promotion_photo'] : $photo;
                    $title = empty($title) ? $productInfo['product_name'] : $title;
                    $desc = empty($desc) ? $productInfo['product_desc'] : $desc;

			        if (!empty($productInfo['cart_tag']) and $productInfo['tag_begin_time'] > 0 and $productInfo['tag_end_time'] > 0) {
			            $iNowUnixTime = $_SERVER['REQUEST_TIME'];

			            if ($iNowUnixTime < $productInfo['tag_begin_time'] or $iNowUnixTime > $productInfo['tag_end_time']) {
			                $productInfo['cart_tag'] = '';
			            }
			        }
                    $offline = 0;
                }

                // 获取 SKU 信息
                $wherePrice = ['product_id' => $banner['target_id']];
                if ($banner['target_price_id']) {
                    $wherePrice['id'] = $banner['target_price_id'];
                }
                $productPrice = $this->ci->product_price_model->getList('id, price, old_price, volume,product_no', $wherePrice, 0, 1, 'order_id');
                if ($productPrice) {
                    $priceInfo = $productPrice[0];
                    $stockInfo = $this->ci->product_model->getRedisProductStock($priceInfo['id'],$ware_id,0,$priceInfo['product_no']);
                    $isSale = ($stockInfo['use_store'] == 1 && $stockInfo['stock'] == 0) ? '0' : '1';
                }
            }
        }

        // 分类详情
        if (in_array($banner['type'], [27])) {
            if (empty($banner['target_id'])) {
                return false;
            } else {
                $classInfo = $this->ci->cat_model->dump([
                    'is_show' => 1,
                    'parent_id' => 0,
                    'id' => $banner['target_id'],
                ], 'name');
                if ($classInfo) {
                    $title = $classInfo['name'];
                } else {
                    return false;
                }
            }
        }

        // 早晚市
        $startTime = $endTime = '';
        if ($banner['morning_or_night'] == 1) {
            $startTime = date("Y-m-d 08:00:00");
            $endTime = date("Y-m-d 14:00:00");
        } elseif ($banner['morning_or_night'] == 2) {
            $startTime = date("Y-m-d 20:00:00");
            $endTime = date("Y-m-d 24:00:00");
        }

//        // 单品广告在结束时间之前都显示（距开始、距结束）
        // 单品广告位显示倒计时
        if ($type == 13) {
            $startTime = $banner['start_time'] ?: '';
            $endTime = $banner['end_time'] ?: '';
        }
//        // 普通广告位在开始时间和结束时间之间显示
//        elseif ($_SERVER['REQUEST_TIME'] < strtotime($banner['start_time'])) {
//            return false;
//        }

        /**
         * 如果广告商品没货、已下架，返回 false
         * 如果 $storeId 为空，o2o 商品详情广告、o2o H5链接广告，返回 false
         */
        if ($isSale == '0' || $offline == 1 || (empty($storeId) && in_array($banner['type'], [13, 26]))) {
            return false;
        }

        return [
            'photo' => empty($photo) ? '' : (PIC_URL . $photo),
            // target
            'type' => $banner['type'],
            'target_id' => $banner['target_id'],
            'page_url' => $banner['page_url'],
            // other
            'title' => $title,
            'desc' => $desc,
            'store_id' => in_array($banner['type'], [13, 26]) ? $storeId : '',
            'active_desc' => $banner['active_desc'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_more' => $banner['is_more'],
            'need_navigation_bar' => $banner['need_navigation_bar'],
            // product
            'banner_tag' => isset($productInfo['cart_tag']) ? $productInfo['cart_tag'] : '',
            'promotion_tag' => isset($productInfo['promotion_tag']) ? $productInfo['promotion_tag'] : '',
            'volume' => isset($priceInfo['volume']) ? $priceInfo['volume'] : '',
            'original_price' => isset($priceInfo['old_price']) ? strval((float)$priceInfo['old_price']) : '',
            'current_price' => isset($priceInfo['price']) ? strval((float)$priceInfo['price']) : '',
            'product_store' => $isSale, // 0 没货、1 有货
        ];
    }
}
