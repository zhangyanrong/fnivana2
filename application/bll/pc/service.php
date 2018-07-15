<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/4/11
 * Time: 下午2:04
 */
namespace bll\pc;
include_once("pc.php");

class Service extends pc {


    public function __construct($params = array()) {
        $this->ci = &get_instance();
    }

}