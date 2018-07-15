<?php

namespace bll\app;
include_once("app.php");

/**
* 商品相关接口
*/
class Marketing extends app
{
	public function __construct($params = array())
    {
        parent::__construct();

        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }

    function banner($params){
		$this->ci->load->model('user_model');
		$this->ci->user_model->add_connectid_region_id($params['connect_id'],$params['region_id']);
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id']."_".$params['channel'];
            $data = $this->ci->memcached->get($mem_key);
            if($data){
                return $data;
            }
        }

		$banner_list = parent::call_bll($params);

		$rotation_banner_list = array();
		$top_banner_list = array();
		$normal_banner_list = array();
		$mix_banner_list = array();
		$horizontal_banner_list = array();
		$mobile_product_list = array();
		$qiangxian_product_list = array();

        //4in1
        $fourth_banner_list = array();

        //icons
        $sd_icon_url = array();  //新品速递
        $th_icon_url = array();  //天天特惠
        $yp_icon_url = array();   //优品生活
        $yg_icon_url = array();   //员工关爱

        $foretaste_banner_list = array();   //试吃

        $home_market_banner = array();   //app-浮动广告

		$is_store_exist = isset($banner_list['is_store_exist'])?$banner_list['is_store_exist']:0;
		$is_o2o_initial = isset($banner_list['is_o2o_initial'])?$banner_list['is_o2o_initial']:0;
		$chennel_icons = isset($banner_list['chennel_icons'])?$banner_list['chennel_icons']:'';
		unset($banner_list['is_store_exist']);
		foreach ($banner_list as $key => &$value) {
			//判断版本
			if(strcmp($params['version'],'2.3.0')<0 && $value['type']==12){
				continue;
			}

			$is_top = $value['is_top'];
			unset($value['is_top']);
			switch ($value['position']) {
				case '0':
					$rotation_banner_list[] = $value;
					break;
				case '1':
					if($is_top == 1){
						$top_banner_list[] = $value;
					}else{
						$normal_banner_list[] = $value;
					}
					break;
				case '14':
					$mobile_product_list[] = $value;
					break;
				case '15':
					$qiangxian_product_list[] = $value;//todo by lusc
					break;
				case '16':
					$mix_banner_list[] = $value;//todo by lusc
					break;
				case '18':
					$horizontal_banner_list[] = $value;//todo by lusc
					break;
                case '19':
                    $fourth_banner_list[] = $value;//todo by jackchen
                    break;
                case '20':
                    $sd_icon_url[] = $value;//todo by jackchen
                    break;
                case '21':
                    $th_icon_url[] = $value;//todo by jackchen
                    break;
                case '22':
                    $yp_icon_url[] = $value;//todo by jackchen
                    break;
                case '23':
                    $foretaste_banner_list[] = $value;//todo by jackchen
                    break;
                case '24':
                    $yg_icon_url[] = $value;//todo by jackchen
                    break;
                case '90':
                    $home_market_banner[] = $value;//todo by jackchen
                    break;
				default:
					# code...
					break;
			}
		}

		// $day_product_list = $this->xsh($region_id);//todo
		$data = array(
			'rotation'=>$rotation_banner_list,
			'banner'=>$normal_banner_list,
			'top_banner'=>$top_banner_list,
			'mobile_product_list'=>$mobile_product_list,
			'qiangxian_product_list'=>$qiangxian_product_list,
			//'mix_product_banner'=>$mix_banner_list,
			'horizontal_product_banner'=>$horizontal_banner_list,
			'is_store_exist'=>$is_store_exist,
			'is_o2o_initial'=>$is_o2o_initial,
			'register_gift_desc'=>'时令鲜果一份',
			'shake_url' => 'http://huodong.fruitday.com/sale/shake_v3/app.html?', //'http://huodong.fruitday.com/sale/shake/index.html?',
            'foretaste_banner'=>$foretaste_banner_list,
            'home_market'=>$home_market_banner
		);

