<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// 通知中心 Notify Lib
// 蔡昀辰 2015-12-31
// usage:
// $this->load->library("notify");



// single 立即发送一条：

// 短信：
// $type    = ["sms"];
// $target  = ["mobile"=>"1862168xxxx"];
// $message = ["body"=>"恭喜您中了500箱苹果！"];

// app：
// $type    = ["app"]; 
// $target  = ["uid"=>"3528371"];
// $message = ["title"=>"天天果园通知", "body"=>"恭喜您中了500箱苹果！"];
// $extras  = ["tabType"=>"HomeMarket", "type"=>"6", "page_url"=>"a", "page_photo"=>"b"]

// email：
// $type    = ["email"];
// $target  = ["email"=>"caiyunchen@fruitday.com"];
// $message = ["title"=>"天天果园通知", "body"=>"恭喜您中了500箱苹果！"];

// 一起发送：
// $type    = ["sms", "email", "app"];
// $target  = ["mobile"=>"1862168xxxx", "email"=>"caiyunchen@fruitday.com", "uid"=>"3528371"];
// $message = ["title"=>"天天果园通知", "body"=>"恭喜您中了500箱苹果！"];

// $params = [
// 	"source"  => "api",
// 	"mode"    => "single", 
// 	"type"    => json_encode($type),
// 	"target"  => json_encode($target),
// 	"message" => json_encode($message),
// 	"extras"  => json_encode($extras),
// ];

// $this->notify->send($params);
// 返回：Array ( [code] => 200 [msg] => send app success );





// group 批量(队列)发送相同内容(给不同用户)
// email：
// $type    = ["email"];
// $target  = [
//	["email"=>"caiyunchen@fruitday.com"],
//	["email"=>"lusc@fruitday.com"]
//];
// $message = ["title"=>"天天果园通知", "body"=>"恭喜您中了500箱苹果！"];
//
// $params = [
// 	"source"  => "api",
// 	"mode"    => "group", 
// 	"type"    => json_encode($type),
// 	"target"  => json_encode($target),
// 	"message" => json_encode($message),
// ];

// $this->notify->send($params);
// 返回：Array ( [code] => 200 [msg] => commit jobs success );






// bulk 批量(队列)发送不同内容(给不同用户)
// email：
// $type    = ["email"];
// $target  = [
//	["email"=>"caiyunchen@fruitday.com"],
//	["email"=>"lusc@fruitday.com"]
//];
// $message = [
// 		["title"=>"天天果园通知", "body"=>"恭喜您中了500箱苹果！"],
// 		["title"=>"天天果园通知", "body"=>"恭喜您中了500箱橙子！"]
// ];
//
// $params = [
// 	"source"  => "api",
// 	"mode"    => "bulk", 
// 	"type"    => json_encode($type),
// 	"target"  => json_encode($target),
// 	"message" => json_encode($message),
// ];

// $this->notify->send($params);
// 返回：Array ( [code] => 200 [msg] => commit jobs success );

class notify {

	var $ci;
	var $error;
	var $url = "http://sms.fruitday.com/notify/send"; // prod 10.168.126.48

	function __construct($params = []) {
		$this->ci = &get_instance();
		$this->ci->load->library('http');
	}

	public function send(Array $params) {
		$params['sign'] = $this->sign($params);

		$resp = $this->ci->http->req([
			"url"    =>$this->url,
			"method" =>"POST",
			"params" =>$params
		]);
    	return $resp->toArray();
    }	

    private function sign(Array $params) {
        unset($params['sign']);
        ksort($params);
        $query = '';
        foreach($params as $k=>$v) {
            $query .= $k.'='.$v.'&';
        }
        $valid_sign = md5(substr(md5($query.SMS_SECRET), 0,-1).'s');

        return $valid_sign;     	
    }    

}