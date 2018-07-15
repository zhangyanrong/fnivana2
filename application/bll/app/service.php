<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/4/26
 * Time: 下午5:56
 */

namespace bll\app;
include_once("app.php");

class Service extends App
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
