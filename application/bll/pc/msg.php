<?php
namespace bll\pc;
include_once("pc.php");

class Msg extends Pc
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
