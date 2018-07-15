<?php
class product_type_model extends MY_Model {

    public function table_name(){
        return 'product_type';
    }

    public function o2OGetDataList($city_id)
    {
        if(empty($city_id)) return array();
        $tids = array();
        $product_type = $this->getList();
        $new_types = array();
        foreach ($product_type as $key=>$value) {
            $tids[] = $value['id'];
            $new_types[$value['id']]['id'] = $value['id'];
            $new_types[$value['id']]['name'] = $value['name'];
            $new_types[$value['id']]['photo'] = $value['photo']?PIC_URL.$value['photo']:'';
            $new_types[$value['id']]['icon'] = $value['icon']?PIC_URL.$value['icon']:'';
        }

        $sql = "select sort_type from  ttgy_o2o_region_product_type where area_id=".$city_id;
        $sort_info = $this->db->query($sql)->row_array();
        $typeIDs = explode(',', $sort_info['sort_type']);
        $sort_array = array_unique(array_merge($typeIDs,$tids));
        $p_types = $new_types;
        $key_array = array_keys($p_types);
        $intersect = array_intersect($sort_array, $key_array);
        $new_key_array = array_merge($intersect, array_diff($key_array, $intersect));
        $new_p_types = array();
        foreach ($new_key_array as $val) {
            $new_p_types[] = $p_types[$val];
        }
        return $new_p_types;
    }
}