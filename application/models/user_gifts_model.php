<?php

/**
 * 用户赠品
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   model
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: user_gift_model.php 1 2014-12-18 11:32:43 pax $
 * @link      http://www.fruitday.com
 **/
class User_gifts_model extends MY_Model {
    /**
     * 赠品表
     *
     * @var string
     **/
    const _TABLE_NAME = 'user_gifts';

    public function table_name() {
        return self::_TABLE_NAME;
    }

    /**
     * 获取用户赠品
     *
     * @param id 赠品明细ID
     *
     * @return void
     * @author
     **/
    public function get_user_gift($id) {
        $usergift = $this->db->select('*')
            ->from('user_gifts')
            ->where('id', $id)
            ->get()
            ->row_array();
        if (!$usergift) return array();

        $giftsend = $this->db->select('*')
            ->from('gift_send')
            ->where('id', $usergift['active_id'])
            ->get()
            ->row_array();
        $usergift['gift'] = $giftsend;

        return $usergift;
    }

    public function return_user_gift($order_id, $uid) {
        $filter = array(
            'bonus_order' => $order_id,
            'uid' => $uid,
            'has_rec' => '1',
        );

        $set = array(
            'has_rec' => '0',
            'cancel_order' => $order_id,
        );

        return $this->update($set, $filter);
    }


    /**
     * 获取用户赠品
     *
     * @param id 赠品活动ID
     *
     * @return void
     * @author
     **/
    public function get_gift_send($id) {
        $giftsend = $this->db->select('*')
            ->from('gift_send')
            ->where('id', $id)
            ->get()
            ->row_array();

        if ($gift_send) {
            $usergift = $this->db->select('*')
                ->from('user_gifts')
                ->where('active_id', $id)
                ->get()
                ->result_array();
            $giftsend['user_gifts'] = $usergift;
        }

        return $gift_send;
    }

    /**
     * 获取会员有效赠品
     *
     * @return void
     * @author
     **/
    public function get_valid_gifts($uid, $region_id, $gift_type = 2) {
        $now = date('Y-m-d H:i:s');
        $now = date('Y-m-d');
        $this->db->select('gs.qty,gs.id as gift_send_id,gs.end,ug.id as user_gift_id,gs.product_id,ug.has_rec,ug.active_type,gs.remarks,gs.gift_type,gs.send_region,ug.start_time,ug.end_time')
            ->from('user_gifts as ug')
            ->join('gift_send as gs', 'ug.active_id=gs.id', 'left')
            ->where('ug.uid', $uid)
            // ->where('gs.end >',$now)
            // ->where('gs.start <',$now)
            ->where('ug.end_time >=', $now)
            ->where('ug.start_time <=', $now)
            ->where('ug.has_rec', 0)
            ->where('ug.active_type', 2);
        if ($gift_type == 0) {
            $this->db->where('gs.gift_type', 0);
        } elseif ($gift_type == 1) {
            $this->db->where('gs.gift_type', 1);
        }
        $rows = $this->db->get()->result_array();
        $this->load->model('area_model');
        foreach ($rows as $key => $value) {
            //020赠品，判断配送地区
            if ($value['gift_type'] == 1) {
                if ($value['send_region'] && $value['send_region'] != ',') {
                    $send_regions = explode(',', $value['send_region']);
                    $region_arr = array();
                    foreach ($send_regions as $region) {
                        $region_new = $this->area_model->getProvinceByArea($region);
                        $region_new and $region_arr[] = $region_new['id'];
                    }
                    if ($region_arr && $region_id && !in_array($region_id, $region_arr)) {
                        unset($rows[$key]);
                    }
                }
            }
        }
        return $rows;
    }


