<?php
namespace bll;

class Costfreight {

    public function __construct()
    {
        $this->ci = & get_instance();
    }


      /*
    *购物车商品总重量
    */
    function cacu_weight($cart_info) {
        if(isset($cart_info['pro_weight'])){
            return $cart_info['pro_weight'];
        }else{
            $weight = 0;
            if(!empty($cart_info['items'])) {
                foreach($cart_info['items'] as $val) {
                    
                    // 未勾选
                    if($value['selected'] != true)
                        continue;
                        
                    // 已失效
                    if($value['valid'] != true)
                        continue;                        
                                         
                    $weight += $val['weight']*$val['qty'];
                }
            }
            return $weight;
        }
    }

    public function cost_freight_alter_v2($cart_info,$region_id)
    {   
        $data = [
            'id'       => 0,
            'name'     => '包邮提醒',
            'type'     => 'costfreight',        // pmt_alert统一用type
            'pmt_type' => 'costfreight',        // 蔡昀辰：todo:reomove 
        ];
        // 找到对应的省份
        $site_list = $this->ci->config->item("site_list");
        $province_id = isset($site_list[$region_id]) ? $site_list[$region_id] : $region_id;

        $this->ci->load->model("region_model");
        $area_freight_info = $this->ci->region_model->get_area_info($region_id);

        $this->ci->load->model("order_model");
        $check_result = $this->ci->order_model->check_cart_pro_status($cart_info);
        if($check_result['free_post']=='1'){
            return array();
        }

        $weight = $this->cacu_weight($cart_info);
        
        if(empty($area_freight_info['send_role'])) {
            $send_role = unserialize('a:4:{i:0;a:5:{s:3:"low";i:0;s:5:"hight";s:5:"49.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:20;s:19:"follow_weight_money";i:0;}i:1;a:5:{s:3:"low";i:50;s:5:"hight";s:5:"99.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:10;s:19:"follow_weight_money";i:0;}i:2;a:5:{s:3:"low";i:100;s:5:"hight";s:6:"199.99";s:12:"first_weight";i:8;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:2;}i:3;a:5:{s:3:"low";i:200;s:5:"hight";i:9999;s:12:"first_weight";i:9999;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:0;}}');
        }else{
            $send_role = unserialize($area_freight_info['send_role']);
        }
        $goods_money = $cart_info['total_amount'];


        // $goods_money = 500;
        // $cart_info['total_amount'] = 500;
        // $weight = 100;

        foreach ($send_role as $key => $value) {
            if($value['hight']==9999){//运费规则上限,每多x元，首重增加ykg
                // $first_weight_tmp = $value['first_weight'] + floor(($goods_money-$value['low'])/$value['increase'])*$value['add_first_weight'];
                // if($weight <= $first_weight_tmp) {
                //     $method_money = $value['first_weight_money'];
                // }else {
                //     $method_money = $value['first_weight_money'] + ceil(($weight - $first_weight_tmp))*$value['follow_weight_money'];
                // }
                $method_money = 0;
                break;
            }else if($goods_money >= $value['low'] && $goods_money <= $value['hight']){
                if($weight <= $value['first_weight']) {
                    $method_money = $value['first_weight_money'];
                }else {
                    $method_money = $value['first_weight_money'] + ceil(($weight - $value['first_weight']))*$value['follow_weight_money'];
                }
                break;
            }
        }

        foreach ($send_role as $key => $value) {
            if($value['first_weight_money']==0 && $cart_info['total_amount']<$value['low'] ){
                $free_post_money_limit = $value['low'];
                if($value['hight']==9999){
                    $free_weight = '';    
                }else{
                    $free_weight = '('.$value['first_weight'].'公斤以下)';
                }
                
                break;
            }else if($value['hight']==9999){
                // $free_post_money_limit = $value['low'] + floor(($goods_money-$value['low'])/$value['increase'])*$value['increase']+$value['increase'];
                // $free_weight = $value['first_weight'] + floor(($goods_money-$value['low'])/$value['increase'])*$value['add_first_weight']+$value['add_first_weight'];
                $free_post_money_limit = $value['low'];
                $free_weight = '';
                break;
            }
        }

        if($method_money>0 && $cart_info['total_amount']>0){
            $outofmoney = bcsub($free_post_money_limit,$cart_info['total_amount'],2);
            if ($province_id == 106092) {
                $region = '上海(外环以内)';
            } else {
                $region = $area_freight_info['name'];
            }
            $data['solution'] = array(
                'method_money' => $method_money,
                'url'          => true,
                'outofmoney'   => $outofmoney,
                'tag'          => '邮',
                'title'        => $region.'满'.$free_post_money_limit.'元包邮'.$free_weight.'，还差'.$outofmoney.'元，去凑单',
                'name'         => $region.'满'.$free_post_money_limit.'元包邮'.$free_weight,
                'type'         => 'costfreight',        // pmt_alert统一用type
                'pmt_type'     => 'costfreight',        // 蔡昀辰：todo: reomove                
                'pmt_id'       => 0
            );
            return $data;
        }else{
            return array();
        }
    }

