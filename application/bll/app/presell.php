<?php

namespace bll\app;

include_once 'app.php';

class Presell extends App
{
    function __construct()
    {
        parent::__construct();
        $this->ci->load->helper('public');
        $this->ci->load->model('presell_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model('order_model');

        $this->_filtercol = array(
            'device_limit',
            'card_limit',
            'jf_limit',
            'group_limit',
            'pay_limit',
            'first_limit',
            'active_limit',
            'delivery_limit',
            'pay_discount_limit',
            'free',
            'offline',
            'type',
            'free_post',
            'free_post',
            'is_tuan',
            'use_store',
            'xsh',
            'xsh_limit',
            'ignore_order_money',
            'group_pro',
            'iscard',
            'pmt_pass',
        );
    }

    /**
     * 订单初始化
     *
     * @return void
     * @author
     **/
    public function orderInit($params)
    {
        $connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        $item       = $params['items'] ? @json_decode($params['items'],true) : '';
        //$item = array_shift($items);
        $province_id = $params['province_id'] ? $params['province_id'] : 0;

        //支付方式
        $pay_id = $params['pay_id'] ? $params['pay_id'] : 0;
        $pay_parent_id = $params['pay_parent_id'] ? $params['pay_parent_id'] : 1;

        if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
        if (!$province_id)    return array('code'=>300,'msg'=>'param `province_id` is required');
        if (!$item)        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');

        //if (!isset($params['pay_id']))    return array('code'=>300,'msg'=>'请选择支付方式');
        //if (!isset($params['pay_parent_id']))    return array('code'=>300,'msg'=>'请选择支付方式');

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }

        if(!isset($item['qty']) || $item['qty'] <=0)
        {
            return array('code'=>300,'msg'=>'购买商品数量错误');
        }

        //check
        $check = $this->check_product($item);
        if(!empty($check))
        {
            return $check;
        }

        //一次仅允许一件商品
        $cart_items = array();
        $cart_items[] = array(
            'sku_id'     => $item['ppid'],
            'product_id' => $item['pid'],
            'qty'        => $item['qty'],
            'product_no' => $item['pno'],
            'item_type'  => 'presell',
        );

        $this->ci->load->bll('cart');
        $this->ci->bll_cart->set_province($region_id);
        $res = $this->ci->bll_cart->setCart($cart_items);//something to do;

        $error = $this->ci->bll_cart->get_error();
        if ($error) {
            return array('code'=>300,'msg'=>implode(';',$error));
        }

        $this->ci->load->bll('presell');
        $rs = $this->ci->bll_presell->orderInit('',$pay_parent_id,$pay_id);

        if (!$rs) {
            $code = $this->ci->bll_presell->get_code();
            $error = $this->ci->bll_presell->get_error();

            return array('code'=>$code ? $code : 300,'msg' => $error);
        }

        foreach ((array) $rs['cart_info']['items'] as $key => $value) {
            foreach ($value as $k => $v) {
                if (in_array($k,$this->_filtercol)) {
                    unset($rs['cart_info']['items'][$key][$k]);
                }
            }
        }

        unset($rs['cart_info']['pmt_alert']);

        /*用户积分start*/
        $this->ci->load->bll('user');
        $user_info = $this->ci->bll_user->userInfo(array('connect_id'=>$connect_id));
        $rs['user_money'] = number_format($user_info['money'],2,'.','');
        $rs['user_coupon_num'] = $user_info['coupon_num'];
        $rs['user_mobile'] = $user_info['mobile'];
        /*用户积分end*/

        //支付方式
        $this->ci->load->bll($params['source'].'/region');
        $obj = 'bll_' . $params['source'] . '_region';
        $send_time_params = array(
            'service'=>'region.getPay',
            'province_id'=>$province_id,
            'connect_id'=>$params['connect_id'],
            'source'=>$params['source'],
            'platform' => $params['platform'],
        );
        $pay_arr = $this->ci->$obj->getPay($send_time_params);
        unset($pay_arr['offline']);
        $rs['payments'] = $pay_arr;

        //by jackchen
        $rs['cart_info']['total_amount'] = number_format((float)$rs['cart_info']['total_amount'], 2,'.','');
        $rs['cart_info']['goods_amount'] = number_format((float)$rs['cart_info']['goods_amount'], 2,'.','');
        $rs['cart_info']['goods_cost'] = number_format((float)$rs['cart_info']['goods_cost'], 2,'.','');


        //预售商品限制
        $rs['can_use_card'] =0;
        $rs['can_use_jf'] =0;

        self::str($rs);
        return $rs;
    }

