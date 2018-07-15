<?php
class Cityshop_product_model extends MY_Model {

    public function table_name(){
        return 'cityshop_product';
    }

    public function getCityShopProductBarCode(){
        $sql = "select skuid,barcode from ttgy_cityshop_product group by skuid";
        $result = $this->db->query($sql)->result_array();
        $data_info = array();
        foreach ($result as $key => $value) {
            $data_info[$value['skuid']] = $value['barcode'];
        }
        return $data_info;
    }

    public function getCityShopSaleTTGYProducts(){
        $sql = "SELECT a.store_code,a.inner_code FROM (SELECT sp.sap_code AS store_code,ep.inner_code FROM ttgy_o2o_store_goods sg JOIN ttgy_o2o_store s ON sg.store_id=s.id JOIN ttgy_o2o_store_physical sp ON s.physical_store_id=sp.id JOIN ttgy_product p ON p.id=sg.product_id JOIN ttgy_product_price  pp ON p.id=pp.product_id JOIN ttgy_erp_products ep ON ep.code=pp.product_no WHERE s.seller_id=3 AND p.sid=3 GROUP BY sp.sap_code,ep.inner_code) AS a LEFT JOIN ttgy_cityshop_sync_goods csg ON csg.sap_code=a.inner_code AND a.store_code=csg.store_code WHERE csg.sap_code IS NULL AND csg.store_code IS NULL";
        $result = $this->db->query($sql)->result_array();
        return $result;
    }

    public function addCityShopSaleTTGYProducts($data){
        return $this->db->insert_batch("cityshop_sync_goods", $data);
    }

    public function getSyncProductInfo(){
        $sql = "SELECT distinct(sap_code) FROM ttgy_cityshop_sync_goods WHERE sync_oms=0 limit 50";
        $result = $this->db->query($sql)->result_array();
        return $result;
    }

    public function updateCityShopTTGYProducts($data,$sap_code){
        $this->db->where('sap_code',$sap_code);
        $this->db->update('cityshop_sync_goods',$data);
    }

    public function setSyncedCityshopProducts($sap_codes){
        $synced_codes = array();
        if(empty($sap_codes)) return $synced_codes;
        $this->db->select('sap_code');
        $this->db->where('sap_code',$sap_codes);
        $this->db->where('sync_city_shop',1);
        $this->db->from('cityshop_sync_goods');
        $res = $this->db->get()->result_array();
        foreach ($res as $key => $value) {
            $synced_codes = $value['sap_code'];
        }
        $synced_codes = array_unique($synced_codes);
        if($synced_codes){
            $this->db->where('sap_code',$synced_codes);
            $this->db->update('cityshop_sync_goods',array('sync_city_shop'=>1));
        }
        
    }
}
