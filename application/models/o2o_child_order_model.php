<?php
class o2o_child_order_model extends MY_Model {

    public function table_name(){
        return 'o2o_child_order';
    }

    public function get_push_orders($order_names = array(),$valid = true){
        $where = "c.sync_status = 0 AND o.channel!='99' AND (((c.pay_status=1 OR o.pay_parent_id=4) AND c.operation_id in ('0','1') AND o.channel!='7')
               OR ((c.pay_status=1 OR o.pay_parent_id=4) AND c.operation_id='2' AND o.erp_active_tag!='' AND o.channel='7')
               OR ( c.pay_status=1 AND o.operation_id=5 AND o.pay_parent_id in (1,3,7,9) AND o.channel!='7'))";
        if(!empty($order_names)){
            $order_names = implode("','", $order_names);
            $where = "c.order_name in('".$order_names."') AND o.channel!='99' AND (((c.pay_status=1 OR o.pay_parent_id=4) AND c.operation_id in ('0','1') AND o.channel!='7')
               OR ((c.pay_status=1 OR o.pay_parent_id=4) AND c.operation_id='2' AND o.erp_active_tag!='' AND o.channel='7'))";
            if ($valid === false) {
                $where = "c.order_name in('".$order_names."') AND o.channel!='99' AND c.operation_id != 5";
            }
        }
        $sql = "SELECT o.*,o.order_name p_order_name,c.*
                FROM ttgy_o2o_child_order c INNER JOIN ttgy_order o ON c.p_order_id=o.id
                WHERE $where LIMIT 1000";

        return $this->db->query($sql)->result_array();

    }

    public function get_child_order($name)
    {
        $sql = "SELECT o.*,o.order_name p_order_name,c.* FROM ttgy_o2o_child_order c INNER JOIN ttgy_order o ON c.p_order_id=o.id WHERE c.order_name=?";
        return $this->db->query($sql, array($name))->row_array();
    }

    public function get_child_orders_by_parent_id($id){
        $sql = "SELECT * FROM ttgy_o2o_child_order WHERE p_order_id=?";
        return $this->db->query($sql, array($id))->result_array();
    }
}