<?php

class Product_warehouse_stock_model extends MY_Model
{
	function __construct() {
        // $this->load->library('phpredis');
        // $this->redis = $this->phpredis->getConn();
        $this->load->library('productredis');
        $this->redis = $this->productredis;
    }

	public function table_name()
	{
		return 'product_warehouse_stock';
	}

    public function getProductStock($product_no,$ph_warehouse_id){
        if($this->redis->exists("product_stock:".$product_no.":".$ph_warehouse_id)){
            $sku_info = $this->redis->hMget("product_stock:".$product_no.":".$ph_warehouse_id,array("stock"));
        }else{
            $sku_info = $this->initRedisStock($product_no,$ph_warehouse_id);
        }
        return $sku_info;
    }

    public function setProductStock($product_no,$ph_warehouse_id,$stock=0){
        $sku_info = array("stock"=>$stock);
        $this->redis->hMset("product_stock:".$product_no.":".$ph_warehouse_id,$sku_info);
    }

    public function initRedisStock($product_no,$ph_warehouse_id){
        $where = array();
        $where['product_no'] = $product_no;
        $where['ph_warehouse_id'] = $ph_warehouse_id;
        $sku_info = $this->dump($filter,'stock');
        if($sku_info){
            $this->setProductStock($product_no,$ph_warehouse_id,$sku_info['stock']);
        }
        return $sku_info;
    }

    public function reduceProductStock($product_no,$qty,$ph_warehouse_id){
        $sku_info = $this->getProductStock($product_no,$ph_warehouse_id);
        if($sku_info){
            $stock = $sku_info['stock'] - $qty;
            if($stock<0) $stock = 0;
            return $this->setProductStock($product_no,$ph_warehouse_id,$stock);
        }
        return true;
    }

    public function returnProductStock($product_no,$qty,$ph_warehouse_id){
        $sku_info = $this->getProductStock($product_no,$ph_warehouse_id);
        if($sku_info){
            $stock = $sku_info['stock'] + $qty;
            if($stock<0) $stock = 0;
            return $this->setProductStock($product_no,$ph_warehouse_id,$stock);
        }
        return true;
    }

    public function addProductStock($data){
        if(empty($data) || empty($data[0])) return;
        $fields_array = array_keys($data[0]);
        $fields = implode(',', $fields_array);
        $values = '';
        
        foreach ($data as $key => $value) {
            $values .= "(";
            foreach ($fields_array as $field_name) {
                $values .= "'".addslashes($value[$field_name])."',";
            }
            $values = rtrim($values,',');
            $values .= "),";
        }
        $values = rtrim($values,',');
        $updates = '';
        foreach ($fields_array as $field_name) {
            $updates .= $field_name ."= VALUES(".$field_name."),";
        }
        $updates = rtrim($updates,',');
        $sql = "INSERT INTO `ttgy_product_warehouse_stock` (".$fields.") VALUES ".$values." ON DUPLICATE KEY UPDATE ".$updates;
        $this->db->query($sql);
    }
}