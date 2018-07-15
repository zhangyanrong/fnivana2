<?php
class Order_model extends MY_Model {
    var $generate_order_name = "";
    function Order_model() {
        parent::__construct();
        $this->load->helper('public');
        $this->load->model('cart_model');
        $this->db_master = $this->load->database('default_master', TRUE);
        // $this->load->library("session");
    }

    /*
    *获取历史操作订单信息
    */
    function get_order_id($uid){
        $this->db_master->select("id");
        $this->db_master->from("order");
        $this->db_master->where(array("order_status" => "0","uid"=>$uid));
        $this->db_master->order_by("time","desc");
        $this->db_master->limit(1);
        $query=$this->db_master->get();
        $result=$query->row_array();
        if(isset($result['id'])) {
            $order_id = intval($result['id']);
            return $order_id;
        }else{
            return false;
        }
     }

     /*
     *获取上一张订单信息
     */
    function preOrderInfo($uid){
         $this->db->select("order_name,pay_parent_id,pay_id,address_id");
         $this->db->from("order");
         $this->db->where(array("uid"=> $uid, "order_status"=>1,"order_type !="=>'3',"order_type !="=>'4',"pay_parent_id !="=>'6','is_enterprise'=>''));
         $this->db->order_by("time","desc");
         $this->db->limit(1);
         $query=$this->db->get();
         $result=$query->row_array();
         if(isset($result['order_name']))
         {
             return array(
                 'pay_parent_id'=>$result['pay_parent_id'],
                 'pay_id'=>$result['pay_id'],
                 'address_id'=>$result['address_id']
             );
         }
         else
         {
            return;
         }
     }

    /*
     *插入预生成订单
     */
    function generate_order($table, $fields, $prefix="")
     {
         if(isset($table) && isset($fields)){
             $order_name = date("ymdi").rand_code(4);
             if($table == "trade"){
                 $order_name = "T".$order_name;
                 $fields[array_search("",$fields)]=$order_name;

                 $fields['post_at']=date("Y-m-d H:i:s");
                if($fields['money']>0)
                {
                    $fields['type']="income";
                    $fields['status']=isset($fields['status'])?$fields['status']:"等待款项到帐";
                }
                else
                {
                    $fields['type']="outlay";
                }
             } else {
                 $order_name = $prefix.$order_name;
                 $fields[array_search("",$fields)]=$order_name;
             }

             $insert_query = $this->db->insert_string($table,$fields);
             // $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
             $query = $this->db->query($insert_query);
             $order_id = $this->db->insert_id();
             if($order_id=="0"){
                $order_name=$this->generate_order($table, $fields, $prefix);
             }
            $this->generate_order_name = $order_name;
            return  $order_id;
         }else{
            return false;
         }
     }

     /*
	 *是否有2-3天配送的商品
     */
     function check_offline_sendtime($uid){
        // $this->load->bll('cart',$this->cart_bll_params);
        // if($this->region_id) $this->bll_cart->set_province($this->region_id);
        $cart_info = $this->cart_info;

        if(!empty($cart_info['items'])){
            foreach ($cart_info['items'] as $key => $value) {
                if($value["delivery_limit"]==1){
                    return true;
                }
            }
        }
        return false;
    }

    /*
	*预售商品配送时间
    */
    function check_advsale_sendtime($uid,$cart_info=array()){
        $now_data = date("Y-m-d H:i:s");
        $this->db->select('product_id,send_date');
        $this->db->from('advance_sales');
        $this->db->where(array('start <='=>$now_data,'end >='=>$now_data,'id !='=>"6"));
        $active_info = $this->db->get()->result_array();
        if(empty($active_info)){
          return false;
        }

        $active_arr = array();
        foreach ($active_info as $key => $value) {
            $product_id_arr = explode(",", $value['product_id']);
            foreach ($product_id_arr as $k => $v) {
                $active_arr[$v] = $value['send_date'];
            }
        }

        if(empty($cart_info)){
            $this->load->bll('cart',$this->cart_bll_params);
            if($this->region_id) $this->bll_cart->set_province($this->region_id);
            $cart_info = $this->bll_cart->get_cart_info();
        }

        if(!empty($cart_info['items'])){
            $return_date = '';
                foreach ($cart_info['items'] as $key => $value) {
                    if(isset($active_arr[$value['product_id']])){
                        if($active_arr[$value['product_id']]>$return_date){
                            $return_date = $active_arr[$value['product_id']];
                        }
                    }
                }
        }
        if($return_date && $return_date <= date('Y-m-d')){
            $return_date = date('Y-m-d',strtotime('+ 1 day'));
        }
        return empty($return_date)?false:$return_date;
//        return false;
    }

    /*
    *获取默认配送时间
    *已经废弃
    */
    // function defaultSendTime($area_info){
    //     $days = 6;//可配送时间段
    //     $h=date("H");
    //     $a=$this->getArea($area_info['identify_code']);
    //     $a=$a[0];
    //     $cut_off = $area_info['cut_off_time'];
    //     $cut_off_m = $area_info['cut_off_time_m'];
    //     if($a==1){
    //         if($h>=0 && $h<$cut_off_m){
    //             $return_date = array('first_date'=>date("Ymd",strtotime("+1 day")),'first_time'=>'0918');
    //         }elseif($h<$cut_off){
    //             $return_date = array('first_date'=>date("Ymd",strtotime("+1 day")),'first_time'=>'0918');
    //         }else {
    //             if($cut_off_m>0){
    //                 $return_date = array('first_date'=>date("Ymd",strtotime("+2 day")),'first_time'=>'0918');
    //             }else{
    //                 $return_date = array('first_date'=>date("Ymd",strtotime("+2 day")),'first_time'=>'0918');
    //             }
    //         }
    //     }else if($a==2){
    //         if($h<$cut_off){
    //             $return_date = array('first_date'=>date("Ymd",strtotime("+1 day")),'first_time'=>'0918');
    //         }else {
    //             $return_date = array('first_date'=>date("Ymd",strtotime("+2 day")),'first_time'=>'0918');
    //         }
    //     }else if($a == 3){
    //         $return_date = array('first_date'=>date("Ymd",strtotime("+3 day")),'first_time'=>'0918');
    //     }elseif($a==4){
    //         $return_date = array('first_date'=>'after2to3days','first_time'=>'');
    //     }elseif($a==5){
    //         if($h>=0 && $h<$cut_off_m){
    //             $return_date = array('first_date'=>date("Ymd",strtotime("+1 day")),'first_time'=>'0918');
    //         }elseif($h<$cut_off){
    //             $return_date = array('first_date'=>date("Ymd",strtotime("+1 day")),'first_time'=>'0918');
    //         }else {
    //             if($cut_off_m>0){
    //                 $return_date = array('first_date'=>date("Ymd",strtotime("+2 day")),'first_time'=>'0918');
    //             }else{
    //                 $return_date = array('first_date'=>date("Ymd",strtotime("+2 day")),'first_time'=>'0918');
    //             }
    //         }
    //     }elseif($a==9){
    //         $return_date = array('first_date'=>'after2to3days','first_time'=>'');
    //     }
    //     /*2014大年30晚上不送货start*/

    //     if($return_date['first_date']=='20150501'){
    //             $return_date['first_date'] = '20150502';
    //     }

    //     /*2014大年30晚上不送货end*/
    //     return $return_date;
    // }

    /*
    *获取配送标识
    */
    function getArea($identify_code){
        $m="";
        preg_match("/\d+/",$identify_code,$m);
        return $m;
    }

    /*
    *验证企业订单
    */
    function check_enterprise($uid,$cart_info=array()){
        $sql = "select e.product_id,e.uid,e.send_day,e.tag from ttgy_enterprise as e join ttgy_user as u on u.enter_id=e.id where u.id=".$uid;
        $enter_arr = $this->db->query($sql)->row_array();
        if(empty($enter_arr)){
            return false;
        }
        $product_id_arr = explode(',', $enter_arr['product_id']);
        $product_id_tmp = array();
        foreach ($product_id_arr as $value) {
            $product_id_tmp[$value] = $value;
        }
        if(empty($cart_info)){
            $this->load->bll('cart',$this->cart_bll_params);
            if($this->region_id) $this->bll_cart->set_province($this->region_id);
            $cart_info = $this->bll_cart->get_cart_info();

            // 移除未勾选和失效商品
            foreach($cart_info['items'] as $key=>$item) {
                if(!$item['selected'] || !$item['valid'])
                    unset($cart_info['items'][$key]);
            }

        }

        if(!empty($cart_info['items'])){
            foreach ($cart_info['items'] as $value) {
                if(isset($product_id_tmp[$value['product_id']])){
                    return array('uid'=>$enter_arr['uid'],'tag'=>$enter_arr['tag'],'send_day'=>$enter_arr['send_day']);
                }
            }
        }
        return false;
    }

    /*
    *企业订单初始化
    */
    function init_enterprise_order($order_id){
        $data = array(
            'use_jf'=>0,
            'jf_money'=>0,
            'use_card'=>'',
            'card_money'=>0,
            'address_id'=>'',
            'shtime'=>'',
            'stime'=>''
        );
        $this->db->where('id',$order_id);
        $this->db->update('order', $data);
        return true;
    }

    /*
    *获取优惠券积分使用情况
    */
    function get_card_jf($id){
        $this->db->select("use_jf,use_card,pay_discount,new_pay_discount,jf_money,card_money");
        $this->db->from("order");
        $this->db->where(array("id"=>$id));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result;
     }

    /*
    *重置积分
    */
    function init_order_jf($order_id){
        $data = array(
            'use_jf'=>0,
            'jf_money'=>0,
        );
        $this->db->where('id',$order_id);
        $this->db->update('order', $data);
        return true;
     }

     /*
     *重置优惠券
     */
     function init_order_card($order_id){
        $data = array(
            'use_card'=>'',
            'card_money'=>0,
        );
        $this->db->where('id',$order_id);
        $this->db->update('order', $data);
        return true;
     }

     /*
     *查看订单信息根据ID
     */
     function getInfoById($id){
        $this->db->select('*');
        $this->db->from('order');
        $this->db->where('id',$id);
		$order_info = $this->db->get()->row_array();
        return $order_info;
     }

     /*
     *重置余额抵扣
     */
    function deduction_init($order_id){
        $this->db->where("id",$order_id);
        $this->db->update("order",array("use_money_deduction" =>0));
        return true;
    }

    /*
     *重置配送时间
     */
    function sendtime_init($order_id,$shtime='',$stime=''){
        $this->db->where("id",$order_id);
        $this->db->update("order",array("shtime" =>$shtime,"stime"=>$stime));
        return true;
    }

    //重置支付折扣,支付方式
    function init_pay_discount($order_id){
        $this->db->where("id",$order_id);
        $this->db->update("order",array("new_pay_discount" =>0,"pay_parent_id"=>'',"pay_id"=>''));
        return true;
    }

    //重置支付方式
    function init_pay($order_id){
        $this->db->where("id",$order_id);
        $this->db->update("order",array('pay_parent_id'=>'7','pay_id'=>'0','pay_name'=>'微信支付'));
        return true;
    }

    /*
    *获取订单地址
    */
    function get_order_address($address_id=''){
        $this->db->select('id,name,province,city,area,address,telephone,mobile,flag,is_default as isDefault');
        $this->db->from('user_address');
        $this->db->where('id',$address_id);
        $query = $this->db->get();
        $result = $query->result_array();
        foreach ($result as $key => $value) {
            $area_arr = array($value['province'],$value['city'],$value['area']);
            $this->db->select('id,name');
            $this->db->from('area');
            $this->db->where_in('id',$area_arr);
            $area_query = $this->db->get();
            $area_result = $area_query->result_array();
            $result[$key]['province'] = array(
                'id'=>isset($area_result[0]['id']) ? $area_result[0]['id'] : '',
                'name'=>isset($area_result[0]['name']) ? $area_result[0]['name'] : '',
            );
            $result[$key]['city'] = array(
                'id'=>isset($area_result[1]['id']) ? $area_result[1]['id'] : '',
                'name'=>isset($area_result[1]['name']) ? $area_result[1]['name'] : '',
            );
            $result[$key]['area'] = array(
                'id'=>isset($area_result[2]['id']) ? $area_result[2]['id'] : '',
                'name'=>isset($area_result[2]['name']) ? $area_result[2]['name'] : '',
            );
            if(empty($value['flag'])){
                $result[$key]['flag'] = '';
            }
        }
        return $result[0];
    }

    /*
    *格式化配送时间
    */
    function formateDateCopy($date){
        $str = "";
        $year = substr($date,0,4);
        $dat  = substr($date,4,2);
        $day  = substr($date,6,2);
        return $dat."-".$day;
    }

