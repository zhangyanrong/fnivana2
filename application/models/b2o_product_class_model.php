<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 17/6/8
 * Time: 下午6:03
 */

class B2o_product_class_model extends MY_Model {
    public function table_name() {
        return 'b2o_product_class';
    }

    public function getClass3IdsByClass1($class1Ids) {
        $this->db->select('id');
        $this->db->from('b2o_product_class');
        $condition = array();
        foreach($class1Ids as $id){
            $condition[] = ' cat_path like "'.$id.',%" ';
        }
        $this->db->where(array('step' => 3 , 'is_show' => 1 , '('.implode(' OR ', $condition). ')'=>null));
        $result = $this->db->get()->result_array();
        return $result;
    }
}
