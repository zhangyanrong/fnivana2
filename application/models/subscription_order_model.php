<?php
/**
 * 周期购订单模型
 */
class Subscription_order_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'subscription_order';
    }

    /**
     * 添加订单
     *
     * @params $data array 订单数据
     */
     public function add($data) {
         if (!$data OR !is_array($data)) {
             return 0;
         }
         $insert_query = $this->db->insert_string($this->table_name(), $data);
         $insert_query = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $insert_query);
         $query = $this->db->query($insert_query);
         $id = $this->db->insert_id();
         return $id;
    }

    /**
     * 订单列表
     *
     * @params $uid int 用户编号
     */
    public function get_list($uid) {
        if (!$uid) {
            return [];
        }
        $rows = $this->db->from($this->table_name())->where(['uid' => $uid])->order_by('id','DESC')->get()->result_array();
        return $rows;
    }

    /**
     * 获取指定用户所有的进行中或暂停的订单编号和套餐编号
     *
     * @params $uid int 用户编号
     */
    public function get_ids($uid) {
        if (!$uid) {
            return [];
        }
        $rows = $this->db->select('id,combo_id')->from($this->table_name())->where(['uid' => $uid])->where_in('status', [1,2])->get()->result_array();
        return $rows;
    }

    /**
     * 取消订单
     *
     * @params $uid int 用户ID
     * @params $order_id int 订单ID
     */
    public function cancel($uid, $order_id) {
        if (!$uid OR !$order_id) {
            return FALSE;
        }
        $data = [
            'status' => 5
        ];
        $this->db->where(['id' => $order_id, 'uid' => $uid]);
        $this->db->update($this->table_name(), $data);
        if (!$this->db->affected_rows()) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 获取指定用户的有效订单总数
     *
     * @params $uid int 用户编号
     */
    public function get_total($uid) {
        if (!$uid) {
            return 0;
        }
        $total = $this->db->from($this->table_name())->where(['uid' => $uid])->get()->num_rows();
        return $total;
    }

    /**
     * 获取单个订单明细
     *
     * @params $uid int 用户ID
     * @params $id int 订单ID
     * @params $order_name string 订单编码
     */
    public function detail($uid, $id, $order_name = '') {
        $wheres = ["uid" => $uid];
        if ($id) {
            $wheres['id'] = $id;
        }
        if ($order_name) {
            $wheres['order_name'] = $order_name;
        }
        $result = $this->db->from($this->table_name())->where($wheres)->get()->row_array();
        return $result;
    }

    /**
     * 修改订单
     *
     * @params $order_id int 订单ID
     * @params $updata array 修改数据
     */
    public function update($order_id, $updata) {
        if (!$order_id) {
            return FALSE;
        }
        $this->db->where(['id' => $order_id]);
        $this->db->update($this->table_name(), $updata);
        if (!$this->db->affected_rows()) {
            return FALSE;
        }
        return TRUE;
    }

    /*
    *更新订单
    */
    function update_order($data,$where,$op_log=array()){
        $this->_filter($where);
        $this->db->update($this->table_name(), $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    /**
     * 获取日历订单列表
     *
     * @params $uid int 用户编号
     * @params $limit int 调用条数
     */
    public function get_calendar_orders($uid, $limit = 10) {
        if (!$uid) {
            return [];
        }
        $rows = $this->db->from($this->table_name())->where(['uid' => $uid])->where_in('status', [1,2])
            ->order_by('id', 'DESC')->limit($limit)->get()->result_array();
        return $rows;
    }

}
