<?php

namespace bll\alipay;

class User
{
    private $session_expiretime = 1209600;

    public function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->model('alipay_model');
        $this->ci->load->helper('public');
    }
    
    /**
     * 网页授权后的账号绑定。
     * @param array $aParam
     * @return array
     */
    public function bind($aParams)
    {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '310', 'msg' => '帐号不能为空')),
            'password' => array('required' => array('code' => '311', 'msg' => '密码不能为空')),
            'alipay_user_info' => array('required' => array('code' => '312', 'msg' => '支付宝用户信息不能为空')),
        );

        if ($alert_msg = check_required($aParams, $required_fields)) {
            return ['code' => $alert_msg['code'], 'msg' => $alert_msg['msg']];
        }

        $aUserInfo = $this->login($aParams);
        $aAlipayUserInfo = json_decode($aParams['alipay_user_info'], true);
    
        /*$log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------login\r\n";
        $log .= var_export($aAlipayUserInfo,true)."\r\n";
        error_log( $log, 3, "/tmp/alipay_token.log");*/

        if ($aUserInfo['code'] > 200) {
            return $aUserInfo;
        } else {
            $aResult = $this->ci->alipay_model->bind($aUserInfo['user_id'], $aParams['mobile'], $aAlipayUserInfo);
            
            /*$log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------bind\r\n";
            $log .= var_export($aResult,true)."\r\n";
            error_log( $log, 3, "/tmp/alipay_token.log");*/
            

            if ($aResult['code'] == 200) {
                $aResult['connect_id'] = $aUserInfo['connect_id'];

                // 如果有绑定相关的送礼活动，则给用户送礼并推送微信消息。
                //$this->ci->alipay_model->bindingGift($aUserInfo['user_id'], $aParams['mobile'], $aWeixinUserInfo);
            }

            return $aResult;
        }
    }
    
    
    public function basebind( $aParams )
    {
        if( !$aParams['mobile'] ){
            return ['code' => '301', 'msg' => '手机号不能为空'];
        }
        if( !$aParams['user_id'] ){
            return ['code' => '302', 'msg' => '用户ID不能为空'];
        }
        if( !$aParams['alipay_user_id'] ){
            return ['code' => '303', 'msg' => '支付宝用户信息不能为空'];
        }
        
        $aResult = $this->ci->alipay_model->bind_user_id($aParams['user_id'], $aParams['mobile'], $aParams['alipay_user_id']);
        
        return $aResult;
    }
    
    //发放卡券
    public function updateCard( $aParams )
    {
        if( $aParams['card_no'] ){
            $where = array( 'card_number' => $aParams['card_no'] );
        }else{
            return ['code' => '301', 'msg' => '优惠券卡号不能为空'];
        }
        
        if( $aParams['user_id'] ){
            $data = array( 'uid' => $aParams['user_id'] );
        }else{
            return ['code' => '302', 'msg' => 'user_id不能为空'];
        }
        
        //检查卡券发放状态
        $card_where = array( 'card_number' => $aParams['card_no'] );
        $card_info = $this->ci->alipay_model->get_alipay_card( $card_where );
        if( $card_info ){
            return ['code' => '201', 'msg' => '卡券已经被使用'];
        }
        
        $card_where = array( 'uid' => $aParams['user_id'] );
        $card_info = $this->ci->alipay_model->get_alipay_card( $card_where );
        if( $card_info ){
            return ['code' => '202', 'msg' => '用户已领券'];
        }
        
        $card_where = array( 'alipay_user_id' => $aParams['alipay_user_id'] );
        $card_info = $this->ci->alipay_model->get_alipay_card( $card_where );
        if( $card_info ){
            return ['code' => '203', 'msg' => '支付宝用户已领券'];
        }
        
        $this->ci->load->model('card_model');
        $bResult = $this->ci->card_model->update_card($data,$where);
        
    
        // $this->ci->load->library('fdaylog');
        // $db_log = $this->ci->load->database('db_log', TRUE);
        // $log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------updateCard\r\n";
        // $log .= var_export($where,true)."\r\n";
        // $log .= var_export($data,true)."\r\n";
        // $log .= var_export($bResult,true)."\r\n";
        // //error_log( $log, 3, "C:/alipay_token.log");
        // $this->ci->fdaylog->add($db_log,'alipay_token', $log  );
        
        if( $bResult ){
            
            $card_info = array(
                'uid' => $aParams['user_id'],
                'card_number' => $aParams['card_no'],
                'alipay_user_id' => $aParams['alipay_user_id'],
                'device_id' => '',
                'identity_no' => '',
                'mobile' => '',
                'status' => 0,
                'ver_status' => 'N'
            );
            
            $this->ci->alipay_model->set_alipay_card( $card_info );
            
            return ['code' => '200', 'msg' => '更新成功'];
        }else{
            return ['code' => '301', 'msg' => '更新失败'];
        }
    }
    
    
    public function cardInfo( $aParams )
    {
        if( $aParams['card_no'] ){
            $where = array( 'card_number' => $aParams['card_no'] );
        }else{
            return ['code' => '301', 'msg' => '优惠券卡号不能为空'];
        }
        
        $this->ci->load->model('alipay_model');
        $cardInfo = $this->ci->alipay_model->get_alipay_card($aParams['card_no']);
        
        if( $cardInfo ){
            return ['code' => '200', 'cardInfo' => $cardInfo];
        }else{
            return ['code' => '201', 'msg' => '卡券为空'];
        }
        
    }
    
    /**
     * 绑定地址
     * @param array $params
     * @return array
     */
    public function bindAddress( $aParams )
    {
        if( !$aParams['user_id'] ){
            return ['code' => '302', 'msg' => '用户ID不能为空'];
        }
        if( !$aParams['address'] ){
            return ['code' => '301', 'msg' => '数据不能为空'];
        }else{
            $address = json_decode($aParams['address'], true);
        }
        
        $aResult = $this->ci->alipay_model->bind_alipay_address($aParams['user_id'], $address);
        
        return $aResult;
    }
    

    /**
     * 登录操作。
     * @param array $params
     * @return array
     */
    private function login($params)
    {
        if (!preg_match('/@/', $params['mobile'])) {
            $where = array(
                "mobile" => $params['mobile'],
            );
        } else {
            $where = array(
                "email" => $params['mobile'],
            );
        }
        
        /*登录次数验证start*/
        $this->ci->load->model('user_model');
        $users = $this->ci->user_model->selectUsers("id,chkpass", $where);
        
        if (empty($users)) {
            return array('code' => '300', 'msg' => '用户名错误');
        }
        $uid = $users[0]['id'];
        if($this->ci->user_model->setLoginErrorNum($uid)>=5){
            return array("code"=>"300","msg"=>"重试登录次数过多，请操作找回密码");
        }
        /*登录次数验证end*/
        $this->ci->load->library("PassMd5");
        if($users[0]['chkpass']=='1'){
            $userPassWord = $this->ci->passmd5->userPwd($params['password']);
        }else{
            $userPassWord = $params['password'];
            $newPwd = $this->ci->passmd5->userPwd($params['password']);
        }
        

        $where['password'] = $userPassWord;
        $user = $this->ci->user_model->getUser("", $where);
        if (isset($user['code'])) {
            return $user;
        }


        //更新密码
        if($users[0]['chkpass']!='1'){
            $update_where = array(
                'id' => $uid
            );
            $update_data = array(
                'chkpass' => '1',
                'password' => $newPwd,
            );
            $this->ci->user_model->updateUser($update_where, $update_data);
        }

        //重置登陆错误
        $this->ci->load->library('session');
        $this->ci->user_model->setLoginErrorNum($user['id'], 1);
        $this->ci->session->sess_expiration = $this->session_expiretime;
		$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
        $session_id = $this->ci->session->set_userdata($user);
		session_id($session_id);
        session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
        $_SESSION['user_detail'] = $user;
        session_write_close();
        return ['code' => 200, 'user_id' => $user['id'], 'connect_id' => $session_id];
    }
    
    
    //手机号快捷登录
    public function quick_login( $params )
    {
        
        $required_fields = array(
            'mobile' => array('required' => array('code' => '310', 'msg' => '帐号不能为空')),
            'alipay_user_info' => array('required' => array('code' => '312', 'msg' => '支付宝用户信息不能为空')),
        );

        if ($alert_msg = check_required($aParams, $required_fields)) {
            return ['code' => $alert_msg['code'], 'msg' => $alert_msg['msg']];
        }
        
        $aAlipayUserInfo = json_decode($aParams['alipay_user_info'], true);
    }
    
    
    //手机号
    public function register( $aParam )
    {    
        $required_fields = array(
            'mobile' => array('required' => array('code' => '310', 'msg' => '帐号不能为空')),
            'alipay_user_info' => array('required' => array('code' => '312', 'msg' => '支付宝用户信息不能为空')),
        );

        if ($alert_msg = check_required($aParams, $required_fields)) {
            return ['code' => $alert_msg['code'], 'msg' => $alert_msg['msg']];
        }
        
        $aAlipayUserInfo = json_decode($aParams['alipay_user_info'], true);
        
        $user_info = array(
            "username" => $aParam['mobile'],
            "mobile" => $aParam['mobile'],
            "password" => '55129ff0a2d7c7af8a8bf91aef127fb5',
            "mobile_status" => 0,
            "reg_time" => date("Y-m-d H:i:s"),
            "last_login_time" => date("Y-m-d H:i:s"),
            "invite_code" => 'alipay',
            "randcode" => '',
            "reg_from" => 'alipayPass',
            "chkpass" => '0',
        );

        $this->ci->load->model('user_model');
        $uid = $this->ci->user_model->addUser($user_info);
        
        if( $uid ){
            
            $aResult = $this->ci->alipay_model->bind($uid, $aParams['mobile'], $aAlipayUserInfo);

            if( $aResult['code'] == '200' ){
                return ['code' => 200, 'msg' => 'suss', 'user_info' => $aResult['user_info'] ];    
            }else{
               return ['code' => 301, 'msg' => 'bind fail']; 
            }
            
        }else{
            return ['code' => 300, 'msg' => 'reg fail'];
        }
    }
    
}
