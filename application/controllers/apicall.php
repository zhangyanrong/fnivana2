<?php
class Apicall extends CI_Controller{

    public function index()
    {
        $data      = str_replace(' ','+',trim($_POST['data']));
        $signature = trim($_POST['signature']);
        $method    = trim($_POST['method']);

        $this->load->bll('rpc/response','bll_rpc_response');

        $this->bll_rpc_response->process($method,$data,$signature)->output();
    }

    function test(){
    	return false;
    	$this->load->bll('pool/order',null,'bll_pool_order');
        $this->bll_pool_order->cancel(array('a'));	
    }
}