<?php

namespace bll\wap;
include_once("wap.php");


/**
 * 商品相关接口
 */
class Qiangxian extends wap{

    function __construct($params = array()) {
        $this->ci = &get_instance();
        $this->ci->load->model('qiangxian_model');
        $this->ci->load->helper('public');

    }

    public function getQxAd($params){
        $rs = $this->ci->qiangxian_model->getQxAd($params['id']);
        return array('code'=>200, 'msg'=>$rs);
    }

    public function getQx($params){
        $rs = $this->ci->qiangxian_model->getQx($params['id']);
        return array('code'=>200, 'msg'=>$rs);
    }

    public function doRemind($params) {
        $qxId = $params['qxId'];
        $H = date('H', time());
        if ($H < 12) {
            $mOrN = 1;
        } else {
            $mOrN = 2;
        }

        $modelInfo = $this->ci->qiangxian_model->getQxAd($qxId);


        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

        $priceGroup = $mOrN == 1 ? $modelInfo[1]['ad_config']['morningPriceId'] : $modelInfo[1]['ad_config']['nightPriceId'];

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
