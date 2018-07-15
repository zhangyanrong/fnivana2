<?php
namespace bll\pc;
include_once("pc.php");
/**
* 商品相关接口
*/
class User extends pc{

	function __construct($params=array()){
		$this->ci = &get_instance();
		$this->ci->load->model('user_model');
	}

	 /**
     * 会员赠品
     *
     * @return void
     * @author
     **/
    public function giftsGet($params)
    {

        $session_id = $params['connect_id'];
        if (!$session_id) {
            return array('code'=>500,'msg'=>'connect id can not be null');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {
            return array('code'=>300,'msg'=>'登录超时');
        }

        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3));

        $valid = isset($params['valid'])?$params['valid']:1;
        $region_id = $params['region_id']?$params['region_id']:106092;
        //$gift_type = $params['gift_type']?$params['gift_type']:2;
        $gift_type = 0;
        $page = isset($params['page']) ? $params['page'] : 1;
		$limit = isset($params['limit']) ? $params['limit'] : -1;

        $this->ci->load->bll('user');
        $gifts = $this->ci->bll_user->giftsGet($region_id,$valid,$gift_type, $page, $limit);

        return array('code'=>200,'msg'=>'','data'=>array('usergifts'=>$gifts));
    }

    /**
     * 礼品卷
     *
     * @return void
     * @author
     **/
    public function gcouponGet($params)
    {
        $session_id = $params['connect_id'];

        if (!$session_id) {
            return array('code'=>500,'msg'=>'connect id can not be null');
        }

        $card_number = $params['card_number'];
        if (!$card_number) {
            return array('code' => 300,'msg' => '礼品券不能为空');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {

            return array('code'=>300,'msg'=>'登录超时');
        }

        /*临时购物车*/
        // $this->ci->load->bll('cart',array('session_id'=>$session_id));
        // if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
        //     $this->ci->bll_cart->setCart($carttmp);
        // }
        // $cart_items = $this->ci->bll_cart->get_cart_items();


        $this->ci->load->bll('gcoupon');
        $uid = $this->ci->login->get_uid();
        $gifts = $this->ci->bll_gcoupon->giftsGet($card_number,$uid,$msg);

        if ($gifts === false) {
            return array('code'=>300,'msg'=>$msg);
        }

        /*加入购物车*/
        // foreach ($gifts['products'] as $key => $value) {
        //     $item = array(
        //         'sku_id'             => $value['product_price_id'],
        //         'product_id'         => $value['product_id'],
        //         'qty'                => $gifts['giftsend']['qty'],
        //         'product_no'         => $value['product_no'],
        //         'item_type'          => 'coupon_gift',
        //         'gift_send_id'       => $gifts['giftsend']['id'],
        //         'gift_coupon_id'     => $gifts['giftcoupon']['id'],
        //         'gift_coupon_number' => $gifts['giftcoupon']['card_number'],
        //         'user_gift_id'       => $gifts['giftsend']['user_gifts']['id'],
        //     );

        //     $rs = $this->ci->bll_cart->addCart($item,'coupon_gift');

        //     if (!$rs){
        //         $error = $this->ci->bll_cart->get_error();

        //         return array('code'=>300,'msg'=>implode('、', $error),);
        //     }

        // }

        // $data = array(
        //     'cartcount' => $this->ci->bll_cart->get_cart_count()
        //     );

        return array('code'=>200,'msg'=>'兑换成功，请在我的赠品里查看','data'=>array());
    }

    /*
   *   会员特权
   */
    public function privilege($params){
        $result = parent::call_bll($params);
        if(isset($result['code']) && $result['code']!='200'){
            return $result;
        }else{
            $return  = $result;
            $user = $result;
            // 会员等级
            $rank = $this->ci->user_model->get_rank($user['user_rank']);

            $next_user_rank = $user['user_rank']+1;
            $next_rank = $this->ci->user_model->get_rank($next_user_rank);

            $cycle = $this->ci->user_model->get_cycle();
            // $cycle += 1;

            // $end_time = date('Y-m-d 59:59:59',strtotime('this month'));
            // $start_time = date('Y-m-d 59:59:59',strtotime("last day of -{$cycle} month"));
            $start_time = date('Y-m-d',strtotime("- {$cycle} month"));
            $end_time = date('Y-m-d H:i:s');

            $order_stat = $this->ci->user_model->user_rank_order_info($user['id'],$start_time,$end_time);
            $rank['curr_ordernum'] = (int) $order_stat['ordernum'];
            $rank['curr_ordermoney'] = (float) $order_stat['ordermoney'];

            $return['rank_info'] = $rank;
            unset($return['rank_info']['pmt'],$return['rank_info']['ordernum'],$return['rank_info']['ordermoney']);

            $return['rank_info']['next_rank_name'] = '';
            $return['rank_info']['diff_ordernum'] = 0;
            $return['rank_info']['diff_ordermoney'] = 0;
            $return['rank_info']['rate_ordernum'] = 0;
            $return['rank_info']['rate_ordermoney'] = 0;
            if ($next_rank) {
                $return['rank_info']['next_rank_name'] = $next_rank['name'];
                $return['rank_info']['diff_ordernum'] = $next_rank['ordernum'] > $order_stat['ordernum'] ? $next_rank['ordernum'] - $order_stat['ordernum'] : 0;
                $return['rank_info']['diff_ordermoney'] = $next_rank['ordermoney'] > $order_stat['ordermoney'] ? $next_rank['ordermoney'] - $order_stat['ordermoney'] : 0;

                $return['rank_info']['rate_ordernum'] = $order_stat['ordernum'] > 0 && $next_rank['ordernum'] > 0 ? min(100,intval($order_stat['ordernum']/$next_rank['ordernum']*100)) : 0;
                $return['rank_info']['rate_ordermoney'] = $next_rank['ordermoney'] > 0 && $order_stat['ordermoney'] > 0 ? min(100,intval($order_stat['ordermoney']/$next_rank['ordermoney']*100)) : 0;
            }

            if(strstr($return['userface'], 'default_userpic.png')){
                $return['userface'] = '';
            }
        }
        return $return;
    }


    /*获取大客户收款列表*/
    public function getComplanyService($params){
        $session_id = $params['connect_id'];

        if (!$session_id) {
            return array('code'=>500,'msg'=>'connect id can not be null');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {

            return array('code'=>300,'msg'=>'登录超时');
        }

        $uid = $this->ci->login->get_uid();

        $list = $this->ci->user_model->getComplany($uid);

        return array('code'=>200,'msg'=>'succ','data'=>$list);
    }

    public function getOneComplanyService($params){
        $session_id = $params['connect_id'];

        if (!$session_id) {
            return array('code'=>500,'msg'=>'connect id can not be null');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {

            return array('code'=>300,'msg'=>'登录超时');
        }

        if (preg_match('/^A/', $params['out_trade_number'])) {
            $list = $this->ci->user_model->getOneComplany($params['out_trade_number']);
            return $list;
        } else {
            return array('code'=>300,'msg'=>'订单号错误');
        }

    }


    public function updateComplanyService($params){
        $session_id = $params['connect_id'];

        if (!$session_id) {
            return array('code'=>500,'msg'=>'connect id can not be null');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {

            return array('code'=>300,'msg'=>'登录超时');
        }

        $uid = $this->ci->login->get_uid();

        if (preg_match('/^A/', $params['out_trade_number'])) {
            $list = $this->ci->user_model->updateComplanyStauts($uid,$params['out_trade_number']);
            return $list;
        } else {
            return array('code'=>300,'msg'=>'订单号错误');
        }
    }



}