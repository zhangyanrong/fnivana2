<?php
namespace bll\wap;
include_once("wap.php");
/**
* 商品相关接口
*/
class Order extends wap{

	function __construct($params=array()){
		$this->ci = &get_instance();
	}

	function orderInit($params){
		$order_info = parent::call_bll($params);
        $cart_info = parent::$bll_obj->cart_info;
        if (!$order_info['order_id'] && isset($order_info['code'])) {
            return $order_info;
        }

		$order_id = $order_info['order_id'];
		/*时间选择列表start*/
        $this->ci->load->bll($params['source'].'/region');
        $obj = 'bll_' . $params['source'] . '_region';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;
        if(!empty($order_info['address_id']) && !empty($order_info['order_address']['area']['id'])){
            $send_time_params = array(
                'service'=>'region.getSendTime',
                'area_id'=>$order_info['order_address']['area']['id'],
                'region_id'=>$region_id,
                'connect_id'=>$params['connect_id'],
                'cart_info'=>$cart_info,
                'is_init'=>1,
            );
            $send_time_arr = $this->ci->$obj->getSendTime($send_time_params);
            if($send_time_arr['code'] && $send_time_arr['code'] == '300'){
                return $send_time_arr;
            }
            $order_info['send_times'] = $send_time_arr;
        }
        /*时间选择列表end*/

         /*默认地址start by lusc*/
        //$this->ci->load->model('user_model');
        //$address_id = $this->ci->user_model->get_user_default_address_by_pm($order_info['uid'], $order_info['address_id']);
        //$address_id = $this->ci->user_model->get_user_default_address($order_info['uid'], $order_info['address_id']);
        //$order_info['address_id'] = $address_id;
        //$data = array(
        //    'address_id' => $order_info['address_id'],
        //);
        //$where = array(
        //    'id' => $order_id,
        //);
        //$this->ci->order_model->update_order($data, $where);
        //if($order_info['address_id']){
        //    $order_info['order_address'] = $this->ci->order_model->get_order_address($order_info['address_id']);
        //}
        /*默认地址end*/

        /*配送时间重置start*/
        $init_sendtime = true;//by lusc
        if(isset($order_info['shtime']['after2to3days']) && !isset($order_info['send_times']['date_key']) && $order_info['send_times']['date_key']!='after2to3days'){
            $init_sendtime = true;
        }elseif( !isset($order_info['shtime']['after2to3days']) && isset($order_info['send_times']['date_key']) && $order_info['send_times']['date_key']=='after2to3days'){
            $init_sendtime = true;
        }
        if($init_sendtime){
        	$this->ci->order_model->sendtime_init($order_id);
        	$order_info['shtime'] = '';
        	$order_info['stime'] = '';
        }
        if(empty($order_info['shtime']))
        {   
            $defaultSendDate = $this->ci->bll_order->getDefaultSendTime($send_time_arr);
            $this->ci->order_model->sendtime_init($order_id,$defaultSendDate['shtime'],$defaultSendDate['stime']);
            if(!empty($defaultSendDate['shtime'])){
                $formateDate = $this->ci->order_model->format_send_date($defaultSendDate['shtime'],$defaultSendDate['stime']);
                $order_info['shtime'] = $formateDate['shtime'];
                $order_info['stime'] = $formateDate['stime'];
            }else{
                $order_info['shtime'] = '';
                $order_info['stime'] = '';
            }

        }
        /*配送时间重置end*/

        /*支付方式选择列表start*/
        if(!empty($order_info['address_id']) && !empty($order_info['order_address']['province']['id'])){
            $send_time_params = array(
                'service'=>'region.getPay',
                'province_id'=>$order_info['order_address']['province']['id'],
                'connect_id'=>$params['connect_id'],
                'source'=>$params['source'],
                'version'=>$params['version'],
            );
            $pay_arr = $this->ci->$obj->getPay($send_time_params);
            $order_info['payments'] = $pay_arr;
        }
        /*支付方式选择列表end*/

        /*购物车start*/
        $order_info['cart_info'] = $cart_info;//$this->ci->bll_cart->get_cart_info();

        if (!$order_info['cart_info']['items']) {
            $error = $this->ci->bll_cart->get_error();
            $msg = $error ? implode('、',$error) : '购物车为空，请先添加商品';
            return array( "code"=>"300","msg"=>$msg );
        }
        /*购物车end*/

        /*用户积分start*/
        $this->ci->load->bll('user');
        $user_info = $this->ci->bll_user->userInfo(array('connect_id'=>$params['connect_id']));
        // $order_info['user_jf_money'] = number_format(floor($user_info['jf']/100),0,'','');
        $order_info['user_money'] = number_format($user_info['money'],2,'.','');
        $order_info['user_coupon_num'] = $user_info['coupon_num'];
        $order_info['user_mobile'] = $user_info['mobile'];
        /*用户积分end*/

        /*运费start*/
        $order_detail = $this->ci->bll_order->orderDetails($order_info['uid'],0,$params['source'],$params['device_id']);
        /*运费end*/

//        //默认使用积分
//        $jf = intval($user_info['jf']/100);
//        $can_jf = intval($order_detail['order_jf_limit']);
//        $is_can = $jf - $can_jf;
//
//        if($user_info['jf'] >= 100 && $can_jf > 0 && $is_can >= 0)
//        {
//            $jf_params = array(
//                'jf'=>$can_jf,
//                'connect_id'=>$params['connect_id'],
//                'region_id'=>$params['region_id'],
//                'source'=>$params['source'],
//            );
//            $rs = $this->ci->bll_order->usejf($jf_params);
//            if($rs['code'] == 200)
//            {
//                $order_info['is_use_jf'] = 1;
//            }
//            else
//            {
//                $order_info['is_use_jf'] = 0;
//            }
//            $order_detail = $this->ci->bll_order->orderDetails($order_info['uid'],0,$params['source'],$params['device_id']);
//        }
//        else
//        {
//            $order_info['is_use_jf'] = 0;
//        }

        $order_info['order_money'] = number_format($order_detail['money'],2,'.','');
        $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
        $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
        $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
        $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
        $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
        $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');

        /*订单数据校验start*/
        if (!(strcmp($params['version'], '3.2.0') > 0)) {
            $goods_money = $cart_info['total_amount'];
        }else{
            $goods_money = $cart_info['goods_amount'];
        }
        //$goods_money = $cart_info['goods_amount'];
        $check_result = $this->ci->order_model->check_cart_pro_status($cart_info);//重新组织商品属性判断
        $check_order_result = $this->ci->bll_order->check_order_data($order_info['uid'],$cart_info,$params,$goods_money,$check_result,$user_info,$order_detail,'init');
        if($check_order_result){
            return array("code"=>$check_order_result['code'],"msg"=>$check_order_result['msg']);
        }
        /*订单数据校验end*/
        
		return $order_info;
	}
}