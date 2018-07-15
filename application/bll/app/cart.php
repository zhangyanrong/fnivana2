<?php
namespace bll\app;

class Cart {

    public function __construct($params = array()) {

        // cyc登录可以统一放在这里

        $this->ci = & get_instance();

        // cyc 商品字段过滤
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


    public function add($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;
        $version = $params['version'] ? $params['version'] : '3.5.0';

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3,'version'=>$version));

        if ($region_id) $this->ci->bll_cart->set_province($region_id);

        if (!$params['items']) return array('code'=>300,'msg'=>'请选择商品后添加');

        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $params['items'] = json_decode($params['items'],true);

        foreach ($params['items'] as $value) {
            $item = array(
                'sku_id'     => $value['ppid'],
                'product_id' => $value['pid'],
                'qty'        => $value['qty'],
                'product_no' => $value['pno'],
                'item_type'  => $value['type'],
            );

            if (isset($value['pmt_id'])) $item['pmt_id'] = $value['pmt_id'];
            if (isset($value['user_gift_id'])) $item['user_gift_id'] = $value['user_gift_id'];
            if (isset($value['gift_send_id'])) $item['gift_send_id'] = $value['gift_send_id'];
            if (isset($value['gift_active_type'])) $item['gift_active_type'] = $value['gift_active_type'];

            $item_type = isset($value['type']) ? $value['type'] : 'normal';

            $rs = $this->ci->bll_cart->addCart($item,$item_type);


            if (!$rs){
                $error = $this->ci->bll_cart->get_error();
                if(count($error)==1 && in_array('缺少必要参数', $error)){

                }else{
                    return array('code' => 300, 'msg' => implode('、',$error));
                }

            }
        }
        $data = array(
            'cartcount' => $this->ci->bll_cart->get_cart_count(),
            );

        if (!$session_id){
            $cart_items =  $this->ci->bll_cart->get_cart_items();

            // 安卓输出
            // if ($params['platform'] == 'ANDROID') {
            //     $data['cart_items'] = array_values($cart_items);
            // }

            $data['cart_items'] = (object) $cart_items;
        }

        return $data;
    }

    public function remove($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;
        $version = $params['version'] ? $params['version'] : '3.5.0';

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3,'version'=>$version));
        if($region_id) $this->ci->bll_cart->set_province($region_id);

        if (!$params['item']) return array('code'=>300,'msg'=>'请选择商品后添加');

        if (!$session_id && $carttmp = json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $params['item'] = json_decode($params['item'],true);

        $sku_id = $params['item']['ppid'];
        $ik = $params['item']['ik'];
        $item_type = isset($params['item']['type']) ? $params['item']['type'] : 'normal';

        $rs = $this->ci->bll_cart->removeCart($ik,$item_type);

        if ($rs) {
            $cart =  $this->ci->bll_cart->get_cart_info($this->_filtercol);
            if ($cart['items']) {
                $cart['items'] =  array_values($cart['items']);
            }

            $data['cart'] = (object) $cart;
            $data['cartcount'] = $this->ci->bll_cart->get_cart_count();

            if (!$session_id){
                $cart_items = $this->ci->bll_cart->get_cart_items();

                // 安卓输出
                // if ($params['platform'] == 'ANDROID') {
                //     $data['cart_items'] = array_values($cart_items);
                // }

                $data['cart_items'] = (object) $cart_items;
            }

            return $data;
        } else {
            $error = $this->ci->bll_cart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));
        }
    }

    public function update($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;
        $version = $params['version'] ? $params['version'] : '3.5.0';

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3,'version'=>$version));
        if($region_id) $this->ci->bll_cart->set_province($region_id);

        if (!$params['item']) return array('code'=>300,'msg'=>'请选择商品后添加');
        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $params['item'] = json_decode($params['item'],true);

        $item_type = isset($params['item']['item_type']) ? $params['item']['item_type'] : 'normal';
        $item = array(
            // 'sku_id'     => $params['item']['ppid'],
            // 'product_id' => $params['item']['pid'],
            'qty'        => $params['item']['qty'],
            // 'item_type'  => $item_type,

            'ik' => $params['item']['ik'],
            );

        $rs = $this->ci->bll_cart->updateCart($item,$item_type);

        if ($rs) {

            $cart = $this->ci->bll_cart->get_cart_info($this->_filtercol);
            if ($cart['items']) {
                $cart['items'] = array_values($cart['items']);
            }

            $data['cart'] = (object) $cart;
            $data['cartcount'] = $this->ci->bll_cart->get_cart_count();

            if (!$session_id){
                $cart_items =  $this->ci->bll_cart->get_cart_items();

                // 安卓输出
                // if ($params['platform'] == 'ANDROID') {
                //     $data['cart_items'] = array_values($cart_items);
                // }

                $data['cart_items'] = (object) $cart_items;

            }

            return $data;
        } else {
                $error = $this->ci->bll_cart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));
        }
    }

    public function clear($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart');
        $this->ci->bll_cart->emptyCart();

        $cart =  $this->ci->bll_cart->get_cart_info($this->_filtercol);
        if ($cart['items']) {
            $cart['items'] = array_values($cart['items']);
        }

        $data['cart'] = (object) $cart;
        $data['cartcount'] = $this->ci->bll_cart->get_cart_count();

        return $data;
    }

    public function get($params)
    {

        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id  = $params['region_id'] ? $params['region_id'] : 0;
        $version = $params['version'] ? $params['version'] : '3.5.0';

        // 登录初始
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        // cyc初始化购物车 (从SESSION/USER_TABLE中载入商品基本信息)
        $this->ci->load->bll('cart',array('session_id'=>$session_id, 'terminal'=>3,'version'=>$version));

        // cyc设置省份
        if($region_id) {
            $this->ci->bll_cart->set_province($region_id);
        }

        // cyc载入临时购物车
        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        // cyc获取商品详情以及活动规则
        $cart = $this->ci->bll_cart->get_cart_info($this->_filtercol);
        if ($cart['items']) {
            $cart['items'] = array_values($cart['items']);
        }

        // cyc整理数据返回data
        $data['cart']      = (object) $cart;
        $data['cartcount'] = $this->ci->bll_cart->get_cart_count();

        if (!$session_id) {
            $cart_items = $this->ci->bll_cart->get_cart_items();
            // 安卓输出
            // if ($params['platform'] == 'ANDROID') {
            //     $data['cart_items'] = array_values($cart_items);
            // }
            $data['cart_items'] = (object) $cart_items;
        }
        return $data;
    }

    /**
     * 购物车商品优惠
     *
     * @return void
     * @author
     **/
    public function selpmt($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $version = $params['version'] ? $params['version'] : '3.5.0';

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $pmt_type = $params['pmt_type'];
        $pmt_id   = (int) $params['pmt_id'];


        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3,'version'=>$version));
        $this->ci->bll_cart->set_province($params['region_id']);

        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $data = $this->ci->bll_cart->selpmt($pmt_type,$pmt_id,$params['outofmoney'],$params['region_id']);

        return $data;
    }
}