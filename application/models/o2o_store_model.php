<?php
class O2o_store_model extends MY_Model {

    public function table_name(){
        return 'o2o_store';
    }

    function get_o2o_store_banner($store_id,$filter = array(),$limit=0,$channel = '', $exclude_type = array(18)){
        $this->db->select('b.*');
        $this->db->from('o2o_store_banner b');
        $this->db->join('o2o_store_bind_banner as a','b.id=a.banner_id');
        $this->db->where(array('a.store_id'=>$store_id));
        $this->db->where(array('b.is_show'=>'1'));
        $this->db->where('b.start_time <= ',date("Y-m-d H:i:s"));
        $this->db->where('b.end_time >= ',date("Y-m-d H:i:s"));

        //默认过滤类型:18 门店配送提示banner
        if(!empty($exclude_type)){
            $this->db->where_not_in('b.type', $exclude_type);
        }

        if($filter){
            $this->_filter($filter);
        }
        if($limit){
            $this->db->limit($limit,0);
        }
        $this->db->order_by('b.sort desc,b.time desc');
        $results = $this->db->get()->result_array();
        foreach ($results as $key => $value) {
            $results[$key]['photo'] = $value['photo']?PIC_URL.$value['photo']:'';
            $results[$key]['store_id'] = $store_id;
            if($value['type']=='5'){
                $this->db->select('photo');
                $this->db->from('apppage');
                $this->db->where('id',$value['target_id']);
                $photo_result = $this->db->get()->row_array();
                $results[$key]['page_photo'] = PIC_URL.$photo_result['photo']; 
            }
            if(!empty($channel)){
                $value['source'] = explode(',', $value['source']);
                if(!in_array($channel, $value['source'])){
                    unset($results[$key]);
                }
            }
        }
        return $results;
    }

    function getOrderList($where,$offset,$limit){
        $this->db->select('o.id,o.pay_name,o.pay_status,o.order_name,o.time,o.shtime,o.stime,o.money,o.operation_id,o.msg,o.goods_money,o.jf_money,o.card_money,o.pay_discount,o.method_money,o.last_modify,a.address,a.name,a.mobile,e.store_id,e.building_id,s.name as store_name,s.address as store_address,s.phone as store_phone,s.province_id as store_province,s.city_id as store_city,s.area_id as store_area,r.name as building_name,a.province,a.city,a.area');
        $this->db->from('order as o');
        $this->db->join('order_address as a','a.order_id=o.id');
        $this->db->join('o2o_order_extra as e','e.order_id=o.id');
        $this->db->join('o2o_store as s','s.id=e.store_id');
        $this->db->join('o2o_region as r','r.id=e.building_id');
        $this->db->where($where);
        $this->db->limit($limit,$offset);
        $result = $this->db->get()->result_array();
        if(empty($result)){
           return array();
        }
        $order_result_tmp = array();
        $order_ids = array();
        $region_id_arr = array();
        foreach ($result as $key => $value) {
            $region_id_arr[] = $value['store_province'];
            $region_id_arr[] = $value['store_city'];
            $region_id_arr[] = $value['store_area'];
            $region_id_arr[] = $value['province'];
            $region_id_arr[] = $value['city'];
            $region_id_arr[] = $value['area'];

            $order_ids[] = $value['id'];
            $order_result_tmp[$value['id']] = $value;
            
        }
        $this->db->select('area_id,name');
        $this->db->from('o2o_region');
        $this->db->where_in('area_id',$region_id_arr);
        $region_result_tmp = $this->db->get()->result_array();
        $region_result = array();
        foreach ($region_result_tmp as $key => $value) {
            $region_result[$value['area_id']] = $value['name'];
        }

        foreach ($order_result_tmp as $key => $value) {
            if(isset($region_result[$value['province']])){
                $order_result_tmp[$key]['province'] = $region_result[$value['province']];
            }
            if(isset($region_result[$value['city']])){
                $order_result_tmp[$key]['city'] = $region_result[$value['city']];
            }
            if(isset($region_result[$value['area']])){
                $order_result_tmp[$key]['area'] = $region_result[$value['area']];
            } 
            if(isset($region_result[$value['store_province']])){
                $order_result_tmp[$key]['store_province'] = $region_result[$value['store_province']];
            }
            if(isset($region_result[$value['store_city']])){
                $order_result_tmp[$key]['store_city'] = $region_result[$value['store_city']];
            }
            if(isset($region_result[$value['store_area']])){
                $order_result_tmp[$key]['store_area'] = $region_result[$value['store_area']];
            } 
        }
        
        

        $this->db->select('id,product_id,product_name,gg_name,price,qty,order_id');
        $this->db->from('order_product');
        $this->db->where_in('order_id',$order_ids);
        $result = $this->db->get()->result_array();
        foreach ($result as $key => $value) {
            if(isset($order_result_tmp[$value['order_id']])){
                $order_result_tmp[$value['order_id']]['items'][] = $value;
            }
        }
        $order_result = array();
        foreach ($order_result_tmp as $key => $value) {
            $order_result[] = $value;
        }
        return $order_result;
    }

    function getOrderListCount($where){
        $this->db->from('order as o');
        $this->db->join('order_address as a','a.order_id=o.id');
        $this->db->join('o2o_order_extra as e','e.order_id=o.id');
        $this->db->where($where);
        return $this->db->get()->num_rows();
    }

    public function getStoreIdByCode($code = '')
    {
        $store = $this->db->query('select s.id from ttgy_o2o_store s inner join ttgy_o2o_store_physical p on s.physical_store_id = p.id where s.isopen=1 and p.sap_code = ?', array($code))->row_array();
        return !empty($store) ? $store['id'] : 0;
    }
}