<?php
// 促销类
// pax <chenping@fruitday.com> 2014
// 蔡昀辰 2015
class Promotion_model extends CI_model {

    public function __construct() {
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    // pro_sales
    // 蔡昀辰2015
    // 改成从redis获取
    public function get_single_promotion($type = '1') {
        $rows = array();

        // 从Redis中获取在有效期内的pro_sales
        if ($this->redis) {
            $raw = $this->redis->hGetAll("api:promotion:pro_sales");
            foreach($raw as $val) {
                $row = json_decode($val, true);
                if($row["type"] == $type)
                    array_push($rows, $row);
            }

            return $rows;            
        }

        // 老的逻辑
        $now = date("Y-m-d H:i:s");
        $rows = $this->db->from("pro_sales")
            ->where(array('start <=' => $now,'end >=' => $now,'type' => $type))
            ->get()
            ->result_array();

        return $rows;
    }

    // pro_sales single
    // 蔡昀辰2015
    // 改成从redis获取
    public function get_one_single_promotion($id) {
        $row = "";

        // 从Redis中获取在有效期内的pro_sales
        if ($this->redis) {
            $row = $this->redis->hGet("api:promotion:pro_sales", $id);
            $row = json_decode($row, true);

            return $row;
        }

        // 老的逻辑
        $now = date("Y-m-d H:i:s");
        $row = $this->db->from("pro_sales")
            ->where(array('start <=' => $now,'end >=' => $now))
            ->where('id',$id)
            ->get()
            ->row_array();

        return $row;
    }

    // bind_sales
    public function get_pkg_promotion() {

        // 蔡昀辰：和陆盛超商量后认为这个表无用，直接关闭此方法，节约SQL开销。      
        return;

        $now = date("Y-m-d H:i:s");
        $rows = $this->db->from("bind_sales")
                         ->where(array('start <=' => $now,'end >=' => $now))
                         ->get()
                         ->result_array();

        return $rows;
    }

    // money_upto
    // 蔡昀辰2015
    // 改成从redis获取    
    public function get_order_promotion($type='0') {
        $rows = array();

        // 从Redis中获取在有效期内的money_upto
        if ($this->redis) {
            $raw = $this->redis->hGetAll("api:promotion:money_upto");
            foreach($raw as $val) {
                $row = json_decode($val, true);
                if($row["type"] == $type)
                    array_push($rows, $row);
            }

            return $rows;            
        }

        // 老的逻辑        
        $now = date("Y-m-d H:i:s");
        $rows = $this->db->from("money_upto")
            ->where(array('start <=' => $now,'end >=' => $now,'type' => $type))
            ->get()
            ->result_array();

        return $rows;
    }

    // money_upto single
    public function get_one_order_promotion($id)
    {
        $now = date("Y-m-d H:i:s");
        $row = $this->db->from("money_upto")
                         ->where(array('start <=' => $now,'end >=' => $now,'id' => $id))
                         ->get()
                         ->row_array();

        return $row;
    }

    // sale_rules
    // 蔡昀辰2015
    // 改成从redis获取    
    public function get_sale_rule($type = '1') {

        $rows = array();

        // cyc从Redis中获取在有效期内的money_upto
        if ($this->redis) {
            $raw = $this->redis->hGetAll("api:promotion:sale_rules");
            foreach($raw as $val) {
                $row = json_decode($val, true);
                if($row["type"] == $type)
                    array_push($rows, $row);
            }

            return $rows;            
        }

        // cyc老的逻辑 
        $now = time();
        $rows = $this->db->from('sale_rules')
            ->where(array('start_time <=' => $now,'end_time >=' => $now,'type' => $type))
            ->get()
            ->result_array();

        return $rows;
    }

    // sale_rules single
    // 蔡昀辰2015
    // 改成从redis获取
    public function get_one_sale_rule($id) {
        $row = "";

        // 从Redis中获取在有效期内的sale_rules
        if ($this->redis) {
            $row = $this->redis->hGet("api:promotion:sale_rules", $id);
            $row = json_decode($row, true);

            return $row;
        }

        $now = time();
        $row = $this->db->from('sale_rules')
            ->where(array('start_time <=' => $now,'end_time >=' => $now,'id' => $id))
            ->get()
            ->row_array();

        return $row;
    }

    public function get_limit2buy_promotion($id)
    {
        $row = $this->db->from('limit2buy')
            ->where(['id' => $id])
            ->get()
            ->row_array();

        if ($row) {
            $row['content_arr'] = json_decode($row['content'], true);
        }

        return $row;
    }

    public function get_limit_gift_rule(){
        $this->db->select("*");
        $this->db->from("active_rules");
        $this->db->where("id",1);
        $query = $this->db->get();
        $res = $query->row_array();
        return $res;
    }

    /**
     * 获得满额换购的活动列表信息。
     *
     * @param string $datetime
     * @return array     
     */
    public function get_limit2buy($datetime)
    {
        $this->db->select("*");
        $this->db->from("limit2buy");
        $this->db->where(['begin_time <=' => $datetime,'end_time >=' => $datetime]);

        return $this->db->get()->result_array();
    }
}

# end of this file