    /**
     * 获取会员有效赠品
     *
     * @return void
     * @author
     **/
    public function get_valid_gifts_new($storeIdList, $uid, $gift_type = 2) {
        $this->load->model('b2o_store_product_model');

        $now = date('Y-m-d');
        $this->db->select('gs.qty,gs.id as gift_send_id,gs.start,gs.end,ug.id as user_gift_id,gs.product_id,ug.has_rec,ug.active_type,gs.remarks,gs.gift_type,gs.send_region,ug.start_time,ug.end_time')
            ->from('user_gifts as ug')
            ->join('gift_send as gs', 'ug.active_id=gs.id', 'left')
            ->where('ug.uid', $uid)
            ->where('ug.end_time >=', $now)
            ->where('ug.has_rec', 0)
            ->where('ug.active_type', 2)
            ->order_by('ug.time desc');
        if ($gift_type == 0) {
            $this->db->where('gs.gift_type', 0);
        } elseif ($gift_type == 1) {
            $this->db->where('gs.gift_type', 1);
        }
        $rows = $this->db->get()->result_array();
        $this->load->model('area_model');
        //$now = time();
        $status1 = array();
        $status2 = array();
        $status3 = array();
        foreach ($rows as $key => $value) {
            //020赠品，判断配送地区
            if ($value['gift_type'] == 1) {
                if ($value['send_region'] && $value['send_region'] != ',') {
                    $send_regions = explode(',', $value['send_region']);
                    $region_arr = array();
                    foreach ($send_regions as $region) {
                        $region_new = $this->area_model->getProvinceByArea($region);
                        $region_new and $region_arr[] = $region_new['id'];
                    }
                }
            }
            //判断即将过期 即将生效 普通
            $startTime = $value['start_time'];
            $endTime = date('Y-m-d', strtotime('-1 day', strtotime($value['end_time'])));
            if ($startTime > $now) {
                //即将开始
                $rows[$key]['critical_status'] = 3;
                $status3[] = $rows[$key];
            } else if ($now >= $endTime) {
                //即将结束
                $rows[$key]['critical_status'] = 1;
                $status1[] = $rows[$key];
            } else {
                //普通
                $rows[$key]['critical_status'] = 2;
                $status2[] = $rows[$key];
            }
        }
        $result = array_merge($status1, $status2, $status3);
        if (!empty($result)) {
            $productIdGroup = array_column($result, 'product_id');
            $join[] = array(
                'name' => 'product',
                'cond' => 'product.id=b2o_store_product.product_id',
                'type' => 'left'
            );
            $filter['product.id'] = (array)$productIdGroup;
            $filter['b2o_store_product.store_id'] = (array)$storeIdList;
            $validProductGroup = $this->b2o_store_product_model->getProductList('b2o_store_product.product_id , b2o_store_product.store_id', $filter, $join);
            $productId2Store = array();
            foreach ($validProductGroup as $v) {
                $productId2Store[$v['product_id']] = $v['store_id'];
            }
            $validProductIdGroup = array_column($validProductGroup, 'product_id');
            $validGift = array();
            $invalidGift = array();
            foreach ($result as $gift) {
                if (in_array($gift['product_id'], $validProductIdGroup)) {
                    $gift['storeEnabled'] = 1;
                    $gift['storeId'] = $productId2Store[$gift['product_id']];
                    $validGift[] = $gift;
                } else {
                    $gift['storeEnabled'] = 0;
                    $gift['storeId'] = 0;
                    $invalidGift[] = $gift;
                }
            }
            $result = array_merge($validGift, $invalidGift);
        }

        return $result;
    }


    public function gift_count($uid) {
        $count = 0;

        $now = date('Y-m-d');
        $c = $this->db->from('user_gifts as ug')
            //->join('gift_send as gs','ug.active_id=gs.id','left')
            ->where('ug.uid', $uid)
            ->where('ug.end_time >=', $now)
            ->where('ug.start_time <=', $now)
            ->where('ug.has_rec', 0)
            //->where('ug.active_type',2)
            ->count_all_results();
        $count += $c;

//        $c = $this->db->from('user_gifts')
//                        ->where('uid',$uid)
//                        ->where('has_rec',0)
//                        ->where('active_type',1)
//                        ->count_all_results();

        // $c = 0;
        // $arr = $this->db->select('time')->from('user_gifts')
        // 				->where('uid',$uid)
        // 				->where('has_rec', 0)
        // 				->where('active_type', 1)
        // 				->get()->result_array();
        // foreach ($arr as $val) {
        // 	$activeToUserTime = $val['time'];
        // 	if (date('Y-m-d', strtotime("2015-08-05")) < $activeToUserTime) {
        // 		$activeEndTime = date('Y-m-d', strtotime($activeToUserTime . " +2 week"));
        // 		if ($activeEndTime < date('Y-m-d')) {
        // 			continue;
        // 		}
        // 	}
        // 	$c++;
        // }
        // $count += $c;

        return $count;
    }

