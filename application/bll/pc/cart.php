<?php
namespace bll\pc;

class Cart{

    public function __construct()
    {
        $this->ci = & get_instance();

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
        // 测试代码
        // $this->test();
    }


    public function add($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3));
        if($region_id) $this->ci->bll_cart->set_province($region_id);

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
            // if (isset($value['deppid'])) $item['deppid'] = $value['deppid'];

            $item_type = isset($value['type']) ? $value['type'] : 'normal';

            $rs = $this->ci->bll_cart->addCart($item,$item_type);


            if (!$rs){
                $error = $this->ci->bll_cart->get_error();
                return array('code' => 300, 'msg' => implode('、',$error));
            }
        }
        $data = array(
            'cart_items' => $this->ci->bll_cart->get_cart_items(),
            'cartcount' => $this->ci->bll_cart->get_cart_count(),
			'cart' => $this->ci->bll_cart->get_cart_info(),
            );

        //by jackchen
        $data['cart']['total_amount'] = number_format((float)$data['cart']['total_amount'], 2,'.','');
        $data['cart']['goods_amount'] = number_format((float)$data['cart']['goods_amount'], 2,'.','');
        $data['cart']['goods_cost'] = number_format((float)$data['cart']['goods_cost'], 2,'.','');

        return array('code' => 200, 'msg' => '加入购物车成功','data'=>$data,'cart');
    }

    public function remove($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3));

        if ($region_id) $this->ci->bll_cart->set_province($region_id);

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
            $data['cart'] = $this->ci->bll_cart->get_cart_info($this->_filtercol);
            $data['cart_items'] = $this->ci->bll_cart->get_cart_items();
            $data['cartcount'] = $this->ci->bll_cart->get_cart_count();

            //by jackchen
            $data['cart']['total_amount'] = number_format((float)$data['cart']['total_amount'], 2,'.','');
            $data['cart']['goods_amount'] = number_format((float)$data['cart']['goods_amount'], 2,'.','');
            $data['cart']['goods_cost'] = number_format((float)$data['cart']['goods_cost'], 2,'.','');

            return array('code' => 200, 'msg' => '删除成功','data' => $data);
        } else {
            $error = $this->ci->bll_cart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));
        }
    }

    public function update($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3));
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
            $data['cart'] = $this->ci->bll_cart->get_cart_info($this->_filtercol);
            $data['cart_items'] = $this->ci->bll_cart->get_cart_items();
            $data['cartcount'] = $this->ci->bll_cart->get_cart_count();

            //by jackchen
            $data['cart']['total_amount'] = number_format((float)$data['cart']['total_amount'], 2,'.','');
            $data['cart']['goods_amount'] = number_format((float)$data['cart']['goods_amount'], 2,'.','');
            $data['cart']['goods_cost'] = number_format((float)$data['cart']['goods_cost'], 2,'.','');

            return array('code' => 200, 'msg' => '更新成功','data'=>$data);
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

        return array('code'=>200,'msg'=>'清空购物车成功');
    }

    public function get($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);


        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3));
        if ($region_id) $this->ci->bll_cart->set_province($region_id);

        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $data['cart'] = $this->ci->bll_cart->get_cart_info($this->_filtercol);

        //by jackchen
        $data['cart']['total_amount'] = number_format((float)$data['cart']['total_amount'], 2,'.','');
        $data['cart']['goods_amount'] = number_format((float)$data['cart']['goods_amount'], 2,'.','');
        $data['cart']['goods_cost'] = number_format((float)$data['cart']['goods_cost'], 2,'.','');

        return array('code' => 200, 'data' => $data, 'msg'=> '');
    }

    /**
     * 获取购物车数量
     *
     * @return void
     * @author
     **/
    public function count($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id));

        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $data['cartcount'] = $this->ci->bll_cart->get_cart_count();

        return array('code' => 200, 'data' => $data, 'msg'=>'');
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

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $pmt_type = $params['pmt_type'];
        $pmt_id   = $params['pmt_id'];


        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>3));
        $this->ci->bll_cart->set_province($params['province_id']);

        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $data = $this->ci->bll_cart->selpmt($pmt_type,$pmt_id);

        return array('code'=>200,'data'=>$data,'msg'=>'');
    }

    public function newget($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);


        $this->ci->load->bll('cart',array('session_id'=>$session_id,'terminal'=>1));
        if ($region_id) $this->ci->bll_cart->set_province($region_id);

        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_cart->setCart($carttmp);
        }

        $data['cart'] = $this->ci->bll_cart->get_cart_info($this->_filtercol);

        //by jackchen
        $data['cart']['total_amount'] = number_format((float)$data['cart']['total_amount'], 2,'.','');
        $data['cart']['goods_amount'] = number_format((float)$data['cart']['goods_amount'], 2,'.','');
        $data['cart']['goods_cost'] = number_format((float)$data['cart']['goods_cost'], 2,'.','');

        return array('code' => 200, 'data' => $data, 'msg'=> '');
    }
}