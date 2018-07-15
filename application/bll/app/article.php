<?php

namespace bll\app;
include_once 'app.php';

class Article extends App
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }
}
