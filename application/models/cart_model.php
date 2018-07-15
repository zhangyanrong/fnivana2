<?php
class Cart_model extends CI_model {

    function Cart_model() {
        parent::__construct();
        // $this->load->library("session");
    }

    function cart_info($uid){
    	/*购物车模拟数据start*/
        //normal
        //gift赠品
        //mb_gift满赠
        //exch换购
        //user_gift帐号赠品

    	$cart_demo['items']['normal_3780']['sku_id'] = '3647';
        $cart_demo['items']['normal_3780']['product_id'] = '2604';
        $cart_demo['items']['normal_3780']['product_no'] = '201410325';
        $cart_demo['items']['normal_3780']['qty'] = '1';

        $cart_demo['items']['normal_3780']['name'] = '越南红心火龙果';
        $cart_demo['items']['normal_3780']['price'] = 138;
        $cart_demo['items']['normal_3780']['unit'] = '盒';
        $cart_demo['items']['normal_3780']['spec'] = '5斤装';
        $cart_demo['items']['normal_3780']['amount'] = 138;
        $cart_demo['items']['normal_3780']['product_photo'] = 'http://img9.fruitday.com/product_pic/2604/1/1-370x370-2604-5FYS7R9X.jpg';
        $cart_demo['items']['normal_3780']['product_id'] = '2604';
        $cart_demo['items']['normal_3780']['product_no'] = '201410325';
        $cart_demo['items']['normal_3780']['weight'] = 10;
        $cart_demo['items']['normal_3780']['pmt_price'] = 0;
        $cart_demo['items']['normal_3780']['item_type'] = 'normal';

        $cart_demo['items']['normal_3780']['user_gift_id'] = '1';

        $cart_demo['items']['normal_3780']['device_limit'] = '0';//是否限制一个设备只能买一次
        $cart_demo['items']['normal_3780']['card_limit'] = '0';//是否限制不能使用优惠券
        $cart_demo['items']['normal_3780']['jf_limit'] = '0';//是否限制不能使用积分
        $cart_demo['items']['normal_3780']['pay_limit'] = '0';//是否限制只能线上支付
        $cart_demo['items']['normal_3780']['first_limit'] = '0';//是否显示只能新用户购买
        $cart_demo['items']['normal_3780']['active_limit'] = '0';//是否限制不参加任何促销活动
        $cart_demo['items']['normal_3780']['delivery_limit'] = '0';//是否限制只能2-3天送达
        $cart_demo['items']['normal_3780']['group_limit'] = '0';//是否限制不能单独购买
        $cart_demo['items']['normal_3780']['pay_discount_limit'] = '0';//是否限制不参加支付折扣活动
        $cart_demo['items']['normal_3780']['free'] = '0';//是否是企业专享商品
        $cart_demo['items']['normal_3780']['offline'] = '0';//是否是线下活动商品
        $cart_demo['items']['normal_3780']['type'] = '1';//商品类型，1:水果;2:生鲜
        $cart_demo['items']['normal_3780']['free_post'] = '0';//是否包邮
        $cart_demo['items']['normal_3780']['free_post'] = '0';//手机端是否包邮
        $cart_demo['items']['normal_3780']['is_tuan'] = '0';//是否在列表页隐藏
        $cart_demo['items']['normal_3780']['use_store'] = '0';//是否启用库存
        $cart_demo['items']['normal_3780']['xsh'] = '0';//是否是抢购商品(抢购时间，每人限购一份)
        $cart_demo['items']['normal_3780']['xsh_limit'] = '1';//抢购商品限购数量
        $cart_demo['items']['normal_3780']['ignore_order_money'] = '0';//是否无起送限制，单独收取运费
        $cart_demo['items']['normal_3780']['group_pro'] = '1111,1112';//组合商品
        $cart_demo['items']['normal_3780']['iscard'] = '0';//是否是券卡



        $cart_demo['total_amount'] = 69;
        $cart_demo['pmt_goods'] = 0;
        $cart_demo['cost_freight'] = 0;

        return $cart_demo;
        /*购物车模拟数据end*/
    }

    /**
     * 购物车mem KEY
     *
     * @return void
     * @author
     **/
    private function _get_memkey($uid)
    {
        return 'cart_'.$uid;
    }


