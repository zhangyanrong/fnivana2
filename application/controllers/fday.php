<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Fday extends CI_Controller {
	public function __construct()
    {   
        parent::__construct();
    }

	function sms($action,$type){
		exit($this->return_error('500','error'));
		$params = $this->repost();

		// $this->load->library('fdaylog'); 
  //       $db_log = $this->load->database('db_log', TRUE); 
  //       $this->fdaylog->add($db_log,'sms_oms',$params);

		// if($params['via']!='fruitday'){
		// 	exit($this->return_error('500','Invalid API'));
		// }

		$this->check_sign($params);

		if($action=='send'){
			
			switch ($type) {
				case 'single':
					$this->send_sms($params['mobile'],$params['message']);
					$this->return_error('200','succ');	
					break;
				case 'bulk':
					if(!is_array($params['messages'])){
						$messages = json_decode($params['messages'],1);
					}else{
						$messages = $params['messages'];
					}
					foreach ($messages as $key => $value) {
						$this->send_sms($value['mobile'],urldecode($value['message']));
					}
					$this->return_error('200','succ');			
					break;
				default:
					# code...
					break;
			}
		}
		

		// if(in_array($params['service'],$this->allowed_services)){
		//     if(!isset($params['sign'])){
		//         exit($this->return_error('500','Invalid API sign'));
		//     }

		//     $sign = $params['sign'];
		//     unset($params['sign']);
		//     $validate_sign = $this->create_sign($params);

		//     if($sign != $validate_sign){
		//         exit($this->return_error('500','Invalid API sign'));
		//     }
		// 	if(!empty($_FILES)){
		// 		$params['SYSTEM_FILES'] = $_FILES;
		// 	}

		//     $func = explode('.',$params['service']);
		//     $model_name = $func[0];
		//     $this->load->model($model_name);
  //           $return = $this->$model_name->$func[1]($params);

  //           if(isset($params['jsonpCallback'])){
  //           	exit($params['jsonpCallback'].'('.json_encode($return).')');
  //           }else{
  //           	exit(json_encode($return));
  //           }
		// }else{
		//     exit($this->return_error('500','Invalid API call'));
		// }
	}

	function repost(){
		if(empty($_POST)){
			$_POST = json_decode(file_get_contents("php://input"),1);
		}
    		$params =  array_merge($_POST,$_GET);
		return $params;
	}

	function send_sms($phone,$content){
		$account = 'dh1689';
	    $password = md5('8s7*KYaL');//i_love#fruitday!123
	    $sendSmsAddress = 'http://3tong.net/http/sms/Submit';
	    $message ="<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
	                                ."<message>"
	                                . "<account>"
	                                . $account
	                                . "</account><password>"
	                                . $password
	                                . "</password>"
	                                . "<msgid></msgid><phones>"
	                                . $phone
	                                . "</phones><content>"
	                                . $content
	                                . "</content><subcode>"
	                                ."</subcode>"
	                                ."<sendtime></sendtime>"
	                                ."</message>";
	    $params = array('message' => $message);
	    $data = http_build_query($params);
	    $context = array('http' => array(
	        'method' => 'POST',
	        'header'  => 'Content-Type: application/x-www-form-urlencoded',
	        'content' => $data,
	    ));
	    $contents = file_get_contents($sendSmsAddress, false, stream_context_create($context));
	}

	function send_sms_bak($phone,$content){
		$account = '144';
	    $password = 'ttgy144';
	    $sendSmsAddress = 'http://121.40.60.163:8081/message/sendMsg?';
	    $sendSmsAddress .= "loginname=".$account."&password=".$password."&mobile=".$phone."&content=".$content."&extNo=";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sendSmsAddress);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
	}

	function return_error($response_code,$response_error){
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
	    	if(is_array($v)){
	    		$v = json_encode($v);
	    	}
	        $query .= $k.'='.$v.'&';
	    }
	    $validate_sign = md5(substr(md5($query.SMS_SECRET), 0,-1).'s');
	    if($validate_sign!=$sign){
	    	exit($this->return_error('500','Invalid API sign'));
	    }
	}

	
}
