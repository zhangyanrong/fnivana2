<?php
namespace bll\pc;
include_once("pc.php");

class Point extends Pc
{
    function __construct(){
        $this->ci = &get_instance();
    }
}