<?php
namespace bll\rpc;

class Request54 {
    private $_data_format = 'json';

    private $_error = array();

    private $_rpc_log = array();

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function set_rpc_log($rpc_log)
    {
        $this->_rpc_log = $rpc_log;

        return $this;
    }

    /**
     * 获取错误信息
     *
     * @return void
     * @author 
     **/
    public function get_errorinfo()
    {
        return $this->_error;
    }

    public function set_data_format($data_format)
    {
        $this->_data_format = $data_format;
    }

    public function preprocess_data($data)
    {
        if (is_array($data)) {
            switch ($this->_data_format) {
                case 'json':
                    return json_encode($data,JSON_UNESCAPED_UNICODE);
                    break;
                case 'serialize':
                    return serialize($data);
                    break;
                default:
                    return '';
                    break;
            }
        }

        return trim($data);
    }

    /**
     * 实时请求
     *
     * @return void
     * @author 
     **/
    public function realtime_call($url,$odata,$method = 'POST',$timeout = 6)
    {
        $data = $this->preprocess_data($odata);

        $this->ci->load->library('aes54',null,'encrypt_aes54');
        $params = array(
            'data' => $this->ci->encrypt_aes54->AesEncrypt($data),
            'signature' => $this->ci->encrypt_aes54->data_hash($data),
        );

        $this->ci->load->library('curl',null,'http_curl');

        $options['timeout'] = $timeout;
        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
        }

        $rs = $this->ci->http_curl->request($url,$params,'POST',$options);

        if ($rs['errorNumber'] || $rs['errorMessage']) {

            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);
            
            $this->insert_log($url,$odata,$params['data'],'fail');
            return false;
        }

        $response = json_decode($rs['response'],true);

        // 解密
        $data = $this->ci->encrypt_aes54->AesDecrypt($response['data']);

        $data = json_decode($data,true);

        if ($data['result'] != '1') {
            $this->_error = array('errorNumber' => '', 'errorMessage' => $data['msg']);

            $this->insert_log($url,$odata,$params['data'],'fail');
            return false;
        }

        $this->insert_log($url,$odata,$params['data'],'succ');

        return $data;
    }

    /**
     * rpc日志
     *
     * @return void
     * @author 
     **/
    private function insert_log($url,$odata,$encrypt_data,$status)
    {
        $data = array(
                'origin_data' => $odata,
                'encrypt_data' => $encrypt_data,
        );
        $log = array(
            'data' => serialize($data),
            'createtime' => time(),
            'type' => 'request',
            'status' => $status,
        );

        if ($status == 'fail') {
            $log['errorNumber'] = $this->_error['errorNumber'];
            $log['errorMessage'] = $this->_error['errorMessage'];
        }

        switch ($url) {
            case POOL_ORDER_URL:
                $log['rpc_desc'] = '订单推送';
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

        $urlpath = parse_url($url);
        $this->ci->load->model('rpc_log_model');

        if ($urlpath['host'] != '122.144.167.54')
            $this->ci->rpc_log_model->insert($log);

    }
}