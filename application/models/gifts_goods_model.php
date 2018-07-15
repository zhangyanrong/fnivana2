<?php
/**
 * 赠品购买套餐产品模型
 */
class Gifts_goods_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'gifts_goods';
    }

    /**
     * 获取单个套餐明细
     *
     * @params $id int 套餐ID
     */
    public function detail($id) {
        $result = $this->db->from($this->table_name())->where(["id" => $id])
                  ->get()->row_array();
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

    /**
     * 套餐产品列表
     *
     * @params $pid int 套餐编号
     */
    public function get_goods($pid) {
        if (!$pid) {
            return [];
        }
        $this->db->select('p.product_name, gs.product_id, g.price, g.qty, pp.volume, pp.unit, pp.product_no, pp.unit_weight, p.thum_min_photo');
        $this->db->from($this->table_name() . ' g');
        $this->db->join('gift_send gs', 'gs.id = g.active_id');
        $this->db->join('product p', 'p.id = gs.product_id');
        $this->db->join('product_price pp', 'p.id = pp.product_id');
        $this->db->where('g.pid', $pid);
        $this->db->group_by('g.active_id', 'DESC');
        $this->db->order_by('g.id', 'DESC');
        $rows = $this->db->get()->result_array();
        $data = [];
        if ($rows) {
            foreach ($rows as $v) {
                $data[] = [
                    'product_name' => $v['product_name'],
                    'product_id' => $v['product_id'],
                    'price' => $v['price'],
                    'qty' => $v['qty'],
                    'volume' => $v['volume'],
                    'unit' => $v['unit'],
                    'product_no' => $v['product_no'],
                    'weight' => $v['unit_weight'],
                    'photo' => constant(CDN_URL.rand(1, 9)) . $v['thum_min_photo']
                ];
            }
        }
        return $data;
    }

    /**
     * 根据订单编号查找赠品活动信息
     *
     * @params $order_name string 订单编号
     */
    public function get_gift_active($order_name) {
        if (!$order_name) {
            return [];
        }
        $this->db->select('
          c.pid,
          c.active_id,
          e.order_name,
          d.`start`,
          d.`end`,
          d.`product_id`,
          d.`gift_end_time`,
          d.`gift_start_time`,
          d.`gift_valid_day`
        ');
        $this->db->from($this->table_name() . ' c');
        $this->db->join('gift_send d', 'd.`id` = c.`pid`', 'left');
        $this->db->join('gifts_package_order e', 'e.`pid` = c.`pid`', 'left');
        $this->db->where('e.`order_name`', $order_name);
        $data = $this->db->get()->result_array();
        return $data;
    }

}