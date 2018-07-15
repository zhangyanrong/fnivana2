<?php
namespace bll\pc;
include_once("pc.php");

class Survey extends Pc
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
