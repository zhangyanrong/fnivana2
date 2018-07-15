<?php
class Weixin_model extends MY_Model {
    const TABLE_USER = 'weixin_user_info';
    const TABLE_MENU_MSG = 'weixin_menu_msg';
    const TABLE_GROUP = 'weixin_user_group';
    const TABLE_GROUP_MAPPING = 'weixin_user_group_mapping';
    const TABLE_MATERIAL_IMAGE = 'weixin_material_image';
    const TABLE_MATERIAL_NEWS = 'weixin_material_news';
    const TABLE_MSG_STATUS = 'weixin_msg_status';
    const TABLE_QR_SUBSCRIBE = 'weixin_qr_subscribe';
    const TABLE_NEWUSER_PRIZE = 'newuser_prize';
    const TABLE_NEWUSER_PRIZE_DETAIL = 'newuser_prize_detail';
    const TABLE_USER_WAREHOUSE = 'weixin_user_warehouse';

    const MENU_MSG_PREFIX = 'menu_msg_';

    const BINDING_POOL_NAME = 'api:weixin:binding_pool';
    const SUBSCRIBLE_RANGE = 'api:weixin:subscrible_range';

    const QUEUE_FIX_LOCATION = 'api:weixin:fix_location';

    /**
     * 获得群发消息的状态信息。
     * @param int $iStart 起始时间的时间戳。
     * @param int $iEnd 结束时间的时间戳。
     * @return array
     */
    public function getMsgStatus($iStart, $iEnd)
    {
        $this->db->select('*');
        $this->db->from(self::TABLE_MSG_STATUS);

        if ($iStart > 0) {
            $this->db->where('update_time > ', date('Y-m-d H:i:s', $iStart));
        }

        if ($iEnd > 0) {
            $this->db->where('update_time < ', date('Y-m-d H:i:s', $iEnd));
        }

        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * 添加群发消息的状态信息。
     * @param array $aData
     * @return bool
     */
    public function addMsgStatus($aData)
    {
        $aData['params'] = json_encode($aData['params']);
        return $this->db->insert(self::TABLE_MSG_STATUS, $aData);
    }

    /**
     * 更新消息发送状态。
     * @param int $iMsgID
     * @param array $aData
     * @return bool
     */
    public function updateMsgStatus($iMsgID, $aData)
    {
        return $this->db->update(self::TABLE_MSG_STATUS, $aData, ['msg_id' => $iMsgID]);
    }

    /**
     * 根据media_id获得图片信息。
     * @param string $sMediaID
     * @return array
     */
    public function getImageInfo($sMediaID)
    {
        $this->db->select('*');
        $this->db->from(self::TABLE_MATERIAL_IMAGE);
        $this->db->where('media_id', $sMediaID);

        $query = $this->db->get();
        $result = $query->result_array();

        return empty($result) ? ['url' => ''] : $result[0];
    }

    /**
     * 根据关键字搜索图文消息。
     * @param string $sKeyword 搜索词。
     * @param int $iOffset 偏移量。
     * @param int $iAmount 读取的数据条数。
     * @return array
     */
    public function searchNews($sKeyword, $iOffset = 0, $iAmount = 10)
    {
        $this->db->select('*');
        $this->db->from(self::TABLE_MATERIAL_NEWS);
        $this->db->like('keywords', $sKeyword);
        $this->db->order_by('update_time', 'desc');
        $this->db->limit($iAmount, $iOffset);

        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    /**
     * 清空图文消息素材。
     * @return bool
     */
    public function clearNews()
    {
        return $this->db->empty_table(self::TABLE_MATERIAL_NEWS);
    }

    /**
     * 添加图文消息。
     * @param array $data
     * @return array
     */
    public function addNews($data)
    {
        $result = [
            'status' => true,
            'message' => 'none'
        ];

        $r = $this->db->replace(self::TABLE_MATERIAL_NEWS, $data);

        if (!$r) {
            $result['status'] = false;
            $result['message'] = $this->db->_error_message();
        }

        return $result;
    }

    /**
     * 添加图片。
     * @param array $data
     * @return array
     */
    public function addImage($data)
    {
        $result = [
            'status' => true,
            'message' => 'none'
        ];

        $r = $this->db->replace(self::TABLE_MATERIAL_IMAGE, $data);

        if (!$r) {
            $result['status'] = false;
            $result['message'] = $this->db->_error_message();
        }

        return $result;
    }

    /**
     * 获得本地图片素材的数量。
     * @return int
     */
    public function getImageCount()
    {
        return $this->db->count_all_results(self::TABLE_MATERIAL_IMAGE);
    }

    /**
     * 获得本地图文素材的数量。
     * @return int
     */
    public function getNewsCount()
    {
        return $this->db->count_all_results(self::TABLE_MATERIAL_NEWS);
    }

    /**
     * 根据media_id获得消息内容。
     * @param string $media_id
     * @return array
     */
    public function getNewsByMediaID($media_id)
    {
        $this->db->select('*');
        $this->db->from(self::TABLE_MATERIAL_NEWS);
        $this->db->where('media_id', $media_id);
        $this->db->limit(1);
        $query = $this->db->get();

        return $query->row_array();
    }

    /**
     * 是否是文本消息。
     * @param string $sKey 事件xml中的EventKey
     * @return bool
     */
    public function isTextMessageKey($sKey)
    {
        return strpos($sKey, self::MENU_MSG_PREFIX) !== false;
    }

    /**
     * 统计用户数。
     * @return int
     */
    public function countUser($aFilter)
    {
        $aFilterFields = ['sex', 'country', 'province', 'city'];

        $this->db->reconnect();
        $this->db->select(1);

        foreach ($aFilterFields as $sField) {
            if (isset($aFilter[$sField])) {
                $this->db->where($sField, $aFilter[$sField]);
            }
        }

        if (isset($aFilter['start_time']) && isset($aFilter['end_time'])) {
            $this->db->where('bind_time >=', $aFilter['start_time']);
            $this->db->where('bind_time <', $aFilter['end_time']);
        }

        $count = $this->db->count_all_results(self::TABLE_USER);
        return $count;
    }

    /**
     * 根据UID获得绑定用户的微信账号信息。
     * @param int $uid
     * @return array
     */
    public function getWeixinUserByUID($uid){
        $this->db->select("*");
        $this->db->from(self::TABLE_USER);
        $this->db->where("uid", $uid);
        $query = $this->db->get();
        $res = $query->result_array();

        return empty($res) ? [] : $res[0];
    }

    /**
     * 获得所有用用户的国家。
     * @return array
     */
    public function getCountryList()
    {
        $this->db->select('country');
        $this->db->from(self::TABLE_USER);
        $this->db->group_by('country');

        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    /**
     * 获得所有用用户的某个国家的省份。
     * @return array
     */
    public function getProvinceList($sCountry)
    {
        $this->db->select('province');
        $this->db->from(self::TABLE_USER);
        $this->db->where('country', $sCountry);
        $this->db->group_by('province');

        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    /**
     * 获得所有用用户的某个省份的城市。
     * @return array
     */
    public function getCityList($sProvince)
    {
        $this->db->select('city');
        $this->db->from(self::TABLE_USER);
        $this->db->where('province', $sProvince);
        $this->db->group_by('city');

        $query = $this->db->get();
        $result = $query->result_array();
        return $result;
    }

    /**
     * 获得用户列表。
     * @param array $aFilter
     * @return array
     */
    public function getUserList($aFilter = [])
    {
        $aFilterFields = ['sex', 'country', 'province', 'city'];

        $this->db->select("*");
        $this->db->from(self::TABLE_USER);

        foreach ($aFilterFields as $sField) {
            if (isset($aFilter[$sField])) {
                $this->db->where($sField, $aFilter[$sField]);
            }
        }

        if (isset($aFilter['keyword'])) {
            if (is_numeric($aFilter['keyword'])) {
                $this->db->where('uid', $aFilter['keyword']);
            } else {
                $this->db->like('nickname', $aFilter['keyword']);
            }
        }

        if (isset($aFilter['start_time'])) {
            $this->db->where('bind_time >=', $aFilter['start_time']);
        }

        if (isset($aFilter['end_time'])) {
            $this->db->where('bind_time <=', $aFilter['end_time']);
        }

        $this->db->limit($aFilter['limit'], $aFilter['offset']);
        $this->db->order_by('bind_time', 'desc');
        $query = $this->db->get();
        $result = $query->result_array();

        // For total.
        $this->db->reconnect();
        $this->db->select(1);

        foreach ($aFilterFields as $sField) {
            if (isset($aFilter[$sField])) {
                $this->db->where($sField, $aFilter[$sField]);
            }
        }

        if (isset($aFilter['keyword'])) {
            if (is_numeric($aFilter['keyword'])) {
                $this->db->where('uid', $aFilter['keyword']);
            } else {
                $this->db->like('nickname', $aFilter['keyword']);
            }
        }

        if (isset($aFilter['start_time'])) {
            $this->db->where('bind_time >=', $aFilter['start_time']);
        }

        if (isset($aFilter['end_time'])) {
            $this->db->where('bind_time <=', $aFilter['end_time']);
        }

        $count = $this->db->count_all_results(self::TABLE_USER);

        return [$count, $result];
    }

    /**
     * 检查某openid绑定情况。
     * @param string $openid
     * @return array
     */
    public function checkOpenID($openid)
    {
        $this->db->select('*');
        $this->db->from(self::TABLE_USER);
        $this->db->where('openid', $openid);

        return $this->db->get()->row_array();
    }

    /**
     * 回传官网id等信息
     */
    public function bind($iUserID, $aWeixinUserInfo){
        if (!isset($aWeixinUserInfo['openid'])) {
            return ['code' => 301, 'msg' => '没有获得用户微信账号信息。'];
        }

        if ((int)$iUserID <= 0) {
            return ['code' => 302, 'msg' => '您输入的账号信息错误，请重新输入。'];
        }

        $info = $this->checkOpenID($aWeixinUserInfo['openid']);

        $data = [
            'uid' => $iUserID,
            'openid' => $aWeixinUserInfo['openid'],
            'nickname' => $aWeixinUserInfo['nickname'] ? : "",
            'sex' => $aWeixinUserInfo['sex'] ? : 0,
            'city' => $aWeixinUserInfo['city'] ? : "",
            'country' => $aWeixinUserInfo['country'] ? : "",
            'province' => $aWeixinUserInfo['province'] ? : "",
            'headimgurl' => $aWeixinUserInfo['headimgurl'] ? : "",
            'subscribe_time' => 0,
            'unionid' => $aWeixinUserInfo['unionid'] ? : "",
            'privilege' => json_encode($aWeixinUserInfo['privilege']),
            'bind_time' => time()
        ];

        if (!empty($info)) {
            // 用户信息已经导入过了，但是还没有绑定。
            unset($data['openid']);
            $result = $this->db->update(self::TABLE_USER, $data, ['openid' => $aWeixinUserInfo['openid']]);
        } else {
            $result = $this->db->insert(self::TABLE_USER, $data);
        }

        if ($result === false) {
            return ['code' => 300, 'msg' => '绑定失败'];
        }

        $this->bindingPoolAdd($iUserID, $data['bind_time']);

        return ['code' => 200, 'user_info' => $data];
    }

    /**
     * 更新微信用户信息。
     * @param array $aWeixinUserInfo 微信用户信息。
     * @return bool
     */
    public function updateWeixinUserInfo($aWeixinUserInfo){
        if (empty($aWeixinUserInfo['openid'])) {
            return false;
        }

        $aFieldList = [
            'nickname',
            'sex',
            'city',
            'country',
            'province',
            'headimgurl',
            'unionid',
            'bind_time'
        ];

        $aData = [];

        foreach ($aFieldList as $sField) {
            if (isset($aWeixinUserInfo[$sField])) {
                $aData[$sField] = $aWeixinUserInfo[$sField];
            }
        }

        if (isset($aWeixinUserInfo['privilege'])) {
            $aData['privilege'] = json_encode($aWeixinUserInfo['privilege']);
        }

        $this->db->where('openid', $aWeixinUserInfo['openid']);
        return $this->db->update(self::TABLE_USER, $aData);
    }

    /**
     * 根据openid更新微信用户信息。
     * @param string $openid
     * @param array $aWeixinUserInfo
     * @return bool
     */
    public function updateUser($openid, $aWeixinUserInfo)
    {
        $data = [
            'nickname' => $aWeixinUserInfo['nickname'],
            'sex' => $aWeixinUserInfo['sex'],
            'city' => $aWeixinUserInfo['city'],
            'country' => $aWeixinUserInfo['country'],
            'province' => $aWeixinUserInfo['province'],
            'headimgurl' => $aWeixinUserInfo['headimgurl'],
            'unionid' => $aWeixinUserInfo['unionid'],
            'privilege' => json_encode($aWeixinUserInfo['privilege']),
        ];

        $this->db->where('openid', $openid);
        return $this->db->update(self::TABLE_USER, $data);
    }

    /**
     * 将官方后台编辑出来的菜单信息转换成API模式的格式。
     * @param array $aInfo
     * @return array
     */
    public function menuConvert($aMenu)
    {
        foreach ($aMenu as $key => $value) {
            if ($value['type'] === 'click' && strpos($value['key'], self::MENU_MSG_PREFIX) !== false) {
                $aMenu[$key]['text'] = $this->getTextMessage($value['key']);
                continue;
            }

            if (!isset($value['sub_button'])) {
                continue;
            }

            foreach ($value['sub_button'] as $k => $v) {
                switch ($v['type']) {
                    case 'text':
                        $msg_id = $this->addTextMessge($v['value']);
                        $aMenu[$key]['sub_button'][$k] = [
                            'name' => $v['name'],
                            'type' => 'click',
                            'key' => $msg_id,
                            'text' => $v['value']
                        ];
                        break;
                    case 'news':
                        $aMenu[$key]['sub_button'][$k] = [
                            'name' => $v['name'],
                            'type' => 'click',
                            'key' => $v['value']
                        ];
                        break;
                    case 'click':
                        if (strpos($v['key'], self::MENU_MSG_PREFIX) !== false) {
                            $aMenu[$key]['sub_button'][$k]['text'] = $this->getTextMessage($v['key']);
                        }

                        break;
                }
            }
        }

        return $aMenu;
    }

    /**
     * 消息更新前的预处理。
     * @param array $aMenu
     * @return array
     */
    public function prePostMenu($aMenu)
    {
        foreach ($aMenu['button'] as $key => $value) {
            if (is_null($value)) {
                unset($aMenu['button'][$key]);
                continue;
            }

            if (!empty($value['sub_button'])) {
                unset(
                    $aMenu['button'][$key]['type'],
                    $aMenu['button'][$key]['key'],
                    $aMenu['button'][$key]['url'],
                    $aMenu['button'][$key]['text']
                );

                foreach ($value['sub_button'] as $k => $v) {
                    // @todo
                    // if ($v['name'] === '在线客服') {
                    //     $aMenu['button'][$key]['sub_button'][$k] = [
                    //         'name' => '在线客服',
                    //         'type' => 'click',
                    //         'key' => '53KF'
                    //     ];

                    //     continue;
                    // }

                    if (!isset($v['text'])) {
                        continue;
                    }

                    unset($aMenu['button'][$key]['sub_button'][$k]['text']);

                    if (empty($v['key'])) {
                        $aMenu['button'][$key]['sub_button'][$k]['key'] = $this->addTextMessge($v['text']);
                    } else {
                        $this->updateTextMessge($v['text'], $v['key']);
                    }
                }
            } else {
                unset($aMenu['button'][$key]['sub_button']);

                // @todo
                // if ($aMenu['button'][$key]['name'] === '在线客服') {
                //     $aMenu['button'][$key] = [
                //             'name' => '在线客服',
                //             'type' => 'click',
                //             'key' => '53KF'
                //     ];

                //     continue;
                // }

                if (isset($value['text'])) {
                    unset($aMenu['button'][$key]['text']);

                    if (empty($value['key'])) {
                        $aMenu['button'][$key]['key'] = $this->addTextMessge($value['text']);
                    } else {
                        $this->updateTextMessge($value['text'], $value['key']);
                    }
                }
            }
        }

        return $aMenu;
    }

    /**
     * 添加一条文本消息。
     * @param string $sMessage
     * @return string EVENT_KEY
     */
    public function addTextMessge($sMessage)
    {
        $this->db->insert(self::TABLE_MENU_MSG, ['content' => $sMessage]);
        $auto_id = $this->db->insert_id();
        $msg_id = self::MENU_MSG_PREFIX . $auto_id;
        return $msg_id;
    }

    /**
     * 更新一条文本消息。
     * @param string $sMessage 消息内容。
     * @param string $sKey 对应的KEY。
     * @return bool
     */
    public function updateTextMessge($sMessage, $sKey)
    {
        $id = ltrim($sKey, self::MENU_MSG_PREFIX);
        $this->db->where(['id' => $id]);
        return $this->db->update(self::TABLE_MENU_MSG, ['content' => $sMessage]);
    }

    /**
     * 获得一条文本消息的内容。
     * @param string $sKey 菜单配置中的key。
     * @return string
     */
    public function getTextMessage($sKey)
    {
        $id = ltrim($sKey, self::MENU_MSG_PREFIX);
        $this->db->select("content");
        $this->db->from(self::TABLE_MENU_MSG);
        $this->db->where("id", $id);
        $query = $this->db->get();
        $res = $query->result_array();

        return empty($res[0]['content']) ? 'nothing' : $res[0]['content'];
    }

    /**
     * 记录带参数二维码的关注记录。
     * @param array $aData
     * @return bool
     */
    public function addQRSubscribe($aData)
    {
        return $this->db->insert(self::TABLE_QR_SUBSCRIBE, $aData);
    }

    /**
     * 获得一个openid的关注记录。
     * @param string $openid
     * @param int $start_tme
     * @param int $end_time
     * @return bool
     */
    public function getSubscribeLog($openid, $start_time = 0, $end_time = 0)
    {
        $this->db->select("*");
        $this->db->from(self::TABLE_QR_SUBSCRIBE);
        $this->db->where("FromUserName", $openid);
        $this->db->where('Event', 'subscribe');

        if ($start_time > 0 && $end_time > 0) {
            $this->db->where('CreateTime > ', $start_time);
            $this->db->where('CreateTime < ', $end_time);
        }

        $this->db->order_by('CreateTime');

        $query = $this->db->get();
        $res = $query->result_array();

        return $res;
    }

    /**
     * 获得一个openid的关注记录。
     * @param string $openid
     * @param int $start_tme
     * @param int $end_time
     * @return bool
     */
    public function getUnsubscribeLog($openid, $start_time, $end_time)
    {
        $this->db->select("*");
        $this->db->from(self::TABLE_QR_SUBSCRIBE);
        $this->db->where("FromUserName", $openid);
        $this->db->where('CreateTime > ', $start_time);
        $this->db->where('CreateTime < ', $end_time);
        $this->db->where('Event', 'unsubscribe');

        $query = $this->db->get();
        $res = $query->result_array();

        return $res;
    }

    /**
     * 获得场景ID信息。
     * @param int $iSceneID
     * @return bool
     */
    public function getSceneIDInfo($iSceneID)
    {
        $this->db->select("*");
        $this->db->from('weixin_qr_scene_mapping');
        $this->db->where("scene_id", $iSceneID);
        $query = $this->db->get();
        $res = $query->result_array();
        return $res[0];
    }

    /**
     * 发送赠品
     * @param int $uid
     * @param string $tag
     * @return false or array
     */
    public function sendGift($uid, $tag) {
        $now_data_time = date("Y-m-d H:i:s");
        $this->db->select('a.*,b.product_name,b.thum_photo,template_id');
        $this->db->from('gift_send a');
        $this->db->join('product b', 'a.product_id = b.id');
        $this->db->where(array('tag' => $tag, 'start <=' => $now_data_time, 'end >=' => $now_data_time));
        $query = $this->db->get();
        $user_gift_info = $query->row_array();

        // 获取产品模板图片
        if ($user_gift_info['template_id']) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($user_gift_info['template_id'], 'main');
            if (isset($templateImages['main'])) {
                $user_gift_info['thum_photo'] = $templateImages['main']['thumb'];
            }
        }

        if (empty($user_gift_info)) {
            return false;
        } else {
            $this->db->from("user_gifts");
            $this->db->where(array("uid" => $uid, "active_id" => $user_gift_info['id']));
            $count_isgifted = $this->db->count_all_results();

            if ($count_isgifted) {//已有赠品，跳出
                return false;
            }
            $gift_send = $user_gift_info;
            if($gift_send['gift_valid_day'] && $gift_send['gift_valid_day']>0){
                $gift_start_time = date('Y-m-d');
                $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_send['gift_valid_day'])-1).' day'));
            }elseif($gift_send['gift_start_time'] && $gift_send['gift_end_time'] && $gift_send['gift_start_time'] != '0000-00-00' && $gift_send['gift_end_time'] != '0000-00-00'){
                $gift_start_time = $gift_send['gift_start_time'];
                $gift_end_time = $gift_send['gift_end_time'];
            }else{
                $gift_start_time = $gift_send['start'];
                $gift_end_time = $gift_send['end'];
            }
            $user_gift_data = array(
                'uid' => $uid,
                'active_id' => $user_gift_info['id'],
                'active_type' => '2',
                'has_rec' => '0',
                'start_time'=>$gift_start_time,
                'end_time'=>$gift_end_time,
            );

            $this->db->insert('user_gifts', $user_gift_data);

            return $user_gift_info;
        }
    }

    /**
     * 调用通知中心接口进行推送。
     * @return array
     */
    public function servicePush($postBody, $type = 'weixin', $op = 'send', $source = 'api', $version = 'v1')
    {
        if (defined('WEIXIN_TEST')) {
            return [];
        }

        $json = json_encode($postBody);

        $get_sign = function ($json) {
            //字符串拼接密钥后md5加密,去处最后一位再拼接”s"，再md5加密
            return md5(substr(md5($json . '3410w312ecf4a3j814y50b6abff6f6b97e16'), 0,-1) . 's');
        };

        $sign = $get_sign($json);

        $url = "https://notify.fruitday.com/{$version}/{$type}/{$op}?source={$source}&sign={$sign}";
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $aResult = curl_exec($ch);
        curl_close($ch);

        return json_decode($aResult, true);
    }

    /**
     * 绑定用户送礼。
     * @param int $uid
     */
    public function bindingGift($uid, $mobile, $aWeixinUserInfo)
    {
        $now = date('Y-m-d H:i:s');

        // 查询进行中的活动。
        $this->db->select('*');
        $this->db->from(self::TABLE_NEWUSER_PRIZE);
        $this->db->where('is_open', '是');
        $this->db->where('start_time < ', $now);
        $this->db->where('end_time > ', $now);
        $this->db->where('reg_type', '绑定微信');
        $this->db->order_by('id asc');
        $this->db->limit(1);
        $query = $this->db->get();
        $activity_info = $query->row_array();

        if (empty($activity_info)) {
            return false;
        }

        $this->db->select('*');
        $this->db->from(self::TABLE_NEWUSER_PRIZE_DETAIL);
        $this->db->where('prize_id', $activity_info['id']);
        $prize_info = $this->db->get()->result_array();

        if (empty($prize_info)) {
            return false;
        }

        // 发礼品。
        foreach ($prize_info as $value) {
            $send_result = $this->sendGift($uid, $value['content']);

            if (!$send_result) {
                return false;
            }
        }

        if ($activity_info['message_type'] === '微信') {
            $weixin_message = json_decode($activity_info['message'], true);

            if (empty($weixin_message)) {
                return false;
            }

            $params = [
                'openid' => $aWeixinUserInfo['openid'],
                'template_name' => '微信绑定成功通知',
                'first' => $weixin_message['weixin_head'],
                'keyword1' => strval($aWeixinUserInfo['nickname']),
                'keyword2' => $mobile,
                'keyword3' => '账户信息查询、消息提醒',
                'remark' => $weixin_message['weixin_tail'],
                'url' => 'http://m.fruitday.com'
            ];

            $this->servicePush($params);
        } else if ($activity_info['message_type'] === '短信') {
            $params = [
                'mobile' => $mobile,
                'message' => $activity_info['message']
            ];

            $this->servicePush($params, 'sms');
        }
    }

    /**
     * 省份确定所属的分仓。
     * @param string $province
     * @return array
     */
    public function decideWarehouse($province)
    {
        if (empty($this->warehouseMapping)) {
            $config = [
                '上海' => ['上海'],
                '康花' => ['江苏', '浙江', '安徽'],
                '北京' => ['北京', '天津', '河北'],
                '广州' => ['广东'],
                '四川' => ['四川', '重庆']
            ];

            $ret = [];

            foreach ($config as $k => $v) {
                $arr = array_map(function($v)use($k){
                    return $k;
                }, array_flip($v));

                $ret = array_merge($ret, $arr);
            }

            $this->warehouseMapping = $ret;
        }

        $warehouse = '其他';

        if (isset($this->warehouseMapping[$province])) {
            $warehouse = $this->warehouseMapping[$province];
        }

        // 各个仓库在数据库里保存的值。
        $db_value_setting = $this->getWarehouseSetting();

        return $db_value_setting[$warehouse];
    }

    /**
     * 获得分仓推送的配置。
     * @return array
     */
    public function getWarehouseSetting()
    {
        $db_value_setting = [
            '测试' => -1,
            '其他' => 0,
            '上海' => 1,
            '康花' => 2,
            '北京' => 3,
            '广州' => 4,
            '四川' => 5
        ];

        return $db_value_setting;
    }

    /**
     * 更新分仓数据。
     * @param string $openid
     * @param int $warehouse
     * @param int $source 分仓地理位置数据的来源。可能值：1=微信用户信息，2=微信用户经纬度 + 地图API
     * @return bool
     */
    public function updateWarehouse($openid, $warehouse, $source = 1)
    {
        $data = [
            'openid' => $openid,
            'warehouse' => $warehouse,
            'source' => $source
        ];

        $r = $this->db->replace(self::TABLE_USER_WAREHOUSE, $data);
        return $r;
    }

    /**
     * 解除绑定。
     * @param int $uid
     * @return bool
     */
    public function unbind($uid)
    {
        if (!is_numeric($uid) or $uid <= 0) {
            return false;
        }

        $this->db->from(self::TABLE_USER);
        $this->db->where(['uid' => $uid]);
        $this->db->limit(1);
        return $this->db->delete();
    }

    /**
     * 绑定池初始化。
     */
    public function bindingPoolInit()
    {
        $this->db->select('uid,bind_time');
        $this->db->from(self::TABLE_USER);
        $this->db->where('bind_time >', 0);
        $aUserList = $this->db->get()->result_array();

        foreach ($aUserList as $aUser) {
            $this->bindingPoolAdd($aUser['uid'], $aUser['bind_time']);
        }
    }

    /**
     * 获得绑定池数量。
     * @return int
     */
    public function bindingPoolCount()
    {
        $oRedis = $this->getRedis();
        return $oRedis->hLen(self::BINDING_POOL_NAME);
    }

    /**
     * 添加一个用户到绑定池。
     * @param int $uid 果园用户ID。
     * @param int $time 最早绑定时间。
     */
    public function bindingPoolAdd($uid, $time)
    {
        $oRedis = $this->getRedis();

        if (!$oRedis->hExists(self::BINDING_POOL_NAME, $uid)) {
            $oRedis->hSet(self::BINDING_POOL_NAME, $uid, $time);
        }
    }

    /**
     * 获得一个绑定记录。
     * @param int $uid
     * @return int 绑定时间。如果没有对应记录则返回 0。
     */
    public function bindingPoolGet($uid)
    {
        $oRedis = $this->getRedis();

        return (int)$oRedis->hGet(self::BINDING_POOL_NAME, $uid);
    }

    /**
     * 检查一个日期是否处在正在进行中的增粉活动区间。
     * @param int $iTime
     * @return bool
     */
    public function isActive($iTime)
    {
        $aRange = $this->getSubscribeRange();

        if ($iTime >= strtotime($aRange['start_time']) && $iTime <= strtotime($aRange['end_time'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获得增分活动的首尾时间。
     * @return array 结构如：['start'=> '2016-04-01', 'end' => '2016-04-30']
     */
    public function getSubscribeRange()
    {
        $aRange = [
            'start_time' => '',
            'end_time' => ''
        ];

        $oRedis = $this->getRedis();

        $aRange['start_time'] = $oRedis->hget(self::SUBSCRIBLE_RANGE, 'start_time');
        $aRange['end_time'] = $oRedis->hget(self::SUBSCRIBLE_RANGE, 'end_time');

        return $aRange;
    }

    private function getRedis()
    {
        $ci = &get_instance();
        $ci->load->library('phpredis');
        $oRedis = $ci->phpredis->getConn();

        return $oRedis;
    }
}

# end of this file
