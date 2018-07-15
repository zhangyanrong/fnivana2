<?php
namespace bll\pmt\product\condition;

require_once('condition_abstract.php');
class Pkgqty extends Condition_abstract
{
    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$item,$cart_info)
    {
        $satify_num = 0;

        $product_id = $condition['product_id'];

        foreach ($cart_info['items'] as $value) {
            if (in_array($value['product_id'],$product_id) && $value['qty'] >= 1) {
                $satify_num++;
            }
        }

        return ($satify_num > 0 && $satify_num == count($product_id)) ? false : true;
    }

    /**
     * 
     *
     * @return void
     * @author 
     **/
    public function get_tag()
    {
        return '捆绑';
    }

    public function meet($pmt_id,$condition,$cart_info)
    {
        return false;
    }

    public function get_solution($pmt_id,$solution,$cart_info)
    {

    }

    public function get_alert_title($pmt_id,$pmt,$cart_info)
    {
        return array();
    }
}