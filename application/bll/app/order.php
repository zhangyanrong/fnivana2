<?php
namespace bll\app;
include_once("app.php");

/**
 * 商品相关接口
 */
class Order extends app {
    private $can_comment_period = "3 months";

    function __construct($params = array()) {
        $this->ci = &get_instance();
    }

    function orderInit($params) {

        //银行秒杀排队
        $bank_limit = $this->orderLimit();
        if($bank_limit['code'] != 200)
        {
            return array("code"=>$bank_limit['code'],"msg"=>$bank_limit['msg'],"data"=>$bank_limit['data']);
        }

        $order_info = parent::call_bll($params);

        if (!$order_info['order_id'] && isset($order_info['code'])) {
            return $order_info;
        }

        /*用户积分start*/
        $this->ci->load->bll('user');
        $user_info = $this->ci->bll_user->userInfo(array('connect_id' => $params['connect_id']));
        $order_info['user_money'] = number_format($user_info['money'], 2, '.', '');
        $order_info['user_coupon_num'] = $user_info['coupon_num'];
        $order_info['user_mobile'] = $user_info['mobile'];
        /*用户积分end*/

        $order_detail = $this->ci->bll_order->b2oOrderDetails($order_info['uid'], $params['source'], $params['device_id'], $params);

        $order_info['order_money'] = number_format($order_detail['money'], 2, '.', '');
        $order_info['goods_money'] = number_format($order_detail['total_amount_money'], 2, '.', '');
        $order_info['method_money'] = number_format($order_detail['method_money'], 2, '.', '');
        $order_info['order_limit'] = number_format($order_detail['order_limit'], 2, '.', '');
        $order_info['jf_money'] = number_format($order_detail['jf_money'], 2, '.', '');
        $order_info['use_card'] = $order_detail['use_card'];
        $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
        $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
        $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');

        $order_info['has_invoice'] = $order_detail['has_invoice'];
        $order_info['no_invoice_message'] = $order_detail['no_invoice_message'];
        //购物车金额＋运费
        $order_info['need_pay_money'] = number_format(($order_detail['total_amount_money'] + $order_detail['method_money'])-$order_detail['post_discount'], 2, '.', '');

        $order_info['total_package_count'] = $order_detail['total_package_count'];
        $total_package_weight = number_format($order_detail['total_package_weight'],2,'.','');
        $order_info['total_package_weight'] = $total_package_weight.'kg';

        //邮费特权
        $order_info['post_url'] = $order_detail['post_url'];
        $order_info['post_discount'] = $order_detail['post_discount'];
        $order_info['is_open_post'] = $order_detail['is_open_post'];

        //闪鲜卡
        $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
        $order_info['fresh_no'] = $order_detail['fresh_no'];

        //积点
        $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');

        //优惠券改造
        if (!empty($order_detail['use_card'])) {
            $this->ci->load->model('card_model');
            $cardInfo = $this->ci->card_model->get_card_info($order_detail['use_card']);
            $cards[] = $cardInfo;
            $card = $this->getCouponUseRange($cards);
            $order_info['use_range'] = $card[0]['use_range'];
            $order_info['remarks'] = $card[0]['remarks'];
        } else {
            $order_info['use_range'] = '';
            $order_info['remarks'] = '';
        }

        $cart_info = $order_info['carts_info'];
        $goods_money = $cart_info['total_amount'];
        $check_result = $this->ci->b2o_parent_order_model->check_cart_pro_status($cart_info);//重新组织商品属性判断
        $check_order_result = $this->ci->bll_order->check_order_data($order_info['uid'], $cart_info, $params, $goods_money, $check_result, $user_info, $order_detail, 'init');
        if ($check_order_result) {
            return array("code" => $check_order_result['code'], "msg" => $check_order_result['msg']);
        }

        unset($order_info['carts_info']);
        /*订单数据校验end*/

        //暂时关闭拆包裹开关
        $order_info['show_package'] = 0;

        //自提
        $no_self_products = $this->ci->config->item('no_self_products');
        $store_ids = explode(',',$params['store_id_list']);
        $sid = $store_ids[1];

        //自提
        $t_packcount = 0;
        foreach($order_info['package'] as $k=>$v)
        {
            if($v['package_type'] == 1)
            {
                $t_packcount++;
            }
        }

        foreach($order_info['package'] as $key=>$val)
        {
            $self_pick = array(
                'is_can'=>0,
                'is_select'=>0,
                'store_id'=>'',
                'store_name'=>'',
                'store_address'=>'',
            );

            if($val['package_type'] == 1 && !empty($sid) && $val['zt_send_time'])
            {
                $p_pro_ids = array_column($val['item'],'product_id');
                $no_can_self_pro = array_intersect($p_pro_ids, $no_self_products);
                $this->ci->load->model('b2o_store_model');
                $store_data = $this->ci->b2o_store_model->dump(array('id' =>$sid));
                if($store_data['self_pick'] == 1 && empty($no_can_self_pro) && $t_packcount == 1)
                {
                    $self_pick['is_can'] = 1;
                    $self_pick['store_id'] = $sid;
                    $self_pick['store_name'] = $store_data['name'];
                    $self_pick['store_address'] = '上海市'.$store_data['address'];
                }
            }
            $order_info['package'][$key]['store'] = $self_pick;
        }

        $is_show = 0;
        foreach($order_info['package'] as $key=>$val)
        {
            if($val['package_type'] == 1)
            {
                $is_show = 1;
            }

            //商品超售 - 次日达
            $pg_times = array_keys($val['send_time']);
            $pg_send_time = $pg_times[0];
            $now_send_time = date("Ymd", strtotime('+1 days',time()));
            if($pg_send_time == $now_send_time && $val['package_type'] == 1)
            {
                $order_info['package'][$key]['package_type'] = 2;
            }
        }

        //截单时间－文案
        if($is_show == 1)
        {
            $store_ids = explode(',',$params['store_id_list']);
            $sid = $store_ids[1];
            $m_sid = $store_ids[0];
            $this->ci->load->model('b2o_store_model');
            $store_data = $this->ci->b2o_store_model->dump(array('id' =>$sid));
            $m_store_data = $this->ci->b2o_store_model->dump(array('id' =>$m_sid));
            
            $open_time = strtotime(date('Y-m-d').' '.$store_data['open_time']);
            $js_time = date("H",$open_time+3600).":00 - ".$store_data['close_time'];
            $order_info['package_title'] = "即时达商品每日".$js_time."配送,明日达需在每日".$m_store_data['close_time']."前下单";
        }
        else
        {
            $store_ids = explode(',',$params['store_id_list']);
            if(count($store_ids) >= 1)
            {
                $sid = $store_ids[0];
                $this->ci->load->model('b2o_store_model');
                $store_data = $this->ci->b2o_store_model->dump(array('id' =>$sid));
                $order_info['package_title'] = "明日达需在每日".$store_data['close_time']."前下单";
            }
            else
            {
                $order_info['package_title'] = '';
            }
        }

        //缺货
        if($is_show == 1)
        {
            $stock_list = array(
                array('id'=>2,'text'=>'继续配送/自提有货商品(缺货商品直接退款)','is_select'=>1),
                // 1=>array('id'=>3,'text'=>'直接取消订单并退款','is_select'=>0),
                array('id'=>1,'text'=>'电话与我联系再操作','is_select'=>0),
            );
        }
        else
        {
            $stock_list = array();
        }
        $order_info['stock_list'] = $stock_list;


        return array("code" => "200", "msg" => "succ", "data" => $order_info);
    }

