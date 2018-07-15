<?php
class O2o_store_building_model extends MY_Model {

    public function table_name(){
        return 'o2o_store_building';
    }

    public function getStoreCode($building_id)
    {
        $rs = $this->db->query('SELECT distinct p.code FROM ttgy_o2o_store_building b, ttgy_o2o_store s, ttgy_o2o_store_physical p WHERE b.store_id = s.id AND p.id=s.physical_store_id AND b.building_id=?'
            , array((int) $building_id))
            ->result_array();
        return $rs;
    }

}