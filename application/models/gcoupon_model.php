<?php
class Gcoupon_model extends MY_Model 
{
    /**
    * 礼品券表
    *
    * @var string
    **/
    const _TABLE_NAME = 'user_gift_card';

    public function table_name()
    {
        return self::_TABLE_NAME;
    }
}