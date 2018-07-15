<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class OpenApi extends MY_Controller {
    private $allowed_services = array('open.getMobile','open.getOrangeCard','open.getMsgifts');  //允许访问的接口
    function Api(){
        parent::__construct();
        $this->load->library("memcached");

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
        $validate_sign = md5(substr(md5($query.OPENAPI_SECRET), 0,-1).'w');
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

        if(!isset($params['service']) || !in_array($params['service'],$this->allowed_services)){
            exit($this->return_error('500','service error'));
        }
    }


    /*
    *请求bll
    */
    private function process($params){
        $func = explode('.',$params['service']);
        $service_name = $func[0];
        $this->load->bll($service_name,$params);
        $obj = 'bll_'. $func[0];
        $this->load->library('terminal');
        $this->terminal->set_t($params['source']);

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
