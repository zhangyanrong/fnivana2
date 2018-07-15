<?php
namespace bll;

class Weixin
{
    // 新用户关注公众号时自动回复信息存储在Redis里的key。
    const RESPONSE_REDIS_KEY = 'api:weixin:response';

    // 自动回复消息的类型。可能值：text, news
    const RESPONSE_REDIS_HASH_TYPE = 'type';

    // 自动回复信息的内容所属的hash名称。
    // 如果类型为text，则为文本内容；如果类型为news，则为media_id。
    const RESPONSE_REDIS_HASH_CONTENT = 'content';

    const LOCATION_REDIS_LOG_KEY = 'api:weixin:location';

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
        $this->ci->load->model('weixin_model');
    }

    public function __call($sMethodName, $aParams)
    {
        list ($weixin, $sSubService) = explode('.', $aParams[0]['service']);
        list ($sClassFile, $sMethodName) = explode('-', $sSubService);
        $obj = 'bll_weixin_' . $sClassFile;

        $this->ci->load->bll('weixin/' . $sClassFile);
        return $this->ci->{$obj}->$sMethodName($aParams[0]);
    }

    /**
     * 获得自动回复的内容。
     */
    public function getAutoResponse($aParams)
    {
        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();

        $sType = $oRedis->hget(self::RESPONSE_REDIS_KEY, self::RESPONSE_REDIS_HASH_TYPE) ? : 'text';
        $sContent = $oRedis->hget(self::RESPONSE_REDIS_KEY, self::RESPONSE_REDIS_HASH_CONTENT);

        $aResponse = [
            'type' => $sType
        ];

        if ($sType === 'text') {
            // 文本消息
            $aResponse['content'] = $sContent;
        } else if ($sType === 'news') {
            // 图文消息
            $aNewsInfo = $this->ci->weixin_model->getNewsByMediaID($sContent);
            $aNewsContent = json_decode($aNewsInfo['content'], true);
            $aNewsContent['media_id'] = $sContent;
            $aResponse['content'] = $aNewsContent;
        }

        return $aResponse;
    }

    /**
     * 设置自动回复的内容
     */
    public function setAutoResponse($aParams)
    {
        $content = $aParams['content'];
        $type = $aParams['type'];

        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();

        $r1 = $oRedis->hset(self::RESPONSE_REDIS_KEY, self::RESPONSE_REDIS_HASH_CONTENT, $content);
        $r2 = $oRedis->hset(self::RESPONSE_REDIS_KEY, self::RESPONSE_REDIS_HASH_TYPE, $type);

        return ($r1 !== false or $r2 !== false);
    }

    /**
     * 获得临时二维码图片链接
     * @return string
     */
    public function getTempQR($aParams)
    {
        $iExpires = $aParams['expires'];
        $iSceneID = $aParams['scene_id'];

        $this->ci->load->library("Weixin", ['sModuleName' => 'QR']);
        $aTicket = $this->ci->weixin->QR->getTempTicket($iExpires, $iSceneID);

        return $this->ci->weixin->QR->getQR($aTicket['ticket']);
    }

    /**
     * 获得二维码图片链接
     * @return string
     */
    public function getQR($aParams)
    {
        $sSceneString = $aParams['scene_str'];
        $this->ci->load->library("Weixin", ['sModuleName' => 'QR']);
        $aTicket = $this->ci->weixin->QR->getTicket($sSceneString);

        return $this->ci->weixin->QR->getQR($aTicket['ticket']);
    }

    /**
     * 添加群发消息的状态记录。
     */
    public function addMsgStatus($aParams)
    {
        $aData = json_decode($aParams['data'], true);
        return $this->ci->weixin_model->addMsgStatus($aData);
    }

    /**
     * 更新群发消息的状态。
     */
    public function updateMsgStatus($aParams)
    {
        $iMsgID = $aParams['msg_id'];
        $aData = json_decode($aParams['data']);

        return $this->ci->weixin_model->updateMsgStatus($iMsgID, $aData);
    }

    /**
     * 获得本地的消息发送状态。
     */
    public function getLocalMessageStatus($aParams)
    {
        return $this->ci->weixin_model->getMsgStatus($aParams['start_time'], $aParams['end_time']);
    }

    /**
     * 获得消息状态。
     * @return array
     */
    public function getMessageStatus($aParams)
    {
        $aMsgIDList = explode(',', $aParams['msg_ids']);
        $this->ci->load->library("Weixin", ['sModuleName' => 'Message']);

        $aReturn = [];

        foreach ($aMsgIDList as $iMsgID) {
            $aReturn[] = $this->ci->weixin->Message->getMessageStatus($iMsgID);
        }

        return $aReturn;
    }

    /**
     * 按照openid列表群发消息。
     * @return array
     */
    public function multiPushByOpenID($aParams)
    {
        $aOpenIDList = explode(',', $aParams['openid_list']);
        $sMsgType = $aParams['msgtype'];
        $aData = json_decode($aParams['data'], true);
        $warehouse = $aParams['warehouse'];

        $this->ci->load->library("Weixin", ['sModuleName' => 'Message']);

        foreach (array_chunk($aOpenIDList, 10000) as $value) {
            $aResult = $this->ci->weixin->Message->multiPushByOpenID($value, $sMsgType, $aData);

            $aStatus = [
                'msg_id' => $aResult['msg_id'],
                'msg_data_id' => $aResult['msg_data_id'],
                'status' => $aResult['errmsg'],
                'params' => [
                    'first_openid' => $value[0],
                    'msgtype' => $sMsgType,
                    'data' => $aData,
                    'count' => count($value)
                ],
                'total_count' => 0,
                'filter_count' => 0,
                'sent_count' => 0,
                'error_count' => 0,
                'warehouse' => $warehouse
            ];

            $this->ci->weixin_model->addMsgStatus($aStatus);

            if ((int)$aResult['errcode'] > 0) {
                return $aResult;
            }
        }

        return $aResult;
    }

    /**
     * 按用户组群发消息。
     * @return array
     */
    public function multiPushByGroup($aParams)
    {
        $iGroupID = $aParams['group_id'];
        $sMsgType = $aParams['msgtype'];
        $aData = json_decode($aParams['data'], true);
        $this->ci->load->library("Weixin", ['sModuleName' => 'Message']);

        $aResult = $this->ci->weixin->Message->multiPushByGroup($iGroupID, $sMsgType, $aData);

        $aStatus = [
            'msg_id' => $aResult['msg_id'],
            'msg_data_id' => $aResult['msg_data_id'],
            'status' => $aResult['errmsg'],
            'params' => ['group_id' => $iGroupID, 'msgtype' => $sMsgType, 'data' => $aData],
            'total_count' => 0,
            'filter_count' => 0,
            'sent_count' => 0,
            'error_count' => 0
        ];

        $this->ci->weixin_model->addMsgStatus($aStatus);

        return $aResult;
    }

    /**
     * 推送模板消息。
     * @return array
     */
    public function pushTemplateMessage($aParams)
    {
        unset($aParams['service'], $aParams['source'], $aParams['version'], $aParams['timestamp']);

        $template_id = $this->getTemplateID($aParams['template_name']);
        $user_openid = $aParams['to_user'];
        $url = isset($aParams['url']) ? $aParams['url'] : '';

        unset($aParams['template_name'], $aParams['to_user']);

        if (isset($aParams['url'])) {
            unset($aParams['url']);
        }

        $data = [];

        foreach ($aParams as $k => $v) {
            $data[$k] = [
                'value' => $v,
                'color' => '#173177'
            ];
        }

        $this->ci->load->library("Weixin", ['sModuleName' => 'Message']);
        return $this->ci->weixin->Message->pushMessage($template_id, $user_openid, $data, $url);
    }

    private function getTemplateID($sTemplateName)
    {
        $aList = [
            '订单确认收货通知' => '6M3c5QsepE9qvIl0eqrrgwDAWfmhsaEFD_eb5aCET6o',
            '账号未绑定通知' => 'AxyfDOBphF0MexayF565ZgmL0C3gXiob_d73NGzWGIg',
            '优惠券过期提醒' => 'OG99qIJ2tfY20utQ80iSoxuGyraedoyaCFwECOO5bCQ',
            '未付款订单通知' => 'S0k-568bk66wNDTVfar8r9KuoRiWME7pjXbQCkr1YKk',
            '领取成功通知' => 'SJjpPdeEbguU3AOrclQdVAsmbPsAsx2gYdnnOcmyDuE',
            '付款成功通知' => 'VhGzZ94c-p-idiFWuna6bHNSwKKw2YdeBq6GeqcmVMY',
            '收到回复通知' => 'WmMEi_Ho4REoXNGpCxT3VwWSd32yW_TO6zYAtQoVlYY',
            '积分提醒' => 'aUW9_t0ZqSbYq014D1KaE45id12kkgtazMWjFHHW024',
            '优惠券领取成功通知' => 'htOzN_Bp99pcy_fldbq0ORtewF7nCiRQJWM9f_rCqz8',
            '微信绑定成功通知' => 'i0_7qa_KUbwcntjW4FU2mySIzoVG1L0J54uP24gfKQI',
            '微信解绑成功通知' => 'jXJlHgM-XxizzNHYwHGFa3tqrrDkpxrtmR8ME8W-WAw',
            '订单取消通知' => 'tX3BWGsfpn2ZEAXr7-alt9kVjCOSpz6ELICkLhyEsm4',
            '订单标记发货通知' => 'u7Tob2MQP6vwlMXs5YA5aJvVIqEzIgamLkBfFk0UC2c'
        ];

        if (isset($aList[$sTemplateName])) {
            return $aList[$sTemplateName];
        } else {
            return '';
        }
    }

    /**
     * 获得token详情。
     * @return array
     */
    public function getToken($aParams)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'Menu']);
        return $this->ci->weixin->Menu->getGlobalAccessTokenDetail();
    }

    /**
     * 删除redis中的access_token。
     * @return bool
     */
    public function delToken($aParams)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'Menu']);
        return $this->ci->weixin->Menu->delGlobalAccessToken();
    }

    /**
     * 响应消息请求。
     * @param array $aParam
     * @return array
     */
    public function pushMessage($aParams)
    {
        $this->xml = $aParams['xml'];
        $aInput = $this->xml2array($aParams['xml']);

        if ($aInput['MsgType'] === 'event') {
            $sMethodName = strval(strtolower($aInput['Event']) . 'Event');

            if (method_exists($this, $sMethodName)) {
                $xml = $this->$sMethodName($aInput);
            }
        } else {
            if ($_SERVER['HTTP_HOST'] === 'staging.nirvana.fruitday.com' or $_SERVER['HTTP_HOST'] === 'api.fruit.com:8002') {
                // 测试公众号 <=> 客服系统的测试环境。
                $this->sendTo53KFtest($aParams['xml']);

                // 测试公众号 <=> 客服系统的生产环境。
                // $this->sendTo53KF($aParams['xml']);
            } else {
                // 正式公众号 <=> 客服系统的生产环境。
                $this->sendTo53KF($aParams['xml']);

                // 使用官方多客服系统。
                // $xml = $this->transferMessage($aInput);
            }
        }

        return $xml;
    }

    /**
     * 上报地理位置事件
     * @param array $aInput
     */
    private function LOCATIONEvent($aInput)
    {
        $openid = $aInput['FromUserName'];

        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();

        $iNowUnixTime = $_SERVER['REQUEST_TIME'];

        // 距离上次更新不到一天，则不更新。
        if (($iNowUnixTime - (int)$oRedis->hget(self::LOCATION_REDIS_LOG_KEY, $openid)) > 86400) {
            $oRedis->hset(self::LOCATION_REDIS_LOG_KEY, $openid, $iNowUnixTime);
            $oRedis->lpush(\Weixin_model::QUEUE_FIX_LOCATION, json_encode($aInput));
        }

        return '';
    }

    /**
     * 用户已关注时的事件推送
     * @param array $aInput
     */
    private function SCANEvent($aInput)
    {
        $aData = [
            'FromUserName' => $aInput['FromUserName'],
            'CreateTime' => $aInput['CreateTime'],
            'Event' => $aInput['Event'],
            'EventKey' => $aInput['EventKey'],
            'Ticket' => $aInput['Ticket'],
            'Date' => date('Y-m-d', $aInput['CreateTime'])
        ];

        $this->logQRSubscribe($aData);
        return '';
    }

    /**
     * 群发消息的状态回调。
     * @param array $aInput
     */
    private function masssendjobfinishEvent($aInput)
    {
        $iMsgID = $aInput['MsgID'];
        $aData = [
            'status' => $aInput['Status'],
            'total_count' => $aInput['TotalCount'],
            'filter_count' => $aInput['FilterCount'],
            'sent_count' => $aInput['SentCount'],
            'error_count' => $aInput['ErrorCount']
        ];

        $this->ci->weixin_model->updateMsgStatus($iMsgID, $aData);
    }

    /**
     * 新用户关注事件的响应。
     * @param array $aInput 事件请求信息。
     * @return string $xml
     */
    private function subscribeEvent($aInput)
    {
        // 扫描带参数二维码关注的用户（增粉活动）。
        if (isset($aInput['Ticket']) && !empty($aInput['Ticket'])) {
            $aData = [
                'FromUserName' => $aInput['FromUserName'],
                'CreateTime' => $aInput['CreateTime'],
                'Event' => $aInput['Event'],
                'EventKey' => $aInput['EventKey'],
                'Ticket' => $aInput['Ticket'],
                'Date' => date('Y-m-d', $aInput['CreateTime'])
            ];

            $this->logQRSubscribe($aData);

            // 增粉活动的排序逻辑：先按人数排，如果出现并列，则按达成相同人数的时间较早者排位靠前。
            $this->setRedisRank($aData);
        }

        $aResponse = $this->getAutoResponse([]);
        $xml = $this->getResponseXML($aResponse, $aInput);

        return $xml;
    }

    /**
     * 获得自动回复消息的XML。
     * @param array $aResponse 自动回复信息的详情。
     * @param array $aInput 用户动作事件。
     * @return xml
     */
    private function getResponseXML($aResponse, $aInput)
    {
        if ($aResponse['type'] === 'text') {
            $tpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[%s]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
            $sMessage = $aResponse['content'];
            $xml = sprintf($tpl, $aInput['FromUserName'], $aInput['ToUserName'], time(), 'text', $sMessage);
        } else if ($aResponse['type'] === 'news') {
            $tpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>
%s
</Articles>
</xml>";
            unset($aResponse['content']['media_id']);

            $ToUserName = $aInput['FromUserName'];
            $FromUserName = $aInput['ToUserName'];
            $CreateTime = time();
            $ArticleCount = count($aResponse['content']);

            $Articles = '';
            $ArticleItemTpl = "<item>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[%s]]></PicUrl>
<Url><![CDATA[%s]]></Url>
</item>";

            foreach ($aResponse['content'] as $aArticle) {
                $Articles .= sprintf($ArticleItemTpl, $aArticle['title'], $aArticle['digest'], $aArticle['thumb_url'], $aArticle['url']) . "\n";
            }

            $xml = sprintf($tpl, $ToUserName, $FromUserName, $CreateTime, $ArticleCount, $Articles);
        }

        return $xml;
    }

    private function unsubscribeEvent($aInput)
    {
        $aRange = $this->ci->weixin_model->getSubscribeRange();
        $iStartTime = strtotime($aRange['start_time']);
        $iEndTime = strtotime($aRange['end_time']);

        $aData = [
            'FromUserName' => $aInput['FromUserName'],
            'CreateTime' => $aInput['CreateTime'],
            'Event' => $aInput['Event'],
            'EventKey' => '',
            'Ticket' => '',
            'Date' => date('Y-m-d', $aInput['CreateTime'])
        ];

        $this->logQRSubscribe($aData);

        // 增粉活动的排序逻辑：如果在活动期间取消关注，对应用户的邀请人数要减1。
        $aSubscribeLog = $this->ci->weixin_model->getSubscribeLog($aInput['FromUserName'], $iStartTime, $iEndTime);
        $aUnsubscribeLog = $this->ci->weixin_model->getUnsubscribeLog($aInput['FromUserName'], $iStartTime, $iEndTime);

        if (!empty($aSubscribeLog) && count($aUnsubscribeLog) === 1) {
            $this->reduceRank($aSubscribeLog, $aData);
        }

        return '';
    }

    /**
     * 增粉活动期间取消关注，则邀请用户要减分。
     * @param array $aSubscribeLog
     * @param array $aData
     * @param int $iStep
     */
    private function reduceRank($aSubscribeLog, $aData, $iStep = -1)
    {
        $scene_id = (int)ltrim($aSubscribeLog[0]['EventKey'], 'qrscene_');
        $scene_info = $this->ci->weixin_model->getSceneIDInfo($scene_id);

        if (empty($scene_info)) {
            return false;
        }

        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();
        $oRedis->zIncrBy('subscribe_score', $iStep, $scene_info['openid']);
    }

    /**
     * 增粉活动排名信息。用sorted set记录人数排名，同时记录每个人最新的、符合活动规则的新用户的加入时间。
     * @param array $aData
     * @param int $iStep
     * @return bool
     */
    private function setRedisRank($aData, $iStep = 1)
    {
        $isActive = $this->ci->weixin_model->isActive($aData['CreateTime']);

        if (!$isActive) {
            return '';
        }

        // 检查场景ID是否合法
        $scene_id = (int)ltrim($aData['EventKey'], 'qrscene_');
        $scene_info = $this->ci->weixin_model->getSceneIDInfo($scene_id);

        if (empty($scene_info)) {
            return false;
        }

        // 检查该用户之前是否已经关注过了。重复关注不算人数。
        $history = $this->ci->weixin_model->getSubscribeLog($aData['FromUserName']);

        if (count($history) > 1) {
            return false;
        }

        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();

        $oRedis->zIncrBy('subscribe_score', $iStep, $scene_info['openid']);
        $oRedis->hSet('subscribe_time', $scene_info['openid'], $aData['CreateTime']);
    }

    /**
     * 自定义菜单点击事件响应。
     * @param array $aInput 事件请求信息。
     * @return string $xml
     */
    private function clickEvent($aInput)
    {
        $this->ci->load->library("Weixin", ['sModuleName' => 'Material']);

        if ($aInput['EventKey'] === '53KF') {
            if ($_SERVER['HTTP_HOST'] === 'staging.nirvana.fruitday.com' or $_SERVER['HTTP_HOST'] === 'api.fruit.com:8002') {
                // 测试公众号 <=> 客服系统的测试环境。
                $this->sendTo53KFtest($this->xml);
            } else {
                // 正式公众号 <=> 客服系统的生产环境。
                $this->sendTo53KF($this->xml);
            }

            return '';
        }

        if ($this->ci->weixin_model->isTextMessageKey($aInput['EventKey'])) {
            $xml = $this->createTextMessageXML($aInput);
        } else {
            $xml = $this->createImageTextMessageXML($aInput);
        }

        return $xml;
    }

    /**
     * 转发用户消息到多客服系统。
     * @param array $aInput 事件请求信息。
     * @return string $xml
     */
    private function transferMessage($aInput)
    {
        $tpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[transfer_customer_service]]></MsgType>
