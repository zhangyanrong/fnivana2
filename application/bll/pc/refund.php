<?php
namespace bll\pc;
include_once("pc.php");

class Refund extends Pc
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
