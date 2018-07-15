<?php

namespace bll;

class Auth
{
    private $config = array(
        '500jia' => array('salt' => '1c6dac5b12922a732103161e21caa5a6', 'alphabet' => 'lvjEbGVX7mACSiQ3YM8OKp0ngNhoaTFUkuLxeZJ5fdWtr6cRz9sIy1qP2HB4wD'),
    );

    function __construct($params = array())
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }

    /**
     * 第三方获取用户信息
     * @param $params
     * @return array
     */
    public function userInfo($params)
    {
        if (empty($params['connect_id'])) {
            return array('code' => 300, 'msg' => '无效connectID');
        }
        $config = isset($this->config[$params['appid']]) ? $this->config[$params['appid']] : array();
        if (empty($config)) {
            return array('code' => 301, 'msg' => '无效的AppID');
        }

        $this->ci->load->library('login');
        $this->ci->load->library('HashUid', $config, 'HashUid');
        $this->ci->login->init($params['connect_id']);

        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '无效connectID');
        }
        $user = $this->ci->login->get_user();
        $open = $this->ci->db->query('select * from ttgy_auth where uid = ?', array($user['id']))->row_array();
        if (empty($open)) {
            $openid = $this->ci->HashUid->encode($user['id']) . tag_code(time() . rand_code(4));
            $this->ci->db->insert('ttgy_auth', array('uid' => $user['id'], 'openid' => $openid));
        } else {
            $openid = $open['openid'];
        }
        $user_info = array(
            'openid' => $openid,
            'mobile' => $user['mobile'],
        );
        return $user_info;
    }

    public function orderTotal(){
        $this->ci->load->model('subscription_model');
        return $this->ci->subscription_model->orderTotal();
    }
}
