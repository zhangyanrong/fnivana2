<?php

class CronWeixin extends CI_Controller
{
    public function fixLocation()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('weixin_model');

        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();

        $num = 0;

        while (true) {
            $json_input = $oRedis->rpop(Weixin_model::QUEUE_FIX_LOCATION);

            if (empty($json_input)) {
                break;
            }

            $num++;
            $aInput = json_decode($json_input, true);
            $this->fixLocationByXML($aInput);
            sleep(1);
        }

        $this->errorLog('weixin_location_cron', ['total' => $num]);
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
     * 根据微信推送的XML更新用户地理位置和分仓信息。
     * @param array $aInput
     * @return void
     */
    private function fixLocationByXML($aInput)
    {
        // 通过API拿到地理位置。
        $region = $this->getRegionByCoordinate($aInput['Latitude'], $aInput['Longitude']);

        if (empty($region)) {
            return '';
        }

        // 更新用户信息。
        $aUpdateWeixinInfo = [
            'openid' => $aInput['FromUserName'],
            'country' => $region['nation'],
            'province' => $region['province'],
            'city' => $region['city'],
        ];

        if (in_array($region['province'], ['上海', '北京', '天津', '重庆'])) {
            $aUpdateWeixinInfo['city'] = $region['district'];
        }

        $this->ci->weixin_model->updateWeixinUserInfo($aUpdateWeixinInfo);

        // 更新分仓信息。
        $warehouse = $this->ci->weixin_model->decideWarehouse($region['province']);
        $this->ci->weixin_model->updateWarehouse($aInput['FromUserName'], $warehouse, 2);
    }

    private function getRegionByCoordinate($fLat, $fLng)
    {
        load_class('Model', 'core');
        $this->ci->load->library('phpredis');
        $oRedis = $this->ci->phpredis->getConn();

        $sPrimaryCacheKey = 'api:weixin:coodinate';
        $sCacheKey = $fLat . ',' . $fLng;

        if ($oRedis->hexists($sPrimaryCacheKey, $sCacheKey)) {
            $value = $oRedis->hget($sPrimaryCacheKey, $sCacheKey);
            list($nation, $province, $city, $district) = explode(',', $value);

            return ['nation' => $nation,'province' => $province, 'city' => $city, 'district' => $district];
        }

        $key = '46NBZ-6AHRQ-Y2V55-GE4JV-7QXQ5-GBBMZ';
        $url = "http://apis.map.qq.com/ws/geocoder/v1/?location={$fLat},{$fLng}&key={$key}";

        $return = file_get_contents($url);
        $arr = json_decode($return, true);

        $log = [
            'url' => $url,
            'return' => $return
        ];

        if (empty($return)) {
            // $this->errorLog('weixin_geo_error', $log);
            return [];
        }

        if ((int)$arr['status'] > 0) {
            // $this->errorLog('weixin_geo_error', $log);
            return [];
        }

        $nation = $arr['result']['ad_info']['nation'];
        $province = $arr['result']['ad_info']['province'];
        $city = $arr['result']['ad_info']['city'];
        $district = $arr['result']['ad_info']['district'];

        if (mb_substr($province, -1) === '省' || mb_substr($province, -1) === '市') {
            $province = mb_substr($province, 0, -1);
        }

        if (mb_substr($city, -1) === '市') {
            $city = mb_substr($city, 0, -1);
        }

        if (mb_substr($district, -1) === '区' and $district !== '浦东新区') {
            $district = mb_substr($district, 0, -1);
        }

        $oRedis->hset($sPrimaryCacheKey, $sCacheKey, $nation . ',' . $province . ',' . $city . ',' . $district);
        $oRedis->setTimeout($sPrimaryCacheKey, 2592000);

        return ['nation' => $nation, 'province' => $province, 'city' => $city, 'district' => $district];
    }
}

# end of this file
