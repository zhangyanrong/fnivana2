<?php
namespace bll\pool\o2o;

class Store
{
    private $updateCode = false;
    public function __construct()
    {
        $this->ci = & get_instance();
    }

    public function add($filter, $isParent)
    {

        $this->ci->load->model('o2o_store_model');
        $this->ci->load->model('o2o_store_physical_model');
        $this->ci->load->model('area_model');
        $this->ci->load->model('o2o_seller_model');
        $this->ci->load->model('o2o_store_model');
        $this->ci->load->bll('pool/o2o/area');

        if(!$this->updateCode || $isParent) {
            if (!$filter['name']) {
                return array('result' => 0, 'msg' => '门店名称不能留空');
            }

            if (!$filter['address']) {
                return array('result' => 0, 'msg' => '门店地址不能留空');
            }

            $region_match = $this->ci->bll_pool_o2o_area->region_match_from_oms($filter['province'], $filter['city'], $filter['area']);

            $province = $this->ci->area_model->dump(array('name' => $region_match['province']));
            if (!$filter['province'] || empty($province)) {
                return array('result' => 0, 'msg' => '省份不能留空');
            }

            $city = $this->ci->area_model->dump(array('name' => $region_match['city']));
            if (!$filter['city'] || empty($city)) {
                return array('result' => 0, 'msg' => '城市不能留空');
            }

            $area = $this->ci->area_model->dump(array('name' => $region_match['area'], 'pid' => $city['id']));
            if (!$filter['area'] || empty($area)) {
                return array('result' => 0, 'msg' => '行政区不能为留空', 'data'=> $region_match['area']);
            }


            $seller = $this->ci->o2o_seller_model->dump(array('ext_id' => $filter['sellerId']));

            if (empty($seller)) {
                return array('result' => 0, 'msg' => '商铺不存在');
            }
            $seller_id = $seller['id'];
            $filter['openTime'] = json_decode($filter['openTime'], true);
            if ($filter['openTimeSetting'] == 1) {
                $ot = array_shift($filter['openTime']);
                $from = explode(':', $ot['from']);
                $to = explode(':', $ot['to']);
                $opentime = array(
                    'AM' => array(
                        'h' => $from[0],
                        'm' => $from[1]
                    ),
                    'PM' => array(
                        'h' => $to[0],
                        'm' => $to[1]
                    )
                );
            } else {
                $opentime = array();
                foreach ($filter['openTime'] as $value) {
                    if(empty($value['from'])){
                        continue;
                    }
                    $from = explode(':', $value['from']);
                    $to = explode(':', $value['to']);
                    $opentime[$value['key'] != 7 ? $value['key'] : 0] = array(
                        'AM' => array(
                            'h' => $from[0],
                            'm' => $from[1]
                        ),
                        'PM' => array(
                            'h' => $to[0],
                            'm' => $to[1]
                        )
                    );
                }
            }
            $cutOffTime = explode(':',$filter['cutOffTime']);
            $data = array(
                'name' => $filter['name'],
                'ext_id' => $filter['sid'],
                'type' => $filter['type'],
                'code' => $filter['code'],
                'seller_id' => $seller_id,
                'logo' => $filter['logo'],
                'opentime' => serialize($opentime),
                'phone' => $filter['phone'],
                'address' => $filter['address'],
                'longitude' => $filter['longitude'],
                'latitude' => $filter['latitude'],
                'lastmodify' => time(),
                'createtime' => time(),
                'isopen' => $filter['status'],
                'province_id' => $province['id'],
                'city_id' => $city['id'],
                'area_id' => $area['id'],
                'cutofftime' => $cutOffTime[0],
                'opentimesetting' => $filter['openTimeSetting'],
            );

            if(!$isParent){
                $parent_store = $this->ci->o2o_store_physical_model->dump(array('code' => $filter['parentCode']));
                if(empty($parent_store)){
                    $pid = $this->ci->o2o_store_physical_model->insert(array('code' => $filter['parentCode']));
                    $parent_store['id'] = $pid;
                }
                $data['physical_store_id'] = $parent_store['id'];
                $data['order'] = !empty($filter['ordinal']) ? $filter['ordinal'] : 0;
                $store_model = $this->ci->o2o_store_model;
            }else{
                $data['sap_code'] = $filter['sapCode'];
                $data['stock_flag'] = $filter['stockFlag'];
                $store_model = $this->ci->o2o_store_physical_model;
            }

            $store = $store_model->dump(array('code' => $filter['code']));

            if(empty($store)){
                $store_model->insert($data);
            }else{
                if(isset($data['createtime'])) {
                    unset($data['createtime']);
                }
                $store_model->update($data, array('code' => $filter['code']));
            }

        }else{

            $seller = $this->ci->o2o_seller_model->dump(array('ext_id' => $filter['sellerId']));
            if (empty($seller)) {
                return array('result' => 0, 'msg' => '商铺不存在');
            }
            $seller_id = $seller['id'];

            $parent_store = $this->ci->o2o_store_physical_model->dump(array('code' => $filter['parentCode']));

            $data = array(
                'ext_id' => $filter['sid'],
                'code' => $filter['code'],
                'type' => $filter['type'],
                'seller_id' => $seller_id,
                'physical_store_id' => $parent_store['id'],
            );

            $store = $this->ci->o2o_store_model->dump(array('name' => $filter['name']));
            if(!empty($store)){
                $this->ci->o2o_store_model->update($data, array('name' => $filter['name']));
            }
        }

        return array('result'=>1, 'msg' => '成功');
    }

