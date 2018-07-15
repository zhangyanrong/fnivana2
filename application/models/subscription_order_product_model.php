<?php
/**
 * 周期购订单菜池模型
 */
class Subscription_order_product_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'subscription_order_product';
    }

    /**
     * 添加配送地址
     *
     * @params $data array 订单配送地址数据
     */
     public function add($data) {
         if (!$data OR !is_array($data)) {
             return 0;
         }
         $insert_query = $this->db->insert_string($this->table_name(), $data);
         $query = $this->db->query($insert_query);
         $id = $this->db->insert_id();
         return $id;
    }

    /**
     * 菜池获取详情
     *
     * @params $wheres array 筛选条件
     */
    public function detail($wheres) {
        if (!$wheres OR !is_array($wheres)) {
            return [];
        }
        $result = $this->db->from($this->table_name())->where($wheres)->get()->row_array();
        return $result;
    }

    /**
     * 删除菜池
     *
     * @params $wheres array 筛选条件
     */
    public function delete($wheres) {
        if (!$wheres OR !is_array($wheres)) {
            return false;
        }
        $result = $this->db->delete($this->table_name(), $wheres);
        return ($result == true) ? $this->db->affected_rows() : false;
    }

    /**
     * 修改菜品
     *
     * @params $wheres array 筛选条件
     * @params $updata array 修改数据
     */
    public function update($wheres, $updata) {
        if (!$wheres OR !is_array($wheres)) {
            return false;
        }
        if (!$updata OR !is_array($updata)) {
            return false;
        }
        $this->db->where($wheres);
        $this->db->update($this->table_name(), $updata);
        if (!$this->db->affected_rows()) {
            return false;
        }
        return true;
    }
}