    // c保存购物车
    public function save($key, $cart, $persist = false) {

        // c整理成json array->object
        $items = array_values($cart);
        foreach($items as &$item) {
            $item = (object)$item;
        }
        
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();        

        $this->redis->set("cart_v1:user_cart:{$key}", json_encode($items));

            
        // $cart = is_array($cart) ? serialize($cart) : $cart;
        
        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
        //     $this->load->library('memcached');

        //     $this->memcached->set($this->_get_memkey($uid),$cart);
        // }

        // if ($persist == true)
        //     $this->db->update('user',array('cart_info'=>$cart),array('id'=>$key)); // 持久化
    }

    // c获取购物车
    public function get($key) {

        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();           
        
        $items = $this->redis->get("cart_v1:user_cart:{$key}");
        
        $items = json_decode($items);
        
        if(!$items)
            return;
       
        // c整理成hash
        $cart = [];
        
        foreach($items as $item) {
            
            // $item->selected = true; // 强制勾选
            
            if($item->item_type == 'user_gift')
                $cart["{$item->item_type}_{$item->gift_send_id}_{$item->sku_id}"] = (array)$item; // 用户赠品key不一样
            elseif($item->item_type == 'coupon_gift')
                $cart["{$item->item_type}_{$item->gift_send_id}_{$item->sku_id}"] = (array)$item; // coupon_gift key不一样
            else
                $cart["{$item->item_type}_{$item->sku_id}"] = (array)$item;
                
        }

        return $cart;
                 
        // // 老的逻辑保留
        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
        //     $this->load->library('memcached');

        //     $cart = $this->memcached->get($this->_get_memkey($uid));

        //     if ($cart) {
        //         $cart = @unserialize($cart);
        //     }
            
        //     // 强制勾选
        //     // foreach($cart as &$item) {
        //     //     $item['selected'] = true;
        //     // }
                        
        // }

        // if (!$cart) {
        //     // 从持久化中读取
        //     $user = $this->db->select('cart_info')
        //                     ->from('user')
        //                     ->where('id',$uid)
        //                     ->get()
        //                     ->row_array();
        //     $cart = @unserialize($user['cart_info']);
            
        //     // 强制勾选
        //     // foreach($cart as &$item) {
        //     //     $item['selected'] = true;
        //     // }            
            
        // }

        // return $cart;
    }

