<?php
namespace bll\rpc\o2o;

class Request
{

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

    /**
     * 请求
     *
     * @param $params ['url']
     * @param $params ['appId']
     * @param $params ['method']
     * @param $params ['v']
     * @param $params ['jsonData']
     * @param $params ['secret']
     * @param $params ['cnone']
     * @param $params ['timestamp']
     *
     * @param int $timeout
     * @return mixed
     */
    public function realtime_call($params, $timeout = 6)
    {
        $this->ci->load->library('poolhash');
        $this->ci->load->library('aes', null, 'encrypt_aes');
        $this->ci->load->library('curl', null, 'http_curl');

        $nParams = array(
            'appId'     => !empty($params['appId'])     ? $params['appId'] : POOL_O2O_OMS_APPID,
            'cnone'     => !empty($params['cnone'])     ? $params['cnone'] : $this->gen_uuid(),
            'timestamp' => !empty($params['timestamp']) ? $params['timestamp'] : date('Y-m-d H:i:s'),
            'method'    => !empty($params['method'])    ? $params['method'] : '',
            'v'         => !empty($params['v'])         ? $params['v'] : POOL_O2O_OMS_VERSION,
            'data'      => is_array($params['data'])    ? json_encode($params['data'], JSON_UNESCAPED_UNICODE) : $params['data'],
        );

        $secret = !empty($params['secret']) ? $params['secret'] : POOL_O2O_OMS_SECRET;
        unset($params['secret']);

        $nParams['sign'] = $this->ci->poolhash->create_sign($nParams, $secret);
        $nParams['data'] = urlencode($this->ci->encrypt_aes->AesEncrypt($nParams['data'], !empty($params['aesKey']) ? $params['aesKey'] : base64_decode(POOL_O2O_AES_KEY)));

        $options['timeout'] = $timeout;
        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
        }
        $rs = $this->ci->http_curl->request($params['url'], $nParams, 'POST', $options);

        if ($rs['errorNumber'] || $rs['errorMessage']) {

            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);

