<?php
/**
 * 终端判断
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   libraries
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Terminal.php 1 2015-01-27 17:30:21Z pax $
 * @link      http://www.fruitday.com
 **/
class Terminal
{
    private $_t = 'pc';

    public function set_t($t)
    {
        $this->_t = $t;
        return $this;
    }

    public function is_app()
    {
        return $this->_t == 'app' ? true : false;
    }

    public function is_wap()
    {
        return $this->_t == 'wap' ? true : false;
    }

    public function is_web()
    {
        return $this->_t == 'pc' ? true : false;
    }

    public function getname()
    {
        $name='';

        switch ($this->_t) {
            case 'app':
                $name='APP';
                break;
            case 'wap':
                $name='WAP';
                break;
            case 'pc':
                $name='官网';
                break;
        }

        return $name;
    }

    public function get_source()
    {
        return $this->_t;
    }

    public function get_channel()
    {
        $channel='1';

        switch ($this->_t) {
            case 'app':
                $channel='6';
                break;
            case 'wap':
                $channel='2';
                break;
            case 'pc':
                $channel='1';
                break;
        }

        return $channel;
    }
}
