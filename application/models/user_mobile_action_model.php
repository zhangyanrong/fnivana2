<?php

/**
 * 功能：用户操作行为收集
 *
 * @author luke_lu
 */
class User_mobile_action_model extends MY_Model {
    
    //页面类型定义
    private $action_type = array(
        1 => '注册',
        2 => '加入购物车',
        3 => '提交订单'
    );

    /**
     * 获取表名
     */
    public function table_name() {
        return 'user_mobile_action';
    }

   /**
    * 用户具体操作行为收集
    *
    * @param $params 各种参数集合
    * @param $action_type int 操作类型: 1注册 2加入购物车 3提交订单
    * @param $order_id int 订单ID/注册用户ID
    * @param $order_money decimal 订单金额/购物车金额
    * @param $goods string 商品列表:编号,数量;
    *
    * @return void
    */
	public function add($params, $action_type = 1, $order_id = 0, $order_money = 0.00, $goods = NULL) {
        $city_id = isset($params['region_id']) ? intval($params['region_id']) : 0; //城市id
        $operating_system = isset($params['platform']) ? mysql_escape_string(strtolower($params['platform'])) : NULL; //手机操作系统名称
        $version_number = isset($params['version_number']) ? mysql_escape_string($params['version_number']) : NULL; //操作系统版本号
        $device_number = isset($params['device_id']) ? mysql_escape_string($params['device_id']) : NULL; //IMEI,IDFA,MEID
        $android_id = isset($params['android_id']) ? mysql_escape_string($params['android_id']) : NULL; //安卓系统的Android Id
        
        if ($operating_system && ($device_number || $android_id)) {
            $data = array(
                'city_id' => $city_id,
                'operating_system' => $operating_system,
                'version_number' => $version_number,
                'device_number' => $device_number,
                'android_id' => $android_id,
                'action_type' => $action_type,
                'order_id' => $order_id,
                'order_money' => $order_money,
                'goods' => rtrim($goods, ';'),
                'create_time' => time()
            );        
            $this->db->insert($this->table_name(), $data);
        }
    }
}