    /*
    *时间格式转化
    */
    function week($date){
        $d=date("w",strtotime($date));
        if($d>7){
            $d=$d-7;
        }
        if($d==0){
            return "周日";
        }else if($d==1){
            return "周一";
        }else if($d==2){
            return "周二";
        }else if($d==3){
            return "周三";
        }else if($d==4){
            return "周四";
        }else if($d==5){
            return "周五";
        }else if($d==6){
            return "周六";
        }else if($d==7){
            return "周日";
        }

    }

    /*
    *发票信息
    */
    function has_invoice($pay_parent=0, $pay_son=0) {
        $current_payment = $pay_parent;
        if ($pay_son) {
           $current_payment = $current_payment . '-' . $pay_son;
        }
        $payments = $this->config->item('no_invoice');
        return array_key_exists($current_payment, $payments) ? 0 : 1;
    }
    /*
    *初始化订单
    */
    function init_order($order_id,$cart_info=array()){
        $this->db_master->select("order_name,uid,pay_parent_id,pay_id,pay_name,use_card,use_jf,address_id,shtime,stime,pay_discount,new_pay_discount,fp,fp_dz");
        $this->db_master->from("order");
        $this->db_master->where(array("id"=>$order_id));
        $query=$this->db_master->get();
        $result=$query->row_array();
        if($result['uid']==803007 || $result['uid']==4952564  || $result['uid']==332208 || $result['uid']==2278648){
           $this->load->library('fdaylog');
           $db_log = $this->load->database('db_log', TRUE);
           $this->fdaylog->add($db_log,'order_send_time','2: '.json_encode($result));
        }
        $shtime = $result['shtime'];
        $stime = $result['stime'];
        $this->load->bll('region');
        if(empty($cart_info)){
            $this->load->bll('cart',$this->cart_bll_params);
            if($this->region_id) $this->bll_cart->set_province($this->region_id);
            $cart_info = $this->bll_cart->get_cart_info();
        }
        $uid = $result['uid'];
        /*不合法的配送时间重置start*/
        $address_id = '';
        if($result['address_id']){
            $send_date_tmp = explode('-', $shtime);
            $this->load->model('region_model');
            $check = $this->region_model->checkUserAddr($result['address_id']);
            $user_address_info = $this->region_model->get_province_id($result['address_id'],$result['uid']);
            if(empty($user_address_info) || $check == false){
                $this->db->where('id',$order_id);
                $this->db->update('order',array('address_id'=>''));
                $reset_shtime = true;
            }else{
                $address_id = $result['address_id'];
                //$area_info = $this->region_model->get_area_info($user_address_info['area']);
                //$cut_off_time = $area_info['cut_off_time'];
                //$cut_off_time_m = $area_info['cut_off_time_m'];
                $reset_shtime = false;
                //$h = date('H');
                $must_zj = false;
                if($result['pay_parent_id'] && $result['pay_parent_id']==4 && $result['pay_id'] && in_array($result['pay_id'], array(7,8,9))){
                    $must_zj = true;
                }
                $reset_shtime = $this->region_model->checkResetShtime($user_address_info['area'],$send_date_tmp[0],$stime,$user_address_info['province'],$must_zj,$address_id);
                // if($cut_off_time_m>0){
                //     if($h>=$cut_off_time_m && $send_date_tmp[0]==date('Ymd')){
                //         $reset_shtime = true;
                //     }elseif($h>=$cut_off_time && $send_date_tmp[0]==date('Ymd',strtotime("+1 day")) && in_array($stime, array('0918','0914','1418'))){
                //         $reset_shtime = true;
                //     }
                //     if(strcmp($send_date_tmp[0], date('Ymd')) < 0){
                //         $reset_shtime = true;
                //     }
                //     if($send_date_tmp[0]==date('Ymd') && in_array($stime, array('0918','0914','1418'))){
                //         $reset_shtime = true;
                //     }
                //     if($send_date_tmp[0]==date('Ymd') && in_array($stime, array('weekday','weekend','all')) ){
                //         $reset_shtime = true;
                //     }
                // }else{
                //     if($h>=$cut_off_time && $send_date_tmp[0]==date('Ymd',strtotime("+1 day"))){
                //         $reset_shtime = true;
                //     }
                //     if(strcmp($send_date_tmp[0], date('Ymd')) <= 0){
                //         $reset_shtime = true;
                //     }
                // }
                if($this->check_offline_sendtime($result['uid']) && !in_array($stime, array('weekday','weekend','all'))){
                    $reset_shtime = true;
                }
                $order_address = $this->get_order_address($result['address_id']);
                $data['order_address'] = $order_address;
            }
            if($adv_send_time=$this->check_advsale_sendtime($result['uid'],$cart_info)){
                if(date('Ymd',strtotime($adv_send_time)) > $shtime){
                    $reset_shtime = true;
                }
            }


            $pro_send = $this->bll_region->check_cart_pro_send_time($cart_info);
            $check_result = $pro_send['last_date'];
            $validity = $pro_send['validity'];
            if($check_result == 'after2to3days'){    //2到3天
                if(!in_array($stime, array('weekday','weekend','all'))){
                    $reset_shtime = true;
                }
            }else{                                  //最早送货时间
                $diff_day = round((strtotime($check_result)-strtotime(date('Ymd')))/3600/24);
                if($diff_day<0)
                    $diff_day = 0;
            }
            if($diff_day && $send_date_tmp[0] < date('Ymd',strtotime("+ ".$diff_day." day"))){
                $reset_shtime = true;
            }
            if($validity && $send_date_tmp[0] > date('Ymd',strtotime("+ ".($validity-1)." day"))){
                $reset_shtime = true;
            }
        }else{
            $reset_shtime = true;
        }
        if($reset_shtime){
            $this->db->where(array("id"=>$order_id));
            $this->db->update("order",array('shtime'=>'','stime'=>''));
            $shtime = '';
            $stime = '';
        }
    //if( $this->xsh_filter( $cart_arr ) )
       //$stime = "下单后3天内送达";
        $data['order_name'] = $result['order_name'];
        /*不合法的配送时间重置end*/


        $formateDate = $this->format_send_date($shtime,$stime);
        $data['shtime'] = $formateDate['shtime'];
        $data['stime'] = $formateDate['stime'];
        $data['address_id']=$address_id;

        //app 升级版本特殊处理
        if($result['pay_parent_id'] == 0)
        {
            $this->db->where(array("id"=>$order_id));
            $this->db->update("order",array('pay_parent_id'=>'7','pay_id'=>'0','pay_name'=>'微信支付'));
            $result['pay_parent_id'] = 7;
        }

        $has_invoice = 0;
        if($result['pay_parent_id']){
            $pay_array  =  $this->config->item("pay_array");
            $parent=$pay_array[$result['pay_parent_id']]['name'];
            $son=$result['pay_parent_id']==1?"":$pay_array[$result['pay_parent_id']]['son'][$result['pay_id']];
            $payment_son_id = $result['pay_parent_id'] == 1 ? '' : $result['pay_id'];

            if($result['pay_parent_id']==10 || !isset($pay_array[$result['pay_parent_id']]) || (!empty($pay_array[$result['pay_parent_id']]['son']) && !isset($pay_array[$result['pay_parent_id']]['son'][$result['pay_id']]))){
                $this->db->where(array("id"=>$order_id));
                $this->db->update("order",array('pay_parent_id'=>'7','pay_id'=>'0','pay_name'=>'微信支付'));
                $result['pay_parent_id'] = '7';
                $result['pay_id'] = '0';
                $result['pay_name'] = '微信支付';
            }

            if(isset($user_address_info['province'])){
                $area_refelect = $this->config->item("area_refelect");
                if(!in_array($user_address_info['province'], $area_refelect['1']) && $result['pay_parent_id']==4){
                    $this->db->where(array("id"=>$order_id));
                    $this->db->update("order",array('pay_parent_id'=>'7','pay_id'=>'0','pay_name'=>'微信支付'));
                    $result['pay_parent_id'] = '7';
                    $result['pay_id'] = '0';
                    $result['pay_name'] = '微信支付';
                }
            }

            if($parent!=""){
                $has_invoice = $this->has_invoice($result['pay_parent_id'], $payment_son_id);
            }
            if($parent == '账户余额支付') {
                $has_invoice = 0;
            }

        }
        $data['order_id'] = $order_id;
        $data['uid'] = $result['uid'];
        $data['pay_parent_id'] = $result['pay_parent_id'];
        $data['pay_id'] = $result['pay_id'];
        $data['pay_name'] = $result['pay_name'];
        $data['has_invoice'] = $has_invoice;
        $no_invoice_message = '';
        if(!$has_invoice){
            $no_invoice_message = '您选择的支付方式不支持开发票';
        }

        if (in_array($user_address_info['province'], array(1, 54351, 106340, 106092))) {
//            $data['support_einvoice'] = 1;
            $data['support_einvoice'] = 0;
        } else {
            $data['support_einvoice'] = 0;
        }

        $data['no_invoice_message'] = $no_invoice_message;
        $data['use_card']=$result['use_card'];
        $data['use_jf']=$result['use_jf'];
        //支付方式减免todo
    // if($result['pay_parent_id']=='3'){
 //          $data['pay_discount_money'] = 10;
 //        }else{
 //          $data['pay_discount_money'] = 0;
 //        }
        $data['pay_discount'] = $result['pay_discount'];
        $data['new_pay_discount'] = $result['new_pay_discount'];

        /*发票信息start*/
        $data['fp'] = $result['fp'];
        if(!empty($result['fp_dz'])){
            $this->db->from('order_invoice');
            $this->db->where('order_id',$order_id);
            $invoice_result = $this->db->get()->row_array();
            if(!empty($invoice_result)){
                $this->db->select('id,name');
                $this->db->from('area');
                $this->db->where_in('id',array($invoice_result['province_id'],$invoice_result['city_id'],$invoice_result['area_id']));
                $area_info = $this->db->get()->result_array();
                if(!empty($area_info) && count($area_info)==3){

                }else{
                    $data = array(
                        'fp'=>'',
                        'fp_dz'=>'',
                        'invoice_money'=>0,
                    );
                    $where = array(
                        'id'=>$order_id
                    );
                    $this->update_order($data,$where);
                    $this->delete_order_invoice($order_id);
                    $data['fp'] = '';
                }
            }
        }
        /*发票信息end*/

        return $data;
     }

     function format_send_date($shtime,$stime){
        $result = array();
        if(empty($shtime)){
            $result['shtime'] = '';
        }elseif($shtime=='after2to3days'){
            $result['shtime']['after2to3days'] = '下单后3天内送达';
        }elseif($shtime=='after1to2days'){
            $result['shtime']['after1to2days'] = '下单后2天内送达';
        }else{
            $result['shtime'] = array($shtime=>$this->formateDateCopy($shtime).'|'.$this->week($shtime));
        }
        $stime_value = '';
        switch ($stime) {
            case '0918':
                $stime_value = '09:00-18:00';
                break;
            case '0914':
                $stime_value = '09:00-14:00';
                break;
            case '1418':
                $stime_value = '14:00-18:00';
                break;
            case '1822':
                $stime_value = '18:00-22:00';
                break;
            case 'weekday':
                $stime_value = '仅在工作日配送';
                break;
            case 'weekend':
                $stime_value = '仅在双休日、假日配送';
                break;
            case 'all':
                $stime_value = '工作日、双休日与假日均可配送';
                break;
            default:
                $stime_value = $stime;
                break;
        }

        $result['stime'] = empty($stime)?'':array($stime=>$stime_value);
        return $result;
     }

     /*
     *获取订单发票信息
     */
     function get_invoice_info($order_id){
        $this->db->select('fp,fp_dz');
        $this->db->from('order');
        $this->db->where('id',$order_id);
        $result = $this->db->get()->row_array();
        $invoice_info = array(
            'invoice_type'=>1,
            'invoice_username'=>'个人',
            'invoice_address_type'=>1,
            'invoice_address'=>'使用收货地址',
            'invoice_mobile'=>'',
            'invoice_name'=>'',
            'invoice_province_key'=>'',
            'invoice_province'=>'',
            'invoice_city_key'=>'',
            'invoice_city'=>'',
            'invoice_area_key'=>'',
            'invoice_area'=>'',
        );

        if(empty($result)){
            return $invoice_info;
        }

        if(!empty($result['fp']) && $result['fp']!='个人'){
            $invoice_info['invoice_type'] = 2;
            $invoice_info['invoice_username'] = $result['fp'];
        }

        if(!empty($result['fp_dz'])){
            $this->db->from('order_invoice');
            $this->db->where('order_id',$order_id);
            $invoice_result = $this->db->get()->row_array();
            if(!empty($invoice_result)){
                $this->db->select('id,name');
                $this->db->from('area');
                $this->db->where_in('id',array($invoice_result['province_id'],$invoice_result['city_id'],$invoice_result['area_id']));
                $area_info = $this->db->get()->result_array();
                if(!empty($area_info) && count($area_info)==3){
                    $invoice_info['invoice_address_type'] = 2;
                    $invoice_info['invoice_address'] = $invoice_result['address'];
                    $invoice_info['invoice_mobile'] = $invoice_result['mobile'];
                    $invoice_info['invoice_name'] = $invoice_result['name'];
                    $invoice_info['invoice_province_key'] = $invoice_result['province_id'];
                    $invoice_info['invoice_province'] = $invoice_result['province'];
                    $invoice_info['invoice_city_key'] = $invoice_result['city_id'];
                    $invoice_info['invoice_city'] = $invoice_result['city'];
                    $invoice_info['invoice_area_key'] = $invoice_result['area_id'];
                    $invoice_info['invoice_area'] = $invoice_result['area'];
                }else{
                    $data = array(
                        'fp_dz'=>'',
                        'invoice_money'=>0,
                    );
                    $where = array(
                        'id'=>$order_id
                    );
                    $this->update_order($data,$where);
                    $this->delete_order_invoice($order_id);
                }
            }
        }
        return $invoice_info;
     }

