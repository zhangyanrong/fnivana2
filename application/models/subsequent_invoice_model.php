<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 17/4/21
 * Time: 下午1:57
 */
class subsequent_invoice_model extends MY_Model {
    public function table_name() {
        return 'subsequent_invoice';
    }

    public function upInvoiceTrack($invoice_id,$tracking_number,$express,$express_id,$invoice_no,&$msg){
        $invoice_info = $this->dump(array('id'=>$invoice_id));
        if(empty($invoice_info)){
            $msg = '无此充值发票';
            return false;
        }
        if(empty($tracking_number)){
            $msg = '缺少运单号';
            return false;
        }
        if(empty($express_id)){
            $msg = '缺少物流公司ID';
            return false;
        }
        if(empty($express)){
            $msg = '缺少物流公司名称';
            return false;
        }
        if(empty($invoice_no)){
            $msg = '缺少发票号';
            return false;
        }
        $res = $this->db->select('id')->from('subsequent_invoice_track')->where(array('invoice_id'=>$invoice_id))->get()->row_array();
        $data = array();
        $data['tracking_number'] = $tracking_number;
        $data['express_id'] = $express_id;
        $data['express'] = $express;
        $data['invoice_no'] = $invoice_no;
        if($res){
            $this->db->where(array('id'=>$res['id']));
            $result = $this->db->update('subsequent_invoice_track',$data);
        }else{
            $data['invoice_id'] = $invoice_id;
            $result = $this->db->insert('subsequent_invoice_track',$data);
        }
        if(! $result){
            $msg = '物流休息录入失败';
            return false;
        }
        return true;
    }

    public function getListNew($cols='*', $filter=array(), $offset=0, $limit=-1, $orderby=''){
//        $this->db->reconnect();
        $this->db->distinct(true);
        $this->db->select($cols);
        $this->_filter($filter);
        $this->db->from($this->table_name());
        if ($orderby) $this->db->order_by($orderby);
        if ($limit < 0) $limit = '4294967295';
        $this->db->limit($limit,$offset);
        $list = $this->db->get()->result_array();
        return $list ? $list : array();
    }
}