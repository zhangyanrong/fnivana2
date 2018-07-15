<?php
namespace bll;
/* create by tangcw */
class Userinvite {

	//邀请一个好友1000分
	private $invite_per_score = 1000;

	function __construct($params = array()) {
	    $this->ci = &get_instance();
		$this->ci->load->model('user_model');
		$this->ci->load->model('user_invite_model');
		$this->ci->load->model('user_invite2_model');
		$this->ci->load->model('user_invite_prune_model');
		$this->ci->load->helper('public');
	}

	/*
	 * 获取邀请的积分
	 */

	public function getJF($params) {
		$required_fields = array(
			'uid' => array('required' => array('code' => '300', 'msg' => '用户id不能为空')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		$where = array(
			"id" => $params['uid'],
		);
	    /*验证用户是否存在*/
		$users = $this->ci->user_model->selectUsers("id", $where);
		if (empty($users)) {
			return array('code' => '300', 'msg' => '该用户不存在');
		}
		$uid = $users[0]['id'];
		
		$info = $this->ci->user_invite_model->countInvite($uid);
		//获取最近10位邀请的好友
		$where = array(
            "invite_by" => $uid,
        );
		$myInviteList = $this->ci->user_invite_model->getList("mobile_phone,is_finish_order",$where,'',array('key'=>'ctime','value'=>'desc'),array('curr_page'=>0,'page_size'=>10));
		foreach($myInviteList as $i=>$v){
		    if (mb_strlen($myInviteList[$i]['mobile_phone']) == 11){
		        $myInviteList[$i]['mobile_phone'] = substr_replace($myInviteList[$i]['mobile_phone'],'****',3,4);
		    }
		}
		//获取积分榜的前十名
		/* $rankList = $this->ci->user_invite_model->rankList(10);
		foreach ($rankList as $i=>$eachRank){
		    if ($eachRank['mobile']){
		        $rankList[$i]['mobile'] = substr_replace($rankList[$i]['mobile'],'****',3,4);
		    }
		} */
		$rankList = '';
		
		$count_invite = $info[0]['count_invite'] ? $info[0]['count_invite'] : 0;
		//20160125改成每2个人=一份樱桃 上限10
		//20160126改成上限5
		$jf_number = floor($count_invite/2);
		return array('code' => '200', 'uid' => $uid, 'jf_number' => $jf_number, 'myInviteList'=>$myInviteList, 'rankList'=>$rankList);


    }
    
    /*
     * 获取邀请的积分
     */
    
    public function getJF2($params) {
        $required_fields = array(
            'uid' => array('required' => array('code' => '300', 'msg' => '用户id不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $where = array(
            "id" => $params['uid'],
        );
        /*验证用户是否存在*/
        $users = $this->ci->user_model->selectUsers("id", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '该用户不存在');
        }
        $uid = $users[0]['id'];
    
        $info = $this->ci->user_invite2_model->countInvite($uid);
        //获取最近10位邀请的好友
        $myInviteList = $this->ci->user_invite_model->getInvite2($uid);
        foreach($myInviteList as $i=>$v){
            if (mb_strlen($myInviteList[$i]['mobile_phone']) == 11){
                $myInviteList[$i]['mobile_phone'] = substr_replace($myInviteList[$i]['mobile_phone'],'****',3,4);
            }
        }
        
        $rankList = $this->ci->user_invite_model->rankList(10);
        foreach ($rankList as $i=>$eachRank){
            if ($eachRank['mobile']){
                $rankList[$i]['mobile'] = substr_replace($rankList[$i]['mobile'],'****',3,4);
            }
        }
        //当前邀请成功人数
        $count_invite = isset($info[0]['invite_num']) ? $info[0]['invite_num'] : 0;
        $weekEnd = date('Y/m/d 23:59:59',(time()+(7-(date('w')==0?7:date('w')))*24*3600));
        return array('code' => '200', 'uid' => $uid, 'count_invite' => $count_invite, 'rankList'=>$rankList, 'myInviteList'=>$myInviteList, 'weekEnd'=>$weekEnd);
    
    
    }
    
    /*
     * 获取邀请的积分
     */
    
    public function getJFPrune($params) {
        $required_fields = array(
            'uid' => array('required' => array('code' => '300', 'msg' => '用户id不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $where = array(
            "id" => $params['uid'],
        );
        /*验证用户是否存在*/
        $users = $this->ci->user_model->selectUsers("id", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '该用户不存在');
        }
        $uid = $users[0]['id'];
        
        //邀请成功
        $info = $this->ci->user_invite_prune_model->countInvite($uid);
        //邀请总数
        $info2 = $this->ci->user_invite_prune_model->countInvite2($uid);
        $info_all = $this->ci->user_invite_prune_model->countAll();
        //获取最近10位邀请的好友
        $myInviteList = $this->ci->user_invite_prune_model->getInvite($uid);
        foreach($myInviteList as $i=>$v){
            if (mb_strlen($myInviteList[$i]['mobile_phone']) == 11){
                $myInviteList[$i]['mobile_phone'] = substr_replace($myInviteList[$i]['mobile_phone'],'****',3,4);
                $myInviteList[$i]['finish_order_time_date'] = date('m月d日 H:i:s',$myInviteList[$i]['finish_order_time']);
                $myInviteList[$i]['ctime_date'] = date('m月d日 H:i:s',$myInviteList[$i]['ctime']);
            }
        }
    
        $rankList = $this->ci->user_invite_prune_model->rankList(10);
        foreach ($rankList as $i=>$eachRank){
            if ($eachRank['mobile']){
                $rankList[$i]['mobile'] = substr_replace($rankList[$i]['mobile'],'****',3,4);
            }
        }
        //当前邀请成功人数
        $count_invite = isset($info[0]['count_invite']) ? $info[0]['count_invite'] : 0;
        $count_invite2 = isset($info2[0]['count_invite']) ? $info2[0]['count_invite'] : 0;
        $count_all = isset($info_all[0]['count_invite']) ? $info_all[0]['count_invite'] : 0;
        return array('code' => '200', 'uid' => $uid, 'count_invite' => $count_invite, 'count_invite2' => $count_invite2, 'count_all' => $count_all, 'rankList'=>$rankList, 'myInviteList'=>$myInviteList);
    
    
    }
    
    /*
     * 获取邀请的好友列表
     */
    public function getInvite($params){
        $required_fields = array(
            'uid' => array('required' => array('code' => '300', 'msg' => '用户id不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $where = array(
            "id" => $params['uid'],
        );
        /*验证用户是否存在*/
        $users = $this->ci->user_model->selectUsers("id", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '该用户不存在');
        }
        $uid = $users[0]['id'];
        
        $info = $this->ci->user_invite_model->getInvite($uid);
        foreach ($info as $k=>$eachInfo){
            if (empty($eachInfo['user_head'])){
                $info[$k]['user_head'] = PIC_URL . "up_images/default_userpic.png";
            } else {
                $user_head = unserialize($eachInfo['user_head']);
                $info[$k]['user_head'] = PIC_URL_TMP.$user_head['middle'];
            }
        }
        return array('code' => '200', 'invite_list' => $info);
    }
    
    public function getInvite2($params){
        $required_fields = array(
            'uid' => array('required' => array('code' => '300', 'msg' => '用户id不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $where = array(
            "id" => $params['uid'],
        );
        /*验证用户是否存在*/
        $users = $this->ci->user_model->selectUsers("id", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '该用户不存在');
        }
        $uid = $users[0]['id'];
    
        $info = $this->ci->user_invite_model->getInvite2($uid);
        foreach ($info as $k=>$eachInfo){
            if (empty($eachInfo['user_head'])){
                $info[$k]['user_head'] = PIC_URL . "up_images/default_userpic.png";
            } else {
                $user_head = unserialize($eachInfo['user_head']);
                $info[$k]['user_head'] = PIC_URL_TMP.$user_head['middle'];
            }
        }
        return array('code' => '200', 'invite_list' => $info);
    }
    
    public function getInviteById($params){
        $required_fields = array(
            'invite_id' => array('required' => array('code' => '300', 'msg' => '邀请id不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $where = array(
            "id" => $params['invite_id'],
        );
        $invite = $this->ci->user_invite_model->getList("weixin_nickname,weixin_headimg", $where);
        $invite = $invite ? $invite[0] : '';
        return array('code' => '200', 'invite' => $invite);
    }
    
    public function getGift($params){
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '手机号不能为空')),
            'invite_code' => array('required' => array('code' => '300', 'msg' => '邀请号码不能为空'))
            
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $mobile = $params['mobile'];
        $invite_code = $params['invite_code'];
        $invite_code = base64_decode($invite_code);
        $wx_name = $params['wx_name'] ? $params['wx_name'] : '';
        $wx_pic = $params['wx_pic'] ? $params['wx_pic'] : '';
        if (strstr($invite_code,"_") === false){
            return array('code' => '300', 'msg' => '该邀请参数有误！请重新打开页面！');
        }
        $arr_invite_code = explode("_",$invite_code);
        $invite_by = $arr_invite_code[1];
        $where = array(
            "id" => $invite_by
        );
         
        /*验证邀请人用户是否存在*/
        $users = $this->ci->user_model->selectUsers("id,mobile", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '该邀请人不存在！');
        }
        $uid = $users[0]['id'];
        $invite_mobile = $users[0]['mobile'];
        if ($invite_mobile == $mobile){
            return array('code' => '300', 'msg' => '不能邀请自己哦！');
        }
        /* 验证是否已被邀请过 */
        $invite_where = array(
            'mobile_phone' => $mobile
        );
        $is_invite = $this->ci->user_invite_model->getList("id", $invite_where);
        if (!empty($is_invite)){
            return array('code' => '300', 'msg' => '您已经领取过了！直接使用该手机注册即可使用大礼包！');
        }
        
        /* 验证参与人的手机是否注册过 */
        $attend_where = array(
            'mobile' => $mobile
        );
        $attend_user = $this->ci->user_model->selectUsers("id", $attend_where);
        //是否要往card表里插数据
        $add_card = 1;
        $attend_uid = '';
        if (!empty($attend_user)){
            return array('code' => '300', 'msg' => '您已经是天天果园的会员了，无法参与此活动，更多活动请查看天天果园app');
        }
        
        /* 插入邀请记录 */
        $invite_data = array(
            'mobile_phone'=>$mobile,
            'invite_by'=>$invite_by,
            'ctime'=>time(),
            'weixin_nickname' => $wx_name,
            'weixin_headimg' => $wx_pic,
            'is_finish_order'=>0
        );
        $invite_id = $this->ci->user_invite_model->insertInvite($invite_data);
        
        /* 给手机号发送两个优惠券*/
        $this->ci->user_invite_model->addCard($attend_uid,$mobile);
        
        return array('code' => '200', 'invite_id'=>$invite_id, 'msg' => '恭喜您领取50元果园大礼包成功！'); 
    }
    
    /**
     * 第二期邀请好友 领取赠品
     * 
     * @param unknown $params
     * @return multitype:unknown Ambigous <NULL> |multitype:string |multitype:string unknown  */
    public function getGift2($params){
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '手机号不能为空')),
            'invite_code' => array('required' => array('code' => '300', 'msg' => '邀请号码不能为空'))
    
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $mobile = $params['mobile'];
        $invite_code = $params['invite_code'];
        $invite_code = base64_decode($invite_code);
        $wx_name = $params['wx_name'] ? $params['wx_name'] : '';
        $wx_pic = $params['wx_pic'] ? $params['wx_pic'] : '';
        if (strstr($invite_code,"_") === false){
            return array('code' => '300', 'msg' => '该邀请参数有误！请重新打开页面！');
        }
        $arr_invite_code = explode("_",$invite_code);
        $invite_by = $arr_invite_code[1];
        $where = array(
            "id" => $invite_by
        );
         
        /*验证邀请人用户是否存在*/
        $users = $this->ci->user_model->selectUsers("id,mobile", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '该邀请人不存在！');
        }
        $uid = $users[0]['id'];
        $invite_mobile = $users[0]['mobile'];
        if ($invite_mobile == $mobile){
            return array('code' => '300', 'msg' => '不能邀请自己哦！');
        }
        /* 验证是否已被邀请过 */
        $invite_where = array(
            'mobile_phone' => $mobile
        );
        $is_invite = $this->ci->user_invite2_model->getList("id", $invite_where);
        if (!empty($is_invite)){
            return array('code' => '300', 'msg' => '您已经领取过了！直接使用该手机注册即可使用大礼包！');
        }
    
        /* 验证参与人的手机是否注册过 */
        $attend_where = array(
            'mobile' => $mobile
        );
        $attend_user = $this->ci->user_model->selectUsers("id", $attend_where);
        if (!empty($attend_user)){
            return array('code' => '300', 'msg' => '您已经是天天果园的会员了，无法参与此活动，更多活动请查看天天果园app');
        }
    
        /* 插入邀请记录 */
        $invite_data = array(
            'mobile_phone'=>$mobile,
            'invite_by'=>$invite_by,
            'ctime'=>time(),
            'weixin_nickname' => $wx_name,
            'weixin_headimg' => $wx_pic,
            'is_finish_order'=>0
        );
        //$this->ci->db->trans_begin();
        $invite_id = $this->ci->user_invite2_model->insertInvite($invite_data);
        $log = $this->ci->db->select('id')
        ->from('user_invite_new2_log')
        ->where(array('uid'=>$invite_by))
        ->get()
        ->row_array();
        
        //新增
        if (!$log){
            $log_data = array(
                'uid'=>$invite_by,
                'invite_num'=>0,
                'created_time'=>time()
            );
            $this->ci->db->insert('user_invite_new2_log',$log_data);
        } else {
            
        }
        
    
        /* 给手机号赠品*/
        /* 2016-05-17不发了 统一走后台配置 */
        /* $now_data_time = date("Y-m-d H:i:s");
        $this->ci->db->select('a.*,b.product_name,b.thum_photo');
        $this->ci->db->from('gift_send a');
        $this->ci->db->join('product b', 'a.product_id = b.id');
        $this->ci->db->where(array('tag' => 'I4A032', 'start <=' => $now_data_time, 'end >=' => $now_data_time));
        $query = $this->ci->db->get();
        $user_gift_info = $query->row_array();
        $gift_id = $user_gift_info['id'];
        $user_gift_data = array(
            'uid' => 0,
            'active_id' => $gift_id,
            'active_type' => '2',
            'has_rec' => '0'
        );
        $this->ci->db->insert('user_gifts', $user_gift_data);
        $user_gift_id = $this->ci->db->insert_id();
        $active_data = array(
            'mobile' => $mobile,
            'card_number' => $user_gift_id,
            'active_tag' => 'userinvite2',
            'active_type' => 2
        );
        $this->ci->db->insert('wqbaby_active', $active_data); */
        
        /* if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array('code' => '300', 'msg' => '领取失败');
        } else {
            $this->ci->db->trans_commit();
        } */
    
        return array('code' => '200', 'invite_id'=>$invite_id, 'msg' => '恭喜您领取50元果园大礼包成功！');
    }
    
    /**
     * 邀请好友西梅  领取赠品
     *
     * @param unknown $params
     * @return multitype:unknown Ambigous <NULL> |multitype:string |multitype:string unknown  */
    public function getGiftPrune($params){
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '手机号不能为空')),
            'invite_code' => array('required' => array('code' => '300', 'msg' => '邀请号码不能为空'))
    
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $mobile = $params['mobile'];
        $invite_code = $params['invite_code'];
        $invite_code = base64_decode($invite_code);
        $wx_name = $params['wx_name'] ? $params['wx_name'] : '';
        $wx_pic = $params['wx_pic'] ? $params['wx_pic'] : '';
        if (strstr($invite_code,"_") === false){
            return array('code' => '300', 'msg' => '该邀请参数有误！请重新打开页面！');
        }
        $arr_invite_code = explode("_",$invite_code);
        $invite_by = $arr_invite_code[1];
        $where = array(
            "id" => $invite_by
        );
         
        /*验证邀请人用户是否存在*/
        $users = $this->ci->user_model->selectUsers("id,mobile", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '该邀请人不存在！');
        }
        $uid = $users[0]['id'];
        $invite_mobile = $users[0]['mobile'];
        if ($invite_mobile == $mobile){
            return array('code' => '300', 'msg' => '不能邀请自己哦！');
        }
        /* 验证是否已被邀请过 */
        $invite_where = array(
            'mobile_phone' => $mobile
        );
        $is_invite = $this->ci->user_invite_prune_model->getList("id", $invite_where);
        if (empty($is_invite)){
            /* 插入邀请记录 */
            $invite_data = array(
                'mobile_phone'=>$mobile,
                'invite_by'=>$invite_by,
                'ctime'=>time(),
                'weixin_nickname' => $wx_name,
                'weixin_headimg' => $wx_pic,
                'is_finish_order'=>0
            );
            //$this->ci->db->trans_begin();
            $invite_id = $this->ci->user_invite_prune_model->insertInvite($invite_data);
        }
    
