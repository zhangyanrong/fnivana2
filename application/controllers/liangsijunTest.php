<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class liangsijunTest extends CI_Controller {
    public function __construct()
    {
        parent::__construct();
    }

    function index(){

        echo 1111;die;


    }

    function test(){
        echo date_default_timezone_get();
    }
}
