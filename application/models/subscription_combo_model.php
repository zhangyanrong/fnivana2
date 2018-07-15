<?php
/**
 * 周期购套餐模型
 */
class Subscription_combo_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'subscription_combo';
    }

    /**
     * 获取单个套餐明细
     *
     * @params $id int 套餐ID
     */
    public function detail($id) {
        $result = $this->db->from($this->table_name())->where(["id" => $id])
                  ->get()->row_array();
        if ($result && $result['photo']) {
            $photo_url = constant(CDN_URL.rand(1, 9));
            $result['photo'] = $photo_url . $result['photo'];
        }
        return $result;
    }

    /**
     * 获取单个套餐明细
     *
     * @params $id int 套餐ID
     */
    public function get_name($id) {
        $result = $this->db->select('name')->from($this->table_name())->where(["id" => $id])
            ->get()->row_array();
        return $result ? $result['name'] : '';
    }

}