    /*
    * 是否互斥
    * */
    function fan($cart_arr,$product_info){
        $carProArr = array();
        foreach ($cart_arr as $key=>$val){
            $carProArr[] = $val['product_id'];
        }
        if(in_array($product_info['product_id'],$carProArr)){
            return true;
        }
        $time = date("Y-m-d H:i:s");
        $sql = "select m_id,m_productId,m_type,m_desc from ttgy_mutex where m_btime<='".$time."' and m_etime>='".$time."'";
        $mutex = $this->db->query($sql)->result_array();
        if(!empty($mutex)){
            foreach($mutex as $kk=>$vv){
                $proArr = explode(',',$vv['m_productId']);
                $count_aa = 0;
                if(!empty($cart_arr)){
//                    if($vv['m_type']==1){
                        foreach( $cart_arr as $key=>$val)
                        {
                            if( in_array($val['product_id'], $proArr) )
                            {
                                $count_aa++;
                            }
                        }
//                    }else{
//                        $cart_pro_arr = array();
//                        foreach( $cart_arr as $key=>$val)
//                        {
//                            $cart_pro_arr[] = $val['product_id'];
//                        }
//                        $cart_pro_arr1 = array_unique($cart_pro_arr); 
//                        foreach ($cart_pro_arr1 as $k=>$v){
//                        if( in_array($v, $proArr) )
//                            {
//                                $count_aa++;
//                            }
//                        }
//                    }
                    if($count_aa>0&&in_array($product_info['product_id'],$proArr)){
                        if(!empty($vv['m_desc'])){
                            return array('code' => '300', 'msg' => $vv['m_desc']);
                        }else{
                            if($vv['m_id']>12&&$vv['m_id']<25){
                                return array('code' => '300', 'msg' => '您的购物车中包含5月3日发货的美国加州樱桃，届时此商品可能会缺货，建议您单独下单哦~');
                            }else{
                                return array('code' => '300', 'msg' => '单笔订单只能领取一份免费水果哟，么么哒');

                            }
                        }
                    }
                }
            }
        }
    }

//    function huchi($cart_arr,$uid,$product_info){
//        $huchi_arr = $this->config->item('huchi');
//        if(in_array($product_info['product_id'],$huchi_arr)){
//            if(!empty($product_info)){
//                $huchi_str = "'".implode("','",$huchi_arr)."'";
//                $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
//                $result = $this->db->query($sql)->row_array();
////                echo $sql."<br>";exit;
////                $this->db->from('order');
////                $this->db->join('order_product',"order_product.order_id=order.id");
////                $this->db->where(array('order.uid'=>$uid,'order.order_status'=>'1','order.operation_id !='=>'5'));//,'order.pay_status !='=>'0'
////                $this->db->where_in('order_product.product_id',$huchi_arr)->or_where_in('group_pro_id',$huchi_arr);
//                if($result['num']!=0){
//                    return array('code' => '300', 'msg'=>'虽然小果想给您更多机会，但抢购商品限抢1款，您已经购买过抢购商品咯');
//                }
//            }
//            if(!empty($cart_arr)){
//                foreach( $cart_arr as $key=>$val)
//                {
//                    if( in_array($val['product_id'], $huchi_arr) )
//                    {
//                        return array('code' => '300', 'msg'=>'虽然小果想给您更多机会，但抢购商品限抢1款，您已经购买过抢购商品咯');
//                    }
//                }
//            }
//        }
//    }
//
//    function huchi1($cart_arr,$uid,$product_info){
//        $huchi_arr = $this->config->item('huchi1');
//        if(in_array($product_info['product_id'],$huchi_arr)){
//            if(!empty($product_info)){
//                $huchi_str = "'".implode("','",$huchi_arr)."'";
//                $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
//                $result = $this->db->query($sql)->row_array();
////                echo $sql."<br>";exit;
////                $this->db->from('order');
////                $this->db->join('order_product',"order_product.order_id=order.id");
////                $this->db->where(array('order.uid'=>$uid,'order.order_status'=>'1','order.operation_id !='=>'5'));//,'order.pay_status !='=>'0'
////                $this->db->where_in('order_product.product_id',$huchi_arr)->or_where_in('group_pro_id',$huchi_arr);
//                if($result['num']!=0){
//                    return array('code' => '300', 'msg'=>'虽然果园君想给您更多机会 但特惠商品仅限购买1份哦!');
//                }
//            }
//            if(!empty($cart_arr)){
//                foreach( $cart_arr as $key=>$val)
//                {
//                    if( in_array($val['product_id'], $huchi_arr) )
//                    {
//                        return array('code' => '300', 'msg'=>'虽然果园君想给您更多机会 但特惠商品仅限购买1份哦!');
//                    }
//                }
//            }
//        }
//    }
//
//    /*
//* 是否互斥
//* */
//    function huchi3($cart_arr,$uid,$product_info){
//        $huchi_arr = $this->config->item('huchi3');
//        if(in_array($product_info['product_id'],$huchi_arr)){
//            if(!empty($product_info)){
//                $huchi_str = "'".implode("','",$huchi_arr)."'";
//                $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
//                $result = $this->db->query($sql)->row_array();
////                echo $sql."<br>";exit;
////                $this->db->from('order');
////                $this->db->join('order_product',"order_product.order_id=order.id");
////                $this->db->where(array('order.uid'=>$uid,'order.order_status'=>'1','order.operation_id !='=>'5'));//,'order.pay_status !='=>'0'
////                $this->db->where_in('order_product.product_id',$huchi_arr)->or_where_in('group_pro_id',$huchi_arr);
//                if($result['num']!=0){
//                    return array('code' => '300', 'msg'=>'虽然果园君想给您更多机会 但特惠商品仅限购买1份哦!');
//                }
//            }
//            if(!empty($cart_arr)){
//                foreach( $cart_arr as $key=>$val)
//                {
//                    if( in_array($val['product_id'], $huchi_arr) )
//                    {
//                        return array('code' => '300', 'msg'=>'虽然果园君想给您更多机会 但特惠商品仅限购买1份哦!');
//                    }
//                }
//            }
//        }
//    }
//
//    function huchi2($cart_arr,$uid,$product_info){
//        $huchi_arr = $this->config->item('huchi2');
//        if(in_array($product_info['product_id'],$huchi_arr)){
//            if(!empty($product_info)){
//                $huchi_str = "'".implode("','",$huchi_arr)."'";
//                $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
//                $result = $this->db->query($sql)->row_array();
////                echo $sql."<br>";exit;
////                $this->db->from('order');
////                $this->db->join('order_product',"order_product.order_id=order.id");
////                $this->db->where(array('order.uid'=>$uid,'order.order_status'=>'1','order.operation_id !='=>'5'));//,'order.pay_status !='=>'0'
////                $this->db->where_in('order_product.product_id',$huchi_arr)->or_where_in('group_pro_id',$huchi_arr);
//                if($result['num']!=0){
//                    return array('code' => '300', 'msg'=>'亲，717秒杀仅限秒杀1款商品哦，么么哒~');
//                }
//            }
//            if(!empty($cart_arr)){
//                foreach( $cart_arr as $key=>$val)
//                {
//                    if( in_array($val['product_id'], $huchi_arr) )
//                    {
//                        return array('code' => '300', 'msg'=>'亲，717秒杀仅限秒杀1款商品哦，么么哒~');
//                    }
//                }
//            }
//        }
//    }

