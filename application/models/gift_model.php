<?php
/**
 * 赠品
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   model
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: gift_model.php 1 2014-12-18 11:32:43 pax $
 * @link      http://www.fruitday.com
 **/
class Gift_model extends MY_Model {
    /**
     * 规格表
     *
     * @var string
     **/
    const _TABLE_NAME = 'product_gifts';

    public function table_name()
    {
        return self::_TABLE_NAME;
    }
}