    /**
     * 获取充值赠品
     *
     * @return void
     * @author
     **/
    public function get_trade_gifts($uid, $region_id, $valid = 2) {
        $this->db->select('id as user_gift_id,active_id,has_rec,active_type,time,start_time,end_time')
            ->from('user_gifts')
            ->where('uid', $uid)
            ->where('has_rec', 0)
            ->where('active_type', 1);
        $now = date('Y-m-d');
        if ($valid == 0) {  //已过期
            $this->db->where('end_time <', $now);
        } elseif ($valid == 1) { //可用
            $this->db->where('end_time >=', $now);
        }
        $rows = $this->db->get()->result_array();
        $i = 0;
        if (!empty($rows)) {
            $trade_ids = array();
            $activeToUsergif = array();
            $activeToUserTime = array();
            foreach ($rows as $key => $value) {
                $trade_ids[] = $value['active_id'];
                $activeToUserTime[$value['active_id']] = $value['time'];
                $activeToUsergif[$value['active_id']] = $value['user_gift_id'];
            }
            $this->db->select('id,trade_number,money,bonus_products,time');
            $this->db->from('trade');
            $this->db->where_in('id', $trade_ids);
            $trade_query = $this->db->get();
            $trade_result = $trade_query->result_array();

            $now = time();
            foreach ($trade_result as $k => $v) {
//                $products_array = $this->rules_to_charge_data($v['money'],$v['trade_number'],$region_id);
//				if(empty($products_array)){
//                    continue;
//                }
                // $activeEndTime = '';
                // if (date('Y-m-d', strtotime("2015-08-05")) < $activeToUserTime[$v['id']]) {
                // 	$activeEndTime = date('Y-m-d', strtotime($activeToUserTime[$v['id']] . " +2 week"));
                // }
                // if ($activeEndTime < date('Y-m-d') && $activeEndTime != '') {
                // 	continue;
                // }

                $user_gift_arr[$i]['qty'] = '1';
                $user_gift_arr[$i]['gift_send_id'] = $v['id'];
                $user_gift_arr[$i]['end'] = $value['end_time'];
                $user_gift_arr[$i]['start'] = $value['start_time'];
                $user_gift_arr[$i]['user_gift_id'] = $activeToUsergif[$v['id']];
                $user_gift_arr[$i]['product_id'] = $v['bonus_products'];
                $user_gift_arr[$i]['has_rec'] = 0;
                $user_gift_arr[$i]['active_type'] = 1;
                $user_gift_arr[$i]['remarks'] = "帐户充值赠品";


                //判断即将过期 即将生效 普通
                $startTime = strtotime($user_gift_arr[$i]['start']);
                $endTime = strtotime($user_gift_arr[$i]['end']);
                if ($now > ($endTime - 86400)) {
                    //即将结束
                    $user_gift_arr[$i]['critical_status'] = 1;
                } else if ($startTime > $now) {
                    //即将开始
                    $user_gift_arr[$i]['critical_status'] = 3;
                } else {
                    //普通
                    $user_gift_arr[$i]['critical_status'] = 2;
                }

                $i++;
            }
        }
        return $user_gift_arr;
    }


