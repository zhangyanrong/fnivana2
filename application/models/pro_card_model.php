<?php

class Pro_card_model extends MY_Model
{
    public function table_name()
    {
        return 'pro_card';
    }

    public function upErrorNum($cardNum)
    {
        if (empty($cardNum)) {
            return false;
        }
        $rs = $this->db->set('error_num', 'error_num+1', false)
                       ->where('card_number', $cardNum)
                       ->limit(1)
                       ->update($this->table_name());
        return $rs;
    }
}
