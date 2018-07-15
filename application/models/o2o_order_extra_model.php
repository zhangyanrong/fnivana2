<?php
class o2o_order_extra_model extends MY_Model {

    public function table_name(){
        return 'o2o_order_extra';
    }

    /**
     * 获取用户常用提货门店
     *
     * @return void
     * @author 
     **/
    public function commonstores($uid,$city_id,$type)
    {
        $sql = "SELECT DISTINCT o.store_id,s.* 
                    FROM ttgy_o2o_order_extra AS o 
                    LEFT JOIN ttgy_o2o_store AS s on(o.store_id=s.id)
                    WHERE o.uid='{$uid}' AND o.type='{$type}' AND s.city_id='{$city_id}' ";

        $rows = $this->db->query($sql)->result_array();

        return $rows;
    }

    /**
     * 获取最后一次成功下单
     *
     * @return void
     * @author 
     **/
    public function getLastOrder($uid)
    {
        $sql = "SELECT extra.order_id,extra.building_id
                FROM ttgy_o2o_order_extra AS extra,ttgy_order AS order
                WHERE extra.order_id=order.id AND order.uid='{$uid}' AND order.order_status=1 AND order.operation_id!=5 ORDER BY order.time DESC LIMIT 1";

        $row = $this->db->query($sql)->row_array();

        return $row;
    }


    public function addresslist($uid)
    {
        // $sql = "SELECT distinct building_id,id,order_id,address
        //         FROM ttgy_o2o_order_extra 
        //         WHERE uid='{$uid}' GROUP BY uid,building_id";

        $sql = "SELECT DISTINCT e.building_id,e.address,d.name,d.mobile FROM ttgy_o2o_order_extra AS e,ttgy_order_address AS d
                WHERE e.order_id=d.order_id AND e.uid='{$uid}' order by e.id desc";

        $rows = $this->db->query($sql)->result_array();

        return $rows;
    }

    public function getStoreByOrderId($order_id){
        $sql = "SELECT store_id FROM ttgy_o2o_order_extra WHERE order_id={$order_id}";
        $rows = $this->db->query($sql)->row_array();
        return $rows['store_id'];
    }

    /**
     * 限时惠检查
     *
     * @return void
     * @author 
     **/
    public function _check_xsh($items,$uid)
    {   
        $sku_id = array();
        $qty_arr = array();
        foreach ($items as $key => $value) {
            $sku_id[] = $value['sku_id'];
            $qty_arr[$value['sku_id']] = $value['qty'];
        }

        $this->db->select('p.start_time,p.over_time,g.xsh_limit,p.product_id,g.product_name,p.id as price_id,g.is_xsh_time_limit');
        $this->db->from('product_price as p');
        $this->db->join('product as g','g.id=p.product_id');
        $this->db->where_in('p.id',$sku_id);
        $this->db->where(array('g.xsh'=>'1',));
        $result = $this->db->get()->result_array();
        if(empty($result)){
            return false;
        }
        
        $error_msg = '';
        foreach ($result as $key => $value) {
            $this->db->select_sum('qty')
                    ->from('order as o')
                    ->join('order_product as i','o.id=i.order_id','left')
                    ->where('o.uid',$uid)
                    ->where('i.product_id',$value['product_id'])
                    ->where('o.order_status','1')
                    ->where('o.operation_id !=','5');
            if($value['is_xsh_time_limit']!=1){//不是永久
                $this->db->where('o.time >=',date('Y-m-d 00:00:00'))
                         ->where('o.time <=',date('Y-m-d 59:59:59'));
                $error_msg = "您购买的".$value['product_name']."是活动商品，每个用户每天限购".$value['xsh_limit']."份";
            }else{
                $error_msg = "您购买的".$value['product_name']."是活动商品，每个用户限购".$value['xsh_limit']."份";
            }
            $limit_num = $this->db->get()->row_array();
            $limit_num = $limit_num['qty'];
            if($value['xsh_limit']< ($limit_num + $qty_arr[$value['price_id']])){
                break;
            }else{
                $error_msg = '';
            }
        }
        return $error_msg;
    }