            $this->insert_log($params['url'], $params['data'], $nParams['data'], 'fail');
            return false;
        }

        $response = json_decode($rs['response'], true);

        if ($response['code'] != '1000' && $response['code'] != '200') {
            $this->_error = array('errorNumber' => $response['code'], 'errorMessage' => $response['msg']);

            $this->insert_log($params['url'], $params['data'], $nParams['data'], 'fail');
            return false;
        }
        if(!empty($response['data'])){
            $response['data'] = urldecode($response['data']);
            $data = $this->ci->encrypt_aes->AesDecrypt($response['data'], !empty($params['aesKey']) ? $params['aesKey'] : base64_decode(POOL_O2O_AES_KEY));
            //$data = $response['data'];

            $data = json_decode($data, true);
        }else{
            $data = true;
        }

        $this->insert_log($params['url'], $params['data'], $nParams['data'], 'succ');

        return $data;
    }

    /**
     * rpc日志
     *
     * @return void
     * @author
     **/
    private function insert_log($url, $odata, $encrypt_data, $status)
    {
        $data = array(
            'origin_data' => $odata,
            'encrypt_data' => $encrypt_data,
        );
        $log = array(
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'createtime' => date("Y-m-d H:i:s"),
            'type' => 'request',
            'status' => $status,
        );

        if ($status == 'fail') {
            $log['errorNumber'] = $this->_error['errorNumber'];
            $log['errorMessage'] = $this->_error['errorMessage'];
        }

        switch ($url) {
            case POOL_O2O_OMS_URL:
                $log['rpc_desc'] = 'o2o订单推送';
                break;

            default:

                break;
        }

        if (is_array($this->_rpc_log)) {
            $allow_key = array('rpc_desc', 'obj_type', 'obj_name');
            foreach ($allow_key as $value) {
                if ($this->_rpc_log[$value]) $log[$value] = $this->_rpc_log[$value];
            }
        }

        $this->ci->load->model('o2o_rpc_log_model');

        $this->ci->o2o_rpc_log_model->insert($log);

    }

    private function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * @param $params
     * $params参数:
     *      method
     *      v
     *      appKey
     *      ts
     *      自定义参数
     * @return mixed
     */
    public function tms_call($params){
        $this->ci->load->library('curl', null, 'http_curl');
        $url = POOL_O2O_TMS_URL;
        $params += array(
            'v'         => POOL_O2O_TMS_VERSION,
            'appKey'    => POOL_O2O_TMS_APPKEY,
            'ts'        => time() . '000'
        );
        $params['sign'] = $this->create_tms_sign($params);

        $options['timeout'] = 100;
        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
        }

        $rs = $this->ci->http_curl->request($url, $params, 'POST', $options);

        if ($rs['errorNumber'] || $rs['errorMessage']) {
            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);
            $this->insert_log($url, '', $params, 'fail');
            return false;
        }

        $response = json_decode($rs['response'], true);

        if (empty($response['success'])) {
            $this->_error = array('errorNumber' => $response['errorCode'], 'errorMessage' => $response['message']);
            $this->insert_log($url, '', $params, 'fail');
            return false;
        }
        $this->insert_log($url, '', $params, 'succ');
        return $response;
    }


    /**
     * @param $params
     * $params参数:
     *      method
     *      v
     *      appKey
     *      ts
     *      自定义参数
     * @return mixed
     */
    public function tmsware_call($params){
        $this->ci->load->library('curl', null, 'http_curl');
        $url = POOL_WARE_TMS_URL;
        $params += array(
            'v'         => POOL_O2O_TMS_VERSION,
            'appKey'    => POOL_O2O_TMS_APPKEY,
            'ts'        => time() . '000'
        );
        $params['sign'] = $this->create_tms_sign($params);

        $options['timeout'] = 100;
        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
        }

        $rs = $this->ci->http_curl->request($url, $params, 'POST', $options);

        if ($rs['errorNumber'] || $rs['errorMessage']) {
            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);
            //$this->insert_log($url, '', $params, 'fail');
            return false;
        }

        $response = json_decode($rs['response'], true);

        if (empty($response['success'])) {
            $this->_error = array('errorNumber' => $response['errorCode'], 'errorMessage' => $response['message']);
            //$this->insert_log($url, '', $params, 'fail');
            return false;
        }
        //$this->insert_log($url, '', $params, 'succ');
        return $response;
    }

    /**
     * @param $params
     * $params参数:
     *      method
     *      v
     *      appKey
     *      ts
     *      自定义参数
     * @return mixed
     */
    public function tmscitybox_call($params){
        $this->ci->load->library('curl', null, 'http_curl');
        $url = POOL_BOX_TMS_URL;
        $params += array(
            'v'         => POOL_O2O_TMS_VERSION,
            'appKey'    => POOL_O2O_TMS_APPKEY,
            'ts'        => time() . '000'
        );
        $params['sign'] = $this->create_tms_sign($params);

        $options['timeout'] = 100;
        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
        }

        $rs = $this->ci->http_curl->request($url, $params, 'POST', $options);

        if ($rs['errorNumber'] || $rs['errorMessage']) {
            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);
            //$this->insert_log($url, '', $params, 'fail');
            return false;
        }

        $response = json_decode($rs['response'], true);

        if (empty($response['success'])) {
            $this->_error = array('errorNumber' => $response['errorCode'], 'errorMessage' => $response['message']);
            //$this->insert_log($url, '', $params, 'fail');
            return false;
        }
        //$this->insert_log($url, '', $params, 'succ');
        return $response;
    }

    public function create_tms_sign($params){
        $sign = '';
        if(!empty($params) && is_array($params)){
            ksort($params);
            foreach($params as $k => $v){
                $sign .= $k . $v;
            }
            $sign = sha1($sign . POOL_O2O_TMS_SECRET);
        }

        return strtoupper($sign);
    }
}