    function orderList($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            $return = $result['list'];
            $curr_time = time();
            $order_names = array_column($return, 'order_name');
            //订单申诉处理
            if (!empty($return)) {
                foreach ($return as &$val) {
                    //1.初始判断(是否符合申诉时间)
                    // if($val['shtime']=='after2to3days'){
                    // 	$send_time = date("Y-m-d",strtotime($val['time']));
                    // 	$can_report_issue_time = date("Y-m-d",strtotime("+6 days" ,strtotime($send_time)));
                    // }elseif ($val['shtime'] == 'after1to2days') {
                    // 	$send_time = date("Y-m-d",strtotime($val['time']));
                    // 	$can_report_issue_time = date("Y-m-d",strtotime("+5 days" ,strtotime($send_time)));
                    // } elseif ($val['stime'] == '2hours') {
                    // 	$send_time = date("Y-m-d",strtotime($val['time']));
                    // 	$can_report_issue_time = date("Y-m-d",strtotime("+3 days" ,strtotime($send_time)));
                    // }else{
                    // 	$send_time = date("Y-m-d",strtotime($val['shtime']));
                    // 	$can_report_issue_time = date("Y-m-d",strtotime("+3 days" ,strtotime($send_time)));
                    // }
                    if (in_array($val['order_type'], array(2, 3, 4))) {
                        $can_report_issue = 'false';
                        //$can_report_issue_new = (in_array($val['operation_id'], array(2,3,9))) ? 'true' : 'false';
                    } else {
                        $send_time = date("Y-m-d", strtotime($val['send_date']));
                        $can_report_issue_time = date("Y-m-d", strtotime("+3 days", strtotime($send_time)));
                        $can_report_issue = ($curr_time <= strtotime($can_report_issue_time) && in_array($val['operation_id'], array(2, 3, 6, 9))) ? 'true' : 'false';
                        $can_report_issue_new = (in_array($val['operation_id'], array(2, 3, 6, 9))) ? 'true' : 'false';
                    }
                    $report_issue_num = 0;

                    if ($can_report_issue_new == 'true') {
                        $val['can_report_issue_new'] = 'true';
                    } else {
                        $val['can_report_issue_new'] = 'false';
                    }

                    if ($can_report_issue == 'false') {
                        $val['can_report_issue'] = 'false';
                    } else {
                        foreach ($val['item'] as $item) {
                            if ($item['product_type'] != 3) {
                                $product_ids[$item['product_id']] = $item['product_id'];
                                $report_issue_num++;
                            }
                        }
                    }
                    $val['report_issue_num'] = $report_issue_num;
                }
                //2.深入判断(是否订单内的商品已经申诉)
                $qualitys_where_in = array(
                    array('key' => 'ordername', 'value' => $order_names),
                    array('key' => 'product_id', 'value' => $product_ids),
                );
                $qualitys_res = $this->ci->quality_complaints_model->selectQualitys("ordername,product_id", '', $qualitys_where_in);
                $qualitys = array();
                foreach ($qualitys_res as $v) {
                    $qualitys[$v['ordername']][$v['product_id']] = 1;
                }
                foreach ($return as &$value) {
                    if (!empty($value['report_issue_num'])) {
                        if (!isset($qualitys[$value['order_name']])) {
                            $value['can_report_issue'] = 'true';
                        } else {
                            if ($value['report_issue_num'] == count($qualitys[$value['order_name']])) {
                                $value['can_report_issue'] = 'false';
                            } else {
                                $value['can_report_issue'] = 'true';
                            }
                        }
                    }
                    unset($value['report_issue_num']);
                }
            }
        }
        return $return;
    }

    function useCard($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');

            //闪鲜卡
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    function cancelUseCard($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');

            //闪鲜卡
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    function usejf($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');

            //闪鲜卡
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    function cancelUsejf($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');

            //闪鲜卡
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    function orderInfo($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {

            if (in_array($result['order_type'], array(2, 3, 4))) {
                $can_report_issue = false;
            } else {
                if ($result['operation_id'] == 6 || $result['operation_id'] == 9) {
                    $can_report_issue = 'true';
                } else {
                    $can_report_issue = 'false';
                }
            }

            //获取订单评论
            $comments = $this->ci->comment_model->selectComments("product_id", array('order_id' => $result['id']));
            $comments_product_ids = array_column($comments, 'product_id');

            /*商品处理*/
            $order_comments = $this->ci->comment_model->selectComments("id", array('order_id' => $result['id']));
            $order_comments = array_column($order_comments, null, 'product_id');

            $package_type = array();
            foreach ($result['package'] as $k => $v) {
                foreach ($v['item'] as $key => $value) {
                    //商品可评论
                    if (in_array($value['product_id'], $comments_product_ids) || $value['type'] != 'normal' || $result['time'] < date("Y-m-d", strtotime('-' . $this->can_comment_period))) {
                        $result['package'][$k]['item'][$key]['can_comment'] = 'false';
                    } else {
                        $result['package'][$k]['item'][$key]['can_comment'] = 'true';
                    }
                    //商品可申诉
                    if ($can_report_issue == 'true') {
                        if ($value['type'] != 'normal') {
                            $result['package'][$k]['item'][$key]['can_report_issue'] = 'false';
                        } else {
                            $result['package'][$k]['item'][$key]['can_report_issue'] = 'true';
                        }
                    } else {
                        $result['package'][$k]['item'][$key]['can_report_issue'] = 'false';
                    }

                    if ($value['product_id'] && $value['product_no']) {
                        $sqlbb = "SELECT id FROM ttgy_quality_complaints WHERE uid=" . $result['uid'] . " AND ordername='" . $result['order_name'] . "' AND product_id=" . $value['product_id'] . " AND product_no='" . $value['product_no'] . "' LIMIT 1";
                        $resbb = $this->ci->db->query($sqlbb)->row_array();
                        if ($resbb) {
                            $result['package'][$k]['item'][$key]['has_report_issue'] = 1;
                            $result['package'][$k]['item'][$key]['quality_complaints_id'] = $resbb['id'];
                        } else {
                            $result['package'][$k]['item'][$key]['has_report_issue'] = 0;
                        }
                    }
                                    //商品超售 - 次日达
                    if(isset($value['is_oversale']) && $value['is_oversale'] == 1)
                    {
                        $result['package'][$k]['package_type'] = 2;
                    }
                }
                if(isset($v['store']) && $v['store']['is_select'] == 1){
                    if(empty($v['zt_send_time'])){
                        $isShowChangeTimeButton = false;
                    }
                    $result['package'][$k]['send_time'] = $v['zt_send_time'];
                }
                array_push($package_type,$v['package_type']);
            }

            //封装地址
            $result['show_address'] = array(
                'name'=>$result['name'],
                'mobile'=>$result['mobile'],
                'address'=>$result['address'],
                'flag'=>$result['flag'],
                'address_id'=>$result['address_id'],
                'isDefault'=>$result['isDefault'],
            );
            $addr_list = $this->ci->user_model->geta_user_address($result['uid'], $result['address_id'],'', $params['source']);
            $result['select_address'] = $addr_list[0];

            //变更地址按钮
            $result['isShowChangeAddressButton'] = $result['change_addr_status'];
            if(in_array($result['order_type'],array(3,4)) || in_array(1,$package_type))
            {
                $result['isShowChangeAddressButton'] = 0;
            }

            //变更时间按钮
            $result['isShowChangeTimeButton'] = 1;
            if(isset($isShowChangeTimeButton) && $isShowChangeTimeButton === false){
                $result['isShowChangeTimeButton'] = 0;
            }
            $s_date = date('Y-m-d',strtotime($result['time']));
            $e_date = date('Y-m-d');
            $diff_day  = (strtotime($e_date) - strtotime($s_date))/86400;
            if($diff_day > 3)
            {
                $result['isShowChangeTimeButton'] = 0;
            }

            //自提
            if($result['order_type'] == 4)
            {
                $result['isShowChangeTimeButton'] = 0;
            }

            //缺货文案
            if($result['no_stock'] == 2)
            {
                $result['no_stock'] = '若订单出现缺货商品，我希望：继续配送/自提有货商品（缺货商品直接退款）';
            }
            else if($result['no_stock'] == 3)
            {
                $result['no_stock'] = '若订单出现缺货商品，我希望：直接取消订单并退款';
            }
            else
            {
                $result['no_stock'] = '若订单出现缺货商品，我希望：电话与我联系再操作';
            }
            
            if(!in_array($result['order_type'],array(3,4)))
            {
                $result['no_stock'] = '';
            }

            //红包链接
            if($result['p_order_id'] >0)
            {
                $packet_info = $this->ci->user_model->redBagTag($result['p_order_id']);
            }
            else
            {
                $packet_info = $this->ci->user_model->redBagTag($result['id']);
            }

            $packet_info = array(); //暂停红包
            if(!empty($packet_info))
            {
                $bag_url = "http://awshuodong.fruitday.com/sale/redBag1710/wechat.html?link_tag=". $packet_info['link_tag'];
                $result['redBag'] = array(
                    'mainTitle'=>'【天天果园】你的好友送你一个大红包，快打开看看！！！',
                    'title'=>'超大红包等你来撩，用心抢，放肆买~',
                    'image'=>'https://huodongcdnws.fruitday.com/sale/appindex/images/redbag.jpg',
                    'url'=>$bag_url,
                );
            }
            else
            {
                $result['redBag'] = array(
                    'mainTitle'=>'【天天果园】你的好友送你一个大红包，快打开看看！！！',
                    'title'=>'超大红包等你来撩，用心抢，放肆买~',
                    'image'=>'https://huodongcdnws.fruitday.com/sale/appindex/images/redbag.jpg',
                    'url'=>'',
                );
            }
        }
        return array('code' => '200', 'msg' => 'succ', 'data' => $result);
    }

    /*
     * 订单物流评价 － new
     */
    function orderEval($params) {
        $order_info = parent::call_bll($params);

        $uid = $order_info['uid'];
        $order_name = $order_info['order_name'];
        $score = $params['score'];
        $remark = $params['remark'] ? $params['remark'] : '';
        $starWord = json_decode($params['star_word'], true);
        $packageStarWord = json_decode($params['package_star_word'], true);
        $expressStarWord = json_decode($params['express_star_word'], true);

        //new
        $score_time = $params['score_time'];
        $score_service = $params['score_service'];
        $score_show = $params['score_show'];

        $score_ensemble = $params['score_ensemble'];
        $score_package = $params['score_package'];
        $score_express = $params['score_express'];

        if ($score_time > 0 || $score_service > 0 || $score_show > 0) {
            $all = (int)$score_time + (int)$score_service + (int)$score_show;
            $all = round($all / 3);
            $score = intval($all);
        } else {
            if ($score > 0) {
                $score_time = $score;
                $score_service = $score;
                $score_show = $score;
            } else {
                $score = 1;
                $score_time = 0;
                $score_service = 0;
                $score_show = 0;
            }
        }

        if(version_compare($params['version'], '5.2.0') >= 0){
            if($params['platform'] == 'IOS'){
                $score = $score_service;
            } else {
                $score = $score_time;
            }
        }

        $dataSupply = array();
        if(version_compare($params['version'], '5.4.0') >=0){
            $dataSupply = array('score_ensemble' => $score_ensemble, 'score_package' => $score_package, 'score_express' => $score_express);
            $score = round(((int)$score_ensemble + (int)$score_package + (int)$score_express) / 3) ;
        }

        $this->ci->load->model('evaluation_model');

        $evl = $this->ci->evaluation_model->get_info($uid, $order_name);
        if (!empty($evl)) {
            return array('code' => 300, 'msg' => '用户订单物流已评价');
        }

        if(version_compare($params['version'], '5.5.0') < 0){
            if($score < 1 || mb_strlen($remark) < 10){
                return array('code'=>300, 'msg'=>'至少一颗星且评论必须超过10个字');
            }
        }
        $data = array(
            'uid' => $uid,
            'order_id' => $order_name,
            'score' => $score,
            'remark' => $remark,
            'star_word' => implode(',', $starWord),
            'package_star_word' => implode(',', $packageStarWord),
            'express_star_word' => implode(',', $expressStarWord),
            'score_time' => $score_time,
            'score_service' => $score_service,
            'score_show' => $score_show,
            'ctime' => time()
        );

        $this->ci->evaluation_model->insert(array_merge($data,$dataSupply));

        //赠送积分
        if (!empty($remark) && $params['version'] >= '3.9.0') {
            $this->ci->load->model('user_model');
            $this->ci->load->model('user_jf_model');

            $jf = $this->ci->user_model->getUserJf($uid);
            $data = array(
                'jf' => '5',
                'reason' => '评论物流,获得5积分',
                'time' => date("Y-m-d H:i:s"),
                'uid' => $uid,
            );
            $data['type'] = '评论商品';
            $this->ci->user_jf_model->insert($data);
            $this->ci->user_model->updateJf($uid, $jf, 1);
        }

        return array('code' => 200, 'msg' => 'success');
    }

    /*
	 * 优惠券使用范围
	 */
    private function getCouponUseRange($result) {
        if (!empty($result)) {
            foreach ($result as $val) {
                if (strpos($val['product_id'], ",") === false) {
                    $productids[] = $val['product_id'];
                } else {
                    $productids = isset($productids) ? array_merge(explode(',', $val['product_id']), $productids) : explode(',', $val['product_id']);
                }
            }
            if (!empty($productids)) {
                $this->ci->load->model("product_model");
                $where_in[] = array('key' => 'id', 'value' => $productids);
                $results = $this->ci->product_model->selectProducts('product_name,id', '', $where_in);
                foreach ($results as $key => $val) {
                    $products[$val['id']] = $val['product_name'];
                }
            }
            foreach ($result as &$val) {
                if (empty($val['product_id'])) {
                    $val['use_range'] = "全站通用(个别商品除外)";
                } elseif (strpos($val['product_id'], ",") === false) {
                    $val['use_range'] = "仅限" . $products[$val['product_id']] . "使用";
                    $val['card_product_id'] = $val['product_id'];
                } else {
                    $currids = explode(',', $val['product_id']);
                    $curr_range = array();
                    foreach ($currids as $curr_val) {
                        $curr_range[] = $products[$curr_val];
                        // $val['card_product_id'] = $curr_val;
                    }
                    $val['card_product_id'] = $val['product_id'];
                    $val['use_range'] = "仅限" . join(",", $curr_range) . "使用";
                }
                if ($val['order_money_limit'] > 0)
                    $val['use_range'] .= "满" . $val['order_money_limit'] . "使用";
//                $val['to_date'] = date("Y-m-d",strtotime("{$val['to_date']} -1 day"));

                if ($val['maketing'] == 1) {
                    if ($val['remarks'] == '仅限18元正价果汁抵用') {
                        $val['use_range'] = '';
                    }
                    unset($val['card_product_id']);
                    $val['card_o2o_only'] = 1;
                }

                if ('仅限app购买美国加州樱桃一斤装使用' == $val['remarks']) {
                    if ('4435' == $val['card_product_id'])
                        $val['remarks'] = '仅限app购买美国红宝石（Ruby）樱桃一斤装使用';
                    else
                        $val['remarks'] = '仅限app购买美国西北樱桃一斤装使用';
                }
                if (!empty($val['direction'])) {
                    $val['use_range'] = $val['direction'];
                }
                unset($val['product_id']);
                unset($val['direction']);
            }
        }
        return $result;
    }

    public function getCanApplyInvoiceList($params) {
        $this->ci->load->bll('rpc/request');
        $this->ci->load->helper('public');
        $this->ci->load->library('login');
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_product_model');
        $this->ci->load->model('b2o_product_template_image_model');

        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }
        $uid = $this->ci->login->get_uid();
        $orderGroup = $this->ci->order_model->getCanApplyInvoice($uid);
        if (empty($orderGroup)) {
            send(200, array());
        }

        $orderName2Info = array();
        foreach($orderGroup as $v){
            $orderName2Info[$v['order_name']] = $v;
        }
        $orderNameGroup = array_column($orderGroup, 'order_name');



        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_INVOICE_DETAIL_URL, array('nos' => $orderNameGroup), 'POST', 20);
        foreach($rs['list'] as $info){
            if($info['enable'] == 0){
                unset($orderName2Info[$info['orderNo']]);
            }
            $orderName2Info[$info['orderNo']]['invoiceMoney'] = $info['amt'];
            $orderName2Info[$info['orderNo']]['wareHouse'] = $info['wareHouse'];
        }

        $formatList = array();
        foreach ($orderName2Info as $order) {
            $productGroup = $this->ci->order_product_model->getOrderProductList($order['id'], 'product.id , product.template_id , product.thum_photo, order_product.product_name, order_product.gg_name, order_product.qty');
            foreach($productGroup as $k=>$v){
                if(empty($v['thum_photo'])){
                    $templateImageInfo = $this->ci->b2o_product_template_image_model->dump(array('template_id' => $v['template_id'], 'image_type' => 'main'), 'thumb');
                    $v['thum_photo'] = $templateImageInfo['thumb'];
                }

                $productGroup[$k]['thum_photo'] = cdnImageUrl($v['id']) . $v['thum_photo'];
            }
            if(empty($order['order_name'])){
                continue ;
            }
            $formatList[] = array('order_name' => $order['order_name'],
                'cang_name' => $order['wareHouse'],
                'productGroup' => $productGroup,
                'productCount' => count($productGroup),
                'money' => sprintf('%.2f',round($order['money'] + $order['new_pay_discount'] + $order['use_money_deduction'], 2)),
                'method_money' => sprintf('%.2f',round($order['method_money'], 2)),
                'invoice_money' => sprintf('%.2f',round($order['invoiceMoney'], 2)),
                'time' => $order['time']
            );
        }
        return array('code' => 200, 'msg' => '', 'data' => $formatList);

    }

    public function replenishmentCheck($params) {
        $this->ci->load->bll('rpc/request');
        $this->ci->load->helper('public');
        $this->ci->load->library('login');
        $this->ci->load->model('order_model');
        $this->ci->load->model('b2o_product_class_model');

        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }

        $orderNameGroup = $params['order_name_group'];

        $orderNameArr = json_decode($orderNameGroup, true);

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_INVOICE_DETAIL_URL, array('nos' => $orderNameArr));
        if(!$rs){
            return array('code'=> 300, 'msg'=>'订单无法开具发票');
        }

        $invoiceMoney = 0;
        foreach($rs['list'] as $info){
            if($info['enable'] == 0){
                return array('code'=>300, 'msg' => '订单:'.$info['orderNo'].'无法开票,'.$info['msg']);
            }
            $invoiceMoney += $info['amt'];
        }

        //判断仓
        $cangIdGroup = $this->ci->order_model->getList('cang_id', array('order_name' => $orderNameArr));
        $cangId2Word = array(2 => '广州仓', 3 => '北京仓', 47 => '广州仓');

        $lastCang = '';
        foreach ($cangIdGroup as $v) {
            $cang = in_array($v['cang_id'], array_keys($cangId2Word)) ? $cangId2Word[$v['cang_id']] : '上海仓';
            if (!empty($lastCang) && strcmp($lastCang, $cang) != 0) {
                return array('code' => 300, 'msg' => '不同仓订单无法合并开票');
            }
            $lastCang = $cang;
        }

        $foodTypeClassIdGroup = $this->ci->b2o_product_class_model->getClass3IdsByClass1(array(5,7,8));
        $foodTypeClassIdGroup = array_column($foodTypeClassIdGroup, 'id');

        $this->ci->load->model('order_model');
        $invoiceType2Food = false;
        foreach ($orderNameArr as $orderName) {
            $check = $this->ci->order_model->checkOrderInvoiceType($orderName, $foodTypeClassIdGroup);
            if ($check) {
                $invoiceType2Food = true;
            }
        }

        //发票内容
        if(version_compare($params['version'], '5.6.0') >= 0) {
            $type = array(
                ['id' => 3, 'name' => '明细', 'desc' => ''],
                ['id' => 4, 'name' => '商品大类', 'desc' => '根据购买商品,开具其所属大类']
            );

        }else if (version_compare($params['version'], '5.5.0') == 0) {
            if ($invoiceType2Food) {
                $type = array(1, 2, 3);
            } else {
                $type = array(1, 3);
            }
        } else {
            if ($invoiceType2Food) {
                $type = array(1, 2);
            } else {
                $type = array(1);
            }
        }

        //开票方式
        if (count($orderNameArr) == 1) {
            $way = array(1, 2); //1电子 2纸质
        } else {
            $way = array(1, 2);
        }

        $needFreight = $invoiceMoney >= 500 ? 0 : 1;
        $Freight = $invoiceMoney >= 500 ? 0 : 5;

        return array('code' => 200, 'msg' => '', 'data' => array('way' => $way, 'type' => $type, 'needFreight' => $needFreight, 'freight' => $Freight));
    }

    public function invoiceHistory($params) {
        $this->ci->load->helper('public');
        $this->ci->load->library('login');
        $this->ci->load->model('subsequent_invoice_model');
        $this->ci->load->model('subsequent_invoice_track_model');
        $this->ci->load->model('order_model');

        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }
        $uid = $this->ci->login->get_uid();

        $offset = $params['offset'] * $params['limit'];
        $limit = $params['limit'];

        $list = $this->ci->subsequent_invoice_model->getListNew('*', array('uid' => $uid), $offset, $limit, 'create_time desc');
        if(empty($list)){
            return array('code'=>200, 'msg'=>'', 'data'=>array());
        }
        $invoiceIdGroup = array_column($list, 'id');
        //发票物流信息
        $trackInfoList = $this->ci->subsequent_invoice_track_model->getList('*', array('invoice_id' => $invoiceIdGroup));
        $invoiceId2TrackInfo = array();
        foreach($trackInfoList as $v){
            $invoiceId2TrackInfo[$v['invoice_id']] = $v;
        }
        foreach($list as &$invoice){
            $trackInfo = $invoiceId2TrackInfo[$invoice['id']];
            $invoice['trackInfo'] = empty($trackInfo) ? '' : $trackInfo['express'] . ' [单号:' . $trackInfo['tracking_number'] . ']';
        }

        return array('code' => 200, 'msg' => '', 'data' => $list);
    }

    public function invoiceDetail($params) {
        $this->ci->load->helper('public');
        $this->ci->load->library('login');
        $this->ci->load->bll('rpc/request');

        $this->ci->load->model('order_model');
        $this->ci->load->model('order_product_model');
        $this->ci->load->model('b2o_product_template_image_model');
        $this->ci->load->model('subsequent_invoice_model');
        $this->ci->load->model('subsequent_invoice_order_model');
        $this->ci->load->model('subsequent_invoice_track_model');
        $this->ci->load->model('area_model');

        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }
        $invoiceId = $params['invoiceId'];
        $invoiceInfo = $this->ci->subsequent_invoice_model->dump(array('id' => $invoiceId), '*');
        $bindOrderGroup = $this->ci->subsequent_invoice_order_model->getList('*', array('invoice_id' => $invoiceInfo['id']));

        $areaInfo = $this->ci->area_model->getList('id,name', array('id' => array($invoiceInfo['province'], $invoiceInfo['city'], $invoiceInfo['area'])));
        $areaId2name = array();
        foreach($areaInfo as $v){
            $areaId2name[$v['id']] = $v['name'];
        }
        $invoiceInfo['province'] = $areaId2name[$invoiceInfo['province']];
        $invoiceInfo['city'] = $areaId2name[$invoiceInfo['city']];
        $invoiceInfo['area'] = $areaId2name[$invoiceInfo['area']];

        $data = $invoiceInfo;
        foreach($bindOrderGroup as $bindOrder){
            $orderInfo = $this->ci->order_model->dump(array('order_name'=>$bindOrder['order_name']));
            $productGroup = $this->ci->order_product_model->getOrderProductList($orderInfo['id'], 'product.id , product.template_id , product.thum_photo, order_product.product_name, order_product.gg_name, order_product.qty');
            foreach($productGroup as &$product){
                $pic_url = cdnImageUrl($product['id']);
                if(empty($product['thum_photo'])){
                    $templateImageInfo = $this->ci->b2o_product_template_image_model->dump(array('template_id' => $product['template_id'], 'image_type' => 'main'), 'thumb');
                    $product['thum_photo'] = $templateImageInfo['thumb'];
                }

                $product['thum_photo'] = $pic_url. $product['thum_photo'];
            }
            $data['orderGroup'][] = array('order_name'=> $orderInfo['order_name'],
                                'money' => sprintf('%.2f', round($orderInfo['money'] + $orderInfo['new_pay_discount'] + $orderInfo['use_money_deduction'], 2)),
                                'productGroup'=>$productGroup
                );
        }
        //发票物流信息
        $trackInfo = $this->ci->subsequent_invoice_track_model->dump(array('invoice_id'=>$invoiceInfo['id']),'tracking_number , express');
        if(empty($trackInfo)){
            $data['trackInfo'] = '';
        } else {
            $data['trackInfo'] = $trackInfo['express'] . ' [单号:' . $trackInfo['tracking_number'] . ']';
        }

        //电子发票
        if ($invoiceInfo['type'] == 1) {
            $data['einvoice_download'] = 0;
            $order = $bindOrderGroup[0]['order_name'];
            $rs = $this->_realtime_call('http://invoice.fruitday.com/pdf/getPdfInvoices', array('order' => $order), 'POST', 20);
            if ($rs['data'][$order]['fpztdm'] == 1) {
                $data['einvoice_download'] = 1;
            }
        } else if ($invoiceInfo['type'] == 2) {
            $this->ci->load->bll('express');
            $expressMoney = $this->ci->bll_express->getInfo(array('connect_id' => $params['connect_id'], 'invoice_id' => $invoiceId));
            $data['expressMoney'] = $data['money'] >= 500 ? 0.00 : sprintf('%.2f', round($expressMoney, 2));
        }
        $data['needFreight'] = $data['money'] >= 500 ? 0 : 1;
        $type2Name = array('1'=>'水果','2'=>'食品','3'=>'明细','4'=>'商品大类');
        $data['kpTypeName'] = $type2Name[$data['kp_type']];

        return array('code'=>200, 'msg'=>'' , 'data'=>$data);
    }

    public function fpIdNoHistory($params){
        $this->ci->load->helper('public');
        $this->ci->load->library('login');
        $this->ci->load->model('subsequent_invoice_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('trade_invoice_model');

        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }
        $uid = $this->ci->login->get_uid();
        $fp = $params['fp'];

        $type = $params['type'];
        if($type == 1){
            $rs = $this->ci->subsequent_invoice_model->getListNew("fp_id_no",array('uid'=>$uid, 'fp'=>$fp));
        } else if($type == 2){
            $rs = $this->ci->order_model->get_fpIdNo_list($uid, $fp);
        } else if($type == 3){
            $rs = $this->ci->trade_invoice_model->getFpIdNoHistory($uid, $fp);
        }

        return array('code'=>200, 'msg'=>'', 'data'=>array_column($rs, 'fp_id_no'));
    }

    /**
     * 3种发票统一获取抬头及税号接口
     */
    public function fpCommunalData($params){
        $this->ci->load->model('invoice_communal_data_model');
        $this->ci->load->library('login');

        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }
        $uid = $this->ci->login->get_uid();

        $rs = $this->ci->invoice_communal_data_model->getListNew("fp,fp_id_no", array('uid'=>$uid));
        return array('code'=>200, 'msg'=>'', 'data'=>$rs);
    }

    /*
     * 使用闪鲜卡
     */
    function usefc($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    /*
     * 取消使用闪鲜卡
     */
    function cancelUsefc($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    /*
     * 银行秒杀排队
     */
    public function orderLimit()
    {
        $res = array('code'=>'200','msg'=>'succ');

        $bank_order_limit = $this->ci->config->item('bank_order_limit');
        $is_open = $bank_order_limit['open'];
        $limit_count = $bank_order_limit['count'];
        $min = $bank_order_limit['min'];
        $max = $bank_order_limit['max'];
        $time = $bank_order_limit['time'];
        $rand = rand($min,$max);

        if($is_open == 1)
        {
            $this->ci->load->library('orderredis');
            $redis = $this->ci->orderredis->getConn();
            if($redis != false)
            {
                $ordercount = $redis->get('order_limit_count');

                if($rand != 5 && $ordercount >= $limit_count && $is_open == 1)
                {
                    $res = array('code'=>'321','msg'=>'当前抢购人数太多啦，系统正在奋力处理中，请稍后重试~','data'=>array('time'=>$time));
                }
            }
        }

        return $res;
    }


    private function _realtime_call($url, $odata, $method = 'POST', $timeout = 6) {
        $data = $this->_preprocess_data($odata);

        $this->ci->load->library('aesfp', null, 'encrypt_aes');
        $params = array(
            'data' => $this->ci->encrypt_aes->AesEncrypt($data),
            'signature' => $this->ci->encrypt_aes->data_hash($data),
        );

        $this->ci->load->library('curl', null, 'http_curl');
        $options['timeout'] = $timeout;

        if (defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')) {
            $options['proxy'] = CURL_PROXY_ADDR . ":" . CURL_PROXY_PORT;
        }
        $rs = $this->ci->http_curl->request($url, $params, $method, $options);
        if ($rs['errorNumber'] || $rs['errorMessage']) {

            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);

            return false;
        }

        $response = json_decode($rs['response'], true);

        // 解密
        $data = $this->ci->encrypt_aes->AesDecrypt($response['data']);

        $data = json_decode($data, true);
        if ($data['code'] != '0000') {
            $this->_error = array('errorNumber' => '', 'errorMessage' => $data['msg']);

            return false;
        }

        return $data;
    }

    private function _preprocess_data($data) {
        if (is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        return trim($data);
    }

    /*
     * 使用积点
     */
    function usejd($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');
            $order_info['jd'] = $this->ci->bll_order->getJd($result['uid']);

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    /*
     * 取消使用积点
     */
    function cancelUsejd($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');
            $order_info['jd'] = $this->ci->bll_order->getJd($result['uid']);

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }


    /*
     * 使用自提
     */
    function useSelfPick($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');
            $order_info['jd'] = $this->ci->bll_order->getJd($result['uid']);

            //自提
            $order_info['self_tag'] = $params['self_pick'];

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }

    /*
     * 取消使用自提
     */
    function cancelSelfPick($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            /*运费start*/
            $params['no_self_pick'] = 0;
            $order_detail = $this->ci->bll_order->b2oOrderDetails($result['uid'],$params['source'],$params['device_id'],$params);
            $order_info['money'] = number_format($order_detail['money'],2,'.','');
            $order_info['goods_money'] = number_format($order_detail['total_amount_money'],2,'.','');
            $order_info['method_money'] = number_format($order_detail['method_money'],2,'.','');
            $order_info['order_limit'] = number_format($order_detail['order_limit'],2,'.','');
            $order_info['jf_money'] = number_format($order_detail['jf_money'],2,'.','');
            $order_info['card_money'] = number_format($order_detail['card_money'],2,'.','');
            $order_info['pay_discount'] = number_format($order_detail['pay_discount'],2,'.','');
            $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'],2,'.','');
            $order_info['fresh_discount'] = number_format($order_detail['fresh_discount'],2,'.','');
            $order_info['fresh_no'] = $order_detail['fresh_no'];

            //积点
            $order_info['jd_discount'] = number_format($order_detail['jd_discount'],2,'.','');
            $order_info['jd'] = $this->ci->bll_order->getJd($result['uid']);

            //自提
            $order_info['self_tag'] = $params['self_pick'];

            /*运费end*/
            unset($result['uid']);
            $result['msg'] = 'succ';
            $result['data'] = $order_info;
        }
        return $result;
    }
}