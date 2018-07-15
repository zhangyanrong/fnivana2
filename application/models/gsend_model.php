<?php
class Gsend_model extends MY_Model 
{
    /**
    * 赠品活动
    *
    * @var string
    **/
    const _TABLE_NAME = 'gift_send';

    public function table_name()
    {
        return self::_TABLE_NAME;
    }
}