        /* 给手机号赠品*/
        $active_id = array(
	        1 => array('20160831' =>3577, '20160901' => 3577, '20160902' => 3579, '20160903' => 3581, '20160904' => 3583, '20160905' => 3585, '20160906' => 3587, '20160907' => 3589, '20160908' => 3591, '20160909' => 3593, '20160910' => 3595, '20160911' => 3597, '20160912' => 3599, '20160913' => 3601), //新客赠品
	        2 => array('20160831' =>3577, '20160901' => 3578, '20160902' => 3580, '20160903' => 3582, '20160904' => 3584, '20160905' => 3586, '20160906' => 3588, '20160907' => 3590, '20160908' => 3592, '20160909' => 3594, '20160910' => 3596, '20160911' => 3598, '20160912' => 3600, '20160913' => 3602), //老客赠品
	    );
	    /*
	     * 判断新老客户 start
	    */
        //先根据手机号获取uid
        $where = array(
            "mobile" => $mobile
        );
         
        $is_register = $this->ci->user_model->selectUsers("id,mobile", $where);
        if (empty($is_register)){//未注册
            $type = 1;
            $dd = date('Ymd');
            $this->ci->db->from('user_invite_prune_record');
            $this->ci->db->where(array('mobile' => $mobile, 'get_date' => $dd));
            if ($this->ci->db->get()->num_rows() > 0){
                return array('code' => '300', 'msg' => '已经领取过赠品拉！');
            }
            $gift_send = $this->ci->db->select('*')->from('gift_send')->where('id', $active_id[$type][$dd])->get()->row_array();
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
                'uid' => 0,
                'active_id' => $active_id[$type][$dd],
                'active_type' => '2',
                'has_rec' => '0',
                'start_time'=>$gift_start_time,
                'end_time'=>$gift_end_time,
            );
            $result = $this->ci->db->insert('user_gifts', $user_gift_data);
            $user_gift_id = $this->ci->db->insert_id();
            $active_data = array(
                'mobile' => $mobile,
                'card_number' => $user_gift_id,
                'active_tag' => 'userinviteprune',
                'active_type' => 2
            );
            $this->ci->db->insert('wqbaby_active', $active_data);
            $record_data = array(
                'mobile' => $mobile,
                'get_date' => $dd,
                'ctime' => time()
            );
            $this->ci->db->insert('user_invite_prune_record', $record_data);
            
        } else {//已注册
            $invite_uid = $is_register[0]['id'];
            $this->ci->db->from('order');
            $this->ci->db->where(array('order.uid' => $invite_uid, 'order.order_status' => '1', 'order.operation_id !=' => '5', 'order.pay_status !=' => '0'));
            $type = ($this->ci->db->get()->num_rows() == 0) ? 1 : 2;
            $dd = date('Ymd');
            $this->ci->db->from("user_gifts");
            $this->ci->db->where(array("uid" => $invite_uid, "active_id" => $active_id[$type][$dd]));
            $result = $this->ci->db->get()->row_array();
            if (empty($result)) {
                $gift_send = $this->ci->db->select('*')->from('gift_send')->where('id', $active_id[$type][$dd])->get()->row_array();
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
                    'uid' => $invite_uid,
                    'active_id' => $active_id[$type][$dd],
                    'active_type' => '2',
                    'has_rec' => '0',
                    'start_time'=>$gift_start_time,
                    'end_time'=>$gift_end_time,
                );
                $result = $this->ci->db->insert('user_gifts', $user_gift_data);
                /* $user_gift_id = $this->ci->db->insert_id();
                 $active_data = array(
                 'mobile' => $mobile,
                 'card_number' => $user_gift_id,
                 'active_tag' => 'userinviteprune',
                 'active_type' => 2
                 );
                $this->ci->db->insert('wqbaby_active', $active_data); */
            } else {
                return array('code' => '300', 'msg' => '已经领取过赠品拉！');
            }
        }
	    
	    
    
        /* if ($this->ci->db->trans_status() === FALSE) {
         $this->ci->db->trans_rollback();
         return array('code' => '300', 'msg' => '领取失败');
         } else {
         $this->ci->db->trans_commit();
         } */
    
        return array('code' => '200', 'invite_id'=>$invite_id, 'msg' => '恭喜您领取50元果园大礼包成功！','type'=>$type);
    }
    
    public function checkIfInvite($uid,$mobile){
        $invite = $this->ci->db->select('invite_by,ctime')
        ->from('user_invite_new')
        ->where(array('mobile_phone'=>$mobile, 'is_finish_order'=>0))
        ->get()
        ->row_array();
        //不是被邀请的用户
        if (!$invite['invite_by']) return false;
        
        return $invite;
        //$invite_by = $invite['invite_by'];
        /* $invite_data = array('mobile_phone'=>$mobile,'is_finish_order'=>1,'uid'=>$uid);
        $this->ci->user_invite_model->updateInvite($invite_data); */
        
        //return $invite_by;
        
    }
    
    public function checkIfInvite2($uid,$mobile){
        $invite = $this->ci->db->select('invite_by,ctime')
        ->from('user_invite_new2')
        ->where(array('mobile_phone'=>$mobile, 'is_finish_order'=>0))
        ->get()
        ->row_array();
        //不是被邀请的用户
        if (!$invite) return false;
    
        return $invite;
        //$invite_by = $invite['invite_by'];
        /* $invite_data = array('mobile_phone'=>$mobile,'is_finish_order'=>1,'uid'=>$uid);
         $this->ci->user_invite_model->updateInvite($invite_data); */
    
        //return $invite_by;
    
    }
    
    public function checkIfInvitePrune($uid,$mobile){
        $invite = $this->ci->db->select('invite_by,ctime')
        ->from('user_invite_prune')
        ->where(array('mobile_phone'=>$mobile, 'is_finish_order'=>0))
        ->get()
        ->row_array();
        //不是被邀请的用户
        if (!$invite) return false;
    
        return $invite;
        //$invite_by = $invite['invite_by'];
        /* $invite_data = array('mobile_phone'=>$mobile,'is_finish_order'=>1,'uid'=>$uid);
         $this->ci->user_invite_model->updateInvite($invite_data); */
    
        //return $invite_by;
    
    }
    
    /* 更新这条邀请记录 将finish_order变为1 下次不再送积分 */
    public function updateFinishOrder($uid,$mobile){
        $invite_data = array('mobile_phone'=>$mobile,'is_finish_order'=>1,'uid'=>$uid,'finish_order_time'=>time());
        //更新invite表
        $result = $this->ci->user_invite_model->updateInvite($invite_data);
        return $result;
    }
    
    /* 更新这条邀请记录 将finish_order变为1 下次不再送积分 */
    public function updateFinishOrder2($uid,$mobile){
        $invite_data = array('mobile_phone'=>$mobile,'is_finish_order'=>1,'uid'=>$uid,'finish_order_time'=>time());
        //更新invite表
        $result = $this->ci->user_invite2_model->updateInvite($invite_data);
        return $result;
    }
    
    public function updateFinishOrderPrune($uid,$mobile){
        $invite_data = array('mobile_phone'=>$mobile,'is_finish_order'=>1,'uid'=>$uid,'finish_order_time'=>time());
        //更新invite表
        $result = $this->ci->user_invite_prune_model->updateInvite($invite_data);
        return $result;
    }
    
    /* 送 樱桃 */
    public function sendInviteGift($invite_by){
        $today = date('Y-m-d');
        $gift = array();
        $gift['2016-06-20'] = array('1'=>'t07aG','2'=>'FIwTW3','3'=>'hb8ts','4'=>'ioLgL3','5'=>'FIwTW3');
        $gift['2016-06-21'] = array('1'=>'DpuyD3','2'=>'bZZtY4','3'=>'2lN7c2','4'=>'E2OaS3','5'=>'S2cyF3');
        $gift['2016-06-22'] = array('1'=>'3JqTt','2'=>'JNGkm2','3'=>'Wdfc41','4'=>'zxgnx3','5'=>'BqIOy2');
        $gift['2016-06-23'] = array('1'=>'B3AlQ3','2'=>'OZSLW2','3'=>'Fbopx3','4'=>'5TYVx','5'=>'T3hy34');
        $gift['2016-06-24'] = array('1'=>'eeTS54','2'=>'bjx4G2','3'=>'zUI3j3','4'=>'jzC011','5'=>'LWIGj2');
        $gift['2016-06-25'] = array('1'=>'SX58R4','2'=>'ayE6N4','3'=>'e479e2','4'=>'m1YqP2','5'=>'klDuK1');
        $gift['2016-06-26'] = array('1'=>'F6Oai2','2'=>'gn7uR1','3'=>'yADP12','4'=>'1PLON4','5'=>'BAQSh3');
        $gift['2016-06-27'] = array('1'=>'VrWjI4','2'=>'JkX8k','3'=>'OOvzj2','4'=>'YN0u1','5'=>'AaIYq1');
        $gift['2016-06-28'] = array('1'=>'9yJ5V3','2'=>'Gr7ft1','3'=>'gDEES2','4'=>'gzGtX4','5'=>'usabD');
        $gift['2016-06-29'] = array('1'=>'AEJs84','2'=>'f2j9k2','3'=>'DsHFO4','4'=>'2zhjE1','5'=>'s5x2C');
        $gift['2016-06-30'] = array('1'=>'TzRCb4','2'=>'aQLjv1','3'=>'LjB11','4'=>'OauYB4','5'=>'oO5J21');
        $gift['2016-07-01'] = array('1'=>'vG8Ja1','2'=>'mqHpn','3'=>'U5wkA','4'=>'ntQrA2','5'=>'f1mYa3');
        $gift['2016-07-02'] = array('1'=>'2sZoT1','2'=>'mFmRO3','3'=>'Zj8QH3','4'=>'vHe622','5'=>'bM6Gx2');
        $gift['2016-07-03'] = array('1'=>'U6opq1','2'=>'qRQy13','3'=>'jVAcN1','4'=>'fKLkD2','5'=>'2YI902');
        $gift['2016-07-04'] = array('1'=>'1WehB1','2'=>'Jhyv41','3'=>'bQbFv1','4'=>'fbQuG3','5'=>'uOd8E1');
        $gift['2016-07-05'] = array('1'=>'q7GvD4','2'=>'1n8ev','3'=>'07Sx12','4'=>'rUVUA1','5'=>'GTBii1');
        $gift['2016-07-06'] = array('1'=>'2SBbR','2'=>'h9wKc4','3'=>'mToqk1','4'=>'zeb1d3','5'=>'2YRuj3');
        $gift['2016-07-07'] = array('1'=>'kmAqV','2'=>'SBxoF1','3'=>'kLNoA2','4'=>'oRlUK3','5'=>'qFuEw1');
        $gift['2016-07-08'] = array('1'=>'4CBdW3','2'=>'urbmF2','3'=>'YYzVa1','4'=>'9waDn3','5'=>'vrHDo');
        $gift['2016-07-09'] = array('1'=>'RrGZE3','2'=>'dXcBi3','3'=>'Oodyk2','4'=>'RNtXl2','5'=>'FuTYN3');
        $gift['2016-07-10'] = array('1'=>'sGCRv','2'=>'Z1cFX4','3'=>'AsusG2','4'=>'CUCOs','5'=>'1O1Bp2');
        $gift['2016-07-11'] = array('1'=>'xEvYy3','2'=>'u0ETK4','3'=>'Jyp89','4'=>'Jo6p61','5'=>'lXJTu2');
        $gift['2016-07-12'] = array('1'=>'Yf9KK1','2'=>'y2aEX3','3'=>'7YqJ53','4'=>'mguq04','5'=>'b672e1');
        $gift['2016-07-13'] = array('1'=>'5WB2w1','2'=>'HBlJD2','3'=>'91fnx3','4'=>'fq9kT','5'=>'eRfSI');
        $gift['2016-07-14'] = array('1'=>'0yvzi3','2'=>'0H2rx','3'=>'uTa934','4'=>'NF4KR','5'=>'KZubc4');
        $gift['2016-07-15'] = array('1'=>'bOIO11','2'=>'bZGg6','3'=>'PirOT3','4'=>'a1uos1','5'=>'7WmPA4');
        $gift['2016-07-16'] = array('1'=>'hVxCW3','2'=>'9Wr8R2','3'=>'fpIcN1','4'=>'teIlV1','5'=>'YG6Ns');
        $gift['2016-07-17'] = array('1'=>'0EECU','2'=>'lohzM3','3'=>'Lk1J','4'=>'iW93D1','5'=>'TinAm');
        $gift['2016-07-18'] = array('1'=>'vhiTl2','2'=>'bz2xc','3'=>'Ea9DF3','4'=>'l76nw3','5'=>'AkoGb4');
        $gift['2016-07-19'] = array('1'=>'RICKg','2'=>'MRkC82','3'=>'BvtOE','4'=>'M64Tj2','5'=>'t91DU3');
        $gift['2016-07-20'] = array('1'=>'LHlDQ1','2'=>'hMzGM1','3'=>'bIT463','4'=>'8ubiU','5'=>'qQ5Vy2');
        $gift['2016-07-21'] = array('1'=>'8hx2c','2'=>'WNQl01','3'=>'5POez1','4'=>'GeeYs3','5'=>'CSVFD2');
        $gift['2016-07-22'] = array('1'=>'khbyF4','2'=>'bE3PF1','3'=>'QU9tT3','4'=>'4vBxG4','5'=>'LIRly');
        $gift['2016-07-23'] = array('1'=>'brKPs3','2'=>'7VGmS4','3'=>'fhr402','4'=>'ntR5Z4','5'=>'NX7DA3');
        $gift['2016-07-24'] = array('1'=>'xbF7w1','2'=>'F0JaK3','3'=>'hkV6V','4'=>'eQTDz1','5'=>'rELNK');
        $gift['2016-07-25'] = array('1'=>'ZStFn1','2'=>'JJEGQ1','3'=>'86WTh2','4'=>'mv0Vj','5'=>'1mAYD2');
        
        
        
        
        //$info = $this->ci->user_invite_model->countInvite($invite_by);
        //改为按日期送
        $info = $this->ci->user_invite_model->countAdd($invite_by);
        $count_add = $info[0]['count_add'] ? $info[0]['count_add'] : 0;
        $tag = '';
		/* if ($count_add >= 20){
		    $tag = $gift[$today][10];
		} elseif ($count_add >= 18) {
		    $tag = $gift[$today][9];
		} elseif ($count_add >= 16) {
		    $tag = $gift[$today][8];
		} elseif ($count_add >= 14) {
		    $tag = $gift[$today][7];
		} elseif ($count_add >= 12) {
		    $tag = $gift[$today][6];
		} else */
		if ($count_add >= 10) {
		    $tag = $gift[$today][5];
		} elseif ($count_add >= 8) {
		    $tag = $gift[$today][4];
		} elseif ($count_add >= 6) {
		    $tag = $gift[$today][3];
		} elseif ($count_add >= 4) {
		    $tag = $gift[$today][2];
		} elseif ($count_add >= 2) {
		    $tag = $gift[$today][1];
		}
		if ($tag) {
		    $gift = $this->ci->user_invite_model->send_gift($invite_by,$tag);
		} else {
            $gift = '';        
        }
		return array('gift'=>$gift);
    }
    
    /* 送 樱桃 */
    public function sendInviteGiftPrune($invite_by){
        $today = date('Y-m-d');
        $gift = array();
        $gift = array('1'=>'iYIMD4','2'=>'sdl9a','3'=>'LdgaO','4'=>'iUJ4H2','5'=>'bGvfU3');
    
    
        //$info = $this->ci->user_invite_model->countInvite($invite_by);
        //改为按日期送
        $info = $this->ci->user_invite_prune_model->countAdd($invite_by);
        $count_add = $info[0]['count_add'] ? $info[0]['count_add'] : 0;
        $tag = '';
        /* if ($count_add >= 20){
         $tag = $gift[$today][10];
         } elseif ($count_add >= 18) {
         $tag = $gift[$today][9];
         } elseif ($count_add >= 16) {
         $tag = $gift[$today][8];
         } elseif ($count_add >= 14) {
         $tag = $gift[$today][7];
         } elseif ($count_add >= 12) {
         $tag = $gift[$today][6];
         } else */
        if ($count_add >= 25) {
            $tag = $gift[5];
        } elseif ($count_add >= 20) {
            $tag = $gift[4];
        } elseif ($count_add >= 15) {
            $tag = $gift[3];
        } elseif ($count_add >= 10) {
            $tag = $gift[2];
        } elseif ($count_add >= 5) {
            $tag = $gift[1];
        }
        if ($tag) {
            $gift = $this->ci->user_invite_prune_model->send_gift($invite_by,$tag);
        } else {
            $gift = '';
        }
        //短信提醒
        if ($gift){
            $where = array(
                "id" => $invite_by
            );
             
            $user = $this->ci->user_model->selectUsers("id,mobile", $where);
            if ($user){
                $mobile_phone = $user[0]['mobile'];
                $this->ci->load->library("notifyv1");
                $send_params = [
                    "mobile"  => $mobile_phone,
                    "message" => "恭喜获得大大大龙虾一只！你离iphone6s只有一步之遥了，速去朋友圈邀请好友下单吧，考验友情的时候到啦~",
                ];
                $this->ci->notifyv1->send('sms','send',$send_params);
            }
            
        }
        return array('gift'=>$gift);
    }
    
    /* 0201之后最多送三份樱桃 */
    public function sendInviteGift0201($invite_by){
        $today = date('Y-m-d');
        $gift = array();
        $gift['2016-02-01'] = array('1'=>'mTN6O2','2'=>'U1vav3','3'=>'sKW8u2','4'=>'8IiPM4','5'=>'Fk6Vi');
        $gift['2016-02-02'] = array('1'=>'oyTsn2','2'=>'yvw0A4','3'=>'T4cRF2','4'=>'WzySi1','5'=>'9rM7s1');
        $gift['2016-02-03'] = array('1'=>'w8O0q','2'=>'Ml0rj2','3'=>'eUMgi','4'=>'ZYw9t2','5'=>'28WJd4');
        $gift['2016-02-04'] = array('1'=>'D7dSZ4','2'=>'gLYgM4','3'=>'NCXLP1','4'=>'1a2Z61','5'=>'WF5iU4');
        $gift['2016-02-05'] = array('1'=>'o6v9E3','2'=>'zvmqk2','3'=>'6X09b3','4'=>'Vx3OT3','5'=>'jFBG8');
        $gift['2016-02-06'] = array('1'=>'nf2TD1','2'=>'hAjFa2','3'=>'bluKI2','4'=>'PSbaN3','5'=>'1GzkU2');
        $gift['2016-02-07'] = array('1'=>'doVjP3','2'=>'cKFgC4','3'=>'HH9lL1','4'=>'JUHxv2','5'=>'YQXza2');
        $gift['2016-02-08'] = array('1'=>'5QXnr1','2'=>'6QD0Y4','3'=>'7p1vF1','4'=>'fx9Fx','5'=>'F9FmR1');
        $gift['2016-02-09'] = array('1'=>'TGzyz3','2'=>'vI6LL4','3'=>'2Ktwa4','4'=>'A8cOQ','5'=>'e8PWi3');
        $gift['2016-02-10'] = array('1'=>'WP2qh1','2'=>'Fakcs1','3'=>'uW4tT3','4'=>'wr75b4','5'=>'MdReE');
        $gift['2016-02-11'] = array('1'=>'7wMgO1','2'=>'jZUgA','3'=>'Mf23c','4'=>'EiR5e4','5'=>'Rleup1');
        $gift['2016-02-12'] = array('1'=>'JZ54P2','2'=>'jHDwi3','3'=>'hDq674','4'=>'k6sSe1','5'=>'ta1nV4');
        $gift['2016-02-13'] = array('1'=>'5Pzlf2','2'=>'UIQbZ2','3'=>'qRIi14','4'=>'Ynqt8','5'=>'csXkB4');
        $gift['2016-02-14'] = array('1'=>'NAEEa4','2'=>'GIBD81','3'=>'LKGoK2','4'=>'ORjPu1','5'=>'133oJ');
        $gift['2016-02-15'] = array('1'=>'zMl7z1','2'=>'lZxcP1','3'=>'Ycc5O4','4'=>'A1Fiu2','5'=>'wWyak3');
        $gift['2016-02-16'] = array('1'=>'lSpbl1','2'=>'kB1Fw2','3'=>'0xyAm1','4'=>'pJTjM2','5'=>'NYyfo');
        //$info = $this->ci->user_invite_model->countInvite($invite_by);
        //改为按日期送
        $info = $this->ci->user_invite_model->countAdd($invite_by);
        $count_add = $info[0]['count_add'] ? $info[0]['count_add'] : 0;
        $tag = '';
        /* if ($count_add >= 20){
         $tag = $gift[$today][10];
         } elseif ($count_add >= 18) {
         $tag = $gift[$today][9];
         } elseif ($count_add >= 16) {
         $tag = $gift[$today][8];
         } elseif ($count_add >= 14) {
         $tag = $gift[$today][7];
         } elseif ($count_add >= 12) {
         $tag = $gift[$today][6];
         } else */
        if ($count_add >= 6) {
            $tag = $gift[$today][3];
        } elseif ($count_add >= 4) {
            $tag = $gift[$today][2];
        } elseif ($count_add >= 2) {
            $tag = $gift[$today][1];
        }
        if ($tag) {
            $gift = $this->ci->user_invite_model->send_gift($invite_by,$tag);
        } else {
            $gift = '';
        }
        return array('gift'=>$gift);
    }
    
    /* 0207之后送凤梨香梨 */
    public function sendInviteGift0207($invite_by){
        $info = $this->ci->user_invite_model->countAddAfter0207($invite_by);
        $count_add = $info[0]['count_add'] ? $info[0]['count_add'] : 0;
        $user = $this->ci->db->select('mobile')
        ->from('user')
        ->where(array('id'=>$invite_by))
        ->get()
        ->row_array();
        if ($user['mobile']){
            $mobile = $user['mobile'];
        }
        $gift = '';
        //正好满2个的时候送香梨
        if ($count_add == 2){
            //送香梨
            $black_list ='';// $active_product_id;
            $p_card_number = 'invitepear';
            $card_number = $p_card_number.$this->rand_card_number($p_card_number);
            $time = date("Y-m-d");
            $d=strtotime("+3 days");
            $to_date = date("Y-m-d H:i:s",$d);
            $remarks = '邀请好友赠品香梨';
            /*优惠券优惠券生成设置end*/
            $card_data = array(
                'uid'=>$invite_by,
                'sendtime'=>$time,
                'card_number'=>$card_number,
                'card_money'=>59,
                'product_id'=>8854,
                'maketing'=>0,
                'is_sent'=>1,
                'restr_good'=>1,
                'remarks'=>$remarks,
                'time'=>$time,
                'to_date'=>$to_date,
                'can_use_onemore_time'=>'false',
                'can_sales_more_times'=>'false',
                'card_discount'=>1,
                'order_money_limit'=>188,//100
                'black_list'=>$black_list,
                'channel'=>serialize(array("2"))//serialize(array("2"))
            );
            $this->ci->db->insert('card',$card_data);
            
            if ($mobile){
                $active_tag = 'invitepear';
                $link_tag = '';
                $active_data = array(
                    'mobile' => $mobile,
                    'card_number' => $card_number,
                    'active_tag' => $active_tag,
                    'link_tag' => $link_tag,
                    'description' => '',
                    'card_money' => 59
                );
                $this->ci->db->insert('wqbaby_active', $active_data);
            }
            $gift = $card_number;
        } elseif ($count_add == 1){ //1个的时候送凤梨
            //送凤梨
            $black_list ='';// $active_product_id;
            $p_card_number = 'invitepine';
            $card_number = $p_card_number.$this->rand_card_number($p_card_number);
            $time = date("Y-m-d");
            $d=strtotime("+3 days");
            $to_date = date("Y-m-d H:i:s",$d);
            $remarks = '邀请好友赠品凤梨';
            /*优惠券优惠券生成设置end*/
            $card_data = array(
                'uid'=>$invite_by,
                'sendtime'=>$time,
                'card_number'=>$card_number,
                'card_money'=>45,
                'product_id'=>8853,
                'maketing'=>0,
                'is_sent'=>1,
                'restr_good'=>1,
                'remarks'=>$remarks,
                'time'=>$time,
                'to_date'=>$to_date,
                'can_use_onemore_time'=>'false',
                'can_sales_more_times'=>'false',
                'card_discount'=>1,
                'order_money_limit'=>144,//100
                'black_list'=>$black_list,
                'channel'=>serialize(array("2"))//serialize(array("2"))
            );
            $this->ci->db->insert('card',$card_data);
            
            if ($mobile){
                $active_tag = 'invitepine';
                $link_tag = '';
                $active_data = array(
                    'mobile' => $mobile,
                    'card_number' => $card_number,
                    'active_tag' => $active_tag,
                    'link_tag' => $link_tag,
                    'description' => '',
                    'card_money' => 45
                );
                $this->ci->db->insert('wqbaby_active', $active_data);
            }
            $gift = $card_number;
        } else {
            $gift = ''; 
        }
        return array('gift'=>$gift);
    }
    
    /* 邀请好友第二期 获取邀请赠品 */
    public function takeGift($params) {
		$required_fields = array(
			'uid' => array('required' => array('code' => '300', 'msg' => '用户id不能为空')),
		    'gift_tag' => array('required' => array('code' => '300', 'msg' => '赠品不能为空'))
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		$uid = $params['uid'];
		$gift_tag = $params['gift_tag'];
		$gift = $this->ci->user_invite2_model->send_gift($uid,$gift_tag);
		if (!$gift){
		    return array('code' => '300', 'msg' => '你还有想通的赠品未使用哦~请先使用后再来领取~');
		}
        return array('code' => '200', 'msg' => '成功领取赠品！'); 


    }
    
    /* 送 蓝莓樱桃 */
    public function sendOldInviteGift($invite_by){
        $info = $this->ci->user_invite_model->countOldInvite($invite_by);
        $count_invite = $info[0]['count_invite'] ? $info[0]['count_invite'] : 0;
        $gift1 = '';
        $gift2 = '';
        if ($count_invite >= 5){
            //樱桃
            $gift1 = $this->ci->user_invite_model->send_gift($invite_by,'trl3E4');
            //蓝莓
            $gift2 = $this->ci->user_invite_model->send_gift($invite_by,'B1GFE');
        } elseif($count_invite >= 2){
            //蓝莓
            $gift1 = $this->ci->user_invite_model->send_gift($invite_by,'B1GFE');
        }
        return array('gift1'=>$gift1,'gift2'=>$gift2);
    }
    
    /* 验证今天送积分次数 */
    public function countAdd($invite_by){
        $info = $this->ci->user_invite_model->countAdd($invite_by);
        $count_add = $info[0]['count_add'] ? $info[0]['count_add'] : 0;
        return $count_add;
    }
    
    private function rand_card_number($p_card_number = '') {
        $a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
        $a_array = explode(",", $a);
        $tname = '';
        for ($i = 1; $i <= 10; $i++) {
            $tname.=$a_array[rand(0, 31)];
        }
        return $tname;
    }
    
    
    
}