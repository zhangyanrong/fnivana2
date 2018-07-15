<?php
namespace bll\app;

class B2ccart {

    // 蔡昀辰2016优化
    public function __construct($params = []) {

        $this->ci = &get_instance();
        
        // 登陆
        if( $params['connect_id'] )
            $this->ci->load->library('login', ['session_id'=>$params['connect_id']]);

        // 载入b2ccart
        $this->ci->load->bll('b2ccart');

        // 设置省份
        if( $params['region_id'] ) 
            $this->ci->bll_b2ccart->set_province($params['region_id']);

        // 载入临时购物车
        if (!$params['connect_id'] && $carttmp = json_decode($params['carttmp'], true))
            $this->ci->bll_b2ccart->setCart($carttmp);            
        
        // 商品字段过滤
        // 蔡昀辰 2016-5-26 暂时保留
        $this->_filtercol = [
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
        ];
    }

    // 查询
    public function get($params) {

        // 载入临时购物车
        if (!$params['connect_id'] && $carttmp = @json_decode($params['carttmp'], true))
            $this->ci->bll_b2ccart->setCart($carttmp);

        // 获取商品详情以及活动规则
        $cart = $this->ci->bll_b2ccart->get_cart_info($this->_filtercol);
        
        // 整理 hash->array
        if ($cart['items']) 
            $cart['items'] = array_values($cart['items']);
        
        // 返回
        $resp = [         
            'cart'      => (object) $cart,
            'cartcount' => $this->ci->bll_b2ccart->get_cart_count()            
        ];

        // 未登陆的话返回购物车内容
        if (!$params['connect_id'])
            $resp['cart_items'] = (object)$this->ci->bll_b2ccart->get_cart_items();

        return $resp;
    }

    // 添加
    public function add($params) {

        if (!$params['items']) 
            return ['code'=>300,'msg'=>'请选择商品后添加'];

        if (!$params['connect_id'] && $carttmp = @json_decode($params['carttmp'], true)) 
            $this->ci->bll_b2ccart->setCart($carttmp);
        

        $params['items'] = json_decode($params['items'], true);

        foreach ($params['items'] as $value) {
            $item = array(
                'sku_id'     => $value['ppid'],
                'product_id' => $value['pid'],
                'qty'        => $value['qty'],
                'product_no' => $value['pno'],
                'item_type'  => $value['type'],
            );

            if (isset($value['pmt_id'])) 
                $item['pmt_id'] = $value['pmt_id'];
            if (isset($value['user_gift_id'])) 
                $item['user_gift_id'] = $value['user_gift_id'];
            if (isset($value['gift_send_id'])) 
                $item['gift_send_id'] = $value['gift_send_id'];
            if (isset($value['gift_active_type'])) 
                $item['gift_active_type'] = $value['gift_active_type'];

            $item_type = isset($value['type']) ? $value['type'] : 'normal';

            $rs = $this->ci->bll_b2ccart->addCart($item,$item_type);

            if (!$rs){
                $error = $this->ci->bll_b2ccart->get_error();
                if(count($error)==1 && in_array('缺少必要参数', $error)){

                }else{
                    return array('code' => 300, 'msg' => implode('、',$error));
                }

            }
            
        }
        
        // 返回
        $resp = [
            'cartcount' => $this->ci->bll_b2ccart->get_cart_count(),
        ];

        if (!$params['connect_id'])
            $resp['cart_items'] = (object)$this->ci->bll_b2ccart->get_cart_items();

        return $resp;
    }

    // 修改(数量)
    public function update($params) {

        if (!$params['item']) 
            return ['code'=>300,'msg'=>'请选择商品后添加'];
            
        if (!$params['connect_id'] && $carttmp = @json_decode($params['carttmp'], true))
            $this->ci->bll_b2ccart->setCart($carttmp);

        $params['item'] = json_decode($params['item'],true);

        $item_type = isset($params['item']['item_type']) ? $params['item']['item_type'] : 'normal';
        $item = [
            // 'sku_id'     => $params['item']['ppid'],
            // 'product_id' => $params['item']['pid'],
            'qty'        => $params['item']['qty'],
            // 'item_type'  => $item_type,
            'ik' => $params['item']['ik'],
        ];

        $rs = $this->ci->bll_b2ccart->updateCart($item,$item_type);
        
        if(!$rs) {
            $error = $this->ci->bll_b2ccart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));            
        }

        $cart = $this->ci->bll_b2ccart->get_cart_info($this->_filtercol);

        if ($cart['items']) 
            $cart['items'] = array_values($cart['items']);

        // 返回
        $resp = [
            'cart'      => (object) $cart,
            'cartcount' => $this->ci->bll_b2ccart->get_cart_count()
        ];
        
        // 未登陆的话返回购物车内容
        if (!$params['connect_id'])
            $resp['cart_items'] = (object)$this->ci->bll_b2ccart->get_cart_items();

        return $resp;

    }


    // 删除(单个或者批量)
    public function remove($params) {

        // 入参
        if (!$params['item'] && !$params['items']) 
            return ['code'=>300,'msg'=>'请选择商品后添加'];

        if($params['item'])
            $items[] = json_decode($params['item'], true);
        if($params['items'])    
            $items = json_decode($params['items'], true);

        // 删除    
        foreach($items as $item) {
            $ik        = $item['ik'];
            $item_type = $item['type'] ? $item['type'] : 'normal';     
            $rs = $this->ci->bll_b2ccart->removeCart($ik, $item_type);     
            if(!$rs)
                return ['code' => 300, 'msg' => '删除失败'];              
        }
        
        // 返回
        $cart =  $this->ci->bll_b2ccart->get_cart_info($this->_filtercol);

        if ($cart['items'])
            $cart['items'] =  array_values($cart['items']);
        
        $resp = [
            'cart'      => (object) $cart,
            'cartcount' => $this->ci->bll_b2ccart->get_cart_count()
        ];

        if (!$params['connect_id'])
            $resp['cart_items'] = (object)$this->ci->bll_b2ccart->get_cart_items();

        return $resp;

    }


    // 清空
    public function clear($params) {
        
        $this->ci->bll_b2ccart->emptyCart();

        $cart = $this->ci->bll_b2ccart->get_cart_info($this->_filtercol);
        
        
        // if ($cart['items'])
        //     $cart['items'] = array_values($cart['items']);
        
        // 返回
        $resp = [
            'cart'      => (object) $cart,
            'cartcount' => $this->ci->bll_b2ccart->get_cart_count()
        ];

        return $resp;
    }



    //  获取单个优惠详情
    public function selpmt($params) {
        if (!$params['connect_id'] && $carttmp = @json_decode($params['carttmp'],true)) 
            $this->ci->bll_b2ccart->setCart($carttmp);
       
        $resp = $this->ci->bll_b2ccart->selpmt($params['pmt_id'], $params['pmt_type']);
        return $resp;
    }
}
