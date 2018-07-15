<?php
namespace bll\pc;
include_once("pc.php");
/**
* 商品相关接口
*/
class Order extends pc{

	function __construct($params=array()){
		$this->ci = &get_instance();
        $this->ci->load->helper('public');  //验证
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
if($order_info['is_enterprise']!='1'){
         /*默认地址start by lusc*/
        $this->ci->load->model('user_model');
        $address_id = $this->ci->user_model->get_user_default_address_by_pm($order_info['uid'], $order_info['address_id']);
        $order_info['address_id'] = $address_id;
        $data = array(
            'address_id' => $order_info['address_id'],
        );
        $where = array(
            'id' => $order_id,
        );
        $this->ci->order_model->update_order($data, $where);
        if($order_info['address_id']){
            $order_info['order_address'] = $this->ci->order_model->get_order_address($order_info['address_id']);    
        }
        /*默认地址end*/
        /*配送时间重置start*/
        $init_sendtime = true;//by lusc
}else{
        $init_sendtime = false;
}
        
        
        if((isset($order_info['shtime']['after2to3days']) || isset($order_info['shtime']['after1to2days']) ) && !isset($order_info['send_times']['date_key']) && ($order_info['send_times']['date_key']!='after2to3days' && $order_info['send_times']['date_key']!='after1to2days') ){
            $init_sendtime = true;
        }elseif( !isset($order_info['shtime']['after2to3days']) && !isset($order_info['shtime']['after1to2days']) && isset($order_info['send_times']['date_key']) && ($order_info['send_times']['date_key']=='after2to3days' || $order_info['send_times']['date_key']=='after1to2days' )){
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
        $order_detail = $this->ci->bll_order->orderDetails($order_info['uid'],$params['source'],$params['device_id']);

        $order_info['order_money'] =   number_format((float)$order_detail['money'], 2,'.','');
        $order_info['method_money'] = number_format((float)$order_detail['method_money'], 2,'.','');
        $order_info['order_limit'] = number_format((float)$order_detail['order_limit'], 2,'.','');
        $order_info['jf_money'] = number_format((float)$order_detail['jf_money'], 2,'.','');
        $order_info['card_money'] = number_format((float)$order_detail['card_money'], 2,'.','');
        $order_info['pay_discount'] = number_format((float)$order_detail['pay_discount'], 2,'.','');
        $order_info['order_jf_limit'] = number_format((float)$order_detail['order_jf_limit'], 2,'.','');

        /*运费end*/

//        //默认使用积分
//        $jf = intval($user_info['jf']/100);
//        $can_jf = intval($order_info['order_jf_limit']);
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
//        }
//        else
//        {
//            $order_info['is_use_jf'] = 0;
//        }


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

    /*
     * 设置电子发票
     */
    function setElectronInvoice($params)
    {

        //必要参数验证start
        $required_fields = array(
            'connect_id'=>array('required'=>array('code'=>'500','msg'=>'connect id can not be null')),
            'mobile'=>array('required'=>array('code'=>'500','msg'=>'mobile can not be null')),
        );
        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }

        //获取session信息start
        $this->ci->load->model('user_model');
        $this->ci->load->model('order_model');

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);

        if (!$this->ci->login->is_login()) {
            return array('code'=>300,'msg'=>'登录超时');
        }

        $uid = $this->ci->login->get_uid();

        $order_id = $this->ci->order_model->get_order_id($uid);

        $order_info = $this->ci->order_model->selectOrder("order_name",array("id"=>$order_id));
        $order_name = $order_info['order_name'];


        if (empty($order_id)) {
            return array('code'=>300,'msg'=>'订单号不存在');
        }

        $data = array(
            'fp'=>'个人',
            'fp_dz'=>'',
            'invoice_money'=>0,
        );
        $where = array(
            'id'=>$order_id
        );
        $this->ci->order_model->update_order($data,$where);   //更新订单发票信息
        $this->ci->order_model->delete_order_invoice($order_id);   //删除纸质发票

        $res = $this->ci->order_model->getDzFp($order_name);
        if(!empty($res))
        {
            $data = array(
                'mobile'=>$params['mobile']
            );
            $where = array(
                'order_name'=>$order_name
            );
            $this->ci->order_model->update_DzFp($data,$where);   //更新电子订单电话
        }
        else{
            $this->ci->order_model->add_DzFp($order_name,$params['mobile']);
        }
        return array('code'=>'200','msg'=>$order_id);
    }

    /*
    * 选择支付方式 － pc
    */
    function chosePcPayment($params){
        //必要参数验证start
        $required_fields = array(
            'connect_id'=>array('required'=>array('code'=>'500','msg'=>'connect id can not be null')),
            'pay_parent_id'=>array('required'=>array('code'=>'300','msg'=>'请选择支付方式')),
            'pay_id'=>array('required'=>array('code'=>'300','msg'=>'请选择支付方式')),
            'region_id'=>array('required'=>array('code'=>'500','msg'=>'region id can not be null')),
        );
        if($alert_msg = check_required($params,$required_fields)){
            return array('code'=>$alert_msg['code'],'msg'=>$alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $this->ci->load->model('user_model');
        $this->ci->load->model('order_model');

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);

        if (!$this->ci->login->is_login()) {
            return array('code'=>300,'msg'=>'登录超时');
        }

        $uid = $this->ci->login->get_uid();

        if($params['pay_parent_id']=='3' && $params['pay_id']==='3'){
            $params['pay_id'] = '00003';
        }

        if($params['ispc'] == 1)
        {
            $pay_array  =  $this->ci->config->item("pc_pay_array");
        }
        else{
            $pay_array  =  $this->ci->config->item("pay_array");
        }
        $pay_parent_id=$params['pay_parent_id'];
        $pay_id=$params['pay_id'];
        //支付方式合法性验证
        if(!isset($pay_array[$pay_parent_id])){
            return array('code'=>'300','msg'=>'支付方式错误，请返回购物车重新操作');
        }
        $parent=$pay_array[$pay_parent_id]['name'];
        $son_name = '';

        if(!empty($pay_array[$pay_parent_id]['son'])){
            $son=$pay_array[$pay_parent_id]['son'];
            if(!isset($son[$pay_id])){
                return array('code'=>'300','msg'=>'支付方式错误，请返回购物车重新操作');
            }
            $son_name=$son[$pay_id];
        }else{
            $pay_id = '0';
        }

        if($son_name==""){
            $pay_name = $parent;
        }else{
            $pay_name = $parent."-".$son_name;
        }

        //处理已创建的订单，变更支付方式
        $order_name = $params['order_name'];
        $order = $this->ci->order_model->dump(array('order_name'=>$order_name,'uid'=>$uid));
        if(empty($order))
        {
            return array('code'=>300,'msg'=>'用户订单不存在');
        }
        $order_id = $order['id'];
        //事务开始
        $this->ci->db->trans_begin();
        $this->ci->order_model->set_ordre_payment($pay_name,$pay_parent_id,$pay_id,$order_id);
        $this->ci->db->trans_commit();


        return array('code'=>'200','msg'=>$order_id);
    }

}