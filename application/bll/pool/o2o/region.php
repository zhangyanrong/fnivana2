<?php
namespace bll\pool\o2o;

class Region
{
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }

    public function getBuildings($filter)
    {
        $this->ci->load->model('o2o_region_model');
        $this->ci->load->model('o2o_store_building_model');
        $this->ci->load->model('o2o_push_model');
        $this->ci->load->bll('pool/o2o/area');

        $oids = $this->ci->o2o_push_model->get_pushes('region.getBuildings', $filter['startTime'], $filter['endTime']);
        if (empty($oids))
            return array();

        $ids = array();
        $rs = array();
        foreach ($oids as $v) {
            if (empty($v['is_deleted'])) {
                array_push($ids, $v['oid']);
            } else {
                $rs[] = array(
                    'buildingNo' => $v['oid'],
                    'isDelete' => 1
                );
            }
        }

        $buildings = array();
        if (!empty($ids))
            $buildings = $this->ci->o2o_region_model->getList('*', array('id' => $ids));

        foreach ($buildings as $key => $val) {
            $region = $this->ci->o2o_region_model->getParents($val['pid']);
            $store = $this->ci->o2o_store_building_model->getStoreCode($val['id']);
            $code = array();
            foreach ($store as $v) {
                array_push($code, $v['code']);
            }

            $build = array(
                'buildingNo' => $val['id'],
                'name' => $val['name'],
                'latitude' => $val['latitude'],
                'longitude' => $val['longitude'],
                'addr' => $val['addr'],
                'provinceName' => $region[3]['name'],
                'cityName' => $region[2]['name'],
                'areaName' => $region[1]['name'],
                'bszoneName' => $region[0]['name'],
                'shop' => $code,
                'isDelete' => 0
            );

            $region_match = $this->ci->bll_pool_o2o_area->region_match($build['provinceName'], $build['cityName'], $build['areaName']);

            $build['provinceName'] = $region_match['province'];
            $build['cityName'] = $region_match['city'];
            $build['areaName'] = $region_match['area'];
            $rs[] = $build;

        }
        return $rs;
    }

    public function save($filter)
    {
        return array('result' => 0, 'msg' => '该接口未开放');

        $this->ci->load->model('o2o_region_model');
        $this->ci->load->model('area_model');
        $this->ci->load->model('o2o_store_model');
        $this->ci->load->model('o2o_store_building_model');
        $this->ci->load->library('geohash');

        if (empty($filter))
            return array('result' => 0, 'msg' => '楼宇不能为空');

        foreach ($filter as $data) {
            $province = $data['provinceId'];
            $city = $data['cityId'];
            $area = $data['areaId'];
            $bszone = $data['bszoneName'];
            $building = $data['name'];
            $ext_no = $data['buildingNo'];
            $latitude = $data['latitude'];
            $longitude = $data['longitude'];
            $address = $data['addr'];
            $order = $data['sortFlag'];
            $store = $data['shopCode'];
            $is_delete = $data['isDeleted'];


            if (!$province) return array('result' => 0, 'msg' => '省不能留空');
            if (!$city) return array('result' => 0, 'msg' => '市不能留空');
            if (!$area) return array('result' => 0, 'msg' => '区不能留空');
            if (!$bszone) return array('result' => 0, 'msg' => '商圈不能留空');
            if (!$building) return array('result' => 0, 'msg' => '楼宇不能留空');
            if (!$latitude) return array('result' => 0, 'msg' => '纬度不能留空');
            if (!$longitude) return array('result' => 0, 'msg' => '经度不能留空');
            if (!$ext_no) return array('result' => 0, 'msg' => '楼宇编号不能留空');
            //if (!$store) return array('result' => 0, 'msg' => '门店不能留空');

            $geohash = $this->ci->geohash->encode($latitude, $longitude);

            // 判断楼宇存在
            // $exist = $this->o2o_region_model->dump(array('name'=>$building));
            // if ($exist) continue;

            // 判断省市区存在
            $p = $this->ci->o2o_region_model->dump(array('area_id' => $province, 'attr' => 1));

            if (!$p) {
                $p_area = $this->ci->area_model->dump(array('id' => $province));
                if (!$p_area) return array('result' => 0, 'msg' => '楼宇: ' . $building . '省[' . $province . ']不存在');

                $p = array(
                    'area_id' => $p_area['id'],
                    'pid' => 0,
                    'name' => $p_area['name'],
                    'attr' => 1,
                    'path' => ',',
                );

                $p['id'] = $this->ci->o2o_region_model->save($p);
            }

            $c = $this->ci->o2o_region_model->dump(array('area_id' => $city, 'pid' => $p['id'], 'attr' => 2));
            if (!$c) {
                $c_area = $this->ci->area_model->dump(array('id' => $city));
                if (!$c_area) return array('result' => 0, 'msg' => '楼宇: ' . $building . '市[' . $city . ']不存在');

                $c = array(
                    'area_id' => $c_area['id'],
                    'pid' => $p['id'],
                    'name' => $c_area['name'],
                    'attr' => 2,
                    'path' => $p['path'] . $p['id'] . ',',
                );
                $c['id'] = $this->ci->o2o_region_model->save($c);
            }


            $a = $this->ci->o2o_region_model->dump(array('area_id' => $area, 'pid' => $c['id'], 'attr' => 3));
            if (!$a) {
                $a_area = $this->ci->area_model->dump(array('id' => $area));
                if (!$a_area) return array('result' => 0, 'msg' => '楼宇: ' . $building . '区[' . $area . ']不存在');

                $a = array(
                    'area_id' => $a_area['id'],
                    'pid' => $c['id'],
                    'name' => $a_area['name'],
                    'attr' => 3,
                    'path' => $c['path'] . $c['id'] . ',',
                );
                $a['id'] = $this->ci->o2o_region_model->save($a);
            }

            $bs = $this->ci->o2o_region_model->dump(array('name' => $bszone, 'pid' => $a['id']));
            if (!$bs) {
                $bs = array(
                    'area_id' => 0,
                    'pid' => $a['id'],
                    'name' => $bszone,
                    'attr' => 4,
                    'path' => $a['path'] . $a['id'] . ',',
                );
                $bs['id'] = $this->ci->o2o_region_model->save($bs);
            }

            $b = $this->ci->o2o_region_model->dump(array('ext_no' => $ext_no));
            $bupdate = array(
                'area_id' => 0,
                'pid' => $bs['id'],
                'name' => $building,
                'attr' => 5,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'addr' => $address ? $address : '',
                'order' => $order,
                'is_delete' => $is_delete,
                'path' => $bs['path'] . $bs['id'] . ',',
                'geohash' => $geohash,
                'ext_no' => $ext_no,
            );

            if (!$b) {
                $b = $bupdate;
                $b['id'] = $this->ci->o2o_region_model->save($bupdate);
            } else {
                $this->ci->o2o_region_model->update($bupdate, array('id' => $b['id']));
            }


            if ($is_delete == 1 || empty($store)) {
                $this->ci->o2o_store_building_model->delete(array('building_id' => $b['id']));
                continue;
            }

            $existStores = $this->ci->o2o_store_building_model->getList('store_id', array('building_id' => $b['id']));
            $newStores = $this->ci->o2o_store_model->getList('id', array('code' => $store));
            $old = array_column($existStores, 'store_id');
            $new = array_column($newStores, 'id');
            $del = array_diff($old, $new);
            $ins = array_diff($new, $old);
            if(!empty($del)){
                $this->ci->o2o_store_building_model->delete(array('store_id' => $del, 'building_id' => $b['id']));
            }
            foreach ($ins as $sid) {
                $this->ci->o2o_store_building_model->insert(array('store_id' => $sid, 'building_id' => $b['id']));
            }
        }
    }

    public function change()
    {
        $sql = "select building.id extNo, bszone.name regionName, area.area_id areaId, city.area_id cityId, province.area_id provinceId from ttgy_o2o_region building
                left join ttgy_o2o_region bszone on bszone.id=building.pid
                left join ttgy_o2o_region area on   area.id=bszone.pid
                left join ttgy_o2o_region city on city.id=area.pid
                left join ttgy_o2o_region province on province.id=city.pid
                where building.attr=5";
        $building = $this->ci->db->query($sql)->result_array();
        $store = $this->ci->db->query("select b.building_id, s.code from ttgy_o2o_store_building b, ttgy_o2o_store s where s.id=b.store_id")->result_array();
        $buildingStore = array();
        foreach($store as $k => $s){
            $buildingStore[$s['building_id']][] = $s['code'];
        }
        unset($store);
        foreach($building as $k => $v){
            $building[$k]['shop'] = isset($buildingStore[$v['extNo']]) ? $buildingStore[$v['extNo']] : array();
        }
        return $building;
    }

    /**
     * 同步oms楼宇ID(初始化)
     */
    public function initBuildingID()
    {
        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'url' => POOL_O2O_OMS_URL,
            'method' => 'building.extNo',
            'v' => '1.0',
            'data' => array(),
        );
        $this->ci->bll_rpc_o2o_request->set_rpc_log(array('rpc_desc' => '初始化Building ID', 'obj_type' => 'building.extNo'));
        $response = $this->ci->bll_rpc_o2o_request->realtime_call($params, 10);
        $rs = array();
        if ($response === false) {
            $error = $this->ci->bll_rpc_o2o_request->get_errorinfo();
            echo '<pre>' . print_r($error, true) . '</pre>';die;
        }
        foreach ($response as $v) {
            if(empty($v['extNo']) || empty($v['sid'])){
                $rs[] = $v;
                continue;
            }

            $this->ci->db->update('ttgy_o2o_region', array('ext_no' => $v['sid']), array('id' => $v['extNo']));
        }
        echo '<pre>' . print_r($rs, true) . '</pre>';die;
    }
}
