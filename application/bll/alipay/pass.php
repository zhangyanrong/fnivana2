<?php

namespace bll\alipay;


/**
 *
 *@desc 支付宝卡券平台
 *@author cuiyang
 **/
class Pass
{
    
    private $app_id = '2014070400006820';
    
    private $partner_id = '2088701449339747';
    
    private $gatewayUrl = 'https://openapi.alipay.com/gateway.do';
    
    private $certPath = '';
    private $signType = 'RSA';
    private $format = 'JSON';
    private $charset = 'UTF-8';
    private $app_version = '1.0';
    private $postCharset = 'UTF-8';
	private $fileCharset = "UTF-8";

    public function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->helper('public');
        
        $this->certPath = FCPATH . "application/cert/";
        $this->alipayPublicKey = $this->certPath . 'alipay_public_key.pem';
        $this->rsaPrivateKeyFilePath = $this->certPath . 'app_private_key.pem';
    }
    
    
    /**
     * 支付宝pass新建卡券实例接口
     * @param array $aParam
     * @return array
     */
    public function add( $params )
    {
        
        $sysParams = $this->initSysParams( 'alipay.pass.instance.add' );
        
		//设置业务参数
        $apiParams['tpl_id'] = $params['tpl_id'];
        $apiParams['recognition_type'] = $params['recognition_type'];
        $apiParams['recognition_info'] = $params['recognition_info'];
        
        if( isset($params['tpl_params']) ){
            $apiParams['tpl_params'] = $params['tpl_params'];    
        }
        
		//签名
		$sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams), $this->signType);
        
		//系统参数放入GET请求串
		$requestUrl = $this->gatewayUrl . "?";
		foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($this->characet($sysParamValue, $this->postCharset)) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);
        
		//发起HTTP请求
        $resp = $this->curl($requestUrl, $apiParams);
        
        // $log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------add\r\n";
        // $log .= var_export($params,true)."\r\n";
        // $log .= var_export($resp,true)."\r\n";
        // $this->ci->fdaylog->add($db_log,'alipay_pass', $log  );
        
        exit($resp);
    }
    
    
    /**
     * 支付宝pass 核销接口
     * @param array $aParam
     * @return array
     */
    public function update( $params )
    {
        $sysParams = $this->initSysParams( 'alipay.pass.instance.update' );
        
		//设置业务参数
        $apiParams['serial_number'] = $params['serial_number'];
        $apiParams['channel_id'] = $this->partner_id;
        $apiParams['status'] = $params['status'];
        
        if( isset($params['tpl_params']) ){
            $apiParams['tpl_params'] = $params['tpl_params'];    
        }
        
		//签名
		$sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams), $this->signType);
        
		//系统参数放入GET请求串
		$requestUrl = $this->gatewayUrl . "?";
		foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($this->characet($sysParamValue, $this->postCharset)) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);
        
		//发起HTTP请求
        $resp = $this->curl($requestUrl, $apiParams);
        $response = json_decode( $resp, true );
        
        // $this->ci->load->library('fdaylog');
        // $db_log = $this->ci->load->database('db_log', TRUE);
        // $log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------updatepass\r\n";
        // $log .= var_export($params,true)."\r\n";
        // $log .= var_export($response,true)."\r\n";
        // $this->ci->fdaylog->add($db_log, 'alipay_pass', $log );
        
        return $response;
    }
    
    
    /**
     *
     *@desc 组装系统参数
     *@author cuiyang
     **/
    private function initSysParams( $method, $auth_token = '' )
    {
		//组装系统参数
        $sysParams = array();
		$sysParams["method"] = $method;
		$sysParams["app_id"] = $this->app_id;
		$sysParams["version"] = $this->app_version;
		$sysParams["format"] = $this->format;
		$sysParams["charset"] = $this->charset;
		$sysParams["sign_type"] = $this->signType;
		$sysParams["timestamp"] = date("Y-m-d H:i:s");
        
        if( $auth_token ){
            $sysParams["app_auth_token"] = $auth_token;    
        }
        
        return $sysParams;
    }
    
    
	public function generateSign($params, $signType = "RSA") {
		return $this->sign($this->getSignContent($params), $signType);
	}
    
    
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
		return $reponse;
	}
    

	protected function sign($data, $signType = "RSA") {
		$priKey = file_get_contents($this->rsaPrivateKeyFilePath);
		$res = openssl_get_privatekey($priKey);
		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置'); 

		if ("RSA2" == $signType) {
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
		} else {
			openssl_sign($data, $sign, $res);
		}
		openssl_free_key($res);
		$sign = base64_encode($sign);
		return $sign;
	}
    
    protected function getSignContent($params) {
		ksort($params);

		$stringToBeSigned = "";
		$i = 0;
		foreach ($params as $k => $v) {
			if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

				// 转换成目标字符集
				$v = $this->characet($v, $this->postCharset);

				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		unset ($k, $v);
		return $stringToBeSigned;
	}
    
    
	protected function checkEmpty($value) {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}
    
	function characet($data, $targetCharset) {


		if (!empty($data)) {
			$fileType = $this->fileCharset;
			if (strcasecmp($fileType, $targetCharset) != 0) {

				$data = mb_convert_encoding($data, $targetCharset);
			}
		}


		return $data;
	}
}
