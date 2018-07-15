<?php
namespace bll\app;

/**
* 线下门店接口
*/
class O2o
{

    function __construct()
    {
        $this->ci = &get_instance();

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
     * 根据ID获取门店地区下一级
     *
     * @return void
     * @author
     **/
    public function regionlist($params)
    {
        $pid = $params['pid'];

        if (!isset($pid)) return array('code'=>300,'msg'=>'param `pid` is required');

        $data = array();

        $this->ci->load->model('o2o_region_model');

        // 获取城市列表
        if ($pid == 0) {
            $is_top_region = true;
            $provincelist = $this->ci->o2o_region_model->getList('id',array('pid'=>0));
            $pid = array_map('current', $provincelist);
        }

        $rows = $this->ci->o2o_region_model->getList('id,name,attr,pid,latitude,longitude,area_id',array('pid'=>$pid));
        if($is_top_region){
            $user_region = $this->ci->o2o_region_model->get_region_by_connectid($params['connect_id']);
            if(!empty($user_region)){
                $user_region_id = $this->ci->o2o_region_model->get_city_by_province_id($user_region['region_id']);
                foreach ($rows as $key => $value) {
                    if($value['area_id']!=$user_region_id){
                        unset($rows[$key]);
                    }
                }
            }
        }
        $this->ci->load->model('o2o_store_building_model');
        $attr = $this->ci->o2o_region_model->attr;
        foreach ($rows as $key => $row) {
            $d = array();

            // 是否存在楼宇绑定店铺
            if ($row['attr'] < 5) {
                $is_show = $this->ci->o2o_region_model->is_show($row['id']);
                if (!$is_show) continue;
            }

            $d['name'] = preg_replace('/（.*）/', '', $row['name']);
            $d['id']   = $row['id'];

            if ($row['attr'] == 5) {
                $store_building = $this->ci->o2o_store_building_model->dump(array('building_id'=>$row['id']));

                if (!$store_building) continue;

                $d['store']     = $store_building['store_id'] ? array('id'=>$store_building['store_id']) : '{}';
                $d['latitude']  = $row['latitude'];
                $d['longitude'] = $row['longitude'];


                // 获取行政区
                $parents = $this->ci->o2o_region_model->getParents($row['pid']);
                foreach ($parents as $p) {
                  $d[$attr[$p['attr']]]['id']   = $p['id'];
                  $d[$attr[$p['attr']]]['name'] = $p['name'];
                }
            }

            $data[] = $d;
        }

        return $data;
    }// done

    /**
     * 定位最近的商区
     *
     * @return void
     * @author
     **/
    public function nearbyBszone($params)
    {
        $area_id   = $params['area_id'];
        $latitude  = $params['latitude'];
        $longitude = $params['longitude'];

        if (!isset($area_id))   return array('code'=>300,'msg'=>'param `area_id` is required');
        if (!isset($latitude))  return array('code'=>300,'msg'=>'param `latitude` is required');
        if (!isset($longitude)) return array('code'=>300,'msg'=>'param `longitude` is required');

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        return $this->ci->bll_o2o->nearbyBszone($area_id,$longitude,$latitude);
    }// done

    /**
     * 获取就近
     *
     * @return void
     * @author
     **/
    public function nearbyBuilding($params)
    {
        $area_id   = $params['area_id'];
        $latitude  = $params['latitude'];
        $longitude = $params['longitude'];

        if (!isset($area_id))   return array('code'=>300,'msg'=>'param `area_id` is required');
        if (!isset($latitude))  return array('code'=>300,'msg'=>'param `latitude` is required');
        if (!isset($longitude)) return array('code'=>300,'msg'=>'param `longitude` is required');

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        return $this->ci->bll_o2o->nearbyBuilding($area_id,$longitude,$latitude);
    } // done

    /**
     * 获取上次下单成功的配送地址
     *
     * @return void
     * @author
     **/
    public function lastAddress($params)
    {
        $connect_id = $params['connect_id'];

        if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);

        if (!$this->ci->login->is_login()) {
            return array('code'=>400,'msg'=>'登录过期，请重新登录');
        }

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        return (object) $this->ci->bll_o2o->lastAddress();
    } // done

    /**
     * 获取历史地址列表
     *
     * @return void
     * @author
     **/
    public function addresslist($params)
    {
        $connect_id = $params['connect_id'];

        if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);

