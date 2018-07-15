<?php
class Order_op_model extends MY_Model {
    private $_cancel_type = array(1=>'客户取消',2=>'OMS取消',3=>'支付超时取消',4=>'未成团取消');

    public function table_name(){
        return 'order_op';
    }

    public function addCancelDetail($order_id,$type=1,$per_op_id,$per_pay_status){
        $insert_data = array();
        $insert_data['order_id'] = $order_id;
        $cancel_type = $this->_cancel_type[$type];
        $insert_data['type'] = $cancel_type;
        $insert_data['per_op_id'] = $per_op_id;
        $insert_data['per_pay_status'] = $per_pay_status;
        $insert_data['time'] = date('Y-m-d H:i:s');
        $this->db->insert('order_cancel_detail',$insert_data);
    }
}