    /**
     * 获取充值赠品
     *
     * @return void
     * @author
     **/
    public function get_trade_gifts_new($storeIdList,$uid, $valid) {
        $this->load->model('b2o_store_product_model');

        $now = date('Y-m-d');
        $hasRes = $valid == 2 ? 1 : 0;
        $rows = $this->db->select('id as user_gift_id,active_id,has_rec,active_type,time,start_time,end_time')
            ->from('user_gifts')
            ->where('uid', $uid)
            ->where('has_rec', $hasRes)
            ->where('active_type', 1)
            ->get()
            ->result_array();
        $i = 0;
        if (!empty($rows)) {
            $trade_ids = array();
            $activeToUsergif = array();
            $activeToUserTime = array();
            foreach ($rows as $key => $value) {
                $expiredTime = $value['end_time'];
                if ($valid == 1) {
                    //有效
                    if ($now > $expiredTime) {
                        continue;
                    }
                }
//
                if ($valid == 0) {
                    //已过期

                    if ($now <= $expiredTime) {
                        continue;
                    }
                }

                $trade_ids[] = $value['active_id'];
                $activeToUserTime[$value['active_id']]['start_time'] = $value['start_time'];
                $activeToUserTime[$value['active_id']]['end_time'] = $value['end_time'];
                $activeToUsergif[$value['active_id']] = $value['user_gift_id'];
            }
            if (empty($trade_ids)) {
                return array();
            }
            $this->db->select('id,trade_number,money,bonus_products,time');
            $this->db->from('trade');
            $this->db->where_in('id', $trade_ids);
            $trade_query = $this->db->get();
            $trade_result = $trade_query->result_array();
            $productIdGroup = array_column($trade_result, 'bonus_products');
            $join[] = array(
                'name' => 'product',
                'cond' => 'product.id=b2o_store_product.product_id',
                'type' => 'left'
            );
            $filter['product.id'] = (array)$productIdGroup;
            $filter['b2o_store_product.store_id'] = (array)$storeIdList;
            $validProductGroup = $this->b2o_store_product_model->getProductList('b2o_store_product.product_id , b2o_store_product.store_id', $filter, $join);

            $productId2Store = array();
            foreach ($validProductGroup as $v) {
                $productId2Store[$v['product_id']] = $v['store_id'];
            }
            $validProductIdGroup = array_column($validProductGroup, 'product_id');

            $now = time();
            foreach ($trade_result as $k => $v) {
//                $products_array = $this->rules_to_charge_data($v['money'],$v['trade_number'],$region_id);
//				if(empty($products_array)){
//                    continue;
//                }
                // $activeEndTime = '';
                // if (date('Y-m-d', strtotime("2015-08-05")) < $activeToUserTime[$v['id']]['start_time']) {
                //     $activeEndTime = $activeToUserTime[$v['id']]['end_time'];
                // }
                // if ($activeEndTime < date('Y-m-d') && $activeEndTime != '') {
                //     continue;
                // }

                $user_gift_arr[$i]['qty'] = '1';
                $user_gift_arr[$i]['gift_send_id'] = $v['id'];
                $user_gift_arr[$i]['end'] = $activeToUserTime[$v['id']]['end_time'];
                $user_gift_arr[$i]['start'] = $activeToUserTime[$v['id']]['start_time'];
                $user_gift_arr[$i]['end_time'] = $activeToUserTime[$v['id']]['end_time'];
                $user_gift_arr[$i]['start_time'] = $activeToUserTime[$v['id']]['start_time'];
                $user_gift_arr[$i]['user_gift_id'] = $activeToUsergif[$v['id']];
                $user_gift_arr[$i]['product_id'] = $v['bonus_products'];
                $user_gift_arr[$i]['has_rec'] = 0;
                $user_gift_arr[$i]['active_type'] = 1;
                $user_gift_arr[$i]['remarks'] = "帐户充值赠品";

                if (in_array($v['bonus_products'], $validProductIdGroup)) {
                    $user_gift_arr[$i]['storeEnabled'] = 1;
                    $user_gift_arr[$i]['storeId'] = $productId2Store[$v['bonus_products']];
                } else {
                    $user_gift_arr[$i]['storeEnabled'] = 0;
                    $user_gift_arr[$i]['storeId'] = 0;
                }

                //判断即将过期 即将生效 普通
                $startTime = strtotime($user_gift_arr[$i]['start']);
                $endTime = strtotime($user_gift_arr[$i]['end']);
                if ($now > ($endTime - 86400)) {
                    //即将结束
                    $user_gift_arr[$i]['critical_status'] = 1;
                } else if ($startTime > $now) {
                    //即将开始
                    $user_gift_arr[$i]['critical_status'] = 3;
                } else {
                    //普通
                    $user_gift_arr[$i]['critical_status'] = 2;
                }

                $i++;
            }
        }

        return $user_gift_arr;
    }


