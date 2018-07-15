<?php
namespace bll\app;

class Cart_v1 {

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

        // 设置版本
        if( $params['version'] )
            $this->ci->bll_b2ccart->setVersion($params['version']);

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
    // connect_id
    // device_id
    // region_id
    // source
    // 返回全量购物车
    public function get($params) {

        // 未登录载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        // 已登录尝试合并购物车
        if ($params['connect_id'] && $params['device_id']) {

            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);

            if($tmp_cart) {
                $params['items'] = json_encode($tmp_cart);
                $this->add($params);

                $this->ci->cart_model->save($params['device_id'], []);
            }

        }

        $items = $this->ci->bll_b2ccart->get_cart_items();
        $cart  = $this->ci->bll_b2ccart->getContents($this->_filtercol);

        // 返回
        $resp = [
            'code'         => 200,
            'msg'          => "购物车获取成功",
            'login'        => $this->ci->login->is_login(),
            'user'         => $this->ci->login->get_user()['username'],
            'items'        => array_values($items),
            'content'      => array_values($cart['items']), //todo: remove
            'contents'     => array_values($cart['items']),
            'pmt_ids'      => $cart['pmt_ids'],
            'pmt_detail'   => $cart['pmt_detail'],
            'pmt_alert'    => $cart['pmt_alert'],
            'pmt_total'    => $cart['pmt_total'],
            'items_count'  => $this->ci->bll_b2ccart->get_cart_count(),
            'select_count' => $this->ci->bll_b2ccart->getSelected(),
            'weight'       => $cart['pro_weight'],
            'cost'         => $cart['goods_amount'],
            'total'        => $cart['total_amount'],
        ];

        // content清除失效商品
        // todo: remove
        foreach($resp['content'] as $key=>$content) {
            if(!$content['valid'])
                unset($resp['content'][$key]);
        }
        $resp['content'] = array_values($resp['content']);

