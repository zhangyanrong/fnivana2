<?php
namespace bll\wap;
/**
* wap接口基类
*/
class Wap{
	public static $bll_obj;
	function __construct(){
		$this->ci = &get_instance();
	}

	function __call($func_name,$params){
		$func = explode('.',$params[0]['service']);
		$service_name = $func[0];
		$this->ci->load->bll($service_name,$params[0]);
		$obj = 'bll_'.$service_name;
		return $this->ci->$obj->$func[1]($params[0]);
	}

	function call_bll($params){
		$func = explode('.',$params['service']);
		$service_name = $func[0];
		$this->ci->load->bll($service_name,$params);
		$obj = 'bll_'.$service_name;
		self::$bll_obj = $this->ci->$obj;
		return $this->ci->$obj->$func[1]($params);	
	}
}