        if(count($sd_icon_url) >0)
        {
            $data['new_url']= $sd_icon_url[0]['page_url'];
        }
        else
        {
            $data['new_url'] = '';
        }

        if(count($th_icon_url) >0)
        {
            $data['preferential_url']= $th_icon_url[0]['page_url'];
        }
        else
        {
            $data['preferential_url'] = '';
        }

        if(count($yp_icon_url) >0)
        {
            $data['superior_url']= $yp_icon_url[0]['page_url'];
        }
        else
        {
            $data['superior_url'] = '';
        }

        if(count($yg_icon_url) >0)
        {
            $data['company_user_url']= $yg_icon_url[0]['page_url'];
        }
        else
        {
            $data['company_user_url'] = '';
        }


		if(strcmp($params['version'],'3.2.0') >= 0)
        {
        	$data['register_gift_desc'] = ' 享新客专属折扣';
        }

        if(strcmp($params['version'],'3.1.0') > 0)
        {
            $fourth_count = count($fourth_banner_list);
            if($fourth_count%4 == 0)
            {
                $data['fourInOne_banner'] = $fourth_banner_list;
            }
            else{
                $data['fourInOne_banner'] = array();
            }
        }
        else if(strcmp($params['version'],'3.1.0') == 0)  //由于ios bug 调整版本
        {
            $fourth_count = count($fourth_banner_list);
            if($fourth_count%4 == 0)
            {
                $ds[0] = $fourth_banner_list[0];
                $ds[1] = $fourth_banner_list[1];
                $ds[2] = $fourth_banner_list[2];
                $ds[3] = $fourth_banner_list[3];
                $data['fourInOne_banner'] = $ds;
            }
            else{
                $data['fourInOne_banner'] = array();
            }
        }
        else{
            $mix_count = count($mix_banner_list);
            if($mix_count%3 == 0)
            {
                $data['mix_product_banner'] = $mix_banner_list;
            }
            else{
                $data['mix_product_banner'] = array();
            }
        }

