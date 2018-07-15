<?php

namespace bll\app;
include_once("app.php");

class Apisd extends App
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
