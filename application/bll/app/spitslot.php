<?php

namespace bll\app;
include_once("app.php");

class Spitslot extends App
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