     /*
     *获取发票抬头列表
     */
     function get_invoice_title_list($uid){
        $this->db->distinct();
        $this->db->select('fp');
        $this->db->from('order');
        $this->db->where(array('uid'=>$uid,'order_status'=>'1','fp !='=>'','fp !='=>'个人'));
        $result = $this->db->get()->result_array();
        return $result;
     }

    /*
    *获取发票抬头列表
    */
    function get_fpIdNo_list($uid, $fp) {
        $this->db->distinct();
        $this->db->select('fp_id_no');
        $this->db->from('order');
        $this->db->where(array('uid' => $uid, 'fp' => $fp, 'fp_id_no is not null'=>null));
        $result = $this->db->get()->result_array();
        return $result;
    }

     /*
     *地址准确性验证
     */
     function check_addr($params){
        $this->db->select('pid');
        $this->db->from('area');
        $this->db->where(array('id'=>$params['area']));
        $query = $this->db->get();
        $result=$query->row_array();
        if(isset($result['pid']) && $result['pid'] == $params['city']){
            $this->db->select('pid');
            $this->db->from('area');
            $this->db->where(array('id'=>$params['city']));
            $query = $this->db->get();
            $result=$query->row_array();
            if(isset($result['pid']) && $result['pid'] == $params['province']){

            }else{
                return array('code'=>'300','msg'=>'地区信息错误,请重新选择');
            }
        }else{
            return array('code'=>'300','msg'=>'地区信息错误,请重新选择');
        }
        $area_info_arr = array($params['province'],$params['city'],$params['area']);
        $this->db->from('area');
        $this->db->where_in('id',$area_info_arr);
        $this->db->where('active','1');
        $area_query = $this->db->get();
        $area_num = $area_query->num_rows();

        if($area_num<3){
            return array('code'=>'300','msg'=>'您选择的区域暂时不支持配送，当前可配送区域请查看配送说明');
        }
        return true;
    }

    /*
    *插入收货地址
    */
    function add_user_address($data){
        if($data['is_default']=='1'){
            $this->db->where('uid',$data['uid']);
            $this->db->update('user_address',array('is_default'=>'0'));
        }

        $this->db->insert('user_address', $data);
        return $this->db->insert_id();
    }

    /*
    *插入收货地址
    */
    function get_user_address($where){
        if(!empty($where)){
            $this->db->where($where);
        }
        $this->db->from('user_address');
        $query = $this->db->get();
        $result = $query->row_array();
        return $result;
    }

    /*
    *更新收货地址
    */
    function update_user_address($data,$where){
        if($data['is_default']=='1'){
            $this->db->where('uid',$where['uid']);
            $this->db->update('user_address',array('is_default'=>'0'));
        }

        $this->db->where($where);
        $this->db->update('user_address', $data);
        return true;
    }

    /*
    *删除收货地址
    */
    function delete_user_address($where){
        $address_ids = $where['address_id'];
        $this->db->where_in('id',explode(',', $address_ids));
        $this->db->where('uid',$where['uid']);
        $this->db->delete('user_address');
        return true;
    }