    public function pull($filter = array())
    {
        $this->updateCode = !empty($filter['updateCode']);

        $this->ci->load->bll('rpc/o2o/request');

        $startTime = date('Y-m-d H:i:s', time() - 3600 * 24 * 15);
        $endTime = date('Y-m-d H:i:s');

        if(!empty($filter['startTime'])){
            $startTime = $filter['startTime'];
        }

        if(!empty($filter['endTime'])){
            $endTime = $filter['endTime'];
        }

        $params = array(
            'url' => POOL_O2O_OMS_URL,
            'method' => 'seller.shop.get',
            'data' => array('startTime'=>$startTime,'endTime'=>$endTime, 'sellerId'=>'', 'parent'=> 1),
        );
        $this->ci->bll_rpc_o2o_request->set_rpc_log(array('rpc_desc' => '门店同步', 'obj_type' => 'seller.shop.get'));
        $response = $this->ci->bll_rpc_o2o_request->realtime_call($params, 10);

        $rs = array();
        if(!empty($response)){
            foreach($response as $v){
                if(empty($v['parentCode'])){
                $rs[] = $this->add($v, true);
                }
            }

            foreach($response as $v){
                if(!empty($v['parentCode'])){
                    $rs[] = $this->add($v, false);
                }
            }
        }
        return $rs;
    }


    public function queryAll($filter = array()){
        $this->ci->load->model('o2o_store_model');
        $this->ci->load->model('area_model');
        $this->ci->load->bll('pool/o2o/area');

        if(!empty($filter['start_id']))
            $rs = $this->ci->o2o_store_model->getList('*', array('id >'=>$filter['start_id']));
        else
            $rs = $this->ci->o2o_store_model->getList();


        $stores = array();
        foreach($rs as $k => $v){
            $opentime = unserialize($v['opentime']);
            $newOpentime = array();
            if($v['opentimesetting'] == 1){

                $newOpentime[] = array(
                    'key' => "0",
                    'from'=>$opentime['AM']['h'] . ':' . $opentime['AM']['m'],
                    'to'=>$opentime['PM']['h'] . ':' . $opentime['PM']['m'],
                );
            }else{
                for($i=1; $i<8; $i++){
                    $key = $i != 7 ? $i : 0;
                    if(isset($opentime[$key])){
                        $newOpentime[] = array(
                            'key' => "$i",
                            'from'=>$opentime[$key]['AM']['h'] . ':' . $opentime[$key]['AM']['m'],
                            'to'=>$opentime[$key]['PM']['h'] . ':' . $opentime[$key]['PM']['m']
                        );
                    }else{
                        $newOpentime[] = array(
                            'key' => "$i",
                            'from'=>'',
                            'to'=>''
                        );
                    }
                }
                /*
                foreach($opentime as $_k => $_v){
                    $newOpentime[] = array(
                        'key' => $_k,
                        'from'=>$opentime[$_k]['AM']['h'] . ':' . $opentime[$_k]['AM']['m'],
                        'to'=>$opentime[$_k]['PM']['h'] . ':' . $opentime[$_k]['PM']['m']
                    );
                }*/

            }
            $province = $this->ci->area_model->dump(array('id' => $v['province_id']));
            $rs[$k]['province'] = $province['name'];
            $city = $this->ci->area_model->dump(array('id' => $v['city_id']));
            $rs[$k]['city'] = $city['name'];
            $area = $this->ci->area_model->dump(array('id' => $v['area_id']));
            $rs[$k]['area'] = $area['name'];
            $region_match = $this->ci->bll_pool_o2o_area->region_match($rs[$k]['province'], $rs[$k]['city'], $rs[$k]['area']);



            $stores[] = array(
                'id'              => $v['id'],
                'name'              => $v['name'],
                'code'              => $v['code'],
                'photo'              => $v['photo'],
                'logo'              => $v['logo'],
                'opentime'          => json_encode($newOpentime, JSON_UNESCAPED_UNICODE),
                'phone'             => $v['phone'],
                'address'           => $v['address'],
                'longitude'         => $v['longitude'],
                'latitude'          => $v['latitude'],
                'isopen'            => $v['isopen'],
                'province'          => $region_match['province'],
                'city'              => $region_match['city'],
                'area'              => $region_match['area'],
                'cutofftime'        => $v['cutofftime'],
                'opentimesetting'   => $v['opentimesetting'],
                'order'             => $v['order'],
            );
        }
        return $stores;
    }
}
