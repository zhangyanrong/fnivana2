<?php
namespace bll;

class Apisd
{
    private $_api_url = 'https://pay.fruitday.com/gateway';     //正式
    private $_payment_id = '20';

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }

    /*
     *  查询卡号余额
     */
    public function doQuery($params)
    {
        $params['payment_id'] = $this->_payment_id;
        $url = $this->createUrl('doQuery',$params);
        $data = $this->curl($url);

        return $data;
    }

    /*
     * 支付金额 － 全额
     */
    public function doPay($params)
    {
        $params['payment_id'] = $this->_payment_id;
        $params['trade_type'] = 'F';
        $url = $this->createUrl('doPay',$params);
        $data = $this->curl($url);

        return $data;
    }

    /*
     * 绑定
     */
    public function doBind($params)
    {
        $params['payment_id'] = $this->_payment_id;
        $url = $this->createUrl('doBind',$params);
        $data = $this->curl($url);

        return $data;
    }

    /*
     * 解绑
     */
    public function unBind($params)
    {
        $params['payment_id'] = $this->_payment_id;
        $url = $this->createUrl('unBind',$params);
        $data = $this->curl($url);

        return $data;
    }

    /*
     * 获取卡列表
     */
    public function getCardList($params)
    {
        $params['payment_id'] = $this->_payment_id;
        $url = $this->createUrl('doCardlist',$params);
        $data = $this->curl($url);

        return $data;
    }

    /*
     * 生成请求连接
     */
    private function createUrl($method,$params)
    {
        $url = $this->_api_url;
        $url .= '/'.$method;
        unset($params['service']);
        $i = 0;
        foreach($params as $k=>$v)
        {
            if($i == 0)
            {
                $url .= '?'.$k.'='.$v;
            }
            else
            {
                $url .= '&'.$k.'='.$v;
            }
            $i++;
        }
        $sign = $this->getSign($params);
        $url .='&sign='.$sign;

        return  $url;
    }


    /*
     * 生成密钥
     */
    private function getSign( $parma , $mer_key='afsvq2mqwc7j0i69uzvukqexrzd1jq6h')
    {
        ksort($parma);
        reset($parma);
        $mac= "";
        foreach($parma as $k=>$v){
            $mac .= "&{$k}={$v}";
        }
        $mac = substr($mac,1);
        $mac = md5($mac.$mer_key);
        return $mac;
    }

    /*
     * 请求连接
     */
    protected function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";
        $encodeArray = Array();
        $postMultipart = false;


        if (is_array($postFields) && 0 < count($postFields)) {

            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {
                    $postBodyString .= "$k=" . urlencode($this->characet($v, $this->postCharset)) . "&";
                    $encodeArray[$k] = $this->characet($v, $this->postCharset);
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }

            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {

            $headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
        } else {

            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {
            return false;
            //throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                return false;
                //throw new Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);
        $reponse =  json_decode($reponse,true);

        return $reponse;
    }

}
