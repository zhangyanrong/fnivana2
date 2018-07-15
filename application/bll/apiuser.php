<?php
namespace bll;

class Apiuser
{
    //private $_api_url = 'http://stagingserviceuser.fruitday.com';  //staging
    private $_api_url = 'http://internal-service-user-inner-565898489.cn-north-1.elb.amazonaws.com.cn';     //正式

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }


    /*
     * 更新用户信息
     */
    public function set($params)
    {
        $uid = $params['uid'];
        $this->ci->load->library('restclient');
        $this->ci->restclient->set_option('base_url',$this->_api_url);
        $this->ci->restclient->get("v1/cache_update/user_detail/".$uid,[]);
    }

    public function cardtips($params)
    {
        $uid = $params['uid'];
        $this->ci->load->library('restclient');
        $this->ci->restclient->set_option('base_url',$this->_api_url);
        $result = $this->ci->restclient->post("v1/card/cardtips",['uid'=>$uid]);
        $cardtips  = json_decode($result->response,true);
        return $cardtips;
    }
}