        return $resp;
    }

    // 添加
    // connect_id
    // device_id
    // items
    // [
    //     {
    //         "sku_id":"3221" //规格商品ID
    //         "product_id":"4840" //主商品ID
    //         "product_no":"20149460" //货号
    //         "product_name":"火龙果" //商品名称
    //         "qty":"1" //数量
    //         "type":"normal" //普通商品类型
    //     },
    //     {
    //         "sku_id":"3221" //规格商品ID
    //         "product_id":"4840" //主商品ID
    //         "qty":"1" //数量
    //         "product_no":"20149460" //货号
    //         "product_name":"火龙果" //商品名称
    //         "type":"exch" //换购商品类型
    //         "pmt_id":"30" //换购的优惠ID
    //     },
    //     {
    //         "sku_id":"3221" //规格商品ID
    //         "product_id":"4840" //主商品ID
    //         "qty":"1" //数量
    //         "product_no":"20149460" //货号
    //         "product_name":"火龙果" //商品名称
    //         "gift_send_id":28,
    //         "gift_active_type":1,
    //         "type":"user_gift" //赠品领取类型,
    //     }
    // ]
    // 返回简略购物车
    public function add($params) {

        if (!$params['items'])
            return ['code'=>300,'msg'=>'请选择商品后添加'];

        // 载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        $params['items'] = json_decode($params['items'], true);

        foreach ($params['items'] as $item) {

            $new_item = $item;

            if (isset($item['pmt_id']))
                $new_item['pmt_id'] = $item['pmt_id'];
            if (isset($item['user_gift_id']))
                $new_item['user_gift_id'] = $item['user_gift_id'];
            if (isset($item['gift_send_id']))
                $new_item['gift_send_id'] = $item['gift_send_id'];
            if (isset($item['gift_active_type']))
                $new_item['gift_active_type'] = $item['gift_active_type'];

            $item_type = isset($item['item_type']) ? $item['item_type'] : 'normal';

            $rs = $this->ci->bll_b2ccart->addCart($new_item, $item_type);

            if (!$rs) {
                $error = $this->ci->bll_b2ccart->get_error();
                if( version_compare($params['version'], '3.9.0', '>=') )
                    if(in_array('请先登录', $error))
                        return ['code' => 301, 'msg' => implode('、', $error)];
                return ['code' => 300, 'msg' => implode('、', $error)];
            }

        }

        // 获取items
        $items = $this->ci->bll_b2ccart->get_cart_items();

        // 保存临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $this->ci->cart_model->save($params['device_id'], $items);
        }

        // 返回
        $resp = [
            'code'         => 200,
            'msg'          => '购物车添加成功',
            'login'        => $this->ci->login->is_login(),
            'items'        => array_values($items),
            'items_count'  => $this->ci->bll_b2ccart->get_cart_count(),
            'select_count' => $this->ci->bll_b2ccart->getSelected(),
        ];

        return $resp;
    }

    // 修改(数量)
    // connect_id
    // device_id
    // region_id
    // source
    // item {sku_id,item_type,qty}
    // 返回全量购物车
    public function update($params) {

        if (!$params['item'])
            return ['code'=>300,'msg'=>'请选择商品后添加'];

        // 载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        $params['item'] = json_decode($params['item'],true);

        $item_type = isset($params['item']['item_type']) ? $params['item']['item_type'] : 'normal';
        $item = [
            'qty' => $params['item']['qty'],
            'ik'  => $params['item']['item_type'] . '_' . $params['item']['sku_id'],
        ];

        $rs = $this->ci->bll_b2ccart->updateCart($item, $item_type);

        if(!$rs) {
            $error = $this->ci->bll_b2ccart->get_error();
            return array('code' => 300, 'msg' => implode('、',$error));
        }

        // 获取items
        $items = $this->ci->bll_b2ccart->get_cart_items();

        // 保存临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $this->ci->cart_model->save($params['device_id'], $items);
        }

        // 返回
        $resp = $this->get();
        $resp['msg'] = '购物车更新成功';
        return $resp;

    }


    // 删除(批量)
    // connect_id
    // device_id
    // region_id
    // source
    // clear "true"/"false" 清空购物车
    // items [{sku_id,item_type}]
    // 返回全量购物车
    public function remove($params) {

        // 清空购物车
        if($params['clear'] == "true" || $params['clear'] == '1')
            return $this->clear($params);

        // 入参
        if (!$params['items'])
            return ['code'=>300,'msg'=>'请选择商品后删除'];

        // 载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        if($params['items'])
            $items = json_decode($params['items'], true);

        // 删除
        foreach($items as $item) {
            if( $item['item_type'] == 'user_gift' )
                $ik = $item['item_type'] .'_'.$item['gift_send_id'].'_'. $item['sku_id'];
            elseif ( $item['item_type'] == 'coupon_gift' )
                $ik = $item['item_type'] .'_'.$item['gift_send_id'].'_'. $item['sku_id'];
            else
                $ik = $item['item_type'] .'_'. $item['sku_id'];
            $item_type = $item['item_type'] ? $item['item_type'] : 'normal';
            $rs = $this->ci->bll_b2ccart->removeCart($ik, $item_type);
            if(!$rs)
                return ['code' => 300, 'msg' => '删除失败'];
        }

        // 获取items
        $items = $this->ci->bll_b2ccart->get_cart_items();

        // 保存临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $this->ci->cart_model->save($params['device_id'], $items);
        }

        // 返回
        $resp = $this->get();
        $resp['msg'] = '购物车删除成功';
        return $resp;

    }

    // 清空
    // connect_id
    // device_id
    // 返回简略购物车
    public function clear($params) {

        // 载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        $this->ci->bll_b2ccart->emptyCart();

        // 保存临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $this->ci->cart_model->save($params['device_id'], []);
        }

        // 返回
        $resp = [
            'code'         => 200,
            'msg'          => '购物车清空成功',
            'login'        => $this->ci->login->is_login(),
            'items_count'  => $this->ci->bll_b2ccart->get_cart_count(),
            'select_count' => $this->ci->bll_b2ccart->getSelected(),
        ];

        return $resp;
    }

    // 获取购物车总数
    // connect_id
    // device_id
    // 返回简略购物车
    public function count($params) {

        // 载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        // $items = $this->ci->bll_b2ccart->get_cart_items();
        // die(json_encode($items));

        // 返回
        $resp = [
            'code'         => 200,
            'msg'          => '购物车统计成功',
            'login'        => $this->ci->login->is_login(),
            'items_count'  => $this->ci->bll_b2ccart->get_cart_count(),
            'select_count' => $this->ci->bll_b2ccart->getSelected(),
        ];

        return $resp;

    }

    // 勾选
    // connect_id
    // device_id
    // region_id
    // source
    // items: [sku_id, ...]
    // 返回全量购物车
    public function select($params) {

        // 入参
        if (!$params['items'])
            return ['code'=>300,'msg'=>'请选择商品后勾选'];

        // 载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        if($params['items'])
            $sku_ids = json_decode($params['items'], true);

        $items = $this->ci->bll_b2ccart->get_cart_items();

        // 全部不勾选
        foreach($items as $item) {
            $this->ci->bll_b2ccart->unselect($item['sku_id']);
        }

        // 勾选
        foreach($sku_ids as $sku_id) {
            $this->ci->bll_b2ccart->select($sku_id);
        }

        $this->ci->bll_b2ccart->saveCart();

        // 获取items
        $items = $this->ci->bll_b2ccart->get_cart_items();

        // 保存临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $this->ci->cart_model->save($params['device_id'], $items);
        }

        // 返回
        $resp = $this->get();
        $resp['msg'] = '购物车勾选成功';
        return $resp;

    }

    // 比较本地购物车和线上购物车
    // connect_id
    // device_id
    // region_id
    // source
    // items
    public function compare($params) {

        // 未登录载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        // 已登录尝试合并购物车
        if ($params['connect_id'] && $params['device_id']) {

            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);

            if($tmp_cart) {
                $params['items'] = json_encode($tmp_cart);
                $this->add($params);

                $this->ci->cart_model->save($params['device_id'], []);
            }

        }

        $same = true;

        $input_items = json_decode($params['items'], true);
        $items = $this->ci->bll_b2ccart->get_cart_items();
 
        foreach($items as $item) {
            if( $item != array_shift($input_items) )
                $same = false;
        }

        $input_items = json_decode($params['items'], true);

        if( count($input_items) != count($items) )
            $same = false;

        if( count($input_items) == 0 && count($items) == 0 )
            $same = true;

        // 返回
        $resp = [
            'code'         => 200,
            'msg'          => '购物车比较成功成功',
            'login'        => $this->ci->login->is_login(),
            'same'         => $same,
            'input_items'  => $input_items,
            'items'        => $items,
            'items_count'  => $this->ci->bll_b2ccart->get_cart_count(),
            'select_count' => $this->ci->bll_b2ccart->getSelected(),
        ];

        return $resp;
    }

    // 获取优惠详情
    // connect_id
    // device_id
    // region_id
    // source
    // pmt_id 可选
    // pmt_type
    // 返回去凑单/换购列表
    public function pmt($params) {

        // 入参
        // if (!$params['pmt_id'])
        //     return ['code'=>300,'msg'=>'请选择优惠'];
        // if (!$params['pmt_type'])
        //     return ['code'=>300,'msg'=>'请选择优惠类型'];

        // 载入临时购物车
        if (!$params['connect_id'] && $params['device_id']) {
            $this->ci->load->model('cart_model');
            $tmp_cart = $this->ci->cart_model->get($params['device_id']);
            $this->ci->bll_b2ccart->setCart($tmp_cart);
        }

        $resp = $this->ci->bll_b2ccart->selpmt($params['pmt_id'], $params['pmt_type']);
        return $resp;
    }

    // 关注/收藏(批量)
    // connect_id
    // device_id
    // product_ids [product_id, ...]
    // 返回关注成功
    public function mark($params) {

        if(!$params['connect_id'])
            return ['code'=>300, 'msg'=>"请先登录"];

        if( !$this->ci->login->is_login() )
            return ['code'=>300, 'msg'=>"登录失败"];

        $product_ids = json_decode($params['product_ids']);

        if(!$product_ids)
            return ['code'=>300, 'msg'=>"请先选择商品"];

        $this->ci->load->model('product_model');

        foreach($product_ids as $product_id) {
            $result = $this->ci->product_model->select_user_mark('id', ['uid' => $this->ci->login->get_uid(), 'product_id' => $product_id]);

            $insert_data = [
                'uid'        => $this->ci->login->get_uid(),
                'product_id' => $product_id,
                'mark_time'  => date("Y-m-d H:i:s"),
            ];

            if(empty($result))
                $this->ci->product_model->add_user_mark($insert_data);
        }

        // 返回
        $resp = [
            'code'  => 200,
            'login' => $this->ci->login->is_login(),
            'msg'   => "购物车商品关注成功",
        ];

        return $resp;

    }

}