    function rules_to_charge_data($charge_money, $trade_number, $region_id = '106092') {
        $rules = $this->all_rules_by_region(1, $region_id);
        if (empty($rules)) {
            return;
        }

        $products_content = "";
        $products_num = "";

        $add_up_money = $this->add_up_to($trade_number, $rules);

        foreach ($rules as $rule) {
            $content = unserialize($rule->content);
            foreach ($content as $val2) {
                $low = $val2['low'];
                $high = $val2['high'];

                if ($high == "-1") {
                    $high = 100000;
                }

                $products_content = $val2['products_content'];
                $products_num = $val2['products_num'];
                if ($charge_money >= $low && $charge_money <= $high && $products_content != "") {
                    return array($products_content, $products_num);
                } else if ($add_up_money >= $low && $add_up_money <= $low) {
                    return array($products_content, $products_num);
                }
            }
        }
        return;
    }

    function all_rules_by_region($type = '0', $region_id = '106092') {
        $site_list = $this->config->item('site_list');
        $area = $site_list[$region_id];
        $area or $area = $region_id;
        $now = date("Y-m-d H:i:s");
        $this->db->from("money_upto");
        $this->db->where(array('start <=' => $now, 'end >=' => $now, 'type' => $type));
        $area_where = 'send_region like \'%"' . $area . '"%\'';
        $this->db->where($area_where);

        $query = $this->db->get();
        $result = $query->result();
        return $result;
    }

    function add_up_to($trade_number, $rules = '') {
        $min_low = 0;
        if ($rules) {
            foreach ($rules as $rule) {
                $content = unserialize($rule->content);
                foreach ($content as $val2) {
                    if ($val2['low'] < $min_low && $min_low != 0) {
                        $min_low = $val2['low'];
                    } else if ($min_low == 0) {
                        $min_low = $val2['low'];
                    }
                }
            }
        }
        $this->db->select("uid,time");
        $this->db->from("trade");
        $this->db->where("trade_number", $trade_number);
        $query = $this->db->get();
        $result = $query->row_array();
        if (empty($result))
            return;

        $time = date("Y-m-d", strtotime($result['time']));

        $sql = "SELECT sum(money) as money,bonus_products FROM (`ttgy_trade`) WHERE
            `time` LIKE '%$time%' and has_deal = 1 and type='income' and uid = {$result['uid']} and money<{$min_low}";
        $query = $this->db->query($sql);
        $result = $query->result();

        if (empty($result))
            return '0';

        return empty($result[0]->money) ? '0' : ($result[0]->money);
    }

