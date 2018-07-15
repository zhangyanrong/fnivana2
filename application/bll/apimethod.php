<?php
namespace bll;

class Apimethod
{
    //private $_api_url = 'http://api.order.guantest.fruitday.com';  //staging
    private $_api_url = 'http://internal-service-order-inner-1515377583.cn-north-1.elb.amazonaws.com.cn';     //正式
    private $_service_open = 1;   //邮费服务开关

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }


    /*
     * 获取运费
     */
    public function get($params)
    {
        $is_open = $this->_service_open;

        if(version_compare($params['version'], '5.2.0') < 0)
        {
            $is_open = 0;
        }

        if($is_open  == 1)
        {
            $this->ci->load->library('restclient');
            $this->ci->restclient->set_option('base_url',$this->_api_url);
            $res = $this->ci->restclient->post("v1/order/methodMoney",[
                    'cart_info'=>$params['cart_info'],
                    'area_code'=>$params['area_code'],
                    'uid'=>$params['uid'],
                    'store_id_list'=>$params['store_id_list'],
                ]
            );
            $method  = json_decode($res->response,true);
            $method_money = $method['method_money'];
        }
        else
        {
            $method_money = $params['total_method_money'];
        }

        return $method_money;
    }
}
