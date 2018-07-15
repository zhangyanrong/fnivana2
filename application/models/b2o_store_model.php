<?php
class B2o_store_model extends MY_Model {

	function B2o_store_model() {
		parent::__construct();
	}

    public function table_name()
    {
        return 'b2o_store';
    }

	/*
	* 获取门店id - 列表
	*/
	function selectStoreList($strSapCode)
    {
        $sql = "select id,is_open,type from ttgy_b2o_store where code in (".$strSapCode.")";
        $result = $this->db->query($sql)->result_array();
        return $result;
	}

    /*
	* 获取门店id - 列表
	*/
    function storeIsOpen($ids)
    {
        $sql = "select id,is_open from ttgy_b2o_store where id in (".$ids.")";
        $result = $this->db->query($sql)->result_array();
        return $result;
    }

    public function getCangByStore($store_ids,$area_adcode){
        $sid2cang = array();
        $store_infos = $this->db->select('id,code')->from('b2o_store')->where_in('id',$store_ids)->get()->result_array();
        $tms_codes = array();
        $tmscode2sid = array();
        $area_cangid = 0;
        foreach ($store_infos as $store) {
            $tms_codes[] = $store['code'];
            $tmscode2sid[$store['code']] = $store['id'];
        }
        if($tms_codes){
            $warehouses = $this->db->select('id,tmscode')->from('warehouse')->where(array('status'=>1))->where_in('tmscode',$tms_codes)->get()->result_array();
            foreach ($warehouses as $warehouse) {
                $sid2cang[$tmscode2sid[$warehouse['tmscode']]] = $warehouse['id'];
            }
        }
        $areas = $this->db->select('id,adcode')->from('area')->where(array('adcode'=>$area_adcode,'tree_lvl'=>'3','active'=>1))->get()->result_array();
        foreach ($areas as $area) {
            if($area['adcode'] == '310115'){
                $area_id = '106096';
            }else{
                $area_id = $area['id'];
            }
            $ware_regions = $this->db->select('warehouse_id')->from('warehouse_region')->where(array('region_id'=>$area_id))->get()->result_array();
            foreach ($ware_regions as $wr) {
                $warehouse = $this->db->select('id')->from('warehouse')->where(array('id'=>$wr['warehouse_id'],'status'=>1))->get()->row_array();
                if($warehouse){
                    $area_cangid = $warehouse['id'];
                    break 2;
                }
            }
        }
        foreach ($store_ids as $store_id) {
            if(! isset($sid2cang[$store_id])){
                $sid2cang[$store_id] = $area_cangid;
            }
        }
        return $sid2cang;
    }
}
