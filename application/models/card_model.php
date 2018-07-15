<?php
class Card_model extends MY_Model {
    var $card_channel = array('1'=>'官网','2'=>'APP','3'=>'WAP');
    function Card_model() {
        parent::__construct();
        // $this->load->library("session");
    }

    public function table_name()
    {
        return 'card';
    }

    /*
    *获取优惠券信息
    */
    function get_card_info($card_number){
        $this->db->from("card");
        $this->db->where(array("card_number"=>$card_number));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result;
    }

    /*
    *验证优惠券状态
    */
    function card_can_use($card,$uid,$goods_money,$source,$jf_money,$pay_discount,$is_o2o = 0,$cart_products = array()) {

        if (empty($card)) {
            return array(0, '卡号无效');
        }
         
        if($is_o2o == 0 && $card['maketing'] == 1){
            return array(0, '该卡是天天到家专用券卡，不能抵扣');
        }elseif($is_o2o == 1 && $card['maketing'] != 1){
            return array(0, '该卡不是天天到家专用券卡，不能抵扣');
        }

        // whether send
        if ($card['is_sent'] == 0) {
            return array(0, "卡号无效");
        }
        // check card denomination
        if($card['maketing']==6){
            if ($card->card_discount == 0.00) {
                return array(0, '该卡折扣为0，不能抵扣');
            }
        }else{
            if ($card['card_money'] <= 0) {
                return array(0, '该卡价值为0，不能抵扣');
            }
        }    

        if(($goods_money-$jf_money-$pay_discount) < $card['card_money']){
            return array(0, "购买商品总金额低于优惠券金额，不能进行配送");
        }
        
        if(!empty($card['channel'])){
            $channel = unserialize($card['channel']);
            $request_channel = 0;
            switch ($source) {
                case 'pc':
                    $request_channel = 1;
                    break;
                case 'app':
                    $request_channel = 2;
                    break;
                case 'wap':
                    $request_channel = 3;
                    break;
                default:
                    $request_channel = 0;
                    break;
            }
            if(!(is_array($channel) && count($channel) == 1 && $channel[0] == 0) && $request_channel!=0 && !in_array($request_channel,$channel)){
                $msg_str = "";
                foreach($channel as $val){
                    $msg_arr[]= $this->card_channel[$val];
                }
                $msg_str = join(",",$msg_arr);
                return array(0, '该优惠券仅限'.$msg_str.'使用');
            }
        }

        switch ($source) {
            case 'pc':
                if($card['promotion_type'] == 2){
                    return array(0, '该优惠券仅限线下使用');
                }
                break;
            case 'app':
                if($card['promotion_type'] == 2){
                    return array(0, '该优惠券仅限线下使用');
                }
                break;
            case 'wap':
                if($card['promotion_type'] == 2){
                    return array(0, '该优惠券仅限线下使用');
                }
                break;
            case 'pos':
                if($card['promotion_type'] == 1){
                    return array(0, '该优惠券仅限线上使用');
                }
                break;
            default:
                # code...
                break;
        }

        if ($card['order_money_limit'] && $goods_money < $card['order_money_limit']) {
            return array(0, "订单满" . $card['order_money_limit'] . "元该优惠券才能使用");
        }
        // check used times
        if ($card['max_use_times']) {
            if (($card['used_times'] > 0) && ($card['used_times'] >= $card['max_use_times'])) {
                return array(0, "该卡已经被使用");
            } 
        }
        if ($card['is_used'] == 1) {
            return array(0, "该卡已经被使用");
        }
        // check card start date
         $exr_arr=array("双11官网红包","双11官网红包(满188使用)","双11官网红包(满258使用)","双11官网红包(满300使用仅限app)");
         if (strcmp($card['time'], date('Y-m-d')) > 0) {
            if(in_array($card['remarks'],$exr_arr)){
                return array(0, "此券仅可在双十一当天使用");
            }else
                return array(0, "该优惠券有效期为".$card['time']."至".$card['to_date']);
        } 
        // check expired date
        if (strcmp($card['to_date'], date('Y-m-d')) < 0){
            if(in_array($card['remarks'],$exr_arr)){
                return array(0, "此券仅可在双十一当天使用");
            }else
                return array(0, "该优惠券有效期为".$card['time']."至".$card['to_date']);
        }
        if($uid!=''){
            if($uid!=$card['uid'] && $card['uid']!='0' && $card['uid']!=''){
                return array(0,"您登录的帐号不能使用该抵扣码");
            }
        }else{
            return array(0,"请先登录您的帐号");
        }

        if($cart_products){
            $dp_card_money = 0;  //可以使用优惠券的商品总金额
            $cart_pro_ids = array();    //可以使用优惠券的商品ID
            $c_ps = array();    //优惠券指定商品ID
            if($card['product_id']){
                $c_ps = explode(',', $card['product_id']);
            }
            foreach ($cart_products as $product) {
                if($product['card_limit'] == 1){
                    
                }else{
                    if(empty($c_ps) || (!empty($c_ps) && in_array($product['product_id'], $c_ps))){
                        $dp_card_money = bcadd($dp_card_money,bcsub($product['amount'],$product['discount'],2),2);
                        $cart_pro_ids[] = $product['product_id'];
                    }
                }
            }
            if(empty($cart_pro_ids)){
                return array(0,"没有可以使用此优惠券的商品");
            }
            if($dp_card_money < $card['card_money']){
                return array(0,"可用券的商品总金额低于优惠券抵扣金额");
            }
            if ($card['order_money_limit'] && $dp_card_money < $card['order_money_limit']) {
                return array(0, "可用券的商品总金额未满足优惠券使用条件");
            }
        }

        // $cart_pro_ids = array();
        // if($cart_products){
        //     $cart_product_amount = array();
        //     foreach ($cart_products as $product) {
        //         $cart_pro_ids[] = $product['product_id'];
        //         if($product['card_limit'] == 1){
        //             return array(0,"购物车中有不能使用优惠券商品");
        //         }
        //         $cart_product_amount[$product['product_id']] = $product['amount'];
        //     }
        // }
        // if($card['product_id'] && !empty($cart_products)){
        //     $c_ps = explode(',', $card['product_id']);
        //     if($c_ps){
        //         $_arr = array_intersect($cart_pro_ids,$c_ps);
        //         if(empty($_arr)){
        //             return array(0,"购物车中无优惠券指定商品");
        //         }else{
        //             $dp_card_money = 0;
        //             foreach ($_arr as $card_pro_id) {
        //                 $dp_card_money = bcadd($dp_card_money, isset($cart_product_amount[$card_pro_id]) ? $cart_product_amount[$card_pro_id] : 0 , 2);
        //             }
                    
        //             if($dp_card_money < $card['card_money']){
        //                 return array(0,"购物车中优惠券指定商品金额小于优惠券金额");
        //             }
        //         }
        //     }
        // }
        return array(1, '');
    }

