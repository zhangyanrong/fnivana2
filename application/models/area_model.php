<?php
class Area_model extends MY_Model {

    public function table_name(){
        return 'area';
    }

    /**
     * 根据ID获取最上级省市
     *
     * @return void
     * @author 
     **/
    public function getProvinceByArea($region_id)
    {
        $region = $this->dump(array('id'=>$region_id));

        if (!$region || $region['pid'] == 0) return $region;

        return $this->getProvinceByArea($region['pid']);
    }

    public function getProadcodeByAdcode($adcode){
        $region = $this->dump(array('adcode'=>$adcode,'active'=>1));
        if (!$region || $region['pid'] == 0) return $region;
        $region = $this->dump(array('id'=>$region['pid'],'active'=>1));
        return $this->getProadcodeByAdcode($region['adcode']);
    }

    public function getCityByAreacode($area_code){
        $sql = "select c.* from ttgy_area c join ttgy_area a on c.id=a.pid where a.active=1 and c.active=1 and a.tree_lvl=3 and a.adcode=".$area_code;
        return $this->db->query($sql)->row_array();
    }
}