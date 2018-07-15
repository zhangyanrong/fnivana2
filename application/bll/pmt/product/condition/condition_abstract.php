<?php
namespace bll\pmt\product\condition;

abstract class Condition_abstract
{
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$item,$cart_info)
    {
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
        return '';
    }

    public function meet($pmt_id,$condition,$cart_info)
    {
        return false;
    }

    public function get_solution($pmt_id,$solution,$cart_info)
    {
        return array();
    }

    public function get_alert_title($pmt_id,$pmt,$cart_info)
    {
        return array();
    }
}