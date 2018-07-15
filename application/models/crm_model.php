<?php

class Crm_model extends MY_Model {

	function Crm_model() {
		parent::__construct();
		$this->db_master = $this->load->database('default_master', TRUE);
	}

	/*
	*crm获取用户信息		add by dengjm 2015-09-06
	*/
	public function billGetUserInfo($mobile){
        $this->load->model('user_model');
		 $sql1 = "SELECT id,reg_time,username,money,jf,user_rank,freeze FROM ttgy_user WHERE mobile='".$mobile."'";

		 $result1 = $this->db_master->query($sql1)->result_array();

		 if (empty($result1)) {
		 	return array('code'=>300,'msg'=>'此用户不存在');
		 }
		// if (count($result1) >1 ) {
		// 	$this->load->library('fdaylog');
   		//  $db_log = $this->load->database('db_log', TRUE);
        //  $this->fdaylog->add($db_log,'djm_mobile_more',$mobile);
		// }
		$user_rank = $this->config->item('user_rank')['level'];

        foreach ($result1 as $key => $value) {
        	$bsql = "SELECT round(sum(jf),2)as jf FROM ttgy_user_jf WHERE uid=".$value['id'];
        	$bres = $this->db_master->query($bsql)->row_array();
        	$result1[$key]['jf'] = $bres['jf'] > 0 ? $bres['jf'] : intval($bres['jf']);

        	$result1[$key]['user_rank'] = $user_rank[$value['user_rank']]['name'];


        	//离下级升级计算start		//add by dengjm 2015-10-14
        	$next_user_rank = $value['user_rank']+1;
            $next_rank =  $user_rank[$next_user_rank];

            $start_time = date('Y-m-d',strtotime("-12 month"));
            $end_time = date('Y-m-d H:i:s');

    		//$ibsql = "SELECT SUM(money+use_money_deduction) AS amoney,count(id) AS num FROM ttgy_order WHERE order_status=1 AND operation_id=3 AND uid=".$value['id']." AND time BETWEEN '".$start_time ."' AND '".$end_time."'";

			//$ibres = $this->db_master->query($ibsql)->row_array();
            $ibres_data = $this->user_model->user_rank_order_info($value['id'], $start_time, $end_time);
            $ibres['amoney'] = $ibres_data['ordermoney'];
            $ibres['num'] = $ibres_data['ordernum'];
			if (!$ibres['amoney']) {
				$ibres['amoney'] = 0.00;
			}

            if ($next_rank) {
                $result1[$key]['next_rank_name'] = $next_rank['name'];
                $result1[$key]['diff_ordernum'] = $next_rank['ordernum'] > $ibres['num'] ? $next_rank['ordernum'] - $ibres['num'] : 0;
                $result1[$key]['diff_ordermoney'] = $next_rank['ordermoney'] > $ibres['amoney'] ? $next_rank['ordermoney'] - $ibres['amoney'] : 0;
            }
        	//离下级升级计算end

            //会员优惠券start
            $b_time = date('Y-m-d',strtotime("-90 day"));

        	$sql2 = "SELECT c.id,c.uid,c.card_number,c.card_money,c.product_id,c.is_used,c.remarks,c.time,c.to_date,c.order_money_limit,c.channel,t.type FROM ttgy_card c left join ttgy_card_type t on c.card_number=t.card_number WHERE c.uid=".$value['id']." AND c.is_sent=1 AND c.time>'".$b_time."' ORDER BY c.time DESC";
		    $result2 = $this->db_master->query($sql2)->result_array();
		    if (empty($result2)) {
		    	$result1[$key]['card'] = array();
		    } else {
		    	foreach($result2 as &$val){
					$channel = unserialize($val['channel']);
					$val['channel_web'] = in_array(1,$channel) ? 1:0;
					$val['channel_app'] = in_array(2,$channel) ? 1:0;
					$val['channel_wap'] = in_array(3,$channel) ? 1:0;
					$sqlff = "select order_name from ttgy_order where use_card='".$val['card_number']."' and pay_status=1 and operation_id!=5 and order_status=1";
					$resff = $this->db_master->query($sqlff)->row_array();
					if ($resff) {
						$val['order_name'] = $resff['order_name'];
					} else {
						$val['order_name'] = '';
					}
                    $val['type'] = $val['type']?$val['type']:'';
                    $val['pro_info'] = array();
                    if($val['product_id']){
                        $sql_prodcut = "select id,product_name from ttgy_product where id in(".$val['product_id'].")";
                        $res_product = $this->db_master->query($sql_prodcut)->result_array();
                        foreach ($res_product as $card_pro) {
                            $c_pro_info = array();
                            $c_pro_info['product_id'] = $card_pro['id'];
                            $c_pro_info['product_name'] = $card_pro['product_name'];
                            $val['pro_info'][] = $c_pro_info;
                        }
                    }
		    	}
		    	$result1[$key]['card'] = $result2;
		    }
		    //会员优惠券end

		    //会员赠品start

		    $bgift = $this->getGift($value['id']);

		    //会员赠品end
			$result1[$key]['gift'] = $bgift;


            $uid = $value['id'];
            $date_cur = date('Y-m-01');
            $date_pre = date('Y-m-d', strtotime($date_cur . ' -1 month'));
            $date_year = date('Y-01-01');

            //签到
            $sign_in_cur =  $this->db->query('select count(*) cou from ttgy_check_ins where c_uid= ? and c_date >= ?', array($uid, $date_cur))->row_array();
            $sign_in_pre =  $this->db->query('select count(*) cou from ttgy_check_ins where c_uid= ? and c_date >= ? and c_date < ?', array($uid, $date_pre, $date_cur))->row_array();
            $sign_in_year =  $this->db->query('select count(*) cou from ttgy_check_ins where c_uid= ? and c_date >= ?', array($uid, $date_year))->row_array();
            $result1[$key]['sign_in'] = array(
                'cur_month' => $sign_in_cur['cou'],
                'pre_month' => $sign_in_pre['cou'],
                'year' => $sign_in_year['cou'],
            );

            //邀请
            $invite_cur =  $this->db->query('select count(*) cou from ttgy_user_invite_new where invite_by = ? and ctime >= ?', array($uid, strtotime($date_cur)))->row_array();
            $invite_pre =  $this->db->query('select count(*) cou from ttgy_user_invite_new where invite_by = ? and ctime >= ? and ctime < ?', array($uid, strtotime($date_pre), strtotime($date_cur)))->row_array();
            $invite_year =  $this->db->query('select count(*) cou from ttgy_user_invite_new where invite_by = ? and ctime >= ?', array($uid, strtotime($date_year)))->row_array();

            $invite2_cur =  $this->db->query('select count(*) cou from ttgy_user_invite_new2 where invite_by = ? and ctime >= ?', array($uid, strtotime($date_cur)))->row_array();
            $invite2_pre =  $this->db->query('select count(*) cou from ttgy_user_invite_new2 where invite_by = ? and ctime >= ? and ctime < ?', array($uid, strtotime($date_pre), strtotime($date_cur)))->row_array();
            $invite2_year =  $this->db->query('select count(*) cou from ttgy_user_invite_new2 where invite_by = ? and ctime >= ?', array($uid, strtotime($date_year)))->row_array();

            $result1[$key]['invite'] = array(
                'cur_month' => $invite_cur['cou'] + $invite2_cur['cou'],
                'pre_month' => $invite_pre['cou'] + $invite2_pre['cou'],
                'year' => $invite_year['cou'] + $invite2_year['cou'],
            );

            //提供客户所在地（省，市）
            $address = $this->db->query('select p.name province, c.name city, is_default from ttgy_user_address ua left join ttgy_area p on p.id=ua.province left join ttgy_area c on c.id=ua.city where uid = ?', array($uid))->result_array();
            $result1[$key]['region'] = array();
            if ($address) {
                $result1[$key]['region'] = $address[0];
                foreach ($address as $v) {
                    if ($v['is_default']) {
                        $result1[$key]['region'] = $v;
                        break;
                    }
                }
                unset($result1[$key]['region']['is_default']);
            }

            //好评度（订单好评的评价比例）
            $comment_good = $this->db->query('select count(*) cou from ttgy_comment_new where star in (4,5) and uid = ?', array($uid))->row_array();
            $comment_all = $this->db->query('select count(*) cou from ttgy_comment_new where uid = ?', array($uid))->row_array();
            $result1[$key]['good_comment_rate'] = 0;
            if($comment_all['cou'] > 0){
                $result1[$key]['good_comment_rate'] = round($comment_good['cou'] / $comment_all['cou'], 2) * 100;
            }

            //分享
            $share_cur =  $this->db->query('select count(*) cou from ttgy_active_card_log where (uid=? || mobile=?) and addtime >= ?', array($uid, $mobile, $date_cur))->row_array();
            $share_pre =  $this->db->query('select count(*) cou from ttgy_active_card_log where (uid=? || mobile=?) and addtime >= ? and addtime < ?', array($uid, $mobile, $date_pre, $date_cur))->row_array();
            $share_year =  $this->db->query('select count(*) cou from ttgy_active_card_log where (uid=? || mobile=?) and addtime >= ?', array($uid, $mobile, $date_year))->row_array();
            $result1[$key]['share'] = array(
                'cur_month' => $share_cur['cou'],
                'pre_month' => $share_pre['cou'],
                'year' => $share_year['cou'],
            );

            $freeze = 0;
            $login_error = $this->db->query('select num from ttgy_login_error where uid=?', array($uid))->row_array();
            if($value['freeze'] || $login_error['num'] >= 5){
                $freeze = 1;
            }
            $result1[$key]['freeze'] = $freeze;

            $black_list = $this->db->query('select credit_rank from ttgy_user_black_list where uid=?', array($uid))->row_array();
            $result1[$key]['black_list_rank'] = isset($black_list['credit_rank']) ? $black_list['credit_rank'] : 0;

        }

        return array('code'=>200,'msg'=>'获取用户信息成功','data'=>$result1);
	}