	/**
	 * 判断用户是否为新鲜点用户
	 * @param type $items	商品
	 * @param type $uid		用户id
	 */
	public function checkNewO2oUser($items, $uid) {
		if (!empty($items)) {
			$sku_id = array();
			$qty_arr = array();
			$product_ids = array();
			foreach ($items as $key => $value) {
				$sku_id[] = $value['sku_id'];
				$qty_arr[$value['sku_id']] = $value['qty'];
				$product_ids[] = $value['product_id'];
			}
			$where = array('uid' => $uid, 'operation_id <>' => '5');
			$count = $this->db->from('order')->where_in('order_type', array(3, 4))->where($where)->count_all_results();
			if ($count > 0) {
				$this->db->select('id,product_name');
				$this->db->from('product');
				$this->db->where('first_limit', '1');
				$this->db->where_in('id', $product_ids);
				$result = $this->db->get()->result_array();
				if (!empty($result)) {
					foreach ($result as $key => $value) {
						$return = "您购买的" . $value['product_name'] . "为活动商品，只有给天天到家新用户才能购买，请删除后重新提交订单";
						return $return;
					}
				}
			}
		}
		return FALSE;
	}

    /**
     * 判断商品是否可以团购购买
     * @param type $items	商品
     * @param type $uid		用户id
     */
    public function check_tuan_pro($items, $uid) {
		/*褚橙*/
		$tuan_pro_orange1203 = 7411; //褚橙团购
		$taglm1="1".date('Ymd');
		/* 褚橙 */
		/* 龙眼 上海 */
		$longan_sh = 8990;
		$taglm2 = "2" . date('Ymd');
		/* 龙眼 */
		/* 龙眼 北京 */
		$longan_bj = 8989;
		$taglm3 = "3" . date('Ymd');
		/* 龙眼 */
		/* 龙眼 广东 */
		$longan_gd = 8988;
		$taglm4 = "4" . date('Ymd');
		/* 龙眼 */
		/* 龙眼 成都 */
		$longan_cd = 8987;
		$taglm5 = "5" . date('Ymd');
		/* 龙眼 */
		/* 椰青 上海 */
		$coco_sh = 9131;
		$taglm6 = "6" . date('Ymd');
		/* 椰青 */
		/* 椰青 北京 */
		$coco_bj = 9132;
		$taglm7 = "7" . date('Ymd');
		/* 椰青 */
		/* 椰青 广东 */
		$coco_gd = 9133;
		$taglm8 = "8" . date('Ymd');
		/* 椰青 */
		/* 椰青 成都 */
		$coco_cd = 9134;
		$taglm9 = "9" . date('Ymd');
		/* 椰青 */
		/* 果汁 上海 */
		$juice_sh = 9616;
		$taglm10 = "10" . date('Ymd');
		/* 果汁 */
		/* 果汁 广东 */
		$juice_gd = 9615;
		$taglm11 = "11" . date('Ymd');
		/* 果汁 */

        /* 果汁 上海 */
        $juice_sh12 = 10019;
        $taglm12 = "12" . date('Ymd');
        /* 果汁 */
        /* 果汁 广东 */
        $juice_bj13 = 10024;
        $taglm13 = "13" . date('Ymd');
        /* 果汁 */

        /* 果汁 上海 */
        $juice_sh14 = 10276;
        $taglm14 = "14" . date('Ymd');
        /* 果汁 */

		if (!empty($items)) {
            $sku_id = array();
            $qty_arr = array();
            $product_ids = array();
            foreach ($items as $key => $value) {
                $sku_id[] = $value['sku_id'];
                $qty_arr[$value['sku_id']] = $value['qty'];
                $product_ids[] = $value['product_id'];
            }

            if (in_array($juice_sh14, $product_ids)) {
                $tuan_member = $this->db->from('tuan_member')->where(array(
                    'member_uid' => $uid,
                    'tag' => $taglm14
                ))->get()->row_array();
                if (empty($tuan_member)) {
                    return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
                } else {
                    $tuan_id = $tuan_member['tuan_id'];
                    $tuan_status = $this->db->from("tuan")->where(array(
                        'id' => $tuan_id,
                        'product_id' => $taglm14
                    ))->get()->row_array();
                    if ($tuan_status['is_tuan'] != 1) {
                        return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
                    }
                }
            }

            if (in_array($juice_sh12, $product_ids)) {
                $tuan_member = $this->db->from('tuan_member')->where(array(
                    'member_uid' => $uid,
                    'tag' => $taglm12
                ))->get()->row_array();
                if (empty($tuan_member)) {
                    return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
                } else {
                    $tuan_id = $tuan_member['tuan_id'];
                    $tuan_status = $this->db->from("tuan")->where(array(
                        'id' => $tuan_id,
                        'product_id' => $taglm12
                    ))->get()->row_array();
                    if ($tuan_status['is_tuan'] != 1) {
                        return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
                    }
                }
            }

            if (in_array($juice_bj13, $product_ids)) {
                $tuan_member = $this->db->from('tuan_member')->where(array(
                    'member_uid' => $uid,
                    'tag' => $taglm13
                ))->get()->row_array();
                if (empty($tuan_member)) {
                    return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
                } else {
                    $tuan_id = $tuan_member['tuan_id'];
                    $tuan_status = $this->db->from("tuan")->where(array(
                        'id' => $tuan_id,
                        'product_id' => $taglm13
                    ))->get()->row_array();
                    if ($tuan_status['is_tuan'] != 1) {
                        return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
                    }
                }
            }

			if (in_array($juice_sh, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
						'member_uid' => $uid,
						'tag' => $taglm10
					))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
							'id' => $tuan_id,
							'product_id' => $taglm10
						))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
					}
				}
			}
			if (in_array($juice_gd, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
						'member_uid' => $uid,
						'tag' => $taglm11
					))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
							'id' => $tuan_id,
							'product_id' => $taglm11
						))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~果汁团购1个仅5元，仅成团用户可购买！";
					}
				}
			}

			if (in_array($coco_sh, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
						'member_uid' => $uid,
						'tag' => $taglm6
					))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
							'id' => $tuan_id,
							'product_id' => $taglm6
						))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
					}
				}
			}
			if (in_array($coco_bj, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
						'member_uid' => $uid,
						'tag' => $taglm7
					))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
							'id' => $tuan_id,
							'product_id' => $taglm7
						))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
					}
				}
			}
			if (in_array($coco_gd, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
						'member_uid' => $uid,
						'tag' => $taglm8
					))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
							'id' => $tuan_id,
							'product_id' => $taglm8
						))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
					}
				}
			}
			if (in_array($coco_cd, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
						'member_uid' => $uid,
						'tag' => $taglm9
					))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
							'id' => $tuan_id,
							'product_id' => $taglm9
						))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~椰青团购1个仅5元，仅成团用户可购买！";
					}
				}
			}


			if(in_array($tuan_pro_orange1203, $product_ids)){
				$tuan_member = $this->db->from('tuan_member')->where(array(
                    'member_uid'=>$uid,
                    'tag' => $taglm1
                ))->get()->row_array();
                if(empty($tuan_member)){
                    return "啊哟~10元1斤的云南褚橙为团购商品哦，仅成团用户可购买，请删除后重新提交订单！";
                }else{
                    $tuan_id = $tuan_member['tuan_id'];
                    $tuan_status = $this->db->from("tuan")->where(array(
                        'id'=>$tuan_id,
                        'product_id' => $taglm1
                    ))->get()->row_array();
                    if($tuan_status['is_tuan']!=1){
                        return "啊哟~10元1斤的云南褚橙为团购商品哦，仅成团用户可购买，请删除后重新提交订单！";
                    }
                }
			}

			if (in_array($longan_sh, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
							'member_uid' => $uid,
							'tag' => $taglm2
						))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
								'id' => $tuan_id,
								'product_id' => $taglm2
							))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
					}
				}
			}

			if (in_array($longan_bj, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
							'member_uid' => $uid,
							'tag' => $taglm3
						))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
								'id' => $tuan_id,
								'product_id' => $taglm3
							))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
					}
				}
			}

			if (in_array($longan_gd, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
							'member_uid' => $uid,
							'tag' => $taglm4
						))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
								'id' => $tuan_id,
								'product_id' => $taglm4
							))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
					}
				}
			}

			if (in_array($longan_cd, $product_ids)) {
				$tuan_member = $this->db->from('tuan_member')->where(array(
							'member_uid' => $uid,
							'tag' => $taglm5
						))->get()->row_array();
				if (empty($tuan_member)) {
					return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
				} else {
					$tuan_id = $tuan_member['tuan_id'];
					$tuan_status = $this->db->from("tuan")->where(array(
								'id' => $tuan_id,
								'product_id' => $taglm5
							))->get()->row_array();
					if ($tuan_status['is_tuan'] != 1) {
						return "啊哟~元宵节龙眼200克3.9元，仅成团用户可购买，请删除后重新提交订单！";
					}
				}
			}

			$proidList = $this->getTuanList();
			foreach ($proidList as $val) {
				$pro_id = $val['product_id'];
				$taglm = $val['id'] . date('md');
				if (in_array($pro_id, $product_ids)) {
					$tuan_member = $this->db->from('tuan_member')->where(array(
								'member_uid' => $uid,
								'tag' => $taglm
							))->get()->row_array();
					$return = "啊哟~" . $val['name'] . "为团购商品哦，仅成团用户可购买，请删除后重新提交订单！";
					if (empty($tuan_member)) {
						return $return;
					} else {
						$tuan_id = $tuan_member['tuan_id'];
						$tuan_status = $this->db->from("tuan")->where(array(
									'id' => $tuan_id,
									'product_id' => $taglm
								))->get()->row_array();
						if ($tuan_status['is_tuan'] != 1) {
							return $return;
						}
						$tuanCount = $this->db->from('tuan_member')->where('tuan_id', $tuan_id)->count_all_results();
						if ($tuanCount < $val['user_num']) {
							return $return;
						}
					}
				}
			}
            $shaoTuanList = $this->get_shao_tuan_list();//扫码团购
            if(!empty($shaoTuanList)){
                foreach ($shaoTuanList as $val) {
                    $pro_id = $val['product_id'];
                    $taglm = $val['id'] . date('md');
                    if (in_array($pro_id, $product_ids)) {
                        $tuan_member = $this->db->from('tuan_member')->where(array(
                            'member_uid' => $uid,
                            'tag' => $taglm
                        ))->get()->row_array();
                        $return = "啊哟~" . $val['name'] . "为团购商品哦，仅成团用户可购买，请删除后重新提交订单！";
                        if (empty($tuan_member)) {
                            return $return;
                        } else {
                            $tuan_id = $tuan_member['tuan_id'];
                            $tuan_status = $this->db->from("tuan")->where(array(
                                'id' => $tuan_id,
                                'product_id' => $taglm
                            ))->get()->row_array();
                            if ($tuan_status['is_tuan'] != 1) {
                                return $return;
                            }
                        }
                    }
                }
            }
