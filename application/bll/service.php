<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/4/11
 * Time: 下午2:06
 */
namespace bll;
class Service {


    public function __construct($params = array()) {
        $this->ci = &get_instance();
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        if ($session_id) {
            $this->ci->load->library('session', array('session_id' => $session_id));
        }
        $cart_bll_params['session_id'] = $params['connect_id'];
    }

    function getECinfo($params) {
        //暂时关闭电子发票下载
        return array('code'=>300, 'data'=>'','msg'=>'抱歉,电子发票服务器维护中,暂不支持下载');

        $data = array(
            'order' => $params['order'],
        );
        $rs = $this->_realtime_call('http://54.223.98.16:64000/pdf/getPdfInvoice', $data, 'POST', 20);
        if ($rs == false) {
            return array('code' => '300', 'data' => '', 'msg' => '暂时无法开票,请联系客服');
        }
        $info = $rs['req_RECORDS']['req_RECORD'];
        $str = '';
        foreach ($info as $key => $value) {
            if ($value['bhcbz'] == 1) {
                return array('code' => '300', 'data' => '', 'msg' => '红冲发票');
            } else {
                if ($value['fpztdm'] == 1) {
                    $downloadUrl = $this->_downloadEinvoice($params['order'], $value['fphm'], $value['fpdm']);
                    if ($downloadUrl == false) {
                        return array('code' => '300', 'data' => '', 'msg' => '电子商票尚未开出，等商品出库后即可开出，请稍后再过来尝试');
                    } else {
                        return array('code' => '200', 'data' => $downloadUrl, 'msg' => 'succ');
                    }
                }
            }
        }
    }


    private function _downloadEinvoice($order, $invoice_code, $blueFPDM = '') {
        $data = array(
            'order' => $order,
            'invoce_code' => $invoice_code,
            'blueFPDM' => $blueFPDM
        );

        $rs = $this->_realtime_call('http://54.223.98.16:64000/pdf/pdfDownload', $data, 'POST', 20);
        if (!$rs['req_RECORD']['pdf']) {
            return false;
        }
        $pdf = $rs['req_RECORD']['pdf'];

        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
        $keySuffix = uniqid();
        $key = md5($order . $keySuffix);
        $this->redis->hSet('einvoice:filestreams', $key, $pdf);
        return 'http://www.fruitday.com/download/einvoice?order=' . $key;
    }

    private function _realtime_call($url, $odata, $method = 'POST', $timeout = 6) {
        $data = $this->_preprocess_data($odata);

        $this->ci->load->library('aesfp', null, 'encrypt_aes');
        $params = array(
            'data' => $this->ci->encrypt_aes->AesEncrypt($data),
            'signature' => $this->ci->encrypt_aes->data_hash($data),
        );

        $this->ci->load->library('curl', null, 'http_curl');
        $options['timeout'] = $timeout;

        if (defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')) {
            $options['proxy'] = CURL_PROXY_ADDR . ":" . CURL_PROXY_PORT;
        }
        $rs = $this->ci->http_curl->request($url, $params, $method, $options);
        if ($rs['errorNumber'] || $rs['errorMessage']) {

            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);

            $this->_insert_log($url, $odata, $params['data'], 'fail');
            return false;
        }

        $response = json_decode($rs['response'], true);

        // 解密
        $data = $this->ci->encrypt_aes->AesDecrypt($response['data']);

        $data = json_decode($data, true);
        if ($data['code'] != '0000') {
            $this->_error = array('errorNumber' => '', 'errorMessage' => $data['msg']);

            $this->_insert_log($url, $odata, $params['data'], 'fail');
            return false;
        }

        $this->_insert_log($url, $odata, $params['data'], 'succ');
        return $data;
    }

    private function _preprocess_data($data) {
        if (is_array($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        return trim($data);
    }

    /**
     * rpc日志
     *
     * @return void
     * @author
     **/
    private function _insert_log($url, $odata, $encrypt_data, $status) {
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
            case POOL_ORDER_STATUS_URL:
                $log['rpc_desc'] = '订单状态推送';
                $log['obj_name'] = $odata['orderNo'];
                $log['obj_type'] = 'order';
                break;
            case POOL_RECHARGE_URL:
                $log['rpc_desc'] = '充值记录推送';
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

        $this->ci->load->model('rpc_log_model');

        $this->ci->rpc_log_model->insert($log);

    }

}
