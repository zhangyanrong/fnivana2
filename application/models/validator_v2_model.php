<?php

class Validator_v2_model extends CI_model {

    var $methods = ['checkBasic', 'checkOnline', 'checkStock', 'checkFirstLimit', 'checkStore', 'checkQtyLimit', 'checkUserGift', 'checkAddExchange'];

    function __construct() {
        $this->load->model('cart_v2_model');
    }

    function check($product, $cart) {

        // run methods
        foreach($this->methods as $method) {
            $error = $this->$method($product, $cart);

            if ( $error !== false )
                return $error;
        }

        return false;

    }

    // 商品入参检查 主要是addItem的时候
    // @Decrepted
    private function checkBasic($product, $cart) {

        if( !$product->product_id )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '缺少商品id',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请重新添加商品',
            ]);

        if( !$product->store_id )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '缺少门店参数',
                'reason'       => '缺少store_id',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请重新添加商品',
            ]);

        if( !in_array($product->type, ['normal', 'exchange', 'gift', 'user_gift']) )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '商品类型错误',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请重新添加商品',
            ]);

        if( $product->qty < 1 )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '商品数量小于1',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请重新添加商品',
            ]);

        if( !$product->sku_id )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '商品规格错误',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请重新添加商品',
            ]);

        return false;

    }

    // 检查上下架
    // @Migrated
    private function checkOnline($product, $cart) {
        $online = "is_{$cart->source}_online";

        // 购物车只有正常商品判断上下架 by 霍霖 2017/1/25
        if($product->type == 'normal' && !$product->$online)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '商品已下架',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请购买其它商品',
            ]);

        return false;
    }

    // 检查库存
    // @Migrated
    private function checkStock($product, $cart) {

        $this->load->model('b2o_store_product_model');
        $stock = $this->b2o_store_product_model->get_product_stock($product->product_id, $product->store_id);

        if(!$stock)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '该商品已缺货',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请购买其它商品',
            ]);

        if($product->qty > $stock)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '您购买的数量超出目前库存',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请修改后再购买',
            ]);

        return false;

    }

    // 检查购物车添加限制
    // @Migrated
    private function checkQtyLimit($product, $cart) {

        if(!$product->qty_limit)
            return false;

        if($product->qty > $product->qty_limit)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '您购买的数量超出最大购买数量',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请修改后再购买',
            ]);

        return false;

    }

    // 首够限制
    // @Migrated
    private function checkFirstLimit($product, $cart) {

        // 不是首购
        if(!$product->first_limit)
            return false;

        $this->db->from('order');
        $this->db->where('uid', $cart->user->id);
        $this->db->where('order_status', '1');
        $this->db->where('operation_id !=', '5');
        $this->db->where('order_type !=', '2');
        $orders = $this->db->count_all_results();

        if($orders > 0)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '此商品为新客专享商品，您可以挑选其他优惠商品',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请购买其它商品',
            ]);

        $this->db->from('b2o_parent_order');
        $this->db->where('uid', $cart->user->id);
        $this->db->where('order_status', '1');
        $this->db->where('operation_id !=', '5');
        $this->db->where('order_type !=', '2');
        $orders = $this->db->count_all_results();

        if($orders > 0)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '此商品为新客专享商品，您可以挑选其他优惠商品',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请购买其它商品',
            ]);

        return false;
    }

    // 门店检查
    // @Migrated
    private function checkStore($product, $cart) {

        if( !in_array($product->store_id, $cart->stores) )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '您所在的地域无货',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请购买其它商品',
            ]);

        if( !$product->price )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '您所在的地域没有此商品',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请购买其它商品',
            ]);

        if( $cart->small_stores )
            if(!$product->is_store_sell)
                return $this->cart_v2_model->createError([
                    'code'         => 300,
                    'msg'          => '您所在的地域不销售此商品',
                    'product_name' => $product->name,
                    'item_id'      => $product->item_id,
                    'action'       => '请购买其它商品',
                ]);

        if( $product->send_region_type < $cart->tms_region_type )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '您所在的地域不在配送范围',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请购买其它商品',
            ]);

        return false;
    }

    // TODO
    private function checkUserGift($product, $cart) {

        if($product->type != 'user_gift')
            return false;

        if (!$product->gift_send_id)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '赠品参数错误',
                'reason'       => '缺少gift_send_id',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请重新领取赠品',
            ]);

        if (!$cart->user->id)
            return $this->cart_v2_model->createError([
                'code'         => 301,
                'msg'          => '请先登录',
                'reason'       => '缺少user_id',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '跳转到登录页面',
            ]);

        $this->load->model('user_gifts_model');

        if($product->user_gift_id)
            $user_gift = $this->user_gifts_model->dump(['id'=>$product->user_gift_id, 'uid'=>$cart->user->id]);
        else
            $user_gift = $this->db->query("select * from ttgy_user_gifts where active_id={$product->gift_send_id} and active_type={$product->gift_active_type} and uid={$cart->user->id} order by id desc")->row_array();

        if(!$user_gift)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '赠品异常',
                'reason'       => '查找不到user_gift数据',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
                'action'       => '请重新领取赠品',
            ]);

        if( date('Y-m-d') < $user_gift['start_time']  ) // TODO check
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '赠品未到领取时间',
                'reason'       => '当前时间小于开始时间',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
            ]);

        if( date('Y-m-d') > $user_gift['end_time'] ) // TODO check
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '赠品过期',
                'reason'       => '当前时间大于结束时间',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
            ]);

        // 会员赠品
        if($product->gift_active_type == 2) {
            $this->load->model('gsend_model');
            $gsend = $this->gsend_model->dump(['id'=>$product->gift_send_id]);

            if (!$gsend)
                return $this->cart_v2_model->createError([
                    'code'         => 300,
                    'msg'          => '赠品不存在',
                    'reason'       => '查找不到gsend数据',
                    'product_name' => $product->name,
                    'item_id'      => $product->item_id,
                    'action'       => '请重新领取赠品',
                ]);

            $valid_products = explode(',', $gsend['product_id']);

            // 赠品不包含在活动期间内
            if( !in_array($product->product_id, $valid_products) ) // TODO check
                return $this->cart_v2_model->createError([
                    'code'         => 300,
                    'msg'          => '赠品不包含在活动期间内',
                    'product_name' => $product->name,
                    'item_id'      => $product->item_id,
                ]);


            // 判断是否允许单领取
            if ($gsend['can_single_buy'] == 1) { // TODO
                $pass = false;
                foreach ($cart->items as $key=>$value) {
                    if ($value->type == 'normal') {
                        $pass = true;
                        break;
                    }
                }

                if ($pass === false)
                    return $this->cart_v2_model->createError([
                        'code'         => 300,
                        'msg'          => '必须购物其他商品才能领取',
                        'product_name' => $product->name,
                        'item_id'      => $product->item_id,
                    ]);
            }

        }

        // 充值赠品
        if($product->gift_active_type == 1) {
            $trade_gift = $this->user_gifts_model->getTradeGift($cart->user->id, $product->gift_send_id);
            if(!$trade_gift)
                return $this->cart_v2_model->createError([
                    'code'         => 300,
                    'msg'          => '充值赠品不存在',
                    'product_name' => $product->name,
                    'item_id'      => $product->item_id,
                ]);
            if( $product->product_id != $trade_gift['bonus_products'] ) // TODO check
                return $this->cart_v2_model->createError([
                    'code'         => 300,
                    'msg'          => '充值赠品异常',
                    'product_name' => $product->name,
                    'item_id'      => $product->item_id,
                ]);
            $gsend['qty'] = 1;
            $gsend['order_money_limit'] = 0; // TODO
        }

        // 判断赠品是否超出数量
        if ($product->qty > $gsend['qty']) // TODO
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '赠品已被领取啦，请去购物车查看',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
            ]);

        if($cart->items[$product->item_id])
            $cart->items[$product->item_id]->user_gift_id = $product->user_gift_id ? $product->user_gift_id : $user_gift['id']; // TODO

        return false;
    }

    // @Migrated
    private function checkAddExchange($product, $cart) {

        if($product->type != 'exchange')
            return false;

        if(!$product->pmt_id)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '没有符合换购条件',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
            ]);

        $this->load->model('promotion_v2_model');
        $strategy = $this->promotion_v2_model->getOneStrategy($product->pmt_id);

        if (!$strategy)
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '换购活动已结束',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
            ]);

        if( !in_array($product->product_id, $strategy->solution_products) )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '没有符合换购条件',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
            ]);

        if( !in_array($product->store_id, $strategy->range_stores) )
            return $this->cart_v2_model->createError([
                'code'         => 300,
                'msg'          => '没有符合换购条件',
                'product_name' => $product->name,
                'item_id'      => $product->item_id,
            ]);

        return false;

    }

    // 最后检查是否满足换购资格
    // 单独运行
    // @Migrated
    public function checkExchange($product, $promotions) {

        $pmt_ids = [];

        foreach($promotions as $promotion) {
            $pmt_ids[] = $promotion->id;
        }

        if($product->type == 'exchange')
            if( !in_array($product->pmt_id, $pmt_ids) )
                return $this->cart_v2_model->createError([
                    'code'         => 300,
                    'msg'          => '没有符合换购条件',
                    'product_name' => $product->name,
                    'item_id'      => $product->item_id,
                    'action'       => '请重新添加商品',
                ]);

        return false;

    }

    // TODO migrate to cart v3
    public function checkMutex($product, $cart) {

        $time = date("Y-m-d H:i:s");
        $sql = "select m_id,m_productId,m_type,m_desc from ttgy_mutex where m_btime<='".$time."' and m_etime>='".$time."'";
        $mutex = $this->db->query($sql)->result();

        foreach($mutex as $m) {
            $mutex_products = explode(',', $m->m_productId);
            // $mutex_products = [9117];
            $count_mutex_product = 0;
            foreach($cart->items as $current_product) {
                if( in_array($current_product->pid, $mutex_products) )
                    $count_mutex_product++;
            }

            if(  $count_mutex_product > 0 && in_array( $product->product_id, $mutex_products ) )
                if( $m->m_desc )
                    return $this->cart_v2_model->createError([
                        'code'         => 300,
                        'msg'          => $m->m_desc,
                        'product_name' => $product->name,
                        'item_id'      => $product->item_id,
                        'action'       => '请重新添加商品',
                    ]);
                else
                    return $this->cart_v2_model->createError([
                        'code'         => 300,
                        'msg'          => '单笔订单只能领取一份免费水果哟，么么哒',
                        'product_name' => $product->name,
                        'item_id'      => $product->item_id,
                        'action'       => '请重新添加商品',
                    ]);
        }

        return false;

    }



}
