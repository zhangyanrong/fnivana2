<?php

class Test extends CI_Controller {

	var $server_name;
	var $allowed_servers = ['ip-10-0-1-59'];

	var $urls = [
		'test'=>'http://caiyunchen.nirvana.guantest.fruitday.com/api'
	];
	var $secrets = [
		'test'=>"caa21c26dfc990c7a534425ec87a111c"
	];
	
	function __construct() {
		parent::__construct();
		$this->server_name = php_uname("n");
		$this->load->library("memcached");		
		$this->check();
	}

	private function check() {
		// dev和staging环境才可以访问
		if ( !in_array($this->server_name, $this->allowed_servers) )
			die("not allowed");		
	}


	public function index() {

		$params["source"]     = "app";
		$params["version"]    = "4.0.0";
		$params["timestamp"]  = time();

		$params = array_merge($params, $_POST);
		$params = array_merge($params, $_GET);

		if( !$params["connect_id"] && $this->memcached->get('connect_id') )
			$params["connect_id"] = $this->memcached->get('connect_id');

		$params["password"]   = $params["password"] ? md5($params["password"]): '';
		$params["sign"]       = $this->nirvanaSign($params);	

		// echo json_encode($params);die;

        $result = $this->post($params);

		echo $result;

		$result = json_decode($result);
		
		// 保存登录状态
		if($result->connect_id)
			$this->memcached->set('connect_id', $result->connect_id, 3600);
	}

	private function post($params) {
        $opts = ['http'=>
			[
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query($params) 
			]
		];
        $context = stream_context_create($opts);
        return file_get_contents($this->urls['test'], false, $context);		
	}

    private function nirvanaSign($params) {
        unset($params['sign']);
        ksort($params);//以键升序排列
        $query = "";
        foreach($params as $k=>$v){
            $query .= $k."=".$v."&";
        }//拼接成get字符串
        $sign = md5(substr(md5($query.$this->secrets['test']), 0,-1)."w");
        //字符串拼接密钥后md5加密,去处最后一位再拼接"w"，再md5加密
        return $sign; 
    } 	


}