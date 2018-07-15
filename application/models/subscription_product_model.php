<?php
/**
 * 周期购菜品库存模型
 */
class Subscription_product_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'subscription_product';
    }

    /**
     * 菜品库存详情
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
     * 修改菜品库存
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

    /**
     * 只修改qty的值，使用表中qty追加新的数值后替换：qty = qty + new_value
     *
     * @params $wheres array 筛选条件
     * @params $new_value int 减少数值
     * @params $replace_type string 操作类型：+/-
     */
    public function update_qty($wheres, $new_value, $replace_type = '-') {
        if (!$wheres OR !is_array($wheres)) {
            return false;
        }
        $this->db->set('qty', 'qty' . $replace_type . $new_value, FALSE);
        $this->db->where($wheres);
        $this->db->update($this->table_name());
        if (!$this->db->affected_rows()) {
            return false;
        }
        return true;
    }

}