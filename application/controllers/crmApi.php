<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class CrmApi extends MY_Controller {

    public function __construct(){
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
            $this->load->library('cryaes');
            $this->cryaes->set_key(CRM_DATA_SECRET);
            $this->cryaes->require_pkcs5();
            $decString = $this->cryaes->encrypt(json_encode($return));
            exit($decString);
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

        $this->load->library('cryaes');
        $this->cryaes->set_key(CRM_DATA_SECRET);
        $this->cryaes->require_pkcs5();
        $decString = $this->cryaes->decrypt($params['data']);
        $data = json_decode($decString,true);
        return $data;
        //return json_decode($params['data'],true);
    }

    /*
    *返回方法
    */
    private function return_error($response_code,$response_error){
        $return = json_encode(array('code'=>$response_code,'msg'=>$response_error));
        $this->load->library('cryaes');
        $this->cryaes->set_key(CRM_DATA_SECRET);
        $this->cryaes->require_pkcs5();
        $decString = $this->cryaes->encrypt($return);
        return $decString;
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
        $validate_sign = md5(substr(md5($query.CRM_SECRET), 0,-1).'w');
        $pro_validate_sign = md5(substr(md5($query.CRM_SECRET), 0,-1).'w');
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

        $service_name = $func[0];
        $this->load->bll($service_name,$params);
        $obj = 'bll_'. $func[0];

        return $this->$obj->$func[1]($params);
    }

}