</xml>";

        return sprintf($tpl, $aInput['FromUserName'], $aInput['ToUserName'], time());
    }

    /**
     * 构造图文消息的XML。
     * @param array $aInput 事件请求信息。
     * @return string $xml
     */
    private function createImageTextMessageXML($aInput)
    {
        $aInfo = $this->ci->weixin->Material->getMaterial($aInput['EventKey']);
        $tpl_outer = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%s</ArticleCount>
<Articles>
%s
</Articles>
</xml>";

        $tpl_item = "<item>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[%s]]></PicUrl>
<Url><![CDATA[%s]]></Url>
</item>";

        $articles = '';

        $sDefaultPicURL = 'http://mmbiz.qpic.cn/mmbiz/70We7uibRw0x3XOmZ0sFZugjwxp1G800I8pfxiciaSFWeMxGaqvTaTBACyiaAH4BwKvtS8Emv6xxT8AJ6JPDK1icgGg/640?wx_fmt=jpeg&tp=webp&wxfrom=5&wx_lazy=1';

        foreach ($aInfo['news_item'] as $item) {
            $articles .= sprintf($tpl_item, $item['title'], $item['digest'], $sDefaultPicURL, $item['url']); // @todo 图片链接暂时写死。
        }

        return sprintf($tpl_outer, $aInput['FromUserName'], $aInput['ToUserName'], time(), count($aInfo['news_item']), $articles);
    }

    /**
     * 构造文本消息的XML。
     * @param array $aInput 事件请求信息。
     * @return string $xml
     */
    private function createTextMessageXML($aInput)
    {
        $sMessage = $this->ci->weixin_model->getTextMessage($aInput['EventKey']);

        $tpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[%s]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";

        $xml = sprintf($tpl, $aInput['FromUserName'], $aInput['ToUserName'], time(), 'text', $sMessage);
        return $xml;
    }

    /**
     * XML字符串转数组。
     * @param string $xml
     * @return array
     */
    private function xml2array($xml)
    {
        libxml_disable_entity_loader(true);
        return (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**
     * 记录错误日志。
     * @param string $sTag
     * @param string $sContent
     */
    private function errorLog($sTag, $sContent)
    {
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, $sTag, $sContent);
    }

    /**
     * 将消息转发给53客服系统。
     * @param string $xml 接收到的XML。
     */
    private function sendTo53KF($xml)
    {
        $arr = $this->xml2array($xml);
        $openid = $arr['FromUserName'];
        $this->ci->load->model('weixin_model');
        $weixin_user_info = $this->ci->weixin_model->checkOpenID($openid);
        $user_id = '';
        $user_rank = '';
        $location = '';

        if (!empty($weixin_user_info)) {
            $user_id = base64_encode($weixin_user_info['uid']);
            $this->ci->load->model('user_model');
            $user_info = $this->ci->user_model->getUser($user_id);
            $user_rank = $user_info['user_rank'];

            foreach (['country', 'province', 'city'] as $field) {
                if (!empty($weixin_user_info[$field])) {
                    $location .= $weixin_user_info[$field] . ' ';
                }
            }

            $location = trim($location);

            if (!empty($location)) {
                $location = urlencode($location);
            }
        }

        $url = "http://chatoms.fruitday.com/sendwechat.jsp?company_id=70722519&style=weixin&location={$location}&53_userid={$user_id}&53_rank={$user_rank}";

        $aHeader = [
            'Content-Type:text/xml',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $aResult = curl_exec($ch);
        curl_close($ch);

        return $aResult;
    }

    /**
     * 将消息转发给53客服系统。测试号对测试服务器。
     * @param string $xml 接收到的XML。
     */
    private function sendTo53KFtest($xml)
    {
        $arr = $this->xml2array($xml);
        $openid = $arr['FromUserName'];
        $this->ci->load->model('weixin_model');
        $weixin_user_info = $this->ci->weixin_model->checkOpenID($openid);
        $user_id = '';
        $user_rank = '';
        $location = '';

        if (!empty($weixin_user_info)) {
            $user_id = base64_encode($weixin_user_info['uid']);
            $this->ci->load->model('user_model');
            $user_info = $this->ci->user_model->getUser($user_id);
            $user_rank = $user_info['user_rank'];

            foreach (['country', 'province', 'city'] as $field) {
                if (!empty($weixin_user_info[$field])) {
                    $location .= $weixin_user_info[$field] . ' ';
                }
            }

            $location = trim($location);

            if (!empty($location)) {
                $location = urlencode($location);
            }
        }

        $url = "http://54.223.60.134/sendwechat.jsp?company_id=70722519&style=default&location={$location}&53_userid={$user_id}&53_rank={$user_rank}";

        $aHeader = [
            'Content-Type:text/xml',
            'Host: chatomstest.fruitday.com'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $aResult = curl_exec($ch);
        curl_close($ch);

        $log = [
            'xml' => $xml,
            'url' => $url,
            'access_token' => $this->getToken()[0]
        ];

        $this->errorLog('weixin_53kf', $log);

        return $aResult;
    }

    /**
     * 记录带参数二维码邀请到的用户的关注信息。
     * @param array $aData
     * @return bool
     */
    private function logQRSubscribe($aData)
    {
        return $this->ci->weixin_model->addQRSubscribe($aData);
    }
}

# end of this file
