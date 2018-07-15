<?php
namespace bll;
/**
* 基类
*/
class Snscomm{
	protected $mem_expire = 10;
	function __construct(){
		$this->ci = &get_instance();
	}

	protected function _before_data($mem_key){
		$mem_data = "";
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if(!$this->ci->memcached){
				$this->ci->load->library('memcached');
			}
			$mem_data = $this->ci->memcached->get($mem_key);
		}
		return $mem_data;
	}
	protected  function _after_data($mem_key,$mem_data){
		if(empty($mem_key)){
			return false;
		}
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if(!$this->ci->memcached){
				$this->ci->load->library('memcached');
			}
			$this->ci->memcached->set($mem_key,$mem_data,$this->mem_expire);
		}
	}
	/**
	 * [果食-检测黑名单]
	 * @param  [type] $params [description]
	 * @return [type]         [description]
	 */
	protected function _ckinvate($params){
		$this->ci->load->model('fruit_invate_model');

		$res = $this->_ckuser();
		if($res['code']!='200'){
			return $res;
		}

		$uid = $res['msg'];
		$invateres = $this->ci->fruit_invate_model->getinvate($uid);
		if($invateres['state']==2){
			return array('code'=>'300','msg'=>'抱歉果友，果食目前只面向部分客户开放');
		}

		return array('code'=>'200','msg'=>$uid);
	}

	/**
	 * [果食-获取用户id]
	 * @param  [string] $session_id [用户标识]
	 * @return [array]             [description]
	 */
	protected function _ckuser(){
		$session =   $this->ci->session->userdata;
		if(empty($session)){
			return array('code'=>'400','msg'=>'not this connect id ,maybe out of date');
		}

		$userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
		if( !isset($userdata['id']) || $userdata['id'] == "" ){
			return array('code'=>'400','msg'=>'not this user,may be wrong connect id');   
		}
		return array('code'=>'200','msg'=>$userdata['id']);
	}
}