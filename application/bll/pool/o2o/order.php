<?php
namespace bll\pool\o2o;

class Order
{
    private $_online_pay = array();

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->_online_pay = $this->ci->config->item("oms_online_pay");
    }

    public function set_sync($orderid_arr, $sync_status)
    {
        $this->ci->load->model('o2o_child_order_model');

        $filter = array(
            'id' => $orderid_arr,
        );
        $params = array('sync_status'=>$sync_status);

        $affected_rows = $this->ci->o2o_child_order_model->update($params, $filter);

        return $affected_rows;
    }


    public function week($sendDate, $shtime = '')
    {
        $sendtime = strtotime($sendDate);

        if ($shtime == 'weekday') {
            $gdate = getdate($sendtime + 86400);
            if ($gdate['wday'] == 6 || $gdate['wday'] == 0) {
                $sendDate = date('Y-m-d', strtotime('sunday', $sendtime));
            }
        }

        if ($shtime == 'weekend') {
            $gdate = getdate($sendtime + 86400);
            if (!in_array($gdate['wday'], array(0, 6))) {
                $sendDate = date('Y-m-d', strtotime('friday', ($sendtime + 86400)));
            }
        }

        return $sendDate;
    }

    /**
     * 两到三天配送时间计算
     *
     * @return void
     * @author
     **/
    public function after2to3days($province, $area, $createtime, $shtime = '')
    {
        $createtime = strtotime($createtime);

        $area_refelect = $this->ci->config->item("area_refelect");
        if (!in_array($province['id'], $area_refelect[1])) { //外地
            $send_h = date('H', $createtime);
            $sendtime = $send_h >= $area['cut_off_time'] ? ($createtime + 86400) : $createtime;

            $sendDate = date('Y-m-d', $sendtime);

            if ($shtime == 'weekday') {
                $gdate = getdate($sendtime + 86400);
                if ($gdate['wday'] == 6 || $gdate['wday'] == 0) {
                    $sendDate = date('Y-m-d', strtotime('sunday', $sendtime));
                }
            }

            if ($shtime == 'weekend') {
                $gdate = getdate($sendtime + 86400);
                if (!in_array($gdate['wday'], array(0, 6))) {
                    $sendDate = date('Y-m-d', strtotime('friday', ($sendtime + 86400)));
                }
            }

            return $sendDate;
        } else {
            $send_h = date('H', $createtime);

            $sendtime = $send_h >= $area['cut_off_time'] ? ($createtime + 172800) : ($createtime + 86400);
            $sendDate = date('Y-m-d', $sendtime);

            if ($shtime == 'weekday') {
                $gdate = getdate($sendtime);
                if (!in_array($gdate['wday'], array(1, 2, 3, 4, 5))) {
                    $sendDate = date('Y-m-d', strtotime('weekday', $sendtime));
                }
            }

            if ($shtime == 'weekend') {
                $gdate = getdate($sendtime);

                if (!in_array($gdate['wday'], array(0, 6))) {
                    $sendDate = date('Y-m-d', strtotime('saturday', $sendtime));
                }
            }

            return $sendDate;
        }
    }

    /**
     * 订单校验
     *
     * @return void
     * @author
     **/
    public function check_order($orders)
    {
        $error = array();
        foreach ($orders as $key => $order) {
            $goods_money = 0;

            foreach ($order['order_items'] as $k => $v) {
                $goods_money += $v['totalAmount'];

                if ($v['saletype'] == 2 && 0 != bccomp($v['totalAmount'], 0, 3)) {
                    $error[] = $order['orderNo'];
                    unset($orders[$key]);
                    continue 2;
                }

                if ($v['saletype'] == 1 && 0 == bccomp($v['totalAmount'], 0, 3) && 0 == bccomp($v['cardAmount'], 0, 3) ) {
                    $error[] = $order['orderNo'];
                    unset($orders[$key]);
                    continue 2;
                }
            }

            $totalAmount = $goods_money + $order['freightFee'] - $order['disamount'] - $order['dedamount'] + $order['invoice_info']['invTransFee'];

            if (0 != bccomp($totalAmount, $order['totalAmount'], 3)) {
                $error[] = $order['orderNo'];
                unset($orders[$key]);
            }
        }

        if ($error) {
            $this->ci->load->model('jobs_model');
            $emailList = array('huangb@fruitday.com', 'songtao@fruitday.com', 'lusc@fruitday.com');
            foreach ($emailList as $email) {
                $emailContent = implode('、', $error);
                $this->ci->jobs_model->add(array('email' => $email, 'text' => $emailContent, 'title' => "o2o订单金额异常"), "email");
            }
        }

        return $orders;
    }

    public function get_push_orders($order_names = array(), $valid = true)
    {
        $this->ci->load->model('order_model');
        $this->ci->load->model('o2o_child_order_model');
        $this->ci->load->model('product_price_model');
        $this->ci->load->bll('pool/o2o/area');


        // 获取已支付或货到付款订单
        $orders = $this->ci->o2o_child_order_model->get_push_orders($order_names, $valid);

        if (!$orders) return array();

        // 线上支付方式与订单池的映射关系
        // $online_pay = array(
        //     '1' => array('way_id' => 1, 'platform_id' => 1003),
        //     '2' => array('way_id' => 1, 'platform_id' => 1002),
        //     '3' => array(
        //         'way_id' => 1,
        //         'platform_id' => 1001,
        //         'children_platform_id' => array(
        //             '00021' => 1006,
        //             '00005' => 1004,
        //             '00003' => 1007,
        //             '00100' => 1010,
        //             '00101' => 1012,
        //             '00102' => 1011,
        //             '00103' => 1015,
        //             '00105' => 1016,
        //         ),
        //     ),
        //     '5' => array('way_id' => 9, 'platform_id' => null),
        //     '6' => array('way_id' => 5, 'platform_id' => null),
        //     '7' => array('way_id' => 1, 'platform_id' => 1005),
        //     '8' => array('way_id' => 1, 'platform_id' => 1014),
        //     '9' => array('way_id' => 1, 'platform_id' => 1008),
        //     '10' => array('way_id' => 1, 'platform_id' => 1013),
        //     '11' => array('way_id'=> 1, 'platform_id'=>1017),
        // );

        // 组织结构
        $f_orders = array();

        $addressid_arr = $orderid_arr = $child_orderid_arr = $uid_arr = $ordername = array();
        foreach ($orders as $o) {
            $uid_arr[] = $o['uid'];
            $orderid_arr[] = $o['p_order_id'];
            if ($o['address_id']) $addressid_arr[] = $o['address_id'];
            $ordername[] = $o['p_order_name'];
            $child_orderid_arr[] = $o['id'];
            $child_ordername[] = $o['order_name'];
        }

        $users = array();
        if ($uid_arr) {
            $this->ci->load->model('user_model');
            $tmp_users = $this->ci->user_model->getList('id,uname,username,mobile,user_rank', array('id' => $uid_arr), 0, -1);
            foreach ((array)$tmp_users as $key => $value) {
                $users[$value['id']] = $value;
            }
            unset($tmp_users);
        }

        $order_addresses = array();
        $this->ci->load->model('order_address_model');
        $tmp_order_addresses = $this->ci->order_address_model->getList('*', array('order_id' => $orderid_arr), 0, -1);

        foreach ((array)$tmp_order_addresses as $key => $value) {
            $order_addresses[$value['order_id']] = $value;

            if ($value['province']) $areaid_arr[$value['province']] = $value['province'];
            if ($value['city']) $areaid_arr[$value['city']] = $value['city'];
            if ($value['area']) $areaid_arr[$value['area']] = $value['area'];
        }
        unset($tmp_order_addresses);

        $order_products = array();
        $product_ids = array();
        $this->ci->load->model('o2o_order_product_extra_model');
        $tmp_order_products = $this->ci->o2o_order_product_extra_model->get_child_order_product($child_orderid_arr);
        $store_product = array();
        foreach ((array)$tmp_order_products as $key => $value) {
            $order_products[$value['c_order_id']][] = $value;
            if(!in_array($value['product_id'], $product_ids)){
                array_push($product_ids, $value['product_id']);
            }
            $store_product[$value['store_id']][] = $value['product_id'];
        }
        unset($tmp_order_products);
 
        $this->ci->load->model('cityshop_product_model');
        $city_shop_products = $this->ci->cityshop_product_model->getCityShopProductBarCode();

        //售罄log
        $this->sold_out($store_product);

        //组合商品
        $product_groups = array();
        if(!empty($product_ids)){
            $this->ci->load->model('product_groups_model');
            $tmp_product_groups = $this->ci->product_groups_model->get_product_groups($product_ids);
            foreach($tmp_product_groups as $pg){
                $product_groups[$pg['product_id']][] = $pg;
            }
        }

        if ($addressid_arr) {
            $user_addresses = array(); //$areaid_arr = array();
            $this->ci->load->model('user_address_model');
            $tmp_user_addresses = $this->ci->user_address_model->getList('*', array('id' => $addressid_arr), 0, -1);
            foreach ((array)$tmp_user_addresses as $key => $value) {
                $user_addresses[$value['id']] = $value;
                $areaid_arr[$value['province']] = $value['province'];
                $areaid_arr[$value['city']] = $value['city'];
                $areaid_arr[$value['area']] = $value['area'];
            }
            unset($tmp_user_addresses);
        }

        if ($areaid_arr) {
            $area = array();
            $this->ci->load->model('area_model');
            $tmp_area = $this->ci->area_model->getList('id,name,cut_off_time', array('id' => $areaid_arr), 0, -1);
            foreach ((array)$tmp_area as $key => $value) {
                $area[$value['id']] = $value;
            }
            unset($tmp_area);
        }

        $sale_bankcom = array();
        $this->ci->load->model('bankcom_records_model');
        $tmp_sale_bankcom = $this->ci->bankcom_records_model->getList('ordername,sale', array('ordername' => $ordername), 0, -1);
        if (!empty($tmp_sale_bankcom)) {
            foreach ((array)$tmp_sale_bankcom as $key => $value) {
                $sale_bankcom[$value['ordername']] = $value['sale'];
            }
        }
        unset($tmp_sale_bankcom);

        $order_invoice = array();
        $this->ci->load->model('order_invoice_model');
        $tmp_order_invoice = $this->ci->order_invoice_model->getList('*', array('order_id' => $orderid_arr,'is_valid'=>1), 0, -1);
        if (!empty($tmp_order_invoice)) {
            foreach ($tmp_order_invoice as $key => $value) {
                $order_invoice[$value['order_id']] = $value;
            }
        }
        unset($tmp_order_invoice);

        $order_record = array();
        $tmp_order_record = $this->ci->order_model->getOrderRecord($orderid_arr);

        if (!empty($tmp_order_record)) {
            foreach ($tmp_order_record as $key => $value) {
                $order_record[$value['order_id']] = $value;
            }
        }
        unset($tmp_order_record);

        $dz_fp = array();
        $tmp_dz_fp = $this->ci->order_model->getDzFp($ordername);
        if (!empty($tmp_dz_fp)) {
            foreach ($tmp_dz_fp as $key => $value) {
                $dz_fp[$value['order_name']] = $value;
            }
        }
        unset($tmp_dz_fp);

        $inner_codes = array();
        $error_list = array();
        foreach ($orders as $o) {

            $this->ci->load->model('o2o_region_model');
            $building_info = $this->ci->o2o_region_model->dump(array('id' => $o['building_id']));
            $province_id = $order_addresses[$o['p_order_id']]['province'] ? $order_addresses[$o['p_order_id']]['province'] : $user_addresses[$o['address_id']]['province'];
            $city_id = $order_addresses[$o['p_order_id']]['city'] ? $order_addresses[$o['p_order_id']]['city'] : $user_addresses[$o['address_id']]['city'];
            $area_id = $order_addresses[$o['p_order_id']]['area'] ? $order_addresses[$o['p_order_id']]['area'] : $user_addresses[$o['address_id']]['area'];
            if(empty($province_id) || empty($province_id) || empty($area_id)){
                $error_list[] = $o['order_name'];
                continue;
            }
            $order = array();
            // $order['chId']        = (int) $o['channel']; // 渠道(1:官网,2:手机,3:预售,4:光明,5:手机预售,6:app订单,7:app线下订单)

            // if ($o['channel'] == '3') $order['chId'] = 1;
            // if ($o['channel'] == '5') $order['chId'] = 2;
            // if ($o['channel'] == '8') $order['chId'] = 9;

            $order['type'] = (int)$o['order_type']; // 订单类型(1:普通订单,2:试吃订单,3:o2o配送订单,4:o2o自提订单，5:预售订单)

            // 大客户订单
            if ($o['is_enterprise']) {
                // $order['chId'] = 8;
                $order['type'] = 13;
                $order['enterpriseno'] = $o['is_enterprise'];
            }

            $order['chId'] = $this->get_pool_channel($o['channel'], $o['is_enterprise']);
            if ($o['order_type'] == '6') $order['chId'] = 10;

            $order['orderNo'] = $o['order_name']; // 单号
            $order['createdate'] = $o['time']; // 创建时间
            $order['payDate'] = ($o['update_pay_time'] && $o['update_pay_time'] != '0000-00-00 00:00:00') ? $o['update_pay_time'] : $o['time'];



            $order['sendDate'] = is_numeric($o['shtime']) ? date('Y-m-d',strtotime($o['shtime'])) : $o['shtime'];
            $order['delivertime'] = $o['stime'];

            //
            $area_refelect = $this->ci->config->item("area_refelect");

            $order['delDay'] = 0;


            $order['billno'] = $o['billno'] ? $o['billno'] : '';
            $order['score'] = (float)$o['score'];
            $order['gcardinfo'] = $o['hk'];
            $order['note'] = $o['msg'];
            $order['orderAmount'] = (float)$o['goods_money'];
            $order['freightFee'] = $o['fp_dz'] ? (float)($o['method_money'] - 5) : (float)$o['method_money'];
            $order['totalAmount'] = bcsub($o['money'],$o['bank_discount'],2);
            $order['disamount'] = (float)($o['manbai_money'] + $o['member_card_money'] + $o['pay_discount'] + $o['oauth_discount'] + $sale_bankcom[$o['order_name']]+$o['bank_discount']);
            $order['dedamount'] = (float)$o['card_money'];
            $order['ispay'] = $o['pay_status'] == '1' ? 1 : 0;
            $order['payment'] = (int)$o['pay_id']; // 线上，线下...
            if ($o['pay_parent_id'] == 4 && $o['pay_id'] == 6) {
                $order['payment'] = 1;
            }
            if (isset($this->_online_pay[$o['pay_parent_id']]) || $o['order_type'] == '2') $order['payment'] = 0;

            // if ($o['pay_parent_id']=='6' && $o['pay_id'] == '1') {
            //     $order['disamount'] = (float) ($o['goods_money'] - $o['money']);
            // }

            $order['status'] = $o['operation_id']; // 订单状态(0:待审核核,1:已审核,2:已发货,3:已完成,4:未完成,5:已取消,6:等待完成,7:退货中,8:换货中)
            $order['deliverystate'] = 2;

            if ($o['channel'] == '7') {
                $order['deliverystate'] = 1;
                $order['activityno'] = $o['erp_active_tag'];
                $order['type'] = 4;
            }

            $order['invoice_info']['invoiceis'] = $o['fp_dz'] ? 0 : 1; // 是否和货物一起配送

            $region_match = $this->ci->bll_pool_o2o_area->region_match($order_invoice[$o['p_order_id']]['province'], $order_invoice[$o['p_order_id']]['city'], $order_invoice[$o['p_order_id']]['area']);

            $order['invoice_info']['ivprovince'] = $region_match['province'];
            $order['invoice_info']['ivcity'] = $region_match['city'];
            $order['invoice_info']['ivarea'] = $region_match['area'];
            unset($region_match);

            $order['invoice_info']['ivRec'] = $order_invoice[$o['id']]['username'];
            if (isset($dz_fp[$o['order_name']]['order_name'])) {
                $order['invoice_info']['invoicetype'] = 2;//电子发票
                $order['invoice_info']['ivPhone'] = isset($dz_fp[$o['order_name']]['mobile']) ? $dz_fp[$o['order_name']]['mobile'] : '';
            } else {
                $order['invoice_info']['invoicetype'] = 1;//纸质发票
                $order['invoice_info']['ivPhone'] = $order_invoice[$o['id']]['mobile'];
            }
            $order['invoice_info']['invoiceaddr'] = $order_invoice[$o['id']]['address'] ? $order_invoice[$o['id']]['address'] : $o['fp_dz'];
            $order['invoice_info']['invoicetitle'] = $o['fp'];
            $order['invoice_info']['ivAmount'] = $o['fp'] ? (float)$o['money'] : 0;
            $order['invoice_info']['invTransFee'] = $o['fp_dz'] ? 5 : 0;


            $order['member_info']['buyerId'] = (int)$o['uid'];
            $order['member_info']['buyer'] = $users[$o['uid']]['username'] ? $users[$o['uid']]['username'] : $users[$o['uid']]['mobile'];
            $order['member_info']['buyerPhone'] = $users[$o['uid']]['mobile'];
            $order['member_info']['buyerLevel'] = $users[$o['uid']]['user_rank'];
            $order['member_info']['name'] = isset($order_record[$o['id']]['name']) ? $order_record[$o['id']]['name'] : '';
            $order['member_info']['idCardType'] = isset($order_record[$o['id']]['id_card_type']) ? $order_record[$o['id']]['id_card_type'] : '';
            $order['member_info']['idCardNumber'] = isset($order_record[$o['id']]['id_card_number']) ? $order_record[$o['id']]['id_card_number'] : '';
            $order['member_info']['phoneNumber'] = isset($order_record[$o['id']]['mobile']) ? $order_record[$o['id']]['mobile'] : '';
            $order['member_info']['email'] = isset($order_record[$o['id']]['email']) ? $order_record[$o['id']]['email'] : '';


            $order['consignee_info']['buildingNo'] = $o['building_id'];
            $order['consignee_info']['buildingName'] = $building_info['name'];
            $order['consignee_info']['buildingExtNo'] = $building_info['ext_no'];

            $order['consignee_info']['province'] = $area[$province_id]['name'];
            $order['consignee_info']['city'] = $area[$city_id]['name'];
            $order['consignee_info']['region'] = preg_replace('/（.*）/', '', $area[$area_id]['name']);

            $region_match = $this->ci->bll_pool_o2o_area->region_match($order['consignee_info']['province'], $order['consignee_info']['city'], $order['consignee_info']['region']);

            $order['consignee_info']['reAddress'] = str_replace($order_addresses[$o['p_order_id']]['position'], '', $order_addresses[$o['p_order_id']]['address']);
            if ($region_match['area'] != $order['consignee_info']['region'] && preg_match('/开发区/', $order['consignee_info']['region'])) {
                $order['consignee_info']['reAddress'] = $order['consignee_info']['region'] . $order['consignee_info']['reAddress'];
            }

            $order['consignee_info']['province'] = $region_match['province'];
            $order['consignee_info']['city'] = $region_match['city'];
            $order['consignee_info']['region'] = $region_match['area'];


            unset($region_match);

            $order['consignee_info']['receiver'] = $order_addresses[$o['p_order_id']]['name'];

            $order['consignee_info']['rePhone'] = $order_addresses[$o['p_order_id']]['telephone'];
            $order['consignee_info']['reMobile'] = $order_addresses[$o['p_order_id']]['mobile'];

            if (!trim($order['consignee_info']['reMobile']) && $order['consignee_info']['rePhone']) {
                $order['consignee_info']['reMobile'] = $order['consignee_info']['rePhone'];
            }

            $order['wh'] = $o['order_type'] == '6' ? 'FSHZM' : '';



            /*优惠券促销信息整理start*/
            // $order['is_special_card'] = 0;
            
            $card_pro_arr = array();
            if(!empty($o['use_card'])){
                $card_info = $this->ci->db->select('product_id')->from('card')->where(array('card_number'=>$o['use_card']))->get()->row_array();
                if(!empty($card_info) && !empty($card_info['product_id'])){
                    $card_pro_arr = explode(',', $card_info['product_id']);
                }
            }
            $card_used_pro = array();
            $card_used_pro_total = 0;
            $card_used_pro_i = 0;
            $card_used_pro_tmp = array();
            if(!empty($card_pro_arr)){
                foreach ((array) $order_products[$o['id']] as $op) {
                    if ($op['type'] == 2 || $op['type'] == 3 || $op['type'] == 5 || $op['type'] == 6 || 0 == bccomp($op['total_money'], 0,3)){
                        continue;
                    }
                    if(in_array($op['product_id'], $card_pro_arr)){
                        $card_used_pro[$card_used_pro_i]['product_id'] = $op['product_id'];
                        $card_used_pro[$card_used_pro_i]['total_money'] = $op['total_money'];
                        $card_used_pro_total += $op['total_money'];
                        $card_used_pro_i++;
                    }
                }
                if(!empty($card_used_pro)){
                    // $order['is_special_card'] = 1;
                    $card_used_pro_count = count($card_used_pro)-1;
                    $card_used_pro_money_sy = 0;
                    for ($i=0; $i <= $card_used_pro_count; $i++) { 
                        if($i == $card_used_pro_count){
                            $card_used_pro_money = $order['dedamount'] - $card_used_pro_money_sy;
                        }else{
                            $card_used_pro_money = ceil(bcmul(bcdiv($card_used_pro[$i]['total_money'],$card_used_pro_total,3),$order['dedamount'],3));
                            $card_used_pro_money_sy += $card_used_pro_money;
                        }
                        $card_used_pro_tmp[$card_used_pro[$i]['product_id']] = $card_used_pro_money;
                    }
                    $order['orderAmount'] = $order['orderAmount'] - $order['dedamount'];
                    $order['dedamount'] = 0;
                }
            }
            
            /*优惠券促销信息整理end*/

            $order_items = array();
            $discount = 0;
            $amount = 0;
            foreach ((array)$order_products[$o['id']] as $op) {
                if (!$op['product_name'] && !$op['product_id'] && !$op['product_no']) continue;

                if ($op['product_no'] == '30317' || $op['product_no'] == '201411316' || $op['product_no'] == '201411315') continue;

                $order_item = array();
                $sale_price = bcdiv($op['total_money'], $op['qty'], 3);
                if ($op['type'] == 2 || $op['type'] == 3 || $op['type'] == 5 || $op['type'] == 6 || 0 == bccomp($op['total_money'], 0,3)){
                    $cardAmount = 0;
                }else{
                    $cardAmount = isset($card_used_pro_tmp[$op['product_id']])?$card_used_pro_tmp[$op['product_id']]:0;//优惠券促销信息整理
                }

                // $cardAmount = 0;
                $order_item['prdno'] = $op['product_no'];
                $order_item['prdName'] = $op['product_name'];
                $order_item['barcode'] = $city_shop_products[$op['product_no']]?$city_shop_products[$op['product_no']]:'';
                $order_item['price'] = (float)$op['price'];
                $order_item['discount'] = bcsub((float) $op['price'], (float) $sale_price,3);
                $order_item['disPrice'] = (float)$sale_price;
                $order_item['count'] = (int)$op['qty'];
                $order_item['cardAmount']  = (float)$cardAmount;
                $order_item['totalAmount'] = bcsub((float) $op['total_money'], (float) $cardAmount,3);
                $order_item['distype'] = 2;
                $order_item['primaryCode'] = '';
                $order_item['disCode'] = '';
                $order_item['score'] = (float)$op['score'];
                $order_item['volume'] = $op['gg_name'];

                if ($op['type'] == 1 || $op['type'] == 4) $order_item['saletype'] = 1;
                if ($op['type'] == 2 || $op['type'] == 3 || $op['type'] == 5 || $op['type'] == 6 || 0 == bccomp($op['total_money'], 0, 3)) {
                    $order_item['price'] = 0;
                    $order_item['disPrice'] = 0;
                    $order_item['discount'] = 0;
                    $order_item['saletype'] = 2;
                }
                if ($o['order_type'] == 2) $order_item['saletype'] = 3;

                if(!isset($inner_codes[$op['product_id']][$op['product_no']])){
                    $prod_price = $this->ci->product_price_model->dump(array('product_id' => $op['product_id'], 'product_no'=> $op['product_no']), 'inner_code');
                    $inner_codes[$op['product_id']][$op['product_no']] = isset($prod_price['inner_code']) ? $prod_price['inner_code'] : '';
                }
                $order_item['innerCode'] = $inner_codes[$op['product_id']][$op['product_no']];

                $product_group_item = array();
                if(isset($product_groups[$op['product_id']])){
                    foreach($product_groups[$op['product_id']] as $pg){
                        $totalAmount = $pg['g_price'] * $pg['g_qty'];
                        $score = $op['score'] * ( $totalAmount / $order_item['totalAmount']);
                        $g_item = array(
                            'prdno' => $pg['product_no'],
                            'prdName' => $pg['product_name'],
                            'innerCode' => $pg['inner_code'],
                            'price' => (float)$pg['g_price'],
                            'discount' => bcsub((float)$pg['g_price'], (float)$pg['g_price'], 3),
                            'disPrice' => (float)$pg['g_price'],
                            'count' => (int) $pg['g_qty'],
                            'totalAmount' => (float) $totalAmount,
                            'cardAmount' => 0,
                            'distype' => 2,
                            'primaryCode' => '',
                            'disCode' => '',
                            'score' => (float) $score,
                            'volume' => $pg['volume'] . '/' . $pg['unit'],
                            'saletype' => 1
                        );
                        $g_item['barcode'] = $city_shop_products[$pg['product_no']]?$city_shop_products[$pg['product_no']]:'';
                        array_push($product_group_item, $g_item);
                    }
                }

                $order_item['groups'] = $product_group_item;

                $order_items[] = $order_item;

                // $discount += $order_item['discount'] * $op['qty'];

                // 判断是否为生鲜
                if ($order['wh'] == '' && $op['product_id'] && in_array($op['type'], array(1, 4))) {

                    $productinfo = $this->ci->db->query('select type from ttgy_product where id=' . $op['product_id'] . ' limit 1')->row_array();

                    if ($productinfo['type'] == '4') {
                        $order['wh'] = 'FSH1';
                    }
                }

                if ($op['product_id'] == '3664') {
                    $order['wh'] = 'FSH1';
                }

                $amount += $op['total_money'];
            }


            if ($order['wh'] == 'FSH1' && !in_array($area[$user_addresses[$o['address_id']]['province']]['id'], $area_refelect[1]) && ($o['shtime'] == 'after2to3days' || $o['shtime'] == 'after1to2days')) {
                // 指定上海仓且发外地
                $order['sendDate'] = $this->after2to3days($area[$user_addresses[$o['address_id']]['province']], array('cut_off_time' => 16), $o['time'], $o['stime']);
                $order['delivertime'] = 1;
            }

            $order['order_items'] = $order_items;
            if ($o['pay_parent_id'] == '6' && $o['pay_id'] == '1' && $amount >= $o['money']) {
                $order['disamount'] = (float)($amount - $o['money'] + $o['method_money']);
                $order['orderAmount'] = $amount;
            }

            $payment_info = $this->get_pool_payment($o);


            if ($o['use_money_deduction']) { // 帐户余额抵消
                $order['totalAmount'] = bcadd($order['totalAmount'], $o['use_money_deduction'], 3);
            }

            if ($o['jf_money']) { // 积分
                $order['totalAmount'] = bcadd($order['totalAmount'], $o['jf_money'], 3);
            }

            $order['payment_info'] = $payment_info;

            //取消订单处理
            if ($o['operation_id'] == 5) {
                $order['isCancel'] = 1;
            } else {
                $order['isCancel'] = 0;
            }


            $this->ci->load->model('o2o_store_physical_model');
            $this->ci->load->model('o2o_seller_model');
            $store = $this->ci->o2o_store_physical_model->dump(array('id'=>$o['store_id']));
            $seller = $this->ci->o2o_seller_model->dump(array('id'=>$store['seller_id']));
            $order['merchantNo'] = $seller['code'];
            $order['storeNo'] = $store['code'];
            $order['groupNo'] = $o['p_order_name'];
            $order['sendType'] = $o['send_type'];

            //o2o分享有礼活动
            if(!empty($order['ispay'])){
                $this->activeInvite($order['member_info']['buyerPhone']);
            }

            $f_orders[$o['id']] = $order;
        }
        if($error_list){
            $this->ci->load->model('jobs_model');
            $emailList = array( 'songtao@fruitday.com','lusc@fruitday.com');
            foreach ($emailList as $email) {
                $emailContent = implode('、',$error_list);
                $this->ci->jobs_model->add(array('email'=>$email,'text'=>$emailContent,'title'=>"缺少省市区"), "email");  
            }
        }

        $this->rpc_log = array('rpc_desc' => 'o2o订单推送', 'obj_type' => 'order');
        return $f_orders;
    }

    /**
     * 订单推送后的回调
     * status|orderNo|message
     * @return void
     * @author
     **/
    public function callback($filter)
    {
        /* status: 0 成功 */
        if (!$filter['status']) {
            $order_name = $filter['orderNo'];

            $this->ci->load->model('o2o_child_order_model');

            $order = $this->ci->o2o_child_order_model->dump(array('order_name' => $order_name));

            if (!$order) return array('result' => 0, 'msg' => '订单不存在');

            $id = $order['id'];

            if ($order['operation_id'] == 5) {
                $affected_rows = $this->ci->o2o_child_order_model->update(array('sync_status'=>1),array('id'=>$id));
                $this->ci->load->model('order_model');
                $this->ci->order_model->update(array('sync_status'=> 1),array('id' => $order['p_order_id']));
                // 请求取消接口
                $this->pool_cancel($order_name);

            } else {
                $data = array(
                    'sync_status' => 1
                );
                if($order['operation_id'] == 0){
                    $data['operation_id'] = 1;
                }
                $affected_rows = $this->ci->o2o_child_order_model->update($data, array('id' => $id));

                $orders = $this->ci->o2o_child_order_model->get_child_orders_by_parent_id($order['p_order_id']);
                $isUpdate = true;
                foreach($orders as $o){
                    if($o['operation_id'] != 1 && $o['id'] != $order['id']){
                        $isUpdate = false;
                    }
                }

                if($isUpdate){
                    $this->ci->load->model('order_model');
                    $p_order = $this->ci->order_model->dump(array('id' => $order['p_order_id']));
                    if($p_order['operation_id'] == 0){
                        $this->ci->order_model->update(array('operation_id' => 1, 'sync_status'=> 1),array('id' => $order['p_order_id']));
                    }else{
                        $this->ci->order_model->update(array('sync_status'=> 1),array('id' => $order['p_order_id']));
                    }
                }
            }

            return $affected_rows >= 0 ? array('result' => 1, 'msg' => '') : array('result' => 0, 'msg' => '更新失败');
        }

        // 失败发邮件
        $this->ci->load->model('jobs_model');
        $emailList = array('huangb@fruitday.com', 'songtao@fruitday.com', 'lusc@fruitday.com');
        foreach ($emailList as $email) {
            $emailContent = '订单[' . $filter['orderNo'] . ']推送OMS失败,原因：' . $filter['message'];
            $this->ci->jobs_model->add(array('email' => $email, 'text' => $emailContent, 'title' => "o2o推送订单失败" . $filter['orderNo']), "email");
        }

        $this->rpc_log = array('rpc_desc' => '订单回调', 'obj_type' => 'order');

        return array('result' => 0, 'msg' => '');
    }

    /**
     * 确认发货
     *
     * @return void
     * @author
     **/
    public function sendLogistics($filter)
    {
        $order_name = $filter['orderNo'];

        if (!$order_name) return array('result' => 0, 'msg' => '订单号不能为空');

        $this->ci->load->model('o2o_child_order_model');
        $order = $this->ci->o2o_child_order_model->dump(array('order_name' => $order_name));

        if (!$order) return array('result' => 0, 'msg' => '订单不存在');

        $this->ci->load->bll('o2o_pool');
        $rs = $this->ci->bll_o2o_pool->delivery($order, $filter);


        if ($rs['result'] == 1)
            $this->rpc_log = array('rpc_desc' => '订单发货', 'obj_type' => 'o2o_order', 'obj_name' => $order_name);

        return $rs;
    }

    /**
     * 批量发货
     *
     * @return void
     * @author
     **/
    public function batchSendLogistics($filter)
    {
        $deliverys = $filter['orders'];

        if (!$deliverys) return array('result' => 0, 'msg' => '发货信息不存在');

        $errorMsg = array();
        $this->ci->load->model('o2o_child_order_model');
        $this->ci->load->bll('o2o');
        foreach ($deliverys as $key => $delivery) {
            $order = $this->ci->o2o_child_order_model->dump(array('order_name' => $delivery['orderNo']));

            if (!$order) {
                $errorMsg[] = array('orderNo' => $delivery['orderNo'], 'msg' => '订单不存在');
                continue;
            }

            $rs = $this->ci->bll_o2o->delivery($order, $delivery);

            if ($rs['rs'] != 'succ') {
                $errorMsg[] = array('orderNo' => $delivery['orderNo'], 'msg' => $rs['msg']);
            }
        }

        $this->rpc_log = array('rpc_desc' => '批量发货', 'obj_type' => 'o2o_order',);

        return array('result' => 1, 'errorMsg' => $errorMsg);
    }

    /**
     * 订单取消(请求OMS)
     *
     * @return void
     * @author
     **/
    public function pool_cancel($order_name,$sync_status=1)
    {
        if (!$order_name) return array('result' => 0, 'msg' => '订单号不能为空');

        $return = array('result' => '1','msg' => '订单取消成功');

        if ($sync_status != 1 ) return $return;

        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'url' => POOL_O2O_OMS_URL,
            'method' => 'order.cancel',
            'data' => array('orderNo' => $order_name),
        );

        $log = array(
            'rpc_desc' => '订单取消',
            'obj_type' => 'o2o_order_cancel',
            'obj_name' => $order_name,
        );

        $this->ci->bll_rpc_o2o_request->set_rpc_log($log);
        $response = $this->ci->bll_rpc_o2o_request->realtime_call($params, 6);

        if($response === false){
            $error_info = $this->ci->bll_rpc_o2o_request->get_errorinfo();
            return array('result' => 0, 'msg' => $error_info['errorMessage']);
        }
        return array('result' => 1, 'msg' => '订单取消成功');
    }

    /**
     * 订单确认(请求OMS)
     *
     * @return void
     * @author
     **/
    public function pool_confirm($order_name,$sync_status=1)
    {
        if (!$order_name) return array('result' => 0, 'msg' => '订单号不能为空');

        $return = array('result' => '1','msg' => '订单确认收货成功');

        if ($sync_status != 1 ) return $return;

        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'url' => POOL_O2O_OMS_URL,
            'method' => 'order.confirm',
            'data' => array('ouOrderNo' => $order_name),
        );

        $log = array(
            'rpc_desc' => '订单确认收货',
            'obj_type' => 'o2o_order_confirm',
            'obj_name' => $order_name,
        );

        $this->ci->bll_rpc_o2o_request->set_rpc_log($log);
        $response = $this->ci->bll_rpc_o2o_request->realtime_call($params, 6);

        if($response === false){
            $error_info = $this->ci->bll_rpc_o2o_request->get_errorinfo();
            return array('result' => 0, 'msg' => $error_info['errorMessage']);
        }
        return array('result' => 1, 'msg' => '订单确认成功');
    }

    /**
     * 订单取消(提供取消接口)
     *
     * @return void
     * @author
     **/
    public function cancel($filter)
    {
        $order_name = $filter['orderNo'];
        $type = isset($filter['type']) ? $filter['type'] : 1;

        if (!$order_name) return array('result'=>0,'msg' => '订单号不能为空');

        $this->ci->load->model('o2o_child_order_model');
        $order = $this->ci->o2o_child_order_model->dump(array('order_name'=>$order_name),'id');

        if (!$order) return array('result' => 0, 'msg' => '订单不存在');

        $this->ci->load->bll('o2o_pool');
        $result = $this->ci->bll_o2o_pool->cancel($order_name, $type);

        $this->rpc_log = array('rpc_desc' => '订单取消','obj_type'=>'o2o_order','obj_name' => $order_name);

        return $result;
    }

    /**
     * 订单完成
     *
     * @return void
     * @author
     **/
    public function finish($filter)
    {

        $order_name = $filter['orderNo'];
        $score = $filter['score'] ? $filter['score'] : 0;

        if (!$order_name) return array('result' => 0, 'msg' => '订单号不能为空');

        $this->ci->load->model('o2o_child_order_model');
        $order = $this->ci->o2o_child_order_model->get_child_order($order_name);

        if (!$order) return array('result' => 0, 'msg' => '订单不存在');

        $this->ci->load->bll('o2o_pool');
        $rs = $this->ci->bll_o2o_pool->finish($order, $score);

        if ($rs['result'] == 1)
            $this->rpc_log = array('rpc_desc' => '订单完成', 'obj_type' => 'o2o_order', 'obj_name' => $order_name);

        return $rs;
    }

    /**
     * 批量完成
     *
     * @return void
     * @author
     **/
    public function batchFinish($filter)
    {
        $orders = $filter['orders'];

        if (!$orders) return array('result' => 0, 'msg' => '订单信息不存在');

        $errorMsg = array();
        $this->ci->load->model('o2o_child_order_model');
        $this->ci->load->bll('o2o');
        foreach ($orders as $key => $value) {
            $order = $this->ci->o2o_child_order_model->get_child_order($value['orderNo']);

            if (!$order) {
                $errorMsg[] = array('orderNo' => $value['orderNo'], 'msg' => '订单不存在');
                continue;
            }

            $rs = $this->ci->bll_o2o->finish($order, $value['score']);

            if ($rs['rs'] != 'succ') {
                $errorMsg[] = array('orderNo' => $value['orderNo'], 'msg' => $rs['msg']);
            }
        }

        $this->rpc_log = array('rpc_desc' => '批量订单完成', 'obj_type' => 'o2o_order',);

        return array('result' => 1, 'errorMsg' => $errorMsg);
    }

    /**
     * 同步订单状态
     *
     * @return void
     * @author
     **/
    public function status($filter)
    {
        $order_name = $filter['orderNo'];
        $status = $filter['status'];
        if (!$order_name) return array('result' => 0, 'msg' => '订单信息不存在');

        $this->ci->load->bll('o2o_pool');
        $rs = $this->ci->bll_o2o_pool->status($order_name, $status);
        if ($rs['result'] != 1) {
            return $rs;
        }

        $this->rpc_log = array('rpc_desc' => '订单状态', 'obj_type' => 'o2o_order',);

        return array('result' => 1, 'msg' => '成功');
    }

    /**
     * 更新支付状态
     *
     * @return void
     * @author
     **/
    public function pay_status($filter)
    {
        $orders = $filter['orders'];

        if (!$orders) return array('result' => 0, 'msg' => '订单信息不存在');

        $this->ci->load->bll('order', 'bll_order');
        foreach ($orders as $key => $order_name) {
            $rs = $this->ci->bll_order->pay_status($order_name);

            if ($rs['rs'] != 'succ') {
                $errorMsg[] = array('orderNo' => $order_name, 'msg' => $rs['msg']);
            }
        }

        $this->rpc_log = array('rpc_desc' => '批量订单支付状态', 'obj_type' => 'order');

        return array('result' => 1, 'errorMsg' => $errorMsg);
    }


    public function pushoms($params)
    {
        if (!$params['order_name']) return array('result' => 0, 'msg' => '空订单号');
        $orders = $this->get_push_orders($params['order_name']);
        if (!$orders) {
            return array('result' => 0, 'msg' => '订单不满足同步条件');
        }

        $this->ci->load->bll('pool/o2o/order');
        $this->ci->load->bll('rpc/o2o/request');

        $orderids = array_keys($orders);

        if ($orderids) {
            $this->set_sync($orderids, '2');
        }

        // 金额校验
        $orders = $this->check_order($orders);
        if (!$orders) return array('result' => 0, 'msg' => '订单金额异常');

        $this->ci->bll_rpc_o2o_request->set_rpc_log(array('rpc_desc' => '指定订单号同步', 'obj_type' => 'order'));

        $params = array(
            'url' => POOL_O2O_OMS_URL,
            'method' => 'order.save',
            'data' => array_values($orders),
        );

        $response = $this->ci->bll_rpc_o2o_request->realtime_call($params);

        if ($response === false && !empty($orderids)) {
            $this->set_sync($orderids, '0');
            $error = $this->ci->bll_rpc_o2o_request->get_errorinfo();
            return array('result' => 0, 'msg' => $error['errorMessage']);
        }

        return array('result' => 1, 'msg' => '同步成功');
    }

    /**
     * 同步支付方式
     *
     * @return void
     * @author
     **/
    public function syncfee($order_id)
    {
        if (!$order_id) return;

        $this->ci->load->model('order_model');

        $order = $this->ci->order_model->dump(array('id' => $order_id));

        if (!$order) return;

        $data = array(
            'chId' => $this->get_pool_channel($order['channel'], $order['is_enterprise']),
            'orderNo' => $order['order_name'],
            'ispay' => $order['pay_status'] == '1' ? 1 : 0,
            'payment' => (int)$order['pay_id'],
            'payDate' => ($order['update_pay_time'] && $order['update_pay_time'] != '0000-00-00 00:00:00') ? $order['update_pay_time'] : $order['time'],
        );

        if ($order['pay_parent_id'] == 4 && $order['pay_id'] == 6) {
            $data['payment'] = 1;
        }

        if (isset($this->_online_pay[$order['pay_parent_id']]) || $order['order_type'] == 2) {
            $data['payment'] = 0;
        }

        $data['payment_info'] = $this->get_pool_payment($order, true);

        $this->ci->load->bll('rpc/request');

        $log = array(
            'rpc_desc' => '订单支付推送',
            'obj_type' => 'order_payment',
            'obj_name' => $order['order_name'],
        );
        $this->ci->bll_rpc_request->set_rpc_log($log);

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_SYNCFEE_URL, $data, 'POST', 20);

        if ($rs['result'] != 1) {
            return array('status' => 'fail', 'msg' => '推送失败');
        }

        return array('status' => 'succ', 'msg' => '推送成功');
    }


    public function get_pool_payment($order, $syncfee = false)
    {
        $payment_info = array();

        if ($order['pay_status'] == '1' && $order['pay_parent_id'] && $order['money'] > 0 && !in_array($order['pay_parent_id'], array('4', '6'))) {
            $order['bank_discount'] = $order['bank_discount']?$order['bank_discount']:0;
            $payment_info[] = array(
                'paym' => $this->_online_pay[$order['pay_parent_id']]['way_id'], // 1:支付宝付款,2:联华OK会员卡在线支付,3:网上银行支付,4:线下支付,5:账户余额支付,6:券卡支付
                'payAmount' => bcsub($order['money'],$order['bank_discount'],2),//(float)$order['money'],
                'payplatform' => $this->_online_pay[$order['pay_parent_id']]['children_platform_id'][$order['pay_id']] ? $this->_online_pay[$order['pay_parent_id']]['children_platform_id'][$order['pay_id']] : $this->_online_pay[$order['pay_parent_id']]['platform_id'],
                'ticketCode' => '',
                'ticketCount' => 0,
                'chrgno' => ($order['pay_id'] == '00003' && !$order['trade_no']) ? $order['order_name'] : $order['trade_no'],
                'disCode' => '',
            );
        }

        if ($syncfee === false) {
            if ($order['use_money_deduction'] > 0) { // 帐户余额抵消
                $payment_info[] = array(
                    'paym' => 9,
                    'payAmount' => (float)$order['use_money_deduction'],
                    'payplatform' => null,
                    'ticketCode' => '',
                    'ticketCount' => 0,
                    'chrgno' => $order['trade_no'],
                    'disCode' => '',
                );
            }
        }

        if ($syncfee === false) {
            if ($order['jf_money']) { // 积分
                $payment_info[] = array(
                    'paym' => 8,
                    'payAmount' => (float)$order['jf_money'],
                    'payplatform' => '',
                    'ticketCode' => '',
                    'ticketCount' => 0,
                    'chrgno' => '',
                    'disCode' => '',
                );
            }
        }

        if ($order['pay_parent_id'] == '6') {
            $juan = $this->ci->db->select('card_number')->from('pro_card')->where(array('order_name' => $order['order_name'], 'is_used' => '1', 'is_sent' => '1'))->get()->row_array();

            $payment_info[] = array(
                'paym' => 5,
                'payAmount' => (float)$order['money'],
                'payplatform' => 5001,
                'ticketCode' => $juan ? $juan['card_number'] : '',
                'ticketCount' => $juan ? 1 : 0,
                'chrgno' => '',
                'disCode' => '',
            );
        }

        return $payment_info;
    }

    public function get_pool_channel($channel, $is_enterprise)
    {
        switch ($channel) {
            case '3':
                $channel = 1;
                break;
            case '5':
                $channel = 2;
                break;
            case '8':
                $channel = 10;
                break;
            case '9':
                $channel = 11;
                break;
        }

        if ($is_enterprise) {
            $channel = 8;
        }

        return (int)$channel;
    }

    public function syncOrderInfo($filter){
        $order_names = explode(',', $filter['orderNo']);
        return $this->get_push_orders($order_names, false);
    }

    public function allocation($filter){
        $this->ci->load->helper('public');
        $sendDate = $filter['sendDate'];
        $products = $filter['products'];
        $store_id = $filter['storeId'];
        if(empty($products) || empty($sendDate)){
            return array('result' => 0, 'msg' => '参数错误');
        }

        $time = date('Y-m-d H:i:s');
        $money = 0;
        $order_name = 'A' . date("ymdi").rand_code(4);

        $this->ci->load->model('product_price_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model('o2o_store_model');
        $this->ci->load->model('o2o_store_physical_model');
        $this->ci->load->model('area_model');
        $store = $this->ci->o2o_store_model->dump(array('id' => $store_id));
        if(empty($store)){
            return array('result' => 0, 'msg' => '门店错误');
        }
        $physcial_store = $this->ci->o2o_store_physical_model->dump(array('id' => $store['physical_store_id']));
        $province = $this->ci->area_model->dump(array('id' => $store['province_id']));
        $city = $this->ci->area_model->dump(array('id' => $store['city_id']));
        $area = $this->ci->area_model->dump(array('id' => $store['area_id']));
        $consignee_info = [
            "province" => $province['name'],
            "city" => $city['name'],
            "region" => $area['name'],
            "reAddress" => $store['address'],
            "receiver" => $physcial_store['name'],
            "rePhone" => "",
            "reMobile" => $store['phone']
        ];

        $ids = array_column($products, 'product_id');
        $products = array_column($products, null, 'product_id');
        $groupProducts = $this->ci->db->select('g.product_id,g.g_product_id,g.g_qty')
            ->from('product_groups g')
            ->join('product_price p', 'g.g_product_id = p.product_id')
            ->where_in('g.product_id', $ids)
            ->get()->result_array();

        foreach ($groupProducts as $gpt) {
            if(!isset($products[$gpt['g_product_id']])){
                $products[$gpt['g_product_id']]['num'] = 0;
            }
            $products[$gpt['g_product_id']]['num'] += $gpt['g_qty'] * $products[$gpt['product_id']]['num'];
            $products[$gpt['g_product_id']]['product_id'] = $gpt['g_product_id'];
        }
        $groupProducts = array_column($groupProducts, null, 'product_id');
        $items = [];
        foreach ($products as $prod){
            if(isset($groupProducts[$prod['product_id']])){
                continue;
            }
            if($prod['num'] <= 0){
                return array('result' => 0, 'msg' => '商品:'.$prod['product_id']. '数量错误');
            }
            $pp = $this->ci->product_price_model->dump(array('product_id' => $prod['product_id']));
            if(empty($pp)){
                return array('result' => 0, 'msg' => '商品:'.$prod['product_id']. '不存在');
            }
            $money += $pp['price'] * $prod['num'];
            array_push($items, [
                "prdno" => $pp['product_no'],
                "price" => $pp['price'],
                "discount" => 0,
                "add_discount" => 0,
                "disPrice" => $pp['price'],
                "count" => $prod['num'],
                "cardAmount" => 0,
                "itemDisAmount" => 0,
                "totalAmount" => $pp['price'] * $prod['num'],
                "distype" => 2,
                "primaryCode" => "",
                "disCode" => "",
                "score" => 0,
                "saletype" => 1
            ]);
        }

        $order = array(
            "type" => 8,//
            "chId" => 13,//
            "isActivity" => 0,
            "orderNo" => $order_name,
            "createdate" => $time,
            "payDate" => $time,
            "sendDate" => $sendDate,
            "delivertime" => 1,
            "delDay" => 0,
            "receiverDate" => $sendDate,
            "billno" => "",
            "score" => 0,
            "gcardinfo" => "",
            "note" => "",
            "orderAmount" => $money,
            "freightFee" => 0,
            "totalAmount" => $money,
            "disamount" => 0,
            "dedamount" => 0,
            "ispay" => 0,
            "payment" => 12,//
            "status" => 0,
            "deliverystate" => 2,
            "invoice_info" => (object) [],
            "member_info" => (object) [],
            "consignee_info" => $consignee_info,
            "wh" => "",
            "order_items" => $items,
            "payment_info" => [],
            "isCancel" => 0,
            "isPrint" => 1,
            "pmt_detail" => [],
            "lgcCode" => $physcial_store['sap_code']
        );

        $this->ci->db->insert('ttgy_o2o_allocation_order', array(
            'order_name' => $order_name,
            'content' => json_encode($filter['products'], JSON_UNESCAPED_UNICODE),
            'admin_id' => (int)$filter['admin_id'],
            'send_date' => $filter['sendDate'],
            'store_id' => $store_id,
            'sync_status' => 0,
            'created_at' => $time
        ));

        $this->ci->load->bll('rpc/request');
        $this->ci->bll_rpc_request->set_rpc_log( array(
            'rpc_desc' => 'o2o调拨单','obj_type'=>'order.allocation','obj_name'=>$order_name
        ));
        $response = $this->ci->bll_rpc_request->realtime_call(POOL_ORDER_URL,[$order]);
        if($response === false){
            return $response;
        }

        return array('order_name' => $order_name);
    }

    /**
     * 分享有礼活动, 支付后发券
     * @param $mobile
     */
    private function activeInvite($mobile)
    {
        if(empty($mobile)){
            return;
        }
        $rs = $this->ci->db->query("select id,uid,status from ttgy_o2o_active_invite where inv_mobile = ?", array($mobile))->row_array();
        if(empty($rs) || empty($rs['uid']) || $rs['status'] == 1){
            return;
        }
        $p_card_number = 'o2oinv_';
        $card_number = $p_card_number . $this->rand_card_number($p_card_number);
        $user = $this->ci->db->query("select count(*) cou from ttgy_o2o_active_invite where status=1 and uid = ?", array($rs['uid']))->row_array();
        if($user['cou'] < 5){
            $money = 5;
        }elseif($user['cou'] < 10){
            $money = 8;
        }else{
            $money = 10;
        }
        $sc = $this->send_cart($rs['uid'], $card_number, 0, $money, '【天天果园-闪电送】春天来了，你吃水果我拿券！');
        if($sc){
            $this->ci->db->update('ttgy_o2o_active_invite', array(
                'card_number' => $card_number,
                'card_money' => $money,
                'status' => 1,
            ), array('id' => $rs['id']));
        }
    }

    private function send_cart($uid = 0, $card_number, $product_id = 0, $card_money = 0, $remarks = '')
    {
        if(empty($card_number)){
            return false;
        }

        $card_data = array(
            'uid' => $uid,
            'sendtime' => date("Y-m-d"),
            'card_number' => $card_number,
            'card_money' => $card_money,
            'product_id' => $product_id,
            'maketing' => 1,
            'is_sent' => 1,
            'restr_good' => empty($product_id) ? 0 : 1,
            'remarks' => $remarks,
            'time' => date("Y-m-d"),
            'to_date' => date("Y-m-d", strtotime('+7 day')),
            'can_use_onemore_time' => 'false',
            'can_sales_more_times' => 'false',
            'card_discount' => 1,
            'order_money_limit' => 0,//100
            'black_list' => '',
            'channel' => ''
        );
        $result = $this->ci->db->insert('card', $card_data);
        return $result;
    }

    private function rand_card_number($p_card_number = '')
    {
        $a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
        $a_array = explode(",", $a);
        $tname = '';
        for ($i = 1; $i <= 10; $i++) {
            $tname .= $a_array[rand(0, 31)];
        }
        if ($this->checkCardNum($p_card_number . $tname)) {
            $tname = $this->rand_card_number($p_card_number);
        }
        return $tname;
    }

    private function checkCardNum($card_number)
    {
        $this->ci->db->from('card');
        $this->ci->db->where('card_number', $card_number);
        $query = $this->ci->db->get();
        $num = $query->num_rows();
        if ($num > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function sold_out($store_product){
        if (empty($store_product)) {
            return;
        }
        $sold_outs = array();
        $date = date('Y-m-d H:i:s');
        $exist = array();
        $exist_tmp = $this->ci->db->query('select store_id,product_id from ttgy_o2o_sold_out_log where created_at > ?', array(date('Y-m-d')))->result_array();
        foreach ($exist_tmp as $v) {
            $exist[$v['store_id']][] = $v['product_id'];
        }
        foreach ($store_product as $key => $value) {
            $store_id = $key;
            $value = array_unique($value);
            $sold_out = $this->ci->db->select('store_id,product_id')
                ->from('ttgy_o2o_store_goods')
                ->where('store_id', $store_id)
                ->where_in('product_id', $value)
                ->where('stock', 0)
                ->get()
                ->result_array();
            foreach ($sold_out as $k => $v) {
                if (in_array($v['product_id'], $exist[$store_id])) {
                    continue;
                }
                $v['created_at'] = $date;
                $sold_outs[] = $v;
            }
        }

        if (!empty($sold_outs)) {
            $this->ci->db->insert_batch('ttgy_o2o_sold_out_log', $sold_outs);
        }
    }
}