    /*
    *更新优惠券状态
    */
    function update_card($data,$where){
        $this->db->where($where);
        $this->db->update('card', $data);
        if($this->db->affected_rows()){
            
            /**
             * 支付宝服务窗优惠券核销
             * is_used == 1
             * card_number prefix ：zhfb0
             */
            if( isset($where['card_number']) && isset($data['is_used']) && $data['is_used'] == 1 && strpos($where['card_number'], 'zhfu' ) === 0 ){
                
                $ver_data = array(
                    'card_number' => $where['card_number'],
                    'used_time' => time(),
                );
                $this->load->library('phpredis');
                $this->redis = $this->phpredis->getConn();
                $this->redis->lpush('AliPassVer', json_encode($ver_data));
            }
            
            return true;    
        }else{
            return false;
        }
    }

    function get_user_last_card($uid){
        if(empty($uid)) return ;
        $this->db->select('max(sendtime) as maxtime');
        $this->db->from("card");
        $this->db->where(array("uid"=>$uid,'is_sent'=>1));
        $this->db->where('time <=' , date("Y-m-d"));
        $this->db->where('to_date >=' , date("Y-m-d"));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result['maxtime'];
    }

    function get_orderinit_card($uid,$goods_money,$source,$jf_money,$pay_discount,$is_o2o = 0,$cart,$card_limit,$method_money=0){
        $init_card = array();
        if($card_limit != 1){
            $limit_money = 0;
            $cards = $this->getList('*',array('uid'=>$uid,'maketing'=>$is_o2o),0,-1,'card_money desc');
            $buy_items = array();
            foreach ($cart['items'] as $value) {
                $buy_items[] = $value['product_id'];
                if($value['order_money_limit'] > 0){
                    if($limit_money == 0 || $limit_money<$value['order_money_limit']) $limit_money = $value['order_money_limit'];
                }
            }
            foreach ($cards as $card) {
                $res = $this->card_can_use($card,$uid,$goods_money,$source,$jf_money,$pay_discount,$is_o2o);
                if($res[0] == 1){
                    $final_money = $goods_money;
                    //$final_money = $goods_money-$method_money-$jf_money-$pay_discount-$card['card_money'];
                    $final_money = number_format($final_money,3,'.','');
                    if($limit_money > 0 && bccomp($limit_money, $final_money,3) == 1){
                        continue;
                    }
                    if($card['product_id'] && $card['product_id']!=','){
                        $p_ids = explode(',', $card['product_id']);
                        $can_use_product = array_intersect($p_ids, $buy_items);
                        if(empty($can_use_product)){
                            continue;
                        }
                    }
                    $init_card = $card;
                    break;
                }
            }
        }
        return $init_card;
    }

