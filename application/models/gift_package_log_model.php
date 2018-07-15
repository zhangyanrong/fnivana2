<?php
/**
 * 赠品套餐领取退款日志模型
 */
class Gift_package_log_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'gift_package_log';
    }

    /**
     * 新增日志
     *
     * @params $order_name string 订单号
     * @params $type int 订单操作类型：1创建订单，0取消订单
     *
     * @return mixed
     */
    public function add($order_name, $type = 1) {
        if (!$order_name) {
            return 0;
        }
        $sql = "SELECT 
          SUM(op.`gift_price`) AS money 
        FROM
          ttgy_order o 
          LEFT JOIN ttgy_order_product op 
            ON op.`order_id` = o.`id` 
        WHERE op.`gift_price` > 0 
          AND o.`order_name` = '$order_name'";
        $row = $this->db->query($sql)->row_array();
        $data = [
            'order_name' => $order_name,
            'money' => ($type == 1 ? '-' : '') . $row['money'],
            'reason' => '预收账款-周期购' . ($type == 1 ? '支付' : '退款'),
            'create_time' => date('Y-m-d H:i:s')
        ];
        $this->db->insert($this->table_name(), $data);
        return $this->db->insert_id();
    }
}