    function get_user_last_gift($uid) {
        if (empty($uid)) return;
        // $this->db->select('max(time) as maxtime');
        // $this->db->from("user_gifts");
        // $this->db->where(array("uid"=>$uid,'has_rec'=>0));
        // $query  = $this->db->get();
        // $result = $query->row_array();
        $sql = "select time,id,active_type,active_id from ttgy_user_gifts where has_rec=0 and uid=" . $uid . " order by time";
        $result = $this->db->query($sql)->result_array();

        if (empty($result)) {
            return;
        }
        $user_gift_info_tmp = '';
        foreach ($result as $key => $value) {
            if ($value['active_type'] == 2) {
                $user_gift_info_tmp .= $value['active_id'] . ",";
            }
        }

        $sql2 = "select * from ttgy_gift_send where id in (" . trim($user_gift_info_tmp, ',') . ") and start<='" . date('Y-m-d H:i:s') . "' and end>='" . date('Y-m-d H:i:s') . "'";
        $result2 = $this->db->query($sql2)->result_array();
        $active_gift = array();
        if (!empty($result2)) {
            foreach ($result2 as $k => $v) {
                $active_gift[] = $v['id'];
            }
        }

        $times = array();
        foreach ($result as $key => $value) {
            // if($value['active_type'] == 1){
            //     $activeEndTime = '';
            //     if (date('Y-m-d', strtotime("2015-08-05")) < $value['time']) {
            //         $activeEndTime = date('Y-m-d', strtotime($value['time'] . " +2 week"));
            //     }
            //     if ($activeEndTime < date('Y-m-d') && $activeEndTime != '') {
            //         continue;
            //     }
            // }
            // elseif($value['active_type'] ==2){
            //     $sql2 = "select * from ttgy_gift_send where id=".$value['active_id']." and start<='".date('Y-m-d H:i:s')."' and end>='".date('Y-m-d H:i:s')."'";
            //     $row = $this->db->query($sql2)->row_array();
            //     if(!$row){
            //         continue;
            //     }
            // }

            // elseif($value['active_type'] ==2){
            //     if(!in_array($value['active_id'], $active_gift)){
            //         continue;
            //     }
            // }
            $times[] = $value['time'];
        }


        return max($times);
    }


    /**
     * 获取会员过期赠品
     *
     * @return void
     * @author
     **/
    public function get_expired_gifts($uid, $region_id, $gift_type = 2, $page = 1, $limit = -1) {
        $now = date('Y-m-d');
        $this->db->select('gs.qty,gs.id as gift_send_id,gs.end,ug.id as user_gift_id,gs.product_id,ug.has_rec,ug.active_type,gs.remarks,gs.gift_type,gs.send_region,ug.start_time,ug.end_time')
            ->from('user_gifts as ug')
            ->join('gift_send as gs', 'ug.active_id=gs.id', 'left')
            ->where('ug.uid', $uid)
            ->where('ug.end_time <', $now)
            ->where('ug.has_rec', 0)
            ->where('ug.active_type', 2);
        if ($gift_type == 0) {
            $this->db->where('gs.gift_type', 0);
        } elseif ($gift_type == 1) {
            $this->db->where('gs.gift_type', 1);
        }

        // add pagination
        if ($limit > 0 && $page > 0) {
            $offset = ($page - 1) * $limit;
            $this->db->limit($limit, $offset);
        }

        $rows = $this->db->get()->result_array();
        $this->load->model('area_model');
        foreach ($rows as $key => $value) {
            //020赠品，判断配送地区
            if ($value['gift_type'] == 1) {
                if ($value['send_region'] && $value['send_region'] != ',') {
                    $send_regions = explode(',', $value['send_region']);
                    $region_arr = array();
                    foreach ($send_regions as $region) {
                        $region_new = $this->area_model->getProvinceByArea($region);
                        $region_new and $region_arr[] = $region_new;
                    }
                    if ($region_arr && $region_id && !in_array($region_id, $region_arr)) {
                        unset($rows[$key]);
                    }
                }
            }
        }
        return $rows;
    }

    public function get_expired_gifts_new($uid, $gift_type = 2) {
        $now = date('Y-m-d');
        $last30DayTime = date('Y-m-d', strtotime('- 30day'));
        $this->db->select('gs.qty,gs.id as gift_send_id,gs.start,gs.end,ug.id as user_gift_id,gs.product_id,ug.has_rec,ug.active_type,gs.remarks,gs.gift_type,gs.send_region,ug.start_time,ug.end_time')
            ->from('user_gifts as ug')
            ->join('gift_send as gs', 'ug.active_id=gs.id', 'left')
            ->where('ug.uid', $uid)
            ->where('ug.end_time <', $now)
            ->where('ug.end_time >', $last30DayTime)
            ->where('ug.has_rec', 0)
            ->where('ug.active_type', 2)
            ->order_by('gs.end desc');
        if ($gift_type == 0) {
            $this->db->where('gs.gift_type', 0);
        } elseif ($gift_type == 1) {
            $this->db->where('gs.gift_type', 1);
        }
        $rows = $this->db->get()->result_array();
        $this->load->model('area_model');
        foreach ($rows as $key => $value) {
            //020赠品，判断配送地区
            if ($value['gift_type'] == 1) {
                if ($value['send_region'] && $value['send_region'] != ',') {
                    $send_regions = explode(',', $value['send_region']);
                    $region_arr = array();
                    foreach ($send_regions as $region) {
                        $region_new = $this->area_model->getProvinceByArea($region);
                        $region_new and $region_arr[] = $region_new;
                    }
                }
            }
        }
        return $rows;
    }


