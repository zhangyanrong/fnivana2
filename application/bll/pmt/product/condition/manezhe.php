<?php
namespace bll\pmt\product\condition;

require_once('condition_abstract.php');
class Manezhe extends Condition_abstract
{
    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$item,$cart_info)
    {
        $goods_cost = 0;

        foreach ($cart_info['items'] as $value) {
            $goods_cost += $value['price'] * $value['qty'];
        }

        return $goods_cost > $condition['totalamount'] && in_array($item['product_id'], $condition['product_id']) ? false : true;
    }

    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function get_tag()
    {
        return '满额折';
    }

    public function meet($pmt_id,$condition,$cart_info)
    {
        $meet = false;

        $goods_cost = 0; $cart_product_id = array();

        foreach ($cart_info['items'] as $value) {
            $goods_cost += $value['price'] * $value['qty'];

            if ($value['item_type'] == 'normal') $cart_product_id[] = $value['product_id'];
        }

        if ($goods_cost < $condition['totalamount'] && array_intersect($condition['product_id'], $cart_product_id)) {
            $meet = true;
        }

        return $meet;
    }

    public function get_solution($pmt_id,$solution,$cart_info)
    {

    }

    public function get_alert_title($pmt_id,$pmt,$cart_info)
    {
        $solution = array();
        foreach ($pmt['solution']['money'] as $key => $value) {
            $solution[$value['product_id']] = $value;
        }

        $title = '订单满'.$pmt['condition']['totalamount'].'元：';
        foreach ($cart_info['items'] as $key => $value) {
            if ($value['item_type'] == 'normal' && isset($solution[$value['product_id']])) {
                $title .= $value['name'].'仅售￥'.$solution[$value['product_id']]['price'].'、';
            }
        }

        return array('url'=>false, 'tag'=>'折','title'=>$title,'pmt_id'=>$pmt_id);
    }
}