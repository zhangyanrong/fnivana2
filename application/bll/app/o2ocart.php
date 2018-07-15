<?php
namespace bll\app;

class O2ocart {

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
        $building_id = isset($params['building_id']) ? $params['building_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$params['items']) return array('code'=>300,'msg'=>'请选择商品后添加');

        $params['items'] = json_decode($params['items'],true);

        $store_id = $this->get_cart_store($params['items']);

        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id,'terminal'=>3));

        if ($region_id) $this->ci->bll_o2ocart->set_province($region_id,$building_id,$store_id);

        foreach ($params['items'] as $value) {
            $item = array(
                'sku_id'     => $value['ppid'],
                'product_id' => $value['pid'],
                'qty'        => $value['qty'],
                'product_no' => $value['pno'],
                'item_type'  => $value['type'],
                'store_id'  => $value['store_id'],
            );

            if (isset($value['pmt_id'])) $item['pmt_id'] = $value['pmt_id'];
            if (isset($value['user_gift_id'])) $item['user_gift_id'] = $value['user_gift_id'];
            if (isset($value['gift_send_id'])) $item['gift_send_id'] = $value['gift_send_id'];
            if (isset($value['gift_active_type'])) $item['gift_active_type'] = $value['gift_active_type'];

            $item_type = isset($value['type']) ? $value['type'] : 'o2o';

            $rs = $this->ci->bll_o2ocart->addCart($item,$item_type);


            if (!$rs){
                $error = $this->ci->bll_o2ocart->get_error();
                if(count($error)==1 && in_array('缺少必要参数', $error)){

                }else{
                    return array('code' => 300, 'msg' => implode('、',$error));
                }

            }
        }
        $cart = $this->ci->bll_o2ocart->get_cart_info($this->_filtercol);
        if ($cart['items']) {
            $cart['items'] = array_values($cart['items']);
            $cart['items'] = $this->format_o2ocart($cart['items']);
        }

        $data['cart'] = (object) $cart;
        $data['cartcount'] = $this->ci->bll_o2ocart->get_cart_count();
        $data['cart_alter'] = $this->ci->bll_o2ocart->get_cart_alter();
        $data['cart_freight'] = $this->ci->bll_o2ocart->get_cart_freight();

        return $data;
    }

    public function remove($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;
        $building_id = isset($params['building_id']) ? $params['building_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id,'terminal'=>3));

        $store_id = $this->ci->bll_o2ocart->getO2oCartStore();

        if($region_id) $this->ci->bll_o2ocart->set_province($region_id,$building_id,$store_id);

        if (!$params['item']) return array('code'=>300,'msg'=>'请选择商品后添加');

        $params['item'] = json_decode($params['item'],true);

        $sku_id = $params['item']['ppid'];
        $ik = $params['item']['ik'];
        $item_type = isset($params['item']['type']) ? $params['item']['type'] : 'o2o';

        $rs = $this->ci->bll_o2ocart->removeCart($ik,$item_type);

        if ($rs) {
            $cart =  $this->ci->bll_o2ocart->get_cart_info($this->_filtercol);
            if ($cart['items']) {
                $cart['items'] =  array_values($cart['items']);
                $cart['items'] = $this->format_o2ocart($cart['items']);
            }

            $data['cart'] = (object) $cart;
            $data['cartcount'] = $this->ci->bll_o2ocart->get_cart_count();
            $data['cart_alter'] = $this->ci->bll_o2ocart->get_cart_alter();
            $data['cart_freight'] = $this->ci->bll_o2ocart->get_cart_freight();

            return $data;
        } else {
            $error = $this->ci->bll_o2ocart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));
        }
    }

    public function update($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;
        $building_id = isset($params['building_id']) ? $params['building_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id,'terminal'=>3));

        $store_id = $this->ci->bll_o2ocart->getO2oCartStore();
        if($region_id) $this->ci->bll_o2ocart->set_province($region_id,$building_id,$store_id);

        if (!$params['item']) return array('code'=>300,'msg'=>'请选择商品后添加');

        $params['item'] = json_decode($params['item'],true);

        $item_type = isset($params['item']['type']) ? $params['item']['type'] : 'o2o';
        $item = array(
            // 'sku_id'     => $params['item']['ppid'],
            // 'product_id' => $params['item']['pid'],
            'qty'        => $params['item']['qty'],
            // 'item_type'  => $item_type,

            'ik' => $params['item']['ik'],
            );

        $rs = $this->ci->bll_o2ocart->updateCart($item,$item_type);

        if ($rs) {

            $cart = $this->ci->bll_o2ocart->get_cart_info($this->_filtercol);
            if ($cart['items']) {
                $cart['items'] = array_values($cart['items']);
                $cart['items'] = $this->format_o2ocart($cart['items']);
            }

            $data['cart'] = (object) $cart;
            $data['cartcount'] = $this->ci->bll_o2ocart->get_cart_count();
            $data['cart_alter'] = $this->ci->bll_o2ocart->get_cart_alter();
            $data['cart_freight'] = $this->ci->bll_o2ocart->get_cart_freight();

            return $data;
        } else {
                $error = $this->ci->bll_o2ocart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));
        }
    }

    public function clear($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $store_id = isset($params['store_id']) ? $params['store_id'] : '';
        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id, 'terminal'=>3));
        if($store_id){
            $this->ci->bll_o2ocart->clearCart($store_id);
        }else{
            $this->ci->bll_o2ocart->emptyCart();
        }
        $cart =  $this->ci->bll_o2ocart->get_cart_info($this->_filtercol);
        if ($cart['items']) {
            $cart['items'] = array_values($cart['items']);
            $cart['items'] = $this->format_o2ocart($cart['items']);
        }

        $data['cart'] = (object) $cart;
        $data['cartcount'] = $this->ci->bll_o2ocart->get_cart_count();
        $data['cart_alter'] = $this->ci->bll_o2ocart->get_cart_alter();
        $data['cart_freight'] = $this->ci->bll_o2ocart->get_cart_freight();

        return $data;
    }

    public function get($params)
    {

        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id  = $params['region_id'] ? $params['region_id'] : 0;
        $building_id  = $params['building_id'] ? $params['building_id'] : 0;


        // 登录初始
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        // cyc初始化购物车 (从SESSION/USER_TABLE中载入商品基本信息)
        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id, 'terminal'=>3));
        $store_id = $this->ci->bll_o2ocart->getO2oCartStore();
        // cyc设置省份
        if($region_id) {
            $this->ci->bll_o2ocart->set_province($region_id,$building_id,$store_id);
        }

        // cyc获取商品详情以及活动规则
        $cart = $this->ci->bll_o2ocart->get_cart_info($this->_filtercol);
        if ($cart['items']) {
            $cart['items'] = array_values($cart['items']);
            $cart['items'] = $this->format_o2ocart($cart['items']);
        }

        // cyc整理数据返回data
        $data['cart']      = (object) $cart;
        $data['cartcount'] = $this->ci->bll_o2ocart->get_cart_count();
        $data['cart_alter'] = $this->ci->bll_o2ocart->get_cart_alter();
        $data['cart_freight'] = $this->ci->bll_o2ocart->get_cart_freight();

        return $data;
    }

    function format_o2ocart($items){
        $format_cart = array();
        $this->ci->load->model('o2o_store_model');
        foreach ($items as $key => $value) {
            if($value['store_id']){
                if(!isset($format_cart[$value['store_id']])){
                    $store = $this->ci->o2o_store_model->dump(array('id'=>$value['store_id']));
                    $format_cart[$value['store_id']]['store_id'] = $value['store_id'];
                    $format_cart[$value['store_id']]['store_name'] = $store['name'];
                }
                $format_cart[$value['store_id']]['store_items'][] = $value;
            }else{
                $format_cart['gift']['gift_itmes'][] = $value;
            }
        }
        krsort($format_cart);
        return array_values($format_cart);
    }

    public function checkCartInit($params){
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id  = $params['region_id'] ? $params['region_id'] : 0;
        $building_id  = $params['building_id'] ? $params['building_id'] : 0;
        $store_id = $params['store_id'] ? $params['store_id'] : 0;
        $latitude  = $params['latitude'];
        $longitude = $params['longitude'];
        $is_clear = $params['is_clear']?true:false;
        $version = $params['version'];

        $store_info = array();
        if (!$session_id) return array('code'=>300,'msg'=>'param `connect_id` is required');
        if(!$store_id){
            $params['lonlat'] = $params['lonlatForO2O']?$params['lonlatForO2O']:$params['lonlat'];
            $this->ci->load->bll('deliver');
            $deliver = $this->ci->bll_deliver->getO2oTmsCode($params);
            $store_id = $deliver['store_id']?$deliver['store_id']:0;
        }
        // 登录初始
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {
            return array('code'=>400,'msg'=>'登录过期，请重新登录');
        }
        // cyc初始化购物车 (从SESSION/USER_TABLE中载入商品基本信息)
        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id, 'terminal'=>3));

        // cyc设置省份
        //if($region_id) {
            $this->ci->bll_o2ocart->set_province($region_id,$building_id,$store_id);
        //}
        if(version_compare($version, '4.1.0') < 0){
            $rs = $this->ci->bll_o2ocart->checkCartInit($latitude,$longitude,$building_id,$store_info,$is_clear);
        }else{
            $rs = $this->ci->bll_o2ocart->checkCartInit_v2($store_id,$is_clear);
        }
        if (!$rs){
            $error = $this->ci->bll_o2ocart->get_error();
            if(count($error)==1 && in_array('缺少必要参数', $error)){

            }else{
                return array('code' => 300, 'msg' => implode('、',$error));
            }
        }
        return array('code'=>200,'msg'=>'succ');
    }

    public function selpmt($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);
        $pmt_id = $params['pmt_id'];

        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id,'terminal'=>3));
        $this->ci->bll_o2ocart->set_province($params['region_id']);

        $rs = $this->ci->bll_o2ocart->selpmt($pmt_id);

        if ($rs) {
            // $cart = $this->ci->bll_o2ocart->get_cart_info($this->_filtercol);
            // if ($cart['items']) {
            //     $cart['items'] = array_values($cart['items']);
            //     $cart['items'] = $this->format_o2ocart($cart['items']);
            // }

            // $data['cart'] = (object) $cart;
            // $data['cartcount'] = $this->ci->bll_o2ocart->get_cart_count();
            return $rs;
        } else {
                $error = $this->ci->bll_o2ocart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));
        }
    }

    private function get_cart_store($items){
        foreach ($items as $value) {
            if($value['store_id']){
                return $value['store_id'];
            }
        }
        return false;
    }

    public function checkCartExist($params){
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $latitude  = $params['latitude'];
        $longitude = $params['longitude'];
        $region_id = $params['region_id'];
        $version = $params['version'];
        $this->ci->load->bll('o2ocart',array('session_id'=>$session_id, 'terminal'=>3));
        if(version_compare($version, '4.1.0') < 0) {
            $rs = $this->ci->bll_o2ocart->checkCartExist($latitude, $longitude);
        }else{
            $rs = $this->ci->bll_o2ocart->checkCartExist_v2($latitude,$longitude, $region_id);
        }
        return array('code'=>200, 'msg'=> '', 'status' => (int) $rs );

    }
}