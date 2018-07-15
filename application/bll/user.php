<?php

namespace bll;

class User {

    //用户头像
    private $head_photopath = "up_images/";
    //用户头像大小
    private $userface_size = array('big' => 200, 'middle' => 130, 'small' => 112);
    var $session_expiretime = 1209600;
    var $share_channel = array('1', '2', '3', '4', '5');
    var $share_type_arr = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10');
    private $terminal_arr = array('pc' => 1, 'app' => 2, 'wap' => 3);
    private $sess_allow = array('user.signin', 'user.register', 'user.sendPhoneTicket', 'user.sendVerCode', 'user.oAuthSignin', 'user.getLoginError', 'user.setLoginError', 'user.OAuthLogin');
    private $score2change = 10;//积分换摇一摇次数
    private $exchange_rule = "使用10积分兑换额外一次摇一摇机会";  //文案

    function __construct($params = array()) {
        $this->ci = &get_instance();
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        if ($session_id || (isset($params['service']) && in_array($params['service'], $this->sess_allow))) {
            $this->ci->load->library('session', array('session_id' => $session_id));
        }
        $this->ci->load->model('user_model');
        $this->ci->load->model('user_points');
        $this->ci->load->model('user_device_model');
        $this->ci->load->helper('public');
        $this->ci->load->library("PassMd5");
        $this->head_photopath = $this->head_photopath . date("Y-m-d");
    }

    /**
     * 与第三方APP登录。
     */
    public function OAuthLogin($aParam) {
        $required_fields = [
            'client' => [
                'required' => [
                    'code' => '300',
                    'msg' => '客户标识不能为空'
                ]
            ],
            'uid' => [
                'required' => [
                    'code' => '300',
                    'msg' => '用户ID不能为空'
                ]
            ],
        ];

        if ($alert_msg = check_required($aParam, $required_fields)) {
            return ['code' => $alert_msg['code'], 'msg' => $alert_msg['msg']];
        }

        $fields = "id,email,username,money,mobile,mobile_status,reg_time,last_login_time,sex,birthday,user_head,enter_id,user_rank,is_pic_tmp,msgsetting,how_know as can_set_password,http_user_agent,how_to_know,invite_code,reg_from";
        $user = $this->ci->user_model->getUser($aParam['uid'], '', $fields);

        if (isset($user['code'])) {
            return $user;
        } else {
            if ($user['invite_code'] !== 'oauth' or $user['reg_from'] != $aParam['client']) {
                return ['code' => 500, 'msg' => '非法登录请求'];
            }
        }
		$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
        $connect_id = $this->ci->session->set_userdata($user);
        session_id($connect_id);
        session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
        $_SESSION['user_detail'] = $user;
        session_write_close();

        return ['code' => 200, 'msg' => $connect_id];
    }

    /**
     * 与第三方APP注册。
     */
    public function OAuthRegister($aParam) {
        $required_fields = [
            'client' => [
                'required' => [
                    'code' => '300',
                    'msg' => '客户标识不能为空'
                ]
            ],
        ];

        if ($alert_msg = check_required($aParam, $required_fields)) {
            return ['code' => $alert_msg['code'], 'msg' => $alert_msg['msg']];
        }

        $user_insert_data = array(
            "username" => $aParam['client'] . $aParam['number'],
            "mobile" => '',
            "password" => '55129ff0a2d7c7af8a8bf91aef127fb5', # md5('fruitday_oauth!@#$$+_09')
            "mobile_status" => 0,
            "reg_time" => date("Y-m-d H:i:s"),
            "last_login_time" => date("Y-m-d H:i:s"),
            "invite_code" => 'oauth',
            "randcode" => '',
            "reg_from" => $aParam['client'],
            "chkpass" => '0',
        );

        $uid = $this->ci->user_model->addUser($user_insert_data);

        return ['code' => 200, 'msg' => $uid];
    }

    /**
     * @api              {post} / 用户登录
     * @apiDescription   用户登录
     * @apiGroup         user
     * @apiName          signin
     *
     * @apiParam {Number} mobile 帐号.
     * @apiParam {String} password 密码.
     *
     * @apiSuccess {String} connect_id 用户登录状态.
     *
     * @apiSampleRequest /api/test?service=user.signin
     */
    public function signin($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
            'password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

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
        $users = $this->ci->user_model->selectUsers("id,chkpass", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '用户名错误');
        }
        $uid = $users[0]['id'];
        if ($this->ci->user_model->setLoginErrorNum($uid) >= 5) {
            return array("code" => "300", "msg" => "重试登录次数过多，请操作找回密码");
        }
        /*登录次数验证end*/

        if ($users[0]['chkpass'] == '1') {
            $userPassWord = $this->ci->passmd5->userPwd($params['password']);
        } else {
            $userPassWord = $params['password'];
            $newPwd = $this->ci->passmd5->userPwd($params['password']);
        }


        $where['password'] = $userPassWord;
        $user = $this->ci->user_model->getUser("", $where);
        if (isset($user['code'])) {
            return $user;
        }

        $mobile_white_list = $this->ci->config->item('mobile_white_list');
        $isEmail = $this->is_email($params['mobile']);   //邮箱用户
        if (!in_array($params['mobile'], $mobile_white_list) && !$isEmail) {//白名单


            //account-safe
            $user_dev = $this->ci->user_device_model->getList('id,uid,ip,device_id', array('uid' => $uid, 'state' => 1));
            $arr_dev = array();
            $arr_ip = array();
            if (!empty($user_dev)) {
                $arr_dev = array_column($user_dev, 'device_id');
                $arr_ip = array_column($user_dev, 'ip');
            }

            if (count($arr_dev) == 0 && !empty($params['device_id'])) {
                $ds = array(
                    'uid' => $uid,
                    'device_id' => $params['device_id'],
                    'time' => date('Y-m-d H:i:s')
                );
                $this->ci->user_device_model->add($ds);
            } else if (count($arr_dev) > 0 && !empty($params['device_id'])) {
                if (!in_array($params['device_id'], $arr_dev)) {
                    //冻结账号
                    $this->ci->user_model->freeze_user($uid);
                    return array('code' => '401', 'msg' => '当前登录设备不是您常用的设备，请通过手机快捷登录');
                }
            }

            if ($params['source'] == 'pc' || $params['source'] == 'wap') {
                $ip = $params['ip'];
                if (count($arr_ip) == 0 && !empty($ip)) {
                    $ds = array(
                        'uid' => $uid,
                        'ip' => $ip,
                        'time' => date('Y-m-d H:i:s')
                    );
                    $this->ci->user_device_model->add($ds);
                } else if (count($arr_ip) > 0 && !empty($ip)) {
                    if (!in_array($ip, $arr_ip)) {
                        $this->ci->user_model->freeze_user($uid);
                        return array('code' => '401', 'msg' => '当前登录不是您常用的地址，请通过手机快捷登录');
                    }
                }
            }
        }
        //黑名单验证
        // if($user_black = $this->ci->user_model->check_user_black($user['id'])){
        //     if($user_black['type']==1){
        //      $this->ci->load->library('fdaylog');
        //          $db_log = $this->ci->load->database('db_log', TRUE);
        //          $this->ci->fdaylog->add($db_log,'user_cherry_black',$params['mobile']);
        //         return array("code"=>"300","msg"=>"果园君发现您的账号为无效手机号，为保证您的购物体验请用有效手机号注册，敬请谅解。");
        //     }else{
        //         return array("code"=>"300","msg"=>"您的帐号可能存在安全隐患，暂时冻结，请联系客服处理!");
        //     }
        // }
        //重置登陆错误
        $this->ci->user_model->setLoginErrorNum($user['id'], 1);
        //fanli:返利参数，判断是否返利用户登陆
        if (!empty($params['fl_channel_id']) && !empty($params['fl_u_id']) && !empty($params['fl_tracking_code'])) {
            $fanli = array("fl_channel_id" => $params['fl_channel_id'],
                "fl_u_id" => $params['fl_u_id'],
                "fl_tracking_code" => $params['fl_tracking_code']
            );
            $user = array_merge($user, $fanli);
        }
        $this->ci->session->sess_expiration = $this->session_expiretime;

		$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
        $session_id = $this->ci->session->set_userdata($user);
        session_id($session_id);
        session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
        $_SESSION['user_detail'] = $user;
        session_write_close();

        //cart:未登陆客户端购物车物品
        $cartcount = 0;
        $this->ci->load->bll('cart', array('session_id' => $session_id));
        if ($carttmp = @json_decode($params['carttmp'], true)) {
            $this->ci->bll_cart->after_signin_regist($carttmp);
        }

        $cartcount = $this->ci->bll_cart->get_cart_count();
        $this->ci->user_model->add_connectid_region_id($session_id, $params['region_id']);

        //获取用户信息
        $user1 = $this->ci->user_model->getUser($uid);

        $response = array();
        $user_score = $this->ci->user_model->getUserScore($uid);
        $coupon_num = $this->ci->user_model->getCouponNum($uid, 0);
        $response = array_merge($user1, $user_score);
        $response['coupon_num'] = $coupon_num;

        $user_url = 'http://wapi.fruitday.com/appMarketing/userActive/' . $uid . '/' . $this->create_sign($uid);
        $response['qr_code'] = $user_url;
        $response['push_group'] = 'group' . substr($uid, -1, 1);

        //登录以后跳转页面
        switch ($params['code']) {
            case 1:
                $redirect_url = "http://huodong.fruitday.com/sale/shake/wechat.html?connect_id=" . $session_id;
                break;
            case 2:
                $codeuid = base64_encode('2015z' . $uid);
                $redirect_url = "http://huodong.fruitday.com/sale/bill160127/wechat.html?connect_id=" . $codeuid;
                break;
            default:
                $redirect_url = '1';
        }

        if ($user['money'] > 0 && is_mobile($user['mobile'])) {
            //调用通知start
            $this->ci->load->library("notifyv1");

            $params = [
                "mobile" => $user['mobile'],
                "message" => "尊敬的用户：您的帐号在" . date("Y-m-d H:i:s") . "登录，为了确保账号安全，非本人操作或授权操作，请致电400-720-0770",
            ];
            // $this->ci->notifyv1->send('sms','send',$params);
            //调用通知end
        }

        //更新密码
        if ($users[0]['chkpass'] != '1') {
            $update_where = array(
                'id' => $uid
            );
            $update_data = array(
                'chkpass' => '1',
                'password' => $newPwd,
            );
            $this->ci->user_model->updateUser($update_where, $update_data);
        }