		if(!empty($chennel_icons)){
            if(strcmp($params['version'],'3.3.0') < 0)
            {
                $data['chennel_icons']=$chennel_icons;
            }
            else
            {
                $chennel_icons['logistic_service'] = $chennel_icons['top_up'];
                $chennel_icons['top_up'] =  $chennel_icons['flash_sale'];

                $data['chennel_icons']=$chennel_icons;
            }
		}
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['region_id']."_".$params['channel'];
            $this->ci->memcached->set($mem_key,$data,600);
        }
		return $data;
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

    /**
     * 获取 App 首页布局
     */
    public function homepageLayout($params)
    {
        // 检查参数
        $required = array(
			'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
		);
        $checkResult = check_required($params, $required);
		if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
		}

        $regionId = $this->get_region_id($params['region_id']);
        $defaultChannle = $this->ci->config->item('default_channle');
        $channel = empty($params['channel']) ? array('portal') : array('portal', $params['channel']);
		if (in_array($channel, $defaultChannle)) {
			$channel = array('portal');
		}

        // 设备是否绑定过会员号
        $this->ci->load->model('user_mobile_data_model');
        $isBindUser = $this->ci->user_mobile_data_model->count(array(
            'device_id' => $params['device_id'],
            'uid !=' => '0',
        ));

        $this->ci->load->model('user_model');
        $uid = 0;
        // 未登录
        if (empty($params['connect_id'])) {
            if (!$isBindUser) {
                $userType = 'unregistered';
            } else {
                $uids = $this->ci->user_mobile_data_model->getList('uid', array('device_id' => $params['device_id']), 0, 1, 'id DESC');
                $uid = $uids[0]['uid'];
            }
        }
        // 已经登陆
        else {
            $this->ci->load->library('session', array('session_id' => $params['connect_id']));
            $session = $this->ci->session->userdata;
            $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
            $uid = $userdata['id'];
        }
        if ($uid) {
            $userInfo = $this->ci->user_model->dump(array('id' => $uid), 'user_rank');
            $userType = 'v' . ($userInfo['user_rank'] - 1);
        }
        if ($userType == 'v0') {
            $this->ci->load->model('order_model');
            $orderCount = $this->ci->order_model->count(array(
                'order_status' => '1',
                'pay_status' => '1',
                'operation_id !=' => '5',
                'uid' => $uid,
            ));
            if ($orderCount == 0) {
                $userType = 'register_no_buy';
            }
        }

        $memKey = $params['service'] . "_" . $params['source'] . "_" . $params['version']
                        . "_" . $regionId . "_" . implode('_', $channel) . "_" . $userType;

        // 尝试获取缓存数据
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$data = $this->ci->memcached->get($memKey);
			if ($data) {
				return array('code' => '200', 'msg' => 'succ', 'data' => $data);
			}
		}

        $this->ci->load->model('banner_disply_group_model');
        $pageCode = 'mobile-index';
        if ($params['version'] >= '3.9.0') {
            $pageCode = 'mobile-index-390';
        }
        $displayGroups = $this->ci->banner_disply_group_model->getDisplayGroup($channel, $params['source'], $regionId, $pageCode);
        if (!$displayGroups) {
            return array('code' => 300, 'msg' => '没有分组数据');
        }

        $data = array();
        foreach ($displayGroups as $group) {
            // 用户类型
            if (!strstr($group['user_type'], $userType)) {
                continue;
            }

            switch ($group['banner_type_id']) {
                case 1: // 首页轮播
                    $type = 'rotationBanner'; break;
                case 2: // 导航Icon
                    $type = 'iconBanner'; break;
                case 3: // 1+2广告位
                    $type = 'topBanner'; break;
                case 4: // 爆款专区
                    $type = 'horizontalBanner_TypeOne'; break;
                case 5: // 4in1广告位
                    $type = 'fourInOneBanner'; break;
                case 6: // 底部轮播
                    $type = 'horizontalBanner_TypeTwo'; break;
                case 7: // 单品广告位
                    $type = 'normalBanner'; break;
            }

            $banners = $this->ci->banner_disply_group_model->getDisplayGroupBanner($group['group_id']);
            // 检查 banner 数量
            if (
                count($banners) == 0 ||
                ($type == 'fourInOneBanner' && count($banners) != 4)
            ) { continue; }

            $content = array();
            foreach ($banners as $banner) {
                if ($type == 'iconBanner') {
                    // 新客：未注册、注册未购买
                    $newCustomer = in_array($userType, array('unregistered', 'register_no_buy')) ? 1 : 0;
                    // 如果不是新客，隐藏新客专享
                    if (!$newCustomer && in_array($banner['banner_target_url'], array(
                            'http://huodong.fruitday.com/b2cCard/index/303?',
                            'http://huodong.fruitday.com/b2cCard/index/304?',
                            'http://huodong.fruitday.com/cms/indexapp/686?',
                            'http://huodong.fruitday.com/cms/indexapp/594?',
                            'http://huodong.fruitday.com/cms/indexapp/595?',
                            'http://huodong.fruitday.com/cms/indexapp/596?',
                            'http://huodong.fruitday.com/cms/indexapp/597?',
                            'http://huodong.fruitday.com/cms/indexapp/598?',
                            'http://huodong.fruitday.com/cms/indexapp/639?',

                            // 美国西北樱桃 start 160608
                            'http://huodong.fruitday.com/b2cCard/index/269?',
                            'http://huodong.fruitday.com/b2cCard/index/270?',
                            'http://huodong.fruitday.com/b2cCard/index/271?',
                            'http://huodong.fruitday.com/b2cCard/index/273?',
                            'http://huodong.fruitday.com/b2cCard/index/274?',
                    ))) {
                        continue;
                    }
                    // 如果是新客，隐藏会员专享
                    if ($newCustomer && $banner['banner_target_url'] == 'http://m.fruitday.com/vip/center?') {
                        continue;
                    }
                    // 只有上海、北京支持 O2O
                    if ($banner['banner_target_type_id'] == 9 && !in_array($regionId, array(106092, 143949))) {
                        continue;
                    }
                }

                // topBanner_Big
                $check3 = false;
                if ($type == 'topBanner' && !$check3 && $banner['banner_size_id'] == 3) {
                    $type .= '_Big';
                    $check3 = true;
                }

                $this->ci->load->model('product_model');
                $this->ci->load->model('product_price_model');

                // 天天爆款，bannerTitle 为 商品名称
                $bannerTitle = '';
                if ($type == 'horizontalBanner_TypeOne') {
                    $product = $this->ci->product_model->selectProducts('product_name', array('id' => $banner['banner_target_id']));
                    $bannerTitle = $product ? $product[0]['product_name'] : '';
                }

                // 商品价格、是否有货
                $priceInfo = $stockInfo = array();
                $isSale = '';
                if ($banner['banner_target_price_id']) {
                    $priceInfo = $this->ci->product_price_model->dump(array(
                        'id' => $banner['banner_target_price_id']
                    ), 'price, old_price');
                    $region_to_warehouse = $this->ci->config->item('region_to_cang'); 
                    $ware_id = $region_to_warehouse[$params['region_id']];        
                    $stockInfo = $this->ci->product_model->getRedisProductStock($banner['banner_target_price_id'],$ware_id);
                    $isSale = ($stockInfo['use_store'] == 1 && $stockInfo['stock'] == 0) ? 0 : 1;
                }

                $content[] = array(
                    'photo' => PIC_URL . $banner['banner_file'],
                    'title' => $bannerTitle,
                    'banner_tag' => $banner['banner_tag'],
                    'type' => $banner['banner_target_type_id'],
                    'target_id' => $banner['banner_target_id'],
                    'original_price' => isset($priceInfo['old_price']) ? $priceInfo['old_price'] : 0,
                    'current_price' => isset($priceInfo['price']) ? $priceInfo['price'] : 0,
                    'product_store' => $isSale, // 0 没货、1 有货
                    'page_url' => $banner['banner_target_url'],
                    'store_id' => '', // toDo
                );
            }

            // 倒计时
            if ($group['end_time'] == '0000-00-00 00:00:00') {
                $groupEndTime = '';
            } elseif ($group['everyday_start_hour']) {
                $nowHour = date("H");
                if ($nowHour >= $group['everyday_start_hour']) {
                    $tempTime = date("Y-m-d {$group['everyday_start_hour']}:00:00", strtotime("+1 day"));
                } else {
                    $tempTime = date("Y-m-d {$group['everyday_start_hour']}:00:00");
                }
                $groupEndTime = $group['end_time'] >= $tempTime ? $tempTime : $group['end_time'];
            } else {
                $groupEndTime = $group['end_time'];
            }

            if ($group['banner_type_id'] == 8) {
                $data['floatBanner'] = $content[0];
            } else {
                $data['mainBanners'][] = array(
                    'group_type' => $type,
                    'group_page_url' => $group['group_url'],
                    'group_title' => $group['group_title'],
                    'group_end_time' => $groupEndTime,
                    'content' => $content,
                );
            }
        }

        // 尝试保存到缓存
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}

			$this->ci->memcached->set($memKey, $data, 60);
		}
        return array('code' => 200, 'msg' => 'succ', 'data' => $data);
    }

    /**
     * 获取地区标识
     */
    private function get_region_id($region_id = 106092)
    {
        $region_id = empty($region_id) ? 106092 : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if (isset($site_list[$region_id])) {
            $region_result = $site_list[$region_id];
        } else {
            $region_result = 106092;
        }
        return $region_result;
    }
}
