<?php
  Class Opencard extends CI_Model{
	 
	function Opencard(){
	     parent::__construct();
	     $this->load->helper('public');
	}

	function sendCard($params){
		// return array("result"=>"error","msg"=>"该活动已结束，您可以下载天天果园app参与其他活动");
		/*常量配置start*/
		$active_info['active_tag'] = 'open_yezi_game_07_27';
        $active_info['active_begin'] = 1414771200;//2014-11-01 00:00:00    
        $active_info['active_end'] = 1427644800;//2014-11-08 00:00:00
        $allow_active_arr = array(1,2,3);
        if(!in_array($params['active_type'], $allow_active_arr)){
        	return array('result'=>'error','msg'=>'活动类型错误');
        }
        $active_type = 'card';
        $card_amount = 0;
        switch ($params['active_type']) {
        	case 1:
                $card_amount = 20;
        		$order_money_limit = 200;
                $card_desc = '满200使用';
                $active_type = 'card';
        		break;
        	case 2:
                $card_amount = 10;
        		$order_money_limit = 0;
                $card_desc = '全场通用';
                $active_type = 'card';
        		break;
            case 3:
                $active_type = 'gift';
                $gift_send_id = 575;//todo
                break;
        	default:
                $card_amount = 0;
                $order_money_limit = 0;
                $card_desc = '全场通用';
                $active_type = 'card';
        		break;
        }

        if($active_type == 'card'){
            $active_info['share_to_date'] = date("Y-m-d",strtotime("+7day"));
            $active_info['share_card_money'] = $card_amount;
            $active_info['share_product_id'] = '0';
            $active_info['share_return_jf'] = 0;
            $active_info['share_remarks'] = "迪士尼活动优惠券";
    		$active_info['share_p_card_number'] = 'dn';
    		$active_info['order_money_limit'] = $order_money_limit;
            $active_info['card_desc'] = $card_desc;

    		/*常量配置end*/
    		if(!isset($params['phone']) || empty($params['phone'])){
                return array('result'=>'error','msg'=>'手机号不能为空');
            }
            if($card_amount<=0){
                return array('result'=>'error','msg'=>'抵扣金额错误');
            }

            if(!is_mobile($params['phone'])){
                return array('result'=>'error','msg'=>'手机号码错误');
            }

            return $this->doSendCard($params['phone'],$card_amount,$active_info);
        }elseif($active_type == 'gift'){
            return array("result"=>"error","msg"=>"网络异常，请稍后重试");
            $active_info['gift_send_id'] = $gift_send_id;
            return $this->doSendGift($params['phone'],$gift_send_id,$active_info);
            
        }

		
	}

    function doSendGift($mobile,$gift_send_id,$active_info){
        /*活动验证start*/
        $active_tag = $active_info['active_tag'];
        $active_begin = $active_info['active_begin'];
        $active_end = $active_info['active_end'];
        $now_time = time();
        if($now_time>$active_end){
            return array("result"=>"error","msg"=>"该活动已结束，您可以直接下载天天果园app参与更多活动");
        }
        if($now_time<$active_begin){
            return array("result"=>"error","msg"=>"该活动还未开始，您可以下载天天果园app参与其他活动");
        }
        /*活动验证end*/


        $uid = 0;        
        $this->db->select('id');
        $this->db->from('user');
        $this->db->where('mobile',$mobile);
        $user_result = $this->db->get()->row_array();
        if(!empty($user_result)){
            $uid = $user_result['id'];
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
        /*优惠券设定start*/
        $share_card_data = array(
            'uid'=>$uid,
            'active_id'=>$gift_send_id,
            'active_type'=>2,
            'has_rec'=>0,
            'start_time'=>$gift_start_time,
            'end_time'=>$gift_end_time,
        );
        /*优惠券设定end*/

        /*活动记录start*/
        $this->db->from('wqbaby_active');
        $this->db->where(array('mobile' => $mobile,'active_tag'=>$active_tag));
        $result = $this->db->get()->row_array();
        if(empty($result)){
            $this->db->insert('user_gifts',$share_card_data);
            $user_gift_id = $this->db->insert_id();
            $active_data = array(
                'mobile'=>$mobile,
                'card_number'=>$user_gift_id,
                'active_tag'=>$active_tag,
                'active_type'=>2
            );
            $this->db->insert('wqbaby_active',$active_data);
        }else{
            
        }
        /*活动记录end*/
        return array("result"=>"succ","msg"=>"恭喜您获得橙子一份，已发至您的天天果园账户。请立即下载天天果园App或登录官网www.fruitday.com并使用该手机注册，即可领取。由于近期发现大量违规参与此游戏的行为，天天果园已将违规号码加入非法名单，非法名单中的账号将不予发货。如有疑问请及时联系客服电话4007200770，感谢您对天天果园的支持。");
    }

	function doSendCard($mobile,$card_money,$active_info){
        /*活动验证start*/
        $active_tag = $active_info['active_tag'];
        $active_begin = $active_info['active_begin'];
        $active_end = $active_info['active_end'];
        $active_product_id = $active_info['active_product_id'];
        $card_desc = $active_info['card_desc'];
        $now_time = time();
        if($now_time>$active_end){
            return array("result"=>"error","msg"=>"该活动已结束，您可以直接下载天天果园app参与更多活动");
        }
        if($now_time<$active_begin){
            return array("result"=>"error","msg"=>"该活动还未开始，您可以下载天天果园app参与其他活动");
        }
        /*活动验证end*/


        $uid = '0';        
     	$this->db->select('id');
        $this->db->from('user');
        $this->db->where('mobile',$mobile);
        $user_result = $this->db->get()->row_array();
        if(!empty($user_result)){
            $uid = $user_result['id'];
        }

        /*优惠券设定start*/
        $share_time = date("Y-m-d");
        $share_card_number = $active_info['share_p_card_number'].$this->rand_card_number($active_info['share_p_card_number']);
        $share_card_data = array(
            'uid'=>$uid,
            'sendtime'=>date("Y-m-d",time()),
            'card_number'=>$share_card_number,
            'card_money'=>$active_info['share_card_money'],
            'product_id'=>$active_info['share_product_id'],
            'maketing'=>'0',
            'is_sent'=>'1',
            'restr_good'=>'0',
            'remarks'=>$active_info['share_remarks'],
            'time'=>$share_time,
            'to_date'=>$active_info['share_to_date'],
            'can_use_onemore_time'=>'false',
            'can_sales_more_times'=>'false',
            'card_discount'=>1,
            'order_money_limit'=>$active_info['order_money_limit'],
            'return_jf'=>$active_info['share_return_jf'],
            'direction'=>$card_desc
            // 'black_user_list'=>''
        );
        /*优惠券设定end*/

        /*活动记录start*/
        $this->db->from('wqbaby_active');
        $this->db->where(array('mobile' => $mobile,'active_tag'=>$active_tag));
        $result = $this->db->get()->row_array();
        if(empty($result)){
            $this->db->insert('card',$share_card_data);
            $active_data = array(
                'mobile'=>$mobile,
                'card_number'=>$share_card_number,
                'active_tag'=>$active_tag,
                'active_type'=>1
            );
			$this->db->insert('wqbaby_active',$active_data);
            $to_date = $share_to_date;            
        }else{
            $share_card_number = $result['card_number'];
            $this->db->select('card_money,to_date');
            $this->db->from('card');
            $this->db->where('card_number',$share_card_number);
            $card_result = $this->db->get()->row_array();
            $card_money = $card_result['card_money'];
            $to_date = $card_result['to_date'];
        }
        /*活动记录end*/
        return array("result"=>"succ","msg"=>"恭喜您获得".$active_info['share_card_money']."元优惠券，券已发至您的天天果园账户。请立即下载天天果园App或登录官网www.fruitday.com并使用该手机注册，即可使用。由于近期发现大量违规参与此游戏的行为，天天果园已将违规号码加入非法名单，非法名单中的账号将不予发货。如有疑问请及时联系客服电话4007200770，感谢您对天天果园的支持。");
    }

    function rand_card_number($p_card_number=''){
      $a   =  "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
      $a_array = explode(",",$a);
      for($i=1;$i<=10;$i++){
         $tname.=$a_array[rand(0,31)];
      }
      if($this->checkCardNum($p_card_number.$tname)){
        $tname = $this->rand_card_number($p_card_number);
      }      
      return $tname;
    }

    private function checkCardNum($card_number){
        $this->db->from('card');
        $this->db->where('card_number',$card_number);
        $query = $this->db->get();
        $num = $query->num_rows();
        if($num>0){
          return true;
        }else{
          return false;
        }
    }

    function giftCard($params){
        // return array("result"=>"error","msg"=>"该活动已结束，您可以下载天天果园app参与其他活动");
        /*常量配置start*/
        $active_info['active_tag'] = 'open_20_card_0319';
        $active_info['active_begin'] = 1414771200;//2014-11-01 00:00:00    
        $active_info['active_end'] = 1427817600;//2014-11-08 00:00:00
        
        $active_type = 'card';
        $card_amount = 0;
        $card_amount = 20;
        $order_money_limit = 200;
        $card_desc = '订单满200使用';
        $active_type = 'card';
        $active_limit = 10050;

        $this->db->from('wqbaby_active');
        $this->db->where(array('active_tag'=>$active_info['active_tag']));
        $num = $this->db->get()->num_rows();
        
        if($num>$active_limit){
            return array('result'=>'error','msg'=>'该活动已结束，您可以下载天天果园app参与其他活动');
        }

        if($active_type == 'card'){
            $active_info['share_to_date'] = date("Y-m-d",strtotime("+7day"));
            $active_info['share_card_money'] = $card_amount;
            $active_info['share_product_id'] = '0';
            $active_info['share_return_jf'] = 0;
            $active_info['share_remarks'] = "天天果园通用优惠券（个别商品除外）";
            $active_info['share_p_card_number'] = 'tg';
            $active_info['order_money_limit'] = $order_money_limit;
            $active_info['card_desc'] = $card_desc;

            /*常量配置end*/
            if(!isset($params['phone']) || empty($params['phone'])){
                return array('result'=>'error','msg'=>'手机号不能为空');
            }
            if($card_amount<=0){
                return array('result'=>'error','msg'=>'抵扣金额错误');
            }

            if(!is_mobile($params['phone'])){
                return array('result'=>'error','msg'=>'手机号码错误');
            }
            return $this->doSendCard($params['phone'],$card_amount,$active_info);
        }

        
    }

    function getMobileCard($mobile,$active_tag){
        $is_add = $this->db->from('game_mobile')->where(array(
            'mobile'=>$mobile,
            'active_tag'=>$active_tag
        ))->get()->row_array();
        if(!empty($is_add)){
            return array('code'=>300,'msg'=>'您已经参与过该活动了');
        }
        $data = array(
            'active_tag' => $active_tag,
            'mobile' => $mobile
        );
        $this->db->insert('game_mobile',$data);
        return array('code'=>200,'msg'=>'添加成功');
    }
    
    function getOrangeMobile($mobile,$active_tag){
        
        $this->db->from("user");
        $where = array("mobile" => $mobile);
        $this->db->where($where);
        $query = $this->db->get();
        $userInfo = $query->result_array();
        if (!empty($userInfo)) {
            $uid = $userInfo[0]['id'];
        }else{
            $uid = 0;
        }
        $is_active = $this->db->from('wqbaby_active')->where(array('mobile' => $mobile, 'active_tag' => $active_tag))->get()->row_array();
        if (empty($is_active)) {
            $share_remarks = "滴滴甜橙10元优惠券";
            if ($uid != 0) {
                $sql = "select count(*) as cnt from ttgy_card where uid='".$uid."' and remarks ='".$share_remarks."'";
                $cardCount = $this->db->query($sql)->row_array();
                if ($cardCount['cnt'] > 0) {
                        return array('code'=>300,'msg'=>'果园君掐指一算，您已经领过券了哦~.');
                }
            }
            $share_p_card_number = "orangedd1207";
            $cardNumber = $share_p_card_number . $this->rand_card_number($share_p_card_number);
            $date = date("Y-m-d", time());
            $channel = '';
            $cardData = array(
                'uid' => $uid,
                'sendtime' => $date,
                'card_number' => $cardNumber,
                'product_id' => 7285,
                'maketing' => '0', //天天到家优惠券
                'is_sent' => 1,
                'restr_good' => '1', //是否指定商品id  1为指定,需要给定上面的product_id。。0为不指定，product_id为空
                'time' =>$date,
                'to_date'=>$date,
                'can_use_onemore_time' => 'false',
                'can_sales_more_times' => 'false',
                'card_discount' => 1,
                'return_jf' => 0,
                'channel' => $channel,
                'card_money'=>10,
                'remarks'=>$share_remarks,
                'order_money_limit'=>0,
                'card_number'=>$cardNumber
            );
            $wqbabyData = array(
                    'mobile' => $mobile,
                    'card_number' => $cardNumber,
                    'active_tag' => $active_tag,
                    'link_tag' => '',
//                        'description' => ''
                    'card_money' => 10
            );

            $this->db->trans_begin();
            $this->db->insert('wqbaby_active', $wqbabyData);
            $this->db->insert('card', $cardData);
            if ($this->db->trans_status() === FALSE) {
                    $this->db->trans_rollback();
                    return array('code'=>300,'msg'=>'请重试');
            } else {
                $this->db->trans_commit();
                return array('code'=>200,'msg'=>'添加成功');
            }
        } else {
            return array('code'=>300,'msg'=>'您已经参与过该活动了');
        }
    }

    public function get_cardInfo_by_tag($card_tag) {
        if (empty($card_tag)) {
            return array();
        }
        $this->db->select('*');
        $this->db->from('mobile_card');
        if (is_array($card_tag)) {
            $this->db->where_in('card_tag', $card_tag);
            return $this->db->get()->result_array();
        } else {
            $this->db->where(array("card_tag" => $card_tag));
            return $this->db->get()->row_array();
        }
    }

    //批量插入优惠券
    //插入优惠券
    function addCard_batch($card_data,$active_data,$card_type_arr){
        if(!empty($card_data)){
            $this->db->insert_batch('card',$card_data);
        }
        if(!empty($active_data))
            $this->db->insert_batch('wqbaby_active',$active_data);

        if(!empty($card_type_arr))
            $this->db->insert_batch('card_type',$card_type_arr);
    }

      /**
       * 券卡打标
       * @param type $card_numbers
       * @param type $type
       * @param type $op_id      操作人id
       * @param type $tag     券卡设置表唯一标识，可为空
       * @param type $department       发券部门，券卡设置表里可读取
       * @return boolean
       * $card_number=array('card_number1'=>'type1','card_number2'=>'type2')可同时处理多张不同type的优惠券
       * $card_number=array('card_number1','card_number2')可同时处理多张相同type的优惠券
       */
      function addCardType($card_numbers,$type = '全场通用券',$op_id = 0,$tag= '',$department= ''){
          if(!is_array($card_numbers)) $card_numbers = array($card_numbers);
          if(empty($card_numbers)) return true;
          $insert_data = array();
          foreach ($card_numbers as $key => $value) {
              $data = array();
              if (is_numeric($key) && strlen($key) < 10) {
                  $data['card_number'] = $value;
                  $data['type'] = $type;
                  $data['op_id'] = $op_id;
                  $data['tag'] = $tag;
                  $data['department'] = $department;
              } else {
                  $data['card_number'] = $key;
                  $data['type'] = $value;
                  $data['op_id'] = $op_id;
                  $data['tag'] = $tag;
                  $data['department'] = $department;
              }
              $insert_data[] = $data;
          }
          $res = $this->db->insert_batch("card_type",$insert_data);
          if(!$res){
              return false;
          }
          return true;
      }

      // 现有的券卡类型   $card_type = array('商品专用券','签到通用券','OCJ优惠券','包邮券','猜谜通用券','单品组合券','果食通用券','会员满减券','拼图通用券','平安险通用券','全场通用券','生日礼通用券','天天到家优惠券','现金通用券','新客通用券','邀请好友专用券','应用宝通用券','用户回访券','用户推广券','全场通用券');
      // 发放部门    private $departments = array('KA中心','B2C运营部','O2O运营部','产品部','市场部','客服部');
      // tag码  取优惠券发放活动管理中的tag码


      //调用ttgy_mobile_card 配置的   $type 读表中 card_m_type 字段


      /*
     * ex.
     * 礼品统一操作  赠品有效期改造逻辑add
     */
      function send_gift_by_tag($tag,$uid){
          $gift_config = $this->get_gift_send_info($tag);
          $gift_data = $this->initGiftData($gift_config,$uid);
          return $this->send_gift($gift_data);
      }

      /*
       * 获取赠品配置信息
       */
      function get_gift_send_info($tag){
          $gift_config = $this->db->select('id,start,end,gift_start_time,gift_end_time,gift_valid_day')->from('gift_send')->where(array('tag' => $tag))->get()->row_array();
          return $gift_config;
      }

      /*
       * 根据赠品的配置信息初始化用户赠品的数组
       */
      function initGiftData($gift_config,$uid){
          if($gift_config['gift_valid_day'] && $gift_config['gift_valid_day']>0){
              $gift_start_time = date('Y-m-d');
              $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_config['gift_valid_day'])-1).' day'));
          }elseif($gift_config['gift_start_time'] && $gift_config['gift_end_time'] && $gift_config['gift_start_time'] != '0000-00-00' && $gift_config['gift_end_time'] != '0000-00-00'){
              $gift_start_time = $gift_config['gift_start_time'];
              $gift_end_time = $gift_config['gift_end_time'];
          }else{
              $gift_start_time = $gift_config['start'];
              $gift_end_time = $gift_config['end'];
          }
          $gift_data = array(
              'uid' => $uid,
              'active_id' => $gift_config['id'],
              'active_type' => '2',
              'has_rec' => '0',
              'start_time' => $gift_start_time,
              'end_time' => $gift_end_time
          );
          return $gift_data;
      }

      /*
       * 是否还有该未过期未使用的赠品
       */
      function has_unexpired_gifts($active_id,$uid){
          $date = date('Y-m-d');
          $rs = $this->db->from("user_gifts")->where(array("uid" => $uid, "active_id" => $active_id, "has_rec" => 0, "start_time <=" => $date, "end_time >=" => $date))->get()->row_array();
          return empty($rs)?false:true;
      }

      /*
       * 插入赠品
       */
      function send_gift($gift_data,$active_data=array()){
          if(empty($active_data))
            return $this->db->insert('user_gifts', $gift_data);
          else{
               $this->db->insert('user_gifts', $gift_data);
                $user_gift_id = $this->db->insert_id();
                $active_data['card_number'] = $user_gift_id;
                return $this->db->insert('wqbaby_active', $active_data);
          }
      }

      /*
       * 是否已经插入过
       */
      function is_send($mobile,$active_tag){
          $is_add = $this->db->from('game_mobile')->where(array(
              'mobile'=>$mobile,
              'active_tag'=>$active_tag
          ))->get()->row_array();
          if(!empty($is_add)){
              return false;
          }
          $data = array(
              'active_tag' => $active_tag,
              'mobile' => $mobile
          );
          $this->db->insert('game_mobile',$data);
          return true;
      }

      //通过mobile查询得到uid
      function get_uid_by_mobile($mobile){
          $user = $this->db->select('id')->from('user')->where('mobile',$mobile)->get()->row_array();
          return $user?$user['id']:'';
      }
}
