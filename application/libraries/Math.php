<?php
/**
 * 数字计算
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Math.php 1 2015-01-20 13:14:18Z pax $
 * @link      http://www.fruitday.com
 **/
class Math{

    /**
     * 要保留的小数
     *
     * @var int
     **/
    public $decimals = 2;    

    /**
     * 指定小数点显示的字符 
     *
     * @var string
     **/
    public $dec_point = '.';

    /**
     * 指定千位分隔符显示的字符
     *
     * @var string
     **/
    public $thousands_sep = '';

    /**
     * 加
     *
     * @return void
     * @author 
     **/
    public function add()
    {
        $numbers = func_get_args();

        $add = array_sum($numbers);

        return $this->number_format($add);
    }

    /**
     * 减
     *
     * @return void
     * @author 
     **/
    public function sub()
    {
        $numbers = func_get_args();

        $sub = array_shift($numbers);

        foreach ($numbers as $n) {
            $sub -= $n;
        }

        return $this->number_format($sub);
    }

    /**
     * 乘
     *
     * @return void
     * @author 
     **/
    public function mul()
    {
        $numbers = func_get_args();

        $mul = array_shift($numbers);

        foreach ($numbers as $n) {
            $mul *= $n;
        }

        return $this->number_format($mul);
    }

    /**
     * 除
     *
     * @return void
     * @author 
     **/
    public function div()
    {
        $numbers = func_get_args();

        $div = array_shift($numbers);

        foreach ($numbers as $n) {
            $div = $n == 0 ? 0  : $div/$n;
        }

        return $this->number_format($div);
    }

    /**
     * 格式化
     *
     * @return void
     * @author 
     **/
    public function number_format($number)
    {
        return number_format($number,$this->decimals,$this->dec_point,$this->thousands_sep);
    }
}
