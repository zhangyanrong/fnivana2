<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
defined('PAY_REPAIR_SECRET') or define('PAY_REPAIR_SECRET','nhxqmtgopocjq46frb0s46lvkhbpqi2g');
class payApi extends CI_Controller {
	function PayApi(){
		parent::__construct();
	}

	var $allowed_services = array(
		'paycenter.orderRepair',
		'paycenter.orderCancel',
        'paycenter.orderPayed'
	);
	function index(){
		$params = $this->repost();
		if(in_array($params['service'],$this->allowed_services)){
			if(!isset($params['sign'])){
				exit($this->return_error('500','Invalid API sign'));
			}
			$sign = $params['sign'];
			unset($params['sign']);
			$validate_sign = $this->create_sign($params);
			if($sign != $validate_sign){
				exit($this->return_error('500','Invalid API sign'));
			}

			$func = explode('.',$params['service']);
			$model_name = $func[0];
			$this->load->bll($model_name);
			$obj = 'bll_' .$model_name;
			$return = $this->$obj->$func[1]($params);
			if(isset($params['jsonpCallback'])){
                exit($params['jsonpCallback'].'('.json_encode($return).')');
            }else{
                exit(json_encode($return));
            }			
		}else{
		    exit($this->return_error('500','Invalid API call'));
		}
	}

	function repost(){
		if(empty($_POST)){
			$_POST = json_decode(file_get_contents("php://input"),1);
		}
		return array_merge($_POST,$_GET);
	}

	function return_error($response_code,$response_error){
		return json_encode(array('code'=>$response_code,'msg'=>$response_error));
	}

	function create_sign($params){
		ksort($params);
		$query = '';
		foreach($params as $k=>$v){
			$query .= $k.'='.$v.'&';
		}
		$sign = md5(substr(md5($query.PAY_REPAIR_SECRET), 0,-1).'p');
		return $sign;
	}
}
