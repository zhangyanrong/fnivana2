<?php
require 'weixin/Agent.php';

class Weixin extends Agent
{
    /**
     * @param array $aParam ['sModuleName' => 'User']
     */
    public function __construct($aParams)
    {
        $this->ci = & get_instance();

        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();

        $aRealParams = [
            'sModuleName' => $aParams['sModuleName'],
            'sAppID' => WX_APP_ID,
            'sSecret' => WX_SECRET,
            'oRedis' => $oRedis
        ];

        if (defined('WEIXIN_TEST')) {
            $aRealParams['sAccessToken'] = $this->getStagineToken();
        }
        
        parent::__construct($aRealParams);
    }

    /**
     * 获得预发布环境的token。
     * @return string
     */
    private function getStagineToken()
    {
        $secret = 't5xdj9xrbsg6';

        $ticket = md5(microtime());
        $tmp = md5(strrev($ticket) . $secret);
        $sign = strrev(md5(substr($tmp, 0, -2) . $secret));

        $url = 'http://staging.m.fruitday.com/weixin/getToken/?ticket=' . $ticket . '&sign=' . $sign;
        $result = file_get_contents($url);
        $arr = json_decode($result, true);

        return $arr['data']['token'];
    }
}