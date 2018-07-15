<?php
namespace bll\rpc;

class Response {

    private $_data_format = 'json';

    private $_response_data = null;

    private $_error = null;

    private $_rpc_log = array();

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library('aes',null,'encrypt_aes');
    }

    /**
     * 签名认证
     *
     * @return void
     * @author 
     **/
    public function sign_check($data,$sign)
    {
        $data_hash = $this->ci->encrypt_aes->data_hash($data);

        if ($data_hash !== $sign) {
            $this->_error = array('result' => 0, 'msg' => '验签失败');

            return false;
        }
        return true;
    }

    /**
     * 系统级参数验证
     *
     * @return void
     * @author 
     **/
    public function param_check($method)
    {
        if (!$method) {
            $this->_error = array('result' => 0, 'msg' => '缺少系统级参数');
            return false;
        }

        list($diretory) = explode('.',$method);
        if (!in_array($diretory, array('app','wap','pool'))) {
            $this->_error = array('result' => 0, 'msg' => '接口不支持');
            return false;
        }

        return true;
    }


    /**
     * 处理
     *
     * @return void
     * @author 
     **/
    public function process($method,$data,$signature)
    {
        $this->ci->load->library('aes',null,'encrypt_aes');
        $data = $this->ci->encrypt_aes->AesDecrypt($data);

        $match = $this->sign_check($data,$signature);

        if (!$match) return $this;

        $data = json_decode($data,true);

        $correct = $this->param_check($method);

        if (!$correct) return $this;

        $pos = strrpos($method, '.');
        $act = false ===  $pos ? 'index' : substr($method, $pos+1);
        $class = false !== $pos ? substr($method, 0,$pos) : $method;
        $name = 'bll_' . str_replace('.', '_', $class);
        $class = str_replace('.', '/', $class);

        $this->ci->load->bll($class,null,$name);

        if ( method_exists($this->ci->{$name}, $act)) {

            $this->ci->{$name}->response_obj = $this;

            $res_data = call_user_func_array(array($this->ci->{$name},$act),array($data));

            if ($res_data !== false) {
                $this->_response_data = $res_data;
            }

            if ($this->ci->{$name}->rpc_log) $this->_rpc_log = $this->ci->{$name}->rpc_log;
        }

        $status = 'succ';

        // 记日志
        if ($this->_error || $res_data['result'] === 0) {
            $status = 'fail';
        }

        $this->insert_log($method,$data,$res_data,$status);

        return $this;
    }

    /**
     * 输出
     *
     * @return void
     * @author 
     **/
    public function output()
    {
        $output = $this->_error ? $this->_error : $this->_response_data;

        $output = json_encode($output,JSON_UNESCAPED_UNICODE);

        $this->ci->load->library('aes',null,'encrypt_aes');

        $params = array(
            'data' => $this->ci->encrypt_aes->AesEncrypt($output),
            'signature' => $this->ci->encrypt_aes->data_hash($output),
        );

        switch ($this->_data_format) {
            case 'json':
                echo stripslashes(json_encode($params));
                break;
            case 'xml':
                
                break;
        }
    }

    /**
     * rpc日志
     *
     * @return void
     * @author 
     **/
    private function insert_log($method,$resp,$result,$status)
    {
        $log = array(
            'data' => serialize(array('req'=>$resp,'resp'=>$result)),
            'createtime' => time(),
            'type' => 'response',
            'status' => $status,
        );

        if ($status == 'fail') {
            $result['msg'] or $result['msg'] = $result['errorMsg'];
            $log['errorNumber'] = '';
            $log['errorMessage'] = $this->_error['msg'] ? $this->_error['msg'] : $result['msg'] ;
        }

        switch ($method) {
            case 'pool.order.callback':
                $log['rpc_desc'] = '订单回调';
                $log['obj_type'] = 'order';
                $log['obj_name'] = $resp['orderNo'];
                break;
            default:
                
                break;
        }

        if (is_array($this->_rpc_log)) {
            $allow_key = array('rpc_desc','obj_type','obj_name');
            foreach ($allow_key as $value) {
                if ($this->_rpc_log[$value]) $log[$value] = $this->_rpc_log[$value];
            }
        }

        $this->ci->load->model('rpc_log_model');

        $this->ci->rpc_log_model->insert($log);

    }
}