        return array('connect_id' => $session_id, 'cartcount' => $cartcount, 'userinfo' => $response, 'redirect_url' => $redirect_url);


    }

    /*
     * 发送注册验证码
     */

    public function sendPhoneTicket($params) {
        $code = rand_code();
        $this->ci->session->sess_expiration = $this->session_expiretime;
        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码错误');
        }

        if ($this->ci->user_model->check_user_exist($params['mobile'])) {
            return array('code' => '300', 'msg' => '该手机号已经注册过了');
        }

        $this->ci->load->model("Sms_log_model", "sms_log");
        $this->ci->sms_log->ip = "::1";
        $this->ci->sms_log->mobile = $params['mobile'];
        $this->ci->sms_log->uid = 0;
        if ($this->ci->sms_log->checkSend()) {

        } else {
            return array('code' => '300', 'msg' => '请勿重复操作发送验证码，如需帮助请联系客服');
        }
        // $this->ci->load->library('sms');
        $data = array('register_verification_code' => md5($params['mobile'] . $code));
        // $this->ci->load->model("jobs_model");
        $job['mobile'] = $params['mobile'];

        if ($params['source'] == 'wap')
            $defaultContent = '您的验证码为：' . $code . '，为保证验证安全，请您在半个小时之内验证。';
        else
            $defaultContent = '您的验证码为：' . $code . '，为保证验证安全，请在半个小时之内验证。';

        $this->ci->load->model('sms_template');
        $sms_template = $this->ci->sms_template;
        //$smsContent = $this->ci->sms_template->getSmsTemplate($sms_template::_SMS_MOBILE_SECURITY_CODE, array('security_code' => $code));
        $job['text'] = $smsContent ? $smsContent : $defaultContent;
        // $this->ci->sms->fastSend($job['mobile'], $job['text']);

        //调用通知start
        //调用通知start
        $this->ci->load->library("notifyv1");

        $params = [
            "mobile" => $job['mobile'],
            "message" => $job['text'],
        ];
        $this->ci->notifyv1->send('sms', 'send', $params);
        //调用通知end
        //调用通知end

        $session_id = $this->ci->session->set_userdata($data);

        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($session_id, $params['mobile']);
        $this->ci->ver_error_model->cleVer();
        $this->ci->ver_error_model->creVer();
        return array('connect_id' => $session_id);
    }

    /*
     * 验证注册
     */

    public function checkRegister($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码错误');
        }

        if ($this->ci->user_model->check_user_exist($params['mobile'])) {
            return array('code' => '300', 'msg' => '该手机号已经注册过了');
        } else {
            return array('code' => '200', 'msg' => 'succ');
        }
    }

    /*
     * 会员注册
     */

    public function register($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
            'connect_id' => array('required' => array('code' => '300', 'msg' => '请先发送验证码')),
            'password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码错误');
        }

        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($params['connect_id'], $params['mobile']);
        // $this->ci->load->model("session_model");
        // $session = $this->ci->session_model->get_session($params['connect_id']);
        $session = $this->ci->session->userdata;
        $userdata = unserialize($session['user_data']);
        $register_verification_code = md5($params['mobile'] . $params['register_verification_code']);
        $ver_error_res = $this->ci->ver_error_model->setVer();
        if ($ver_error_res == false) {
            return array('code' => '300', 'msg' => '短信验证码已过期，请重新发送');
        }
        if (!isset($userdata['register_verification_code']) || $userdata['register_verification_code'] != $register_verification_code) {
            return array('code' => '300', 'msg' => '验证码错误');
        }
        if ($this->ci->user_model->checkUserFreeze($params['mobile'])) {
            return array('code' => '300', 'msg' => '该手机号是注销账户，不能注册，请联系客服');
        }
        if ($this->ci->user_model->check_user_exist($params['mobile'])) {
            return array('code' => '300', 'msg' => '该手机号已经注册过了');
        } else {
            $ip = $this->get_real_ip();
//          if ($this->ci->user_model->check_user_ip($ip)) {
//              return array('code' => '300', 'msg' => '注册失败，请联系客服');
//          }
            $randcode = rand_code(8);
            $invite_code = tag_code(microtime() . $randcode);

            $userPassWord = $this->ci->passmd5->userPwd($params['password']);

            if (empty($params['channel'])) {
                $params['channel'] = 'web';
            }

            $user_insert_data = array(
                "username" => $params['mobile'],
                "mobile" => $params['mobile'],
                "password" => $userPassWord,
                "mobile_status" => 1,
                "reg_time" => date("Y-m-d H:i:s"),
                "last_login_time" => date("Y-m-d H:i:s"),
                "invite_code" => $invite_code,
                "randcode" => $randcode,
                "reg_from" => $params['channel'],
                "chkpass" => '1',
            );

            $uid = $this->ci->user_model->addUser($user_insert_data);
            if (!$uid) {
                return array('code' => '300', 'msg' => '注册失败');
            }

            //account-safe
            if (!empty($params['device_id'])) {
                $ds = array(
                    'uid' => $uid,
                    'device_id' => $params['device_id'],
                    'time' => date('Y-m-d H:i:s')
                );
                $this->ci->user_device_model->add($ds);
            }

            if ($params['source'] == 'web') {
                $this->ci->user_model->addUserRegIP($ip, $uid, $params['mobile']);
            }

//          $score = $this->ci->user_model->score_rule("register");
//          $score['time'] = date("Y-m-d H:i:s");
//          $this->ci->user_model->add_score($uid, $score);

            //调用风控服务
            $this->riskServices($params['mobile'], 'reg');

            /* 注册成功活动事件start */
            $this->ci->user_model->wqbaby_active($params['mobile'], $uid);
            /* 注册成功活动事件end */

            $inviteUid = $this->ci->user_model->getInvite($params['mobile']);
//          $active_id = 1834; //没有推荐人
//          if ($inviteUid !== 0) {//有推荐人
//              $active_id = 1876;
//          }
//          /* 注册送赠品 start */
//          $this->ci->user_model->giveGift($uid, $active_id);
//          /* 注册送赠品 end */
//
//          /*
//           * 注册送优惠券 start
//           */
//          $cardList = array(
////                array('cardMoney' => 20, 'startTime' => date("Y-m-d"), 'endTime' => date("Y-m-d", strtotime("+3 day")), 'remarks' => 'app首单立减20元(' . date("n月d") . '日生效)', 'moneyLimit' => 99),
//              array('cardMoney' => 10, 'startTime' => date("Y-m-d", strtotime("+3 day")), 'endTime' => date("Y-m-d", strtotime("+10 day")), 'remarks' => '10元-新人大礼包40元现金券(' . date("n月d", strtotime("+3 day")) . '日生效)', 'moneyLimit' => 59),
//              array('cardMoney' => 10, 'startTime' => date("Y-m-d", strtotime("+10 day")), 'endTime' => date("Y-m-d", strtotime("+22 day")), 'remarks' => '10元-新人大礼包40元现金券(' . date("n月d", strtotime("+10 day")) . '日生效)', 'moneyLimit' => 78),
//              array('cardMoney' => 20, 'startTime' => date("Y-m-d", strtotime("+10 day")), 'endTime' => date("Y-m-d", strtotime("+22 day")), 'remarks' => '20元-新人大礼包40元现金券(' . date("n月d", strtotime("+10 day")) . '日生效)', 'moneyLimit' => 158),
//          );
//          $this->ci->user_model->sendCard($uid, $cardList);
//          /*
//           * 注册送优惠券 end
//           */
////            $this->ci->load->model("jobs_model");
////            $job['mobile'] = $params['mobile'];
//// //         $job['text'] = "感谢您注册天天果园会员，赠送您" . $score['jf'] . "积分，下单即可使用。";
////            $job['text'] = "亲爱的果友，感谢您成为天天果园会员，您的账户中已加入鲜果一份（新客注册礼）！";
////            $this->ci->jobs_model->add($job, "sms");
//
//          $smsText = '亲爱的果友，感谢您成为天天果园会员，您的账户中已放入59元新客大礼包，可到我的果园－优惠券中和赠品查看（新客注册礼）！';
//          //调用通知start
//          $this->ci->load->library("notify");
//          $type    = ["sms"];
//          $target  = [
//              ["mobile"=>$params['mobile']]
//          ];
//          $message = ["title" => "天天果园通知", "body" => $smsText];
//
//          $params = [
//              "source"  => "api",
//              "mode"    => "group",
//              "type"    => json_encode($type),
//              "target"  => json_encode($target),
//              "message" => json_encode($message),
//          ];
//
//          $this->ci->notify->send($params);
//          //调用通知end
            $this->sendNewUserPrize($uid, $params['mobile'], $inviteUid);
            $user = $this->ci->user_model->getUser($uid);
            if (isset($user['code'])) {
                return $user;
            }

            $this->ci->ver_error_model->setVer(1);

            if (isset($params['weixin_user_info'])) {
                $weixin_user_info = json_decode($params['weixin_user_info'], true);

                if (!empty($weixin_user_info)) {
                    $this->ci->load->model('weixin_model');
                    $this->ci->weixin_model->bind($uid, $weixin_user_info);
                }
            }

            /*
              $response = array();
              $user_score = $this->ci->user_model->getUserScore($user['id']);
              $coupon_num = $this->ci->user_model->getCouponNum($user['id'],0);
              $response = array_merge($user,$user_score);
              $response['coupon_num'] = $coupon_num;
             */
            $user['user_data'] = '';
			$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
            $session_id = $this->ci->session->set_userdata(array('user_data' => serialize($user)));
            session_id($session_id);
            session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
            $_SESSION['user_detail'] = $user;
            session_write_close();

            if ($carttmp = @json_decode($params['carttmp'], true)) {
                $this->ci->load->bll('cart', array('session_id' => $session['session_id'], 'terminal' => $this->terminal_arr[$params['source']]));
                $this->ci->bll_cart->after_signin_regist($carttmp);
            }
            return $user;
        }
    }


    /*
     * 风控服务：蚁盾评分
     */
    public function riskServices($mobile, $from) {
        $ci = &get_instance();
        $ci->load->library('ebuckler');
        $refresh = false;
        $params = array(
            'mobile' => $mobile,
            'from' => $from
        );

        $result = $ci->ebuckler->score($params, $refresh);
        return $result;
    }


    /*
     * 手机快捷登录
     */

    public function mobileLogin($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
            'register_verification_code' => array('required' => array('code' => '300', 'msg' => '请输入短信验证码')),
            'connect_id' => array('required' => array('code' => '300', 'msg' => '请先发送验证码')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码错误');
        }

        //app - 新老客
        $is_new_user = 0;

        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($params['connect_id'], $params['mobile']);
        $session = $this->ci->session->userdata;
        $userdata = unserialize($session['user_data']);
        $register_verification_code = md5($params['mobile'] . $params['register_verification_code']);
        $ver_error_res = $this->ci->ver_error_model->setVer();
        if ($ver_error_res == false) {
            return array('code' => '300', 'msg' => '短信验证码已过期，请重新发送');
        }
        if (!isset($userdata['verification_code']) || $userdata['verification_code'] != $register_verification_code) {
            return array('code' => '300', 'msg' => '验证码错误');
        }

        if ($this->ci->user_model->check_user_exist($params['mobile'])) {

            $user = $this->ci->user_model->getUser("", array('mobile' => $params['mobile']));
            if (isset($user['code'])) {
                return $user;
            }
            $this->ci->user_model->setLoginErrorNum($user['id'], 1);

        } else {
            if ($this->ci->user_model->checkUserFreeze($params['mobile'])) {
                return array('code' => '300', 'msg' => '该手机号是注销账户，不能注册，请联系客服');
            }
            $ip = $this->get_real_ip();
//          if ($this->ci->user_model->check_user_ip($ip)) {
//              return array('code' => '300', 'msg' => '注册失败，请联系客服');
//          }
            $randcode = rand_code(8);
            $invite_code = tag_code(microtime() . $randcode);
            $userPassWord = $this->ci->passmd5->userPwd($params['password']);

            if (empty($params['channel'])) {
                $params['channel'] = 'web';
            }

            $user_insert_data = array(
                "username" => $params['mobile'],
                "mobile" => $params['mobile'],
                "password" => $userPassWord,
                "mobile_status" => 1,
                "reg_time" => date("Y-m-d H:i:s"),
                "last_login_time" => date("Y-m-d H:i:s"),
                "invite_code" => $invite_code,
                "randcode" => $randcode,
                "reg_from" => $params['channel'],
                "how_know" => 1,
                "chkpass" => '1',
            );

            $uid = $this->ci->user_model->addUser($user_insert_data);
            if (!$uid) {
                return array('code' => '300', 'msg' => '注册失败');
            } else {
                //$sms_mobile = '尊敬的用户，您的初始密码为' . $invite_code . '，请勿告知他人。登陆后请尽快修改密码，如非本人操作请忽略。';
                //$this->sendMessage($sms_mobile, $params['mobile']);
            }

            //account-safe
            if (!empty($params['device_id'])) {
                $ds = array(
                    'uid' => $uid,
                    'device_id' => $params['device_id'],
                    'time' => date('Y-m-d H:i:s')
                );
                $this->ci->user_device_model->add($ds);
            }

            if (($params['source'] == 'pc' || $params['source'] == 'wap') && !empty($params['ip'])) {
                $ds = array(
                    'uid' => $uid,
                    'ip' => $params['ip'],
                    'time' => date('Y-m-d H:i:s')
                );
                $this->ci->user_device_model->add($ds);
            }

            if ($params['source'] == 'web') {
                $this->ci->user_model->addUserRegIP($ip, $uid, $params['mobile']);
            }


            /* 注册成功活动事件start */
            $this->ci->user_model->wqbaby_active($params['mobile'], $uid);
            /* 注册成功活动事件end */

            $inviteUid = $this->ci->user_model->getInvite($params['mobile']);
            $this->sendNewUserPrize($uid, $params['mobile'], $inviteUid);
            $user = $this->ci->user_model->getUser($uid);
            if (isset($user['code'])) {
                return $user;
            }

            $this->ci->ver_error_model->setVer(1);

            $is_new_user = 1;
        }


        $this->ci->session->sess_expiration = $this->session_expiretime;
		$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
        $session_id = $this->ci->session->set_userdata($user);
        session_id($session_id);
        session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
        $_SESSION['user_detail'] = $user;
        session_write_close();

        //cart:未登陆客户端购物车物品
        $cartcount = 0;
        $this->ci->load->bll('cart', array('session_id' => $session_id));
        if ($carttmp = @json_decode($params['carttmp'], true)) {
            $this->ci->bll_cart->after_signin_regist($carttmp);
        }

        $cartcount = $this->ci->bll_cart->get_cart_count();
        $this->ci->user_model->add_connectid_region_id($session_id, $params['region_id']);

        //获取用户信息


        $response = array();
        $user_score = $this->ci->user_model->getUserScore($uid);
        $coupon_num = $this->ci->user_model->getCouponNum($uid, 0);
        $response = array_merge($user, $user_score);
        $response['coupon_num'] = $coupon_num;

        $user_url = 'http://wapi.fruitday.com/appMarketing/userActive/' . $uid . '/' . $this->create_sign($uid);
        $response['qr_code'] = $user_url;
        $response['push_group'] = 'group' . substr($uid, -1, 1);

        //登录以后跳转页面
        switch ($params['code']) {
            case 1:
                $redirect_url = "http://huodong.fruitday.com/sale/shake/wechat.html?connect_id=" . $session_id;
                break;
            default:
                $redirect_url = '1';
        }

        //account-safe
        if (!empty($params['device_id'])) {
            $user_dev = $this->ci->user_device_model->dump(array('uid' => $user['id'], 'device_id' => $params['device_id']));
            if (empty($user_dev)) {
                $ds = array(
                    'uid' => $user['id'],
                    'device_id' => $params['device_id'],
                    'time' => date('Y-m-d H:i:s')
                );
                $this->ci->user_device_model->add($ds);
            }
        }

        if ($params['source'] == 'pc' || $params['source'] == 'wap') {
            $ip = $params['ip'];
            $user_ip = $this->ci->user_device_model->dump(array('uid' => $user['id'], 'ip' => $ip));
            if (!empty($ip) && empty($user_ip)) {
                $ds = array(
                    'uid' => $user['id'],
                    'ip' => $ip,
                    'time' => date('Y-m-d H:i:s')
                );
                $this->ci->user_device_model->add($ds);
            }
        }

        //解冻账号
        $this->ci->user_model->thaw_user($user['id']);

        return array('connect_id' => $session_id, 'cartcount' => $cartcount, 'userinfo' => $response, 'redirect_url' => $redirect_url, 'is_new_user' => $is_new_user);
    }

    /*
     * 手机绑定
     */

    public function bindMobile($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
            //'password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'ver_code_connect_id' => array('required' => array('code' => '300', 'msg' => '请先发送验证码')),
        );

        //pc pwd
        // if($params['source'] != 'pc')
        // {
        //     if(empty($params['password']))
        //     {
        //         return array('code' => '300', 'msg' => '密码不能为空');
        //     }
        // }

        if ($params['password'] == 'd41d8cd98f00b204e9800998ecf8427e') {
            $params['password'] = '';
        }
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码错误');
        }

        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        // $session =   $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }
        $userdata = unserialize($session['user_data']);

        // $ver_code_session = $this->ci->session_model->get_session($params['ver_code_connect_id']);
        $this->ci->session->sess_id = $params['ver_code_connect_id'];
        $this->ci->session->sess_read();
        $ver_code_session = $this->ci->session->userdata;
        $var_code = unserialize($ver_code_session['user_data']);
        $register_verification_code = md5($params['mobile'] . $params['register_verification_code']);

        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($params['ver_code_connect_id'], $params['mobile']);
        $ver_error_res = $this->ci->ver_error_model->setVer();
        if ($ver_error_res == false) {
            return array('code' => '300', 'msg' => '短信验证码已过期，请重新发送');
        }

        if (!isset($var_code['register_verification_code']) || $var_code['register_verification_code'] != $register_verification_code) {
            return array('code' => '300', 'msg' => '验证码错误');
        }
        $this->ci->ver_error_model->setVer(1);
        if ($this->ci->user_model->check_user_exist($params['mobile'])) {
            return array('code' => '300', 'msg' => '该手机号已绑定其他帐号');
        } else {
            if ($this->ci->user_model->check_has_bind($userdata['id'])) {
//              $score = $this->ci->user_model->score_rule("register");
//              $score['time'] = date("Y-m-d H:i:s");
//              $this->ci->user_model->add_score($userdata['id'], $score);
//              $succ_text = '手机绑定成功，赠送您' . $score['jf'] . '积分';

                $inviteUid = $this->ci->user_model->getInvite($params['mobile']);
                $this->sendNewUserPrize($userdata['id'], $params['mobile'], $inviteUid);
            }
            $succ_text = '手机绑定成功';

            $old_user = $this->ci->user_model->getUser($userdata['id']);

            $userPassWord = $this->ci->passmd5->userPwd($params['password']);

            $update_data = array(
                "mobile" => $params['mobile'],
                "mobile_status" => 1,
                "password" => $userPassWord,
                "chkpass" => '1',
            );

            //pc pwd
            if ($params['source'] == 'pc') {
                unset($update_data['password']);
                unset($update_data['chkpass']);
            }

            $update_where = array("id" => $userdata['id']);
            /* 注册成功活动事件start */
            $this->ci->user_model->wqbaby_active($params['mobile'], $userdata['id']);
            /* 注册成功活动事件end */
            $this->ci->user_model->updateUser($update_where, $update_data);

            //Log
            if ($params['source'] == 'pc') {
                $dataLog = array(
                    'id' => $userdata['id'],
                    'mobile' => 'from-' . $old_user['mobile'] . 'to-' . $params['mobile']
                );
                $this->insertLog($dataLog);
            }

            //account-safe
            $this->ci->load->library("notifyv1");
            $send_params = [
                "mobile" => $old_user['mobile'],
                "message" => "您的帐号绑定手机更改成功，为了确保账号安全，非本人操作或授权操作，请致电400-720-0770",
            ];
            $this->ci->notifyv1->send('sms', 'send', $send_params);

            return array('code' => '200', 'msg' => $succ_text);
        }
    }

    public function checkMobileAuth($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '手机不能为空')),
            'register_verification_code' => array('required' => array('code' => '300', 'msg' => '验证码不能为空')),
            'ver_code_connect_id' => array('required' => array('code' => '300', 'msg' => '请先发送验证码')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $this->ci->session->sess_id = $params['ver_code_connect_id'];
        $this->ci->session->sess_read();
        $ver_code_session = $this->ci->session->userdata;
        $var_code = unserialize($ver_code_session['user_data']);
        $register_verification_code = md5($params['mobile'] . $params['register_verification_code']);
        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($params['ver_code_connect_id'], $params['mobile']);
        $ver_error_res = $this->ci->ver_error_model->setVer();
        if ($ver_error_res == false) {
            return array('code' => '300', 'msg' => '短信验证码已过期，请重新发送');
        }
        if (!isset($var_code['verification_code']) || $var_code['verification_code'] != $register_verification_code) {
            return array('code' => '300', 'msg' => '验证码错误');
        }
        $this->ci->ver_error_model->setVer(1);
        return array('code' => '200', 'msg' => '验证码正确');
    }

    public function getMsgSetting($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $user_info = $this->ci->user_model->getUser($userdata['id']);
        $this->ci->load->driver('push_msg');
        $setting = $this->ci->push_msg->getwaysetting();


        foreach ($setting as $key => $value) {
            foreach ($value['ways'] as $k => $v) {
                $checked = ($v['value'] & $user_info['msgsetting']) > 0 ? true : false;
                if (($k == 'email' && !$user_info['email']) || ($k == 'sms' && !$user_info['mobile'])) $checked = false;
                $setting[$key]['ways'][$k]['checked'] = $checked;
            }
        }

        return array('code' => '200', 'setting' => $setting);
    }

    public function updateMsgSetting($params) {

        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $user_info = $this->ci->user_model->getUser($userdata['id']);
        $uid = $user_info['id'];

        $ms = 0;
        $setting = json_decode($params['setting'], true);

        foreach ($setting as $set) {
            $ms = $ms | $set;
        }
        $rs = $this->ci->user_model->update_msgsetting($uid, $ms);
        if ($rs) {
            return array('code' => '200', 'msg' => '短信配置修改成功');
        }

    }

    /*
     * 密码修改
     */

    public function password($params) {
        $required_fields = array(
            'old_password' => array('required' => array('code' => '300', 'msg' => '原密码不能为空')),
            'password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
            're_password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        // $session =   $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];

        $user = $this->ci->user_model->selectUser('password,chkpass', array('id' => $uid));

        if ($user['chkpass'] == '1') {
            $userPassWord = $this->ci->passmd5->userPwd($params['old_password']);
        } else {
            $userPassWord = $params['old_password'];
        }


        if ($userPassWord != $user['password']) {
            return array("code" => "300", "msg" => "原密码错误");
        }

        if ($params['password'] != $params['re_password']) {
            return array("code" => "300", "msg" => "密码和确认密码不一致");
        }

        $newPassWord = $this->ci->passmd5->userPwd($params['password']);
        $update_data = array(
            "password" => $newPassWord,
            "chkpass" => "1",
        );
        $update_where = array("id" => $uid);
        $update_result = $this->ci->user_model->updateUser($update_where, $update_data);
        if ($update_result) {
            $this->ci->user_model->setLoginErrorNum($uid, 1);
            return array("code" => "200", "msg" => "修改成功");
        } else {
            return array("code" => "300", "msg" => "新密码和原密码相同");
        }
    }

    /**
     * @api              {post} / 用户登出
     * @apiDescription   用户登出
     * @apiGroup         user
     * @apiName          signout
     *
     * @apiSuccess {String} connect_id 用户登录状态.
     *
     * @apiSampleRequest /api/test?service=user.signout
     */
    public function signout($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        // $this->ci->load->model("session_model");
        // $this->ci->session_model->remove_session($params['connect_id']);
        $this->ci->session->sess_destroy();
        session_id($params['connect_id']);
        session_start();
        session_destroy();
        session_write_close();
        
        return array('code' => '200', 'msg' => '退出成功');
    }

    /*
     * 用户验证码接口
     */

    public function sendVerCode($params) {
        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        // $session =   $this->ci->session_model->get_session($session_id);
        $code = rand_code();
        $this->ci->session->sess_expiration = $this->session_expiretime;
        if (!is_mobile($params['mobile'])) {
            //return array('code' => '300', 'msg' => '手机号码错误');
            $session = $this->ci->session->userdata;
            if (empty($session)) {
                return array('code' => '300', 'msg' => '手机号码错误');
            }

            $userdata = unserialize($session['user_data']);
            unset($userdata['user_data']);
            unset($userdata['connect_id']);

            if (!isset($userdata['id']) || $userdata['id'] == "") {
                return array('code' => '300', 'msg' => '手机号码错误');
            }
            $uid = $userdata['id'];
            $user = $this->ci->user_model->selectUser('mobile', array('id' => $uid));
            $params['mobile'] = $user['mobile'];
            if (!is_mobile($params['mobile'])) {
                return array('code' => '300', 'msg' => '手机号码错误');
            }
        }
        if ($params['use_case'] != 'mobileLogin') {
            if (!$this->ci->user_model->check_user_exist($params['mobile'])) {

                if($params['use_case'] == 'order' && !empty($params['connect_id']))
                {
                    $this->ci->load->library('login');
                    $this->ci->login->init($params['connect_id']);
                    $uid = $this->ci->login->get_uid();
                    if(empty($uid))
                    {
                        return array('code' => '300', 'msg' => '您输入的手机号没有注册过');
                    }
                }
                else
                {
                    return array('code' => '300', 'msg' => '您输入的手机号没有注册过');
                }
            }

            $session = $this->ci->session->userdata;
            if (!empty($session)) {
                $userdata = unserialize($session['user_data']);
                unset($userdata['user_data']);
                unset($userdata['connect_id']);

                if (isset($userdata['id']) && $userdata['id'] != "") {
                    $uid = $userdata['id'];
                    $user = $this->ci->user_model->selectUser('mobile', array('id' => $uid));
                    if ($params['mobile'] != $user['mobile']) {
                        $params['mobile'] = $user['mobile'];
                        // return array('code' => '300', 'msg' => '账号异常，请联系客服处理');
                    }
                }


            }


        }

        $this->ci->load->model("Sms_log_model", "sms_log");
        $this->ci->sms_log->ip = "::1";
        $this->ci->sms_log->mobile = $params['mobile'];
        $this->ci->sms_log->uid = 0;
        $limit_times = 5;
        if (isset($params['use_case']) && $params['use_case'] == 'order') {
            $limit_times = 30;
        }

        if ($this->ci->sms_log->checkSend($limit_times)) {

        } else {
            return array('code' => '300', 'msg' => '请勿重复操作发送验证码，如需帮助请联系客服');
        }

        // $this->ci->load->library('sms');
        $data = array('verification_code' => md5($params['mobile'] . $code));
        // $this->ci->load->model("jobs_model");
        $job['mobile'] = $params['mobile'];
        if ($params['source'] == 'wap')
            $defaultContent = '您的验证码为：' . $code . '，为保证验证安全，请您在半个小时之内进行验证。';
        else
            $defaultContent = '您的验证码为：' . $code . '，为保证验证安全，请在半个小时之内进行验证。';

        $this->ci->load->model('sms_template');
        $sms_template = $this->ci->sms_template;
        //$smsContent = $this->ci->sms_template->getSmsTemplate($sms_template::_SMS_MOBILE_SECURITY_CODE, array('security_code' => $code));
        $job['text'] = $smsContent ? $smsContent : $defaultContent;

        // $this->ci->sms->fastSend($job['mobile'], $job['text']);

        //调用通知start
        //调用通知start
        $this->ci->load->library("notifyv1");

        $params = [
            "mobile" => $job['mobile'],
            "message" => $job['text'],
        ];
        $this->ci->notifyv1->send('sms', 'send', $params);
        //调用通知end
        //调用通知end
// 		$data['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
        $session_id = $this->ci->session->set_userdata($data);
//         session_id($session_id);
//         session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
//         $_SESSION['user_detail'] = $data;
//         session_write_close();
        
        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($session_id, $params['mobile']);
        $this->ci->ver_error_model->cleVer();
        $this->ci->ver_error_model->creVer();
        return array('connect_id' => $session_id);
    }

    /*
     * 密码找回
     */

    public function forgetPasswd($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '300', 'msg' => '请先发送验证码')),
            'mobile' => array('required' => array('code' => '300', 'msg' => '手机号不能为空')),
            'password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
            're_password' => array('required' => array('code' => '300', 'msg' => '密码不能为空')),
            'verification_code' => array('required' => array('code' => '300', 'msg' => '验证码不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码错误');
        }

        $mobile = $params['mobile'];
        $new = $params['password'];
        $renew = $params['re_password'];
        if ($new != $renew) {
            return array("code" => "300", "msg" => "密码和确认密码不一致");
        }

        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($params['connect_id'], $params['mobile']);
        // $this->ci->load->model("session_model");
        // $session = $this->ci->session_model->get_session($params['connect_id']);
        $session = $this->ci->session->userdata;
        $userdata = unserialize($session['user_data']);
        $verification_code = md5($params['mobile'] . $params['verification_code']);
        $ver_error_res = $this->ci->ver_error_model->setVer();
        if ($ver_error_res == false) {
            return array('code' => '300', 'msg' => '短信验证码已过期，请重新发送');
        }
        if (!isset($userdata['verification_code']) || $userdata['verification_code'] != $verification_code) {
            return array('code' => '300', 'msg' => '验证码错误');
        }

        $userPassWord = $this->ci->passmd5->userPwd($new);

        $update_data = array(
            "password" => $userPassWord,
            "how_know" => 0,
            "chkpass" => "1",
        );
        $update_where = array(
            "mobile" => $mobile
        );
        $update_result = $this->ci->user_model->updateUser($update_where, $update_data);

        //重置登陆错误
        $where = array(
            "mobile" => $mobile,
        );
        $users = $this->ci->user_model->selectUsers("id", $where);
        $uid = $users[0]['id'];
        $this->ci->user_model->setLoginErrorNum($uid, 1);
        $this->ci->ver_error_model->setVer(1);
        return array("code" => "200", "msg" => "密码修改成功，请重新登录");
    }

    /**
     * @api              {post} / 用户信息
     * @apiDescription   用户登录
     * @apiGroup         user
     * @apiName          userInfo
     *
     * @apiSuccess {String} connect_id 用户登录状态.
     *
     * @apiSampleRequest /api/test?service=user.userInfo
     */
    public function userInfo($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $user = $this->ci->user_model->getUser($userdata['id']);
        if (isset($user['code'])) {
            return $user;
        }
        $user['mobile_status'] = empty($user['mobile']) ? "0" : "1";
        $response = array();
        $user_score = $this->ci->user_model->getUserScore($user['id']);
        $coupon_num = $this->ci->user_model->getCouponNum($user['id'], 0);
        $response = array_merge($user, $user_score);
        $response['coupon_num'] = $coupon_num;

        $user_url = 'http://m.fruitday.com/user/qrcode/' . $userdata['id'] . '/' . $this->create_sign($userdata['id']);
        $response['qr_code'] = $user_url;
        $response['push_group'] = 'group' . substr($user['id'], -1, 1);
        return $response;
    }

    /*
     * 用户积分列表
     */

    public function userScore($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        // $session =   $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $result = $this->ci->user_model->get_user_jf_list($uid, $limit, $offset);
        foreach ($result as $key => $value) {
            if (strstr($value['jf'], '-')) {
                $result[$key]['type'] = '2';
            } else {
                $result[$key]['jf'] = "+" . $value['jf'];
                $result[$key]['type'] = '1';
            }
            $result[$key]['time'] = date("Y-m-d", strtotime($value['time']));
        }
        return $result;
    }

    /*
     * 用户充值纪录
     */

    public function userTransaction($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        // $session =   $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $result = $this->ci->user_model->get_user_trade_list($uid, $limit, $offset, $params);
        if (!empty($result)) {
            foreach ($result as &$val) {
                if ($val['money'] >= 0) {
                    $val['type'] = 1;
                    $val['money'] = "+" . $val['money'] + $val['bonus'];
                } else {
                    $val['type'] = 2;
                }
                $val['time'] = date("Y-m-d", strtotime($val['time']));
            }
        }

        $allres = $this->ci->user_model->get_user_money($uid);
        $data = array('amount' => sprintf("%.2f", $allres[0]->amount), 'list' => $result);
        return $data;
    }

    /*
     * 获取用户优惠券列表
     */

    public function userCouponList($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        // $session =   $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }

        $uid = $userdata['id'];
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $used = isset($params['coupon_status']) ? $params['coupon_status'] : 0;
        if ($params['source_type']) {
            if ($params['source_type'] == '1') {
                $condition['maketing'] = 0;
            } elseif ($params['source_type'] == '2') {
                $condition['maketing'] = 1;
            }
        }
        if ($uid)
            $condition['uid'] = $uid;

        switch (strtolower($params['source'])) {
            case 'pc':
                $condition['promotion_type'] = array(1,3);
                $condition2['promotion_type'] = array(1,3);
                break;
            case 'app':
                $condition['promotion_type'] = array(1,3);
                $condition2['promotion_type'] = array(1,3);
                break;
            case 'wap':
                $condition['promotion_type'] = array(1,3);
                $condition2['promotion_type'] = array(1,3);
                break;
            case 'pos':
                $condition['promotion_type'] = array(2,3);
                $condition2['promotion_type'] = array(2,3);
                break;
            default:
                # code...
                break;
        }

        if ($used == 1) {
            //$condition['to_date >='] = date("Y-m-d");
            $condition['is_used'] = $used;
        } elseif ($used == 2) {
            $condition['is_used'] = 0;
            $condition['to_date <'] = date("Y-m-d");
        } else if ($used === '0') {
            $condition['to_date >='] = date("Y-m-d");
            $condition['is_used'] = $used;
        } else if ($used == 3) {
            $condition['to_date >='] = date("Y-m-d");
            $condition['is_used'] = 0;

            $condition2['uid'] = $uid;
            $condition2['to_date <'] = date("Y-m-d");
            $condition2['is_used'] = 0;
            $condition2['is_sent'] = 1;
            $result2 = $this->ci->user_model->get_user_coupon_list($condition2, 10, $offset);
        }
        $condition['is_sent'] = 1;
        $result = $this->ci->user_model->get_user_coupon_list($condition, $limit, $offset);

        $goods_money = $params['goods_money'] ? $params['goods_money'] : 0;
        $jf_money = $params['jf_money'] ? $params['jf_money'] : 0;
        $pay_discount = $params['pay_discount'] ? $params['pay_discount'] : 0;
        $new_results = array();
        $can_use = 0;
        $is_no__all_card = 0;
        switch ($params['source_type']) {
            case '0':
                # code...
                break;
            case '1':
                if ($result) {
                    $this->ci->load->model('cart_v2_model');
                    $this->ci->load->library('login');
                    $this->ci->login->init($params['connect_id']);
                    $uid = $this->ci->login->get_uid();
                    $user = $this->ci->login->get_user();

                    //cart -v3
                    $this->ci->load->bll('apicart');
                    $api_cart = array();
                    $api_cart['cart_id'] = $uid;
                    $api_cart['store_id_list'] = $params['store_id_list'];
                    $api_cart['user'] = $uid;
                    $api_cart['source'] = $params['source'];
                    $api_cart['version'] = $params['version'];
                    $api_cart['tms_region_type'] = $params['tms_region_type'];
                    $cart_info = $this->ci->bll_apicart->get($api_cart);

                    //cart -v2
                    //$store_id_list = explode(',',$params['store_id_list']);
                    //$cart = $this->ci->cart_v2_model->init($uid,$store_id_list,$user,$params['source'],$params['version']);
                    //$cart_obj = $cart->getProducts()->validate()->promo()->total()->count()->checkout();
                    //$json_cart = json_encode($cart_obj);
                    //$cart_info = json_decode($json_cart,true);

                    $buy_items = array();
                    foreach ($cart_info['products'] as $item) {
                        $buy_items[] = $item['product_id'];
                        if($item['card_limit'] == 1)
                        {
                            $is_no__all_card =1;
                        }
                    }
                    $this->ci->load->model('card_model');
                    foreach ($result as $key => $value) {
                        $card_can_use = $this->ci->card_model->card_can_use($value, $uid, $goods_money, $params['source'], 0, $pay_discount, 0);
                        if ($card_can_use[0] == 1) {
                            $can_use_product = array();
                            if ($value['product_id'] && $value['product_id'] != ',') {
                                $p_ids = explode(',', trim($value['product_id']));
                                $can_use_product = array_intersect($p_ids, $buy_items);
                                if ($can_use_product) {
                                    $value['can_not_use'] = 0;
                                    $new_results[] = $value;
                                    unset($result[$key]);
                                }
                            } else {
                                $value['can_not_use'] = 0;
                                $new_results[] = $value;
                                unset($result[$key]);
                            }
                        }
                    }
                }
                $can_use = 1;
                break;
            case '2':
                # code...
                break;
            default:
                # code...
                break;
        }
        if (!empty($new_results)) {
            $result = array_merge($new_results, $result);
        }
        if (!empty($result2)) {
            $result = array_merge($result, $result2);
        }
        if (empty($result)) {
            $result = array();
        } else {
            foreach ($result as $key => $value) {
                if ($value['to_date'] < date("Y-m-d")) {
                    $result[$key]['is_expired'] = 1;
                } else {
                    $result[$key]['is_expired'] = 0;
                }
                if (!isset($value['can_not_use']) && $can_use == 1) {
                    $result[$key]['can_not_use'] = 1;
                }
                if($is_no__all_card == 1 && empty($value['product_id']))
                {
                    $result[$key]['can_not_use'] = 1;
                }
            }
            $result = $this->getCouponUseRange($result); //todo by lusc
        }
        return $result;
    }


    /*
     * 获取用户优惠券列表 --  重构
     */
    public function userCouponNewList($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        if (!$this->ci->login->is_login()) {
            return array('code' => 400, 'msg' => '登录过期');
        }
        $uid = $this->ci->login->get_uid();

        $base_filter = array();
        switch (strtolower($params['source'])) {
            case 'pc':
                $base_filter['promotion_type'] = array(1,3);
                break;
            case 'app':
                $base_filter['promotion_type'] = array(1,3);
                break;
            case 'wap':
                $base_filter['promotion_type'] = array(1,3);
                break;
            case 'pos':
                $base_filter['promotion_type'] = array(2,3);
                break;
            default:
                # code...
                break;
        }

        $offset = 0;
        $limit = 100;

        //未使用
        $filter_NotUsed = array();
        $filter_NotUsed['uid'] = $uid;
        $filter_NotUsed['to_date >='] = date("Y-m-d");
        $filter_NotUsed['is_used'] = 0;
        $filter_NotUsed['is_sent'] = 1;
        $filter_NotUsed = array_merge($base_filter,$filter_NotUsed);
        $arrNotUsed = $this->ci->user_model->get_user_coupon_list($filter_NotUsed, $limit, $offset);

        //已使用
        $filter_Used = array();
        $filter_Used['uid'] = $uid;
        $filter_Used['is_used'] = 1;
        $filter_Used['is_sent'] = 1;
        $filter_Used['to_date >='] = date('Y-m-d H:i:s', strtotime('-1 month'));
        $filter_Used = array_merge($base_filter,$filter_Used);
        $arrUsed = $this->ci->user_model->get_user_coupon_list($filter_Used, $limit, $offset);

        //已过期
        $filter_Overdue = array();
        $filter_Overdue['uid'] = $uid;
        $filter_Overdue['to_date <'] = date("Y-m-d");
        $filter_Overdue['is_used'] = 0;
        $filter_Overdue['is_sent'] = 1;
        $filter_Overdue['time >='] = date('Y-m-d H:i:s', strtotime('-1 month'));
        $filter_Overdue = array_merge($base_filter,$filter_Overdue);
        $arrOverdue = $this->ci->user_model->get_user_coupon_list($filter_Overdue, $limit, $offset);

        //构建结构
        foreach ($arrNotUsed as $key => $value) {
            //即将生效
            if ($value['time'] > date('Y-m-d H:i:s')) {
                $arrNotUsed[$key]['will_operation'] = 1;
            } else {
                $arrNotUsed[$key]['will_operation'] = 0;
            }

            //即将过期
            if ($value['to_date'] <= date('Y-m-d H:i:s', strtotime('+1 days'))) {
                $arrNotUsed[$key]['will_expired'] = 1;
            } else {
                $arrNotUsed[$key]['will_expired'] = 0;
            }
        }

        //排序
        $arr_star = array();
        $arr_mod = array();
        $arr_end = array();
        foreach ($arrNotUsed as $k => $v) {
            if ($arrNotUsed[$k]['will_operation'] == 1) {
                array_push($arr_end, $arrNotUsed[$k]);
            } else if ($arrNotUsed[$k]['will_expired'] == 1) {
                array_push($arr_star, $arrNotUsed[$k]);
            } else {
                array_push($arr_mod, $arrNotUsed[$k]);
            }
        }
        $arr_not = array_merge($arr_star, $arr_mod);
        $arr_not = array_merge($arr_not, $arr_end);

        $arrNotUsed = $this->getCouponUseRange($arr_not);
        $arrUsed = $this->getCouponUseRange($arrUsed);
        $arrOverdue = $this->getCouponUseRange($arrOverdue);

        $data = array(
            'notused' => $arrNotUsed,
            'used' => $arrUsed,
            'overdue' => $arrOverdue
        );

        $result = array(
            'code' => 200,
            'msg' => '',
            'data' => $data
        );

        return $result;
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

    /**
     * 预存款充值
     *
     * @return void
     * @author
     * */
    public function deposit_recharge($uid, $money, $msg = '', $order_name) {
        if ($money <= 0)
            return;

        $this->ci->load->model('user_model');
        $affected_row = $this->ci->user_model->deposit_recharge($uid, $money);

        if ($affected_row) {
            $mtime = explode('.', microtime(true));
            $tradenumber = date("Ymdhis") . $mtime[1];
            $time = date("Y-m-d H:i:s");

            $trade = array(
                'uid' => $uid,
                'trade_number' => $tradenumber,
                'payment' => '账户余额充值',
                'money' => $money,
                'status' => $msg,
                'type' => 'income',
                'time' => $time,
                'has_deal' => '1',
                'order_name' => $order_name
            );

            $this->ci->load->model('trade_model');
            $this->ci->trade_model->insert($trade);
        }
    }

    /**
     * 预存款扣款
     *
     * @return void
     * @author
     * */
    public function deposit_charge($uid, $money, $msg = '', $order_name) {
        if ($money <= 0)
            return;

        $this->ci->load->model('user_model');
        $affected_row = $this->ci->user_model->deposit_charge($uid, $money);

        if ($affected_row) {
            $mtime = explode('.', microtime(true));
            $tradenumber = date("Ymdhis") . $mtime[1];
            $time = date("Y-m-d H:i:s");

            $trade = array(
                'uid' => $uid,
                'trade_number' => $tradenumber,
                'payment' => '账户余额扣款',
                'money' => '-' . $money,
                'status' => $msg,
                'type' => 'outlay',
                'time' => $time,
                'has_deal' => '1',
                'order_name' => $order_name
            );

            $this->ci->load->model('trade_model');
            $this->ci->trade_model->insert($trade);
        }
    }

    /**
     * 退积分
     *
     * @return void
     * @author
     * */
    public function return_score($uid, $score, $msg = '', $type = '活动') {
        if (!$score)
            return false;

        $jf = array(
            'jf' => $score,
            'reason' => $msg,
            'time' => date("Y-m-d H:i:s"),
            'uid' => $uid,
            'type' => $type,
        );

        $this->ci->load->model('user_jf_model');
        $this->ci->load->model('user_model');
        $insert_id = $this->ci->user_jf_model->insert($jf);
        $this->ci->user_model->updateJf($uid, $score, 1);
        return $insert_id ? true : false;
    }

    /**
     * 升级勋章
     *
     * @return void
     * @author
     * */
    public function upgrade_badge($uid, $order_name = '') {
        $this->ci->load->model('user_model');
        $user_badge = $this->ci->user_model->get_user_badge($uid);

        $upgrade = false;
        if (!$user_badge || !in_array('1', (array)$user_badge)) {
            $this->ci->load->model('order_model');
            $order = $this->ci->order_model->dump(array('order_name' => $order_name));

            if ($order['money'] >= 1000) {
                $upgrade = true;

                $badge_info = "订单" . $order_name . "单笔购买满1000,于" . date('Y-m-d H:i:s') . "获得鲜果达人勋章";
            } else {
                $total = $this->ci->db->select_sum('money', 'm')
                    ->from('order')
                    ->where(array('operation_id' => '3', 'uid' => $uid))
                    ->get()
                    ->row_array();
                if ($total['m'] >= 2000) {
                    $upgrade = true;
                    $badge_info = "订单" . $order_name . "之前购满2000,于" . date('Y-m-d H:i:s') . "获得鲜果达人勋章";
                }
            }
        }

        if ($upgrade) {
            $badge = array(1);

            $this->ci->db->update('user', array('badge' => serialize($badge), 'badge_info' => $badge_info), array('id' => $uid), 1);

            $user_jf = $this->ci->db->select_sum('jf', 'jf')
                ->from('user_jf')
                ->where('uid', $uid)
                ->get()
                ->row_array();

            $user = $this->ci->db->select('mobile')
                ->from('user')
                ->where('id', $uid)
                ->get()
                ->row_array();

            if ($user['mobile']) {
                $points = round($user_jf['jf'] / 100, 2);

                $defaultContent = sprintf('恭喜您已获得天天果园“鲜果达人”勋章，购物可享受双倍积分福利，目前您的积分余额为%s。更多积分活动请关注fruitday.com', $points);

                $this->ci->load->model('sms_template');
                $sms_template = $this->ci->sms_template;
                $smsContent = $this->ci->sms_template->getSmsTemplate($sms_template::_SMS_MEMBER_LEVEL, array('amount' => $points));

                $smsContent = $smsContent ? $smsContent : $defaultContent;

                $this->ci->load->model("jobs_model");

                $job = array(
                    'mobile' => $user['mobile'],
                    'text' => $smsContent,
                );
                $this->ci->jobs_model->add($job, "sms");
            }
        }

        return;
    }

    /* 联合登陆 */

    public function oAuthSignin($params) {
        $required_fields = array(
            'open_user_id' => array('required' => array('code' => '500', 'msg' => 'open_user_id can not be null')),
            'signin_channel' => array('required' => array('code' => '500', 'msg' => 'channel can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $open_user_id = isset($params['open_user_id']) ? $params['open_user_id'] : "";
        $user_name = isset($params['user_name']) ? $params['user_name'] : "";
        $channel = isset($params['signin_channel']) ? $params['signin_channel'] : "0";

        $fanlidata = array();
        if (!empty($params['fl_channel_id'])) {
            $fanlidata = array("fl_channel_id" => $params['fl_channel_id'],
                "fl_u_id" => $params['fl_u_id'],
                "fl_tracking_code" => $params['fl_tracking_code']
            );
        }
        return $this->do_sso_signin($open_user_id, $user_name, $channel, $params['source'], $fanlidata, $params['user_photo']);
    }

    /* 联合登录操作 */

    function do_sso_signin($open_user_id, $user_name, $channel = 0, $source, $fanlidata = array(), $user_photo) {
        $user = $this->ci->user_model->selectUser('id', array('usersafekey' => $open_user_id));

        //app - 新老客
        $is_new_user = 0;

        switch ($source) {
            case 'pc':
                $login_type = 3;
                break;
            case 'wap':
                $login_type = 1;
                break;
            case 'app':
                $login_type = 2;
                break;
            default:
                $login_type = 3;
                break;
        }
        if (!empty($user)) {//联合登陆会员存在
            $uid = $user['id'];
            $condition = array(
                "usersafekey" => $open_user_id
            );
            $update_data = array();
            $update_data['last_login_time'] = date("Y-m-d H:i:s");
            $user_tmp = $this->ci->user_model->getUser("", $condition);
            if (!empty($user_name) && empty($user_tmp['username'])) {
                $update_data['username'] = $user_name;
            }
            if (!empty($user_photo) && empty($user_tmp['userface'])) {
                $user_head['big'] = $user_photo;
                $user_head['middle'] = $user_photo;
                $user_head['small'] = $user_photo;
                $user_head = serialize($user_head);
                $update_data['user_head'] = $user_head;
            }

            $this->ci->user_model->updateUser($condition, $update_data);

            $user = $this->ci->user_model->getUser("", $condition);
            if (isset($user['code'])) {
                return $user;
            }

            //获取用户信息
            $user1 = $this->ci->user_model->getUser($uid);

            $response = array();
            $user_score = $this->ci->user_model->getUserScore($uid);
            $coupon_num = $this->ci->user_model->getCouponNum($uid, 0);
            $response = array_merge($user1, $user_score);
            $response['coupon_num'] = $coupon_num;

            $user_url = 'http://wapi.fruitday.com/appMarketing/userActive/' . $uid . '/' . $this->create_sign($uid);
            $response['qr_code'] = $user_url;
            $response['push_group'] = 'group' . substr($uid, -1, 1);
            //获取用户信息end

        } else {
            if (in_array($channel, array(1, 2, 3, 4, 5))) {// 登陆渠道(1:新浪、2:qq、3:支付宝、4:微信 5小米 6返利 )
                return array('code' => '300', 'msg' => '快捷登录功能目前只提供老用户登录服务，请您用手机注册，谢谢');
            }


            $userPassWord = $this->ci->passmd5->userPwd("LwShCj_12!@12_Ljy_*&^" . time() . rand(0, 99999));

            $ssl_username = empty($user_name) ? $this->getUnionUserName($channel) : $user_name;
            $userfield = array('email' => $open_user_id . '@fruitday.com', 'username' => $ssl_username, 'password' => $userPassWord, 'chkpass' => '1', 'usersafekey' => $open_user_id, 'reg_time' => date("Y-m-d H:i:s"), 'last_time' => date("Y-m-d H:i:s"), 'reg_from' => $source, 'mobile_status' => 0);
            if (!empty($user_photo)) {
                $user_head['big'] = $user_photo;
                $user_head['middle'] = $user_photo;
                $user_head['small'] = $user_photo;
                $user_head = serialize($user_head);
                $userfield ['user_head'] = $user_head;
            }
            $uid = $this->ci->user_model->addUser($userfield);
            $user = $this->ci->user_model->getUser("", array('id' => $uid));
            if (isset($user['code'])) {
                return $user;
            }

            //获取用户信息start
            $user1 = $this->ci->user_model->getUser($uid);

            $response = array();
            $user_score = $this->ci->user_model->getUserScore($uid);
            $coupon_num = $this->ci->user_model->getCouponNum($uid, 0);
            $response = array_merge($user1, $user_score);
            $response['coupon_num'] = $coupon_num;

            $user_url = 'http://wapi.fruitday.com/appMarketing/userActive/' . $uid . '/' . $this->create_sign($uid);
            $response['qr_code'] = $user_url;
            $response['push_group'] = 'group' . substr($uid, -1, 1);
            //获取用户信息end

            $is_new_user = 1;
        }
        $this->ci->session->sess_expiration = $this->session_expiretime;
        $user = empty($fanlidata) ? $user : array_merge($user, $fanlidata);
		$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
        $session_id = $this->ci->session->set_userdata($user);
        session_id($session_id);
        session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
        $_SESSION['user_detail'] = $user;
        session_write_close();
        
        // $this->ci->load->model('ltg');
        // $ltg_data = array('uid'=>$user['id'],'type'=>$login_type,'channel'=>$channel);
        // $this->ci->ltg->insLtg($ltg_data);
        // $is_first_login = $this->is_first_login($user['id']);
        // if($is_first_login){
        //     return array('connect_id'=>$session_id,'msg'=>'恭喜您第一次登陆天天果园app，获得美国樱桃一份，请前往[我的果园]领取赠品。');
        // }else{
        return array('connect_id' => $session_id, 'userinfo' => $response, 'is_new_user' => $is_new_user);
        // }
    }

    //生成用户名为空的联合登陆用户名
    private function getUnionUserName($channel) {
        //(1|2|3|4|5|6，1:新浪，2:qq，3:支付宝，4:微信, 5小米 ,6返利)
        $username = "";
        switch ($channel) {
            case 1:
                $username = uniqid("sina");
                break;
            case 2:
                $username = uniqid("qq");
                break;
            case 3:
                $username = '支付宝帐号'; //uniqid("taobao");
                break;
            case 4:
                $username = uniqid("weixin");
                break;
            case 5:
                $username = uniqid("xiaomi");
                break;
            case 6:
                $username = uniqid("fanli");
                break;
            default:
                $username = uniqid("fruit");
                break;
        }
        return $username;
    }

    /*
     * 企业帐户绑定
     */

    function bindAccount($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'enterprise_tag' => array('required' => array('code' => '300', 'msg' => '企业标识不能为空')),
            'name' => array('required' => array('code' => '300', 'msg' => '收货人不能为空')),
            'mobile' => array('required' => array('code' => '300', 'msg' => '收货手机不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码错误');
        }

        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $enter_result = $this->ci->user_model->get_enter_info($params['enterprise_tag']);

        if (empty($enter_result)) {
            return array('code' => '300', 'msg' => '企业标识错误');
        } else {
            $user_update_data = array(
                'enter_id' => $enter_result['id'],
                'enterprise_name' => $params['name'],
                'enterprise_mobile' => $params['mobile'],
            );
            $update_where = array("id" => $uid);
            $this->ci->user_model->updateUser($update_where, $user_update_data);
        }
        return array('code' => '200', 'msg' => '认证成功');
    }

    //摇一摇获奖记录
    function shake_history($params) {
        //必要参数验证start

        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
//          'type' => array('required' => array('code' => '500', 'msg' => 'type can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $limits = array('curr_page' => $page, 'page_size' => $limit);
        $field = "id,gift_name,gift_type,gift_price_id,gift_product_id,gift_activity_url,time";

        return $this->ci->user_model->get_shake_history($field, $limits, $uid);
    }

    //积分换摇一摇次数
    function shake_exchange($params) {
        //必要参数验证start

        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
//          'type' => array('required' => array('code' => '500', 'msg' => 'type can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        return $this->ci->user_model->exchange_score($uid, $this->score2change);
    }

    function shake_shake($params) {
        //必要参数验证start

        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'type' => array('required' => array('code' => '500', 'msg' => 'type can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end


        $date = date("Y-m-d");
        if ($params['version'] > '2.3.0') {
            //新版
            $is_version_up = true;
            $exchange_rule = $this->exchange_rule;
            $extra_shake_num = $this->ci->user_model->get_extra_shake_num($uid, $date);
        } else {
            $is_version_up = false;
            $extra_shake_num = 0;
            //旧版
        }

        $is_black = $this->ci->user_model->is_shake_black($uid);

        $user_result = $this->ci->user_model->selectUser('user_rank', array('id' => $uid));
        $user_rank = $user_result['user_rank'];
        $user_rank_arr = $this->ci->config->item('user_rank');
        $level = $user_rank_arr['level'];
        $shake_num = $level[$user_rank]['shake_num'] + $extra_shake_num;//摇一摇每天的上限加上积分换取的次数

//      if (in_array($uid, array("613870", "810503", "327188"))) {
//          $shake_num = 1000;
//      }
        $region_id = $params["region_id"];

        $today_count = $this->ci->user_model->shake_record_count($uid, $date);

        $gift_left = $this->ci->user_model->search_gift_left($date);

        if (empty($gift_left)) {
            $this->ci->user_model->create_gift_left_V155($date);
            $gift_left = $this->ci->user_model->search_gift_left($date);
        }
        if ($params["type"] == 2) {
            $shake_records = $this->ci->user_model->get_shake_records($date);
            if (!empty($shake_records)) {
                $msg = array('code' => '200', 'msg' => '昨日有人抽中特等奖', 'uname' => $shake_records['uname'] ? $shake_records['uname'] : "匿名", 'gift_name' => $shake_records['gift_name'], 'exchange_rule' => $exchange_rule);
            } else {
                $msg = array('code' => '200', 'msg' => '昨日无人抽中特等奖。', 'exchange_rule' => $exchange_rule);
            }
            return $msg;
        } elseif ($params["type"] != 1) {//查询剩余次数
            $my_shake_num = ($shake_num - $today_count);
            $my_shake_num = $my_shake_num < 0 ? 0 : $my_shake_num;
            $msg = array('code' => '200', 'msg' => 'get successfully!', 'today_num' => $my_shake_num, 'exchange_rule' => $exchange_rule);
            return $msg;
        } else {
            if ($today_count >= $shake_num) {
                if ($is_version_up)
                    return array('code' => '200', 'msg' => '您今天已经用完' . $shake_num . '次摇奖机会，明日再来试试吧。', 'chance_left' => 0, 'exchange_rule' => $exchange_rule);
                else
                    return array('code' => '200', 'msg' => '您今天已经用完' . $shake_num . '次摇奖机会，明日再来试试吧。', 'chance_left' => 0);
            }
            $today_shaked_count = $this->ci->user_model->shake_record_count($uid, $date, "9");
            if ($today_count == ($shake_num - 1) && !$today_shaked_count && empty($is_black)) {//必中  除了屏蔽了的
                return $this->shake_win_V155($uid, $today_count, $gift_left, $date, $region_id, $shake_num, $is_version_up);
            } else {
                $rand1 = mt_rand(0, 9);
                switch ($today_count) {
                    case 0:
                        $success_p = 4;
                        break;
                    case 1:
                        $success_p = 5;
                        break;
                    case 2:
                        $success_p = 4;
                        break;
                    case 3:
                    case 4:
                        $success_p = 3;
                        break;  //4，5次中奖率调低
                    default:
                        $success_p = 5;
                }
//                echo $success_p."<br>";
//                $success_p = 10;
                if ($rand1 < $success_p && empty($is_black)) {//除了屏蔽了的
                    return $this->shake_win_V155($uid, $today_count, $gift_left, $date, $region_id, $shake_num, $is_version_up);
                } else {
                    return $this->shake_lose($uid, $today_count, $shake_num);
                }
            }
        }
    }

    public function shake_record_last($today_count, $total_times) {
        return $total_times - ($today_count) - 1;
    }

    private function shake_lose($uid, $today_count, $shake_num) {
        if ($this->ci->user_model->add_shake_record($uid, 9, '')) {
            $msg = array(
                "很遗憾，什么也没有摇中。",
                "啊偶，什么也没有摇中哦。",
                "啥也没摇中，再试一次吧。"
            );
            $exchange_rule = $this->exchange_rule;
            return array('code' => '200', 'msg' => $msg[mt_rand(0, 2)], 'chance_left' => $this->shake_record_last($today_count, $shake_num), 'is_win' => false, 'exchange_rule' => $exchange_rule);
        } else {
            return array('code' => '300', 'msg' => "系统忙请稍后再试。");
        }
    }

    private function getFloorAceil($config) {
        // $ceil_floor_arr = $this->memcached->get("ceil_floor_arr");
        if (empty($ceil_floor_arr)) {
            if (!empty($config)) {
                $ceil_floor_arr = $arr_huchi = array();
                foreach ($config as $k => $v) {
                    if ($k == 0) {
                        $floor = 0;
                        $ceil = $v['percent'];
                        $ceil_floor_arr[$v['id']] = array(
                            'floor' => $floor,
                            "ceil" => (int)$ceil
                        );
                    } else {
                        $floor = $ceil_floor_arr[($v['id'] - 1)]['ceil'];
                        $ceil = $floor + $v['percent'];
                        $ceil_floor_arr[$v['id']] = array(
                            'floor' => $floor,
                            "ceil" => $ceil
                        );
                    }
                    if ($v['mutex'] == 1) {
                        $arr_huchi[] = $v["id"];
                    }
                }
            }
            $arr_zuhe = array($ceil_floor_arr, $arr_huchi);
            // $this->memcached->set("ceil_floor_arr",$arr_zuhe,60*60*24*30);
        }
        return $arr_zuhe;
    }

    private function shake_gift($config, $uid, $date, $chance_left, $app_url, $today_count, $shake_num, $is_old, $arr_huchi, $is_version_up) {  //
//        if($uid==810503){
//            $config['type'] = 3;
//            $config['id'] = 3;
//            $config['type_value'] = 3276;
//        }
        //互斥逻辑
        if (is_array($arr_huchi) && !empty($arr_huchi)) {
            if (in_array($config['id'], $arr_huchi)) {
                $level_num = $this->ci->user_model->get_shake_level_num($uid, $date, $arr_huchi);
                if ($level_num > 0) {
                    return $this->shake_lose($uid, $today_count, $shake_num);
                }
            }
        } else {
            $level_num = $this->ci->user_model->get_shake_level_num($uid, $date, $config['id']);
            if ($level_num > 0) {
                return $this->shake_lose($uid, $today_count, $shake_num);
            }
        }
        switch ($config['type']) {
            case 1:
                $score = array(
                    'jf' => $config['type_value'], //设置摇一摇赠送的积分
                    "reason" => "摇一摇抽中积分",
                    'time' => date("Y-m-d H:i:s"),
                );
                $product_name = "{$score['jf']}积分";
                $share_msg = "我在天天果园摇到了{$product_name},你也来试试!每天摇一摇,总有收获!";
                $this->ci->user_model->add_score($uid, $score);
                $extra_config['type'] = $config['type'];
                break;
            case 2:
                $card_info = $this->ci->user_model->send_card($uid, $date, $config['type_value'], 3);
                if ($card_info) {
                    $product_name = $card_info[1];
                    $share_msg = "我在天天果园摇到了{$product_name},你也来试试!每天摇一摇,总有收获!";
                    $extra_config['type'] = $config['type'];
                } else
                    return array('code' => '300', 'msg' => '优惠券赠品设置有误!');
                break;
            case 3:
                if ($is_old) {
                    return $this->shake_lose($uid, $today_count, $shake_num);
                } else {
                    $special_pro = $config['type_value'];
                    $pro_info = $this->ci->user_model->get_special_pro($special_pro);
                    $my_pro_info = $this->data_fomat($pro_info);

                    $product_name = $pro_info['product']['product_name'];
                    $share_msg = "我在天天果园摇到了{$product_name},你也来试试!每天摇一摇,总有收获!";
                    $extra_config['type'] = $config['type'];
                    $extra_config['gift_price_id'] = $my_pro_info['price_id'];
                    $extra_config['gift_product_id'] = $config['type_value'];
                }
                break;
            case 4:
                $product_name = $this->ci->user_model->send_gift_V155($uid, $config['type_value']);
                if ($product_name) {
                    //扣除今日奖品库存
                    $share_msg = "我在天天果园摇到了特别大奖，{$product_name},你也来试试!每天摇一摇,总有收获!";
                    $extra_config['type'] = $config['type'];
                } else
                    return array('code' => '300', 'msg' => '赠品信息设置有误!');
                break;
            case 5:
                if ($is_version_up) {
                    $activity_url = $config['type_value'];
                    $share_msg = "我在天天果园摇到了那么多活动商品，你也来试试!每天摇一摇,总有收获!";
                    $extra_config['type'] = $config['type'];
                    $extra_config['gift_activity_url'] = $activity_url;
                    $product_name = $config['name'];
                } else {
                    return $this->shake_lose($uid, $today_count, $shake_num);
                }
                break;
            default:
                return array("code" => "300", 'msg' => '配置出错啦!');
        }
        //扣除今日奖品库存
        $level = 'level' . $config['id'] . '_num_left';
        $this->ci->user_model->update_gift_left($level, $date);
        $this->ci->user_model->add_shake_record($uid, $config['id'], $product_name, $extra_config);
        $exchange_rule = $this->exchange_rule;
        $return_arr = array('code' => '200', 'msg' => $product_name, 'chance_left' => $chance_left, 'is_win' => true, 'share_msg' => $share_msg, 'app_url' => $app_url, 'exchange_rule' => $exchange_rule);
        if (!empty($my_pro_info)) {
            $return_arr['pro_info'] = $my_pro_info;
        }
        if (!empty($activity_url)) {
            $return_arr['activity_url'] = $activity_url;
        }
        return $return_arr;
    }

    private function shake_win_V155($uid, $today_count, $gift_left, $date, $region_id, $shake_num, $is_version_up, $is_old = false) {
        $config = $this->ci->user_model->get_cache_shake_config();
        $arr_zuhe = $this->getFloorAceil($config);
        $ceil_floor_arr = $arr_zuhe[0];
        $arr_huchi = $arr_zuhe[1];

//
        $chance_left = $this->shake_record_last($today_count, $shake_num);
        $app_url = "http://www.fruitday.com/sale/tongyong/index.html";   //"http://www.fruitday.com/sale/wap-app/index.html";
        //先摇大奖
        //特惠商品开始
//        $special_pro = '3317';
//        $pro_info = json_decode($this->get_special_pro($special_pro));
//        $my_pro_info = $this->data_fomat($pro_info);
//        $json_pro_info = json_encode($my_pro_info);
//        return array('code'=>'200','msg'=>$pro_info->product->product_name,'chance_left'=>$chance_left,'is_win'=>true,'share_msg'=>"这个分享",'app_url'=>$app_url,'pro_info'=>$my_pro_info);
        //特惠商品结束
        $pro_num = mt_rand(0, 9999);
        if (!empty($config)) {
            foreach ($config as $k => $v) {
                $left_level_name = "level" . $v['id'] . "_num_left";
                if ($ceil_floor_arr[$v['id']]['floor'] <= $pro_num && $pro_num <= $ceil_floor_arr[$v['id']]['ceil']) {
                    if ($gift_left[$left_level_name] != 0)
                        return $this->shake_gift($v, $uid, $date, $chance_left, $app_url, $today_count, $shake_num, $is_old, $arr_huchi, $is_version_up);
                    else
                        return $this->shake_lose($uid, $today_count, $shake_num);
                }
            }
        } else {
            return $this->shake_lose($uid, $today_count, $shake_num);
        }
    }

    private function data_fomat($pro_info) {
        return array(
            "price" => $pro_info['items'][0]['price'],
            "pc_price" => $pro_info['items'][0]['pc_price'],
            "mem_lv" => $pro_info['items'][0]['mem_lv'],
            "mem_lv_price" => $pro_info['items'][0]['mem_lv_price'],
            "can_mem_buy" => $pro_info['items'][0]['can_mem_buy'],
            "stock" => $pro_info['items'][0]['stock'],
            "old_price" => $pro_info['items'][0]['old_price'],
            "volume" => $pro_info['items'][0]['volume'],
            "price_id" => $pro_info['items'][0]['id'],
            "product_name" => $pro_info['product']['product_name'],
            "summary" => $pro_info['product']['summary'],
            "thum_photo" => $pro_info['product']['thum_photo'],
            "photo" => $pro_info['product']['photo'],
            "id" => $pro_info['product']['id'],
            "yd" => $pro_info['product']['yd'],
            //"types"=>$pro_info['product']['types'],
            "types" => 'normal',
            "lack" => $pro_info['product']['lack'],
            "maxgifts" => $pro_info['product']['maxgifts'],
            "parent_id" => $pro_info['product']['parent_id'],
            "gift_photo" => $pro_info['product']['gift_photo'],
            "use_store" => $pro_info['product']['use_store'],
        );
    }

    function giftCardCharge($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'charge_code' => array('required' => array('code' => '300', 'msg' => '充值码不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $charge_code = strtolower(str_replace(" ", "", $params['charge_code']));

        $this->ci->load->library("PassMd5");
        $card_password = $this->ci->passmd5->md5Pass($charge_code);
        if (empty($card_password)) {
            return array('code' => '300', 'msg' => '您输入的充值码错误');
        }

        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = 'giftCardCharge_' . $card_password;

            $charging = $this->ci->memcached->get($mem_key);
            if ($charging) {
                return array('code' => '300', 'msg' => '请勿重复提交');
            }

            $this->ci->memcached->set($mem_key, $card_password, 1800);
        }

        $card = $this->ci->user_model->get_gift_card_info($card_password);

        if (empty($card)) {
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                $this->ci->memcached->delete($mem_key);
            }
            return array('code' => '300', 'msg' => '您输入的充值码错误');
        } else if ($card[0]->is_freeze == 1) {
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                $this->ci->memcached->delete($mem_key);
            }
            return array('code' => '300', 'msg' => '您输入的充值码已冻结');
        } else if ($card[0]->is_used == 1) {
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                $this->ci->memcached->delete($mem_key);
            }
            return array('code' => '300', 'msg' => '您输入的充值码已被使用');
        } else if ($card[0]->to_date < date("Y-m-d") && $card[0]->is_expire == '0') {
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                $this->ci->memcached->delete($mem_key);
            }
            return array('code' => '300', 'msg' => '您输入的充值码已过期');
        } else if ($card[0]->activation != 1) {
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                $this->ci->memcached->delete($mem_key);
            }
            return array('code' => '300', 'msg' => '您输入的充值码未激活');
        }

        $result = $this->router($uid, 0, 0, $charge_code, $params['region_id']);
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            $this->ci->memcached->delete($mem_key);
        }
        return $result;
    }

    private function router($uid, $way, $money = 0, $charge_code = '', $region_id = '106092') {
        $true_payment = array(0, 1, 7);
        if (!in_array($way, $true_payment)) {
            return array('code' => '300', 'msg' => '充值方式错误');
        }

        if ($money > 5000) {
            return array('code' => '300', 'msg' => '单次充值不能超过5000元');
        }
        if ($way != 0) {
            if (!is_numeric($money) || $money <= 0) {
                return array('code' => '300', 'msg' => '充值金额错误');
            }
        }

        switch ($way) {
            case 0:
                $result = $this->ci->user_model->via_acount($charge_code, $uid, $region_id);
                break;
            case 1:
                $result = $this->ci->user_model->via_alipay($money, $uid, $region_id);
                break;
            // case 2:
            //     $this->session->set_userdata("other",$other + 1);
            //     $this->via_hx($money);
            //     break;
            // case 3:
            //     $this->session->set_userdata("other",$other + 1);
            //     $this->via_ble($money);
            //     break;
            case 7:
                $result = $this->ci->user_model->via_weixin($money, $uid, $region_id);
                break;
        }
        return $result;
    }

    /*
     * 帐户充值
     */

    function userCharge($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'pay_type' => array('required' => array('code' => '300', 'msg' => '请选择充值方式')),
            'money' => array('required' => array('code' => '300', 'msg' => '请输入充值金额')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $region_id = (isset($params['region_id']) && $params['region_id'] > 0) ? $params['region_id'] : 106092;
        $result = $this->router($uid, $params['pay_type'], $params['money'], '', $region_id);
        return $result;
    }

    /*
     * 分享奖励接口
     */

    public function shareReward($params) {
//            echo 11;exit;
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'share_type' => array('required' => array('code' => '500', 'msg' => 'share type can not be null')),
            'share_channel' => array('required' => array('code' => '500', 'msg' => 'share channel can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];

        //获取session信息end

        /*
         * 调用活动api begin
         */
        $this->ci->load->library('active');
        $rs = $this->ci->active->curl_share_reward(array_merge($params, array('uid' => $uid)));
        if (!empty($rs)) {
            return $rs;
        }
        /*
         * 调用活动api end
         */

        $channel = $params['share_channel'];
        if (!in_array($channel, $this->share_channel)) {
            return array('code' => '500', 'msg' => 'channel error');
        }
        $share_type = isset($params['share_type']) ? $params['share_type'] : "1";
        if (!in_array($share_type, $this->share_type_arr)) {
            return array('code' => '500', 'msg' => 'share_type error');
        }

        /* b2cCard 后台配置的领券活动 start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3 || $channel == 1) && strpos($params['extra'], "2cCard/share")) {
            $test = substr($params['extra'], strripos($params['extra'], 'b2cCard/share', 0));
            $arr = explode("/", $test);
            $activeId = $arr[2];
            $ip = $this->get_real_ip();
            $this->ci->user_model->addIP($ip, $uid);
            if ($activeId == 383) {
                $this->ci->load->model('active_model');
                return $this->ci->active_model->share_send_gifts($uid, $activeId);
            }
            return $this->ci->user_model->active_card_by_b2c($uid, $activeId);
        }
        /* b2cCard 后台配置的领券活动 end */

        /* 717 分享送通用优惠券活动 start */
//      if( ($share_type==8 || $share_type==10) && ($channel==2 || $channel==3 || $channel == 1) && strpos($params['extra'], "azy0701")) {
//          $log_num_data = array(
//              'active_tag' => 'lazy0701_share',
//              'uid' => $uid,
//              'time' => date("Y-m-d H:i:s")
//          );
//          $this->ci->db->insert('share_num', $log_num_data);
//          return $this->ci->user_model->lazyCardToAll($uid);
//      }
        /* 717 分享送通用优惠券活动 end */

        /**
         * 柚子分享 start 20161009
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "20160928youzi")) {//新客
            $view_data = array(
                'type' => '20160928youzi',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => '20160928youzi',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            $this->ci->load->model('active_model');
            return $this->ci->active_model->share_send_gifts_tuan($uid, '20160928youzi', '蜜柚2个');
        }
        /**
         * 柚子分享 end 20161024
         */

        /**
         *  香蕉分享 start 20161012
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "20161011banana")) {//新客
            $view_data = array(
                'type' => '20161011banana',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => '20161011banana',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            $this->ci->load->model('active_model');
            return $this->ci->active_model->share_send_gifts_tuan($uid, '20161011banana', '香蕉两斤');
        }
        /**
         * 香蕉分享 end 20161024
         */


        /* b2cCard 翻一翻荔枝活动 start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3 || $channel == 1) && strpos($params['extra'], "160623_lottery")) {
            $view_data = array(
                'type' => '160623_lottery',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => '160623_lottery',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);
            $get_fanyifan = $this->ci->user_model->get_wqbaby_active($uid, 'fanpai', date('Ymd'));//获取翻到的东西
            if (empty($get_fanyifan)) {
                return array('code' => '200', 'msg' => '您还没有翻到赠品');
            }
            if ($get_fanyifan['is_add'] == 1) {
                return array('code' => '200', 'msg' => '您的账户中已经存在一份赠品喽，快去“我的赠品”中看看吧么么哒');
            }
            $get_fanyifan = $this->ci->user_model->sendUserGifts($uid, $get_fanyifan['card_number'], $get_fanyifan['id']);

            if ($get_fanyifan) {
                return array('code' => '200', 'msg' => '赠品已经您的账户中啦，快去“我的赠品”中领取么么哒！');
            } else {
                return array('code' => '200', 'msg' => '您的账户中已经存在一份赠品喽，快去“我的赠品”中看看吧么么哒!');
            }
        }
        /* b2cCard 翻一翻荔枝活动 end */


//      /* shake_v3 摇一摇 start */
//      if(strpos($params['extra'], "hake/zhong")) {//hake_v3/app //($share_type==8 || $share_type==10) && ($channel==2 || $channel==3 || $channel == 1) &&
//          if($this->ci->user_model->add_shake_exchange_v3($uid)){
//              return array('code' => '200', 'msg' =>"分享成功!");
//          }else{
//              return array('code' => '300', 'msg'=>"分享失败");
//          }
//      }
//      /* shake_v3 摇一摇 end */

        /* o2oCard 后台配置的领券活动 start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "2oCard/share")) {
            $test = substr($params['extra'], strripos($params['extra'], 'o2oCard/share', 0));
            $arr = explode("/", $test);
            $activeId = $arr[2];
            $ip = $this->get_real_ip();
            $this->ci->user_model->addIP($ip, $uid);
            return $this->ci->user_model->active_card_by_b2c($uid, $activeId, 1);//o2o $maketing 为1
        }
        /* o2oCard 后台配置的领券活动 end */

        /* 积分兑换分享领赠品start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3 || $channel == 1) && strpos($params['extra'], "cms/share")) {
            $tmp = substr($params['extra'], strripos($params['extra'], 'cms/share', 0));
            $arr = explode("/", $tmp);
            $tag = $arr[4];
            $cms_id = $arr[2];
            if ($cms_id && $tag) {
                return $this->ci->user_model->get_gifts_share($uid, $cms_id, $tag);
            }
        }
        /* 积分兑换分享领赠品 end */
        /*o2o团购活动start*/
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "2oTuan0112")) {
            $log_num_data = array(
                'active_tag' => 'o2o_tuan_0112',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('share_num', $log_num_data);
        }
        /*o2o团购活动end*/


//      /* ruby取名活动start */
//      if ($share_type == 8 && strpos($params['extra'], "rubyIntitle")) {
//          $bool = $this->ci->user_model->set_ruby_card($uid);
//          if (!$bool) {
//              return array('code' => '200', 'msg' => '您的优惠券账户中已有一张ruby优惠券!!');
//          }
//          return array('code' => '200', 'msg' => '恭喜你获得20元ruby樱桃优惠券,请尽快使用哦!!');
//      }
//      /* ruby取名活动end */


        /* 拜年礼 start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "ewGift160118")) {
            $view_data = array(
                'type' => 'ewGift160118share',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => 'ewGift160118',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            $share_result = $this->ci->user_model->gift_wqb_active($uid);
            $msg = $share_result['msg'];
            return array('code' => '200', 'msg' => $msg);
        }
        /* 拜年礼 end */

        /**
         * 荔枝答题 start
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "answerlichee0601")) {//新客
            $view_data = array(
                'type' => 'answerlichee0601',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => 'answerlichee0601',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            $share_result = $this->ci->user_model->answer_lichee($uid);
            return array('code' => '200', 'msg' => $share_result);
        }
        /**
         * 荔枝答题 end
         */
        /**
         * 教师节答题 start
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "teacherday160905")) {//新客
            $view_data = array(
                'type' => 'teacherday160905',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => 'teacherday160905',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            $share_result = $this->ci->user_model->answer_teacher($uid);
            return array('code' => '200', 'msg' => $share_result);
        }
        /**
         * 教师节答题 end
         */
        /**
         * 大转盘 start
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "rotor0608")) {
            $dateKey = date('ymd');
            $redisBase = 'active:rotor:';
            $redisShareKey = $redisBase . 'share:' . $uid . ':' . $dateKey; //是否分享过key
            $this->ci->load->library('phpredis');
            $redis = $this->ci->phpredis->getConn();
            $shareCount = $redis->get($redisShareKey);
            if ($shareCount > 0) {
                return array('code' => '200', 'msg' => '恭喜您分享成功！');
            }
            $redis->set($redisShareKey, 1);
            return array('code' => '200', 'msg' => '分享成功！恭喜您再获得一次抽奖机会，姿势已准备好，再来一次！');
        }
        /**
         * 大转盘 end
         */
        /**
         * 果园说投票 start
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "vote160711")) {
            $view_data = array(
                'type' => 'vote160711',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => 'vote160711',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            return array('code' => '200', 'msg' => '分享成功!');
        }
        /**
         * 果园说投票 end
         */
        /**
         * 618刮刮乐 start
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "turntable160614")) {//新客
            $view_data = array(
                'type' => 'turntable160614',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => 'turntable160614',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            $share_result = $this->ci->user_model->turntable160614($uid);
            return array('code' => '200', 'msg' => $share_result);
        }
        /**
         * 618刮刮乐 end
         */
//      /* 11.11优惠券start */
//      $time = date('Y-m-d H:i:s');
//      if($time>='2015-10-30 11:00:00'&&$time<='2016-01-31 11:00:00'){
//          if ($share_type == 10) {
//              $is_true = $this->ci->user_model->sendRedPacket($uid);
//              if ($is_true) {
//                  return array('code' => '200', 'msg' => "恭喜你获得" . $is_true['card_money'] . "元全场通用优惠券，有效期5天，请尽快使用哦~");
//              } else {
//                  return array('code' => '200', 'msg' => '分享成功!!!');
//              }
//          }
//      }
        /* 11.11优惠券end */

        /* o2oxmas start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "o2oxmas")) {
            $view_data = array(
                'type' => 'o2oxmasshare',
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => 'o2oxmas',
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            //$this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);
            $share_result = $this->ci->user_model->active_o2o_xmas($uid);
            if ($share_result !== false) {
                //$ip = $this->get_real_ip();
                //$this->ci->user_model->addIP($ip, $uid);
                $shar_desc = '恭喜您获得5个可用装饰金币，请尽快使用哦！';
                return array('code' => '200', 'msg' => $shar_desc);
            } else {
                return array('code' => '200', 'msg' => '分享成功！');
            }
        }
        /* o2oxmas end */

        /* o2ogoddess start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "o2oGoddess")) {
            $active_tag = '';
            if (strpos($params['extra'], "o2oGoddess/redirect2")) {
                $active_tag = 'o2ogoddess2';
            } elseif (strpos($params['extra'], "o2oGoddess/redirect3")) {
                $active_tag = 'o2ogoddess3';
            } elseif (strpos($params['extra'], "o2oGoddess/redirect4")) {
                $active_tag = 'o2ogoddess4';
            } else {
                $active_tag = 'o2ogoddess';
            }
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            //$this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);
            return array('code' => '200', 'msg' => '分享成功！');

        }
        /* o2ogoddess end */

        /* o2oRotary start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "o2oRotary")) {
            $active_tag = 'o2oRotary';
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            //$this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);
            return array('code' => '200', 'msg' => '分享成功！');

        }
        /* o2oRotary end */

        /* o2oInvite start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "o2oInvite")) {
            $active_tag = 'o2oInvite';
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            //$this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);
            return array('code' => '200', 'msg' => '分享成功！');

        }
        /* o2oInvite end */

        /* o2oShare start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "o2oShare")) {
            $active_tag = 'o2oShare';
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('share_num', $log_num_data);
            $rs = file_get_contents("http://huodong.fruitday.com/o2oShare/succ?connect_id={$params['connect_id']}");
            if ($rs) {
                return json_decode($rs, true);
            }
            return array('code' => '200', 'msg' => '分享成功！');
        }
        /* o2oShare end */

        /* 鲜果欢乐送 start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "appySend")) {
            $active_tag = 'happySend';
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('share_num', $log_num_data);


            $logArr = array(
                'extra' => $params['extra'],
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s"),
                'share_type' => $share_type,
                'channel' => $channel
            );
            $this->ci->db->insert('app_share_log', $logArr);

            $kw = $params['extra'];
            if ($params['platform'] == 'ANDROID') {
                $st = stripos($kw, '*');
                $ed = stripos($kw, '|');
                if (($st == false || $ed == false) || $st >= $ed) {
                    return array('code' => '200', 'msg' => "请选择一种商品");
                }
                $tag = substr($kw, ($st + 1), ($ed - $st - 1));
            } else {
                $st = stripos($kw, '=');//52
                $ed = stripos($kw, '&');
                if (($st == false || $ed == false) || $st >= $ed) {
                    return array('code' => '200', 'msg' => "请选择一种商品");
                }
                $tag = substr($kw, ($st + 1), ($ed - $st - 1));
            }
            $share_result = $this->ci->user_model->happySend($uid, $tag);
            $msg = $share_result['msg'];
            return array('code' => '200', 'msg' => $msg);
        }
        /* 鲜果欢乐送 end */
        /* 懒人节 start wyf */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "azyTwo")) {
            $active_tag = 'lazy1606';
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('share_num', $log_num_data);
            $share_result = $this->ci->user_model->lazyCard($uid);
            $msg = $share_result['msg'];
            return array('code' => '200', 'msg' => $msg);
        }
        /* 懒人节 end */
        /* 农场 start wyf */
        /*
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "arm1608")) {
            $active_tag = 'farm1608';
//
//            $logArr = array(
//                'extra' => $params['extra'],
//                'uid' => $uid,
//                'time' => date("Y-m-d H:i:s"),
//                'share_type'=>$share_type,
//                'channel'=>$channel
//            );
//            $this->ci->db->insert('app_share_log', $logArr);

            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('share_num', $log_num_data);

            $str=$params['extra'];
            if($params['platform']=='ANDROID'){
                $st =stripos($str,'*');
                $link_tag=substr($str,($st+1));
            }else{
                $st =stripos($str,'=');
                $link_tag=substr($str,($st+1));
            }

            $share_num_data = array(
                'link_tag' => $link_tag,
                'uid' => $uid,
                'type'=>1,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('farm_share', $share_num_data);

            $share_result = $this->ci->user_model->farmGift($uid);
            $msg = $share_result['msg'];
            return array('code' => '200', 'msg' => $msg);
        }
         * /

        /* 农场 end */

        /* emoji start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "emoji")) {
            $active_tag = 'emoji';
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            //$this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);
            return array('code' => '200', 'msg' => '分享成功！');

        }
        /* emoji end */

        /* emoji start */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "powertree")) {
            $active_tag = 'powertree';
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            //$this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);
            return array('code' => '200', 'msg' => '分享成功！');

        }
        /* emoji end */

        /**
         * 邀请送西梅
         */
        if (($share_type == 8 || $share_type == 10) && ($channel == 2 || $channel == 3) && strpos($params['extra'], "invite_prune")) {//新客
            $this->ci->db->from('order');
            $this->ci->db->where(array('order.uid' => $uid, 'order.order_status' => '1', 'order.operation_id !=' => '5', 'order.pay_status !=' => '0'));
            $type = ($this->ci->db->get()->num_rows() == 0) ? 1 : 2;
            if ($type == 1) {//新客
                $active_tag = 'invite_prune_new';
            } else {
                $active_tag = 'invite_prune';
            }
            $view_data = array(
                'type' => $active_tag,
                'tag' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $log_num_data = array(
                'active_tag' => $active_tag,
                'uid' => $uid,
                'time' => date("Y-m-d H:i:s")
            );
            $this->ci->db->insert('cherry_view', $view_data);
            $this->ci->db->insert('share_num', $log_num_data);

            $share_result = $this->ci->user_model->invite_prune($uid);
            return array('code' => '200', 'msg' => $share_result);
        }
        /**
         * ruby答题 end
         */

        $result = $this->ci->user_model->get_app_share_log($uid, $channel);

        if (!empty($result)) {
            return array('code' => '200', 'msg' => '分享成功!!', 'needAlert' => "0"); //needAlert  1为需要app系统弹框，"0"为不需要app系统弹框
        } else {
            $extra = isset($params['extra']) ? $params['extra'] : '';
            $insert_data = array(
                'uid' => $uid,
                'channel' => $channel,
                'time' => date("Y-m-d H:i:s"),
                'share_type' => $share_type,
                'extra' => $extra
            );
            $this->ci->user_model->add_app_share_log($insert_data);
            //营销todo
            return array('code' => '200', 'msg' => '分享成功!', 'needAlert' => "1");  //needAlert  1为需要app系统弹框，"0"为不需要app系统弹框
        }
    }

    /*
     * 特权列表
     */

    function privilegeList($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end

        $return_result = array();

        //会员等级
        $level_active = array();
        if (false) {
            $user_rank_info = $this->ci->user_model->selectUser('user_rank', array('id' => $uid));
            $level_active['privilege_type'] = "level";
            $level_active['active_banner'] = "http://cdn.fruitday.com/assets/images/user_rank/level_" . $user_rank_info['user_rank'] . ".jpg";
            $return_result[] = $level_active;
        }

        //果汁权限判断
        // $juice_active = array();
        // if ($active_desc = $this->ci->user_model->check_juice_active($uid)) {
        //  $juice_active['privilege_type'] = "juice";
        //  $juice_active['active_banner'] = "http://cdn.fruitday.com/assets/images/user_rank/juice3_" . $active_desc['user_rank']['user_rank'] . ".jpg";
        //  $juice_active['active_action'] = $active_desc['active_desc'];
        //  $user_url = 'http://wapi.fruitday.com/appMarketing/userActive/' . $uid . '/' . $this->create_sign($uid);
        //  $juice_active['qr_code'] = $user_url;
        //  $return_result[] = $juice_active;
        // }

//      //分享权限判断
//      $share_active = array();
//      if (false) {
//          $share_active['privilege_type'] = "share";
//          $share_active['active_banner'] = "http://cdn.fruitday.com/sale/1111app/images/1111%EF%BC%8Dapp-2_640x240.jpg";
//          if (date("Y-m-d H:i:s") > "2014-11-11 23:59:59") {
//              $share_active['active_banner'] = "http://cdn.fruitday.com/sale/11yingtao/images/640x240%20%E5%9B%A2%E8%B4%AD-01.jpg";
//          }
//          if (date("Y-m-d H:i:s") > "2014-11-18 22:00:00") {
//              $share_active['active_banner'] = "http://cdn.fruitday.com/sale/Celebrate/images/tqfx.jpg";
//          }
//          $share_active['active_action'] = 'appuser.userShareActive';
//          $return_result[] = $share_active;
//      }
//      $hongbao = array();
//      if (false) {//蓝莓团in_array($uid,array("613870"))||$this->can_lmt($uid)
//          $hongbao['privilege_type'] = "share";
//          $hongbao['active_banner'] = "http://cdn.fruitday.com/sale/lmt/images/lmggw.jpg"; //图待改
//          $hongbao['active_action'] = 'appuser.usershare1117'; //usershare1117
//          $return_result[] = $hongbao;
//      }


//      include_once('application/config/user_pri_arr.php');
//      //0310idlist推广banner
//      $uid_banner = array();
//      $uid_end_time = strtotime("2016-09-04 00:00:00");
////        if (strpos($config['cherry'],','.$uid.',')>0 && time() < $uid_end_time){
////            $uid_banner['privilege_type'] = "web";
////            $uid_banner['active_banner'] = "http://activecdnws.fruitday.com/sale/tree_601/images/cherry.jpg";
////            $uid_banner['url'] = 'http://huodong.fruitday.com/sale/tree_601/index_yt.html?connect_id='.$params['connect_id'];
////            $return_result[] = $uid_banner;
////        }
//
//      if(date("Y-m-d")>"2016-08-23"){
//          if (strpos($config['bj'],','.$uid.',')>0 && time() < $uid_end_time){
//              $uid_banner['privilege_type'] = "web";
//              $uid_banner['active_banner'] = "http://activecdnws.fruitday.com/sale/tree_823/images/bj1.jpg";
//              $uid_banner['url'] = 'http://huodong.fruitday.com/sale/tree_823/bj.html?connect_id='.$params['connect_id'];
//              $return_result[] = $uid_banner;
//          }
//          if (strpos($config['sh'],','.$uid.',')>0 && time() < $uid_end_time){
//              $uid_banner['privilege_type'] = "web";
//              $uid_banner['active_banner'] = "http://activecdnws.fruitday.com/sale/tree_823/images/sh1.jpg";
//              $uid_banner['url'] = 'http://huodong.fruitday.com/sale/tree_823/sh.html?connect_id='.$params['connect_id'];
//              $return_result[] = $uid_banner;
//          }
//          if (strpos($config['gz'],','.$uid.',')>0 && time() < $uid_end_time){
//              $uid_banner['privilege_type'] = "web";
//              $uid_banner['active_banner'] = "http://activecdnws.fruitday.com/sale/tree_823/images/gz1.jpg";
//              $uid_banner['url'] = 'http://huodong.fruitday.com/sale/tree_823/gz.html?connect_id='.$params['connect_id'];
//              $return_result[] = $uid_banner;
//          }
//      }

        $list = $this->ci->user_model->stockAll();
        $uid_banner = array();
        foreach ($list as $val) {
            if (strpos($val['user_list'], ',' . $uid . ',') !== FALSE) {
                $uid_banner['privilege_type'] = "web";
                $uid_banner['active_banner'] = $val['index_banner'];
                $uid_banner['url'] = 'http://awshuodong.fruitday.com/sale/stockup160901/index.html?connect_id=' . $params['connect_id'] . '&region_id=' . $params['region_id'] . '&tag=stockuptag' . $val['id'];
                $return_result[] = $uid_banner;
                break;
            }
        }

//
//      include_once('application/config/user_banner_20160321.php');
//      //0321idlist推广banner
//      $uid_banner2 = array();
//      $uid_end_time = strtotime("2016-04-09 00:00:00");
//      if (strpos($config['user_banner_0321_v0'],','.$uid.',')>0 && time() < $uid_end_time){
//          $uid_banner2['privilege_type'] = "web";
//          $uid_banner2['active_banner'] = "http://activecdn.fruitday.com/sale/invite/images/uid_banner3.jpg";
//          $uid_banner2['url'] = 'javascript:void(0)';
//          $return_result[] = $uid_banner2;
//      }elseif(strpos($config['user_banner_0321_v3'],','.$uid.',')>0 && time() < $uid_end_time){
//          $uid_banner2['privilege_type'] = "web";
//          $uid_banner2['active_banner'] = "http://activecdn.fruitday.com/sale/invite/images/uid_banner4.jpg";
//          $uid_banner2['url'] = 'javascript:void(0)';
//          $return_result[] = $uid_banner2;
//      }

        // $wegame = array();
        // $link_tag = $this->ci->user_model->has_packet($uid);
        // if ($link_tag&&date('Y-m-d')>='2015-08-10') {//in_array($uid,array("613870","810503"))&&$this->has_packet($uid)
        //  $wegame['privilege_type'] = "share";
        //  $wegame['active_banner'] = "http://activecdn.fruitday.com/sale/redpacket810/images/640_1.jpg";
        //  $wegame['url'] = "http://huodong.fruitday.com/sale/redpacket810/index.html?link_tag=".$link_tag; //"http://www.fruitday.com/sale/xiarent/join.html?tid={$tuan_id}";
        //  $wegame['active_action'] = 'user.wegame';
        //  $return_result[] = $wegame;
        // }

        //task_list  start
        $this->ci->load->model('banner_model');
        $this->ci->load->model('region_model');

        $params['region_id'] = $this->ci->region_model->get_province_info($params['region_id'])['id'];
        $banner_list = $this->ci->banner_model->get_banner_list($params);
        $time = date('Y-m-d H:i:s');
        foreach ($banner_list as $k => $value) {
            if (($time >= $value['start_time'] && $time <= $value['end_time']) || (empty($value['start_time']) && empty($value['end_time']))) {
                switch ($value['position']) {
                    case '62':
                        switch ($value['type']) {
                            case '6': //链接的时候
                                $url = $value['page_url'] . 'connect_id=' . $params['connect_id'] . '&region_id=' . $params['region_id'];
                                break;
                            case '20':
                                $url = $value['page_url'];
                                break;
                            default:
                                $url = false;
                                break;
                        }
                        if ($url) {
                            $return_result[] = array(
                                'privilege_type' => 'web',
                                'active_banner' => $value['photo'],
                                'url' => $url,
                            );
                        }
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }
        return $return_result;
    }

    public function wegame($params) { //樱桃团
        if (!isset($params['connect_id']) || empty($params['connect_id'])) {
            return array('code' => '500', 'msg' => 'connect id can not be null');
        }
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '500', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];

        $link_tag = $this->ci->user_model->has_packet($uid);

        $return_result = array(
            "share_url" => "http://huodong.fruitday.com/sale/redpacket810/index.html?link_tag=" . $link_tag,
            "share_title" => "【天天果园】喂！你有一个七夕礼物请注意查收！ ",
            "share_desc" => "【天天果园】喂！你有一个七夕礼物请注意查收！ ",
            "share_photo" => "http://activecdn.fruitday.com/sale/redpacket810/images/300_1.jpg",
            "share_alert" => "恭喜你获得优惠券分享特权，分享到朋友圈后，将随机获得全场通用优惠券1张，快戳分享~"
        );
        $return_result['code'] = '200';
        return $return_result;
    }

    private function create_sign($str) {
        $sign = md5(substr(md5($str . WAPI_API_SECRET), 0, -1) . 's');
        return $sign;
    }

    /*
     * 修改用户信息
     */

    public function upUserInfo($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end
        //获取session信息start
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        //获取session信息end
        if (mb_strlen($params['username']) > 20) {
            return array('code' => '300', 'msg' => '您填写的昵称过长，请控制在20个字以内');
        }
        $up_data = array();
        if (!empty($_FILES['photo']['size'])) {

            // 头像图片上传到七牛
            // 蔡昀辰 2016
            $img_arr = [
                "images" => [],
                "thumbs" => []
            ];

            // 载入配置和lib
            $this->ci->config->load("qiniu", true, true);
            $this->ci->load->library('Qiniu/qiniu', $this->ci->config->item('qiniu'));

            // 获取图片
            foreach ($_FILES as $photo) {
                $path = $photo['tmp_name'];
                $name = $photo['name'];
                $date = date("ymd", time());
                $prefix = 'img/user';
                $hash = str_replace('/tmp/php', '', $path);
                $key = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);
                if ($ret) {
                    $user_head['big'] = str_replace('img/', '', $key) . '-userbig';
                    $user_head['middle'] = str_replace('img/', '', $key) . '-usermiddle';
                    $user_head['small'] = str_replace('img/', '', $key) . '-usersmall';
                }
            }
            // $head_photopath = $this->head_photopath;
            // $config['upload_path'] = $this->ci->config->item('photo_base_path') . $head_photopath;
            // $config['allowed_types'] = 'gif|jpg|png';
            // $config['encrypt_name'] = true;
            // $this->ci->load->library('upload', $config);
            // if (!$this->ci->upload->do_upload('photo')) {
            //  return array('code' => '300', 'msg' => '上传失败');
            // }
            // $image_data = $this->ci->upload->data();
            // $curr_image_info = pathinfo($image_data['file_name']);
            // $this->ci->load->library('image_lib');
            // foreach ($this->userface_size as $k => $v) {
            //  $thumb_config = array();
            //  $thumb_image_info = $curr_image_info['filename'] . "_" . $v;
            //  $thumb_photo = $thumb_image_info . "." . $curr_image_info['extension'];
            //  $thumb_config['image_library'] = 'gd2';
            //  $thumb_config['source_image'] = $config['upload_path'] . "/" . $image_data['file_name'];
            //  $thumb_config['thumb_marker'] = "_" . $v;
            //  $thumb_config['create_thumb'] = TRUE;
            //  $thumb_config['maintain_ratio'] = TRUE;
            //  $thumb_config['width'] = $v;
            //  $thumb_config['height'] = $v;
            //  $this->ci->image_lib->initialize($thumb_config);
            //  if (!$this->ci->image_lib->resize()) {
            //      return array('code' => '300', 'msg' => '上传失败');
            //  }
            //  $user_head[$k] = $head_photopath . "/" . $thumb_photo;
            // }


            $up_data['user_head'] = serialize($user_head);
            $up_data['is_pic_tmp'] = 1;
        }
        if (!empty($params['username'])) {
            $up_data['username'] = strip_tags(trim($params['username']));
        }
        if (isset($params['sex'])) {
            $up_data['sex'] = trim($params['sex']);
        }
        if (!empty($params['birthday'])) {
            $up_data['birthday'] = strtotime($params['birthday']);
            $up_data['birthday_status'] = 1;
        }
        $last_birthday = $this->ci->user_model->getUserBirthday($uid);
        if (!empty($up_data)) {
            $update_where = array("id" => $uid);
            $this->ci->user_model->updateUser($update_where, $up_data);
            if ($last_birthday && $up_data['birthday'] && $up_data['birthday'] != $last_birthday) {
                $this->ci->user_model->setUserEditBirthdayLog($uid, $last_birthday);
            }
        }
        return array('code' => '200', 'msg' => '更新成功');
    }

    function welfare($params) {
        //必要参数验证start
        $required_fields = array(
            'name' => array('required' => array('code' => '300', 'msg' => '请填写联系人')),
            'mobile' => array('required' => array('code' => '300', 'msg' => '请填写联系人手机')),
            'company' => array('required' => array('code' => '300', 'msg' => '请填写公司名称')),
            'demand' => array('required' => array('code' => '300', 'msg' => '请填写您的需求')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        // if (!is_mobile($params['mobile'])) {
        //  return array('code' => '300', 'msg' => '手机号码错误');
        // }

        $name = strip_tags(trim($params['name']));
        $mobile = $params['mobile'];
        $company = strip_tags(trim($params['company']));
        $demand = strip_tags(trim($params['demand']));
        $address = isset($params['address']) ? strip_tags(trim($params['address'])) : '';

        $purchase = array('name' => $name, 'mobile' => $mobile, 'company' => $company, 'demand' => $demand, 'time' => date("Y-m-d H:i:s"), 'address' => $address);
        $res = $this->ci->user_model->add_welfare($purchase);
        if ($res) {
            return array('code' => '200', 'msg' => 'succ');
        } else {
            return array('code' => '300', 'msg' => '添加失败');
        }
    }


    /*回传格式ex.
     *  1.分享链接：$return_result = array(
                    "type" => 1,
                    "share_url" => "http://huodong.fruitday.com/sale/scratch/index.html",
                    "share_title" => "【天天果园-帝都专享】".$desc,
                    "share_desc" => "【天天果园-帝都专享】".$desc,
                    "share_photo" => "http://activecdn.fruitday.com/sale/scratch/images/300.jpg",
                    "share_alert" => "恭喜你获得刮刮乐分享特权，分享到朋友圈后，可领取随单赠品哦，快戳分享~"
                    );
                    $return_result['code'] = '200';
                    return $return_result;
        2.跳转到内嵌的页面:$return_result = array(
                    "type" => 2,
                    "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/index.html?link_tag=".$link_tag,
                    "share_alert" => "果园君感谢您的光顾，送您一次刮奖机会，试试运气吧！"
                );
                $return_result['code'] = '200';
                return $return_result;
        3.弹出文案:$return_result = array(
                    "type" => 3,
                    "share_alert" => "这是纯文字提醒"
                );
                $return_result['code'] = '200';
                return $return_result;
        4.无交互：return array();
        5.直接跳转页面：
                $return_result = array(
                    "type" => 4,
                    "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/index.html?link_tag=".$link_tag,
                );
                $return_result['code'] = '200';
                return $return_result;
        6.点击图片，跳转链接：
                $return_result = array(
                    "type" => 5,
                    "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/index.html?link_tag=".$link_tag,
                    "img_url"=>"http://imagews1.fruitday.com/images/product_pic/4394/1/1-370x370-4394-9Y2YD8RY.jpg"
                );
                $return_result['code'] = '200';
                return $return_result;

        7.点击图片分享
                $return_result = array(
                    "type" => 6,
                    "img_url"=>"http://imagews1.fruitday.com/images/product_pic/4394/1/1-370x370-4394-9Y2YD8RY.jpg"，
                    "share_url" => "http://huodong.fruitday.com/sale/scratch/index.html",
                    "share_title" => "【天天果园-帝都专享】".$desc,
                    "share_desc" => "【天天果园-帝都专享】".$desc,
                    "share_photo" => "http://activecdn.fruitday.com/sale/scratch/images/300.jpg",
                );
                $return_result['code'] = '200';
                return $return_result;
     */
    function userShareActive($params) {//（订单完成app会回调）  返回参数带type
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        if (!isset($params['connect_id']) || empty($params['connect_id'])) {
            return array('code' => '500', 'msg' => 'connect id can not be null');
        }
        $order_name = $params['order_name']; //不一定用到
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }

        //app新功能测试代码begin
//        $uid = $userdata['id'];
//        if(in_array($uid,array(327188,324872,613870,3439572))){
//            $return_result = array(
//                    "type" => 6,
//                    "img_url"=>"http://cdnws.fruitday.com/images/product_pic/4394/1/1-370x370-4394-9Y2YD8RY.jpg",
//                    "share_url" => "http://huodong.fruitday.com/sale/scratch/index.html",
//                    "share_title" => "点击图片分享测试",
//                    "share_desc" => "点击图片分享测试",
//                    "share_photo" => "http://activecdn.fruitday.com/sale/scratch/images/300.jpg",
//                );
//                $return_result['code'] = '200';
//                return $return_result;
//        }
        //app新功能测试代码end

        $time = date('Y-m-d H:i:s');
        $order_info = $this->ci->user_model->getOrderType($order_name);
        switch ($order_info['order_type']) {
            case 1:
                $start = "2016-11-28 00:00:00";
                $end = "2016-12-12 00:00:00";
//                $return_result = array(
//                    "type" => 2,
////                      "page_url" => "http://huodong.fruitday.com/sale/farm1608/index.html?connect_id=".$params['connect_id'],  .$params['region_id']
//                    "page_url"=>'http://awshuodong.fruitday.com/sale/farm1608/index.html?connect_id='.$params['connect_id'],
//                    "share_alert" => "恭喜您获得种子一枚,赶紧去农场种植吧,持续几天耕耘,最后就能收获水果赠品哦！"
//                );
                $order_money = $this->ci->user_model->getOrderMoney($order_name);
//
                if ($order_money['money'] >= 100 && $time >= $start && $time < $end && $order_info['type'] == 2) {
                    $return_result = array(
                        "type" => 5,
                        "page_url" => "http://awshuodong.fruitday.com/sale/rebate1611/double.html?connect_id" . $params['connect_id'],
                        "img_url" => "http://huodongcdnws.fruitday.com/sale/rebate1611/images/double12_01.jpg"
                    );
                } else {
                    if ($time < '2016-12-26 00:00:00') {
                        $return_result = array(
                            "type" => 2,
                            //                      "page_url" => "http://huodong.fruitday.com/sale/farm1608/index.html?connect_id=".$params['connect_id'],  .$params['region_id']
                            "page_url" => 'http://awshuodong.fruitday.com/sale/farm1608/index.html?connect_id=' . $params['connect_id'],
                            "share_alert" => "恭喜您获得种子一枚,赶紧去农场种植吧,持续几天耕耘,最后就能收获水果赠品哦！"
                        );
                    }

                }
                $return_result['code'] = '200';
                return $return_result;

//              $return_result = array(
//                  "type" => 5,
//                  "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/index.html",
//                  "img_url"=>"http://imagews1.fruitday.com/images/product_pic/4394/1/1-370x370-4394-9Y2YD8RY.jpg"
//              );
//              $return_result['code'] = '200';
//              return $return_result;
            case 3:
            case 4:
                //$result = $this->ci->user_model->o2o_today_buy_add($order_info['uid'],$order_name,$params['connect_id']);
                //return $result;
                $result = $this->ci->user_model->o2oSendRedPacket($order_info['uid'], $order_name);
                return $result;
            default:
                return array();
        }

        return array();
//      //todo 给邀请人加分
//
//      $return_result = array(
//                  "type" => 4,
//                  "page_url" => "http://huodong.fruitday.com/sale/invite/redpockets.html?",
//      );
//      $return_result['code'] = '200';
//      return $return_result;

        // $is_send = $this->ci->user_model->getKiwiCard($order_name);
        /*
        if($is_send&&$time<'2015-11-11'){
            $return_result = array(
                "type" => 3,
                "share_alert" => "恭喜您！获得天天果园双十一购物神券一张，购买佳沛新西兰绿奇异果10个装仅需19元包邮，11.11记得来哦~"
            );
            $return_result['code'] = '200';
            return $return_result;
        }
        if($time>='2015-10-30 11:00:00'&&$time<='2016-01-31 23:59:59'){
            return array();
            $link_tag = $this->ci->user_model->getRedPacket($order_name);
            if(!$link_tag){
                return array();
            }
            $bingo = mt_rand(0,4);
            $arr_dec = array(
                '一大波优惠券正在逼近，领一个成就你的诗和远方！',
                '免费领一份鲜果，你只需要一步，刮！',
                '免费有水（zhong）果（jiang）的刮刮乐原来真的存在！',
                '你来刮刮乐我就送鲜果，天天果园任性出了新高度！'
            );
            $desc = $arr_dec[$bingo];
            $return_result = array(
                "type" => 1,
                "share_url" => "http://huodong.fruitday.com/sale/redpacket1111/index.html?link_tag=".$link_tag,
                "share_title" => "【天天果园】一大波优惠券正在逼近，领一个成就你的诗和远方！",
                "share_desc" => "【天天果园】一大波优惠券正在逼近，领一个成就你的诗和远方！",
                "share_photo" => "http://activecdn.fruitday.com/sale/redpacket1111/images/300.jpg",
                "share_alert" => "恭喜你获得红包分享特权，分享到朋友圈后，可获得优惠券一张，快戳分享~"
            );
            $return_result['code'] = '200';
            return $return_result;
        }else{
            return array();//关闭后置刮刮乐
            $is_packet = $this->ci->user_model->getBlow($order_name);
            if($is_packet==1){
                return array();
            }elseif($is_packet==2){
                $type=1;
                $bingo = mt_rand(0,4);
                $arr_dec = array(
                    '人人都能领的鲜果，概率大得不像刮刮乐！',
                    '我刚在果园里刮出一份鲜果，不信？敢来刮吗！',
                    '免费领一份鲜果，你只需要一步，刮！',
                    '免费有水（zhong）果（jiang）的刮刮乐原来真的存在！',
                    '你来刮刮乐我就送鲜果，天天果园任性出了新高度！'
                );
                $desc = $arr_dec[$bingo];
            }elseif($is_packet==3){
                $type=2;
                $link_tag = urlencode($order_name."_".$userdata['id']);
            }elseif($is_packet==4){
                $type = 5;
            }elseif($is_packet==5){//sh
                $type = 6;
                $link_tag = urlencode($order_name."_".$userdata['id']);
            }elseif($is_packet==6){
                $type = 7;
                $link_tag = urlencode($order_name."_".$userdata['id']);
            }
        }
        // $type = $params['type'] ? $params['type'] : 1;
  //       $type = (string) $type;
        switch ($type) {//1朋友圈分享信息，2.跳转到html5页面，3.纯文字提示
            case 1:
//              if($now_date<'2015-08-01') {
                 $return_result = array(
                    "type" => $type,
                    "share_url" => "http://huodong.fruitday.com/sale/scratch/index.html",
                    "share_title" => "【天天果园-帝都专享】".$desc,
                    "share_desc" => "【天天果园-帝都专享】".$desc,
                    "share_photo" => "http://activecdn.fruitday.com/sale/scratch/images/300.jpg",
                    "share_alert" => "恭喜你获得刮刮乐分享特权，分享到朋友圈后，可领取随单赠品哦，快戳分享~"
                 );
//              }else{
//              $link_tag = $this->ci->user_model->getLink_tag();
//
//                  if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
//                      if (!$this->ci->memcached) {
//                          $this->ci->load->library('memcached');
//                      }
//                      $mem_key = 'share_link_cherry_728_' . $link_tag;
//                      $this->ci->memcached->set($mem_key, $link_tag);
//                  }
//                  $return_result = array(
//                      "type" => $type,
//                      "share_url" => "http://huodong.fruitday.com/sale/kiwi728/kiwi.html?link_tag=" . $link_tag,
//                      "share_title" => "【天天果园】抢超大优惠券，吃超大绿果，分享到朋友圈立得优惠券1张，买绿果最低只要1元包邮，快戳分享！",
//                      "share_desc" => "【天天果园】抢超大优惠券，吃超大绿果，分享到朋友圈立得优惠券1张，买绿果最低只要1元包邮，快戳分享！",
//                      "share_photo" => "http://activecdn.fruitday.com/sale/kiwi728/images/300.jpg",
//                      "share_alert" => "抢超大优惠券，吃超大绿果，分享到朋友圈立得优惠券1张，买绿果最低只要1元包邮，快戳分享！"
//                  );
////                }
                $return_result['code'] = '200';
//              $return_result = array();
                return $return_result;
                break;
            case 2:
//              return array();
                $return_result = array(
                    "type" => $type,
                    "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/index.html?link_tag=".$link_tag,
                    "share_alert" => "果园君感谢您的光顾，送您一次刮奖机会，试试运气吧！"
                );
                $return_result['code'] = '200';
                return $return_result;
                break;
            case 3:
                return array();
                $return_result = array(
                    "type" => $type,
                    "share_alert" => "这是纯文字提醒"
                );
                $return_result['code'] = '200';
                return $return_result;
                break;
            case 4:
                return array();break;
            case 5://其他地区o2o活动
                $return_result = array(
                    "type" => 1,
                    "share_url" => "http://huodong.fruitday.com/sale/scratch/other.html",
                    "share_title" => "【天天果园】你来刮我送券，天天果园任性出了新高度！",
                    "share_desc" => "【天天果园】你来刮我送券，天天果园任性出了新高度！",
                    "share_photo" => "http://activecdn.fruitday.com/sale/scratch/images/300.jpg",
                    "share_alert" => "恭喜你获得刮刮乐分享特权，分享到朋友圈后，可领取优惠券哦，快戳分享~"
                );
                $return_result['code'] = '200';
                return $return_result;
                break;
            case 6:
//              return array();
                $type = 2;
                $return_result = array(
                    "type" => $type,
//                  "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/o2osh.html?link_tag=".$link_tag,
                    "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/o2o.html?link_tag=".$link_tag,
                    "share_alert" => "果园君感谢您的光顾，送您一次刮奖机会，试试运气吧！"
                );
                $return_result['code'] = '200';
                return $return_result;
                break;
            case 7:
//              return array();
                $type = 2;
                $return_result = array(
                    "type" => $type,
                    "page_url" => "http://huodong.fruitday.com/sale/blowb2c917/o2o.html?link_tag=".$link_tag,
                    "share_alert" => "果园君感谢您的光顾，送您一次刮奖机会，试试运气吧！"
                );
                $return_result['code'] = '200';
                return $return_result;
                break;
            default:return array('code' => '300', 'msg' => 'unknow type case');
        }
        */
    }

    /*
     * 商品推荐
     */

    function recommend($params) {
        $return_count = 20;

        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
        //  if (!$this->ci->memcached) {
        //      $this->ci->load->library('memcached');
        //  }
        //  $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['connect_id'] . "_" . $params['region_id'];
        //  $result = $this->ci->memcached->get($mem_key);
        //  if ($result) {
        //      return $result;
        //  }
        // }

        if (isset($params['connect_id']) && !empty($params['connect_id'])) {
            //获取session信息start
            $this->ci->load->library('session', array('session_id' => $params['connect_id']));
            $session = $this->ci->session->userdata;
            if (empty($session)) {
                return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
            }

            $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
            if (!isset($userdata['id']) || $userdata['id'] == "") {
                return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
            }
            $uid = $userdata['id'];
            //获取session信息end

            $pro_rec_result = $this->ci->user_model->user_recommend($uid);
            $pro_rec_arr = unserialize($pro_rec_result['product_tags']);
            //自动推荐的商品
        }

        $this->ci->load->model('product_model');
        $pro_tag_result = $this->ci->product_model->get_pro_active();
        $pro_tag_tmp = array();
        foreach ($pro_tag_result as $key => $value) {
            if (!empty($value['product_id'])) {
                $pro_tag_tmp[$value['type']] = explode(',', $value['product_id']);
            }
        }//后台设置的推荐商品


        $recommend_result = array();
        // $recommend_result = $this->check_pro_recommend($pro_tag_tmp,'top_recommend');
        // $return_count = 9;
        $recommend_count = $return_count - count($recommend_result);
        $i = 1;
        if (!empty($pro_rec_arr)) {
            foreach ($pro_rec_arr as $value) {
                if ($i > $recommend_count) {
                    break;
                }
                $pro_id = $this->ci->product_model->check_recommend($value);
                if ($pro_id) {
                    $recommend_result[] = $pro_id;
                    $i++;
                }
            }//top+自动推荐商品
        }

        if (count($recommend_result) < $return_count) {
            $recommend_tmp = $this->ci->product_model->check_pro_recommend($pro_tag_tmp, 'recommend');
            $add_count = $return_count - count($recommend_result);
            $n = 0;
            foreach ($recommend_tmp as $value) {
                if ($n >= $add_count) {
                    break;
                } else {
                    $recommend_result[] = $value;
                    $n++;
                }
            }
        }//top+自动推荐商品+设置推荐商品

        $result = array();
        if (!empty($recommend_result)) {
            $region_id = $this->get_region_id($params['region_id']);
            $result = $this->ci->product_model->get_products(array('product_id_arr' => $recommend_result, 'sort' => 0, 'region' => $region_id, 'page_size' => 3, 'curr_page' => 0, 'source' => $params['source'], 'channel' => 'portal'));
        }
        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
        //  if (!$this->ci->memcached) {
        //      $this->ci->load->library('memcached');
        //  }
        //  $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['connect_id'] . "_" . $params['region_id'];
        //  $this->ci->memcached->set($mem_key, $result, 300);
        // }
        return $result;
    }

    /*
     * 获取地区标识
     */

    private function get_region_id($region_id = 106092) {
        $region_id = empty($region_id) ? 106092 : $region_id;
        $site_list = $this->ci->config->item('site_list');
        if (isset($site_list[$region_id])) {
            $region_result = $site_list[$region_id];
        } else {
            $region_result = 106092;
        }
        return $region_result;
    }

    public function notice($params) {
        $pay_num = 0;
        $comment_num = 0;
        $gift_num = 0;
        $privilege_num = 0;
        $foretaste_num = 0;

        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];

        //fix 订单缓存
        $cach_key = 'user_notice_'.$uid;
        $this->ci->load->library('orderredis');
        $redis = $this->ci->orderredis->getConn();
        if($redis != false)
        {
            $userData = $redis->get($cach_key);
            if(!empty($userData))
            {
                return json_decode($userData,true);
            }
        }

        //未支付订单数量
        $pay_num = $this->ci->user_model->show_pay_order_num($uid);

        //可评论
        $comment_num = $this->ci->user_model->can_comment_order_num($uid);


        //赠品
        // $all_gifts = $this->ci->user_model->getUserGift($uid,false);
        // $user_gifts = array();
        // $user_rec_gifts = array();
        // $user_exp_gifts = array();
        // $now_time = date("Y-m-d H:i:s");
        // if(!empty($all_gifts)){
        //     foreach ($all_gifts as $key => $value) {
        //       if($value['has_rec']=='1'){
        //         $user_rec_gifts[]  = $value;
        //       }elseif(isset($value['end_time']) && strcmp($value['end_time'], $now_time) < 0){
        //         $user_exp_gifts[] = $value;
        //       }else{
        //         $user_gifts[] = $value;
        //       }
        //     }
        // }
        // $gift_num = count($user_gifts);
        $this->ci->load->model('user_gifts_model');
        $gift_num = $this->ci->user_gifts_model->gift_count($uid);


        //特权数量
//      $privilege_list = $this->privilegeList($params);
//      $privilege_num = 0;
//      foreach ($privilege_list as $key => $value) {
//          if ($value['privilege_type'] != 'level' && $value['privilege_type'] != 'juice') {
//              $privilege_num++;
//          }
//      }

        //试吃
        $foretaste_num = $this->ci->user_model->foretaste_apply($uid);

        $new_gift_alert = $this->ci->user_model->checkNewGift($uid);
        $new_coupon_alert = $this->ci->user_model->checkNewCard($uid);


        //new - 用户中心
        $new_jf_alert = $this->ci->user_model->checkNewJf($uid);

        $order_paying = $this->ci->user_model->showOrderNum($uid, 1);  //待付款
        $order_shipped = $this->ci->user_model->showOrderNum($uid, 2); //待发货
        $order_receipt = $this->ci->user_model->showOrderNum($uid, 3); //待收货
        $order_comment = $this->ci->user_model->showOrderNum($uid, 4); //待评价
        $order_refund = $this->ci->user_model->showOrderNum($uid, 5);  //退款

        $this->ci->load->bll('apiuser');
        $cardtips = $this->ci->bll_apiuser->cardtips(array('uid'=>$uid));

        $result = array(
            'pay_num' => $pay_num,
            'comment_num' => $comment_num,
            'gift_num' => $gift_num,
            'privilege_num' => $privilege_num,
            'foretaste_num' => $foretaste_num ? $foretaste_num : 0,
            'new_gift_alert' => $new_gift_alert,
            'new_coupon_alert' => $new_coupon_alert,
            'new_jf_alert' => $new_jf_alert,
            'order_paying' => $order_paying,
            'order_shipped' => $order_shipped,
            'order_receipt' => $order_receipt,
            'order_comment' => $order_comment,
            //避免引导退换货,退换货不显示数字红点,都给0
            'order_refund' => 0,
            'notice'       => isset($cardtips['notice'])?$cardtips['notice']:0,
//            'order_refund'=>$order_refund
        );

        //fix 订单缓存
        if($redis != false)
        {
            $redis->set($cach_key, json_encode($result));
            $redis->expire($cach_key, 10);
        }

        return $result;
    }

    public function setLoginError($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        if (!preg_match('/@/', $params['mobile'])) {
            $where = array(
                "mobile" => $params['mobile'],
            );
        } else {
            $where = array(
                "email" => $params['mobile'],
            );
        }
        $users = $this->ci->user_model->selectUsers("id", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '用户名或密码错误');
        }
        $uid = $users[0]['id'];
        $curr_num = $this->ci->user_model->setLoginErrorNum($uid);
        return $curr_num;
    }

    public function getLoginError($params) {
        $required_fields = array(
            'mobile' => array('required' => array('code' => '300', 'msg' => '帐号不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        if (!preg_match('/@/', $params['mobile'])) {
            $where = array(
                "mobile" => $params['mobile'],
            );
        } else {
            $where = array(
                "email" => $params['mobile'],
            );
        }
        $users = $this->ci->user_model->selectUsers("id", $where);
        if (empty($users)) {
            return array('code' => '300', 'msg' => '用户名或密码错误');
        }
        $uid = $users[0]['id'];
        //黑名单验证
        // if($user_black = $this->ci->user_model->check_user_black($uid)){
        //           if($user_black['type']==1){
        //               return array("code"=>"300","msg"=>"果园君发现您的账号为无效手机号，为保证您的购物体验请用有效手机号注册，敬请谅解。");
        //           }else{
        //      return array("code" => "300", "msg" => "您的帐号可能存在安全隐患，暂时冻结，请联系客服处理!");
        //  }
        // }
        $login_error_num = $this->ci->user_model->getLoginErrorNum($uid);
        return $login_error_num;
    }

    /*
     * 获取ip
     */

    private function get_real_ip() {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10│172.16│192.168)./", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    public function levelLog($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        return $this->ci->user_model->levelLog($uid, $limit, $offset);
    }

    public function checkRedIndicator($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        return $this->ci->user_model->checkRedIndicator($uid);
    }

    public function cancelRedIndicator($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'type' => array('required' => array('code' => '300', 'msg' => '类型不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];
        $type = $params['type'];
        if (!in_array($type, array(0, 1, 2, 3, 4)))
            return array('code' => '500', 'msg' => 'type must be 0 or 1');
        $res = $this->ci->user_model->cancelRedIndicator($uid, $type);
        if ($res === true) {
            return array('code' => '200', 'msg' => 'succ');
        } else {
            return array('code' => '500', 'msg' => 'fail');
        }
    }

    /*
     * 会员特权 － 用户信息
     */
    public function privilege($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $user = $this->ci->user_model->getUser($userdata['id']);
        if (isset($user['code'])) {
            return $user;
        }
        $response = array();
        $user_score = $this->ci->user_model->getUserScore($user['id']);
        $coupon_num = $this->ci->user_model->getCouponNum($user['id'], 0);
        $response = array_merge($user, $user_score);
        $response['coupon_num'] = $coupon_num;
        $this->ci->user_model->upgrade_rank($user['id'], true);
        $user_url = 'http://wapi.fruitday.com/appMarketing/userActive/' . $userdata['id'] . '/' . $this->create_sign($userdata['id']);
        $response['qr_code'] = $user_url;
        $response['push_group'] = 'group' . substr($user['id'], -1, 1);
        return $response;
    }

    /**
     * 在用户第一次打开APP时，用来收集用户手机系统相关数据
     */
    public function collectMobileData($params) {
        $this->ci->load->model('user_action_model');
        if ($params['connect_id']) {
            $this->_sessid = $params['connect_id'];
            $params['uid'] = $this->get_userid();
            $this->ci->user_action_model->visitor($params);
        }
        $operating_system = isset($params['platform']) ? $params['platform'] : ''; //手机操作系统名称
        $version_number = isset($params['version_number']) ? $params['version_number'] : ''; //操作系统版本号
        $device_number = isset($params['device_number']) ? $params['device_number'] : ''; //IMEI,IDFA,MEID
        $android_id = isset($params['android_id']) ? $params['android_id'] : ''; //安卓系统的Android Id
        $channel_code = isset($params['channel']) ? $params['channel'] : ''; //广告渠道标识
        $activation_time = isset($params['timestamp']) ? $params['timestamp'] : 0; //首次打开APP的时间戳
        $device_id = isset($params['device_id']) ? $params['device_id'] : ''; //接口约定设备号
        $registration_id = isset($params['registration_id']) ? $params['registration_id'] : ''; //jpush推送设备id
        $ip_address = isset($params['ip']) ? $params['ip'] : ''; //外网IP
        $user_agent = isset($params['ua']) ? urldecode($params['ua']) : ''; //浏览器User-Agent

        //渠道激活数据收集
        if ($device_id && $channel_code) {
            $dataApp = array(
                'operating_system' => $operating_system,
                'device_number' => $device_number,
                'android_id' => $android_id,
                'channel_code' => $channel_code,
                'activation_time' => $activation_time,
                'device_id' => $device_id
            );
            $this->ci->load->model('user_activation_model');
            register_shutdown_function(array($this->ci->user_activation_model, 'add'), $dataApp);
        }

        //config
        $ds = array();
        $ds['service_phone_alert'] = 0;
        $ds['service_alert_text'] = '客服电话处在高峰,推荐使用其他方式';
        $ds['reported_interval'] = 20;

        if ($operating_system && ($device_number || $android_id) && $activation_time) {
            $data = array(
                'operating_system' => $operating_system,
                'version_number' => $version_number,
                'device_number' => $device_number,
                'android_id' => $android_id,
                'channel_code' => $channel_code,
                'activation_time' => $activation_time,
                'device_id' => $device_id,
                'registration_id' => $registration_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'ip_ua' => ($ip_address || $user_agent) ? md5($ip_address . $user_agent) : ''
            );
            $this->ci->load->model('user_mobile_data_model');
            $result = $this->ci->user_mobile_data_model->add($data);
            if ($result) {
                return array('code' => '200', 'msg' => 'succ', 'info' => $ds);
            } else {
                return array('code' => '200', 'msg' => 'fail', 'info' => $ds);
            }
        }
        return array('code' => '200', 'msg' => 'fail', 'info' => $ds); //不写500，是由于APP那边收到500，会向用户报错
    }

    /*
     * 验证注册
     */

    public function updateBasic($params) {
        $required_fields = array(
            'nickname' => array('required' => array('code' => '300', 'msg' => '昵称不能为空')),
            'birthday_y' => array('required' => array('code' => '300', 'msg' => '年份不能为空')),
            'birthday_m' => array('required' => array('code' => '300', 'msg' => '月份不能为空')),
            'birthday_d' => array('required' => array('code' => '300', 'msg' => '日期不能为空')),
            'sex' => array('required' => array('code' => '300', 'msg' => '性别不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }
        $userdata = unserialize($session['user_data']);
        //error_log(var_export($userdata, true),3,"c:/wamp/www/ff.txt");
        $this->ci->user_model->user_id = $userdata['id'];
        $users['nickname'] = $params['nickname'];
        $users['birthday_y'] = $params['birthday_y'];
        $users['birthday_m'] = $params['birthday_m'];
        $users['birthday_d'] = $params['birthday_d'];
        $users['sex'] = $params['sex'];
        $users['email'] = $params['email'];
        $result = $this->ci->user_model->save($users);
        if ($result['error'] == '0') {
            return array('code' => '200', 'msg' => 'succ');
        } else {
            return array('code' => '200', 'msg' => 'fail');
        }
    }

    /*
     * 用户－充值订单详细
     */
    public function userTradeInfo($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_id' => array('required' => array('code' => '500', 'msg' => 'order id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        // $session_id = $params['connect_id'];
        // $this->ci->load->model("session_model");
        // $session =   $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        $uid = $userdata['id'];

        $data = $this->ci->user_model->get_user_trade_info($uid, $params['order_id']);

        if (empty($data)) {
            return array('code' => '300', 'msg' => '充值订单不存在');
        }
        return array('code' => '200', 'msg' => $data);
    }

    /*
     * 用户信息加密 － oms
     */
    public function userInfoAes($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }


        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $user = $this->ci->user_model->getUser($userdata['id']);
        if (isset($user['code'])) {
            return $user;
        }
        $user['mobile_status'] = empty($user['mobile_status']) ? "0" : "1";
        $response = array();
        $user_score = $this->ci->user_model->getUserScore($user['id']);
        $coupon_num = $this->ci->user_model->getCouponNum($user['id'], 0);
        $response = array_merge($user, $user_score);
        $response['coupon_num'] = $coupon_num;

        $user_url = 'http://wapi.fruitday.com/appMarketing/userActive/' . $userdata['id'] . '/' . $this->create_sign($userdata['id']);
        $response['qr_code'] = $user_url;
        $response['push_group'] = 'group' . substr($user['id'], -1, 1);

        $this->ci->load->library('aes', null, 'encrypt_aes');
        $response = urlencode($this->ci->encrypt_aes->AesEncrypt(json_encode($response, JSON_UNESCAPED_UNICODE)));
        return $response;
    }

    /*
     * 发送邮件
     */
    public function sendEmail($params) {
        //error_log(var_export($params, true),3,"c:/wamp/www/ff.txt");
        return array('code' => '300', 'msg' => '功能维护中');
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'email' => array('required' => array('code' => '500', 'msg' => 'order id can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        $this->ci->load->model("emailto");
        $this->ci->user_model->setUniqueCode();
        $this->ci->user_model->saveItem($userdata['id'], $params['email']);
        $setted = $this->ci->emailto->setMessageType("verify");
        if ($setted)
            exit($setted);

        $this->ci->emailto->setEmail($params['email']);
        $this->ci->emailto->setUser($this->ci->user_model);
        $this->ci->emailto->setPoint($this->ci->user_points);
        $success = $this->ci->emailto->send();

        return $success;
    }

    function giftsGet($region_id = '106092', $valid = 1, $gift_type = 2, $page = 1, $limit = -1) {
        $uid = $this->ci->login->get_uid();
        if (!$uid) return array('code' => 300, 'msg' => '登录超时');
        $this->ci->load->bll('gsend');
        $gifts = $this->ci->bll_gsend->get_user_gifts($uid, $region_id, $valid, $gift_type, $page, $limit);
        return $gifts;
    }

    function giftsGetNew($storeIdList, $valid = 1, $gift_type = 2) {
        $uid = $this->ci->login->get_uid();
        if (!$uid) return array('code' => 300, 'msg' => '登录超时');
        $this->ci->load->bll('gsend');
        $gifts = $this->ci->bll_gsend->get_user_gifts_new($storeIdList, $uid, $valid, $gift_type);
        return $gifts;
    }

    /**
     * 充值单
     *
     * @return void
     * @author
     **/
    public function getInvoice($params) {
        $session_id = $params['connect_id'];
        if (!$session_id) {
            return array('code' => 500, 'msg' => 'connect id can not be null');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }

        $page = isset($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $uid = $this->ci->login->get_uid();

        if ($params['ctime'] == '2') {
            $filter = array(
                "has_deal" => 1,
                "money >" => 0,
                "uid" => $uid,
                "invoice >" => 1,
            );
        } elseif ($params['ctime'] == '1') {
            $filter = array(
                "has_deal" => 1,
                "money >" => 0,
                "uid" => $uid,
                "invoice" => 0,
                "time <=" => date("Y-m-d H:i:s", strtotime("-3 months"))
            );
        } else {
            $filter = array(
                "has_deal" => 1,
                "money >" => 0,
                "uid" => $uid,
                "invoice" => 0,
                "time >=" => date("Y-m-d H:i:s", strtotime("-3 months"))
            );
        }
        $trades = $this->ci->user_model->getTransactionBy(
            $filter,
            array(
                "payment",
                array(
                    "支付宝",
                    "网上银行",
                    "微信支付",
                    "微信扫码支付",
                    "微信平台支付",
                    "微信APP支付",
                    "银联支付",
                )
            ),
            $limit,
            $offset
        );
        return array('code' => 200, 'data' => $trades);

    }

    /**
     * 新客注册礼赠品
     *
     * @param  $uid
     * @param  $mobile
     * @param  $inviteUid  推荐人uid 0没有推荐人
     *
     * @return
     */
    private function sendNewUserPrize($uid, $mobile, $inviteUid) {
        $prizeRow = $this->ci->user_model->getPrizeById($inviteUid);
        $prizeDetail = $this->ci->user_model->getPrizeDetailByPrizeid($prizeRow['id']);
        $cardTagArr = array(); //优惠券tag
        $activeTagArr = array(); //赠品tag
        $jf = 0;
        foreach ($prizeDetail as $val) {
            switch ($val['prize_type']) {
                case '赠品':
                    array_push($activeTagArr, $val['content']);
                    break;
                case '优惠券':
                    array_push($cardTagArr, array('tag' => $val['content'], 'use_start' => $val['use_start'], 'use_end' => $val['use_end']));
                    break;
                case '积分':
                    $jf += $val['content'];
                    break;
                default:
                    break;
            }
        }
        $active_id_arr = $this->ci->user_model->getGiftSend($activeTagArr); //要发送的赠品id
        $card_arr = $this->ci->user_model->getMobileCard($cardTagArr); //要发送的优惠券
        $this->ci->db->trans_begin();
        $giftFlag = $this->ci->user_model->giveGift($uid, $active_id_arr);
        $cardFlag = $this->ci->user_model->newSendCard($uid, $card_arr);
        $jfFlag = $this->ci->user_model->sendJf($jf, $uid);
        if ($giftFlag === FALSE && $cardFlag === FALSE && $jfFlag === FALSE) {
            $this->ci->db->trans_rollback();
            return;
        }
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
        } else {
            $this->ci->db->trans_commit();
            $this->sendMessage($prizeRow['message'], $mobile);
        }
    }

    private function sendMessage($smsText, $mobile) {
        //调用通知start
        $this->ci->load->library("notifyv1");
        $params = ["mobile" => $mobile, "message" => $smsText];
        $this->ci->notifyv1->send('sms', 'send', $params);
        //调用通知end
    }

    /*
    *充值发票抬头列表
    */
    function tradeInvoiceTitleList($params) {

        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);

        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $invoice_info = $this->ci->user_model->get_invoice_title_list($uid);

        $invoice_result = array();
        if (!empty($invoice_info)) {
            foreach ($invoice_info as $key => $value) {
                if ($value['name'] != '') {
                    $invoice_result[] = $value['name'];
                }
            }
        }
        return array('code' => '200', 'msg' => 'succ', 'data' => $invoice_result);
    }

    /*
    *获取session
    */
    private function get_uid_by_connect_id($session_id, $lock_order = false) {
        // $this->ci->load->model("session_model");
        // $session = $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        // if($lock_order){
        //     $now_time = time();
        //     if(isset($userdata['lock_order']) && ($now_time-$userdata['lock_order']<=3)){
        //         return array('code'=>'300','msg'=>'请勿重复提交订单,稍后请重试');
        //     }
        //     $userdata['lock_order'] = $now_time;
        //     $this->ci->session->set_userdata($userdata);
        //     // $this->session_model->update_session_userdata($session_id, $userdata);
        // }

        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        return array('code' => '200', 'msg' => $userdata['id']);
    }

    /**
     * 获取会员
     *
     * @return void
     * @author
     **/
    public function get_userid() {
        $this->ci->load->library('login');
        $this->ci->login->init($this->_sessid);

        return $this->ci->login->get_uid();
    }

    /*
    *web扩展测试
    */
    public function webTest() {

        if (!$this->ci->memcached) {
            $this->ci->load->library('memcached');
        }
        $this->ci->memcached->set('webtest20160401', 'test', 100);

        $result = $this->ci->memcached->get('webtest20160401');
        if ($result) {
            echo '<span style="color:green">memcached is ok</span>' . '<br>';
        } else {
            echo '<span style="color:red">memcached is error</span>' . '<br>';
        }

        $this->ci->load->library('phpredis');
        $redis = $this->ci->phpredis->getConn();
        $redis->set('webtest20160401', 1);
        $redis_result = $redis->get('webtest20160401');
        if ($redis_result) {
            echo '<span style="color:green">redis is ok</span>' . '<br>';
        } else {
            echo '<span style="color:red">redis is error</span>' . '<br>';
        }

        $db_arr = array('default_master', 'default_slave', 'default_slave2', 'default_slave3', 'default_slave4', 'default_slave5');
        // $db_arr = array('default_master');

        foreach ($db_arr as $key => $value) {
            $db_master = $this->ci->load->database($value, TRUE);
            $db_master->select('mobile');
            $db_master->from('user');
            $db_master->where('mobile', '13671981025');
            $result = $db_master->get()->row_array();
            if ($result['mobile'] == '13671981025') {
                echo '<span style="color:green">' . $value . ' is ok</span>' . '<br>';
            } else {
                echo '<span style="color:red">' . $value . ' is error</span>' . '<br>';
            }
        }

    }

    /*
        *充值发票历史
    */
    public function getRechargeHistory($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'trade_no' => array('required' => array('code' => '500', 'msg' => 'trade_no can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }
        $parm = array(
            'uid' => $userdata['id'],
            'trade_no' => $params['trade_no'],
        );
        $tradeInvoice = $this->ci->user_model->getTradeInvoice($parm);

        return array('code' => '200', 'msg' => 'succ', 'data' => $tradeInvoice);
    }

    public function tradeInvoiceHistory($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        $limit = $params['limit'] ? $params['limit'] : 10;
        $page = $params['page'] ? $params['page'] : 1;

        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }
        $uid = $userdata['id'];
        $this->ci->load->model('trade_invoice_model');
        $offset = ($page - 1) * $limit;
        $invoice_history = $this->ci->trade_invoice_model->getInvoiceHistory($uid, $limit, $offset);
        foreach ($invoice_history as $key => $value) {
            $invoice_history[$key]['status'] = 0;
            if ($value['time'] <= '2016-04-18' && empty($value['tracking_number'])) {  //已发送，暂无物流信息
                $invoice_history[$key]['status'] = 2;
            }
            if ($value['tracking_number'])                    //已发送，暂无物流信息
                $invoice_history[$key]['status'] = 1;
            foreach ($value as $k => $v) {
                if (is_null($v)) {
                    $invoice_history[$key][$k] = '';
                }
            }
        }
        return $invoice_history;
    }

    public function tradeInvoiceHistory_new($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        $limit = $params['limit'] ? $params['limit'] : 10;
        $page = $params['page'] ? $params['page'] : 1;

        $session = $this->ci->session->userdata;
        if (empty($session)) {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }

        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '300', 'msg' => '登录已过期，请点击右上角设置按钮，退出登录，再重新登录');
        }
        $uid = $userdata['id'];
        $this->ci->load->model('trade_invoice_model');
        $offset = ($page - 1) * $limit;
        $invoice_history = $this->ci->trade_invoice_model->getInvoiceHistory($uid, $limit, $offset);
        foreach ($invoice_history as $key => $value) {
            $invoice_history[$key]['status'] = 0;
            if ($value['time'] <= '2016-04-18' && empty($value['tracking_number'])) {  //已发送，暂无物流信息
                $invoice_history[$key]['status'] = 2;
            }
            if ($value['tracking_number'])                    //已发送，暂无物流信息
                $invoice_history[$key]['status'] = 1;
            foreach ($value as $k => $v) {
                if (is_null($v)) {
                    $invoice_history[$key][$k] = '';
                }
            }
        }
        return array('code' => 200, 'msg' => '', 'data' => $invoice_history);
    }


    /*
     * 樱桃
     */

    function yingtaoCard($params) {
        //必要参数验证start
        $required_fields = array(
            'mobile' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'activeId' => array('required' => array('code' => '300', 'msg' => '活动id')),
            'rating' => array('required' => array('code' => '300', 'msg' => '手机等级')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $user_info = $this->ci->user_model->getUserInfoByMobile($params['mobile']);
        //获取session信息end
        $card_money = $this->ci->user_model->yingtao_card($user_info['id'], $params['activeId'], $params['rating'], $user_info['user_rank']);
        return array('code' => '200', 'card_money' => $card_money, 'mobile' => $params['mobile']);
    }

    /*
     * 日志
     */
    private function insertLog($data) {
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, 'bindlog', json_encode($data));
    }

    /*
    *APP热修复JSPatch
    */
    function hotFix($params) {
        $hotfix_info = $this->ci->user_model->get_hotfix_version();
        if (empty($hotfix_info)) {
            return array('code' => '200', 'is_update' => '0', 'target_version' => '');
        } else {
            $hotfix_tmp = array();
            foreach ($hotfix_info as $key => $value) {
                $hotfix_tmp[$value['version']] = $value['fix_version'];
            }
            if (isset($hotfix_tmp[$params['version']]) && $params['fix_version'] < $hotfix_tmp[$params['version']]) {
                return array('code' => '200', 'is_update' => '1', 'target_version' => $hotfix_tmp[$params['version']]);
            } else {
                return array('code' => '200', 'is_update' => '0', 'target_version' => '');
            }
        }
    }

    /**
     * 收集市场投放渠道tracking码和设备号
     */
    public function collectChannelTracking($params) {
        $code = isset($params['trackingId']) ? $params['trackingId'] : ''; //渠道追踪码
        $device_id = isset($params['device_id']) ? $params['device_id'] : ''; //接口约定设备号
        $visit_time = isset($params['timestamp']) ? $params['timestamp'] : 0; //唤起APP的时间戳

        if ($code && $device_id && $visit_time) {
            $data = array(
                'code' => trim($code, '='),
                'device_id' => $device_id,
                'visit_time' => $visit_time
            );
            $this->ci->load->model('user_channel_tracking_model');
            $result = $this->ci->user_channel_tracking_model->add($data);
            if ($result) {
                return array('code' => '200', 'msg' => 'succ');
            } else {
                return array('code' => '200', 'msg' => 'fail');
            }
        }
        return array('code' => '200', 'msg' => 'fail'); //不写500，是由于APP那边收到500，会向用户报错
    }

    /*
     * 是否email
     */
    private function is_email($email) {
        if (preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email))
            return TRUE;
        else
            return FALSE;
    }
}