    function can_use_card_number($uid,$goods_money,$source,$jf_money,$pay_discount,$is_o2o = 0,$cart,$card_limit){
        if($card_limit == 1) return 0;
        $can_use_card_number = 0;
        $cards = $this->getList('*',array('uid'=>$uid,'maketing'=>$is_o2o),0,-1,'card_money desc');
        $buy_items = array();
        foreach ($cart['items'] as $value) {
            $buy_items[] = $value['product_id'];
        }
        foreach ($cards as $card) {
            $res = $this->card_can_use($card,$uid,$goods_money,$source,0,$pay_discount,$is_o2o);
            if($res[0] == 1){
                if($card['product_id'] && $card['product_id']!=','){
                    $p_ids = explode(',', $card['product_id']);
                    $can_use_product = array_intersect($p_ids, $buy_items);
                    if(empty($can_use_product)){
                        continue;
                    }
                }
                $can_use_card_number++;
            }
        }
        return $can_use_card_number;
    }

    function addCardType($card_numbers,$type = '全场通用券',$op_id = 0, $department = '',$tag = ''){
        if(!is_array($card_numbers)) $card_numbers = array($card_numbers);
        if(empty($card_numbers)) return true;
        $insert_data = array();
        foreach ($card_numbers as $key => $value) {
            $data = array();
            $data['card_number'] = $value;
            $data['type'] = $type;
            $data['op_id'] = $op_id;
            $data['department'] = $department;
            $data['tag'] = $tag;
            $insert_data[] = $data;
        }
        $res = $this->db->insert_batch("card_type",$insert_data);
        if(!$res){
            return false;
        }
        return true;
    }

    function b2o_can_use_card_number($uid,$goods_money,$source,$jf_money,$pay_discount,$is_o2o = 0,$cart,$card_limit,$cart_products = array()){
        //if($card_limit == 1) return 0;
        $can_use_card_number = 0;
        $cards = $this->getList('*',array('uid'=>$uid,'maketing'=>$is_o2o),0,-1,'card_money desc');
        // $buy_items = array();
        // foreach ($cart['products'] as $value) {
        //     $buy_items[] = $value['product_id'];
        // }

        $all_card_num = 0;
        foreach ($cards as $card) {
            $res = $this->card_can_use($card,$uid,$goods_money,$source,0,$pay_discount,$is_o2o,$cart_products);
            if($res[0] == 1){
                // if($card['product_id'] && $card['product_id']!=','){
                //     $p_ids = explode(',', $card['product_id']);
                //     $can_use_product = array_intersect($p_ids, $buy_items);
                //     if(empty($can_use_product)){
                //         continue;
                //     }
                // }
                // else
                // {
                //     $all_card_num++;
                // }
                $can_use_card_number++;
            }
        }
        // if($card_limit == 1 && $can_use_card_number >= $all_card_num)
        // {
        //     $can_use_card_number = $can_use_card_number - $all_card_num;
        // }
        return $can_use_card_number;
    }
}