        if (!$this->ci->login->is_login()) {
            return array('code'=>400,'msg'=>'登录过期，请重新登录');
        }

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        if(version_compare($version, '4.1.0') < 0){
            return $this->ci->bll_o2o->addresslist();
        }else{
            return $this->ci->bll_o2o->addresslist_v2();
        }
    } // done

    /**
     * 获取门店商品
     *
     * @return void
     * @author
     **/
    public function storeproducts($params)
    {
        // $building_id = $params['building_id'];
        $store_id    = $params['store_id'];

        if (!$store_id) return array('code'=>300,'msg'=>'所选楼宇暂无门店支持,请重新选择');

        $this->ci->load->model('o2o_store_model');
        $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));

        if (!$store) {
            return array('code'=>300,'msg'=>'所选门店不存在');
        }

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        $open = $this->ci->bll_o2o->_check_store($store);
        if (!$open) {
            return array('code'=>300,'msg'=>$this->ci->bll_o2o->get_error());
        }

        // $h = date('H');

        // if ($h >= $store['cutofftime']) {
        //     return array('code'=>300,'msg'=>'谢谢光临!今日已打烊,明日请早');
        // }
        if(!empty($params['connect_id'])){
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
        }
        return $this->ci->bll_o2o->storeproducts($store_id);
    }

    /**
     * 选择商品后计算价格
     *
     * @return void
     * @author
     **/
    public function total($params)
    {
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id  = $params['region_id'] ? $params['region_id'] : 0;
        $store_id = $params['store_id'] ? $params['store_id'] : 0;


        if (!$params['items']) return array('code'=>300,'msg'=>'请先选择您需要购买的商品');
        if (!$params['region_id']) return array('code'=>300,'msg'=>'param `region_id` is required');
        if($params['version'] < '2.2.0'){
            if (!$params['store_id']) return array('code'=>300,'msg'=>'所选楼宇暂无门店支持,请重新选择');
        }

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('cart',array('session_id'=>$session_id));
        $this->ci->bll_cart->set_province($region_id);

        $params['items'] = json_decode($params['items'],true);
        foreach ($params['items'] as $value) {
            $store_id = $value['store_id']?$value['store_id']:$store_id;
            if(!$store_id){
                return array('code'=>300,'msg'=>'您购买的商品无门店支持,请重新选择');
            }
            $items[] = array(
                'sku_id'     => $value['ppid'],
                'product_id' => $value['pid'],
                'qty'        => $value['qty'],
                'product_no' => $value['pno'],
                'item_type'  => 'o2o',
                'store_id'   => $store_id,
            );
        }

        $this->ci->bll_cart->setCart($items);

        $data = array();

        $data['cart'] = $this->ci->bll_cart->get_cart_info($this->_filtercol);

        $error = $this->ci->bll_cart->get_error();
        if ($error) {
            return array('code'=>300,'msg'=>implode(';',$error));
        }

        $data['cart']['items'] = array_values($data['cart']['items']);

        return $data;
    }

    /**
     * 获取就近门店
     *
     * @return void
     * @author
     **/
    public function nearbystore($params)
    {
        // 暂时不研发
    }

    /**
     * 获取用户常用自提门店
     *
     * @return void
     * @author
     **/
    public function commonstores($params)
    {
        // $region_id  = $params['region_id'];
        // $connect_id = $params['connect_id'];

        // if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');
        // if (!$region_id) return array('code'=>300,'msg'=>'param `region_id` is required');

        // $this->ci->load->bll('o2o');

        // // 获取实际的CITY_ID
        // $this->ci->model('o2o_region_model');
        // $region = $this->ci->o2o_region_model->dump(array('id' => $region_id));

        // if (!$region) return array('code'=>300,'msg'=>'请选择城市');

        // return $this->ci->bll_o2o->commonstores($city_id);

        // 暂不研发
    }

    /**
     * 获取城市的所有门店
     *
     * @return void
     * @author
     **/
    public function citystores($params)
    {
        // $region_id = $params['region_id'];

        // if(!$region_id) return array('code'=>300,'msg'=>'param `region_id` is required');

        // // 获取实际的CITY_ID
        // $this->ci->model('o2o_region_model');
        // $region = $this->ci->o2o_region_model->dump(array('id' => $region_id));

        // if (!$region) return array('code'=>300,'msg'=>'请选择城市');

        // $this->ci->load->bll('o2o');

        // return $this->ci->bll_o2o->citystores($region['area_id']);

        // 暂不研发

    } // done

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
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
        $building_id = $params['building_id'] ? $params['building_id'] : 0;
        $items       = $params['items'] ? @json_decode($params['items'],true) : '';
        $jfmoney     = $params['jfmoney'];
        $card_number = $params['card'];
        $is_first    = $params['is_first']?$params['is_first']:0;
        $payway      = array();
        $order_address = array();

        if ($params['pay_parent_id']) {
            $payway['pay_parent_id'] = $params['pay_parent_id'];
        }
        if ($params['pay_id']) {
            $payway['pay_id'] = $params['pay_id'];
        }

        if ($params['name']) {
            $order_address['name'] = $params['name'];
        }
        if ($params['mobile']) {
            $order_address['mobile'] = $params['mobile'];
        }
        if ($params['address']) {
            $order_address['address'] = $params['address'];
        }

        if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
        if (!$items)        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');
        if($params['version'] < '3.2.0'){
            return array('code'=>300,'msg'=>'您的APP版本过低,请升级APP');
        }
        if($params['version'] < '2.2.0'){
            if (!$building_id)  return array('code'=>300,'msg'=>'param `building_id` is required');
        }

        if($building_id){
            $this->ci->load->model('o2o_region_model');
            $parents = $this->ci->o2o_region_model->getParents($building_id);
            $province=end($parents);
            $region_id = $province['area_id'];
        }

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);

        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }
        $this->ci->load->model('o2o_store_model');

        if($params['version'] < '2.2.0'){
            // 店铺
            $this->ci->load->model('o2o_store_building_model');
            $store_building = $this->ci->o2o_store_building_model->dump(array('building_id'=>$building_id));

            if (!$store_building) return array('code'=>300,'msg'=>'所在楼暂无门店供货');

            $store_id = $store_building['store_id'];

            $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
            if (!$store) {
                return array('code'=>300,'msg' => '暂无此门店');
            }

            $source = $params['source'];
            $version = $params['version'];
            $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
            $open = $this->ci->bll_o2o->_check_store($store);
            if (!$open) {
                return array('code'=>300,'msg'=>$this->ci->bll_o2o->get_error());
            }
        }
        $cart_items = array();
        foreach ($items as $value) {
            if($params['version'] >= '2.2.0'){
                $store_id = $value['store_id'];
                $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
                if (!$store) {
                    return array('code'=>300,'msg' => '您购买的商品无门店支持,请重新选择');
                }

                $source = $params['source'];
                $version = $params['version'];
                $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
                $open = $this->ci->bll_o2o->_check_store($store);
                if (!$open) {
                    return array('code'=>300,'msg'=>$this->ci->bll_o2o->get_error());
                }
            }
            $cart_items[] = array(
                'sku_id'     => $value['ppid'],
                'product_id' => $value['pid'],
                'qty'        => $value['qty'],
                'product_no' => $value['pno'],
                'item_type'  => 'o2o',
                'store_id'   => $store_id,
            );
        }

        $this->ci->load->bll('cart');
        $this->ci->bll_cart->set_province($region_id);
        $this->ci->bll_cart->setCart($cart_items);

        $error = $this->ci->bll_cart->get_error();
        if ($error) {
            return array('code'=>300,'msg'=>implode(';',$error));
        }



        $rs = $this->ci->bll_o2o->orderInit($building_id,$payway,$order_address,$jfmoney,$card_number,$store,$is_first,$params['version']);

        if (!$rs) {
            $code = $this->ci->bll_o2o->get_code();
            $error = $this->ci->bll_o2o->get_error();

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

        self::str($rs);
        return $rs;
    }

    /**
     * 生成订单
     *
     * @return void
     * @author
     **/
    public function createOrder($params)
    {
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        $items       = $params['items'] ? @json_decode($params['items'],true) : '';
        $building_id = $params['building_id'] ? $params['building_id'] : '';
        $order_type = $params['order_type'] ? $params['order_type'] : '';
        $jfmoney     = $params['jfmoney'];
        $card_number = $params['card'];
        $device_code = $params['device_id'];
        $api_version = $params['version'];
        $stime_key = isset($params['stime_key'])?$params['stime_key']:'2hours';
        // $msg         = $params['msg'];
        $payway        = array();
        $order_address = array();
        $paycode       = array();
        $ordermsg      = array();

        if ($params['pay_parent_id']) {
            $payway['pay_parent_id'] = $params['pay_parent_id'];
        }
        if ($params['pay_id']) {
            $payway['pay_id'] = $params['pay_id'];
        }

        if ($params['name']) {
            $order_address['name'] = $params['name'];
        }
        if ($params['mobile']) {
            $order_address['mobile'] = $params['mobile'];
        }
        if ($params['address']) {
            $order_address['address'] = $params['address'];
        }

        if ($params['verification_code']) {
            $paycode['verification_code'] = $params['verification_code'];
            $paycode['ver_code_connect_id'] = $params['ver_code_connect_id'];
        }

        if ($params['msg']) {
            $ordermsg['msg'] = $params['msg'];
        }

        if (!$connect_id)   return array('code'  =>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'   =>300,'msg'=>'param `region_id` is required');
        if (!$items)        return array('code'       =>300,'msg'=>'请先选择您需要购买的商品');
        if (!$building_id)  return array('code' =>300,'msg'=>'param `building_id` is required');
        if (!$order_type)   return array('code'  =>300,'msg'=>'param `order_type` is required');
        if($params['version'] < '3.2.0'){
            return array('code'=>300,'msg'=>'您的APP版本过低,请升级APP');
        }

        $this->ci->load->model('o2o_region_model');
        $parents = $this->ci->o2o_region_model->getParents($building_id);
        $province=end($parents);
        $region_id = $province['area_id'];

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);

        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }

        $this->ci->load->model('o2o_store_model');
        if($params['version'] < '2.2.0'){
            // 店铺
            $this->ci->load->model('o2o_store_building_model');
            $store_building = $this->ci->o2o_store_building_model->dump(array('building_id'=>$building_id));

            if (!$store_building) return array('code'=>300,'msg'=>'所在楼暂无门店供货');

            $store_id = $store_building['store_id'];
            // 门店校验

            $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
            if (!$store) {
                return array('code'=>300,'msg' => '暂无此门店');
            }
            $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
            $open = $this->ci->bll_o2o->_check_store($store);
            if (!$open) {
                return array('code'=>300,'msg'=>$this->ci->bll_o2o->get_error());
            }
        }

        $cart_items = array();
        foreach ($items as $value) {
            if($params['version'] >= '2.2.0'){
                $store_id = $value['store_id'];
                $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
                if (!$store) {
                    return array('code'=>300,'msg' => '您购买的商品无门店支持,请重新选择');
                }

                $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
                $open = $this->ci->bll_o2o->_check_store($store);
                if (!$open) {
                    return array('code'=>300,'msg'=>$this->ci->bll_o2o->get_error());
                }
            }
            $cart_items[] = array(
                'sku_id'     => $value['ppid'],
                'product_id' => $value['pid'],
                'qty'        => $value['qty'],
                'product_no' => $value['pno'],
                'item_type'  => 'o2o',
                'store_id'   => $store_id,
            );
        }

        $this->ci->load->bll('cart');
        $this->ci->bll_cart->set_province($region_id);
        $this->ci->bll_cart->setCart($cart_items);
        $error = $this->ci->bll_cart->get_error();
        if ($error) {
            return array('code'=>300,'msg'=>implode(';',$error));
        }

        // $this->ci->load->bll('o2o');

        $rs = $this->ci->bll_o2o->createOrder($building_id,$order_type,$paycode,$payway,$order_address,$jfmoney,$card_number,$ordermsg,$device_code,$api_version,$stime_key);

        if (!$rs) {
            $code = $this->ci->bll_o2o->get_code();
            $error = $this->ci->bll_o2o->get_error();
            return array('code'=>$code ? $code : 300,'msg'=>$error);
        }
        return $rs;
    }

    /**
     * 订单初始化
     *
     * @return void
     * @author
     **/
    public function orderInit_new($params)
    {
        $connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        $building_id = $params['building_id'] ? $params['building_id'] : 0;
        //$items       = $params['items'] ? @json_decode($params['items'],true) : '';
        $jfmoney     = $params['jfmoney'];
        $card_number = $params['card'];
        $is_first    = $params['is_first']?$params['is_first']:0;
        $store_id    = $params['store_id']?$params['store_id']:0;
        $need_last_address    = $params['need_last_address']?$params['need_last_address']:0;
        $payway      = array();
        $order_address = array();

        if ($params['pay_parent_id']) {
            $payway['pay_parent_id'] = $params['pay_parent_id'];
        }
        if ($params['pay_id']) {
            $payway['pay_id'] = $params['pay_id'];
        }

        if ($params['name']) {
            $order_address['name'] = $params['name'];
        }
        if ($params['mobile']) {
            $order_address['mobile'] = $params['mobile'];
        }
        if ($params['address']) {
            $order_address['address'] = $params['address'];
        }
        // if ($params['addressName']) {
        //     $order_address['address_name'] = $params['addressName'];
        // }
        // if ($params['address']) {
        //     $order_address['pre_address'] = $params['address'];
        // }
        // if ($params['addressRemark']) {
        //     $order_address['pro_address'] = $params['addressRemark'];
        // }
        // if ($params['province']) {
        //     $order_address['province_name'] = $params['province'];
        // }
        // if ($params['city']) {
        //     $order_address['city_name'] = $params['city'];
        // }
        // if ($params['district']) {
        //     $order_address['area_name'] = $params['district'];
        // }

        if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
        //if (!$items)        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');


        if($building_id){
            $this->ci->load->model('o2o_region_model');
            $parents = $this->ci->o2o_region_model->getParents($building_id);
            $province=end($parents);
            $region_id = $province['area_id'];
        }

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);

        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }

        $source = $params['source'];
        $version = $params['version'];

        if(!$store_id){
            $params['lonlat'] = $params['lonlatForO2O']?$params['lonlatForO2O']:$params['lonlat'];
            $this->ci->load->bll('deliver');
            $deliver = $this->ci->bll_deliver->getO2oTmsCode($params);
            $store_id = $deliver['store_id']?$deliver['store_id']:0;
        }
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        $rs = $this->ci->bll_o2o->orderInit_new($building_id,$payway,$order_address,$jfmoney,$card_number,$store_id,$is_first,$params['version'],$region_id, $need_last_address);

        if (!$rs) {
            $code = $this->ci->bll_o2o->get_code();
            if($code == 431 && $params['platform'] == 'IOS'){
                $code = 430;
            }
            $error = $this->ci->bll_o2o->get_error();

            return array('code'=>$code ? $code : 300,'msg' => $error);
        }
        $rs['cart_info']['items'] = $this->format_o2ocart($rs['cart_info']['items']);
        foreach ((array) $rs['cart_info']['items'] as $key => $value) {
            foreach ($value as $k => $v) {
                if (in_array($k,$this->_filtercol)) {
                    unset($rs['cart_info']['items'][$key][$k]);
                }
            }
        }
        //unset($rs['cart_info']['pmt_alert']);

        self::str($rs);
        return $rs;
    }

    /**
     * 生成订单
     *
     * @return void
     * @author
     **/
    public function createOrder_new($params)
    {
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        //$items       = $params['items'] ? @json_decode($params['items'],true) : '';
        $building_id = $params['building_id'] ? $params['building_id'] : 0;
        $order_type = $params['order_type'] ? $params['order_type'] : '';
        $jfmoney     = $params['jfmoney'];
        $card_number = $params['card'];
        $device_code = $params['device_id'];
        $api_version = $params['version'];
        $stime_key = isset($params['stime_key'])?$params['stime_key']:'';
        $store_id = $params['store_id']?$params['store_id']:0;
        // $msg         = $params['msg'];
        $payway        = array();
        $order_address = array();
        $paycode       = array();
        $ordermsg      = array();

        if ($params['pay_parent_id']) {
            $payway['pay_parent_id'] = $params['pay_parent_id'];
        }
        if ($params['pay_id']) {
            $payway['pay_id'] = $params['pay_id'];
        }

        if ($params['name']) {
            $order_address['name'] = $params['name'];
        }
        if ($params['mobile']) {
            $order_address['mobile'] = $params['mobile'];
        }
        if ($params['address']) {
            $order_address['address'] = $params['address'];
        }

        if ($params['addressName']) {
            $order_address['address_name'] = $params['addressName'];
        }
        if ($params['address']) {
            $order_address['pre_address'] = $params['address'];
        }
        if ($params['addressRemark']) {
            $order_address['pro_address'] = $params['addressRemark'];
        }
        if ($params['addressId']) {
            $order_address['address_id'] = $params['addressId'];
        }


        $params['lonlat'] = $params['lonlatForO2O']?$params['lonlatForO2O']:$params['lonlat'];
        $order_address['lonlat'] = $params['lonlat']?$params['lonlat']:'';
        if ($params['province']) {
            $order_address['province_name'] = $params['province'];
        }
        if ($params['city']) {
            $order_address['city_name'] = $params['city'];
        }
        if ($params['district']) {
            $order_address['area_name'] = $params['district'];
        }

        if ($params['verification_code']) {
            $paycode['verification_code'] = $params['verification_code'];
            $paycode['ver_code_connect_id'] = $params['ver_code_connect_id'];
        }

        if ($params['msg']) {
            $ordermsg['msg'] = $params['msg'];
        }

        if (!$connect_id)   return array('code'  =>300,'msg'=>'param `connect_id` is required');
        if (!$region_id)    return array('code'   =>300,'msg'=>'param `region_id` is required');
        //if (!$items)        return array('code'       =>300,'msg'=>'请先选择您需要购买的商品');
        if(version_compare($params['version'], '4.1.0') < 0){
            if (!$building_id)  return array('code' =>300,'msg'=>'请重新选择收货楼宇');
            //if (!$building_id)  return array('code' =>300,'msg'=>'param `building_id` is required');
        }
        if (!$order_type)   return array('code'  =>300,'msg'=>'param `order_type` is required');
        if($params['version'] < '2.2.0'){
            return array('code'=>300,'msg'=>'您的APP版本过低,请升级APP');
        }
        if($building_id){
            $this->ci->load->model('o2o_region_model');
            $parents = $this->ci->o2o_region_model->getParents($building_id);
            $province=end($parents);
            $region_id = $province['area_id'];
        }
        
        if(version_compare($params['version'], '4.1.0') >= 0){
            $this->ci->load->bll('deliver');
            $deliver = $this->ci->bll_deliver->getO2oTmsCode($params);
            $m_store_id = $deliver['store_id']?$deliver['store_id']:0;
            if($store_id){
                if($m_store_id != $store_id){
                    return array('code' => 300,'msg'=>'您购买的商品无法配送至您的地址');
                }
            }else{
                $store_id = $m_store_id;
            }
        }
        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);

        if (!$this->ci->login->is_login()) {
            return array('code' => 400,'msg'=>'登录过期，请重新登录');
        }

        $this->ci->load->model('o2o_store_model');

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version, 'region_id' => $params['region_id'], 'platform' => $params['platform'], 'version_number' => $params['version_number'], 'device_id' => $params['device_id'], 'android_id' => $params['android_id'])); //luke_lu: 扩展参数，用于收集手机设备信息与品友对接

        $rs = $this->ci->bll_o2o->createOrder_new($building_id,$order_type,$paycode,$payway,$order_address,$jfmoney,$card_number,$ordermsg,$device_code,$api_version,$stime_key,$store_id);

        if (!$rs) {
            $code = $this->ci->bll_o2o->get_code();
            if($code == 431 && $params['platform'] == 'IOS'){
                $code = 430;
            }
            $error = $this->ci->bll_o2o->get_error();
            return array('code'=>$code ? $code : 300,'msg'=>$error);
        }
        return $rs;
    }

    /**
     * 门店兑换券
     *
     * @return void
     * @author
     **/
    public function exchgcoupon($params)
    {
        return array('code'=>300,'msg'=>'兑换券无效');
    }

    /**
     * 取消兑换券
     *
     * @return void
     * @author
     **/
    public function cancelcoupon($params)
    {
        return array('code' => 200,'msg'=>'取消成功');

        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        $coupon = $params['coupon'] ? $params['coupon'] : '';

        if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$coupon) return array('code'=>300,'msg'=>'param `coupon` is required');

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);

        if (!$this->ci->login->is_login()) {
            return array('code'=>400,'msg'=>'登录过期，请重新登录');
        }

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        return $this->ci->bll_o2o->cancelcoupon($coupon);
    }

    /**
     * 获取支付方式
     *
     * @return void
     * @author
     **/
    public function getPay($params)
    {
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;

        if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!$region_id) return array('code'=>300,'msg'=>'param `region_id` is required');

        if(!empty($params['connect_id'])){
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
        }

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        return $this->ci->bll_o2o->getPay($region_id);
    }

    //搜索楼宇
    public function searchBuilding($params)
    {
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        $building_name = $params['building_name'];
        $region_id   = $params['region_id'] ? $params['region_id'] : 0;
        if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!isset($building_name))   return array('code'=>300,'msg'=>'param `building_name` is required');

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        return $this->ci->bll_o2o->searchBuilding($building_name,$region_id);
    }

    public function getStroeInfo($params){
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        $store_id = $params['store_id'];
        if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');
        if (!isset($store_id))   return array('code'=>300,'msg'=>'param `store_id` is required');

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));

        return $this->ci->bll_o2o->getStroeInfo($store_id);
    }

    public function nearbyBuilding_new($params)
    {
        $type   = $params['type'];
        $latitude  = $params['latitude'];
        $longitude = $params['longitude'];

        if (!isset($type))   return array('code'=>300,'msg'=>'param `type` is required');
        if (!isset($latitude))  return array('code'=>300,'msg'=>'param `latitude` is required');
        if (!isset($longitude)) return array('code'=>300,'msg'=>'param `longitude` is required');

        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        //return $this->ci->bll_o2o->nearbyBuilding_new($longitude,$latitude,$type);
        return $this->ci->bll_o2o->nearbyBuilding_geohash($longitude,$latitude,$type);  //新算法
    } // done

    public function getStoreList($params){
        $building_id = $params['building_id'];
        if (!isset($building_id))   return array('code'=>300,'msg'=>'param `building_id` is required');
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        if(!empty($params['connect_id'])){
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
        }
        return $this->ci->bll_o2o->getStoreList($building_id);
    }

    public function getStoreGoodsInfo($params){
        $store_id = $params['store_id'];
        if (!isset($store_id))   return array('code'=>300,'msg'=>'param `store_id` is required');
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        return $this->ci->bll_o2o->getStoreGoodsInfo($store_id);
    }

    public function getStoreOtherGoods($params){
        $nums = $params['nums']?$params['nums']:6;
        $store_id = $params['store_id'];
        $product_id = $params['product_id'];
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        if (!isset($store_id))   return array('code'=>300,'msg'=>'param `store_id` is required');
        if (!isset($product_id))   return array('code'=>300,'msg'=>'param `store_id` is required');
        //if (!$connect_id) return array('code'=>300,'msg'=>'param `connect_id` is required');
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        return $this->ci->bll_o2o->getStoreOtherGoods($store_id,$product_id,$nums);
    }


    function orderList($params){
      return array();
      $before  = isset($params['before']) ? $params['before'] : time();
      $after  = isset($params['after']) ? $params['after'] : time();
      $order_status = isset($params['order_status']) ? $params['order_status'] : '0';
      $page = isset($params['page']) ? $params['page'] : '1';
      $limit = isset($params['limit']) ? $params['limit'] : '10';
      $offset = ($page-1)*$limit;
      $where = array(
        'o.order_status'=>'1',
        'o.pay_status'=>'1',
        'o.order_type'=>'3',
        'o.last_modify >='=>$after,
        'o.last_modify <'=>$before,
      );

      switch ($order_status) {
          case '0':
              $where['o.operation_id']=0;
              break;
          case '1':
              $where['o.operation_id']=5;
              break;
          case '2':
              $where['o.operation_id']=9;
              break;
          default:
              // $where['o.operation_id']=0;
              break;
      }

      $this->ci->load->model('o2o_store_model');

      $result = $this->ci->o2o_store_model->getOrderList($where,$offset,$limit);
      $total_count = $this->ci->o2o_store_model->getOrderListCount($where);
      $return_result = array(
        'before'=>date("Y-m-d H:i:s",$before),
        'after'=>date("Y-m-d H:i:s",$after),
        'page'=>$page,
        'total_count'=>$total_count,
        'orders'=>$result
      );
      return $return_result;
    }

    public function getHomePageInfo($params){
        $latitude  = $params['latitude']?$params['latitude']:0;
        $longitude = $params['longitude']?$params['longitude']:0;
        $building_id = isset($params['building_id']) ? $params['building_id'] : 0;
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id  = $params['region_id'] ? $params['region_id'] : 0;
        $store_id = $params['store_id']?$params['store_id']:0;
        if(!$store_id){
            $params['lonlat'] = $params['lonlatForO2O']?$params['lonlatForO2O']:$params['lonlat'];
            $this->ci->load->bll('deliver');
            $deliver = $this->ci->bll_deliver->getO2oTmsCode($params);
            $store_id = $deliver['store_id']?$deliver['store_id']:0;
        }
        $version = $params['version'];
        if($params['version'] < '3.2.0'){
            return array('code'=>300,'msg'=>'您的APP版本过低,请升级APP');
        }
        if(!empty($params['connect_id'])){
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
        }
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        if(version_compare($version, '4.1.0') < 0){
            return $this->ci->bll_o2o->getHomePageInfo($building_id,$latitude,$longitude,$version,$region_id);
        }else{
            return $this->ci->bll_o2o->getHomePageInfo_v2($region_id,$store_id);
        }
    }

    public function getHomePageDetail($params)
    {
        $latitude = $params['latitude'];
        $longitude = $params['longitude'];
        $building_id = isset($params['building_id']) ? $params['building_id'] : 0;
        $region_id = $params['region_id'] ? $params['region_id'] : 0;
        $version = $params['version'];
        $store_id = $params['store_id']?$params['store_id']:0;
        if(!$store_id){
            $params['lonlat'] = $params['lonlatForO2O']?$params['lonlatForO2O']:$params['lonlat'];
            $this->ci->load->bll('deliver');
            $deliver = $this->ci->bll_deliver->getO2oTmsCode($params);
            $store_id = $deliver['store_id']?$deliver['store_id']:0;
        }
        if (!empty($params['connect_id'])) {
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
        }

        $this->ci->load->bll('o2o');
        if(version_compare($version, '4.1.0') < 0){
            return $this->ci->bll_o2o->getHomePageDetail($building_id, $latitude, $longitude, $version, $region_id);
        }else{
            return $this->ci->bll_o2o->getHomePageDetail_v2($region_id,$store_id, $params['lonlatForO2O']);
        }
    }

    public function o2oProductList($params){
        $latitude  = $params['latitude'];
        $longitude = $params['longitude'];
        $building_id = $params['building_id'];
        $store_id = $params['store_id']?$params['store_id']:0;
        if(!$store_id){
            $params['lonlat'] = $params['lonlatForO2O']?$params['lonlatForO2O']:$params['lonlat'];
            $this->ci->load->bll('deliver');
            $deliver = $this->ci->bll_deliver->getO2oTmsCode($params);
            $store_id = $deliver['store_id']?$deliver['store_id']:0;
        }
        $product_type = isset($params['product_type']) ? $params['product_type']:0;
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        if(version_compare($version, '4.1.0') < 0){
            return $this->ci->bll_o2o->o2oProductList($building_id,$latitude,$longitude,$store_id,$product_type);
        }else{
            return $this->ci->bll_o2o->o2oProductList_v2($store_id,$product_type);
        }
    }

    public function productInfo($params){
        $id = $params['id'];
        $store_id = $params['store_id'];
        if (!$id)   return array('code'=>300,'msg'=>'param `id` is required');
        if (!$store_id)   return array('code'=>300,'msg'=>'param `store_id` is required');

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
              $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $store_id;
            $result = $this->ci->memcached->get($mem_key);
            if(!isset($result['code'])){
                if ($result) {
                    return $result;
                }
            }
        }
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        $result = $this->ci->bll_o2o->productInfo($id,$store_id);
        if(isset($result['code'])) return $result;
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $store_id;
            $this->ci->memcached->set($mem_key, $result, 1800);
        }
        $this->ci->load->model('o2o_store_model');
        $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
        $is_8324_tuan = false;
        $o2oTuan_productids = array(8324, 8647, 8613, 8614, 8615);
        if(in_array($id, $o2oTuan_productids)){
            $is_8324_tuan = true;
        }
        $send_time = $this->ci->bll_o2o->get_o2o_send_time($store,$is_8324_tuan);
        if($send_time['shtime'] == date('Ymd')){
            $send_desc = $send_time['stime'];
        }else{
            $send_desc = date('Y年m月d日',strtotime($send_time['shtime'])).$send_time['stime'];
        }
        $result['product']['send_desc'] = $send_desc;

        //高清
        if(count($result['photo']) >0)
        {
            foreach($result['photo'] as $key=>$val)
            {
                $result['photo'][$key]['photo'] = $val['big_photo'];
            }
        }

        return $result;
    }

    public function pageListProducts($params) {
        if(!$params['page_type']) return array('code' => '500', 'msg' => 'page type can not be null');
        if(!$params['target_id']) return array('code' => '500', 'msg' => 'target id can not be null');
        if(!$params['store_id']) return array('code' => '500', 'msg' => 'store id can not be null');

        $page_type = $params['page_type'];
        $target_id = $params['target_id'];
        $store_id = $params['store_id'];
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        $result = $this->ci->bll_o2o->pageListProducts($page_type,$target_id,$store_id);
        return $result;
    }

    public function getGoodsDetailBanner($params){
        $store_id = $params['store_id'];
        $banner_id = $params['banner_id'];
        $nums = $params['nums']?$params['nums']:1;
        $connect_id  = isset($params['connect_id']) ? $params['connect_id'] : '';
        if (!isset($store_id))   return array('code'=>300,'msg'=>'param `store_id` is required');
        $source = $params['source'];
        $version = $params['version'];
        $this->ci->load->bll('o2o',array('source'=>$source,'version'=>$version));
        return $this->ci->bll_o2o->getGoodsDetailBanner($store_id,$banner_id,$nums);
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
}