	//用户是否是V4V5
	public function billGetUserRank($mobile){
		$sql = "select user_rank from ttgy_user where mobile='".$mobile."'";
		$res = $this->db_master->query($sql)->result_array();
		if(empty($res)){
			return array('code'=>300,'msg'=>'该用户不存在');
		} elseif(count($res)>1) {
			return array('code'=>300,'msg'=>'该手机号对应多个用户，请联系技术部处理，songtao@fruitday.com');
		} else {

		}
		$user_rank = $res[0]['user_rank'];
		if (intval($user_rank) > 4) {
			return array('code'=>200,'data'=>1);
		} else {
			return array('code'=>200,'data'=>0);
		}

	}

	//用户补送赠品		2016-01-14 add by dengjm
	public function billSendGift($mobile,$gift_send_id){

		$sql = "select id from ttgy_user where mobile='".$mobile."'";
		$user = $this->db_master->query($sql)->result_array();
		if(empty($user)){
			return array('code'=>300,'msg'=>'该用户不存在');
		} elseif(count($user)>1) {
			return array('code'=>300,'msg'=>'该手机号对应多个用户，请联系技术部处理，songtao@fruitday.com');
		} else {

		}
        $gift_send = $this->db->select('*')->from('gift_send')->where('id', $gift_send_id)->get()->row_array();
        if($gift_send['gift_valid_day'] && $gift_send['gift_valid_day']>0){
            $gift_start_time = date('Y-m-d');
            $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_send['gift_valid_day'])-1).' day'));
        }elseif($gift_send['gift_start_time'] && $gift_send['gift_end_time'] && $gift_send['gift_start_time'] != '0000-00-00' && $gift_send['gift_end_time'] != '0000-00-00'){
            $gift_start_time = $gift_send['gift_start_time'];
            $gift_end_time = $gift_send['gift_end_time'];
        }else{
            $gift_start_time = $gift_send['start'];
            $gift_end_time = $gift_send['end'];
        }
		$data = array(
			'uid' => $user[0]['id'],
			'active_id' => $gift_send_id,
			'active_type' => 2,
			'has_rec' => 0,
			'bonus_order' => NULL,
			'cancel_order' => NULL,
			'time' => date('Y-m-d H:i:s',time()),
            'start_time'=>$gift_start_time,
            'end_time'=>$gift_end_time,
		);

		$this->db_master->trans_begin();
		$this->db_master->insert('user_gifts',$data);
		if ($this->db_master->trans_status() === FALSE) {
			$this->db_master->trans_rollback();
			return array('code'=>300,'msg'=>'服务器忙忙，请稍后再试');
		} else {
			$this->db_master->trans_commit();
			return array('code'=>200,'msg'=>'补送成功');
		}

	}

	//注销用户账户
	public function billFreezeUser($mobile){
		$sql = "select id from ttgy_user where mobile='".$mobile."'";
		$user = $this->db_master->query($sql)->result_array();
		if(empty($user)){
			return array('code'=>300,'msg'=>'该用户不存在');
		} elseif(count($user)>1) {
			return array('code'=>300,'msg'=>'该手机号对应多个用户，请联系技术部处理，songtao@fruitday.com');
		} else {

		}

		$insert_data = array(
			'uid'=>$user[0]['id'],
			'time'=>date("Y-m-d H:i:s"),
			'mobile'=>$mobile,
			'op_id'=>999999
		);

		$this->db_master->insert('user_freeze',$insert_data);

		$update_data = array(
			'mobile'=>''
		);
		$this->db_master->where('id',$user[0]['id']);
		$this->db_master->update('user',$update_data);
		return array('code'=>200,'msg'=>'注销成功');

	}

	public function getGift($uid){
		$sql = "SELECT active_id,active_type,has_rec,bonus_order,time,start_time,end_time FROM ttgy_user_gifts WHERE uid=".$uid;
		$result = $this->db_master->query($sql)->result_array();
		if (empty($result)) {
			return array();
		}

		$trade_gifts = array();
		$market_send_gifts = array();
		foreach ($result as  $key=>$value) {
			switch ($value['active_type']) {
				case '1':
                    $trade_gifts[$value['active_id']]['active_id'] = $value['active_id'];
                    $trade_gifts[$value['active_id']]['has_rec'] = $value['has_rec'];
                    $trade_gifts[$value['active_id']]['bonus_order'] = $value['bonus_order'];
                    $trade_gifts[$value['active_id']]['time'] = $value['time'];
                    $trade_gifts[$value['active_id']]['start_time'] = $value['start_time'];
                    $trade_gifts[$value['active_id']]['end_time'] = $value['end_time'];
					break;
				case '2':
                    if($value['active_id']!=575){
					    $market_send_gifts[$value['active_id']]['active_id'] = $value['active_id'];
					    $market_send_gifts[$value['active_id']]['has_rec'] = $value['has_rec'];
					    $market_send_gifts[$value['active_id']]['bonus_order'] = $value['bonus_order'];
                        $market_send_gifts[$value['active_id']]['start_time'] = $value['start_time'];
                        $market_send_gifts[$value['active_id']]['end_time'] = $value['end_time'];
                    }
					break;
				default://todo
					# code...
					break;
			}
		}

		$user_gift_arr = array();
		$product_id_arr = array();
		$i = 0;
		/*获取充值赠品信息start*/
		//active_type=1是充值赠品，active_type=2是营销赠品
		if(!empty($trade_gifts)){
			$trade_ids = array();
			foreach ($trade_gifts as $key => $value) {
				$trade_ids[] = $value['active_id'];
				$border[$value['active_id']] = $value['bonus_order'];
			}
			$this->db_master->select('id,trade_number,money,bonus_products');
			$this->db_master->from('trade');
			$this->db_master->where_in('id',$trade_ids);
			$trade_query = $this->db_master->get();
			$trade_result = $trade_query->result_array();


			foreach ($trade_result as $key => $value) {
				$products_array = explode(",", $value['bonus_products']);
				$product_id_arr[] = $products_array[0];

				$user_gift_arr[$i]['bonus_order'] = $border[$value['id']];
				$user_gift_arr[$i]['product_id'] = $products_array[0];
				$user_gift_arr[$i]['qty'] = 1;
				$user_gift_arr[$i]['active_type'] = 1;
				$user_gift_arr[$i]['active_id'] = $value['id'];
				$user_gift_arr[$i]['gift_source'] = '帐户余额充值赠品';
				$user_gift_arr[$i]['start_time'] = $trade_gifts[$value['id']]['start_time'].' 00:00:00';
				$user_gift_arr[$i]['end_time'] = $trade_gifts[$value['id']]['end_time'].' 23:59:59';//date('Y-m-d H:i:s',strtotime($trade_gifts[$value['id']]['time'])+14*24*60*60);
				$user_gift_arr[$i]['has_rec'] = $trade_gifts[$value['id']]['has_rec'];
				$i++;
			}
		}
		/*获取充值赠品信息end*/


		/*获取营销发放赠品信息start*/
		if(!empty($market_send_gifts)){
			$send_ids = array();
			foreach ($market_send_gifts as $key => $value) {
				$send_ids[] = $value['active_id'];
				$bborder[$value['active_id']] = $value['bonus_order'];
			}
			$now_time = date("Y-m-d H:i:s");
			$this->db_master->select('id,product_id,qty,remarks,start,end');
			$this->db_master->from('gift_send');
			$this->db_master->where_in('id',$send_ids);
			// $this->db->where(array('start <'=>$now_time));
			$send_query = $this->db_master->get();
			$send_result = $send_query->result_array();
			if(!empty($send_result)){
				foreach ($send_result as $key => $value) {
					$product_id_arr[] = $value['product_id'];
					$user_gift_arr[$i]['bonus_order'] = $bborder[$value['id']];
					$user_gift_arr[$i]['product_id'] = $value['product_id'];
					$user_gift_arr[$i]['qty'] = $value['qty'];
					$user_gift_arr[$i]['active_type'] = 2;
					$user_gift_arr[$i]['active_id'] = $value['id'];
					$user_gift_arr[$i]['gift_source'] = $value['remarks'];
                    $user_gift_arr[$i]['start_time'] = $market_send_gifts[$value['id']]['start_time'].' 00:00:00';;
                    $user_gift_arr[$i]['end_time'] = $market_send_gifts[$value['id']]['end_time'].' 23:59:59';
					// $user_gift_arr[$i]['start_time'] = $value['start'];
					// $user_gift_arr[$i]['end_time'] = $value['end'];
					$user_gift_arr[$i]['has_rec'] = $market_send_gifts[$value['id']]['has_rec'];
					$i++;
				}
			}
		}
		/*获取营销发放赠品信息end*/

		/*组织赠品信息start*/

        $product_id_arr_str = implode(",", array_filter($product_id_arr));
        $product_id_arr_str = trim($product_id_arr_str,',');
        if (empty($product_id_arr_str)) {
        	return array();
        }
        $ssql = "SELECT p.id,pp.id AS price_id,pp.product_no,pp.volume,pp.price,pp.unit,
        p.product_name,p.thum_photo,p.template_id FROM ttgy_product AS p LEFT JOIN ttgy_product_price AS pp
        ON p.id=pp.product_id WHERE p.id IN (".$product_id_arr_str.")";
        $prices_tmp = $this->db_master->query($ssql)->result_array();

		$prices = array();
		foreach ($prices_tmp as $key => &$value) {
            // 获取产品模板图片
            if ($value['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($value['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $value['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

			$prices[$value['id']] = $value;
		}
		foreach ($user_gift_arr as $key=>$value) {
			$user_gift_arr[$key]['price_id'] = $prices[$value['product_id']]['price_id'];
			$user_gift_arr[$key]['product_name'] = $prices[$value['product_id']]['product_name'];
			$user_gift_arr[$key]['product_no'] = $prices[$value['product_id']]['product_no'];
			$user_gift_arr[$key]['price'] = $prices[$value['product_id']]['price'];
			$user_gift_arr[$key]['photo'] = PIC_URL.$prices[$value['product_id']]['thum_photo'];
			$user_gift_arr[$key]['gg_name'] = $prices[$value['product_id']]['volume'].'/'.$prices[$value['product_id']]['unit'];
		}
		/*组织赠品信息end*/

		//订单唯一id转给订单号 start
		foreach ($user_gift_arr as $k => $v) {
			$sqlb1 = "SELECT order_name FROM ttgy_order WHERE id='".$v['bonus_order']."' LIMIT 1";
			$resb1 = $this->db_master->query($sqlb1)->row_array();
			$user_gift_arr[$k]['bonus_order'] = $resb1['order_name'];
		}
		//订单唯一id转给订单号 end

		//按时间倒叙start
		$time = array();
		foreach ($user_gift_arr as $key => $value) {
			$time[] = $value['start_time'];
		}
		array_multisort($time, SORT_DESC, $user_gift_arr);
		//按时间倒叙end

		return $user_gift_arr;
	}

	//获取最新消息		add by dengjm 2015-09-16
	public function billGetMessage($mobile){

		$sql = "SELECT body AS content,send_date AS create_time FROM ttgy_notify_log WHERE target='".$mobile."' AND is_succ=1 ORDER BY id desc LIMIT 1";

		$result = $this->db_master->query($sql)->row_array();

		return array('code'=>200,'msg'=>'获取信息成功','data'=>$result);
	}

	//清空短信记录		add by dengjm 2015-09-16
	public function billDelMessage($mobile){

		$sql = "DELETE FROM ttgy_sms_log WHERE mobile='".$mobile."'";
		$result = $this->db_master->query($sql);
		if ($result) {
			return array('code'=>200,'msg'=>'清空记录成功');
		} else {
			return array('code'=>300,'msg'=>'清空记录失败');
		}
	}

	//充值交易		add by dengjm 2015-09-16
	public function billTradeList($mobile){

		$sql1 = "SELECT id FROM ttgy_user WHERE mobile='".$mobile."'";
		$res1 = $this->db_master->query($sql1)->result_array();


		if (empty($res1)) {
			return array('code'=>200,'msg'=>'此电话号码不存在');
		}

		$trade_list = array();
		$time = date('Y-m-d', strtotime('-180 day'))." 00:00:00";

		foreach ($res1 as $key => $value) {
			$sql2 = "SELECT id,uid,trade_number,payment,money,status,time,type,invoice,bonus_products,has_rec FROM ttgy_trade WHERE uid=".$value['id']." AND has_deal=1 AND time>'".$time."' order by id desc";
			$res2 = $this->db_master->query($sql2)->result_array();
			foreach ($res2 as $k => $v) {
			if ( $v['invoice']>0 ) {
				$res2[$k]['invoice'] = 1;
			}
			$product_name = '';
			$bb = trim($v['bonus_products'],',');
				if ($bb) {
					$bsql = "SELECT product_name FROM ttgy_product where id IN(".$bb.")";
					$bres = $this->db_master->query($bsql)->result_array();
					foreach ($bres as $key1 => $value1) {
						$product_name.=$value1['product_name'].",";
					}
					$product_name = trim($product_name,',');
				}
			$res2[$k]['bonus_products'] = $product_name;
			}
			$trade_list[$value['id']] = $res2;
		}
		return array('code'=>200,'msg'=>'获取充值交易成功','data'=>$trade_list);

	}

	//提货券订单		add by dengjm 2015-09-16
	public function billCardOrder($mobile,$search_start='',$search_end=''){

		$where = '';
		$where .= empty($search_start) ? '' : " AND o.time >= '".$search_start."'";
		$where .= empty($search_end) ? '' : " AND o.time <= '".$search_end."'";

		$sql = "SELECT o.order_name,o.pay_status,o.operation_id,o.time,o.pay_name FROM ttgy_order AS o INNER JOIN ttgy_order_address AS oa ON o.id=oa.order_id WHERE oa.mobile='".$mobile.
				"' AND o.pay_parent_id=6 AND o.pay_id='1' AND o.order_status=1 ".$where." ORDER BY o.time desc,o.id desc";


		$res = $this->db_master->query($sql)->result_array();
		$pay = $this->config->item('pay');
		$operation = $this->config->item('operation');

		foreach ($res as $key => $value) {
			$res[$key][pay_status] = $pay[$value['pay_status']];
			$res[$key][operation_id] = $operation[$value['operation_id']];
		}

		if (empty($res)) {
			return array('code'=>200,'msg'=>'无提货券订单','data'=>array());
		}
		return array('code'=>200,'msg'=>'获取提货券订单成功','data'=>$res);
	}

	//积分查询		add by dengjm 2015-09-16
	public function billUserJf($mobile){

		$sql1 = "SELECT id FROM ttgy_user WHERE mobile='".$mobile."'";
		$res1 = $this->db_master->query($sql1)->result_array();

		if (empty($res1)) {
			return array('code'=>200,'msg'=>'此电话号码不存在');
		}

		$jf_list = array();

		$btime = date('Y-m-d',strtotime('-30 days'));
		foreach ($res1 as $key => $value) {
			$sql2 = "SELECT jf,reason,time FROM `ttgy_user_jf` WHERE uid=".$value['id']." AND time>'".$btime."' ORDER BY id desc";
			$res2 = $this->db_master->query($sql2)->result_array();
			$jf_list[$value['id']] = $res2;
		}

		return array('code'=>200,'msg'=>'获取积分成功','data'=>$jf_list);
	}

	//提货券查询		add by dengjm 2015-09-17
	public function billTiHuoQuan($card_number){

		 $sql = "SELECT card_number,card_money,order_name,is_used,is_sent,used_time,start_time,to_date,product_id,remarks FROM ttgy_pro_card WHERE card_number='".$card_number."'";

         $combo = $this->db_master->query($sql)->row_array();

         if (empty($combo)) {
         	return array('code'=>200,'msg'=>'提货券卡号不存在','data'=>array());
         } else {
	        //获取产品配送区域 start
	        $product_id_arr = explode(',',$combo['product_id']);
	        $this->db_master->select('send_region');
	        $this->db_master->from('product');
	        $this->db_master->where_in('id',$product_id_arr);
	        $pro_query = $this->db_master->get();
	        $pro_info = $pro_query->result_array();


	        $region_arr = array();
	        foreach($pro_info as $region){
	                $region_arr[] = unserialize($region['send_region']);
	        }

	        $can_send_region = array();
	        foreach ($region_arr as $value) {
	            $can_send_region or $can_send_region = $value;
	            $can_send_region = array_intersect($can_send_region,$value);
	        }

	        $this->db_master->where(array("pid"=>"0","active"=>1));
	        if(!empty($can_send_region)){
	            $this->db_master->where_in('id',$can_send_region);
	        }
	        $this->db_master->from("area");
	        $this->db_master->order_by("order");
	        $query = $this->db_master->get();
	        $result = $query->result_array();

	        $province_strb = "";
	        foreach ($result as $key => $value) {
	            $province_strb .= $value['name'].",";
	        }
	        $province_strb = rtrim($province_strb,",");
	        //获取配送区域end
	        $combo['area'] = $province_strb;

         	return array('code'=>200,'msg'=>'获取提货券信息成功','data'=>$combo);
         }
	}

	//充值卡查询		add by dengjm 2015-09-17
	public function billChargeCard($card_number){

		$sql = "SELECT c.card_number,c.card_money,c.to_date,c.is_used,c.activation,t.trade_number,t.money,t.time,u.mobile
        FROM ttgy_gift_cards AS c
        left join ttgy_trade AS t ON c.card_number=t.card_number
        left join ttgy_user AS u ON u.id=t.uid
        WHERE c.card_number='".$card_number."'";

        $charge = $this->db_master->query($sql)->row_array();

        if (empty($charge)) {
         	return array('code'=>200,'msg'=>'充值卡卡号不存在','data'=>array());
        } else {
         	return array('code'=>200,'msg'=>'获取充值卡信息成功','data'=>$charge);
        }
	}

	//用户优惠券作废
	public function billUnSent($card_number){
		$sqlb = "SELECT id FROM ttgy_card WHERE card_number='".$card_number."' LIMIT 1";
		$resb = $this->db_master->query($sqlb)->row_array();

		if (empty($resb)) {
			return array('code'=>300,'msg'=>'优惠券卡号不存在');
		}

		$sql = "UPDATE ttgy_card SET is_sent=0 WHERE card_number='".$card_number."'";
		$res = $this->db_master->query($sql);

		if ($res) {
			return array('code'=>200,'msg'=>'作废成功');
		} else {
			return array('code'=>300,'msg'=>'作废失败');
		}
	}

	//会员订单
	public function billOrderList($mobile,$page=1,$pagesize=10,$order_name='',$search_start='',$search_end=''){

	    if(!empty($mobile)){
            $sql1 = "SELECT id,reg_time,username,money,jf,user_rank,mobile FROM ttgy_user WHERE mobile='".$mobile."'";
            $result1 = $this->db_master->query($sql1)->result_array();
        }else{
            $sql1 = "SELECT u.id,u.reg_time,u.username,u.money,u.jf,u.user_rank,u.mobile FROM ttgy_order o, ttgy_user u WHERE u.id=o.uid and o.order_name= ?";
            $result1 = $this->db_master->query($sql1, $order_name)->result_array();
        }

		 if (empty($result1)) {
		 	return array('code'=>300,'msg'=>'此用户不存在');
		 }

		$pay = $this->config->item('pay');
		$operation = $this->config->item('operation');

		 $data = array();
		 $i=0;

		 foreach ($result1 as $key => $value) {
			$where = '';
			$where .= empty($order_name) ? '' : " AND order_name='".$order_name."'";
			$where .= empty($search_start) ? '' : " AND time >= '".$search_start."'";
			$where .= empty($search_end) ? '' : " AND time <= '".$search_end."'";


		 	$sql1 = "SELECT COUNT(id) AS count_id FROM ttgy_order WHERE order_status=1 AND uid=".$value['id'].$where;

		 	$res1 = $this->db_master->query($sql1)->row_array();
		 	$count = $res1['count_id'];

		 	$pages     =  ceil($count/$pagesize);
			if ($pages==0) {
			$pages=1;
			}

			$page = ($page>=$pages) ? $pages : $page;

             $user_mobile = $value['mobile'];

			$sql2 = "SELECT id,order_name,money,shtime,pay_name,pay_status,operation_id,time,sync_status,order_type,method_money,today_method_money FROM ttgy_order
					WHERE uid=".$value['id']." AND order_status=1 ".$where." ORDER BY time desc,id desc LIMIT ".($page-1)*$pagesize.",".$pagesize;

			$res2 = $this->db_master->query($sql2)->result_array();

			foreach ($res2 as $key => $value) {
				if ($value['order_type']==3 || $value['order_type']==4) {
					$res2[$key]['order_type'] = 'O2O';
					$res2[$key]['is_up'] = 0;
				} elseif ($value['order_type']==7) {
					$res2[$key]['is_up'] = 1;
					$res2[$key]['order_type'] = 'B2C';
				} else {
					$res2[$key]['order_type'] = 'B2C';
					$res2[$key]['is_up'] = 0;
				}

				$res2[$key]['pay_status'] = $pay[$value['pay_status']];
				$res2[$key]['operation_id'] = $operation[$value['operation_id']];
				$res2[$key]['mobile'] = $user_mobile;

				if($value['shtime']=='after2to3days'){
					$addsql = "SELECT area,province FROM ttgy_order_address WHERE order_id=".$value['id'];
					$address_info = $this->db_master->query($addsql)->row_array();
					if($address_info['area'] && $address_info['province']){
						$cutsql = "SELECT cut_off_time FROM ttgy_area WHERE id=".$address_info['area'];
						$cut = $this->db_master->query($cutsql)->row_array();
						$cut_off_time = $cut['cut_off_time'];

						if (in_array($address_info['province'],array(106092))){
							$is_send_wd = false;
						}else{
							$is_send_wd = true;
						}

						$send_h = date('H',strtotime($value['time']));

						if($send_h>=$cut_off_time){
							$add_time = 86400;
						}else{
							$add_time = 0;
						}
						if(!$is_send_wd){
							$add_time += 86400;
						}
						$res2[$key]['shtime'] = date("Ymd",strtotime($value['time'])+$add_time);
					}
				}
			}

			$data[$i]['page'] = $page;
			$data[$i]['pages'] = $pages;
			$data[$i]['pagesize'] = $pagesize;
			$data[$i]['total'] = $count;
			$data[$i]['order_list'] = $res2;
			$i++;
		 }

		 return array('code'=>200,'msg'=>'获取订单列表成功','data'=>$data);
	}

	//订单详情
	public function billOrderDetail($id){
		$pay = $this->config->item('pay');
		$operation = $this->config->item('operation');

		//会员订单信息
		$sql = "SELECT id,order_name,uid,trade_no,pay_status,operation_id,time,pay_name,shtime,msg,hk,fp,fp_dz,money,goods_money,method_money,today_method_money,card_money,use_money_deduction,jf_money,other_msg,pay_discount,oauth_discount FROM ttgy_order WHERE id=".$id;
		$info = $this->db->query($sql)->row_array();
		$info['pay_status'] = $pay[$info['pay_status']];
		$info['operation_id'] = $operation[$info['operation_id']];

		$uid = $info['uid'];

		//会员信息
		$usql = "SELECT mobile,username FROM ttgy_user WHERE id=".$uid;
		$user_info = $this->db->query($usql)->row_array();
		//会员订单的商品
		$psql = "SELECT id,product_name,gg_name,price,qty,total_money,sale_rule_type,order_id,product_id,product_no FROM ttgy_order_product WHERE order_id=".$info['id'];
		$product_array = $this->db->query($psql)->result_array();

		foreach ($product_array as $key => $value) {
			$fbsql = "SELECT content,star FROM ttgy_comment_new WHERE order_id=".$value['order_id']." AND product_id=".$value['product_id'];
			$recom = $this->db->query($fbsql)->row_array();
			$product_array[$key]['recomment']=$recom;
		}

		//会员订单的收货地址
		$asql = "SELECT * FROM ttgy_order_address WHERE order_id=".$info['id'];
		$address_array = $this->db->query($asql)->row_array();

		//送货时间处理
		if($info['shtime']=='after2to3days'){
			$addsql = "SELECT area,province FROM ttgy_order_address WHERE order_id=".$info['id'];
				$address_info = $this->db->query($addsql)->row_array();
				if($address_info['area'] && $address_info['province']){
					$cutsql = "SELECT cut_off_time FROM ttgy_area WHERE id='".$address_info['area']."'";
					$cut = $this->db->query($cutsql)->row_array();
					$cut_off_time = $cut['cut_off_time'];

					if (in_array($address_info['province'],$area_refelect['1'])){
						$is_send_wd = false;
					}else{
						$is_send_wd = true;
					}

					$send_h = date('H',strtotime($info['time']));

					if($send_h>=$cut_off_time){
						$add_time = 86400;
					}else{
						$add_time = 0;
					}
					if(!$is_send_wd){
						$add_time += 86400;
					}
					$info['shtime'] = date("Ymd",strtotime($info['time'])+$add_time);
				}
		}

		$info['pay_discount_money'] = $info['pay_discount'] + $info['new_pay_discount'] + $info['oauth_discount'];
		$info['pay_discount_money'] or $info['pay_discount_money'] = 0;

		$array = array();
		$array['info'] = $info;
		$array['user_info'] = $user_info;
		$array['op'] = $op;
		$array['product_array'] = $product_array;
		$array['address_array'] = $address_array;

		return array('code'=>200,'msg'=>'获取订单详情成功','data'=>$array);

	}


	//添加优惠券
	public function billSaveCard($params){
		$curr_time = time();						//优惠券添加时间
		$card_money = trim($params['card_money']);  //卡金额
		$maketing =$params['maketing'];             //营销类型
		$channel = $params['channel'];			    //渠道
		$product_id = $params['product_id'];        //商品id
		$restr_good = empty($product_id) ? 0 : 1;
		$order_money_limit = trim($params['order_money_limit']);//金额限制
		$remarks = trim($params['remarks']); 	   //备注
		$direction = trim($params['direction']);   //使用限制说明
		$mobile = $params['mobile'];               //用户手机号
		$time = $params['time'];				  //开始有效期
		$to_date = $params['to_date'];			 //结束有效期
		$is_sent = 1;							//是否激活
		$max_use_times = 1;						//优惠券使用次数
		if (empty($card_money)||!isset($order_money_limit)||empty($remarks)||empty($to_date)||empty($time)) {
			return array('code'=>300,'msg'=>'输入数据有误');
		}

		$sql1 = "SELECT id FROM ttgy_user WHERE mobile='".$mobile."'";
		$result1 = $this->db_master->query($sql1)->result_array();
		if (empty($result1)) {
		 	return array('code'=>300,'msg'=>'此用户不存在');
		 }

		 if ($channel) {
		 	$channel = explode(',', $channel);
		 }

		$data = array(
			'card_number' => '',
			'card_money' => $card_money,
			'product_id' => $product_id,
			'restr_good' => $restr_good,
			'maketing' => $maketing,
			'is_sent' => $is_sent,
			'time' => $time,
			'to_date' => $to_date,
			'order_money_limit' => $order_money_limit,
			'channel' => empty($channel) ? "" :serialize($channel),
			'direction'=>$direction,
			'uid'=>0,
			'remarks'=>$remarks
		);

		$card_number_pre = 'gt';
		$data_arr = array();
        $card_numbers = array();
		foreach ($result1 as $key => $value) {
			$card_number = $this->rand_card_number($card_number_pre);
			$data['card_number'] = $card_number;
			$data['uid'] = $value['id'];
			$data_arr[] = $data;
            $card_numbers[] = $card_number;
		}

		$bres = $this->db_master->insert_batch('card', $data_arr);
        $this->load->model('card_model');
        $this->card_model->addCardType($card_numbers,'客服补偿券',0, '客服部');
		if ($bres) {
			return array('code'=>200,'msg'=>'添加优惠券成功');
		} else {
			return array('code'=>300,'msg'=>'添加优惠券失败');
		}

	}
	protected function rand_card_number($p_card_number=''){
		$a   =  "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
		$a_array = explode(",",$a);
		for($i=1;$i<=10;$i++){
			$tname.=$a_array[rand(0,31)];
		}
		if($this->checkCardNum($p_card_number.$tname)){
			$tname = $this->rand_card_number($p_card_number);
		}
		return $p_card_number.$tname;
	}

	protected function checkCardNum($cardNum){
		$sql = "SELECT count(id) as ids FROM ttgy_card WHERE card_number='".$cardNum."'";
		$res = $this->db_master->query($sql)->row_array();
		if ($res['ids'] > 0) {
			return true;
		} else {
			return false;
		}
	}

	//操作用户积分 		add by dengjm 2015-09-23
	public function billOperateJf($params){
		if (!is_numeric($params['jf'])) {
			return array('code'=>300,'msg'=>'传入的积分有误');
		}

		$sql1 = "SELECT id FROM ttgy_user WHERE mobile='".$params['mobile']."'";
		$res1 = $this->db_master->query($sql1)->result_array();

		if (empty($res1)) {
			return array('code'=>300,'msg'=>'此电话号码不存在');
		}

		if (count($res1) > 1) {
			return array('code'=>300,'msg'=>'此电话号码对应多个用户，请联系技术处理！');
		}

		$sqlb = "select sum(jf) as jf from ttgy_user_jf  where uid=".$res1[0]['id'];

		$allt = $this->db_master->query($sqlb)->row_array();
		$ujf = intval($allt['jf']);
		if ($ujf<0) {
			return array('code'=>300,'msg'=>'用户积分为负数');
		}

		if (abs(intval($params['jf'])) > intval($allt['jf']) && intval($params['jf']) < 0) {
			return array('code'=>300,'msg'=>'用户积分不足');
		}

		$data['jf'] = $params['jf'];
		$data['reason'] = $params['reason'];
		$data['uid'] = $res1[0]['id'];
		$data['time'] = date('Y-m-d H:i:s',time());
        $data['type'] = '电话系统操作';
		$bbb = $this->db_master->insert('user_jf',$data);
		$this->load->model('user_model');
		$type = $params['jf']>0?1:2;
		$this->user_model->updateJf($data['uid'],$params['jf'],$type);
		if ($bbb) {
			return array('code'=>200,'msg'=>'操作成功');
		} else {
			return array('code'=>300,'msg'=>'操作失败');
		}

	}

	//优惠券查询		add by dengjm 2015-10-28
	public function billSelectCard($card_number){
	   $sql = "select c.id,c.card_number,c.card_money,c.is_used,c.maketing,c.direction,c.is_sent,c.content,c.time,c.to_date,c.remarks,c.product_id,
        	  c.order_money_limit,c.channel,o.order_name,u.mobile from ttgy_card as c left join ttgy_user as u on c.uid=u.id
        	  left join ttgy_order as o ON c.card_number=o.use_card AND o.operation_id!=5 AND o.order_status=1
        	    where c.card_number='".$card_number.
              "' order by c.id desc limit 1";
       $res = $this->db_master->query($sql)->row_array();

       if ($res) {
		$channel = unserialize($res['channel']);
		$res['channel_web'] = in_array(1,$channel) ? 1:0;
		$res['channel_app'] = in_array(2,$channel) ? 1:0;
		$res['channel_wap'] = in_array(3,$channel) ? 1:0;

		if ($res['maketing'] == 1) {
			$res['maketing'] = 'O2O';
		} elseif($res['maketing'] == 0) {
			$res['maketing'] = '官网';
		} else {
			#todo
		}
       	return array('code'=>200,'msg'=>'查询成功','data'=>$res);
       } else {
       	return array('code'=>300,'msg'=>'此卡号不存在','data'=>array());
       }
	}

	public function billSelectCardb($order_name){
		$sql = "select c.id,c.card_number,c.card_money,c.is_used,c.maketing,c.direction,c.is_sent,c.content,c.time,c.to_date,c.remarks,c.product_id,
        	  c.order_money_limit,c.channel,o.order_name,u.mobile from ttgy_card as c left join ttgy_user as u on c.uid=u.id
        	  left join ttgy_order as o ON c.card_number=o.use_card AND o.operation_id!=5 AND o.order_status=1
        	    where o.order_name='".$order_name.
              "' order by c.id desc limit 1";
        $res = $this->db_master->query($sql)->row_array();
        if ($res) {
			$channel = unserialize($res['channel']);
			$res['channel_web'] = in_array(1,$channel) ? 1:0;
			$res['channel_app'] = in_array(2,$channel) ? 1:0;
			$res['channel_wap'] = in_array(3,$channel) ? 1:0;

			if ($res['maketing'] == 1) {
				$res['maketing'] = 'O2O';
			} elseif($res['maketing'] == 0) {
				$res['maketing'] = '官网';
			} else {
				#todo
			}
	       	return array('code'=>200,'msg'=>'查询成功','data'=>$res);
       } else {
       		return array('code'=>300,'msg'=>'没有使用优惠券','data'=>array());
       }
	}




	//置赠品为已领取
	public function billResetPro($params){

		$active_id = $params['active_id'];
		$bbsql = "SELECT * FROM ttgy_trade WHERE id=$active_id";
		$bbres = $this->db_master->query($bbsql)->row_array();
		if (!$bbres) {
			return array('code'=>300,'msg'=>'该赠品不存在');
		}

		$this->db->trans_begin();

		if (!empty($params['actor_id']) && !empty($params['actor_name']) && !empty($params['reason'])) {
			$data = array(
						'active_id' => $params['active_id'],
						'actor_id' => $params['actor_id'],
						'actor_name' => $params['actor_name'],
						'reason' => $params['reason'],
						'order_name' => '',
						'act_type' => 1,
						'actor_time' => date('Y-m-d H:i:s',time())
					);
			$this->db_master->insert('gift_log',$data);
		}

		$sql = "UPDATE ttgy_trade SET has_rec=1 WHERE id=$active_id";
		$res = $this->db_master->query($sql);

		$sql1 = "UPDATE ttgy_user_gifts SET has_rec=1 WHERE active_id=$active_id AND active_type=1";
		$res1 = $this->db_master->query($sql1);

		if ($this->db->trans_status() === FALSE)
		{
		    $this->db->trans_rollback();
		    return array('code'=>300,'msg'=>'重置失败');
		}
		else
		{
		    $this->db->trans_commit();
		}

		return array('code'=>200,'msg'=>'置为已领取');
	}

	//置赠品为未领取
	public function billResetProUnused($params){

		$active_id = $params['active_id'];
		$bbsql = "SELECT * FROM ttgy_trade WHERE id=$active_id";
		$bbres = $this->db_master->query($bbsql)->row_array();
		if (!$bbres) {
			return array('code'=>300,'msg'=>'该赠品不存在');
		}


		$this->db->trans_begin();

		if (!empty($params['actor_id']) && !empty($params['actor_name']) && !empty($params['reason'])) {
			$data = array(
						'active_id' => $params['active_id'],
						'actor_id' => $params['actor_id'],
						'actor_name' => $params['actor_name'],
						'reason' => $params['reason'],
						'order_name' => '',
						'act_type' => 2,
						'actor_time' => date('Y-m-d H:i:s',time())
					);
			$this->db_master->insert('gift_log',$data);
		}

		$sql = "UPDATE ttgy_trade SET has_rec=0 WHERE id=$active_id";
		$res = $this->db_master->query($sql);

		$sql1 = "UPDATE ttgy_user_gifts SET has_rec=0 WHERE active_id=$active_id AND active_type=1";
		$res1 = $this->db_master->query($sql1);

		if ($this->db->trans_status() === FALSE)
		{
		    $this->db->trans_rollback();
		    return array('code'=>300,'msg'=>'重置失败');
		}
		else
		{
		    $this->db->trans_commit();
		}

		return array('code'=>200,'msg'=>'置为未领取');
	}

	//申述订单
	public function billOrderComplaints($params){

		$sql = "SELECT q.id,q.ordername,q.product_id,q.product_no,oq.time,oq.order_type FROM ttgy_quality_complaints AS q INNER JOIN ttgy_order AS oq ON q.ordername=oq.order_name WHERE q.status=0 and q.crmsyn=0 ORDER BY q.time ASC,q.id ASC LIMIT 500";
		$res = $this->db_master->query($sql)->result_array();
		if (empty($res)) {
			return array('code'=>300,'msg'=>'没有申诉订单');
		}

		$arr = array();
		//$ordername = array_column($res,null,'ordername');

        foreach ($res as $key => $value) {
        	$sql1 = "SELECT op.product_id,op.product_name,op.product_no,op.gg_name,op.price,op.qty,op.total_money,
        			qc.id,oa.mobile AS information,qc.description,qc.photo,qc.time AS sstime,qc.ordername,p.thum_photo,o.time,p.template_id
                     FROM ttgy_quality_complaints AS qc INNER JOIN ttgy_order AS o ON o.order_name=qc.ordername
                     INNER JOIN ttgy_order_product AS op ON o.id=op.order_id
                     LEFT JOIN ttgy_product AS p ON p.id=op.product_id
                     LEFT JOIN ttgy_order_address AS oa ON oa.order_id =o.id
                     WHERE qc.id=".$value['id'].
                     " AND op.product_id=".$value['product_id']." AND op.product_no='".$value['product_no']."'";
            $res1 = $this->db_master->query($sql1)->row_array();
            if (!$res1) continue;

            // 获取产品模板图片
            if ($res1['template_id']) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($res1['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $res1['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

        	 $res1['photo'] = array_values(array_filter(explode(',', $res1['photo'])));
       		 foreach ($res1['photo'] as $k => $v) {
        		$res1['photo'][$k] = $this->config->item('IMAGE_URL').$v;
       		 }
       		 $res1['thum_photo'] = 'http://cdn.fruitday.com/'.$res1['thum_photo'];
             $res1['order_type'] = in_array($value['order_type'], array(3,4))?'O2O':'B2C';
       		 $arr[] = $res1;

            // $arr[$value['ordername']]['order_name'] = $value['ordername'];
            // $arr[$value['ordername']]['time'] = $value['time'];
        }

		return array('data'=>array_values($arr));

	}
}
