<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Api extends MY_Controller {

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

        //仓储设置
        if(isset($params['region_id']))
        {
            //ios-bug
            if($params['region_id'] == 0 || $params['region_id'] == -1 || $params['region_id'] == '')
            {
                $params['region_id'] = '106092';
            }

            if(!empty($params['region_id']))
            {
                $this->config->load("region");
                $region_to_cang = $this->config->item('region_to_cang');
                $cang_id = $region_to_cang[(int)$params['region_id']];

                if(!empty($cang_id))
                {
                    $params['cang_id'] = $cang_id;
                }
            }
        }

        //年轮设置
        if(!empty($params['store_id_list']))
        {
            $store_id = explode(',',$params['store_id_list']);
            $list = array();
            $params['tms_region_type'] = 1;
            $params['tms_region_time'] = 1;
            if(count($store_id) >1)
            {
                $tms_region_type = array();
                $tms_region_time = array();
                foreach($store_id as $k=>$v)
                {
                    if(strpos($v,"T"))
                    {
                        $nian = explode('T',$v);
                        array_push($tms_region_type,$nian[1]);
                        array_push($tms_region_time,$nian[2]);
                        array_push($list,$nian[0]);
                    }
                    else
                    {
                        array_push($list,$v);
                    }
                }
                if(count($tms_region_type) >0)
                {
                    $params['tms_region_type'] = min($tms_region_type);
                    $params['tms_region_time'] = min($tms_region_time);
                }
                $params['store_id_list'] = implode(',',$list);
            }
        }

        //是否支持晚单
        if(empty($params['is_day_night']))
        {
            $params['is_day_night'] = 0;
        }

        // 高德 浙江省宁波市奉化区 区域码临时解决
        if($params['area_adcode'] && $params['area_adcode'] == '330213'){
            $params['area_adcode'] = '330283';
        }


        $return = $this->process($params);

        if(isset($params['jsonpCallback'])) {
            exit($params['jsonpCallback'].'('.json_encode($return).')');
        } else {
            exit(json_encode($return));
        }

    }

    function test() {

        $this->load->library('session');

        // dev和staging环境才可以访问
        $server_name     = php_uname("n");
        $allowed_servers = ['ip-10-0-1-236', 'ip-10-0-1-55', 'vagrant-ubuntu-trusty'];
        if( !in_array($server_name, $allowed_servers) )
            die("apidoc machine not allowed");

        $params["source"]     = "app";
        $params["version"]    = "9.9.9";
        $params["timestamp"]  = time();

        $params = array_merge($params, $_POST);
        $params = array_merge($params, $_GET);

        // $session = $this->session->all_userdata();
        //
        // if($session)
        // 	$user_data = unserialize($session['user_data']);
        //
        // if( !$params["connect_id"] && $user_data['id'] )
        // 	$params["connect_id"] = $session['session_id'];

        if(!$params["connect_id"] && $_COOKIE['connect_id'])
            $params["connect_id"] = $_COOKIE['connect_id'];

        $params["password"] = $params["password"] ? md5($params["password"]) : '';

        $result = $this->process($params);

        echo json_encode($result);

        if($result['connect_id'])
            setcookie('connect_id', $result['connect_id']);

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

        if(!isset($params['source']) || !in_array($params['source'],$this->config->item('validate_source'))){
            exit($this->return_error('500','source error'));
        }

        if(!isset($params['version'])){
            exit($this->return_error('500','version error'));
        }

        if($params['version'] < '5.7.0' && $params['source'] == 'app' && date('Y-m-d') >= '2017-03-13'){
            exit($this->return_error('900','发现新版本，更多优惠更好推荐，还有神秘游戏等你玩哟～'));
        }

        if($params['version'] < '3.9.0' && $params['source'] == 'app'){
            exit($this->return_error('900','发现新版本，更多优惠更好推荐，还有神秘游戏等你玩哟～'));
        }

    }


    /*
    *请求bll
    */
    private function process($params){

        $this->load->library('terminal');
        $this->terminal->set_t($params['source']);

        $func = explode('.',$params['service']);
        $service_name = $params['source'].'/'.$func[0];
        $this->load->bll($service_name,$params);

        $obj = 'bll_' . $params['source'] . '_' . $func[0];

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
