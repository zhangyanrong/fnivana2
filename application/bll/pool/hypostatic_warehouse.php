<?php 
namespace bll\pool;

class Hypostatic_warehouse
{

    public function __construct()
    {
        $this->ci = & get_instance();
        $this->ci->load->model('hypostatic_warehouse_model');
    }

    public function addWarehouse($list){
        $codes = array();
        foreach ($list as $key => $value) {
            $codes[] = $value['sapCode'];
        }
        $exists = $this->ci->hypostatic_warehouse_model->getList('code',array('code'=>$codes));
        $exist_codes = array();
        foreach ($exists as $key => $value) {
            $exist_codes[] = $value['code'];
        }
        $add_codes = array_diff($codes, $exist_codes);
        $del_codes = array_diff($exist_codes, $codes);
        $update_codes = array_intersect($codes, $exist_codes);

        $insert_data = array();
        $update_data = array();
        foreach ($list as $key => $value) {
            $insert_data_one = array();
            $update_data_one = array();
            if(in_array($value['sapCode'], $add_codes)){
                $insert_data_one['name'] = $value['whName'];
                $insert_data_one['code'] = $value['sapCode'];
                $insert_data_one['status'] = ($value['whState']==1)?1:0;
                $insert_data[] = $insert_data_one;
            }elseif(in_array($value['sapCode'], $update_codes)){
                $update_data_one['name'] = $value['whName'];
                $update_data_one['code'] = $value['sapCode'];
                $update_data_one['status'] = ($value['whState']==1)?1:0;
                $update_data[] = $update_data_one;
            }elseif(in_array($value['sapCode'], $del_codes)){
                $update_data_one['status'] = 0;
                $update_data_one['code'] = $value['sapCode'];
                $update_data[] = $update_data_one;
            }
        }
        $insert_data && $this->ci->hypostatic_warehouse_model->insert_batch($insert_data);
        $update_data && $this->ci->hypostatic_warehouse_model->update_batch($update_data);
    }
}