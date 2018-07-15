<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class O2o extends CI_Controller {
	public function __construct()
    {   
        parent::__construct();
    }

	function index(){

		$params = $this->repost();

		$this->check_sys_params($params);
		$this->check_sign($params);

		$return = $this->process($params);

        if(isset($params['jsonpCallback'])){
            exit($params['jsonpCallback'].'('.json_encode($return).')');
        }else{
            exit(json_encode($return));
        }
		

	}

	function repost(){
		if(empty($_POST)){
			$_POST = json_decode(file_get_contents("php://input"),1);
		}
    		$params =  array_merge($_POST,$_GET);
		return $params;
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
	    $validate_sign = md5(substr(md5($query.O2O_SECRET), 0,-1).'w');
	    if($validate_sign!=$sign){
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

	}


	/*
	*请求bll
	*/
	private function process($params){
		$func = explode('.',$params['service']);
		$service_name = 'app'.'/'.$func[0];
		$this->load->bll($service_name,$params);
        
        $obj = 'bll_' . 'app' . '_' . $func[0];

        $this->load->library('terminal');
        $this->terminal->set_t('app');

        return $this->$obj->$func[1]($params);
	}

	public function initNobuilding(){
		//$sql = "SELECT r.id,concat(r.longitude,',',r.latitude) as lonlat ,CONCAT(r4.name,r3.name,r2.name,r.addr) as addr FROM ttgy_o2o_region r JOIN ttgy_o2o_region r1 ON r.pid = r1.id  JOIN ttgy_o2o_region r2 ON r1.pid = r2.id  JOIN ttgy_o2o_region r3 ON r2.pid = r3.id  JOIN ttgy_o2o_region r4 ON r3.pid = r4.id  WHERE r.attr=5";
		$sql = "SELECT id,concat(longitude,',',latitude) as lonlat FROM ttgy_o2o_region WHERE attr=5";
		$res = $this->db->query($sql)->result_array();
		foreach ($res as $value) {
			$sql = "update ttgy_o2o_order_extra set lonlat='".$value['lonlat']."' where building_id=".$value['id']." and lonlat is null";
			$this->db->query($sql);
		}
	}
}
