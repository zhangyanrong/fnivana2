<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 17/10/17
 * Time: 下午6:10
 */

class Invoice_communal_data_model extends MY_Model {
    public function table_name() {
        return 'invoice_communal_data';
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
