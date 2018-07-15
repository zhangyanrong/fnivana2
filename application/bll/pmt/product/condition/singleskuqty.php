<?php
namespace bll\pmt\product\condition;

require_once('condition_abstract.php');
class Singleskuqty extends Condition_abstract
{
    
    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$item,$cart_info)
    {
        $product_id = $condition['product_id'];
        $qty = $condition['qty'];

        if (in_array($item['product_id'],$product_id) && $item['qty'] >= $qty) {
            return false;
        }
        
        return true;
    }

        /**
     * 
     *
     * @return void
     * @author 
     **/
    public function get_tag()
    {
        return '单品优惠';
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