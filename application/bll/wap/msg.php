<?php
namespace bll\wap;
include_once("wap.php");

class Msg extends wap
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
