<?php
/**
 * 打活动接口
 *
 *
 **/
class Active
{
    private $api_url_active = ACTIVE_URL;
    private $secret = ACTIVE_SECRET;

    function __construct()
    {
        $this->ci =&get_instance();
        $this->api_url_active = $this->api_url_active."/api";
    }

    //签名方法
    private function create_sign_active($params) {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }

        $sign = md5(substr(md5($query . $this->secret), 0, -1) . 'w');
        return $sign;
    }

    function curl_share_reward($params){
        unset($params['sign']);
        $sign = $this->create_sign_active($params);
        $params['sign'] = $sign;
//        $rs = $this->ci->http_curl->request($this->api_url_active.'/share_reward', array('params'=>$params), 'POST', array('timeout' => 180) );
        $rs = $this->curl_do($params,$this->api_url_active.'/share_reward');
        if(in_array($rs['code'],array('300','200')))
            return $rs;
    }

    function curl_do($params,$api_url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $rs = json_decode($result,1);
        return $rs;
    }
}
