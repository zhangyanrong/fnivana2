<?php

namespace bll\app;
include_once("app.php");

class Msg extends App
{
    function __construct(){
        $this->ci = &get_instance();
    }
}
