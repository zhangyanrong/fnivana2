<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// HTTP通讯类 HTTP Lib
// 蔡昀辰 2015-12-31
// usage:
// $params['sign'] = $this->sign($params);
// $resp = $this->ci->http->req([
// 	"url"    =>$this->url,
// 	"method" =>"POST",
// 	"params" =>$params
// ]);
// return $resp->toArray();
class http {

	var $ci;
	var $error;
	var $raw_resp;
	var $info;
	var $statusCode;
	var $opts = [
		"user_agent"=>"PHP HTTP LIB",
		"headers"=>[],
	];

	function __construct($opt = []) {
		$this->ci = &get_instance();
	}

	function __toString() {
		if(strlen($this->error) > 0)
			return $this->error;
		else
			return $this->resp;
	}

	// 发送请求
	public function req($opts) {

		// 处理下参数
		$opts = array_merge($this->opts, $opts);

		// 发送
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $opts["url"]);
		curl_setopt($curl, CURLOPT_POST, strtoupper($opts["method"]) == "POST");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $opts["params"]);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $opts["headers"]);

		$this->resp  = curl_exec($curl);
		$this->info  = curl_getinfo($curl);
		$this->error = curl_error($curl);

		$this->statusCode = $this->info['http_code'];
		curl_close($curl);
		return $this;
	}

	public function toArray() {
		return json_decode($this->resp, true);
	}


}
