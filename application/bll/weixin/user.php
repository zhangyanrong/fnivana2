<?php

namespace bll\weixin;

class User
{
    private $session_expiretime = 1209600;

    public function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->model('weixin_model');
    }

    /**
     * 解除绑定。
     */
    public function unbind($aParams)
    {
        $uid = $aParams['uid'];
        return $this->ci->weixin_model->unbind($uid);
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
            'weixin_user_info' => array('required' => array('code' => '312', 'msg' => '微信用户信息不能为空')),
        );

        if ($alert_msg = check_required($aParams, $required_fields)) {
            return ['code' => $alert_msg['code'], 'msg' => $alert_msg['msg']];
        }

        $aUserInfo = $this->login($aParams);
        $aWeixinUserInfo = json_decode($aParams['weixin_user_info'], true);

        if ($aUserInfo['code'] > 200) {
            return $aUserInfo;
        } else {
            $aResult = $this->ci->weixin_model->bind($aUserInfo['user_id'], $aWeixinUserInfo);

            if ($aResult['code'] == 200) {
                $aResult['connect_id'] = $aUserInfo['connect_id'];

                // 如果有绑定相关的送礼活动，则给用户送礼并推送微信消息。
                $this->ci->weixin_model->bindingGift($aUserInfo['user_id'], $aParams['mobile'], $aWeixinUserInfo);
            }

            return $aResult;
        }
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

    /**
     * 更新微信用户信息。
     */
    public function updateWeixinUserInfo($aParams)
    {
        $aUserInfo = json_decode($aParams['weixin_user_info'], true);

        return $this->ci->weixin_model->updateWeixinUserInfo($aUserInfo);
    }

    /**
     * 判断指定UID或openid是否已绑定微信账号。
     * @return array
     */
    public function isBind($aParams)
    {
        $this->ci->load->model('weixin_model');
        $info = [];

        if (isset($aParams['uid'])) {
            $uid = (int)$aParams['uid'];
            $info = $this->ci->weixin_model->getWeixinUserByUID($uid);

            if (empty($info)) {
                return ['code' => 301, 'msg' => 'The fruitday user has not bound.'];
            } elseif ($info['bind_time'] == 0) {
                return ['code' => 304, 'msg' => 'The uid need to be updated.'];
            } else {
                return ['code' => 200, 'msg' => 'ok'];
            }
        }

        if (isset($aParams['openid'])) {
            $info = $this->ci->weixin_model->checkOpenID($aParams['openid']);

            if (empty($info)) {
                return ['code' => 302, 'msg' => 'The openid has not bound.'];
            } else {
                if ((int)$info['bind_time'] === 0) {
                    return ['code' => 303, 'msg' => 'The uid need to be updated.'];
                }

                $this->ci->load->model('user_model');
                $user = $this->ci->user_model->getUser("", ['id' => $info['uid']]);
                $this->ci->load->library('session');
                $this->ci->session->sess_expiration = $this->session_expiretime;
				$user['session_time'] = date('Y-m-d H:i:s');//@TODO,2017-05-03为排障增加
                $session_id = $this->ci->session->set_userdata($user);
				session_id($session_id);
				session_start();//@TODO,冗余一份session数据,为nivana3的SESSION互通做准备
				$_SESSION['user_detail'] = $user;
				session_write_close();
                return ['code' => 200, 'msg' => 'ok', 'connect_id' => $session_id, 'uid' => $info['uid']];
            }
        }

        return ['code' => 500, 'msg' => 'Invalid params.'];
    }

    /**
     * 获得微信用户信息。
     * @return array
     */
    public function getWeixinUserInfo($aParams)
    {
        $sCode = $aParams['code'];
        $this->ci->load->library("Weixin", ['sModuleName' => 'User']);
        return $this->ci->weixin->User->getUserInfo($sCode);
    }

    /**
     * 删除用户组。
     * @return array
     */
    public function delGroup($aParams)
    {
        $iGroupID = $aParams['group_id'];
        $this->ci->load->library("Weixin", ['sModuleName' => 'User']);
        return $this->ci->weixin->User->delGroup($iGroupID);
    }

    /**
     * 获得用户组列表。
     * @return array
     */
    public function getGroupList($aParams)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'User']);
        return $this->ci->weixin->User->getUserGroupList();
    }

    /**
     * 批量加入用户组。
     * @return bool
     */
    public function joinGroup($aParams)
    {
        $iGroupID = $aParams['group_id'];
        $aOpenIDs = explode(',', $aParams['openid_list']);
        $this->ci->load->library("Weixin", ['sModuleName' => 'User']);
        return $this->ci->weixin->User->joinGroup($iGroupID, $aOpenIDs);
    }

    /**
     * 创建一个用户组。
     * @return array
     */
    public function createGroup($aParams)
    {
        if (!isset($aParams['name'])) {
            return ['code' => 301, 'msg' => '组名必填'];
        }

        $sName = trim($aParams['name']);

        if (empty($sName)) {
            return ['code' => 302, 'msg' => '组名不能为空'];
        }

        $this->ci->load->library("Weixin", ['sModuleName' => 'User']);
        return $this->ci->weixin->User->createGroup($sName);
    }

    /**
     * 计算用户数。
     * @return int
     */
    public function countUser($aParams)
    {
        $aFilter = [];
        $aFieldList = ['sex', 'country', 'province', 'city', 'start_time', 'end_time'];

        foreach ($aFieldList as $sField) {
            if (isset($aParams[$sField])) {
                $aFilter[$sField] = $aParams[$sField];
            }
        }

        return $this->ci->weixin_model->countUser($aFilter);
    }

    /**
     * 获得用户列表。
     * @return array
     */
    public function getList($aParams)
    {
        $aFilter = [];

        $aFieldList = ['limit', 'offset', 'sex', 'country', 'province', 'city', 'keyword', 'start_time', 'end_time'];

        foreach ($aFieldList as $sField) {
            if (isset($aParams[$sField])) {
                $aFilter[$sField] = $aParams[$sField];
            }
        }

        return $this->ci->weixin_model->getUserList($aFilter);
    }

    /**
     * 获得国家列表。
     * @return array
     */
    public function getCountryList($aParams)
    {
        $aCountryList = $this->ci->weixin_model->getCountryList();
        $aResult = $this->getArrayByField('country', $aCountryList);

        return array_filter($aResult, function($v){
            $aBlackList = [
                '順德區北滘鎮林頭居委會@流浪之家',
                'DE�'
            ];

            if (!in_array($v, $aBlackList)) {
                return $v;
            }
        });
    }

    /**
     * 获得某国家的省份列表。
     * @return array
     */
    public function getProvinceList($aParams)
    {
        $aProvinceList = $this->ci->weixin_model->getProvinceList($aParams['country']);
        $aResult = $this->getArrayByField('province', $aProvinceList);

        return $aResult;
    }

    /**
     * 获得某省份的城市列表。
     * @return array
     */
    public function getCityList($aParams)
    {
        $aCityList = $this->ci->weixin_model->getCityList($aParams['province']);
        $aResult = $this->getArrayByField('city', $aCityList);

        return $aResult;
    }

    public function bindingPoolInit($aParams)
    {
        return $this->ci->weixin_model->bindingPoolInit();
    }

    public function bindingPoolCount($aParams)
    {
        return $this->ci->weixin_model->bindingPoolCount();        
    }

    public function bindingPoolGet($aParams)
    {
        $uid = $aParams['uid'];
        return $this->ci->weixin_model->bindingPoolGet($uid);
    }

    /**
     * 取出某个字段作为构成新的数组。
     * @param string $sField
     * @param array $aFrom
     * @return array
     */
    private function getArrayByField($sField, $aFrom)
    {
        $aResult = [];

        foreach ($aFrom as $aValue) {
            $aResult[] = $aValue[$sField] ? : '无';
        }

        return $aResult;
    }
}

# end of this file.