//            if(!empty($game_pro_id)){
//                $active_tag = 'juice'.date('Ymd');
//                if(in_array($game_pro_id,$product_ids)){
//                    $userInfo = $this->db->from('user')->where(array(
//                        'id'=>$uid
//                    ))->get()->row_array();
//                    $mobile = $userInfo['mobile'];
//                    $can_buy = $this->db->from('game_mobile')->where(array('active_tag'=>$active_tag,'mobile'=>$mobile))->get()->row_array();
//                    if(empty($can_buy)){
//                        $return = "去首页玩“果汁分你一半”游戏，榨完果汁就能买！";
//                        return $return;
//                    }
//                }
//            }
		}
        return FALSE;
    }

	/*
	 * 设备号验证
	 */
    function check_device($items,$device_code=''){
        if(!empty($items) && $device_code!=''){
            $sku_id = array();
            $qty_arr = array();
            $product_ids = array();
            foreach ($items as $key => $value) {
                $sku_id[] = $value['sku_id'];
                $qty_arr[$value['sku_id']] = $value['qty'];
                $product_ids[] = $value['product_id'];
            }

            $this->db->select('id,product_name');
            $this->db->from('product');
            $this->db->where('device_limit','1');
            $this->db->where_in('id',$product_ids);
            $result = $this->db->get()->result_array();
            if(!empty($result)){
                $device_product_id = array();
                foreach ($result as $key => $value) {
                    
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
                if(!empty($device_product_id)){
                    return array("code"=>"200","msg"=>serialize($device_product_id));
                }
            }
        }
        return true;
    }

    function add_device_limit($device_products,$device_code,$order_id){
        if(empty($device_products) || empty($device_code)) return true;
        foreach ($device_products as $value) {
            $data = array();
            $data['product_id'] = $value;
            $data['device_code'] = $device_code;
            $data['order_id'] = $order_id;
            $this->db->insert('device_limit',$data);
        }
        return true;
    }

	private function getTuanList() {
		$today = date('Y-m-d H:i:s');
		$list = $this->db->query("select b.name,r.activeId as id,r.user_num,r.product_id from ttgy_active_base as b join ttgy_active_tuan_rule as r on b.id=r.activeId where r.rule_type>0 and b.startTime<'{$today}' and b.endTime>'{$today}' and b.type=4")->result_array();
		return $list;
	}
    //o2o扫码团购list
    private function get_shao_tuan_list(){
        $today = date('Y-m-d H:i:s');
        return $this->db->query("select name, id,product_id from ttgy_active_base  where startTime<'{$today}' and endTime>'{$today}' and type=41 and product_id>0")->result_array();
    }

    public function getLastOpenStoreOrder($uid = 0,$store_id = 0){
        $where = '';
        $store_id and $where = " and store_id=".$store_id." ";
        $sql = "SELECT e.*,s.province_id province,s.city_id city,s.area_id area FROM ttgy_o2o_user_address e JOIN ttgy_o2o_store s ON e.store_id=s.id WHERE e.uid=".$uid.$where." AND s.isopen=1  AND e.lonlat is not null AND lonlat <> '' ORDER BY e.id DESC LIMIT 1";
        $res = $this->db->query($sql)->row_array();
        return $res;
    }

    public function getOpenStoreOrders($uid = 0){
        //$sql = "SELECT e.*,a.name AS area_name,c.name AS city_name,p.name AS province_name FROM ttgy_o2o_user_address e JOIN ttgy_o2o_store s ON e.store_id=s.id JOIN ttgy_area c ON c.id=e.city JOIN ttgy_area p ON p.id=e.province JOIN ttgy_area a ON a.id=e.area WHERE uid=".$uid." AND s.isopen=1 AND e.lonlat is not null AND lonlat <> '' ORDER BY e.id DESC LIMIT 10";
        $sql = "SELECT e.*,s.province_id province,s.city_id city,s.area_id area FROM ttgy_o2o_user_address e JOIN ttgy_o2o_store s ON e.store_id=s.id WHERE uid=".$uid." AND s.isopen=1 AND e.lonlat is not null AND e.lonlat <> '' ORDER BY e.id DESC";
        $res = $this->db->query($sql)->result_array();
        return $res;
    }

    public function addUserAddress($data){
        if(empty($data)) return;
        $fields_array = array_keys($data);
        $fields = implode(',', $fields_array);
        $values = '';
        $updates = '';
        $values .= "(";
        foreach ($fields_array as $field_name) {
            $values .= "'".addslashes($data[$field_name])."',";
            $updates .= $field_name . '='."'".addslashes($data[$field_name])."',";
        }
        $values = rtrim($values,',');
        $values .= ")";
        $updates = rtrim($updates,',');
        $sql = "INSERT INTO `ttgy_o2o_user_address` (".$fields.") VALUES ".$values." ON DUPLICATE KEY UPDATE ".$updates;
        return $this->db->query($sql);
    }
}