<?php
namespace bll;
class Region{
    
    private $terminal_arr = array('pc'=>1,'app'=>2,'wap'=>3);

	public function __construct($params = array()){
		$this->ci = &get_instance();
        $session_id = isset($params['connect_id'])?$params['connect_id']:'';
        if($session_id){
		  $this->ci->load->library('session',array('session_id'=>$session_id));
        }
		$this->ci->load->model("region_model");
        $this->ci->load->model("pay_discount_model");
        $this->ci->load->model("warehouse_model");
        $this->ci->load->model("product_model");
        $this->ci->load->helper('public');
        $cart_bll_params['session_id'] = $params['connect_id'];
        $cart_bll_params['terminal'] = $this->terminal_arr[$params['source']];
        $cart_bll_params['version'] = $params['version'];
        $this->ci->load->bll('cart',$cart_bll_params);
	}

	/*
	*获取地区分站列表
	*/
	function regionSiteList($params){

        // 加载config/region.php配置文件 @蔡昀辰
        $this->ci->config->load("region");

        if(strcmp($params['version'],'2.2.0')>=0){
            $site_region['site_list'] = $this->ci->config->item('site_region');
            $site_region['hot_list'] = $this->ci->config->item('hot_site_region');
        }else{
            $site_region = $this->ci->config->item('site_region');
        }

		return $site_region;
	}

