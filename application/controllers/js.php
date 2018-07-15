<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Js Api
 * User: jack
 */

class Js extends MY_Controller {

    function Js(){
        parent::__construct();
        $this->load->library("memcached");

    }

    function index(){
        $params = $_GET;

        if(empty($params['service']))
        {
            exit($this->return_error('500','Invalid API service'));
        }

        $params['source'] = 'app';
        $params['version'] = '3.2.0';
        $params['timestamp'] = time();

        $params['sign'] = $this->create_sign($params);

        $this->check_sys_params($params);
        $this->check_sign($params);

        $return = $this->process($params);

        if(isset($params['jsonpCallback'])){
            exit($params['jsonpCallback'].'('.json_encode($return).')');
        }else{
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
        $validate_sign = md5(substr(md5($query.API_SECRET), 0,-1).'w');
        $pro_validate_sign = md5(substr(md5($query.PRO_SECRET), 0,-1).'w');
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

        return $this->$obj->$func[1]($params);
    }


    /*
	* 生成接口签名
    */
    private function create_sign($params){
        unset($params['sign']);
        ksort($params);
        $query = '';
        foreach($params as $k=>$v){
            $query .= $k.'='.$v.'&';
        }
        $validate_sign = md5(substr(md5($query.API_SECRET), 0,-1).'w');
        return $validate_sign;
    }
}
