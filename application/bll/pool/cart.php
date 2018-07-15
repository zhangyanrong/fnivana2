<?php
/**
 * 购物车结算
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: cart.php 1 2014-08-14 10:34:22Z pax $
 * @link      http://www.fruitday.com
 **/    
namespace bll\pool;

class Cart
{
    
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    /**
     * 结算
     *
     * @return void
     * @author 
     **/
    public function settle($params = array())
    {
        $cartinfo = $params['products'];
        $province = $params['province'];

        $data = array();

        if (!$cartinfo) return array('result' => 0,'msg'=>'products is null');

        // $product_no = array();
        // foreach ($cartinfo as $item) {
        //     $product_no[] = trim($item['prdCode']);
        // }

        // $this->ci->load->model('product_price_model');
        // $this->ci->load->model('product_model');

        // $skulist = array();
        // $tmp = $this->ci->product_price_model->getList('*',array('product_no'=>$product_no));
        // foreach ($tmp as $key => $value) {
        //     $skulist[trim($value['product_no'])] = $value;
        // }

        $cart_item = array();
        foreach ($cartinfo as $key => $value) {
            // $sku = $skulist[trim($value['prdCode'])];
            // $product = $this->ci->product_model->dump(array('id'=>$sku['product_id']),'id');
            $product_no = trim($value['prdCode']);
            $sku = $this->ci->db->query('SELECT s.* from ttgy_product_price as s left join ttgy_product as p on s.product_id=p.id where p.id > 0 and p.online=1 and s.product_no="'.$product_no.'"')->row_array();

            if ($sku) {
                $cart_item['normal_'.$sku['id']] = array(
                    'sku_id' => $sku['id'],
                    'product_id' => $sku['product_id'],
                    'product_no' => $sku['product_no'],
                    'qty' => $value['count'],
                    'item_type' => 'normal',
                ); 
            } else {
                $data['error_items'][] = array(
                    'prdno' => $value['prdCode'],
                    'errmsg' => '商品不存在',
                );
            }
        }

        if ($province) {
	       if($province == '上海市') $province = '上海';	    

            $this->ci->load->model('area_model');
            $area = $this->ci->area_model->dump(array('name'=>$province),'id');
        }

        $this->ci->load->bll('b2ccart',array('terminal'=>1));
        if ($area) {
            $this->ci->bll_b2ccart->set_province($area['id']);
        }

        // 检测
        // if ($cart_item) {
        //     $result = $this->ci->bll_cart->check_cart_item($cart_item);
        // }

        // 由上面重新计算后的cart_item，一定要做判断
        // if ($cart_item) $this->ci->bll_cart->setCart($cart_item);

        $cart_array = $this->ci->bll_b2ccart->setCart($cart_item)->get_cart_info();

        $data['totalAmount'] = (float) $cart_array['total_amount'];
        $data['disAmount'] =  (float) $cart_array['pmt_total'];

        foreach ((array) $cart_array['items'] as $key => $value) {
            $value['sale_price'] or $value['sale_price'] = $value['price'];
            $value['pmt_price'] or $value['pmt_price'] = '0.00';
            $data['order_items'][] = array(
                'prdno'       => $value['product_no'],
                'price'       => (float) $value['price'],
                'discount'    => $value['price'] ? bcdiv($value['sale_price'], $value['price'],3) : 0,
                'disprice'    => (float) $value['pmt_price'],
                'disType'     => 2,
                'count'       => (int) $value['qty'],
                'totalAmount' => (float) $value['amount'],
                'saletype'    =>  !in_array($value['item_type'],array('gift','mb_gift','user_gift','coupon_gift'))  ? 1 : 2,
                'PrimaryCode' => '',
                'disCode'     => '',
                'sale_price'  => (float)$value['sale_price'],
            );
        }

        if ($result == false) {
            foreach ((array) $this->ci->bll_b2ccart->get_error() as $prdno => $errmsg) {
                $data['error_items'][] = array(
                    'prdno' => $prdno,
                    'errmsg' => is_array($errmsg) ? implode(';', $errmsg) : $errmsg,
                );
            }
        }

        $this->rpc_log = array('rpc_desc' => '购物车计算','obj_type'=>'cart');

        return array('result' => 1,'data'=>$data);
    }
}