    public function cost_freight_alter($cart_info,$region_id)
    {
        $data = array('pmt_type'=>'costfreight');

        $this->ci->load->model('order_model');
        $freight_check = $this->ci->order_model->check_cart_pro_status($cart_info);

        if ($freight_check['free_post']=='1') {
            return array();
        }

        // 找到对应的省份
        $site_list = $this->ci->config->item("site_list");
        $province_id = isset($site_list[$region_id]) ? $site_list[$region_id] : $region_id;

        $this->ci->load->model("region_model");
        // $send_free = $this->ci->region_model->is_send_wd($province_id);

        $area_freight_info = $this->ci->region_model->get_area_info($region_id);
        $free_post_money_limit = $area_freight_info['free_post_money_limit'];

        if( $cart_info['total_amount'] >= $free_post_money_limit) {
            return array();
        }

        $outofmoney = bcsub($free_post_money_limit,$cart_info['total_amount'],2);

        if ($province_id == 106092) {
            $region = '上海(外环以内)';
        } else {
            $region = $area_freight_info['name'];
        }

        // if ($send_free === true) { // 外地

        //     if ( !$area_freight_info['first_weight'] || !$area_freight_info['first_weight_money'] ) {
        //         return array();
        //     }

        //     // $wd_region_free_post_money_limit = $this->ci->config->item("wd_region_free_post_money_limit");

        //     $free_post_money_limit = $area_freight_info['free_post_money_limit'];//isset($wd_region_free_post_money_limit[$region_id])?$wd_region_free_post_money_limit[$region_id]:300;

        //     if( $cart_info['total_amount'] >= $free_post_money_limit) {
        //         return array();
        //     }

        //     $outofmoney = $free_post_money_limit - $cart_info['total_amount'];

        //     $region = $area_freight_info['name'];
        // } else {  // 上海
        //     // $free_post_money_limit = 100;
        //     $free_post_money_limit = $area_freight_info['free_post_money_limit'];

        //     if ($freight_check['ignore_order_money'] == '1' && $cart_info['total_amount'] < 60) {
        //         return array();
        //     }

        //     if ($cart_info['total_amount'] >= $free_post_money_limit) {
        //         return array();
        //     }

        //     $outofmoney = $free_post_money_limit - $cart_info['total_amount'];

        //     if ($province_id == 106092) {
        //         $region = '上海(外环以内)';
        //     } else {
        //         $region = $area_freight_info['name'];
        //     }

        // }

        $data['solution'] = array('url'=>true,'outofmoney'=>$outofmoney,'tag'=>'包','title'=> $region.'满'.$free_post_money_limit.'元包邮，还差'.$outofmoney.'元，去凑单','pmt_type'=>'costfreight', 'pmt_id'=>0);

        return $data;
    }

    public function o2o_cost_freight_alter($cart_info,$region_id)
    {
        $data = array('pmt_type'=>'costfreight');

        $this->ci->load->model('order_model');
        $freight_check = $this->ci->order_model->check_cart_pro_status($cart_info);

        if ($freight_check['free_post']=='1') {
            return array();
        }
        $postFee = $this->ci->order_model->getO2oPostFee();
        $free_post_money_limit = $postFee['limit'];

        if( $cart_info['total_amount'] >= $free_post_money_limit) {
            return array();
        }

        $outofmoney = bcsub($free_post_money_limit,$cart_info['total_amount'],2);

        $data['solution'] = array('url'=>true,'outofmoney'=>$outofmoney,'tag'=>'包','title'=> '天天到家满'.$free_post_money_limit.'元包邮，还差'.$outofmoney.'元，去凑单','type'=>'costfreight', 'pmt_id'=>0);

        return $data;
    }
}