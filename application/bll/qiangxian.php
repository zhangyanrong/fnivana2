<?php

namespace bll;

/**
 * 商品相关接口
 */
class Qiangxian {

    function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->model('qiangxian_model');
        $this->ci->load->helper('public');
    }

    public function getQxAd($id) {
        $rs = $this->qiangxian_model->getQxAd($id);
        return array('code' => 200, 'msg' => $rs);
    }

    public function getQx($id) {
        $rs = $this->qiangxian_model->getQx($id);
        return array('code' => 200, 'msg' => $rs);
    }


    public function setRemind($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'price_id' => array('required' => array('code' => '500', 'msg' => 'price id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();

        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
        $rs = $this->redis->sAdd("qx:remind:" . $params['price_id'], $uid);
        if ($rs) {
            return array('code' => 200);
        } else {
            return array('code' => 300, 'msg' => '设置提醒失败');
        }
    }

    public function hasRemind($params) {
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'price_id' => array('required' => array('code' => '500', 'msg' => 'price id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();

        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
        $rs = $this->redis->sIsMember("qx:remind:" . $params['price_id'], $uid);
        if ($rs) {
            return array('code' => 200);
        } else {
            return array('code' => 300, 'msg' => '未设置提醒');
        }
    }

    public function doRemind($params) {
        $qxId = $params['qxId'];
        $H = date('H', time());
        if ($H < 12) {
            $mOrN = 1;
        } else {
            $mOrN = 2;
        }

        $modelInfo = $this->qiangxian_model->getQxAd($qxId);


        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        foreach($modelInfo[4] as $ms){
            $priceGroup = $mOrN == 1 ? $ms['ad_config']['morningPriceId'] : $ms['ad_config']['nightPriceId'];

            foreach ($priceGroup as $priceId) {
                $keyName = 'qx:remind:' . $priceId;
                $needRemindList = $this->redis->sMembers($keyName);

                foreach ($needRemindList as $needRemindUser) {
                    $this->ci->load->library("notify");
                    $type = ['app'];
                    $target = ["uid" => $needRemindUser];
                    $message = ["title" => '抢鲜回复通知', "body" => '早市即将开始，调整姿势开抢'];
                    $params = ["source" => "api", "mode" => "single", "type" => json_encode($type), "target" => json_encode($target), "message" => json_encode($message), "extras" => json_encode(array())];
                    $no = $this->ci->notify->send($params);
                    $no['source'] = $params;
                }

                $this->redis->delete($keyName);
            }
        }

    }

}
