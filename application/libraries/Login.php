<?php
/**
 * 登录判断
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   libraries
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Login.php 1 2015-01-27 17:30:21Z pax $
 * @link      http://www.fruitday.com
 **/
class Login
{
    private $_user = array();

    public function __construct($params = []) {
        $this->CI =&get_instance();

        // 增加 蔡昀辰 2016-5
        if(isset($params['session_id']))
            $this->init($params['session_id']);
    }

    public function init($sess_id)
    {
        if ($sess_id) {
            $this->CI->load->library('session',array('session_id'=>$sess_id));

            $user_data = $this->CI->session->userdata('user_data');
            $uid = $this->CI->session->userdata('id');

            if ($user_data) {
                $this->_user = @unserialize($user_data);
            } else if ($uid) {
                $this->_user = $this->CI->session->all_userdata();
            }
        }
    }

    public function is_login()
    {
        return $this->_user['id'] ? true : false;
    }

    // 增加 蔡昀辰 2016-5
    public function isLogin() {
        return $this->_user['id'] ? true : false;
    }

    public function get_uid()
    {
        return $this->_user['id'];
    }

    public function get_user()
    {
        return $this->_user;
    }
}
