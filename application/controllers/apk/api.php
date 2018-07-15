<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Api extends MY_Controller {

    var $secret = "87990a0103940kf32km1o30k99bzf6mzkc73";

    function Api(){
		parent::__construct();
		$this->load->library("memcached");

	}

	function index(){
		$params = $this->repost();
		// $ip = $this->get_real_ip();
		// if(OPEN_MEMCACHE){
		// 	if(in_array($params['service'], $this->config->item('limit_service'))){
		// 		if(strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")){
		// 			exit($this->return_error('500','request times limit'));	
		// 		}
		// 		$limit_tag = md5($ip.$params['service'].date("Y-m-d"));
		// 		$limit_log = $this->get_request_log($limit_tag);
		// 		if($limit_log){
		// 			if($limit_log>50){
		// 				exit($this->return_error('500','request times limit'));	
		// 			}else{
		// 				$this->set_request_log($limit_tag,$limit_log+1,86400);	
		// 			}
		// 		}else{
		// 			$this->set_request_log($limit_tag,1,86400);
		// 		}
		// 	}
		// }
		// if(isset($params['timestamp']) && OPEN_MEMCACHE){
		// 	$check_time = time()-$params['timestamp'];
		// 	$request_log = $this->get_request_log($params['sign']);
		// 	if($request_log){
		// 		if($check_time>30 || $check_time<0){
		// 			exit($this->return_error('500','request timeout'));
		// 		}
		// 	}else{
		// 		$this->set_request_log($params['sign'],$params['timestamp']);
		// 	}
		// }

      
		// config中可以关闭参数检测 by 蔡昀辰
		if(!$this->config->item("check_sys_params_off")) {
			$this->check_sys_params($params);
		}

		// config中可以关闭验签检测 by 蔡昀辰
		if(!$this->config->item("check_sign_off")) {
			$this->check_sign($params);			
		}

		$return = $this->process($params);

        if(isset($params['jsonpCallback'])) {
            exit($params['jsonpCallback'].'('.json_encode($return).')');
        } else {
            exit(json_encode($return));
        }
	
	}

	/*
	*获取入参
	*/
	private function repost(){
		if(empty($_POST)){
			$_POST = json_decode(file_get_contents("php://input"),1);
		}
    	$params =  array_merge($_POST,$_GET);
// $params = $_REQUEST;
        // 临时兼容安卓1.6.0,o2oBUG,下个版本会修复掉
        // if ($params['service']=='o2o.orderInit' 
        //     && $params['source'] == 'app' 
        //     && $params['version']=='1.6.0'
        //     && $params['platform'] == 'ANDROID'
        //     ) {
        //     if ( !isset($params['mobile']) ) {
        //         $params['mobile'] = 'null';
        //     }
        //     if ( !isset($params['name']) ) {
        //         $params['name'] = 'null';
        //     }
        // }

		return $params;
	}

	/*
	*返回方法
	*/
	private function return_error($response_code,$response_error){
        return json_encode(array('code'=>$response_code,'msg'=>$response_error));
	}

	/*
	*签名验证
	*/
	private function check_sign($params){
		if(!isset($params['sign'])){
		     exit($this->return_error('500','Invalid API sign'));
		}
		$sign = $params['sign'];
		unset($params['sign']);
		ksort($params);
	    $query = '';
	    foreach($params as $k=>$v){
	        $query .= $k.'='.$v.'&';
	    }
	    $validate_sign = md5('apk'.substr(md5($query.$this->secret), 0,-3));
	    $pro_validate_sign = md5('apk'.substr(md5($query.PRO_SECRET), 0,-3));
	    if($validate_sign!=$sign && $pro_validate_sign!=$sign){
	    	exit($this->return_error('500','Invalid API sign'));
	    }
	}

	/*
	*系统参数验证
	*/
	private function check_sys_params($params){
		if(!isset($params['timestamp']) || !preg_match("/^[0-9]{10}$/",$params['timestamp'])){
			exit($this->return_error('500','timestamp error'));	
		}

		if(!isset($params['service']) || !in_array($params['service'],$this->config->item('allowed_services'))){
			exit($this->return_error('500','service error'));
		}

		if(!isset($params['source']) || !in_array($params['source'],$this->config->item('validate_source'))){
			exit($this->return_error('500','source error'));
		}
	}


	/*
	*请求bll
	*/
	private function process($params){
		$func = explode('.',$params['service']);
		$service_name = $params['source'].'/'.$func[0];
		$this->load->bll($service_name,$params);
        
        $obj = 'bll_' . $params['source'] . '_' . $func[0];

        $this->load->library('terminal');
        $this->terminal->set_t($params['source']);

		$this->load->library('env'); // 增加环境判断，方便上线切换。 蔡昀辰2016-5

        return $this->$obj->$func[1]($params);
	}
	/*
	*memcached get
	*/
	private function get_request_log($key){
      $request_info = $this->memcached->get($key);
      return $request_info;
    }

    /*
	*memcached set
	*/
    private function set_request_log($key,$value,$time=604800){
      $this->memcached->set($key,$value,$time);
    }

    /*
	*获取ip
    */
    private function get_real_ip(){
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("/^(10│172.16│192.168)./", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }
}
