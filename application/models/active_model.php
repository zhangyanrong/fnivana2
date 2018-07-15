<?php
class Active_model extends MY_Model {


    function Active_model() {
        parent::__construct();
        // $this->load->library("session");
    }

    //获取会员等级限制的产品信息
    function getProductRankLimit(){
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            $this->load->library("memcached");
            $product_rank_limit = $this->memcached->get('product_rank_limit');
        }
        if(empty($product_rank_limit)){
            $this->db->from('product_rank_limit');
            $result = $this->db->get()->result_array();
            if(!empty($result)){
                $product_rank_limit = "";
                foreach($result as $v){
                    $product_rank_limit['pro2rank'][$v['product_id']] = explode(",",$v['user_rank']);
                    $product_rank_limit['product_ids'][] = $v['product_id'];
                }
                if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                    $this->memcached->set('product_rank_limit', $product_rank_limit, '60*60*24*30');
                }
            }
        }
        return $product_rank_limit;
    }

    function checkRankBuy($uid,$product_rank_limit,$product_id){
        if(in_array($product_id,$product_rank_limit['product_ids'])){
            if(!empty($uid)){
                $this->db->select('user_rank');
                $this->db->from('user');
                $this->db->where("id",$uid);
                $result = $this->db->get()->row_array();
                $user_rank = $result['user_rank'];
                if(!in_array($user_rank,$product_rank_limit['pro2rank'][$product_id])){
                    return "会员等级不达标，无法购买该产品";
                }
            }else{
                return "活动商品必须先登录才能购买！请先登录";
            }
        }
    }


    //判断当前团数否满了
    public function check_tuan_member($tuan_tag, $max_num=500){
        $this->db->select('*');
        $this->db->from('active_tuan_member');
        $this->db->where("tuan_tag",$tuan_tag);
        $result = $this->db->get()->num_rows();
        return $result<$max_num?true:false;
    }

    //判断当前用户是否参团, false:已参团
    public  function  check_tuan_is_join($uid, $tuan_tag){

        $this->db->select('*');
        $this->db->from('active_tuan_member');
        $this->db->where(array("tuan_tag"=>$tuan_tag, "uid"=>$uid));
        $result = $this->db->get()->num_rows();
        return $result>0?false:true;
    }
    //判断当前商品用户是否购买过
    public function check_order_is_exists($uid, $product_id=8324){
        $this->db->select('o.id,o.time');
        $this->db->from('order as o');
        $this->db->join('order_product as op','op.order_id=o.id');
        $this->db->where(array('op.product_id' => $product_id,'o.order_status' => 1, 'o.uid' => $uid, 'o.operation_id !=' => 5));
        $this->db->order_by('o.id', 'desc');
        return $this->db->get()->row_array();//团购商品只能购买一次
    }

    //根据订单name，通知用户参团
    public function join_tuan_by_order($order_name){
        $this->db->select('o.uid, op.product_id ');
        $this->db->from('order as o');
        $this->db->join('order_product as op','op.order_id=o.id');
        $this->db->where(array('o.order_name'=>$order_name,'o.order_status'=>1));
        $result = $this->db->get()->row_array();//团购商品只能单独购买
        $product_array= $this->get_o2o_tuan_product();
        if(!empty($result) && in_array($result['product_id'], $product_array)){
            $param['uid'] = $result['uid'];
            $param['product_id'] = $result['product_id'];
            $param['tuan_tag'] = $result['product_id'].date("md");
            $param['add_time'] = date("Y-m-d H:i:s");
            $param['is_send']  = 0;
            $this->db->insert('active_tuan_member',$param);
            return $this->tuan_full_update_channel($result['product_id'].date("md"));
        }else{
            return  false;
        }
    }

    //根据订单id，通知用户参团
    public function join_tuan_by_order_id($order_id){
        $this->db->select('o.uid, op.product_id ');
        $this->db->from('order as o');
        $this->db->join('order_product as op','op.order_id=o.id');
        $this->db->where(array('o.id'=>$order_id,'o.order_status'=>1));
        $result = $this->db->get()->row_array();//团购商品只能单独购买
        $product_array= $this->get_o2o_tuan_product();
        if(!empty($result) && in_array($result['product_id'],$product_array)){
            $param['uid'] = $result['uid'];
            $param['product_id'] = $result['product_id'];
            $param['tuan_tag'] = $result['product_id'].date("md");
            $param['add_time'] = date("Y-m-d H:i:s");
            $param['is_send']  = 0;
            $this->db->insert('active_tuan_member',$param);
            return $this->tuan_full_update_channel($result['product_id'].date("md"));
        }else{
            return  false;
        }
    }

    //参团满500人后满团
    public function tuan_full_update_channel($tuan_tag, $product_id, $channel = 99){
        if(!$this->check_tuan_member($tuan_tag)){
            $this->db->select('o.id');
            $this->db->from('order as o');
            $this->db->join('order_product as op','op.order_id=o.id');
            $this->db->where(array('o.channel'=>99,'o.pay_status'=>1, 'op.product_id'=>$product_id, 'o.time > ' => date("Y-m-d 00:00:00"),'o.operation_id != '=>5 ));
            $result = $this->db->get()->result_array();//团购商品只能单独购买
            if(empty($result)){
                return true;
            }
            $ids = array();
            foreach($result as $k=>$v){
                $ids[] = $v['id'];
            }
            $param['channel'] = 6;
            $this->db->where_in("id", $ids);
            return $this->db->update('order',$param);
        };
        return true;
    }
    public function get_o2o_tuan_product(){
        return array(8324, 8647, 8613, 8614, 8615, 8846);
    }

    //判断用户今天是否分享了
    public function check_user_share($uid, $time, $active_tag='o2o_tuan_0112'){
        $this->db->select('*');
        $this->db->from('share_num');
        $this->db->where(array("active_tag"=>$active_tag, "uid"=>$uid, 'time >= '=>$time));
        return $this->db->get()->num_rows();
    }

    //香梨分享  2016-10-08以后失效
    public function pear_share($order){
        $result = $this->db->from("order_product")->where( array("order_id" => $order['id'], "product_id" => 13301) )->get()->num_rows();
        if($result>0){
            $tuan_member = $this->db->from("tuan_member")->where(array('member_uid'=>$order['uid'], 'product_id'=>13301,  'is_bought'=>0))->order_by('id desc')->get()->row_array();
            if(empty($tuan_member)){
                $mobile = $this->get_mobile_by_uid($order['uid']);
                $tuan_member = $this->db->from("tuan_member")->where(array('appid'=>$mobile, 'product_id'=>13301,  'is_bought'=>0, 'time >'=>date('Y-m-d 00:00:00', strtotime('-1 day'))))->get()->row_array();
            }

            if(!empty($tuan_member)){
                //团成员购买总数
                $tuan_buy_members = $this->db->from("tuan_member")->where(array('tuan_id'=>$tuan_member['tuan_id'], 'product_id'=>13301,  'is_bought'=>1))->get()->result_array();
                $this->db->update('tuan_member', array("is_bought" => 1), array("id"=>$tuan_member['id']));
                //加上自己就是5个人， 5人都买送牛奶
                $msg_uids = array();

                if(count($tuan_buy_members)>=4){
                    array_push($msg_uids, strval($order['uid']));
                    foreach($tuan_buy_members as $k=>$v){
                        if($v['member_uid']==0){
                            $v['member_uid'] = $this->get_uid_by_mobile($v['appid']);//appid在这个活动存放的是未注册用户的手机号
                        }
                        array_push($msg_uids, strval($v['member_uid']));
                    }
                    $tuan_owner = $this->db->from("tuan")->where(array('id'=>$tuan_member['tuan_id']))->get()->row_array();
                    array_push($msg_uids, strval($tuan_owner['uid']));
                    $this->db->update('tuan', array("is_tuan" => 1), array("id"=>$tuan_owner['id']));//成团
                    foreach($msg_uids as $k=>$v){
                        if(!$v) continue;
                        $rs = $this->send_gift($v,3733);
                        if($rs){
                            $this -> app_send($v, '恭喜获得兰特鲜牛奶1瓶，满199元可随单免费带走~邀请更多好友，更有机会获得ipad min2，考验友情的时候到啦~');
                            $this -> sms_send($v, '朋友圈大红人，一呼百应，分分钟兰特鲜奶拿到手！邀请更多好友，更有机会获得ipad min2，考验友情的时候到啦~');
                        }
                    }
                }
            }
        }
        return true;
    }


    public function send_gift($uid, $gifts_id){
        $this->db->from("user_gifts");
        $this->db->where(array("uid" => $uid, "active_id" => $gifts_id));
        $result = $this->db->get()->row_array();
        if (empty($result)) {
            $gift_send = $this->db->select('*')->from('gift_send')->where('id', $gifts_id)->get()->row_array();
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
            $user_gift_data = array(
                'uid' => $uid,
                'active_id' => $gifts_id,
                'active_type' => '2',
                'has_rec' => '0',
                'start_time'=>$gift_start_time,
                'end_time'=>$gift_end_time,
            );
            $this->db->insert('user_gifts', $user_gift_data);
            return TRUE;
        }else{
            return FALSE;
        }
    }

    /*
     * @desc 发送app推送
     * */
    public function app_send($uid, $sms_content){
        //调用通知start
        $this->load->library("notifyv1");
        $params = array(
            'uid' => $uid,
            'title' => '天天果园通知',
            'message' => $sms_content,
            "tabType"=>     "UserCenter",
            "type"=>         "6"
        );
        return  $this->notifyv1->send('app', 'send', $params);
    }

    /*
     * @desc 发送短信
     * */
    public function sms_send($uid, $sms_content){
        $mobile = $this->get_mobile_by_uid($uid);
        if(!$mobile){
            return false;
        }
        $this->load->library("notifyv1");
        $params = array(
            'mobile' => $mobile,
            'message' => $sms_content,
        );
        return  $this->notifyv1->send('sms', 'send', $params);
    }

    public function get_mobile_by_uid($uid){
        $this->db->select("mobile");
        $this->db->from("user");
        $this->db->where(array("id" => $uid));
        $result = $this->db->get()->row_array();
        return $result['mobile'];
    }
    public function get_uid_by_mobile($mobile){
        $this->db->select("id");
        $this->db->from("user");
        $this->db->where(array("mobile" => $mobile));
        $result = $this->db->get()->row_array();
        return $result['id'];
    }

    /*
     * @desc 分享送赠品
     * @
     * */
    public function share_send_gifts($uid, $activeId){
        $config = array(
            383 => array(
                '20160927'=>array(3750,3751),//0 新客，1老客
                '20160928'=>array(3934,3935),
                '20160929'=>array(3936,3937),
                '20160930'=>array(3938,3939),
                '20161001'=>array(3940,3941),
                '20161002'=>array(3942,3943),
                '20161003'=>array(3944,3945),
                '20161004'=>array(3946,3947),
                '20161005'=>array(3948,3949),
                '20161006'=>array(3950,3951),
                '20161007'=>array(3952,3953),
                '20161008'=>array(3954,3955),
                '20161009'=>array(3956,3957)
            )
        );
        $date = date("Ymd");
        $yesterdate = date("Ymd", strtotime('-1 day'));
        $gifts_yestday_id = $config[$activeId][$yesterdate];
        //昨天的赠品有没有使用掉
        if($gifts_yestday_id && $this->check_user_gifts_arr($uid, $gifts_yestday_id)){
            return  array('code' => '200', 'msg' =>"果园君掐指一算，您的账户中已有美国有籽红提1斤啦，快去下单带走吧！");
        }
        //判断今天有没有赠品
        $config_tmp = $config[$activeId][$date]?$config[$activeId][$date]:array();
        if(empty($config_tmp)){
            return  array('code' => '200', 'msg' =>"不在活动期间");
        }
        if($this->get_order_count($uid)>0){
            $gifts_id = $config_tmp[1];
        }else{
            $gifts_id = $config_tmp[0];
        }
        //给用户发送今天的赠品,送完就送
        $send_gift = $this->sendUserGifts($uid, $gifts_id, 1);
        if(!$send_gift){
            return  array('code' => '200', 'msg' =>"果园君掐指一算，您的账户中已有美国有籽红提1斤啦，快去下单带走吧！！");
        }
        return  array('code' => '200', 'msg' =>"领取成功，获得美国有籽红提1斤，么么哒");
    }

    /*检查用户赠品*/
    public function check_user_gifts_arr($uid, $gifts_id_arr){
        if(!is_array($gifts_id_arr)){
            return $this->check_user_gifts($uid, $gifts_id_arr);
        }else{
            $this->db->from("user_gifts");
            $this->db->where(array("uid" => $uid, 'has_rec' => 0));
            $this->db->where_in('active_id' , $gifts_id_arr);
            return $this->db->get()->row_array();
        }
    }

    /*
     *  检查赠品
     * */
    public function check_user_gifts($uid, $gifts_id){
        //判断用户是否有未使用的赠品
        $this->db->from("user_gifts");
        $this->db->where(array("uid" => $uid, "active_id" => $gifts_id, 'has_rec' => 0));
        return $this->db->get()->row_array();
    }

    /*
     * @desc 判断用户购买的次数
     * @param $uid int 用户id
     * */
    public  function get_order_count($uid){
        $this->db->from('order');
        $this->db->where(array('uid' => $uid, 'operation_id !=' => 5, 'pay_status' => 1, 'order_status' => 1));
        $this->db->order_by('id desc');
        return  $this->db->get()->num_rows() ;
    }

    /*
     *  赠送赠品
     * */
    public function sendUserGifts($uid, $gifts_id, $is_null=''){
        $where = array("uid" => $uid, "active_id" => $gifts_id);
        if($is_null){
            $where['has_rec'] = 0;
        }
        $this->db->from("user_gifts");
        $this->db->where($where);
        $result = $this->db->get()->row_array();
        if (empty($result)) {
            $gift_send = $this->db->select('*')->from('gift_send')->where('id', $gifts_id)->get()->row_array();
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
            $user_gift_data = array(
                'uid' => $uid,
                'active_id' => $gifts_id,
                'active_type' => '2',
                'has_rec' => '0',
                'start_time'=>$gift_start_time,
                'end_time'=>$gift_end_time,
            );
            return $this->db->insert('user_gifts', $user_gift_data);
        }else{
            return FALSE;
        }
    }

    /*
     * @desc  分享团购系列
     * */

    /*
     * @desc 分享送赠品
     * @
     * */
    public function share_send_gifts_tuan($uid, $tag, $product_name='美国有籽红提1斤'){
        $config = array(
            '20160928youzi' => array(
                '20161008'=>array(3889,3890),//0 新客，1老客
                '20161009'=>array(3891,3892),
                '20161010'=>array(3893,3894),
                '20161011'=>array(3895,3896),
                '20161012'=>array(3897,3898),
                '20161013'=>array(3899,3900)
            ),
            '20161011banana' => array(
                '20161012'=>array(3999,4017),
                '20161013'=>array(4000,4018),
                '20161014'=>array(4001,4019),
                '20161015'=>array(4002,4020),
                '20161016'=>array(4003,4021),
                '20161017'=>array(4004,4022),
                '20161018'=>array(4005,4023),
                '20161019'=>array(4006,4024),
                '20161020'=>array(4007,4025),
                '20161021'=>array(4008,4026),
                '20161022'=>array(4009,4027),
                '20161023'=>array(4010,4028),
                '20161024'=>array(4011,4029),
                '20161025'=>array(4012,4030)
            )
        );
        $date = date("Ymd");
        $yesterdate = date("Ymd", strtotime('-1 day'));
        $gifts_yestday_id = $config[$tag][$yesterdate];
        //昨天的赠品有没有使用掉
        if($gifts_yestday_id && ($this->check_gift_used($gifts_yestday_id, date("Y-m-d H:i:s"))>0) && $this->check_user_gifts_arr($uid, $gifts_yestday_id)){
            return  array('code' => '200', 'msg' =>"果园君掐指一算，您的账户中已有".$product_name."啦，快去下单带走吧！");
        }
        //判断今天有没有赠品
        $config_tmp = $config[$tag][$date]?$config[$tag][$date]:array();
        if(empty($config_tmp)){
            return  array('code' => '200', 'msg' =>"不在活动期间");
        }
        if($this->get_order_count($uid)>0){
            $gifts_id = $config_tmp[1];
        }else{
            $gifts_id = $config_tmp[0];
        }
        //给用户发送今天的赠品,送完就送
        $send_gift = $this->sendUserGifts($uid, $gifts_id, 1);
        if(!$send_gift){
            return  array('code' => '200', 'msg' =>"果园君掐指一算，您的账户中已有".$product_name."啦，快去下单带走吧！！");
        }
        return  array('code' => '200', 'msg' =>"领取成功，获得".$product_name."，么么哒");
    }


    //判断赠品是否有效
    public function check_gift_used($id, $time){
        $this->db->from('gift_send');
        if($time){
            $this->db->where(array('end >' => $time));
        }
        if(is_array($id)){
            $this->db->where_in('id', $id);
            return $this->db->get()->num_rows();
        }else{
            $this->db->where('id', $id);
            return $this->db->get()->num_rows();
        }
    }


    //分享发赠品团
    public function share_gift_tuan($order, $product_id='13559', $product_id_arr=array(13559, 13683), $gift_id=3924, $num=2){
        $result = $this->db->from("order_product")->where( array("order_id" => $order['id']) )->where_in("product_id", $product_id_arr)->get()->num_rows();
        if($result>0){
            $tuan_member = $this->db->from("tuan_member")->where(array('member_uid'=>$order['uid'], 'product_id'=>$product_id,  'is_bought'=>0))->order_by('id desc')->get()->row_array();
            if(empty($tuan_member)){
                $mobile = $this->get_mobile_by_uid($order['uid']);
                $tuan_member = $this->db->from("tuan_member")->where(array('mobile'=>$mobile, 'product_id'=>$product_id,  'is_bought'=>0))->order_by('id desc')->get()->row_array();
            }

            if(!empty($tuan_member)){
                //团成员购买总数
                $tuan_buy_members = $this->db->from("tuan_member")->where(array('tuan_id'=>$tuan_member['tuan_id'], 'product_id'=>$product_id,  'is_bought'=>1))->get()->result_array();
                $this->db->update('tuan_member', array("is_bought" => 1), array("id"=>$tuan_member['id']));
                //加上自己就是5个人， 5人都买送牛奶
                $msg_uids = array();

                if(count($tuan_buy_members)>=$num){
                    array_push($msg_uids, strval($order['uid']));
                    foreach($tuan_buy_members as $k=>$v){
                        if($v['member_uid']==0){
                            $v['member_uid'] = $this->get_uid_by_mobile($v['mobile']);//未注册用户的手机号
                        }
                        array_push($msg_uids, strval($v['member_uid']));
                    }
                    $tuan_owner = $this->db->from("tuan")->where(array('id'=>$tuan_member['tuan_id']))->get()->row_array();
                    array_push($msg_uids, strval($tuan_owner['uid']));
                    $this->db->update('tuan', array("is_tuan" => 1), array("id"=>$tuan_owner['id']));//成团
                    foreach($msg_uids as $k=>$v){
                        if(!$v) continue;
                        $rs = $this->send_gift($v, $gift_id);
                        if($rs){
                            $this -> app_send($v, '本宝宝一呼百应！恭喜获得兰特鲜牛奶1L，快去下单带走吧~');
                            $this -> sms_send($v, '恭喜您获得兰特鲜牛奶1L！可在“我的赠品”中查看，还等什么，快去下单带走吧~');
                        }
                    }
                }
            }
        }
        return true;
    }
    
    //满500参团
    public function five_gift_tuan($order){
//        $order['id'] = 1318066464;
        if(empty($order['id'])){
           return true; 
        }
        $orderInfo = $this->db->query("SELECT id,money,pay_parent_id FROM (`ttgy_order`) WHERE `id` = ".$order['id'])->row_array();
        if(in_array($orderInfo['pay_parent_id'],array(5,6))){
            return true; 
        }
        $uid = $order['uid'];
        $tuanH = $this->db->query("SELECT id,order_money,all_money,link_tag,status,count(*) as cnt FROM (`ttgy_fc_tuan_head`) WHERE `uid` = ".$uid)->row_array();
        $tuanM = $this->db->query("SELECT id,hid,order_money,count(*) as cnt FROM (`ttgy_fc_tuan_members`) WHERE `uid` = ".$uid)->row_array();
//        print_r($tuanM);exit;
        if($tuanH['cnt']>0||$tuanM['cnt']>0){
            if($tuanH['cnt']>0){//团长下单money
                if($tuanH['status']!=1){
                    return true;
                }
                //统计money  当前的团长的总money  以及参团的团员的money
                $HMoney = $tuanH['order_money']+$orderInfo['money'];
                $MNO = $this->db->query("SELECT count(*) as cnt,sum(order_money) as money FROM (`ttgy_fc_tuan_members`) WHERE `hid` = ".$tuanH['id'])->row_array();
                $MMoney = 0;
                if($MNO['cnt']>0){
                    $MMoney = $MNO['money'];
                }
                $allMoney = $HMoney + $MMoney;
                $totalM = $tuanH['all_money']+$orderInfo['money'];
                if($allMoney>=500){  //满500 送赠品
                    $uList = $this->db->query("SELECT uid,mobile FROM (`ttgy_fc_tuan_members`) WHERE `hid` = ".$tuanH['id'])->result_array(); 
                    $old_send_gift = $this->db->query("SELECT id,start,end,gift_start_time,gift_end_time,gift_valid_day FROM (`ttgy_gift_send`) WHERE id =4347 ")->row_array();
                    $new_send_gift = $this->db->query("SELECT id,start,end,gift_start_time,gift_end_time,gift_valid_day FROM (`ttgy_gift_send`) WHERE id =4348 ")->row_array();
                    if(empty($old_send_gift)){
                        return true; 
                    }
                    if(empty($new_send_gift)){
                        return true; 
                    }
                    $this->db->trans_begin();
                    //团长赠品发放
                    $HGiftData = array(
                        'uid' => $uid,
                        'active_id' => 4347,
                        'active_type' => '2',
                        'has_rec' => '0',
                        'start_time' => $old_send_gift['gift_start_time'],
                        'end_time' => $old_send_gift['gift_end_time']
                    );
                    $this->db->insert('user_gifts', $HGiftData);
                    //团员的赠品
                    $giftArr = array();
                    $wqArr = array();
                    foreach($uList as $k=>$v){ 
                        //是否新客
                        if($v['uid']!=0){ 
                            $is_new = $this->is_new($v['uid']);
                            if(!is_new){ //老客 4347 activeID
                                $giftArr[$k]['uid'] = $v['uid'];
                                $giftArr[$k]['active_id'] = 4347;
                                $giftArr[$k]['active_type'] = 2;
                                $giftArr[$k]['has_rec'] = 0;
                                $giftArr[$k]['start_time'] = $old_send_gift['gift_start_time'];
                                $giftArr[$k]['end_time'] = $old_send_gift['gift_end_time'];
                            }else{ //新客 已注册未下单 4348 activeID 
                                $giftArr[$k]['uid'] = $v['uid'];
                                $giftArr[$k]['active_id'] = 4348;
                                $giftArr[$k]['active_type'] = 2;
                                $giftArr[$k]['has_rec'] = 0;
                                $giftArr[$k]['start_time'] = $new_send_gift['gift_start_time'];
                                $giftArr[$k]['end_time'] = $new_send_gift['gift_end_time'];
                            }
                            $this->db->insert('user_gifts', $giftArr[$k]);
//                            $this -> app_send($uid, '本宝宝一呼百应！恭喜获得香梨一份，快去下单带走吧~');
                            $this -> sms_send($uid, '组团购物棒棒哒！恭喜获得新疆库尔勒香梨2斤！可在“我的赠品”中查看，还等什么，快去下单带走吧~');      
                        }else{ //新客 未注册
                            $giftArr[$k]['uid'] = 0;
                            $giftArr[$k]['active_id'] = 4348;
                            $giftArr[$k]['active_type'] = 2;
                            $giftArr[$k]['has_rec'] = 0;
                            $giftArr[$k]['start_time'] = $new_send_gift['gift_start_time'];
                            $giftArr[$k]['end_time'] = $new_send_gift['gift_end_time'];
                            
                            $wqArr[$k]['mobile'] = $v['mobile'];
                            $wqArr[$k]['active_tag'] = date("Ymd").'组团赠品';
                            $wqArr[$k]['link_tag'] = $tuanH['link_tag'];
                            $wqArr[$k]['active_type'] = 6;
                            $wqArr[$k]['card_money'] = 0;
                            $wqArr[$k]['description'] = date("Ymd").'组团未注册用户赠品';
                            
                            
                            $this->db->insert('user_gifts', $giftArr[$k]);
                            $user_gift_id = $this->db->insert_id();
                            $wqArr[$k]['card_number'] = $user_gift_id;
                            $this->db->insert('wqbaby_active', $wqArr[$k]);
                            $this -> sms_mobile_send($v['mobile'], '组团购物棒棒哒！恭喜获得新疆库尔勒香梨2斤！可在“我的赠品”中查看，还等什么，快去下单带走吧~');
                        }
                    }
                    
                    $this->db->update('fc_tuan_head', array("status" => 3,"order_money" => $HMoney,'all_money'=>$totalM), array("id"=>$tuanH['id'])); //成团 状态变为3
                    if ($this->db->trans_status() === FALSE) {
                            $this->db->trans_rollback();
                            return array("code"=>200,"result" => "error", "msg" => "请重试");
//                            $return_result = array("result" => "error", "msg" => "请重试");
//                            echo json_encode($return_result);
//                            exit;
                    } else {
                            $this->db->trans_commit();
                            return array("code"=>200,"result" => "error", "msg" => "累积成功");
//                            return true;
//                            return array("code"=>200,"result" => "succ", "msg" =>'参团成功');
//                            echo json_encode($return_result);
//                            exit;
                    }
                    
                }else{//不满500   资金累积
                    $this->db->trans_begin();
                    $this->db->update('fc_tuan_head', array("order_money" => $HMoney,'all_money'=>$totalM), array("id"=>$tuanH['id'])); //
                    if ($this->db->trans_status() === FALSE) {
                            $this->db->trans_rollback();
                             return array("code"=>200,"result" => "error", "msg" => "请重试");
//                            $return_result = array("result" => "error", "msg" => "请重试");
//                            echo json_encode($return_result);
//                            exit;
                    } else {
                            $this->db->trans_commit();
//                            return true ;
                            return array("code"=>200,"result" => "error", "msg" => "累积成功");
                    }
                }
            }else if($tuanM['cnt']>0){ //团员
//                echo 1;exit;
                $MNO = $this->db->query("SELECT count(*) as cnt,sum(order_money) as money FROM (`ttgy_fc_tuan_members`) WHERE `hid` = ".$tuanM['hid'])->row_array();
                $HNO = $this->db->query("SELECT order_money,uid,status,all_money FROM (`ttgy_fc_tuan_head`) WHERE `id` = ".$tuanM['hid'])->row_array();
//               print_r($HNO);exit;
                if($HNO['status']!=1){
//                     return array('code' => '300', 'msg' => '该团已非开启状态哦~');
                    return true;
                }
                $allMoney = $MNO['money']+$HNO['order_money']+$orderInfo['money'];
                $totalM = $HNO['all_money']+$orderInfo['money'];
//                echo $allMoney;exit;
                if($allMoney>=500){ //成团
//                    echo 1;exit;
                    $this->db->trans_begin();
                    $old_send_gift = $this->db->query("SELECT id,start,end,gift_start_time,gift_end_time,gift_valid_day FROM (`ttgy_gift_send`) WHERE id =4347 ")->row_array();
                    $new_send_gift = $this->db->query("SELECT id,start,end,gift_start_time,gift_end_time,gift_valid_day FROM (`ttgy_gift_send`) WHERE id =4348 ")->row_array();
                    //团长赠品发放
                    $HGiftData = array(
                        'uid'=>$HNO['uid'],
                        'active_id'=>4347,
                        'active_type'=>2,
                        'has_rec'=>0,
                        'start_time' => $old_send_gift['gift_start_time'],
                        'end_time' => $old_send_gift['gift_end_time']
                    );
                    $this->db->insert('user_gifts', $HGiftData);
                    
                    $uList = $this->db->query("SELECT uid,mobile FROM (`ttgy_fc_tuan_members`) WHERE `hid` = ".$tuanM['hid'])->result_array();
                    $giftArr = array();
                    $wqArr = array();
                    foreach($uList as $k=>$v){ 
                        //是否新客
                        if($v['uid']!=0){ 
                            $is_new = $this->is_new($v['uid']);
                            if(!is_new){ //老客 4347 activeID
                                $giftArr[$k]['uid'] = $v['uid'];
                                $giftArr[$k]['active_id'] = 4347;
                                $giftArr[$k]['active_type'] = 2;
                                $giftArr[$k]['has_rec'] = 0;
                                $giftArr[$k]['start_time'] = $old_send_gift['gift_start_time'];
                                $giftArr[$k]['end_time'] = $old_send_gift['gift_end_time'];
                            }else{ //新客 已注册未下单 4348 activeID 
                                $giftArr[$k]['uid'] = $v['uid'];
                                $giftArr[$k]['active_id'] = 4348;
                                $giftArr[$k]['active_type'] = 2;
                                $giftArr[$k]['has_rec'] = 0;
                                $giftArr[$k]['start_time'] = $new_send_gift['gift_start_time'];
                                $giftArr[$k]['end_time'] = $new_send_gift['gift_end_time'];
                            }
                            $this->db->insert('user_gifts', $giftArr[$k]);
//                            $this -> app_send($uid, '本宝宝一呼百应！恭喜获得香梨一份，快去下单带走吧~');
                            $this -> sms_send($uid, '组团购物棒棒哒！恭喜获得新疆库尔勒香梨2斤！可在“我的赠品”中查看，还等什么，快去下单带走吧~');  
                            
                        }else{ //新客 未注册
                            $giftArr[$k]['uid'] = 0;
                            $giftArr[$k]['active_id'] = 4348;
                            $giftArr[$k]['active_type'] = 2;
                            $giftArr[$k]['has_rec'] = 0;
                            $giftArr[$k]['start_time'] = $new_send_gift['gift_start_time'];
                            $giftArr[$k]['end_time'] = $new_send_gift['gift_end_time'];
                            
                            $wqArr[$k]['mobile'] = $v['mobile'];
                            $wqArr[$k]['active_tag'] = date("Ymd").'组团赠品';
                            $wqArr[$k]['link_tag'] = $tuanH['link_tag'];
                            $wqArr[$k]['active_type'] = 6;
                            $wqArr[$k]['card_money'] = 0;
                            $wqArr[$k]['description'] = date("Ymd").'组团未注册用户赠品';
                            
                            
                            $this->db->insert('user_gifts', $giftArr[$k]);
                            $user_gift_id = $this->db->insert_id();
                            $wqArr[$k]['card_number'] = $user_gift_id;
                            $this->db->insert('wqbaby_active', $wqArr[$k]);
                            $this -> sms_mobile_send($uid, '组团购物棒棒哒！恭喜获得新疆库尔勒香梨2斤！可在“我的赠品”中查看，还等什么，快去下单带走吧~');  
                        }
                    }
                    $this->db->update('fc_tuan_head', array("status" => 3,'all_money'=>$totalM), array("id"=>$tuanM['hid'])); //成团 状态变为3
                    $money = $tuanM['order_money']+$orderInfo['money'];
                    $this->db->update('fc_tuan_members', array("order_money" => $money), array("id"=>$tuanM['id']));
                    if ($this->db->trans_status() === FALSE) {
                            $this->db->trans_rollback();
                            return array("code"=>200,"result" => "error", "msg" => "请重试");
//                            $return_result = array("result" => "error", "msg" => "请重试");
//                            echo json_encode($return_result);
//                            exit;
                    } else {
                            $this->db->trans_commit();
                            //短信发送
                            return true;
                    }
                }else{ //未成团 money叠加 
                    $this->db->trans_begin();
                    $this->db->update('fc_tuan_head', array('all_money'=>$totalM), array("id"=>$tuanM['hid']));
                    $money = $tuanM['order_money']+$orderInfo['money'];
                    $this->db->update('fc_tuan_members', array("order_money" => $money), array("id"=>$tuanM['id'])); //
                    if ($this->db->trans_status() === FALSE) {
                            $this->db->trans_rollback();
                            return array("code"=>200, "msg" => "请重试");
//                            return array("result" => "error", "msg" => "请重试");
//                            echo json_encode($return_result);
//                            exit;
                    } else {
                            $this->db->trans_commit();
//                            return true;
                    }
                }
            }
        }
        return true;
        
    }
    
    public function is_new($uid) {
            $this->db->from('order');
            $this->db->where(array('order.uid' => $uid, 'order.order_status' => '1'));
            $this->db->where_in('order.operation_id',array(3,6,9));
            return ($this->db->get()->num_rows() == 0) ? true : false;
    }
    
    /*
     * @desc 发送短信
     * */
    public function sms_mobile_send($mobile, $sms_content){
//        $mobile = $this->get_mobile_by_uid($uid);
        if(!$mobile){
            return false;
        }
        $this->load->library("notifyv1");
        $params = array(
            'mobile' => $mobile,
            'message' => $sms_content,
        );
        return  $this->notifyv1->send('sms', 'send', $params);
    }
    

}