    function getProStock($sku_id){
        $this->db->select('stock');
        $this->db->from('product_price');
        $this->db->where('id',$sku_id);
        return $this->db->get()->row_array();
    }

    function get_active_limit($uid,$tag){
        $this->db->from('active_limit');
        $this->db->where(array('uid'=>$uid,'active_tag'=>$tag));
        $query = $this->db->get();
        $rows = $query->num_rows();
        return $rows;
    }

    /*
    *新满额减
    */
    function checkProSale_upto($cart_info,$uid,$source){
        $pro_sale_upto = $this->getProSaleRules(5);
        if(empty($pro_sale_upto)){
            return false;
        }
        $enough_sales_money = 0;
        $affect_rule_tags = array();
        foreach($pro_sale_upto as $k => $v){
            $content = unserialize($v['content']);
            $channel = unserialize($content['channel']);
            $rule_product_id = explode(',',$v['product_id']);
            $in_rules_money = 0;
            if(in_array($source,$channel)){
                if(!$content['isrepeat']){
                    $rows = $this->get_active_limit($uid,$v['active_tag']);
                    if($rows == 0){
                        foreach ($cart_info['items'] as $key => $value) {
                            if(in_array($value['product_id'],$rule_product_id)){
                                $in_rules_money += $value['amount'];
                            }
                        }
                        if($in_rules_money>=$content['pro_upto_money']){
                            $enough_sales_money += $content['cut_money'];
                            $affect_rule_tags[] = $v['active_tag'];
                        }
                    }
                }else{
                    foreach ($cart_info['items'] as $key => $value) {
                        if(in_array($value['product_id'],$rule_product_id)){
                            $in_rules_money += $value['amount'];
                        }
                    }
                    if($in_rules_money>=$content['pro_upto_money']){
                        $enough_sales_money += $content['cut_money'];
//                                $affect_rule_tags[] = $v['active_tag'];
                    }
                }
//                        echo $enough_sales_money;
//                        var_dump($affect_rule_tags);
            }
        }
        $cut_money = $enough_sales_money;
        return array('cut_money'=>$cut_money,'affect_rule_tags'=>$affect_rule_tags);
    }

    /*
    *获取所有进行中的促销规则
    */
    function getProSaleRules($num){
        $this->load->model("promotion_model");
        $pro_sale_first = $this->promotion_model->get_single_promotion($num);
        return $pro_sale_first;
    }

	public function check_cart_hd_pro($items, $uid) {
		$tuanPro = array(
			9784 => array('taglm' => "1160320", 'text' => '您没有参与许愿，不能免费领取礼品哦'),
		);
		if (!empty($items)) {
			$product_ids = $items['product_id'];
			if (array_key_exists($product_ids, $tuanPro)) {
				$where = array('hd_data.hd_tag' => $tuanPro[$product_ids]['taglm']);
				$hdCount = $this->db->from('hd_relate')->join('hd_data', 'hd_relate.hd_id=hd_data.id')->where($where)->count_all_results();
				if ($hdCount < 20000) {//满20000人才能够买
					return "很遗憾，心愿未达成，不能1分钱购买，记得参与下次心愿单活动奥~";
				}
				$relateWhere = array('hd_relate.uid' => $uid, 'hd_data.hd_tag' => $tuanPro[$product_ids]['taglm']);
				$relateCount = $this->db->from('hd_relate')->join('hd_data', 'hd_relate.hd_id=hd_data.id')->where($relateWhere)->count_all_results();
				if ($relateCount == 0) {//未参加过活动
					return $tuanPro[$product_ids]['text'];
				}
			}
		}
		return FALSE;
	}

}