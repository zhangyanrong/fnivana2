<?php
namespace bll;

class Apicart
{
    //private $_api_url = 'http://stagingservicecart.fruitday.com';  //staging
    private $_api_url = 'http://internal-service-cart-inner-350915296.cn-north-1.elb.amazonaws.com.cn';   //正式

    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->helper('public');
        $this->ci->load->model('user_model');
        $this->ci->load->model('cart_v2_model');
    }


    /*
     * 获取购物车
     */
    public function get($params)
    {
        if(version_compare($params['version'], '5.2.0') < 0)
        {
            $params['uid'] = $params['cart_id'];
            $cart = $this->get_v2($params);
        }
        else
        {
            $params['stores'] = $params['store_id_list'];
            $params['range'] = $params['tms_region_type'];
            $params['uid'] = $params['user'];
            $cart = $this->get_v3($params);
        }

        return $cart;
    }

    /*
     * 删除购物车商品
     */
    public function del($params)
    {
        $msg = 'succ';

        $params['cart_products'] = json_decode($params['cart_products'],true);
        if(version_compare($params['version'], '5.2.0') < 0)
        {
            $params['uid'] = $params['cart_id'];
            $this->del_v2($params);
        }
        else
        {
            $params['stores'] = $params['store_id_list'];
            $params['range'] = $params['tms_region_type'];
            $params['uid'] = $params['user'];

            if(count($params['cart_products']) > 0)
            {
                foreach($params['cart_products'] as $key=>$val)
                {
                    $params['item_id'] = $val['item_id'];
                    $this->del_v3($params);
                }
            }
        }

        return $msg;
    }

    /*
     * 获取购物车 --  v2
     */
    public function get_v2($params)
    {
        $store_id_list = explode(',',$params['store_id_list']);
        $user = $this->ci->user_model->dump(array('id'=>$params['uid']));
        $cart = $this->ci->cart_v2_model->init($params['uid'],$store_id_list,$user,$params['source'],$params['version'],$params['tms_region_type']);
        $cart_obj = $cart->getProducts()->validate()->promo()->total()->count()->checkout();
        $json_cart = json_encode($cart_obj);
        $cart_info = json_decode($json_cart,true);

        return $cart_info;
    }

    /*
     * 删除购物车商品 --  v2
     */
    public function del_v2($params)
    {
        $msg = 'succ';

        $store_id_list = explode(',',$params['store_id_list']);
        $user = $this->ci->user_model->dump(array('id'=>$params['uid']));
        $cart = $this->ci->cart_v2_model->init($params['uid'],$store_id_list,$user,$params['source'],$params['version'],$params['tms_region_type']);

        if(count($params['cart_products']) > 0)
        {
            foreach($params['cart_products'] as $key =>$val) {
                $cart->removeItem($val['item_id']);
            }
        }

        return $msg;
    }

    /*
     * 获取购物车 --  v3
     */
    public function get_v3($params)
    {
        $this->ci->load->library('restclient');
        $this->ci->restclient->set_option('base_url',$this->_api_url);
        $cart = $this->ci->restclient->get("v3/cart/".$params['cart_id']."/order",[
                'cart_id'=>$params['cart_id'],
                'stores'=>$params['stores'],
                'range'=>$params['range'],
                'uid'=>$params['uid'],
                'source'=>$params['source'],
                'source_version'=>$params['version'],
            ]
        );
        $cart_info  = json_decode($cart->response,true);

        //构建
        if(count($cart_info['products']) > 0)
        {
            foreach($cart_info['products'] as $key=>$val)
            {
                $pro = $cart_info['products'][$key]['product'];
                $cart_info['products'][$key]['product_name'] = $pro['product_name'];
                $cart_info['products'][$key]['photo'] = $pro['photo'];
                $cart_info['products'][$key]['has_webp'] = $pro['has_webp'];
                $cart_info['products'][$key]['template_id'] = $pro['template_id'];
                $cart_info['products'][$key]['group_limit'] = $pro['group_limit'];
                $cart_info['products'][$key]['pay_discount_limit'] = $pro['pay_discount_limit'];
                $cart_info['products'][$key]['iscard'] = $pro['iscard'];
                $cart_info['products'][$key]['class_id'] = $pro['class_id'];
                $cart_info['products'][$key]['jf_percent'] = $pro['jf_percent'];  //下单获取商品积分比例

                $sku = $cart_info['products'][$key]['sku'];
                $cart_info['products'][$key]['weight'] = $sku['weight'];
                $cart_info['products'][$key]['volume'] = $sku['volume'];
                $cart_info['products'][$key]['unit'] = $sku['unit'];
                $cart_info['products'][$key]['sku_id'] = $sku['sku_id'];
                $cart_info['products'][$key]['product_no'] = $sku['product_no'];

                $cart_info['products'][$key]['free_post'] = $cart_info['products'][$key]['is_free_post']; //兼容包邮

                unset($cart_info['products'][$key]['product']);
                unset($cart_info['products'][$key]['sku']);
            }
        }
        $cart_info['total']['price'] = $cart_info['total']['original_price'];

        return $cart_info;
    }


    /*
     * 删除购物车商品 --  v3
     */
    public function del_v3($params)
    {
        $msg = 'succ';
        $this->ci->load->library('restclient');
        $this->ci->restclient->set_option('base_url',$this->_api_url);
        $cart = $this->ci->restclient->delete("v3/cart/".$params['cart_id'],[
                'cart_id'=>$params['cart_id'],
                'item_id'=>$params['item_id'],
                'stores'=>$params['stores'],
                'range'=>$params['range'],
                'uid'=>$params['uid'],
                'source'=>$params['source'],
                'source_version'=>$params['version'],
            ]
        );
        return $msg;
    }

    /*
     * 添加购物车商品
     */
    public function add($params)
    {
        $this->ci->load->library('restclient');
        $this->ci->restclient->set_option('base_url',$this->_api_url);

        $cart = $this->ci->restclient->post("v3/cart/".$params['uid'],[
                'cart_id'=>$params['uid'],
                'uid'=>$params['uid'],
                'product_id'=>$params['pid'],
                'stores'=>$params['stores'],
                'type'=>'normal',
                'qty'=>$params['qty'],
            ]
        );
        $code = $cart->info->http_code;
        return $code;
    }
}
