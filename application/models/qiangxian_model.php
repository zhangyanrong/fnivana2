<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property qx_model      $getTable
 */
class Qiangxian_model extends MY_Model {

    function __construct() {
        parent::__construct();
        $this->load->helper('public');
    }


    public function table_name() {
        return 'qx_model';
    }


    public function getQxAd($id) {
        $result = $this->db->from('qx_ad')->where('qx_model_id', $id)->order_by('order_by')->get()->result_array();
        $re = array();
        foreach ($result as $v) {
            $v['ad_config'] = json_decode($v['ad_config'], true);
            if ($v['type'] == 3 ) {
                if (!empty($v['ad_config']['bkLink3'])){
                    $v['tagNum'] = 3;
                } else if (!empty($v['ad_config']['bkLink2'])){
                    $v['tagNum'] = 2;
                } else {
                    $v['tagNum'] = 1;
                }
                $re[$v['type']][] = $v;
            }
            if($v['type'] == 4){
                $re[$v['type']][] = $v;
            }
            if($v['type'] <=2 || $v['type'] == 9){
                $re[$v['type']] = $v;
            }
        }
        return $re;
    }

    public function getQx($id) {
        $result = $this->db->from('qx_model')->where('id', $id)->get()->row_array();
        return $result;

    }

}
