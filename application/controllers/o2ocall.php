<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class O2oCall extends CI_Controller
{
    function index()
    {
        $this->load->bll('rpc/o2o/response');
        $this->bll_rpc_o2o_response->process()->output();
    }
}