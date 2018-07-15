<?php
class WeixinResponser extends MY_Controller
{
    const TOKEN = '87d0ba63';

    public function index()
    {
        if (isset($_GET["echostr"])) {
            $echostr = $_GET['echostr'];

            if ($this->checkSignature()) {
                echo $echostr;
            }
        } else {
            $xml = file_get_contents('php://input');

            if (!empty($xml)) {
                $this->load->bll('wap/weixin');
                echo $this->bll_wap_weixin->pushMessage(['xml' => $xml]);
            }
        }
    }

    public function redirecter()
    {
        $sTag = $this->input->get('tag');

        $get_weixin_code_url = function($tag) {
            return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx1061e4e55dd6de25&redirect_uri=http%3A%2F%2Fpay.fruitday.com%2Fauth%2Fuserinfo%3Ftag%3D{$tag}&response_type=code&scope=snsapi_userinfo&state=STATE&connect_redirect=1#wechat_redirect";
        };

        $aWhiteList = [
            'weixin_binding',
            'fan_active',
            'fan_prize'
        ];

        if (in_array($sTag, $aWhiteList)) {
            $sURL = $get_weixin_code_url($sTag);
            header('Location:' . $sURL);
            return true;
        } else {
            return false;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $tmpArr = [self::TOKEN, $timestamp, $nonce];

        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}

# end of this file.
