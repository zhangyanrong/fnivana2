<?php
namespace bll\pmt\order\condition;

abstract class Condition_abstract 
{
    function __construct()
    {
        $this->ci = & get_instance();
    }

    public function filter($condition,$cart_info,$region='')
    {
        return true;
    }

    /**
     * 过滤出下一个优惠活动
     *
     * @return void
     * @author 
     **/
    public function filter_next_pmt($condition,$cart_info,$region='')
    {
    }
}