    /*
    *更新订单
    */
    function update_order($data,$where,$op_log=array()){
        $this->_filter($where);
        $this->db->update('order', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        if(!empty($op_log)){
            $time  =   date("Y-m-d H:i:s");
            $this->db->insert("order_op",array('manage'=>$op_log['uid'],'operation_msg'=>$op_log['operation_msg'],'time'=>$time,'order_id'=>$op_log['order_id']));
        }
        return true;
    }

    function set_ordre_payment($pay_name,$pay_parent_id,$pay_id,$order_id){
        $sql = "update ttgy_order set pay_name='".$pay_name."',pay_parent_id=".$pay_parent_id.",pay_id='".$pay_id."',version=version+1 where id=".$order_id;
        $this->db->query($sql);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    /*
    *获取订单信息
    */
    function selectOrder($field,$where,$join=''){
        $where['other_msg !='] = 'thj';
        $this->db->select($field);
        $this->db->from('order');
        $this->db->where($where);
        if(!empty($join)){
            foreach($join as $val){
                $this->db->join($val['table'],$val['field'],$val['type']);
            }
        }
        $result = $this->db->get()->row_array();
        return $result;
    }

    /*
    *获取订单信息list
    */
    function selectOrderList($field,$where,$where_in='',$order_by='',$limit=10,$offset=0){
        $where['other_msg !='] = 'thj';
        $this->db->select($field);
        $this->db->from('order');
        $this->db->where($where);
        if(!empty($where_in)){
            foreach($where_in as $val){
               $this->db->where_in($val['key'],$val['value']);
            }
        }
        if($order_by){
            $this->db->order_by($order_by);
        }
        $this->db->limit($limit,$offset);
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
    *获取订单信息list
    */
    function countOrderList($field,$where,$where_in=''){
        $where['other_msg !='] = 'thj';
        $this->db->select($field);
        $this->db->from('order');
        $this->db->where($where);
        if(!empty($where_in)){
            foreach($where_in as $val){
               $this->db->where_in($val['key'],$val['value']);
            }
        }
        $result = $this->db->count_all_results();
		//$result = $this->db->get()->result_array();
        return $result;
    }

     /*
    *获取商品信息
    */
    function selectOrderProducts($field,$where='',$where_in='',$order='',$join=''){
        $this->db->select($field);
        $this->db->from('order_product');
        if(!empty($where)){
            $this->db->where($where);
        }
        if(!empty($where_in)){
            foreach($where_in as $val){
               $this->db->where_in($val['key'],$val['value']);
            }
        }
        if(!empty($order)){
            $this->db->order_by($order);
        }
        if(!empty($join)){
            foreach($join as $val){
                $this->db->join($val['table'],$val['field'],$val['type']);
            }
        }
        $result = $this->db->get()->result_array();
        return $result;
    }

     /*
    *订单可操作状态验证
    */
    public function check_cart_pro_status($cart_info){
        /*判断类型：
            或：有一件商品满足即可;
            和：需要所有商品满足
        */
        $result['card_limit'] = '0';//不能使用优惠券，或；
        $result['jf_limit'] = '0';//不能使用积分，或；
        $result['pay_limit'] = '0';//不能使用线下支付，或；
        $result['active_limit'] = '0';//不参加营销活动，或；
        $result['delivery_limit'] = '0';//只能2-3天送达，或；
        $result['group_limit'] = '0';//不能单独购买，和；
        $result['pay_discount_limit'] = '0';//不能参加支付折扣活动，和；
        $result['free'] = '0';//是企业专享订单，或；
        $result['offline'] = '0';//是线下订单,或；
        $result['type'] = '1';//包含生鲜商品，或；
        $result['free_post'] = '0';//官网包邮，或；
        $result['free_post'] = '0';//手机包邮，或；
        $result['ignore_order_money'] = '0';//无视起送规则，收取运费，或；
        $result['iscard'] = '0';//是否包含券卡，或；
        $result['expect'] = '0';//单独购买；

        $cart_count = 0;
        $group_limit_count = 0;
        $pay_discount_limit_count = 0;
        $card_limit_count = 0;
        $cmoney = 0;
        foreach ($cart_info['items'] as $key => $value) {
            $cart_count++;
            if($value['card_limit']=='1'){
                $card_limit_count++;
                $result['card_limit_pro'] = $value['name'];
            }
            if($value['jf_limit']=='1'){
                $result['jf_limit'] = '1';
                $result['jf_limit_pro'] = $value['name'];
            }
            if($value['pay_limit']=='1'){
                $result['pay_limit'] = '1';
                $result['pay_limit_pro'] = $value['name'];
            }
            if($value['active_limit']=='1'){
                $result['active_limit'] = '1';
                $result['active_limit_pro'] = $value['name'];
            }
            if($value['delivery_limit']=='1'){
                $result['delivery_limit'] = '1';
                $result['delivery_limit_pro'] = $value['name'];
            }
            if($value['group_limit']=='1'){
                $group_limit_count++;
            }
            if($value['pay_discount_limit']=='1'){
                $pay_discount_limit_count++;
            }
            if($value['free']=='1'){
                $result['free'] = '1';
                $result['free_pro'] = $value['name'];
            }
            if($value['offline']=='1'){
                $result['offline'] = '1';
                $result['offline_pro'] = $value['name'];
            }
            if($value['type']=='4'){
                $result['type'] = '2';
            }
            if($value['free_post']=='1'){
                $result['free_post'] = '1';
                $result['free_post_pro'] = $value['name'];
            }
            if($value['free_post']=='1'){
                $result['free_post'] = '1';
                $result['free_post_pro'] = $value['name'];
            }
            if($value['ignore_order_money']=='1'){
                $result['ignore_order_money'] = '1';
                $result['ignore_order_money_pro'] = $value['name'];
            }
            if($value['iscard']=='1'){
                $result['iscard'] = '1';
                $result['iscard_pro'] = $value['name'];
                $cmoney += $value['amount'];
            }
            if($value['expect']=='1'){
                $result['expect'] = '1';
            }
        }

        //当预售商品为购物车单独商品时，可以下单
        if($cart_count==1){
            $result['expect'] = 0;
        }

        if($cart_count==$group_limit_count){
            $result['group_limit'] = '1';
        }
        if($cart_count==$pay_discount_limit_count){
            $result['pay_discount_limit'] = '1';
        }
        // if($cart_count==$card_limit_count){
        if($card_limit_count>0){
            $result['card_limit'] = '1';
        }
        $result['cmoney'] = $cmoney;
        return $result;
    }

    function get_kjt_order_num($product_id){
            $where = array(
                    'order.order_status'=>1,
                    'order.operation_id !='=>5,
                    'order_product.product_id'=>$product_id,
                    'order.time >='=>date('Y-m-d 00:00:00'),
                    'order.time <='=>date('Y-m-d 59:59:59')
            );
            $this->db->select('order.id');
            $this->db->from('order');
            $this->db->join('order_product','order.id=order_product.order_id');
            $this->db->where($where);
            $res = $this->db->get()->result_array();
            return empty($res) ? 0 : count($res);
    }

    //获取团购订单数
    function get_group_order_num($product_id){/*
        $where = array(
            'order.order_status'=>1,
            'order.operation_id !='=>5,
            'order.order_type'=>7,
            'order_product.product_id'=>$product_id,
            'order.time >='=>'2015-11-17 00:00:00',
//            'order.time <='=>date('Y-m-d 59:59:59')
        );
        $this->db->select('order.id');
        $this->db->from('order');
        $this->db->join('order_product','order.id=order_product.order_id');
        $this->db->where($where);
        $res = $this->db->get()->result_array();
        return empty($res) ? 0 : count($res);
*/
$sql = "SELECT count(`ttgy_order`.`id`) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order`.`id`=`ttgy_order_product`.`order_id` WHERE `ttgy_order`.`order_status` = 1 AND `ttgy_order`.`order_type` = 7 AND `ttgy_order_product`.`product_id` = ".$product_id." AND `ttgy_order`.`time` >= '2015-11-17 00:00:00'";
$query = $this->db->query($sql);
$res = $query->row_array();
return $res['num'];
    }

    /*
    *设备号验证
    */
    function check_device($cart_info,$device_code=''){
        if(!empty($cart_info) && $device_code!=''){
            $product_ids = array();
            foreach ($cart_info['items'] as $key => $value) {
                $product_ids[] = $value['product_id'];
            }
            $this->db->select('id,product_name,is_xsh_time_limit');
            $this->db->from('product');
            $this->db->where('device_limit','1');
            $this->db->where_in('id',$product_ids);
            $result = $this->db->get()->result_array();
            if(!empty($result)){
                $device_product_id = array();
                foreach ($result as $key => $value) {
//                    if( $value['id']==8043 || $value['id']==7846 || $value['id']==7883 || $value['id']==8037 || $value['id'] == 7878 || $value['id'] == 6952 || $value['id'] == 8229 || $value['id'] ==8289 || $value['id'] ==8311 || $value['id'] == 8535 || $value['id'] == 8467 || $value['id'] == 8759 || $value['id'] == 3155 || $value['id'] == 8937 || $value['id'] == 8938 || $value['id'] == 9001 || $value['id'] == 9002 || $value['id'] == 9217 || $value['id'] == 9029){   //每人每天限购lusctodo，云南冰糖橙 8037

                    $special_pids = array(9715,9720,7878,9612,9886,9950,9951,10124,10154,10254,10253,10378,10440,10439,10518,10519,10520);//每人每天限购
                    if(in_array($value['id'],$special_pids)||$value['is_xsh_time_limit']==0){
                        $this->db->select('order.operation_id');
                        $this->db->from('device_limit');
                        $this->db->join('order','order.id=device_limit.order_id');
                        $this->db->where(array('device_limit.product_id'=>$value['id'],'device_limit.device_code'=>$device_code,'order.time >='=>date('Y-m-d 00:00:00'),'order.time <='=>date('Y-m-d 59:59:59')));
                        $device_limit_check_result = $this->db->get()->row_array();
                        if(!empty($device_limit_check_result) && $device_limit_check_result['operation_id']!='5'){
                            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                                $this->load->library("memcached");
                                $yt_devlice_limit_cf_num = $this->memcached->get('yt_devlice_limit_cf_num')?$this->memcached->get('yt_devlice_limit_cf_num'):0;
                                $yt_devlice_limit_cf_num += 1;
                                $this->memcached->set('yt_devlice_limit_cf_num', $yt_devlice_limit_cf_num, '60*60*24*30');
// error_log($yt_devlice_limit_cf_num."\n\r",3,"/tmp/lsc.log");
                            }
                            return array("code"=>"300","msg"=>"您购买的".$value['product_name']."为活动商品，一个手机(设备)每天只能购买一次，请删除后重新提交订单");
                        }else{
                            $device_product_id[] = $value['id'];
                        }
                    }else{
                        $this->db->select('order.operation_id');
                        $this->db->from('device_limit');
                        $this->db->join('order','order.id=device_limit.order_id');
                        $this->db->where(array('device_limit.product_id'=>$value['id'],'device_limit.device_code'=>$device_code));
                        $device_limit_check_result = $this->db->get()->row_array();
                        if(!empty($device_limit_check_result) && $device_limit_check_result['operation_id']!='5'){
                            return array("code"=>"300","msg"=>"您购买的".$value['product_name']."为活动商品，一个手机(设备)只能购买一次，请删除后重新提交订单");
                        }else{
                            $device_product_id[] = $value['id'];
                        }
                    }
                }
                if(!empty($device_product_id)){
                    return array("code"=>"200","msg"=>serialize($device_product_id));
                }
            }
        }
    }

       /**
     * 订单明细积分
     *
     * @return void
     * @author
     **/
    public function get_order_product_score($uid,$cart_item){
        $score = 0;
        $this->load->model('user_model');
        $user = $this->user_model->selectUser('user_rank',array('id'=>$uid));
        $user_rank = $user['user_rank'] ? $user['user_rank'] : 1;
        $score = $this->user_model->cal_rank_score($cart_item['price'],$user_rank,$msg);
        return $score;
    }

    /*
    *新增订单商品
    */
    function addOrderProduct($insert_data) {
        $this->db->insert("order_product",$insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    /*
    *新增订单地址
    */
    function addOrderAddr($insert_data) {
        $this->db->insert("order_address",$insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    /*
    *用户赠品处理
    */
    function receive_user_gift($uid,$order_id,$user_gift_id){
        $this->db->select('active_type,active_id');
        $this->db->from('user_gifts');
        $this->db->where(array('id'=>$user_gift_id,'uid'=>$uid,'has_rec'=>0));
        $result = $this->db->get()->row_array();
        $user_gift_data = array(
            'has_rec'=>'1',
            'bonus_order'=>$order_id,
        );
        $this->db->where(array('id'=>$user_gift_id,'uid'=>$uid,'has_rec'=>0));
        $this->db->update('user_gifts', $user_gift_data);
        if(!$this->db->affected_rows()){
            return array("code"=>"300","msg"=>"赠品领取错误，请重新领取");
        }
        if($result['active_type']=='1'){
            $trade_gift_data = array(
                'has_rec'=>'1',
                'bonus_order'=>$order_id,
            );
            $this->db->where(array('id'=>$result['active_id'],'uid'=>$uid));
            $this->db->update('trade', $trade_gift_data);
            if(!$this->db->affected_rows()){
                return array("code"=>"300","msg"=>"赠品领取错误，请重新领取");
            }
        }
    }

    /*
    *插入订单发票信息
    */
    function add_order_invoice($order_id,$fp_info){
        $this->db->select('name');
        $this->db->from('area');
        $this->db->where_in('id',array($fp_info['fp_province'],$fp_info['fp_city'],$fp_info['fp_area']));
        $area_info = $this->db->get()->result_array();
        if(!empty($area_info) && count($area_info)==3){
            $this->db->from('order_invoice');
            $this->db->where('order_id',$order_id);
            $query = $this->db->get();
            if($query->num_rows() > 0){
                $data = array(
                        "name"=>$fp_info['fp'],
                        "address"=>$fp_info['fp_dz'],
                        "mobile"=>$fp_info['fp_mobile'],
                        "username"=>$fp_info['fp_name'],
                        "province"=>$area_info[0]['name'],
                        "city"=>$area_info[1]['name'],
                        "area"=>$area_info[2]['name'],
                        "province_id"=>$fp_info['fp_province'],
                        "city_id"=>$fp_info['fp_city'],
                        "area_id"=>$fp_info['fp_area'],
                    );
                $this->db->where('order_id',$order_id);
                $this->db->update('order_invoice', $data);
            }else{
                $this->db->insert(
                "order_invoice",
                array(
                        "order_id" => $order_id,
                        "name"=>$fp_info['fp'],
                        "address"=>$fp_info['fp_dz'],
                        "mobile"=>$fp_info['fp_mobile'],
                        "username"=>$fp_info['fp_name'],
                        "province"=>$area_info[0]['name'],
                        "city"=>$area_info[1]['name'],
                        "area"=>$area_info[2]['name'],
                        "province_id"=>$fp_info['fp_province'],
                        "city_id"=>$fp_info['fp_city'],
                        "area_id"=>$fp_info['fp_area'],
                    )
                );
            }
        }
    }

    /*
    *删除订单发票信息
    */
    function delete_order_invoice($order_id){
        $this->db->delete('order_invoice',array('order_id'=>$order_id));
    }

    //赠品图片
    function get_pro_gift_info_by_product_id($product_id,$product_no){
        $p_result = array();
        $this->load->model('product_model');
        if (!empty ($product_id)){
            $field = 'thum_photo photo';
            $where = array('id'=>$product_id);
            $result = $this->product_model->selectProducts($field,$where);
            $p_result = $result[0];
        }else if(!empty ($product_no)){
            $field = 'gift_photo photo';
            $where = array('gno'=>$product_no);
            $result = $this->product_model->selectProductGift($field,$where);
            $p_result = $result[0];
        }
        return $p_result['photo'];
    }

        //商品图片
    function get_pro_info_by_product_id($product_id){
        $this->load->model('product_model');
        $field = 'thum_photo';
        $where = array('id'=>$product_id);
        $result = $this->product_model->selectProducts($field,$where);
        $p_result = $result[0];

        return $p_result['thum_photo'];
    }

    function table_name(){
        return 'order';
    }

    //获取预生成订单地区
    function getIorderArea($order_id){
        $this->db->select('address_id');
        $this->db->from('order');
        $this->db->where("id",$order_id);
        $query = $this->db->get();
        $result = $query->row_array();
        $address_id = $result['address_id'];
        if($address_id){
            $this->db->select('uid,name,province,city,area,address,telephone,mobile');
            $this->db->from('user_address');
            $this->db->where("id",$address_id);
            $query = $this->db->get();
            $result = $query->row_array();
            return $result;
        }
        return false;
    }

     /**
     * [ckDistSurvey 验证订单问卷调查]
     * @param  [int] $sid       [问卷主题id]
     * @param  [int] $ordername [订单号]
     * @return [boolen]            [true:无记录false:已经存在]
     */
    function ckDistSurvey($ordername){
        $where = array('sid'=>1, 'remark'=>$ordername);
        $this->db->where($where);
        $this->db->from('survey_usercomp');
        $num = $this->db->count_all_results();
        if(empty($num)){
            return true;
        }else{
            return false;
        }
    }

    /*
    *获取门店电话
    */
    function getStoreTel($order_id){
        $sql = "select s.phone from ttgy_o2o_store as s join ttgy_o2o_order_extra as e on e.store_id=s.id join ttgy_order as o on o.id=e.order_id where o.id=".$order_id;
        $result = $this->db->query($sql)->row_array();
        if(empty($result)){
            return '';
        }else{
            return $result['phone'];
        }
    }

    /*
    *获取物流信息
    */
    function getLogisticInfo($order_name,$uid){
        $sql = 'select id,order_type from ttgy_order where order_name=?';
        $order = $this->db->query($sql, array($order_name))->row_array();
        if(!empty($order['order_type']) && ($order['order_type'] == 3 || $order['order_type'] == 4)){
            $sql = "select l.deliver_method,l.logi_no,l.deliver_name,l.deliver_mobile, unix_timestamp(l.delivertime) delivertime from ttgy_o2o_order_shipping as l join ttgy_o2o_child_order as o on o.id=l.order_id where l.deliver_method!='销售员自提' and o.uid=".$uid." and o.p_order_id='".$order['id']."'";
        }else{
            $sql = "select l.deliver_method,l.logi_no,l.deliver_name,l.deliver_mobile,l.delivertime from ttgy_order_shipping as l join ttgy_order as o on o.id=l.order_id where l.deliver_method!='销售员自提' and o.uid=".$uid." and o.order_name='".$order_name."'";
        }

        $result = $this->db->query($sql)->row_array();
        return $result;
    }

    /*
    *批量获取物流信息
    */
    function getLogisticInfoList($order_ids){
        $sql = 'select id,order_type from ttgy_order where id in (' . implode(',', $order_ids) . ')';
        $orders = $this->db->query($sql)->result_array();

        $result = array();
        $ids = array();
        $o2o_ids = array();
        foreach ($orders as $order) {
            if($order['order_type'] == 3 || $order['order_type'] == 4){
                array_push($o2o_ids, $order['id']);
            }else{
                array_push($ids, $order['id']);
            }
        }

        if(!empty($o2o_ids)){
            $sql = "select o.p_order_id as id from ttgy_o2o_order_shipping as l join ttgy_o2o_child_order as o on o.id=l.order_id where l.deliver_method!='销售员自提' and o.p_order_id in (".implode(',', $o2o_ids).")";
            $rs = $this->db->query($sql)->result_array();
            if(!empty($rs)){
                $result = $rs;
            }
        }

        if(!empty($ids)){
            $sql = "select o.id from ttgy_order_shipping as l join ttgy_order as o on o.id=l.order_id where l.deliver_method!='销售员自提' and o.id in (".implode(',', $ids).")";
            $rs = $this->db->query($sql)->result_array();
            if(!empty($rs)){
                $result = array_merge($result, $rs);
            }
        }

        return $result;
    }

    /*
    *获取订单实名信息
    */
    function getOrderRecord($order_ids){
        $this->db->select('order_id,name,id_card_type,id_card_number,mobile,email');
        $this->db->from('kjt_order');
        $this->db->where_in('order_id',$order_ids);
        $result = $this->db->get()->result_array();
        return $result;
    }

    /*
    *获取电子发票
    */
    function getDzFp($order_names){
        $this->db->select('order_name,mobile');
        $this->db->from('order_einvoices');
        $this->db->where_in('order_name',$order_names);
        $result = $this->db->get()->result_array();
        return $result;
    }

 //    /*
 //     * 是否互斥
 //     * */
 //    function huchi($cart_arr,$uid){
 //        $tuan_tag = "20141225";
 //        $huchi_arr = $this->config->item('huchi');
 //        $count_aa = 0;
 //        if(!empty($cart_arr)){
 //            foreach( $cart_arr['items'] as $key=>$val)
 //            {
 //                if( in_array($val['product_id'], $huchi_arr) )
 //                {
 //                    $count_aa++;
 //                    $huchi_str = "'".implode("','",$huchi_arr)."'";
 //                    $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
 //                    $result = $this->db->query($sql)->row_array();
 //                    if($result['num']!=0){
 //                        return array('code' => '300', 'msg'=>'虽然小果想给您更多机会，但抢购商品限抢1款，您已经购买过抢购商品咯');
 //                    }
 //                }

 //    //                  if($val["is_tuan"]==1){
 //    //                      $this->db->select("t.is_tuan");
 //    //                      $this->db->from("tuan_member um");
 //    //                      $this->db->join("tuan t",'t.id = um.tuan_id');
 //    //                      $this->db->where("um.member_uid",$uid);
 //    //                      $this->db->where("um.product_id",3388);
 //    //                      $this->db->where("um.tag",$tuan_tag);
 //    //                      $query = $this->db->get();
 //    //                      $result = $query->result_array();
 //    //                      if($result[0]['is_tuan']==0||empty($result)){
 //    //                          return array('code' => '300', 'msg'=>$val['product_name'].'此商品为团购商品,您可以去我的果园→我的特权邀请好友参团,一旦成团即可购买此商品!');
 //    //                          exit;
 //    //                      }elseif($result[0]['is_tuan']==1&&$val["product_id"]=='3389'){
 //    //                          return array('code' => '300', 'msg'=>'此商品为团购商品,您的成团人数尚未满足条件!');
 //    //                          exit;
 //    //                      }
 //    //                  }
 //            }
 //            if($count_aa>1){
 //                return array('code' => '300', 'msg'=>'虽然小果想给您更多机会，但抢购商品限抢1款，您已经购买过抢购商品咯');
 //            }
 //        }
 //    }

 //    /*
 //     * 是否互斥
 //     * */
 //    function huchi1($cart_arr,$uid){
 //        $tuan_tag = "20141225";
 //        $huchi_arr = $this->config->item('huchi1');
 //        $count_aa = 0;
 //        if(!empty($cart_arr)){
 //            foreach( $cart_arr['items'] as $key=>$val)
 //            {
 //                if( in_array($val['product_id'], $huchi_arr) )
 //                {
 //                    $count_aa++;
 //                    $huchi_str = "'".implode("','",$huchi_arr)."'";
 //                    $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
 //                    $result = $this->db->query($sql)->row_array();
 //                    if($result['num']!=0){
 //                        return array('code' => '300', 'msg'=>'虽然果园君想给您更多机会 但特惠商品仅限购买1份哦!');
 //                    }
 //                }

 //                //                  if($val["is_tuan"]==1){
 //                //                      $this->db->select("t.is_tuan");
 //                //                      $this->db->from("tuan_member um");
 //                //                      $this->db->join("tuan t",'t.id = um.tuan_id');
 //                //                      $this->db->where("um.member_uid",$uid);
 //                //                      $this->db->where("um.product_id",3388);
 //                //                      $this->db->where("um.tag",$tuan_tag);
 //                //                      $query = $this->db->get();
 //                //                      $result = $query->result_array();
 //                //                      if($result[0]['is_tuan']==0||empty($result)){
 //                //                          return array('code' => '300', 'msg'=>$val['product_name'].'此商品为团购商品,您可以去我的果园→我的特权邀请好友参团,一旦成团即可购买此商品!');
 //                //                          exit;
 //                //                      }elseif($result[0]['is_tuan']==1&&$val["product_id"]=='3389'){
 //                //                          return array('code' => '300', 'msg'=>'此商品为团购商品,您的成团人数尚未满足条件!');
 //                //                          exit;
 //                //                      }
 //                //                  }
 //            }
 //            if($count_aa>1){
 //                return array('code' => '300', 'msg'=>'虽然果园君想给您更多机会 但特惠商品仅限购买1份哦!');
 //            }
 //        }
 //    }

 //    /*
 // * 是否互斥
 // * */
 //    function huchi3($cart_arr,$uid){
 //        $tuan_tag = "20141225";
 //        $huchi_arr = $this->config->item('huchi3');
 //        $count_aa = 0;
 //        if(!empty($cart_arr)){
 //            foreach( $cart_arr['items'] as $key=>$val)
 //            {
 //                if( in_array($val['product_id'], $huchi_arr) )
 //                {
 //                    $count_aa++;
 //                    $huchi_str = "'".implode("','",$huchi_arr)."'";
 //                    $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
 //                    $result = $this->db->query($sql)->row_array();
 //                    if($result['num']!=0){
 //                        return array('code' => '300', 'msg'=>'亲，单笔订单只能换购1份福袋哦');
 //                    }
 //                }

 //                //                  if($val["is_tuan"]==1){
 //                //                      $this->db->select("t.is_tuan");
 //                //                      $this->db->from("tuan_member um");
 //                //                      $this->db->join("tuan t",'t.id = um.tuan_id');
 //                //                      $this->db->where("um.member_uid",$uid);
 //                //                      $this->db->where("um.product_id",3388);
 //                //                      $this->db->where("um.tag",$tuan_tag);
 //                //                      $query = $this->db->get();
 //                //                      $result = $query->result_array();
 //                //                      if($result[0]['is_tuan']==0||empty($result)){
 //                //                          return array('code' => '300', 'msg'=>$val['product_name'].'此商品为团购商品,您可以去我的果园→我的特权邀请好友参团,一旦成团即可购买此商品!');
 //                //                          exit;
 //                //                      }elseif($result[0]['is_tuan']==1&&$val["product_id"]=='3389'){
 //                //                          return array('code' => '300', 'msg'=>'此商品为团购商品,您的成团人数尚未满足条件!');
 //                //                          exit;
 //                //                      }
 //                //                  }
 //            }
 //            if($count_aa>1){
 //                return array('code' => '300', 'msg'=>'亲，单笔订单只能换购1份福袋哦');
 //            }
 //        }
 //    }

 //    /*
 //     * 是否互斥
 //     * */
 //    function huchi2($cart_arr,$uid){
 //        $tuan_tag = "20141225";
 //        $huchi_arr = $this->config->item('huchi2');
 //        $count_aa = 0;
 //        if(!empty($cart_arr)){
 //            foreach( $cart_arr['items'] as $key=>$val)
 //            {
 //                if( in_array($val['product_id'], $huchi_arr) )
 //                {
 //                    $count_aa++;
 //                    $huchi_str = "'".implode("','",$huchi_arr)."'";
 //                    $sql = "SELECT sum(qty) as num FROM (`ttgy_order`) JOIN `ttgy_order_product` ON `ttgy_order_product`.`order_id`=`ttgy_order`.`id` WHERE `ttgy_order`.`uid` = '{$uid}' AND `ttgy_order`.`order_status` = '1' AND `ttgy_order`.`operation_id` != '5' AND (`ttgy_order_product`.`product_id` IN (".$huchi_str.") OR `group_pro_id` IN (".$huchi_str.")) ";
 //                    $result = $this->db->query($sql)->row_array();
 //                    if($result['num']!=0){
 //                        return array('code' => '300', 'msg'=>'亲，秒杀仅限秒杀1款商品哦 ，么么哒~');
 //                    }
 //                }

 //                //                  if($val["is_tuan"]==1){
 //                //                      $this->db->select("t.is_tuan");
 //                //                      $this->db->from("tuan_member um");
 //                //                      $this->db->join("tuan t",'t.id = um.tuan_id');
 //                //                      $this->db->where("um.member_uid",$uid);
 //                //                      $this->db->where("um.product_id",3388);
 //                //                      $this->db->where("um.tag",$tuan_tag);
 //                //                      $query = $this->db->get();
 //                //                      $result = $query->result_array();
 //                //                      if($result[0]['is_tuan']==0||empty($result)){
 //                //                          return array('code' => '300', 'msg'=>$val['product_name'].'此商品为团购商品,您可以去我的果园→我的特权邀请好友参团,一旦成团即可购买此商品!');
 //                //                          exit;
 //                //                      }elseif($result[0]['is_tuan']==1&&$val["product_id"]=='3389'){
 //                //                          return array('code' => '300', 'msg'=>'此商品为团购商品,您的成团人数尚未满足条件!');
 //                //                          exit;
 //                //                      }
 //                //                  }
 //            }
 //            if($count_aa>1){
 //                return array('code' => '300', 'msg'=>'亲，秒杀仅限秒杀1款商品哦，么么哒~');
 //            }
 //        }
 //    }

    /*
    * 是否互斥
    * */
    function fan($cart_arr,$uid){
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
                    if($count_aa>1){
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

    /*
    *获取晚单数量
    */
    function getNightOrderNum($cang_ref){
        $order_time_reg = date("Y-m-d 00:00:00", strtotime("-5 day"));
        $sql = "select count(id) as num,date_format(send_date,'%Y%m%d') as shtime,stime from ttgy_order where order_status=1 and operation_id!=5 and shtime!='19700101' and time>='".$order_time_reg."' and cang_id = ".$cang_ref['cang_id']." and deliver_type = ".$cang_ref['deliver_type']." and order_type not in (3,4) and send_date!='' and send_date!='0000-00-00'  group by send_date,stime";
        $order_time_limit_result_tmp = $this->db->query($sql)->result_array();



          if(empty($order_time_limit_result_tmp)){
            return false;
          }

        $order_time_limit_result = array();

        foreach ($order_time_limit_result_tmp as $key => $value) {
                $order_time_limit_result[$value['shtime'].'_'.$value['stime']] = $value;
            }
            foreach ($order_time_limit_result as $key => $value) {
                if($value['stime']!='0918' && $value['stime']!='1822' && $value['stime']!='0914' && $value['stime']!='1418'){
                  if(isset($order_time_limit_result[$value['shtime'].'_0918'])){
                    $order_time_limit_result[$value['shtime'].'_0918']['num'] += $value['num'];
                  }else{
                    $value['stime'] = '0918';
                    $order_time_limit_result[$value['shtime'].'_0918'] = $value;
                  }
                  unset($order_time_limit_result[$key]);
                }
            }




        return $order_time_limit_result;
    }


     /*
    *提交订单短信验证
    */
    public function checkSendCode($items, $uid,$pay_parent_id=0,$order_address=array(), $order_id = 0) {
        $return_result = false;
        /*支付方式需要验证码&历史地址判断start*/
        if($pay_parent_id == 5){
            $return_result = true;
            if(empty($order_id)){
                if(!empty($order_address)){
                    $order_address['address'] = addslashes($order_address['address']);
                    $sql = "select count(o.id) as num from ttgy_order as o join ttgy_order_address as a on a.order_id=o.id where o.uid=".$uid." and a.address='".$order_address['province']['name'].$order_address['city']['name'].$order_address['area']['name'].$order_address['address']."' and a.mobile='".$order_address['mobile']."' and o.operation_id in (3,9)";
                    $order_address_num = $this->db->query($sql)->row_array();
                    if(!empty($order_address_num) && $order_address_num['num']>=1){
                        $return_result = false;
                    }
                }
            }else{
                $addr = $this->db->query('select address,mobile from ttgy_order_address where order_id=?', array($order_id))->row_array();
                $sql = "select count(o.id) as num from ttgy_order as o join ttgy_order_address as a on a.order_id=o.id where o.uid=".$uid." and a.address=? and a.mobile=? and o.operation_id in (3,9)";
                $order_address_num = $this->db->query($sql, array($addr['address'], $addr['mobile']))->row_array();
                if(!empty($order_address_num) && $order_address_num['num']>=1){
                    $return_result = false;
                }
            }

        }
        /*支付方式需要验证码&历史地址判断end*/

        //组合支付－增加短信验证
        if(!empty($order_id))
        {
            $orderInfo = $this->getInfoById($order_id);
            if($orderInfo['use_money_deduction'] >0)
            {
                $return_result = true;
                $addr = $this->db->query('select address,mobile from ttgy_order_address where order_id=?', array($order_id))->row_array();
                $sql = "select count(o.id) as num from ttgy_order as o join ttgy_order_address as a on a.order_id=o.id where o.uid=".$uid." and a.address=? and a.mobile=? and o.operation_id in (3,9)";
                $order_address_num = $this->db->query($sql, array($addr['address'], $addr['mobile']))->row_array();
                if(!empty($order_address_num) && $order_address_num['num']>=1){
                    $return_result = false;
                }
            }
        }


        /*强制发送验证码商品判断start*/
        $need_send_code_pro  =  $this->config->item("need_send_code_pro");
        if (!empty($items) && !empty($need_send_code_pro)) {
            foreach ($items as $key => $value) {
                if( in_array($value['product_id'], $need_send_code_pro) ){
                    $return_result = true;
                    break;
                }
            }
        }
        /*强制发送验证码商品判断end*/

        /*白名单start*/
        if($uid==5185553 || $uid==504884 || $uid==4643775 || $uid == 1727612 || $uid == 803007 ){
            $return_result = false;
        }
        /*白名单end*/
        return $return_result;
    }

    /*
    *安卓渠道统计
    */
    function order_channel_tj($order_id,$channel){
        if(!empty($channel)){
            $data = array(
                'order_id'=>$order_id,
                'channel'=>$channel
            );
            $this->db->insert('order_channel_tj',$data);
        }
    }

    function checkpf0823($product_id,$uid=0){
        if(in_array($product_id, array(5345,5346,5347))){
            $today = date('Y-m-d',time());
            $end_today = date('Y-m-d 23:59:59',time());
            $sql = "select o.id from ttgy_order o join ttgy_order_product p on o.id=p.order_id where o.uid=".$uid." and o.order_status=1 and o.operation_id<>5 and p.product_id in(5345,5346,5347) and o.time>='".$today."' and o.time<='".$end_today."'";
            $result = $this->db->query($sql)->result_array();
            if($result){
                return false;
            }else{
                return true;
            }
        }
        return true;
    }

    /*
    *   删除电子发票
    */
    function delete_DzFp($order_id){
        $this->db->delete('order_einvoices',array('order_name'=>$order_id));
    }

    /*
    *   更新电子发票
    */
    function update_DzFp($data,$where,$op_log=array()){
        $this->db->where($where);
        $this->db->update('order_einvoices', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        if(!empty($op_log)){
            $time  =   date("Y-m-d H:i:s");
            $this->db->insert("order_op",array('manage'=>$op_log['uid'],'operation_msg'=>$op_log['operation_msg'],'time'=>$time,'order_id'=>$op_log['order_id']));
        }
        return true;
    }

    /*
    *   添加电子发票
    */
    function add_DzFp($order_id,$mobile){
        if(!empty($order_id) && !empty($mobile)){
            $data = array(
                'order_name'=>$order_id,
                'mobile'=>$mobile
            );
            $this->db->insert('order_einvoices',$data);
        }
        return true;
    }

    /*
    *获取历史操作订单信息 -- pc
    */
    function get_pc_order_id($uid){
        $this->db_master->select("id");
        $this->db_master->from("order");
        $this->db_master->where(array("uid"=>$uid));
        $this->db_master->order_by("time","desc");
        $this->db_master->limit(1);
        $query=$this->db_master->get();
        $result=$query->row_array();
        if(isset($result['id'])) {
            $order_id = intval($result['id']);
            return $order_id;
        }else{
            return false;
        }
    }

    //申诉订单
    function billComplaintsList($params,$uid){

        $where = '';
        if ($params['order_name']) {
            $where.=" AND q.ordername='".$params['order_name']."'";
        }

        $where1 = '';
        if ($params['status'] == 3) {
            $where1.=" AND q.status=0 ";
        } elseif ($params['status'] ==1) {
            $where1.= " AND q.status IN (1,2) ";
        }

        $page = intval($params['page'])<=0 ? 1 : intval($params['page']);
        $pagesize = intval($params['pagesize'])<=0 ? 100 : intval($params['pagesize']);

        // $sql = "SELECT q.ordername,q.product_id,q.product_no,oq.time FROM ttgy_quality_complaints AS q INNER JOIN ttgy_order AS oq ON q.ordername=oq.order_name
        //  WHERE q.uid=$uid ".$where.$where1." ORDER BY oq.time DESC LIMIT ".($page-1)*$pagesize.",$pagesize";
        $sql = "SELECT q.ordername,q.product_id,q.product_no,oq.time,r.has_refund FROM ttgy_quality_complaints AS q INNER JOIN ttgy_order AS oq ON q.ordername=oq.order_name LEFT JOIN ttgy_order_refund AS r on r.order_id = oq.id
         WHERE q.uid=$uid ".$where.$where1." ORDER BY oq.time DESC";

        $res = $this->db_master->query($sql)->result_array();

        if (empty($res)) {
            return array('code'=>200,'msg'=>'信息为空','data'=>array());
        }

        $statusDes = array('未处理','处理完成','处理中');
        $service_statusDes = array('不可以评价','可以评价','评价完成');

        $arr = array();

        foreach ($res as $key => $value) {
            $sql1 = "SELECT o.money,o.method_money,o.use_money_deduction,op.product_id,op.product_name,op.product_no,op.gg_name,op.price,op.total_money,qc.id,qc.time,qc.status,qc.service_status,p.thum_photo,p.template_id
                     FROM ttgy_quality_complaints AS qc INNER JOIN ttgy_order AS o ON o.order_name=qc.ordername
                     INNER JOIN ttgy_order_product AS op ON o.id=op.order_id
                     LEFT JOIN ttgy_product AS p ON p.id=op.product_id
                     WHERE qc.ordername='".$value['ordername']."' AND qc.product_id=".$value['product_id'].
                     " AND op.product_id=".$value['product_id']." AND op.product_no='".$value['product_no']."'";
            $res1 = $this->db_master->query($sql1)->row_array();

            // 获取产品模板图片
            if ($res1['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($res1['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $res1['thum_photo'] = $templateImages['main']['thumb'];
                }
            }
            $res1['thum_photo'] = 'http://cdn.fruitday.com/'.$res1['thum_photo'];
            $res1['statusDes'] = $statusDes[$res1['status']];
            $res1['service_statusDes'] = $service_statusDes[$res1['service_status']];

            $arr[$value['ordername']]['order_name'] = $value['ordername'];
            $versionCompare = version_compare($params['version'], '4.1.0');
            if($versionCompare >= 0 && $params['source'] == 'app'){
                $arr[$value['ordername']]['money'] = $res1['money'] + $res1['use_money_deduction'];
                $arr[$value['ordername']]['method_money'] = $res1['method_money'];
                unset($res1['money']);unset($res1['method_money']);unset($res1['use_money_deduction']);
            }
            $arr[$value['ordername']]['time'] = date('Y-m-d',strtotime($value['time']));
            $arr[$value['ordername']]['product'][] = $res1;
            $arr[$value['ordername']]['has_refund'] = $value['has_refund']?1:0;
        }
        $b = $bb = array();
        $b = array_chunk($arr, $pagesize);
        $bb = $b[$page-1] ? $b[$page-1] : array();

        return array('code'=>200,'msg'=>'成功','data'=>array_values($bb));

    }

    //申诉订单
    function billComplaintsListNew($params,$uid){
        if ($params['status'] == 3) {
            //可申请
            $this->db->select("o.money,o.method_money,o.use_money_deduction,o.id,o.order_name,o.time,o.order_type,ord.has_refund");
            $this->db->from("order o");
            $this->db->join('order_refund ord', 'o.id = ord.order_id','LEFT');
            $this->db->where(array("o.uid" => $uid));
            $this->db->where_in('operation_id', array(6, 9));
            $this->db->order_by("o.time","desc");

            $query=$this->db->get();
            $orderInfoGroup=$query->result_array();

            foreach($orderInfoGroup as $key => $orderInfo) {

                //获取商品详情
                $this->db->select("op.product_id,op.product_name,op.product_no,op.gg_name,op.price,op.total_money,op.type, p.thum_photo, p.template_id");
                $this->db->from("order_product op");
                $this->db->join('product p', 'p.id=op.product_id');
                $this->db->where(array("op.order_id" => $orderInfo['id'], 'op.type'=> 1));
                $query=$this->db->get();
                $productInfoGroup=$query->result_array();
//                $arr[$orderInfo['order_name']]['product'] = array();

                $this->db->select('product_id');
                $this->db->from('quality_complaints');
                $this->db->where(array('ordername'=> $orderInfo['order_name']));
                $query = $this->db->get();
                $hasQualityComplaintsProductGroup = $query->result_array();
                $hasQualityComplaintsProductIdGroup = array_column($hasQualityComplaintsProductGroup, 'product_id');

                foreach($productInfoGroup as $k=>$productInfo){
                    //商品已申请过
                    if(in_array($productInfo['product_id'], $hasQualityComplaintsProductIdGroup)){
                        continue;
                    }

                    // 获取产品模板图片
                    if ($productInfo['template_id']) {
                        $this->load->model('b2o_product_template_image_model');
                        $templateImages = $this->b2o_product_template_image_model->getTemplateImage($productInfo['template_id'], 'main');
                        if (isset($templateImages['main'])) {
                            $productInfo['thum_photo'] = $templateImages['main']['thumb'];
                        }
                    }
                    $productInfo['thum_photo'] = 'http://cdn.fruitday.com/' . $productInfo['thum_photo'];
                    $arr[$orderInfo['order_name']]['product'][] = $productInfo;
                }
                if(!empty($arr[$orderInfo['order_name']]['product'])){
                    $arr[$orderInfo['order_name']]['order_name'] = $orderInfo['order_name'];
                    $arr[$orderInfo['order_name']]['has_refund'] = $orderInfo['has_refund'] ? 1 : 0;
                    $arr[$orderInfo['order_name']]['order_type'] = $orderInfo['order_type'];
                    $versionCompare = version_compare($params['version'], '4.1.0');
                    if ($versionCompare >= 0 && $params['source'] == 'app') {
                        $arr[$orderInfo['order_name']]['money'] = $orderInfo['money'] + $orderInfo['use_money_deduction'];
                        $arr[$orderInfo['order_name']]['method_money'] = $orderInfo['method_money'];
                        unset($orderInfo['money']);
                        unset($orderInfo['method_money']);
                        unset($orderInfo['use_money_deduction']);
                    }
                    $arr[$orderInfo['order_name']]['time'] = date('Y-m-d', strtotime($orderInfo['time']));
                }


            }
            return array('code'=>200,'msg'=>'成功','data'=>array_values($arr));
        } elseif ($params['status'] ==1) {
            //已处理
            $this->db->select("qc.status,qc.id as qcid,qc.time,qc.ordername,o.id as oid,op.product_id,op.product_name,op.product_no,op.gg_name,op.price,op.total_money, p.thum_photo, ord.has_refund, p.template_id");
            $this->db->from("quality_complaints qc");
            $this->db->join('order o', 'o.order_name = qc.ordername','LEFT');
            $this->db->join('order_product op', 'op.order_id = o.id and qc.product_id = op.product_id','LEFT');
            $this->db->join('product p', 'qc.product_id = p.id','LEFT');
            $this->db->join('order_refund ord', 'ord.order_id = o.id','LEFT');
            $this->db->where(array("qc.uid" => $uid));
            $this->db->limit($params['pagesize'], ($params['page']-1) * $params['pagesize']);
            $this->db->order_by("qc.time","desc");
            $query=$this->db->get();
            $qualityComplaintsGroup = $query->result_array();


            foreach($qualityComplaintsGroup as $key =>$qualityComplaint){
                // 获取产品模板图片
                if ($qualityComplaint['template_id']) {
                    $this->load->model('b2o_product_template_image_model');
                    $templateImages = $this->b2o_product_template_image_model->getTemplateImage($qualityComplaint['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $qualityComplaint['thum_photo'] = $templateImages['main']['thumb'];
                    }
                }

                $arr[$qualityComplaint['qcid']]['id'] = $qualityComplaint['qcid'];
                $arr[$qualityComplaint['qcid']]['order_name'] = $qualityComplaint['ordername'];
                $arr[$qualityComplaint['qcid']]['product'][] = array('product_id' => $qualityComplaint['product_id'],
                                                'product_name' => $qualityComplaint['product_name'],
                                                'product_no' => $qualityComplaint['product_no'],
                                                'gg_name' => $qualityComplaint['gg_name'],
                                                'price' => $qualityComplaint['price'],
                                                'total_money' => $qualityComplaint['total_money'],
                                                'thum_photo' => 'http://cdn.fruitday.com/' . $qualityComplaint['thum_photo'],
                );
                $arr[$qualityComplaint['qcid']]['has_refund'] = $qualityComplaint['has_refund'];
                if ($qualityComplaint['status'] == 1) {
                    //完成
                    if($qualityComplaint['has_refund'] == 1){
                        $status = '已退款'; //已退款
                    } else {
                        $status = '已完成';
                    }
                } else {
                    $status = '已受理';
                }

                $arr[$qualityComplaint['qcid']]['status'] = $status;
                $arr[$qualityComplaint['qcid']]['has_refund'] = $qualityComplaint['has_refund'] ? 1 : 0;
                $arr[$qualityComplaint['qcid']]['time'] = date('Y-m-d',strtotime($qualityComplaint['time']));

            }
            return array('code'=>200,'msg'=>'成功','data'=>array_values($arr));
        }


    }

    //申诉详情
    function billComplaintsDetail($id){
        $sql = "SELECT qc.id AS qcid,qc.photo,qc.description,qc.status,qc.service_status,dc.id,qc.time,qc.ordername FROM ttgy_quality_complaints AS qc LEFT JOIN ttgy_deal_complaints AS dc
                ON qc.id=dc.quality_complaints_id WHERE qc.id=$id ORDER BY dc.time ASC,dc.id ASC";
        $res = $this->db_master->query($sql)->result_array();

        if (empty($res)) {
            return array('code'=>300,'msg'=>'申诉详情不存在','data'=>array());
        }

        $arr = array();
        $statusDes = array('未处理','处理完成','处理中');
        $service_statusDes = array('不可以评价','可以评价','评价完成');

        foreach ($res as $key => $value) {
            $arr['qcid'] = $value['qcid'];
            $arr['description'] = $value['description'];
            $arr['photo'] = $value['photo'];
            $arr['time'] = $value['time'];
            $arr['service_status'] = $value['service_status'];     //0表示不可以评价，1表示可以评价，2表示评价完成
            $arr['status'] = $value['status'];   //0表示未处理，2表示处理中，1表示已处理
            if ($value['id']) {
                $sql1 = "SELECT time,log,act_user FROM ttgy_deal_complaints WHERE id='".$value['id']."' LIMIT 1";
                $res1 = $this->db_master->query($sql1)->row_array();
                $arr['log'][] = $res1;
            } else {
                $arr['log'] = array();
            }
        }
        array_unshift($arr['log'],array('time'=>$arr['time'],'log'=>'果园客服已经收到您的售后申请，24小时内会电话联系您，请保持您的手机畅通。','act_user'=>'系统'));

        if (count($arr['log'])>1) {
            //按时间倒叙start
            $time = array();
            foreach ($arr['log'] as $key => $value) {
                $time[] = $value['time'];
            }
            array_multisort($time, SORT_DESC, $arr['log']);
            //按时间倒叙end
        }


        $arr['statusDes'] = $statusDes[$arr['status']];
        $arr['service_statusDes'] = $service_statusDes[$arr['service_status']];
        $arr['photo'] = array_values(array_filter(explode(',', $arr['photo'])));
        $arr['order_name'] = $value['ordername'];
        foreach ($arr['photo'] as $k => $v) {
                $arr['photo'][$k] = $this->config->item('IMAGE_URL').$v;
        }

        return array('code'=>200,'msg'=>'成功','data'=>$arr);
    }

    /*
    *  更新发票状态
    */
    function update_order_invoice($data,$where){
        $this->db->where($where);
        $this->db->update('order_invoice', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }


    /*
    * 查询发票地址记录
    */
    function get_order_invoice($order_id,$state){
        $this->db_master->select("id");
        $this->db_master->from("order_invoice");
        $this->db_master->where(array("order_id"=>$order_id,"is_valid"=>$state));
        $this->db_master->limit(1);
        $query=$this->db_master->get();
        $result=$query->row_array();
        if(isset($result['id'])) {
            $invoice_id = intval($result['id']);
            return $invoice_id;
        }else{
            return false;
        }
    }

    /**
     * 获取订单折扣
     */
    public function getOrderProductDiscount($orderId)
    {
        return $this->db->select('o.jf_money, o.card_money, c.product_id, o.money, o.method_money, o.invoice_money, o.today_method_money')
                                 ->from('order AS o')
                                 ->join('card AS c', 'o.use_card=c.card_number', 'left')
                                 ->where(array('o.id' => $orderId))
                                 ->get()->row_array();
    }

    /**
     * 获取O2O运费信息
     */
    public function getO2oPostFee(){
        return array(
            'limit' => date('Y-m-d') >= '2016-12-21' ? '69.00' : '49.00',
            'money' => date('Y-m-d') >= '2016-12-21' ? 5 : 2,
        );
    }

    /**
     * 备货达人活动处理
     */
    public function bh_active($list, $order) {
//        if(strpos($config['bj'],','.$order['uid'].',')>0){
//            $tag = '20160823bj';
//        }elseif(strpos($config['sh'],','.$order['uid'].',')>0){
//            $tag = '20160823sh';
//        }elseif(strpos($config['gz'],','.$order['uid'].',')>0){
//            $tag = '20160823gz';
//        }else{
//            return false;
//        }
//
////        if(strpos($config['kiwi'],','.$order['uid'].',')>0||strpos($config['blueberry'],','.$order['uid'].',')>0){
////能量树确定时间后配置再开
//            //能量树
////            $tag = '20160714';
//            $powertree_endtime = date('2016-09-04 23:59:59');     //延后一段时间
//            $powertree_endtime = strtotime($powertree_endtime);
//            $powertree_order_starttime = date('2016-08-24 00:00:00');  //开始时间
//            $powertree_order_endtime = date('2016-08-31 23:59:59');    //订单结束时间
        $tag = '';
        $powertree_endtime = '';
        $powertree_order_starttime = '';
        $powertree_order_endtime = '';
        foreach ($list as $val) {
            if (strpos($val['user_list'], ',' . $order['uid'] . ',') !== FALSE) {
                $tag = 'stockuptag' . $val['id'];
                $powertree_endtime = strtotime($val['gift_end_time']);     //延后一段时间
                $powertree_order_starttime = $val['start_time'];  //开始时间
                $powertree_order_endtime = $val['end_time'];    //订单结束时间
                break;
            }
        }
        if (empty($tag) || empty($powertree_endtime) || empty($powertree_order_endtime) || empty($powertree_order_starttime)) {
            return FALSE;
        }
        $time = time();

        //当前时间小小于6月30日
            if ($time < $powertree_endtime && $time>strtotime($powertree_order_starttime)) {//&& $time>$powertree_order_starttime
                //判断是否开启能量树
                $is_powertree = $this->db->select('id')->from('powertree')->where(array('uid'=>$order['uid'],'tag'=>$tag))->get()->row_array();
                if (empty($is_powertree)) {
                    $powertree  = array(
                        'uid'=>$order['uid'],
                        'power_value'=>0,
                        'created_time'=>$time,
                        'tag'=>$tag
                    );
                    $this->db->insert('powertree', $powertree);
                    $tree_id = $this->db->insert_id();
                }
                $ordertime = $this->db->select('time,order_type,money,pay_parent_id')->from('order')->where('id', $order['id'])->get()->row_array();
                //订单时间在范围内 且是B2C订单
                if ($ordertime['time'] >= $powertree_order_starttime && $ordertime['time'] <= $powertree_order_endtime && $ordertime['order_type'] == 1 && $ordertime['pay_parent_id']!=5) {
                    $powertree_record = array(
                        'uid' => $order['uid'],
                        'power_value' => $ordertime['money'],
                        'order_id' => $order['id'],
                        'created_time' => $time,
                        'tag' => $tag
                    );
                    $this->db->insert('powertree_record', $powertree_record);

                    //更新能量主表
                    if($tree_id){
                        $this->db->where(array("id" => $tree_id));
                    }else{
                        $this->db->where(array("uid" => $order['uid'],'tag'=>$tag));
                    }
                    $this->db->set('power_value', 'power_value + ' . $ordertime['money'], FALSE);
                    $this->db->update('powertree');
                }
            }
//        }
    }

    public function rocketTree1111($order) {
        $powertree_endtime = date('2016-11-15 23:59:59');     //延后一段时间
        $powertree_endtime = strtotime($powertree_endtime);
        $powertree_order_starttime = date('2016-10-17 00:00:00');  //开始时间
        $powertree_order_endtime = date('2016-11-10 23:59:59');    //订单结束时间
        $time = time();
        if ($time < strtotime($powertree_order_starttime) || time() > $powertree_endtime) {
            return;
        }

        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        $redisKey = 'active:rocketTree1111';
        $tag = 'stock1111tag';
        $redisResult = $redis->get($redisKey);
        $uidArray = array();
        if ($redisResult === FALSE) {
            $where = array('tag' => $tag);
            $powertreeResult = $this->db->from('powertree')->where($where)->get()->result_array();
            foreach ($powertreeResult as $val) {
                $uidArray[$val['uid']] = $val['power_value'];
            }
        } else {
            $uidArray = json_decode($redisResult, TRUE);
        }
        if (!array_key_exists($order['uid'], $uidArray)) {
            return;
        }

        $ordertime = $this->db->select('time,order_type,money,pay_parent_id')->from('order')->where('id', $order['id'])->get()->row_array();
        //订单时间在范围内 且是B2C订单
        if ($ordertime['time'] >= $powertree_order_starttime && $ordertime['time'] <= $powertree_order_endtime &&
                ($ordertime['order_type'] == 1 || $ordertime['order_type'] == 5) && $ordertime['pay_parent_id'] != 5 && $ordertime['pay_parent_id'] != 6) {
            if (!empty($uidArray)) {
                $stime = strtotime('2016-11-20') - time(); //保存到11月20日
                $uidArray[$order['uid']]+=$ordertime['money'];
                $redis->setex($redisKey, $stime, json_encode($uidArray));
            }

            $powertree_record = array(
                'uid' => $order['uid'],
                'power_value' => $ordertime['money'],
                'order_id' => $order['id'],
                'created_time' => $time,
                'tag' => $tag
            );
            $this->db->insert('powertree_record', $powertree_record);

            //更新能量主表
            $this->db->where(array("uid" => $order['uid'], 'tag' => $tag));
            $this->db->set('power_value', 'power_value + ' . $ordertime['money'], FALSE);
            $this->db->update('powertree');
        }
    }

    //购物车营销记录
    public function add_order_cart($order_id,$cart_info){
        $insert_data  = array(
                        'order_id'=>$order_id,
                        'content'=>json_encode($cart_info),
                    );
        $this->db->insert('order_cart', $insert_data);
    }

    //下单后就能获得一颗种子
    public function add_order_seed($order_id){
        $this->db->select("uid");
        $this->db->from("order");
        $this->db->where(array("id" =>$order_id));//
        $order = $this->db->get()->row_array();
        $uid = $order['uid'];
        $time = date("Y-m-d H:i:s");
        //种子ID
        $sql = "select id,name from ttgy_farm_seed  where stype=1 and start_time <= '".$time."' and end_time > '".$time."' order by sort desc ";
        $seedList = $this->db->query($sql)->result_array();
        $sIdArr = array();
        foreach($seedList as $k=>$v){
            $sIdArr[$v['id']] = $v['name'];
        }
        $sid = array_rand($sIdArr);
        $insert_data = array(
            'uid'=>$uid,
            'sid'=>$sid,
            'status'=>1,
            'time'=>date("Y-m-d H:i:s"),
            'remarks'=>'订单种子奖励',
            'last_time'=>date("Y-m-d H:i:s")
        );
        $farmLog = array(
            'uid'=>$uid,
            'time'=>date("Y-m-d H:i:s"),
            'remarks'=>'获得种子:'.$sIdArr[$sid].'1颗',
        );
        $this->db->insert('farm_all_log', $farmLog);
        $this->db->insert('farm_user_seed', $insert_data);
    }

    //根据order_name 获取order_id
    public function getOrderIdByOrderName($order_name){
        $this->db_master->select("id");
        $this->db_master->from("order");
        $this->db_master->where(array("order_name" => $order_name));
        $this->db_master->limit(1);
        $query=$this->db_master->get();
        $result=$query->row_array();
        if(isset($result['id'])) {
            $order_id = intval($result['id']);
            return $order_id;
        }else{
            return false;
        }
    }

    public function add_final_money($order_id,$final_money = '-1'){
        if(empty($order_id)) return;
        if($final_money<0) return;
        $res = $this->db->select('id')->from('finish_order')->where('order_id',$order_id)->get()->row_array();
        if($res){
            $up_data = array();
            $up_data['final_money'] = $final_money;
            $this->db->where('order_id',$order_id);
            $this->db->update('finish_order', $up_data);
        }else{
            $insert_data = array();
            $insert_data['order_id'] = $order_id;
            $insert_data['final_money'] = $final_money;
            $this->db->insert('finish_order', $insert_data);
        }
    }

    //check券卡类型商品
    public function checkCardtypeProd($order_id){
        $sql = "select p.id from ttgy_order o join ttgy_order_product op on o.id=op.order_id join ttgy_product p on op.product_id=p.id where o.id=".$order_id." and p.iscard=1";
        $res = $this->db->query($sql)->row_array();
        if($res) return true;
        else return false;
    }

    /*
     * 订单列表 － New
     */
    public function parentOrderList($field,$where,$where_in='',$order_by='',$limit=10,$offset=0)
    {
        $parentSql = $this->parentSql($field,$where,$where_in,'','','');
        $order_sql = $this->orderSql($field,$where,$where_in,'','','');

        $sql = 'select res.* from ('.$parentSql.' union all '.$order_sql.') as res'.' ORDER BY time desc limit '.$offset.','.$limit;
        $result = $this->db->query($sql)->result_array();

        return $result;
    }

    /*
    * 订单总数 － New
    */
    public function parentCountOrderList($field,$where,$where_in='',$order_by='')
    {
        $parentSql = $this->parentSql($field,$where,$where_in,$order_by);
        $order_sql = $this->orderSql($field,$where,$where_in,$order_by);

        $sql = '('.$parentSql.') union all ('.$order_sql.')';
        $result = $this->db->query($sql)->result_array();
        $total = count($result);

        return $total;
    }

    /*
     * 订单sql
     */
    private function orderSql($field,$where,$where_in='',$order_by='',$limit=0,$offset=0)
    {
        $this->db->select($field);
        $this->db->from('order');
        $this->db->where($where);
        if(!empty($where_in)){
            foreach($where_in as $val){
                $this->db->where_in($val['key'],$val['value']);
            }
        }
        if($order_by){
            $this->db->order_by($order_by);
        }
        if($limit){
            $this->db->limit($limit,$offset);
        }

        $this->db->get();
        return  $this->db->last_query();
    }

    /*
     * 订单sql
     */
    private function parentSql($field,$where,$where_in='',$order_by='',$limit=0,$offset=0)
    {
        $where['p_order_id'] = 0;
        $this->db->select($field);
        $this->db->from('ttgy_b2o_parent_order');
        $this->db->where($where);
        if(!empty($where_in)){
            foreach($where_in as $val){
                $this->db->where_in($val['key'],$val['value']);
            }
        }
        if($order_by){
            $this->db->order_by($order_by);
        }
        if($limit){
            $this->db->limit($limit,$offset);
        }
        $this->db->get();
        return  $this->db->last_query();
    }


    /*
    * 新增订单
    */
    function addOrder($insert_data) {
        $this->db->insert("order",$insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    /*
    * 新增订单发票
    */
    function addOrderInvoice($insert_data) {
        $this->db->insert("order_invoice",$insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    /*
    *   添加电子发票
    */
    function addDfp($order_id,$mobile,$dfp){
        if(!empty($order_id)){
            $data = array(
                'order_name'=>$order_id,
                'mobile'=>$mobile,
                'dfp'=>$dfp
            );
            $this->db->insert('order_einvoices',$data);
        }
        return true;
    }

    /*
	* 订单(待发货) - 列表
	*/
    function waitOrderList($uid,$limit,$offset)
    {
        $fields = 'id,send_date,uid,order_name,time,pay_name,shtime,money,pay_status,operation_id,pay_parent_id,had_comment,has_bask,order_type,sync_status,use_money_deduction,new_pay_discount,sheet_show_price,order_status, method_money,p_order_id,stime,lyg';
        $orderby = 'order by time desc ';
        $lim = 'limit '.$limit.' offset '.$offset;
        $sql = "select ".$fields." from ttgy_order where ( uid='".$uid."' and pay_status =1 and order_status =1 and show_status=1 and operation_id in (0,1,4) and order_type in ('1','2','3','4','5','7','13','9','14')) or (uid='".$uid."' and pay_parent_id =4 and order_status =1 and show_status=1 and operation_id in (0,1,4) and order_type in ('1','2','3','4','5','7','13','9','14')) ";
        $sql .= $orderby;
        $sql .= $lim;
        $result = $this->db->query($sql)->result_array();
        return $result;
    }

    /*
	* 订单(待发货) - 列表总数
	*/
    function waitOrderCount($uid)
    {
        $sql = "select id from ttgy_order where ( uid='".$uid."' and pay_status =1 and order_status =1 and show_status=1 and operation_id in (0,1,4) and order_type in ('1','2','3','4','5','7','13','9','14')) or (uid='".$uid."' and pay_parent_id =4 and order_status =1 and show_status=1 and operation_id in (0,1,4) and order_type in ('1','2','3','4','5','7','13','9','14'))";
        $result = $this->db->query($sql)->result_array();
        $res = count($result);
        return $res;
    }

    /*
    *获取门店电话
    */
    function getLastAddr($uid)
    {
        $last_addr = '';
        $addr = array();
        $sql = "select id,order_name,address_id,COUNT(id) as mcount from ttgy_b2o_parent_order where uid='".$uid."' group by address_id order by mcount desc";
        $order = $this->db->query($sql)->result_array();
        if(count($order) >0)
        {
            foreach($order as $key=>$val)
            {
                $sql = "select id from ttgy_user_address where id = '".$val['address_id']."'";
                $user_addr = $this->db->query($sql)->row_array();
                if(!empty($user_addr))
                {
                    array_push($addr,$val['address_id']);
                }
            }

            if(count($addr) >0)
            {
                $last_addr = $addr[0];
            }
        }
        return $last_addr;
    }

    /*
    * 获取赠品订单 － 0元订单
    */
    public function get_gift_order($pid){
        $this->db_master->select("id,order_name,channel,sync_status,is_enterprise,order_type,uid,p_order_id");
        $this->db_master->from("order");
        $this->db_master->where(array("money"=>'0.00',"p_order_id"=>$pid));
        $query=$this->db_master->get();
        $result=$query->result_array();
        return $result;
    }

    /*
    * 获取赠品订单 － 0元订单
    */
    public function get_gift_product($order_id,$uid,$p_order_id)
    {
        $is_gift = 0;
        $this->db_master->select("id,product_id");
        $this->db_master->from("order_product");
        $this->db_master->where(array("order_id"=>$order_id,'type'=>'3'));
        $query=$this->db_master->get();
        $result=$query->result_array();

        if(count($result) == 1)
        {
            $pro_id = $result[0]['product_id'];
            $sql = "select id,bonus_products from ttgy_trade where uid='".$uid."' and has_rec = 1 and bonus_order='".$p_order_id."' order by id desc limit 1";
            $trade = $this->db->query($sql)->row_array();
            $trade_pro = explode(',',$trade['bonus_products']);
            if(in_array($pro_id,$trade_pro))
            {
                $is_gift = 1;
            }
        }

        return $is_gift;
    }

    public function getCanApplyInvoice($uid){
        $minimumTime = date('Y-m-d H:i:s', strtotime('- 180day', time()));
        $this->db->select('o.id , o.order_name , o.time, o.money , o.new_pay_discount , o.use_money_deduction , o.method_money , o.cang_id , count(1) count');
        $this->db->select_sum('ttgy_subsequent_invoice.is_canceled','sum');
        $this->db->from('order o');
        $this->db->join('subsequent_invoice_order', 'subsequent_invoice_order.order_name=o.order_name','LEFT');
        $this->db->join('subsequent_invoice', 'subsequent_invoice_order.invoice_id=subsequent_invoice.id','LEFT');
        $this->db->where(array('o.uid' => $uid, 'o.operation_id !='=>5 , 'o.order_status' => 1, 'o.pay_status' => 1,  'o.pay_parent_id !=' => 5, 'o.time >= ' => $minimumTime, '(`ttgy_subsequent_invoice`.`is_valid` != 0 OR ttgy_subsequent_invoice.is_valid is null ) ' => null));
        $this->db->where_in('o.order_type', array(1,3,5,9));
        $this->db->group_by('o.order_name ');
        $this->db->having('(sum = count) or sum is null', null);
        $this->db->order_by('o.time desc');
        $res = $this->db->get()->result_array();
        return $res;
    }

    public function checkOrderInvoiceType ($orderName, $foodTypeClassIdGroup){
        $this->db->select('order.id');
        $this->db->from('order');
        $this->db->join('order_product','order.id=order_product.order_id');
        $this->db->join('product','order_product.product_id=product.id');
        $this->db->join('b2o_product_template','product.template_id=b2o_product_template.id');
        $this->db->where(array('order.order_name' => $orderName));
        $this->db->where_in('b2o_product_template.class_id', $foodTypeClassIdGroup);
        $res = $this->db->get()->result_array();

        if(empty($res)){
            return false;
        } else {
            return true;
        }
    }

    /*
    * 获取订单地址
    */
    function get_user_addr($uid,$address_id){
        $this->db->select('id,name,province,city,area,address,telephone,mobile,flag,is_default as isDefault,lonlat,province_adcode,city_adcode,area_adcode,address_name');
        $this->db->from('user_address');
        $this->db->where(array("uid"=>$uid,"id"=>$address_id));
        $query  = $this->db->get();
        $result = $query->row_array();
        return $result;
    }


    /*
     *重置配送时间
     */
    function sendtime_reset($order_id,$shtime='',$stime=''){
        $this->db->where("id",$order_id);
        $this->db->update("order",array("shtime" =>$shtime,"stime"=>$stime,'sync_time'=>0));
        return true;
    }

    public function setOmsRefund($order_id){
        $res = $this->db_master->select('id')->from('oms_refund')->where('order_id',$order_id)->get()->row_array();
        if(empty($res)){
            $insert_data = array();
            $insert_data['order_id'] = $order_id;
            $insert_data['oms_refund'] = 1;
            $this->db_master->insert('oms_refund',$insert_data);
        }else{
            $up_data = array();
            $up_data['oms_refund'] = 1;
            $this->db_master->where('order_id',$order_id);
            $this->db_master->update('oms_refund',$up_data);
        }
    }

    public function setOmsNoRefund($order_id){
        $res = $this->db_master->select('id')->from('oms_refund')->where('order_id',$order_id)->get()->row_array();
        if(empty($res)){
            $insert_data = array();
            $insert_data['order_id'] = $order_id;
            $insert_data['oms_refund'] = 0;
            $this->db_master->insert('oms_refund',$insert_data);
        }else{
            $up_data = array();
            $up_data['oms_refund'] = 0;
            $this->db_master->where('order_id',$order_id);
            $this->db_master->update('oms_refund',$up_data);
        }
    }

    public function setPorderOmsNoRefund($p_order_id){
        $res = $this->db_master->select('id')->from('oms_refund')->where('p_order_id',$p_order_id)->get()->row_array();
        if(empty($res)){
            $insert_data = array();
            $insert_data['p_order_id'] = $p_order_id;
            $insert_data['oms_refund'] = 0;
            $this->db_master->insert('oms_refund',$insert_data);
        }else{
            $up_data = array();
            $up_data['oms_refund'] = 0;
            $this->db_master->where('p_order_id',$p_order_id);
            $this->db_master->update('oms_refund',$up_data);
        }
    }


    /*
     * 订单列表 － 搜索
     */
    public function parentOrderListSearch($field,$where,$where_in='',$order_by='',$limit=10,$offset=0,$like='')
    {
        $parentSql = $this->parentSqlSearch($field,$where,$where_in,'','','',$like);
        $order_sql = $this->orderSqlSearch($field,$where,$where_in,'','','',$like);

        $sql = 'select res.* from ('.$parentSql.' union all '.$order_sql.') as res'.' ORDER BY time desc limit '.$offset.','.$limit;
        $result = $this->db->query($sql)->result_array();

        return $result;
    }

    /*
     * 订单sql － 搜索
     */
    private function parentSqlSearch($field,$where,$where_in='',$order_by='',$limit=0,$offset=0,$product_name='')
    {
        $f_arr = explode(',',$field);
        $f_s_arr = array();
        foreach($f_arr as $key=>$val)
        {
            $str = 'o.'.$val;
            array_push($f_s_arr,$str);
        }
        $field = implode(',',$f_s_arr);

        $where['p_order_id'] = 0;
        foreach($where as $k=>$v)
        {
            $where['o.'.$k] = $v;
            unset($where[$k]);
        }

        $this->db->select($field);
        $this->db->from('b2o_parent_order as o');
        $this->db->join('b2o_parent_order_product as op', 'o.id = op.order_id', 'inner');
        $this->db->where($where);
        $this->db->like('op.product_name',$product_name, 'both');
        if(!empty($where_in)){
            foreach($where_in as $val){
                $this->db->where_in('o.'.$val['key'],$val['value']);
            }
        }
        if($order_by){
            $this->db->order_by($order_by);
        }
        if($limit){
            $this->db->limit($limit,$offset);
        }
        $this->db->get();
        return  $this->db->last_query();
    }

    /*
     * 订单sql － 搜索
     */
    private function orderSqlSearch($field,$where,$where_in='',$order_by='',$limit=0,$offset=0,$product_name='')
    {
        $f_arr = explode(',',$field);
        $f_s_arr = array();
        foreach($f_arr as $key=>$val)
        {
            $str = 'o.'.$val;
            array_push($f_s_arr,$str);
        }
        $field = implode(',',$f_s_arr);

        foreach($where as $k=>$v)
        {
            $where['o.'.$k] = $v;
            unset($where[$k]);
        }

        $this->db->select($field);
        $this->db->from('order as o');
        $this->db->join('order_product as op', 'o.id = op.order_id', 'inner');
        $this->db->where($where);
        $this->db->like('op.product_name',$product_name, 'both');
        if(!empty($where_in)){
            foreach($where_in as $val){
                $this->db->where_in('o.'.$val['key'],$val['value']);
            }
        }
        if($order_by){
            $this->db->order_by($order_by);
        }
        if($limit){
            $this->db->limit($limit,$offset);
        }

        $this->db->get();

        return  $this->db->last_query();
    }

    /*
    * 订单总数 － 搜索
    */
    public function parentCountOrderListSearch($field,$where,$where_in='',$order_by='',$product_name='')
    {
        $parentSql = $this->parentSqlSearch($field,$where,$where_in,$order_by,0,0,$product_name);
        $order_sql = $this->orderSqlSearch($field,$where,$where_in,$order_by,0,0,$product_name);

        $sql = '('.$parentSql.') union all ('.$order_sql.')';
        $result = $this->db->query($sql)->result_array();
        $total = count($result);

        return $total;
    }
}