    /**
     * 获取已领取赠品
     *
     * @return void
     * @author
     **/
    public function get_has_rec_gifts($uid, $gift_type = 2) {
        $now = date('Y-m-d H:i:s');
        $last30DayTime = date('Y-m-d H:i:s', strtotime('- 30day'));
        $this->db->select('gs.qty,gs.id as gift_send_id,gs.start,gs.end,ug.id as user_gift_id,gs.product_id,ug.has_rec,ug.active_type,gs.remarks,gs.gift_type,gs.send_region,ug.start_time,ug.end_time')
            ->from('user_gifts as ug')
            ->join('gift_send as gs', 'ug.active_id=gs.id', 'left')
            ->where('ug.uid', $uid)
            ->where('ug.has_rec', 1)
            ->where('ug.active_type', 2)
            ->where('ug.time >', $last30DayTime)
            ->order_by('ug.time desc');
        if ($gift_type == 0) {
            $this->db->where('gs.gift_type', 0);
        } elseif ($gift_type == 1) {
            $this->db->where('gs.gift_type', 1);
        }

        $rows = $this->db->get()->result_array();
        $this->load->model('area_model');
        foreach ($rows as $key => $value) {
            //020赠品，判断配送地区
            if ($value['gift_type'] == 1) {
                if ($value['send_region'] && $value['send_region'] != ',') {
                    $send_regions = explode(',', $value['send_region']);
                    $region_arr = array();
                    foreach ($send_regions as $region) {
                        $region_new = $this->area_model->getProvinceByArea($region);
                        $region_new and $region_arr[] = $region_new;
                    }
                }
            }
        }
        return $rows;
    }

    function repairOrderUserGift($order_id, $uid) {
        $this->db->from('user_gifts');
        $this->db->where('cancel_order', $order_id);
        $this->db->where('uid', $uid);
        $res = $this->db->get()->result_array();
        if (empty($res)) {
            return true;
        } else {
            foreach ($res as $key => $value) {
                if ($value['has_rec'] == 1) {
                    return false;
                }
            }
            $filter = array(
                'cancel_order' => $order_id,
                'uid' => $uid,
                'has_rec' => '0',
            );

            $set = array(
                'has_rec' => '1',
                'bonus_order' => $order_id,
            );
            return $this->update($set, $filter);
        }
        return true;
    }

    public function getTradeGift($uid, $active_id) {
        $this->db->select('id,trade_number,money,bonus_products,time');
        $this->db->from('trade');
        $this->db->where(array('uid' => $uid, 'id' => $active_id));
        $trade_gift = $this->db->get()->row_array();
        return $trade_gift;
    }


    public function return_b2o_user_gift($order_id, $uid) {
        $filter = array(
            'bonus_b2o_order' => $order_id,
            'uid' => $uid,
            'has_rec' => '1',
        );

        $set = array(
            'has_rec' => '0',
            'cancel_b2o_order' => $order_id,
        );

        return $this->update($set, $filter);
    }

    /*
     * 更新订单用户赠品
     */
    public function set_user_gift($id,$order_id) {
        $filter = array(
            'id' => $id,
        );
        $set = array(
            'bonus_order' => $order_id,
        );
        return $this->update($set, $filter);
    }
}