    /*
     *  创建订单
     */
    public function createOrder($params)
    {
        $address_id = $params['address_id'] ? $params['address_id'] : 0;
        $connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        $item       = $params['items'] ? @json_decode($params['items'],true) : '';

        $msg = $params['msg'] ? $params['msg'] : '';

        //支付方式
        $pay_id = $params['pay_id'] ? $params['pay_id'] : 0;
        $pay_parent_id = $params['pay_parent_id'] ? $params['pay_parent_id'] : 1;

        if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
        if (!$address_id)    return array('code'=>300,'msg'=>'请选择收货地址');
        if (!$item || !$item['pid'])        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');

        if (!isset($params['pay_id']))    return array('code'=>300,'msg'=>'请选择支付方式');
        if (!isset($params['pay_parent_id']))    return array('code'=>300,'msg'=>'请选择支付方式');

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }


        //check
        $check = $this->check_product($item);
        if(!empty($check))
        {
            return $check;
        }

        //预售唯一标示
        $item['active_id'] = 'presell:'.$item['pid'];

        //一次仅允许一件商品
        $cart_items = array();
        $cart_items[] = array(
            'sku_id'     => $item['ppid'],
            'product_id' => $item['pid'],
            'qty'        => $item['qty'],
            'product_no' => $item['pno'],
            'item_type'  => 'presell',
            'active_id'=>$item['active_id'],
        );

        $this->ci->load->bll('cart');
        $this->ci->bll_cart->set_province($region_id);
        $this->ci->bll_cart->setCart($cart_items);//something to do
        $error = $this->ci->bll_cart->get_error();
        if ($error) {
            return array('code'=>300,'msg'=>implode(';',$error));
        }

        $this->ci->load->bll('presell');
        $rs = $this->ci->bll_presell->createOrder($address_id, $item['pid'], $item['ppid'],$msg,$pay_id,$pay_parent_id,$params);

        if (!$rs) {
            $code = $this->ci->bll_presell->get_code();
            $error = $this->ci->bll_presell->get_error();
            return array('code'=>$code ? $code : 300,'msg'=>$error);
        }

        //统计预售购买量
        $presell = $this->ci->presell_model->get_list($item['pid']);
        if(count($presell) >0)
        {
            $pre_count = $presell[0]['ordercount'];
            $pre_qty = 1;
            $count = $pre_count+$pre_qty;
            $this->ci->presell_model->update_count($item['pid'],$count);
        }

        self::str($rs);
        return $rs;
    }

    public static function str(&$array)
    {
        if (is_array($array)) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    self::str($value);
                } else {
                    $value = strval($value);
                }
            }
        } else {
            $array = strval($array);
        }
    }

    /*
     * check product
     */
    private function check_product($item)
    {
        if(isset($item['pid']))
        {
            $presell = $this->ci->presell_model->get_list($item['pid']);

            if(count($presell) >0)
            {
                $pre_max = $presell[0]['max_num'];
                $pre_only = $presell[0]['is_only'];

                //单份购买
                if($pre_only == 1 && $item['qty'] > 1)
                {
                    return array('code'=>300,'msg'=>'该预售商品一次只允许购买一份');
                }

                //最多购买数量
                if($pre_max >0)
                {
                    //一次购买
                    if($item['qty'] > $pre_max)
                    {
                        return array('code'=>300,'msg'=>'该预售商品一次最多只允许购买'.$pre_max.'份');
                    }

                    //用户最多购买数量
                    $uid = $this->ci->login->get_uid();
                    $field ='id,order_status';
                    $where = array(
                        'uid'=>$uid,
                        'order_type'=>5,
                    );
                    $order = $this->ci->order_model->selectOrderList($field,$where);

                    if(count($order) >0)
                    {
                        $order_ids = array();
                        foreach($order as $val) {
                            if ($val['order_status'] != 3) {
                                array_push($order_ids, $val['id']);
                            }
                        }
                        $where_in[] = array(
                            'key'=>'order_id',
                            'value'=>$order_ids
                        );
                        $product = $this->ci->order_model->selectOrderProducts('product_id,qty','',$where_in);

                        //可购买预售商品数量
                        $pcount = 0;
                        if(count($product) >0)
                        {
                            foreach($product as $val)
                            {
                                if($val['product_id'] == $item['pid'])
                                {
                                    $pcount += $val['qty'];
                                }
                            }
                        }

                        //剩余可购买数量
                        $count = ($pre_max - $pcount);
                        if($item['qty'] > $count)
                        {
                            return array('code'=>300,'msg'=>'预售商品每人最多购买'.$pre_max.'份');
                        }
                    }
                }
            }
            else
            {
                return array('code'=>300,'msg'=>'预售商品已下架');
            }
        }
        else
        {
            return array('code'=>300,'msg'=>'请先选择您需要购买的商品');
        }
    }

}