	/*
	*获取地区信息
	*/
	function getRegion($params){
        //必要参数验证start
        $required_fields = array(
            'area_pid'=>array('required'=>array('code'=>'500','msg'=>'area pid can not be null')),
        );
        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }
        //必要参数验证end
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['area_pid'];
            if(!empty($params['product_id']))
            {
                $mem_key .= "_".$params['product_id'];
            }
            $region_arr = $this->ci->memcached->get($mem_key);
            if($region_arr){
                return $region_arr;
            }
        }

        $region_arr = $this->ci->region_model->get_child_region($params['area_pid'],$params['source']);

        if(!empty($params['product_id']))
        {
            $this->ci->load->model('area_model');
            foreach($region_arr as $key=>$rows)
            {
                $province = $this->ci->area_model->getProvinceByArea($rows['id']);
                $product_result = $this->ci->product_model->selectProducts('send_region,delivery_limit',array('id'=>$params['product_id']));
                if(!strpos($product_result[0]['send_region'],'"'.$province['id'].'"')){
                    unset($region_arr[$key]);
                }
            }
        }
        $arr = array();
        $region_arr = array_merge($arr,$region_arr);


        if(empty($region_arr)){
            return array('code'=>'300','msg'=>'您选择的区域暂时不支持配送，当前可配送区域请查看配送说明');
        }else{
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                if(!$this->ci->memcached){
                    $this->ci->load->library('memcached');
                }
                $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['area_pid'];
                if(!empty($params['product_id']))
                {
                    $mem_key .= "_".$params['product_id'];
                }
                $this->ci->memcached->set($mem_key,$region_arr,600);
            }
            return $region_arr;
        }
    }


    /*
	*获取地区信息
	*/
    function getRegionList($params){
        //必要参数验证start
        $required_fields = array(
            'province_id'=>array('required'=>array('code'=>'500','msg'=>'province_id can not be null')),
            'city_id'=>array('required'=>array('code'=>'500','msg'=>'city_id can not be null')),
            'area_id'=>array('required'=>array('code'=>'500','msg'=>'area_id can not be null')),
        );

        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }
        //必要参数验证end
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['area_id'];
            if(!empty($params['product_id']))
            {
                $mem_key .= "_".$params['product_id'];
            }
            $region_arr = $this->ci->memcached->get($mem_key);
            if($region_arr){
                return $region_arr;
            }
        }

        $region_arr = array();
        $region_arr['province'] = $this->ci->region_model->get_child_region($params['province_id'],$params['source']);
        $region_arr['city'] = $this->ci->region_model->get_child_region($params['city_id'],$params['source']);
        $region_arr['area'] = $this->ci->region_model->get_child_region($params['area_id'],$params['source']);


        if(!empty($params['product_id']))
        {
            $this->ci->load->model('area_model');
            foreach($region_arr['province'] as $key=>$rows)
            {
                $province = $this->ci->area_model->getProvinceByArea($rows['id']);
                $product_result = $this->ci->product_model->selectProducts('send_region,delivery_limit',array('id'=>$params['product_id']));
                if(!strpos($product_result[0]['send_region'],'"'.$province['id'].'"')){
                    unset($region_arr['province'][$key]);
                }
            }
        }
        sort($region_arr['province'] );
        $arr = array();
        $region_arr = array_merge($arr,$region_arr);


        if(empty($region_arr)){
            return array('code'=>'300','msg'=>'您选择的区域暂时不支持配送，当前可配送区域请查看配送说明');
        }else{
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                if(!$this->ci->memcached){
                    $this->ci->load->library('memcached');
                }
                $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['area_id'];
                if(!empty($params['product_id']))
                {
                    $mem_key .= "_".$params['product_id'];
                }
                $this->ci->memcached->set($mem_key,$region_arr,600);
            }
            return $region_arr;
        }
    }

    function getSendTime($params){
    	//必要参数验证start
        $required_fields = array(
            'area_id'=>array('required'=>array('code'=>'500','msg'=>'area id can not be null')),
            'region_id'=>array('required'=>array('code'=>'500','msg'=>'region id can not be null')),
        );
        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }
        //必要参数验证end
        $area_id = $params['area_id'];//三级地区id
  
        if(!empty($params['connect_id'])){
            if(empty($params['cart_info'])){

                $this->ci->load->model('area_model');
                $region_arr = $this->ci->area_model->getProvinceByArea($area_id);
                $region_id = $region_arr['id'];
                
                if($region_id) $this->ci->bll_cart->set_province($region_id);
                $cart_info = $this->ci->bll_cart->get_cart_info();
                // 移除未勾选和失效商品
                foreach($cart_info['items'] as $key=>$item) {
                    if(!$item['selected'] || !$item['valid']) {
                        unset($cart_info['items'][$key]);
                    }
                }
            }else{
                $cart_info = $params['cart_info'];
            }
            //获取session信息start
            $uid_result = $this->get_session_by_id($params['connect_id']);
            if($uid_result['code']!='200'){
                return $uid_result;
            }else{
                $uid = $uid_result['msg'];    
            }
            //获取session信息end
        }
        $is_init = $params['is_init']?$params['is_init']:0;
        $price_id = $params['price_id']?$params['price_id']:0;

        $return_result = $this->send_time_process($area_id,$uid,$cart_info,$is_init,0,$price_id);
        
        return $return_result;
    }

    function get_enterprise_sendtime($area_id,$uid,$cart_info){
        $this->ci->load->model("order_model");
        if($send_arr = $this->ci->order_model->check_enterprise($uid,$cart_info)){
            $enterprise_send_time = $this->enterpriseDeliTime($area_id,$send_arr['send_day'],$send_arr['tag']);
            $send_time_tmp = array();
            $now_h = date("H");
            foreach ($enterprise_send_time['date'] as $key => $value) {
                foreach ($enterprise_send_time['time'] as $k => $v) {
                    $send_time_tmp[$key]['date_key'] = $key;
                    $send_time_tmp[$key]['date_value'] = $value;
                    $send_time_tmp[$key]['time'][$k]['time_key'] = $k;
                    $send_time_tmp[$key]['time'][$k]['time_value'] = $v;
                    if($now_h>=$this->cut_off_time){
                        if($key==date("Ymd",strtotime("+1 day")) && $k=='0918'){
                            //$send_time_tmp[$key]['time'][$k]['time_key'] = '1822';
                            //$send_time_tmp[$key]['time'][$k]['time_value'] = '18:00-22:00';
                        }
                    }else{
                        if($key==date("Ymd") && $k=='0918'){
                            //$send_time_tmp[$key]['time'][$k]['time_key'] = '1822';
                            //$send_time_tmp[$key]['time'][$k]['time_value'] = '18:00-22:00';
                        }
                    }
                    $send_time_tmp[$key]['time'][$k]['disable'] = 'true';
                }
            }
            $return_result = array();
            foreach ($send_time_tmp as $value) {
                $time_result = array();
                foreach ($value['time'] as $k => $v) {
                    $time_result[] = $v;
                    unset($value['time'][$k]);
                }
                $value['time'] = $time_result;
                $return_result[] = $value;
            }
            return $return_result;
        }
    }

    function getSendLimit($warehouse_info = array()){
        $limit_arr = array();
        /*每天发货数量限制start*/
        /*获取订单数量start*/
        $order_time_limit_result = array();
        if($warehouse_info){
            $order_time_limit_result = $this->ci->warehouse_model->getWarehouseSendCount($warehouse_info['id']);
            if($order_time_limit_result){
                foreach ($order_time_limit_result as $sh_stime => $nums) {
                    $time_key = explode('_', $sh_stime);
                    $sh_time = $time_key[0];
                    $s_time = $time_key[1];
                    switch ($s_time) {                                      //普通设置
                        case '0918':
                            if($nums >= $warehouse_info['day_limit']){
                                $limit_arr[$sh_time]['0918'] = 'limit';
                            }
                            break;
                        case '0914':
                            if($nums >= $warehouse_info['am_limit']){
                                $limit_arr[$sh_time]['0914'] = 'limit';
                                //$limit_arr[$sh_time]['0918'] = 'limit';
                            }
                            break;
                        case '1418':
                            if($nums >= $warehouse_info['pm_limit']){
                                $limit_arr[$sh_time]['1418'] = 'limit';
                            }
                            break;
                        case '1822':
                            if($nums >= $warehouse_info['night_limit']){
                                $limit_arr[$sh_time]['1822'] = 'limit';
                            }
                            break;
                        default:
                            if($nums >= $warehouse_info['day_limit']){
                                $limit_arr[$sh_time]['0918'] = 'limit';
                            }
                            break;
                    }

                    //有特殊设置
                    if($warehouse_info['special'] && is_array($warehouse_info['special']) && $warehouse_info['special'][0]){
                        foreach ($warehouse_info['special'] as $key => $value) {
                            $s_start_time = date('Ymd',strtotime($value['s_start_time']));
                            $s_end_time = date('Ymd',strtotime($value['s_end_time']));
                            if($sh_time>=$s_start_time && $sh_time<=$s_end_time){
                                switch ($s_time) {
                                    case '0918':
                                        $check_nums = $value['s_day_limit'];
                                        break;
                                    case '0914':
                                        $check_nums = $value['s_am_limit'];
                                        break;
                                    case '1418':
                                        $check_nums = $value['s_pm_limit'];
                                        break;
                                    case '1822':
                                        $check_nums = $value['s_night_limit'];
                                        break;
                                    default:
                                        $check_nums = $value['s_day_limit'];
                                        break;
                                }
                                if($nums >= $check_nums){
                                    $limit_arr[$sh_time][$s_time] = 'limit';
                                }else{
                                    $limit_arr[$sh_time][$s_time] = 'unlimit';
                                }
                                // if($warehouse_info['limit_type'] == 1){
                                //     $limit_arr[$sh_time]['0918'] = $limit_arr[$sh_time]['0914'];
                                // }
                            }
                        }
                    }
                }

            }
            if($warehouse_info['day_limit'] == 0){
                $limit_arr['all']['0918'] = 'limit';
            }
            if($warehouse_info['am_limit'] == 0){
                $limit_arr['all']['0914'] = 'limit';
            }
            if($warehouse_info['pm_limit'] == 0){
                $limit_arr['all']['1418'] = 'limit';
            }
            if($warehouse_info['night_limit'] == 0){
                $limit_arr['all']['1822'] = 'limit';
            }
            // if($warehouse_info['am_limit'] == 0 && $warehouse_info['pm_limit'] == 0 && $warehouse_info['limit_type'] ==1){
            //     $limit_arr['all']['0918'] = 'limit';
            // }
            if($warehouse_info['special'] && is_array($warehouse_info['special']) && $warehouse_info['special'][0]){
                foreach ($warehouse_info['special'] as $key => $value) {
                    $s_start_time = date('Ymd',strtotime($value['s_start_time']));
                    $s_end_time = date('Ymd',strtotime($value['s_end_time']));
                    $sh_time = date('Ymd',strtotime($s_start_time));
                    while ($sh_time <= $s_end_time) {
                        if($value['s_day_limit'] == 0){
                            $limit_arr[$sh_time]['0918'] = 'limit';
                        }
                        if($value['s_am_limit'] == 0){
                            $limit_arr[$sh_time]['0914'] = 'limit';
                        }
                        if($value['s_pm_limit'] == 0){
                            $limit_arr[$sh_time]['1418'] = 'limit';
                        }
                        if($value['s_night_limit'] == 0){
                            $limit_arr[$sh_time]['1822'] = 'limit';
                        }
                        // if($value['s_am_limit'] == 0 && $value['s_pm_limit'] == 0 && $warehouse_info['limit_type'] ==1){
                        //     $limit_arr[$sh_time]['0918'] = 'limit';
                        // }
                        $sh_time = date('Ymd',strtotime($sh_time)+86400);
                    }
                }
            }
        }
        return $limit_arr;
    }

    function send_time_process($area_id,$uid='',$cart_info=array(),$is_init=0,$address_id=0,$price_id=0){
        $diff_day = 0;
        $validity = 0;
        $this->ci->load->model("order_model");

                //组织选择项start
        $area_info = $this->ci->region_model->get_area_info($area_id);
        if(empty($area_info)){
            return array('code'=>'500','msg'=>'area is empty');
        }
        //获取配送限制

        //TMS
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        $ware_id ='';
        $orderHang = $this->ci->order_model->dump(array('uid'=>$uid,'order_status'=>0));
        $address_id or $address_id = $orderHang['address_id'];
        if($address_id)
        {
            $this->ci->load->model('user_address_model');
            $user_add = $this->ci->user_address_model->dump(array('id' => $address_id));
            if(!empty($user_add) && !empty($user_add['tmscode']))
            {
                $arr_tmsCode = explode('-',$user_add['tmscode']);
                $tmsCode =$arr_tmsCode[0];
                $ware = $this->ci->warehouse_model->dump(array('tmscode' => $tmsCode));
                if(!empty($ware))
                {
                    $ware_id = $ware['id'];
                }
            }
        }

        if(!empty($ware_id))
        {
            $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($ware_id);
        }

        if(empty($warehouse_info))
        {
            $warehouse_info = $this->ci->warehouse_model->get_warehouse_by_region($area_id);
        }
        if($orderHang['pay_parent_id'] && $orderHang['pay_parent_id']==4 && $orderHang['pay_id'] && in_array($orderHang['pay_id'], array(7,8,9))){                             //果实卡等支付方式 指定自建物流仓
            $warehouse_info = $this->ci->warehouse_model->getZijianWarehouse($area_id);
        }
        $warehouse_info['special'] = unserialize($warehouse_info['special']);
        $limit_arr = $this->getSendLimit($warehouse_info);

        if($price_id >0)
        {
            $this->ci->load->model('product_price_model');
            $priceInfo = $this->ci->product_price_model->dump(array('id' => $price_id));
            if(!empty($priceInfo))
            {
                $items['items'] = array(
                    0=>array(
                        'product_id'=>$priceInfo['product_id'],
                        'sku_id'=>$priceInfo['id'],
                        'product_no'=>$priceInfo['product_no'],
                        'item_type'=>'normal',
                        'qty'=>1
                    )
                );
                $cart_info = $items;
            }
        }

        if(!empty($cart_info)){
            //企业用户配送时间start
            // if($params['source']=='app'){
            if(!empty($uid)){
                $return_result = $this->get_enterprise_sendtime($area_id,$uid,$cart_info);
                if($return_result) return $return_result;
            }
            // }
            //企业用户配送时间end
 

            //预售商品配送时间start
            if($adv_send_date = $this->ci->order_model->check_advsale_sendtime($uid,$cart_info)){

                $sendDate = date('Ymd',strtotime($adv_send_date));
                $per_sell_diff_day = round((strtotime($check_result)-strtotime(date('Ymd')))/3600/24);
                $is_init = 1;
                // $sendDate = $this->add_after2to3days($sendDate,$limit_arr,strtotime($adv_send_date));
                // $adv_send_date = date('Y-m-d',strtotime($sendDate));
                // $return_result = $this->get2to3days($adv_send_date.'日起发货',date("Ymd",strtotime($adv_send_date)));
                // $return_result = array($return_result);
                // return $return_result;
            }
            //预售商品配送时间end

            
            //配送限制商品start
            $is_after2to3days = false;
            $pro_send = $this->check_cart_pro_send_time($cart_info);
            $check_result = $pro_send['last_date'];
            $validity = $pro_send['validity'];
            if($check_result == 'after2to3days'){    //2到3天
                $is_after2to3days = true;
            }else{                                  //最早送货时间
                $diff_day = round((strtotime($check_result)-strtotime(date('Ymd')))/3600/24);
                if($diff_day<0)
                    $diff_day = 0;
            }
            if($pro_send['default_day'] == 1)
            {
                $is_init = 1;
            }
            if($per_sell_diff_day>$diff_day){
                $diff_day = $per_sell_diff_day;
            }
            //配送限制商品end
        }

        /*获取订单数量end*/
        $send_time = $this->deliTimeCopy($area_info,$is_after2to3days,$diff_day,$warehouse_info,$validity,$is_init);//获取默认配送选择项
        if(isset($send_time['after2to3days']) || isset($send_time['after1to2days'])){   //1到2天，2到3天发货
            $return_result = $send_time;
            /*after2to3days结构修改start*/
            if(isset($return_result['after2to3days'])){
                $return_result = $this->get2to3days('下单后的2-3天发货');
            }
            if(isset($return_result['after1to2days'])){
                $return_result = $this->get2to3days('下单后的1-2天发货','after1to2days');
            }
            /*after2to3days结构修改end*/
            $send_h = date('H');
            $now_time = time();
            $province = $this->ci->region_model->get_province($area_id);
            //$is_pay_wd = $this->is_pay_wd($province);
            if($diff_day){
                $sendtime = $now_time + $diff_day*86400;
            }elseif($area_info['send_time'] == 'chose_days'){
                $sendtime = $send_h >= $this->cut_off_time ? ($now_time+172800) : $now_time+86400;
            }else{
                $sendtime = $send_h >= $this->cut_off_time ? ($now_time+86400) : $now_time;
            }
            $sendDate = date('Ymd',$sendtime);
            $sendDate = $this->add_after2to3days($sendDate,$limit_arr,$sendtime);
            $return_result['date_key'] = date('Ymd',strtotime($sendDate));
            $t = date('m月d日',strtotime($sendDate));
            $t1 = date('m月d日',strtotime($sendDate)+86400);
            $return_result['date_value'] = $t.'、'.$t1.'发货';
            $return_result = array($return_result);
        }else{                                                                            //选择时间发货
            $send_time_tmp = array();
            $now_h = date("H");
            foreach ($send_time['date'] as $key => $value) {
                foreach ($send_time['time'] as $k => $v) {
                    $send_time_tmp[$key]['date_key'] = $key;
                    $send_time_tmp[$key]['date_value'] = $value;
                    $send_time_tmp[$key]['time'][$k]['time_key'] = $k;
                    $send_time_tmp[$key]['time'][$k]['time_value'] = $v;
                    if($now_h>=$this->cut_off_time){
                        if($key==date("Ymd",strtotime("+1 day")) && in_array($k, array('0918','0914'))){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }elseif($key == date("Ymd")){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }else{
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'true';
                        }
                        if($this->cut_off_time_n && $warehouse_info['is_next_day']){   //次日达
                            if($key==date("Ymd",strtotime("+1 day")) && in_array($k, array('0918','0914','1418'))){
                                $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                            }
                        }
                    }elseif($this->cut_off_time_n && $now_h>=$this->cut_off_time_n && $now_h<$this->cut_off_time){//下午截单到晚上截单之间
                        if($key==date("Ymd")){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }else{
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'true';
                        }
                    }elseif($this->cut_off_time_m && $this->cut_off_time_n && $now_h>=$this->cut_off_time_m && $now_h<$this->cut_off_time_n){  //上午截单时间到下午截单之间
                        if($key==date("Ymd") && in_array($k, array('0918','0914','1418'))){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }else{
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'true';
                        }
                        // if($warehouse_info['is_next_day']){  //次日达
                        //     if($key==date("Ymd")){
                        //         $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        //     }
                        // }
                    }elseif($this->cut_off_time_m && $now_h>=$this->cut_off_time_m){ //白晚单情况 白单截单时间到晚单截单时间
                        if($key==date("Ymd")){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }else{
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'true';
                        }
                    }elseif($this->cut_off_time_m && $now_h<$this->cut_off_time_m){  //当天白单截单前
                        if($key==date("Ymd") && in_array($k, array('0918','0914'))){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }else{
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'true';
                        }
                        if($this->cut_off_time_n && $warehouse_info['is_next_day']){   //次日达
                            if($key==date("Ymd") && in_array($k, array('0918','0914','1418'))){
                                $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                            }
                        }
                    }else{             //当天下单 明天送
                        if($key==date("Ymd")){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }else{
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'true';
                        }
                    }
                    if($key<date("Ymd")){
                        $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                    }
                    if($is_init == 1){ //初始化订单 默认无晚单
                        if($k == '1822'){
                            $send_time_tmp[$key]['time'][$k]['disable'] = 'false';
                        }
                    }
                }
            }
            
            $return_result = array();
            foreach ($send_time_tmp as $value) {
                $time_result = array();
                foreach ($value['time'] as $k => $v) {
                    $time_result[] = $v;
                    unset($value['time'][$k]);
                }
                $value['time'] = $time_result;
                $return_result[] = $value;
            }
            $return_tmp = array();
            foreach ($return_result as $key => $value) {
                $is_del = true;
                $can_am_pm = false;
                $can_am = $can_pm = $can_day = true;
                foreach ($value['time'] as $k => $v) {
                    if($limit_arr[$value['date_key']][$v['time_key']] == 'limit'){
                        $value['time'][$k]['disable'] = 'false';
                        $return_result[$key]['time'][$k]['disable'] = 'false';
                        if($v['time_key'] == '0918') $can_day = false;
                        if($v['time_key'] == '0914') $can_am = false;
                        if($v['time_key'] == '1418') $can_pm = false;
                    }elseif($limit_arr['all'][$v['time_key']] == 'limit'){
                        $value['time'][$k]['disable'] = 'false';
                        $return_result[$key]['time'][$k]['disable'] = 'false';
                        if($v['time_key'] == '0918') $can_day = false;
                        if($v['time_key'] == '0914') $can_am = false;
                        if($v['time_key'] == '1418') $can_pm = false;
                    }else{
                        if($value['time'][$k]['disable'] == 'true'){
                            $is_del = false;
                        }elseif($value['time'][$k]['disable'] == 'false'){
                            if($v['time_key'] == '0918') $can_day = false;
                            if($v['time_key'] == '0914') $can_am = false;
                            if($v['time_key'] == '1418') $can_pm = false;
                        }  
                    }
                    if($v['time_key'] == '0914' || $v['time_key'] == '1418') $can_am_pm = true;
                }
                if($can_am_pm === true){
                    foreach ($value['time'] as $k => $v) {
                        if($can_am === false && $can_pm === false && $can_day === true){
                            if(in_array($v['time_key'], array('0914','1418'))){
                                unset($value['time'][$k]);
                            }
                        }else{
                            if($v['time_key'] === '0918'){
                                unset($value['time'][$k]);
                            }
                        }
                    }
                }
                $value['time'] = array_values($value['time']);
                if($is_del) continue;      //全天都不可配送，跳过这天
                $return_tmp[] = $value;
            }
            $return_result = $return_tmp;
        }
        //组织选择项end
        $return_result = array_slice($return_result, 0 , 5 ,true);
        if(empty($return_result)){
            if($pro_send['validity_pro']){
                $product = $this->ci->product_model->dump(array('id'=>$pro_send['validity_pro']));
                return array("code"=>"300","msg"=>"您挑选的商品".$product['product_name']."由于运能原因暂时无法配送，请选择其他商品购买");
            }else{
                return array("code"=>"300","msg"=>"您挑选的商品由于运能原因暂时无法配送，请选择其他商品购买");
            }
        }
        return $return_result;
    }

    function add_after2to3days($sendDate,$limit_arr,$sendtime){
        if(isset($limit_arr[$sendDate]['0918']) && $limit_arr[$sendDate]['0918'] == 'limit'){
            $add_time = $sendtime+86400;
            $add_date = date("Ymd",$add_time);
            return $this->add_after2to3days($add_date,$limit_arr,$add_time);
        }else{
            return $sendDate;
        }
    }

    function check_cang_product($cart_info){
        $cang_product = $this->ci->config->item('cang_product');
        foreach ($cart_info['items'] as $key => $value) {
            if(isset($cang_product[$value['product_id']])){
                return $cang_product[$value['product_id']];
            }
        }
        return false;
    }

    private function get2to3days($sendTime,$sendKey='after2to3days'){
        $return_result = array();
        $return_result['date_key'] = $sendKey;
        $return_result['date_value'] = $sendTime;
        $return_result['time'][0]['time_key'] = 'weekday';
        $return_result['time'][0]['time_value'] = '仅在工作日配送';
        $return_result['time'][0]['disable'] = 'true';
        $return_result['time'][1]['time_key'] = 'weekend';
        $return_result['time'][1]['time_value'] = '仅在双休日、假日配送';
        $return_result['time'][1]['disable'] = 'true';
        $return_result['time'][2]['time_key'] = 'all';
        $return_result['time'][2]['time_value'] = '工作日、双休日与假日均可配送';
        $return_result['time'][2]['disable'] = 'true';
        return $return_result;
    }

    private function get_session_by_id($session_id){
        $result =   $this->ci->session->userdata;
        $userdata = unserialize($result['user_data']);
        if( !isset($userdata['id']) || $userdata['id'] == "" ){
            return array('code'=>'400','msg'=>'not this user,may be wrong connect id');   
        }
        return array('code'=>'200','msg'=>$userdata['id']);
    }

    private function enterpriseDeliTime($area_id,$send_day,$tag){
        $area_info = $this->ci->region_model->get_area_info($area_id);
        if(empty($area_info)){
        return array('code'=>'500','msg'=>'area is empty');
        }
        $h=date("H");
        $cut_off = $area_info['cut_off_time'];
        $m_cut_off = $area_info['cut_off_time_m'];
        $send_day_arr = explode(',', $send_day);
        foreach ($send_day_arr as $value) {
            $i = $this->get_date_by_week($value,$m_cut_off,$tag);
            if($h>=$cut_off && $i==1){
                $i += 7;
            }
            $arr[$this->nowDate($i)] = $this->formateDateCopy($this->nowDate($i)).'|'.$this->week($i);
            $arr_2[] = $this->nowDate($i);
        }
        // if($h<$m_cut_off && $tag=='cpGby'){
        //     $arr_1 = array(
        //         '1822' => '18:00-22:00',
        //     );
        // }else{
        //     $arr_1 = array(
        //         '0918' => '09:00-18:00',
        //     );
        // }
        $arr_1 = array(
                '0918' => '09:00-18:00',
        );
        ksort($arr);
        $return_date = array('date'=>$arr,'time'=>$arr_1,'first_date'=>$arr_2[0],'first_time'=>'0918');
        return $return_date; 
    }

    private function get_date_by_week($weekday,$m_cut_off,$tag){
        //今天星期几
        $d=date("w");
        $h=date("H");
        if($h<$m_cut_off && $tag=='cpGby'){
            if($weekday<$d){
                $add_day = 7-$d+$weekday;
            }else{
                $add_day = $weekday-$d;
            }
        }else{
            if($weekday<=$d){
                $add_day = 7-$d+$weekday;
            }else{
                $add_day = $weekday-$d;
            }
        }
        return $add_day;
    }

    function getArea($identify_code){
        $m="";
        preg_match("/\d+/",$identify_code,$m);
        return $m;
    }

    function nowDate($n){
        if($n!=0){
            /*20150401不发货*/
            $date=date("Ymd",strtotime("+".$n." day"));
            // if($date=='20150401'){
            //   $n++;
            //   $date = date("Ymd",strtotime("+".$n." day"));
            // }
            return $date;
        }else{
            /*20150401不发货*/
            $date=date("Ymd");
            // if($date=='20150401'){
            //   $date = date("Ymd",strtotime("+1 day"));
            // }
            return $date;
        }
    }

    function formateDateCopy($date){
        $str = "";
        $year = substr($date,0,4);
        $dat  = substr($date,4,2);
        $day  = substr($date,6,2);
        return $dat."-".$day;
    }

    function week($w,$c=''){
        if($c==""){
            $d=date("w")+$w;
            if($d>7){
                $d=$d%7;
            }
        }else{
            $d=$c;
        }
        if($d==0){
            return "周日";
        }else if($d==1){
            return "周一";
        }else if($d==2){
            return "周二";
        }else if($d==3){
            return "周三";
        }else if($d==4){
            return "周四";
        }else if($d==5){
            return "周五";
        }else if($d==6){
            return "周六";
        }else if($d==7){
            return "周日";
        }
        
    }

    function offlineDeliTime(){
        $i = 0;    
        $arr[$this->nowDate($i)] = $this->formateDateCopy($this->nowDate($i)).'|'.$this->week($i);
        $arr_2[] = $this->nowDate($i);
        $arr_1 = array(
            '0918' => '09:00-18:00',
        );
        $return_date = array('date'=>$arr,'time'=>$arr_1,'first_date'=>$arr_2[0],'first_time'=>'0918');
        return $return_date; 
    }

    function deliTimeCopy($send_rule_info,$is_after2to3days=false,$diff_day=0,$warehouse_info=array(),$validity=0,$is_init=0){
        $days = 20;//可配送时间段
        $h=date("H");
        // $a=$this->getArea($area_info['identify_code']);
        // $a=$a[0];
        $cut_off = $send_rule_info['cut_off_time'];    
        $this->cut_off_time = $send_rule_info['cut_off_time'];
        $cut_off_m = $send_rule_info['cut_off_time_m'];
        $this->cut_off_time_m = $send_rule_info['cut_off_time_m'];
        $cut_off_n = $send_rule_info['cut_off_time_n'];
        $this->cut_off_time_n = $send_rule_info['cut_off_time_n'];

        if($send_rule_info['send_time']=='after1to2days'){
          $return_date = array('after1to2days'=>'下单后的1-2天送达');
        }elseif($send_rule_info['send_time']=='after2to3days'){
          $return_date = array('after2to3days'=>'下单后的2-3天送达');
        }elseif($is_after2to3days == true){
            $return_date = array('after2to3days'=>'下单后的2-3天送达');
        }elseif($send_rule_info['send_time']=='chose_days'){
            for($i=$diff_day;$i<=$days;$i++) {
                if($validity && $i>($validity-1)){
                    break;
                }
                $arr[$this->nowDate($i)] = $this->formateDateCopy($this->nowDate($i)).'|'.$this->week($i);
                $arr_2[] = $this->nowDate($i);
            }
            $arr_1 = array('0918'=>'09:00-18:00');    //默认白单
            if($is_init == 0)
            {
                if($send_rule_info['can_ampm_send'] == 1 && $warehouse_info['limit_type'] == 1){
                    //unset($arr_1['0918']);
                    // $arr_1 = array(
                    //     '0914' => '09:00-14:00',
                    //     '1418' => '14:00-18:00',
                    // );
                    $arr_1['0914'] = '09:00-14:00';
                    $arr_1['1418'] = '14:00-18:00';
                    //if($is_init) $arr_1['0918'] = '09:00-18:00';
                }
                if($send_rule_info['can_night_send']==1){
                    $arr_1['1822'] = '18:00-22:00';
                }
            }
            $f_days = 0;
            if($send_rule_info['can_ampm_send']==1 && $warehouse_info['limit_type'] == 1){   //上下午单
                if($h>=0 && $h<$cut_off_m){
                    $first_time = '1418';
                }elseif($h>=$cut_off_m && $h<$cut_off_n){
                    if($send_rule_info['can_night_send']==1){
                        $first_time = '1822';
                        $f_days = 0;
                    }else{
                        $first_time = '1418';
                        $f_days = 1;
                    }
                }elseif($h>=$cut_off_n && $h<$cut_off){
                    $first_time = '0914';
                    $f_days = 1;
                }elseif($h>=$cut_off){
                    $first_time = '1418';
                    $f_days = 1;
                }
            }elseif($send_rule_info['can_night_send']==1){   //晚单
                if($h>=0 && $h<$cut_off_m){
                    $first_time = '1822';
                    $f_days = 0;
                }elseif($h>=$cut_off_m && $h<$cut_off){
                    $first_time = '0918';
                    $f_days = 1;
                }elseif($h>=$cut_off){
                    $first_time = '1822';
                    $f_days = 1;
                }
            }else{                                           //只支持白单
                $first_time = '0918';
                $f_days = 1;
                if($h >= $cut_off){
                    $f_days = 2;
                }
            }
            if($diff_day>=$f_days) $f_days = 0;
            $limit = $days-$f_days>0?$days-$f_days:0;
            $arr = array_slice($arr,$f_days,$limit,true);
            $return_date = array('date'=>$arr,'time'=>$arr_1,'first_date'=>$arr_2[$f_days],'first_time'=>$first_time);
        }
        return $return_date; 
    }

    function getPay($params){
        //必要参数验证start
        $required_fields = array(
            'connect_id'=>array('required'=>array('code'=>'500','msg'=>'connect id can not be null')),
            'province_id'=>array('required'=>array('code'=>'500','msg'=>'province id can not be null')),
        );
        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }
        
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_session_by_id($params['connect_id']);
        if($uid_result['code']!='200'){
            return $uid_result;
        }else{
            $uid = $uid_result['msg'];    
        }
        //获取session信息end

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE!=$params['service']) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['province_id'];
            $pay_result = $this->ci->memcached->get($mem_key);
        }

        if(!$pay_result){
            $province = $params['province_id'];
            $pay_array  =  $this->ci->config->item("pay_array");

            //非自建物流不能线下支付start
            $is_pay_wd = $this->is_pay_wd_2($province);
            if(($is_pay_wd === true) && $pay_array['4']['name']=="线下支付"){
                unset($pay_array['4']);
            }
            //非自建物流不能线下支付end
            
             if(strcmp($params['version'],'3.2.0')<0){
                 unset($pay_array['8']);
             }

            $pay_result = array();
            $i = 0;
            foreach ($pay_array as $key => $value) {
                $pay_result[$i]['pay_parent_id'] = $key;
                $pay_result[$i]['pay_parent_name'] = $value['name'];
                if(!empty($value['son'])){
                    $j = 0;
                    foreach ($value['son'] as $k => $v) {
                        $pay_result[$i]['son'][$j]['pay_id'] = $k;
                        
                        
                        $pay_result[$i]['son'][$j]['pay_name'] = $v;
                        $has_invoice = $this->has_invoice($key,$k);
                        $pay_result[$i]['son'][$j]['has_invoice'] = $has_invoice;
                        $no_invoice_message = '';
                        if(!$has_invoice){
                            $no_invoice_message = '您选择的支付方式不支持开发票';
                        }
                        $pay_result[$i]['son'][$j]['no_invoice_message'] = $no_invoice_message;
                        $pay_result[$i]['son'][$j]['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$key.'_'.$k.'.png';
                        $discount_rule = $this->ci->pay_discount_model->getPayDiscountView($key,$k,$params['source']);
                        $discount_rule_tmp = '';
                        if(!empty($discount_rule) && is_array($discount_rule)){
                            foreach ($discount_rule as $dr_k => $dr_v) {
                                $discount_rule_tmp .= $dr_v.'；';
                            }
                        }
                        $discount_rule and $pay_result[$i]['son'][$j]['discount_rule'] = trim($discount_rule_tmp,'；');
                        $j++;
                    }
                }else{
                    $pay_result[$i]['son'][0]['pay_id'] = 0;
                    
                    $pay_result[$i]['son'][0]['pay_name'] = $value['name'];
                    $has_invoice = $this->has_invoice($key,0);
                    $pay_result[$i]['son'][0]['has_invoice'] = $has_invoice;
                    $no_invoice_message = '';
                    if(!$has_invoice){
                        $no_invoice_message = '您选择的支付方式不支持开发票';
                    }
                    $pay_result[$i]['son'][0]['no_invoice_message'] = $no_invoice_message;
                    $pay_result[$i]['son'][0]['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$key.'_0.png';
                    $discount_rule = $this->ci->pay_discount_model->getPayDiscountView($key,0,$params['source']);
                    $discount_rule_tmp = '';
                    if(!empty($discount_rule) && is_array($discount_rule)){
                        foreach ($discount_rule as $dr_k => $dr_v) {
                            $discount_rule_tmp .= $dr_v.'；';
                        }
                    }
                    $discount_rule and $pay_result[$i]['son'][0]['discount_rule'] = trim($discount_rule_tmp,'；');
                }
                $i++;
            }

            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                    if(!$this->ci->memcached){
                        $this->ci->load->library('memcached');
                    }
                    $mem_key = $params['service']."_".$params['source']."_".$params['version']."_".$params['province_id'];
                    $this->ci->memcached->set($mem_key,$pay_result,60);
            }
        }
        

        if(strcmp($params['version'],'3.1.0')>=0){
            $this->ci->load->model('user_model');
            $user = $this->ci->user_model->selectUser("money", array('id'=>$uid));
            $pay_result['user_money'] = $user['money'];
        }
        return $pay_result;
    }

    function is_pay_wd($province){
        $area_refelect = $this->ci->config->item("area_refelect");
        if(in_array($province, $area_refelect['1']) || in_array($province, $area_refelect['5'])){
            return false;
        }else{
            return true;
        }
    }

    function is_pay_wd_2($province){
        $area_refelect = $this->ci->config->item("area_refelect");
        if(in_array($province, $area_refelect['1'])){
            return false;
        }else{
            return true;
        }
    }

    private function has_invoice($pay_parent=0, $pay_son=0) {
        $current_payment = $pay_parent;
        if ($pay_son) {
            $current_payment = $current_payment . '-' . $pay_son;
        }
        $payments = $this->ci->config->item('no_invoice');
        return array_key_exists($current_payment, $payments) ? 0 : 1;
    }

    function getChargePay($params){
        $charge_pay_array  =  $this->ci->config->item("charge_pay_array");
        //非自建物流不能线下支付start
        

        $pay_result = array();
        $i = 0;
        foreach ($charge_pay_array as $key => $value) {
            $pay_result[$i]['pay_parent_id'] = $key;
            $pay_result[$i]['pay_parent_name'] = $value['name'];
            if(!empty($value['son'])){
                $j = 0;
                foreach ($value['son'] as $k => $v) {
                    $pay_result[$i]['son'][$j]['pay_id'] = $k;
                    $pay_result[$i]['son'][$j]['pay_name'] = $v;
                    $pay_result[$i]['son'][$j]['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$key.'_'.$k.'.png';
            
                    $j++;
                }
            }else{
                $pay_result[$i]['son'][0]['pay_id'] = 0;
                
                $pay_result[$i]['son'][0]['pay_name'] = $value['name'];
                $pay_result[$i]['son'][0]['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$key.'_0.png';
            }
            $i++;
        }
        
        return $pay_result;
    }

    function check_cart_pro_send_time($cart_info){
        $pro_send = array();
        $pro_ids = array();
        foreach ($cart_info['items'] as $key => $value) {
            $pro_ids[] = $value['product_id'];
        }
        if($pro_ids){
            $send_detail = $this->ci->product_model->get_pro_send_time($pro_ids);
        }
        $last_date = date('Ymd');
        $validity = 0;
        $after2to3days = false;
        $default_day = 0;
        foreach ($send_detail as $key => $value) {
            if($value['delivery_date'] == 'after2to3days'){
                $after2to3days = true;
            }elseif($value['delivery_date'] != '-'){
                if($last_date < $value['delivery_date'])
                    $last_date = $value['delivery_date'];
            }
            if($value['validity'] && $value['validity']>0){
                if($validity == 0 || $validity>$value['validity'])
                    $validity = $value['validity'];
                    $pro_send['validity_pro'] = $value['product_id'];
            }
            if($value['delay'] && $value['delay']>0)
            {
                $now_day = date('Ymd');
                if($value['delivery_date'] != '-' && $value['delivery_date'] > $now_day)
                {
                    $last_date = $value['delivery_date'];
                }
                else
                {
                    $last_date = date('Ymd',strtotime('+ '.$value['delay'].' day'));
                }
            }
            if($value['default_day'] == 1)
            {
                $default_day = 1;
            }
        }
        if($after2to3days){
            $to3daysdate = date('Ymd',strtotime('+ 3 day'));
            if($to3daysdate > $last_date)
                $last_date = 'after2to3days';
        }
        $pro_send['last_date'] = $last_date;
        $pro_send['validity'] = $validity;
        $pro_send['default_day'] = $default_day;
        return $pro_send;
    }

    /**
     * 联合登陆获取地址ID
     */
    public function getIdByName($params)
    {
        // 检查参数
        $required = array(
            'province' => array('required' => array('code' => '500', 'msg' => 'province name can not be null')),
        );
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        $result['province'] = $this->ci->region_model->getIdByName($params['province']);
        if ($result['province']) {
            $result['city'] = $this->ci->region_model->getIdByName($params['city'], $result['province']);
            $result['area'] = $this->ci->region_model->getIdByName($params['area'], $result['city']);
            $result['region_id'] = $result['province'];
        }

        return $result;
    }
}
