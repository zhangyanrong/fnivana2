<?php
class Warehouse_model extends MY_Model
{
    public function __construct() {
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    public function table_name()
	{
		return 'warehouse';
	}

    public function getRegionKey($area_id){
        return "warehouse_region_".$area_id;
    }

    public function getWarehouseKey($warehouse_id){
        return "warehouse_info_".$warehouse_id;
    }

    public function get_warehouse_by_region($area_id){
        if(empty($area_id)) return false;
        $region_key = $this->getRegionKey($area_id);
        $warehouse_id = '';
        $warehouse_info = array();
        if($this->redis->exists($region_key)){
            $warehouse_id = $this->redis->get($region_key);
        }
        if(empty($warehouse_id)){
            $sql = "select warehouse_id from ttgy_warehouse_region where region_id=".$area_id;
            $warehouse_id = $this->db->query($sql)->row_array();
            $warehouse_id = $warehouse_id['warehouse_id'];
            $this->redis->set($region_key,$warehouse_id);
        }
        if(!$warehouse_id) return false;
        $warehouse_key = $this->getWarehouseKey($warehouse_id);
        if($this->redis->exists($warehouse_key)){
            $warehouse_info = $this->redis->hgetall($warehouse_key);
        }
        if(empty($warehouse_info)){
            $sql = "select * from ttgy_warehouse where id=".$warehouse_id;
            $warehouse_info = $this->db->query($sql)->row_array();
            $warehouse_info and $this->redis->hMset($warehouse_key,$warehouse_info);
        }
        if(empty($warehouse_info) || $warehouse_info['status'] == 0){
            return false;
        }
        $warehouse_info['id'] = $warehouse_id;
        return $warehouse_info;
    }

    public function getWarehouseByID($warehouse_id){
        if(empty($warehouse_id)) return false;
        $warehouse_info = array();
        $warehouse_key = $this->getWarehouseKey($warehouse_id);
        if($this->redis->exists($warehouse_key)){
            $warehouse_info = $this->redis->hgetall($warehouse_key);
        }
        if(empty($warehouse_info)){
            $sql = "select * from ttgy_warehouse where id=".$warehouse_id;
            $warehouse_info = $this->db->query($sql)->row_array();
            $warehouse_info and $this->redis->hMset($warehouse_key,$warehouse_info);
        }
        if(empty($warehouse_info) || $warehouse_info['status'] == 0){
            return false;
        }
        $warehouse_info['id'] = $warehouse_id;
        return $warehouse_info;
    }

    public function getProductAppointWarehouse($price_id){
        $sql = "select * from ttgy_warehouse_product where price_id=".$price_id." and is_appoint = 1";
        $result = $this->db->query($sql)->row_array();
        return $result['warehouse_id']?$result['warehouse_id']:0;
    }

    public function getWarehouseSendCount($warehouse_id){
        $order_time_limit_result = array();
        $key = "warehouseSendCount_".$warehouse_id;
        if($this->redis->exists($key)){
            $order_time_limit_result = $this->redis->hgetall($key);
        }
        return $order_time_limit_result;
    }

    public function getZijianWarehouse($area_id){
        $warehouse_info = $this->get_warehouse_by_region($area_id);
        if(!in_array($warehouse_info['id'],array(1,8))){
            $ware_id = 8;
            $warehouse_info = $this->getWarehouseByID($ware_id);
        }
        $warehouse_info['limit_type'] = 0;
        return $warehouse_info;
    }

    public function getHypostaticWarehouseID($ware_id){
        $warehouse_info = $this->getWarehouseByID($ware_id);
        if(empty($warehouse_info['ph_ware_id'])){
            $sql = "select * from ttgy_warehouse where id=".$warehouse_id;
            $warehouse_info = $this->db->query($sql)->row_array();
            $warehouse_info and $this->redis->hMset($warehouse_key,$warehouse_info);
        }
        if($warehouse_info['ph_ware_id'])
            return $warehouse_info['ph_ware_id'];
        else
            return false;
    }
}