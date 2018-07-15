<?php

class User_address_model extends MY_Model {

    public function table_name() {
        return 'user_address';
    }


    /**
     * 获取用户地址
     */
    function getAddrList($areid,$pag) {
        $rs = $this->db->query('select id,address from ttgy_user_address where province="106092" and (tmscode ="" or tmscode is null) and area='.$areid.' limit '.$pag.',100')
            ->result_array();
        return $rs;
    }

   /*
    * 获取地址省份
    */
    function get_user_address_province($id) {
        $this->db_master->select("province");
        $this->db_master->from("user_address");
        $this->db_master->limit(1);
        $query = $this->db_master->get();
        $result = $query->row_array();
        if (isset($result['province'])) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 获取用户默认收货地址
     *
     * @params $uid int 用户ID
     * @params $aid int 用户新配送地址ID
     */
    public function get_default_address($uid, $aid) {
        $whereArr = ['uid' => $uid];
        if ($aid) {
            $whereArr['id'] = $aid;
        }
        $result = $this->db->from($this->table_name())->where($whereArr)->order_by('is_default', 'DESC')->get()->row_array();
        return $result;
    }

    /**
     * 获取指定的配送地址明细
     *
     * @params $address_id int 配送地址ID
     */
    public function get_address_detail($address_id) {
        if (!$address_id) {
            return [];
        }
        $result = $this->db->from($this->table_name())->where(['id' => $address_id])->get()->row_array();
        return $result;
    }
}