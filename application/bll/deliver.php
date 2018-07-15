<?php
namespace bll;

class Deliver
{
    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->helper('public');

        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();

    }


    /*
     * tms前置仓 - 队列
     */
    public function addTms($params)
    {
        $require_fields = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
            'add_id'=>array('required' => array('code' => '500', 'msg' => 'add_id can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //开仓城市
        $allow_region = array('106092','1');

        if(!in_array($params['region_id'],$allow_region))
        {
            return array("code"=>"200","msg"=>'none');
        }

        $this->ci->load->model('user_address_model');
        $user_add = $this->ci->user_address_model->dump(array('id' => $params['add_id']));

        if(!empty($user_add))
        {
            $this->redis->zAdd('tms_list',time(),$user_add['id']);

            //add tms
            $no = array();
            $no['key'] = $user_add['id'];
            $no['time'] = date('Y-m-d H:i:s');
            $no['status'] = 'start';
            $this->ci->load->library('fdaylog');
            $db_log = $this->ci->load->database('db_log', TRUE);
            $this->ci->fdaylog->add($db_log,'addtms',json_encode($no));
        }

        return array("code"=>"200","msg"=>'add');
    }


    /*
     * 获取tms前置仓 -- 增量
     */
    public function tmsList()
    {
        $tmslist = $this->redis->zRangeByScore('tms_list', '-inf', '+inf', array('withscores'=>false, 'limit'=>array(0,50)));

        if(count($tmslist) >0)
        {
            //add tms
            $nos = array();
            $nos['key'] = 'tms-redis';
            $nos['time'] = date('Y-m-d H:i:s');
            $nos['status'] = 'success';
            $nos['data'] = $tmslist;
            $this->ci->load->library('fdaylog');
            $db_log = $this->ci->load->database('db_log', TRUE);
            $this->ci->fdaylog->add($db_log,'addtms',json_encode($nos));
        }

        $msg = array("code"=>"200","msg"=>'succ');
        if(count($tmslist) >0)
        {
            $this->ci->load->model('user_address_model');
            $this->ci->load->model('area_model');
            $this->ci->load->model('warehouse_model');
            $this->ci->load->bll('rpc/o2o/request');
            foreach($tmslist as $key=>$val)
            {
                $user_add = $this->ci->user_address_model->dump(array('id' => $val));

                $city  = $this->ci->area_model->dump(array('id' => $user_add['city']));
                $area  = $this->ci->area_model->dump(array('id' => $user_add['area']));
                $str_addr = $user_add['address'];
                if(!empty($city['name']) && !empty($area['name']))
                {
                    $str_addr =$city['name'].$area['name'].$str_addr;
                }

                $no = array();

                if(!empty($user_add))
                {
                    $adr[0] = array(
                        'uniqueId'=>$val,
                        'address'=>$str_addr,
                        'mmId'=>'33'
                    );

                    $params = array(
                        'method' => 'address.area',
                        'request_address_list_json' => json_encode($adr,true)
                    );

                    $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);

                    if($response['success'] && !empty($response['addressAreaList'][0]['area']))
                    {
                        $data = array(
                            'tmscode' =>$response['addressAreaList'][0]['area']
                        );
                        $this->ci->user_address_model->update($data, array('id' => $response['addressAreaList'][0]['uniqueId']));

                        $arr_tmsCode = explode('-',$data['tmscode']);
                        $tmsCode =$arr_tmsCode[0];

                        $ware = $this->ci->warehouse_model->dump(array('tmscode' => $tmsCode));
                        if(!empty($ware))
                        {
                            //$this->redis->set('tms_'.$val, $tmsCode.'|'.$ware['id']);
                        }

                        //add tms
                        $no['key'] = $user_add['id'];
                        $no['time'] = date('Y-m-d H:i:s');
                        $no['status'] = 'success';
                        $no['data'] = $response;
                    }
                    else if(empty($response['addressAreaList'][0]['area']))
                    {
                        $data = array(
                            'tmscode' =>''
                        );
                        $this->ci->user_address_model->update($data, array('id' => $response['addressAreaList'][0]['uniqueId']));

                        //add tms
                        $no['key'] = $user_add['id'];
                        $no['time'] = date('Y-m-d H:i:s');
                        $no['status'] = 'fail';
                        $no['data'] = $response;
                    }
                    else
                    {
                        $msg = array("code"=>"300","msg"=>json_encode($response,true));

                        //add tms
                        $no['key'] = $user_add['id'];
                        $no['time'] = date('Y-m-d H:i:s');
                        $no['status'] = 'error';
                        $no['data'] = $response;
                    }
                }

                //add tms
                $this->ci->load->library('fdaylog');
                $db_log = $this->ci->load->database('db_log', TRUE);
                $this->ci->fdaylog->add($db_log,'addtms',json_encode($no));

                $this->redis->zDelete('tms_list',$val);
            }
        }

        return $msg;
    }


    /*
     * 获取tms前置仓 -- 全量
     */
    public function all($par)
    {
        $this->ci->load->model('user_address_model');
        $this->ci->load->model('warehouse_model');

        $pag = $this->redis->get('tms_all_count_'.$par['area']);
        if(empty($pag))
        {
            $pag = 0;
        }

        $str='上海市';
        $this->ci->load->model('area_model');
        $area  = $this->ci->area_model->dump(array('id' => $par['area']));
        if(!empty($area['name']))
        {
            $str .= $area['name'];
        }

        $addrs = $this->ci->user_address_model->getAddrList($par['area'],$pag);

        if(count($addrs) >0)
        {
            $this->ci->load->bll('rpc/o2o/request');
           foreach($addrs as $val)
           {
               $adr[0] = array(
                   'uniqueId'=>$val['id'],
                   'address'=>$str.$val['address'],
                   'mmId'=>'33'
               );

               $params = array(
                   'method' => 'address.area',
                   'request_address_list_json' => json_encode($adr,true)
               );

               //add log
               $no = array();
               $no['key'] = $val['id'];
               $no['addr'] = $str.$val['address'];
               $no['status'] = 'start';
               $this->ci->load->library('fdaylog');
               $db_log = $this->ci->load->database('db_log', TRUE);
               $this->ci->fdaylog->addTms($db_log,'all',json_encode($no));

               $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);

               if($response['success'] && !empty($response['addressAreaList'][0]['area']))
               {
                   $data = array(
                       'tmscode' =>$response['addressAreaList'][0]['area']
                   );
                   $this->ci->user_address_model->update($data, array('id' => $response['addressAreaList'][0]['uniqueId']));

                   $arr_tmsCode = explode('-',$data['tmscode']);
                   $tmsCode =$arr_tmsCode[0];

                   $ware = $this->ci->warehouse_model->dump(array('tmscode' => $tmsCode));
                   if(!empty($ware))
                   {
                       //$this->redis->set('tms_'.$val['id'], $tmsCode.'|'.$ware['id']);
                   }
               }

               //add log
               $nos = array();
               $nos['key'] = $val['id'];
               $nos['addr'] = $str.$val['address'];
               $nos['status'] = 'end';
               $nos['data'] = $response;
               $this->ci->load->library('fdaylog');
               $db_log = $this->ci->load->database('db_log', TRUE);
               $this->ci->fdaylog->addTms($db_log,'all',json_encode($nos));

               $pag++;
               //$this->redis->set('tms_all_count_'.$par['area'],$pag);

               usleep(500000);
           }
        }

        return array("code"=>"200","msg"=>'succ');
    }

    /*
     * 根据坐标，获取tmscode
     * @params region_id
     * @params lonlat
     * @params address
     */
    public function getTmsCode($params)
    {
        $require_fields = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $res = array('cang_id'=>$params['cang_id'],'store_id'=>'');

        //定位
        if(!empty($params['lonlat']) && !empty($params['address']))
        {
            $this->ci->config->load("region");

            $this->ci->load->model('region_model');
            $site_region = $this->ci->config->item('site_region');

            $region_name = '';
            foreach($site_region as $key=>$val)
            {
                if($val['region_id'] == $params['region_id'])
                {
                    $region_name = $val['region_name'];
                }

                foreach($val['son_region'] as $k=>$v)
                {
                    if($v['region_id'] == $params['region_id'])
                    {
                        $region_name = $v['region_name'];
                    }
                }
            }

            $is_region = strstr($params['address'],$region_name);

            if($is_region === false)
            {
                return $res;
            }
            else
            {
                $this->ci->load->bll('rpc/o2o/request');

                $mmId = 0;
                if($params['region_id'] == '106092')
                {
                    $mmId = '33';
                }
                else if($params['region_id'] == '144261')
                {
                    $mmId = '94';
                }
                else if($params['region_id'] == '2')
                {
                    $mmId = '33';
                }

                if($mmId > 0)
                {
                    $adr[0] = array(
                        'lonlat'=>$params['lonlat'],
                        'mmId'=>$mmId,
                    );

                    $params = array(
                        'method' => 'address.area',
                        'request_address_list_json' => json_encode($adr,true)
                    );

                    $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);

                    if($response['success'] == 1 && !empty($response['addressAreaList'][0]['area']))
                    {
                        $data = array(
                            'tmscode' =>$response['addressAreaList'][0]['area'],
                            'o2ocode' =>$response['addressAreaList'][0]['o2oArea'],
                        );
                        //$arr_tmsCode = explode('-',$data['tmscode']);
                        //$tmsCode =$arr_tmsCode[0];

                        $arr_o2oCode = explode('-',$data['o2ocode']);
                        $o2oCode = $arr_o2oCode[0];

                        $this->ci->load->model('warehouse_model');
                        $ware = $this->ci->warehouse_model->dump(array('tmscode' => $o2oCode));
                        if(!empty($ware))
                        {
                            $res['cang_id'] = $ware['id'];
                        }

                        //o2o
                        $this->ci->load->model('o2o_store_model');
                        $store_id = $this->ci->o2o_store_model->getStoreIdByCode($o2oCode);
                        if($store_id >0)
                        {
                            $res['store_id'] = $store_id;
                        }
                    }

                    return $res;
                }
                else
                {
                    return $res;
                }
            }
        }
        else
        {
            return $res;
        }
    }


    /*
     * 根据坐标，获取tmscode O2O
     * @params region_id
     * @params lonlat
     */
    public function getO2oTmsCode($params)
    {
        $require_fields = array(
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $res = array('cang_id'=>$params['cang_id'],'store_id'=>'');

        //定位
        if(!empty($params['lonlat']))
        {
            $this->ci->load->bll('rpc/o2o/request');

            $mmId = 0;
            if ($params['region_id'] == '106092') {
                $mmId = '33';
            } else if ($params['region_id'] == '144261') {
                $mmId = '94';
            }

            if ($mmId > 0) {
                $adr[0] = array(
                    'lonlat' => $params['lonlat'],
                    'mmId' => $mmId,
                    'sendType'=>2,
                );

                $params = array(
                    'method' => 'address.area',
                    'request_address_list_json' => json_encode($adr, true)
                );

                $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);

                if ($response['success'] == 1 && !empty($response['addressAreaList'][0]['area'])) {
                    $data = array(
                        'tmscode' => $response['addressAreaList'][0]['area']
                    );
                    $arr_tmsCode = explode('-', $data['tmscode']);
                    $tmsCode = $arr_tmsCode[0];

                    $this->ci->load->model('warehouse_model');
                    $ware = $this->ci->warehouse_model->dump(array('tmscode' => $tmsCode));
                    if (!empty($ware)) {
                        $res['cang_id'] = $ware['id'];
                    }

                    //o2o
                    $this->ci->load->model('o2o_store_model');
                    $store_id = $this->ci->o2o_store_model->getStoreIdByCode($tmsCode);
                    if ($store_id > 0) {
                        $res['store_id'] = $store_id;
                    }
                }

                return $res;
            }
        }
        else
        {
            return $res;
        }
    }

    /*
     * 根据坐标，获取省市区
     * @params lonlat
     */
    public function getTmsAddr($params)
    {
        $require_fields = array(
            'lonlat' => array('required' => array('code' => '500', 'msg' => 'lonlat can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->bll('rpc/o2o/request');

        $adr[0] = array(
            'lonlat' => $params['lonlat']
        );

        $params = array(
            'method' => 'address.city',
            'request_lonlat_list_json' => json_encode($adr, true)
        );

        $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);
        $res = array();
        if ($response['success'] == 1)
        {
            $res = array(
                'province'=>$response['cityList'][0]['province'],
                'city'=>$response['cityList'][0]['city'],
                'district'=>$response['cityList'][0]['district']
            );
        }

        return $res;
    }

    /**
     * @api              {post} / 获取门店地址列表ID和配送仓
     * @apiDescription   获取门店地址列表ID和配送仓
     * @apiGroup         deliver
     * @apiName          getTmsStore
     *
     * @apiParam {String} lonlat Gps,逗号分隔
     * @apiParam {String} districtCode 区域－国标code
     *
     * @apiSampleRequest /api/test?service=deliver.getTmsStore&source=app
     */
    public function getTmsStore($params)
    {
        $require_fields = array(
            'lonlat' => array('required' => array('code' => '500', 'msg' => 'lonlat can not be null')),
            'districtCode' => array('required' => array('code' => '500', 'msg' => 'lonlat can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $res = array('store_id_list'=>'','delivery_code'=>'','tms_region_type'=>1,'tms_region_time'=>1,'is_day_night'=>0,'delivery_end_time'=>'');

        //region code
        $districtCode = $params['districtCode'];

        // 高德 浙江省宁波市奉化区 区域码临时解决
        if ($districtCode == 330213) {
            $districtCode = 330283;
        }

        if(strlen($districtCode) == 6)
        {
            $provinceCode = mb_substr($districtCode,0,2).'0000';
            $cityCode = mb_substr($districtCode,0,4).'00';
        }
        else
        {
            return array('code' =>'300', 'msg' =>'定位错误，请重试');
        }

        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'method' => 'address.store.query',
            'lonlat' => $params['lonlat'],
            'lonlatType'=>'GAODE',
            'provinceCode'=>$provinceCode,
            'cityCode'=>$cityCode,
            'districtCode'=>$districtCode
        );

        $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);

        if($response['success'] == true && !empty($response['prewhCode']))
        {
            //deliver id
            $this->ci->load->model('warehouse_model');
            // $ware = $this->ci->warehouse_model->dump(array('tmscode' =>$response['prewhCode']));
            // if (!empty($ware)) {
            //     $res['delivery_code'] = $ware['id'];
            // }
            $ware = $this->ci->warehouse_model->dump(array('tmscode' =>$response['prewhCode'],'status'=>1));
            if (!empty($ware)) {
                $res['delivery_code'] = $ware['id'];
            }else{
                //deliver id
                $this->ci->load->model('area_model');
                $area = $this->ci->area_model->getList('id',array('adcode'=>$districtCode,'active'=>1,'tree_lvl'=>3));

                if(!empty($area))
                {
                    $this->ci->load->model('warehouse_region_model');
                    foreach ($area as $value)
                    {
                        if($districtCode == '310115')
                        {
                            $area_id = '106096';
                        }
                        else
                        {
                            $area_id = $value['id'];
                        }

                        $ware = $this->ci->warehouse_region_model->dump(array('region_id'=>$area_id));
                        if($ware)
                        {
                            $warehouse = $this->ci->warehouse_model->dump(array('id' =>$ware['warehouse_id'],'status'=>1));
                            if($warehouse)
                            {
                                $res['delivery_code'] = $warehouse['id'];
                                break;
                            }
                        }
                    }
                }
            }

            $sap = $response['storeList'];
            $sapCode = array();
            $regionTime = array();
            $regionType = array();
            if(count($sap) > 0)
            {
                foreach($sap as $key=>$val)
                {
                    array_push($sapCode,"'".$val['sapCode']."'");
                    array_push($regionTime,$val['regionTime']);
                    array_push($regionType,$val['regionType']);
                }
            }

            if(!empty($response['whCode']) && !empty($response['deliveryType']))
            {
                $sapCode[] = "'".$response['whCode'].'-'.$response['deliveryType']."'";
            }

            if(count($sapCode) > 0)
            {
                $strSapCode = implode(',',$sapCode);
                $this->ci->load->model('b2o_store_model');
                $store = $this->ci->b2o_store_model->selectStoreList($strSapCode);

                //年轮
                $res['tms_region_type'] = min($regionType);
                $res['tms_region_time'] = min($regionTime);

                foreach($store as $k=>$v)
                {
                    if($v['is_open'] == 0)
                    {
                        unset($store[$k]);
                    }
                    else if($v['type'] == 3)
                    {
                        $store[$k]['id'] = $v['id'].'T'.$res['tms_region_type'].'T'.$res['tms_region_time'];
                    }
                }

                if(count($store) > 0)
                {
                    $storeList = array_column($store,'id');
                    $store_ids = implode(',',$storeList);
                    $res['store_id_list'] = $store_ids;
                }
            }

            $pre = mb_substr($response['prewhCode'],0,1);
            if($pre == 'H' || $pre == 'S' || $pre == 'D')
            {
                if($res['tms_region_type'] == 1)
                {
                    //1小时达
                    $res['delivery_type'] = array(
                        'https://huodongjd1.fruitday.com/sale/appxx/shi_1.png',
                        'https://huodongjd1.fruitday.com/sale/appxx/shi_2.png'
                    );
                }
                else{
                    //当日达
                    $res['delivery_type'] = array(
                        'https://huodongjd1.fruitday.com/sale/appxx/di_1.png',
                        'https://huodongjd1.fruitday.com/sale/appxx/di_2.png'
                    );
                }

                $qz = explode(',',$res['store_id_list']);
                if($pre == 'D' && count($qz) == 1)
                {
                    //次日达
                    $res['delivery_type'] = array(
                        'https://huodongjd1.fruitday.com/sale/appxx/ci_1.png',
                        'https://huodongjd1.fruitday.com/sale/appxx/ci_2.png'
                    );
                }
            }
            else
            {
                //次日达
                $res['delivery_type'] = array(
                    'https://huodongjd1.fruitday.com/sale/appxx/ci_1.png',
                    'https://huodongjd1.fruitday.com/sale/appxx/ci_2.png'
                );
            }

            $res['is_day_night'] = $response['selfdeliveryNight'];  //支持晚单
            if($response['endTime'] > 18)
            {
                $res['delivery_end_time'] = $response['endTime']; //截单时间
            }
        }
        else if($response['success'] == true && empty($response['prewhCode']))
        {
            $sapCode = array();
            if(!empty($response['whCode']) && !empty($response['deliveryType']))
            {
                $sapCode[] = "'".$response['whCode'].'-'.$response['deliveryType']."'";
            }

            if(count($sapCode) > 0)
            {
                $strSapCode = implode(',',$sapCode);
                $this->ci->load->model('b2o_store_model');
                $store = $this->ci->b2o_store_model->selectStoreList($strSapCode);
                foreach($store as $k=>$v)
                {
                    if($v['is_open'] == 0)
                    {
                        unset($store[$k]);
                    }
                }
                if(count($store) > 0)
                {
                    $storeList = array_column($store,'id');
                    $store_ids = implode(',',$storeList);
                    $res['store_id_list'] = $store_ids;
                }
            }

            //deliver id
            $this->ci->load->model('area_model');
            $area = $this->ci->area_model->getList('id',array('adcode'=>$districtCode,'active'=>1,'tree_lvl'=>3));

            if(!empty($area))
            {
                $this->ci->load->model('warehouse_region_model');
                foreach ($area as $value)
                {
                    if($districtCode == '310115')
                    {
                        $area_id = '106096';
                    }
                    else
                    {
                        $area_id = $value['id'];
                    }

                    $ware = $this->ci->warehouse_region_model->dump(array('region_id'=>$area_id));
                    if($ware)
                    {
                        $this->ci->load->model('warehouse_model');
                        $warehouse = $this->ci->warehouse_model->dump(array('id' =>$ware['warehouse_id'],'status'=>1));
                        if($warehouse)
                        {
                            $res['delivery_code'] = $warehouse['id'];
                            break;
                        }
                    }
                }
            }

            //次日达
            $res['delivery_type'] = array(
                'https://huodongjd1.fruitday.com/sale/appxx/ci_1.png',
                'https://huodongjd1.fruitday.com/sale/appxx/ci_2.png'
            );

            $res['is_day_night'] = $response['selfdeliveryNight']; //支持晚单
            if($response['endTime'] > 18)
            {
                $res['delivery_end_time'] = $response['endTime']; //截单时间
            }
        }

        //无配送仓日志
        if(empty($res['delivery_code']))
        {
            $this->ci->load->model('adcode_log_model');
            $res_ad = $this->ci->adcode_log_model->dump(array('ad_code'=>$params['districtCode']));
            if(!$res_ad)
            {
                $ad_code = array(
                    'lonlat'=>$params['lonlat'],
                    'ad_code'=>$params['districtCode'],
                    'time'=>date('Y-m-d H:i:s'),
                    'data'=>json_encode($response)
                );
                $this->ci->adcode_log_model->add_adcode($ad_code);
            }
        }

        $out = $this->returnMsg($res);
        return $out;
    }

    public function checkChangeAddress($params)
    {
        $required = [
            'f_lonlat'       => ['required' => ['code' => '500', 'msg' => 'f_lonlat can not be null']],
            'f_districtCode' => ['required' => ['code' => '500', 'msg' => 'f_districtCode can not be null']],
            't_lonlat'       => ['required' => ['code' => '500', 'msg' => 't_lonlat can not be null']],
            't_districtCode' => ['required' => ['code' => '500', 'msg' => 't_districtCode can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        // 获取 to 坐标的 province、city
        $t_districtCode = $params['t_districtCode'];
        if (6 == strlen($t_districtCode)) {
            $t_provinceCode = mb_substr($t_districtCode, 0, 2) . '0000';
            $t_cityCode = mb_substr($t_districtCode, 0, 4) . '00';
        } else {
            return ['code' => '300', 'msg' => '定位错误，请重试'];
        }

        $this->ci->load->bll('rpc/o2o/request');
        $t_params = [
            'method'        => 'address.store.query',
            'lonlat'        => $params['t_lonlat'],
            'lonlatType'    => 'GAODE',
            'provinceCode'  => $t_provinceCode,
            'cityCode'      => $t_cityCode,
            'districtCode'  => $t_districtCode,
        ];
        $t_response = $this->ci->bll_rpc_o2o_request->tmsware_call($t_params);

        if ($t_response['success']) {
            if ($t_response['selfdeliveryNight']) {
                // to selfdeliveryNight = 1
                return ['code' => '200', 'msg' => '', 'data' => ['influence' => false]];
            } else {
                // 获取 from 坐标的 province、city
                $f_districtCode = $params['f_districtCode'];
                if (6 == strlen($f_districtCode)) {
                    $f_provinceCode = mb_substr($f_districtCode, 0, 2) . '0000';
                    $f_cityCode = mb_substr($f_districtCode, 0, 4) . '00';
                } else {
                    return ['code' => '300', 'msg' => '定位错误，请重试'];
                }

                $f_params = [
                    'method'        => 'address.store.query',
                    'lonlat'        => $params['f_lonlat'],
                    'lonlatType'    => 'GAODE',
                    'provinceCode'  => $f_provinceCode,
                    'cityCode'      => $f_cityCode,
                    'districtCode'  => $f_districtCode,
                ];
                $f_response = $this->ci->bll_rpc_o2o_request->tmsware_call($f_params);

                if ($f_response['success']) {
                    if ($f_response['selfdeliveryNight']) {
                        // to selfdeliveryNight = 0, from selfdeliveryNight = 1
                        return ['code' => '200', 'msg' => '', 'data' => ['influence' => true]];
                    } else {
                        // to selfdeliveryNight = 0, from selfdeliveryNight = 0
                        return ['code' => '200', 'msg' => '', 'data' => ['influence' => false]];
                    }
                } else {
                    return ['code' => '300', 'msg' => '获取运能失败，请重新再试'];
                }
            }
        } else {
            return ['code' => '300', 'msg' => '获取运能失败，请重新再试'];
        }
    }

    /**
     * @api              {post} / 获取用户历史地址列表
     * @apiDescription   获取用户历史地址列表
     * @apiGroup         deliver
     * @apiName          getGpsAddress
     *
     * @apiParam {String} lonlat Gps,逗号分隔
     * @apiParam {Number} uid 用户id
     *
     * @apiSampleRequest /api/test?service=deliver.getGpsAddress&source=app
     */
    public function getGpsAddress($params)
    {
        $require_fields = array(
            'uid' => array('required' => array('code' => '500', 'msg' => 'uid can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $res = array('defaultAddress'=>[],'addressList'=>[]);

        //GPS null
        if(empty($params['lonlat']))
        {
            $addressList = $this->userAddrList($params['uid']);
            if(count($addressList) > 0)
            {
                //过滤无效地址
                foreach($addressList as $key=>$val)
                {
                    if(empty($val['lonlat']))
                    {
                        unset($addressList[$key]);
                    }
                }

                if(count($addressList) > 0)
                {
                    $this->ci->load->model('order_model');
                    $last_id = $this->ci->order_model->getLastAddr($params['uid']);
                    if(!empty($last_id))
                    {
                        foreach($addressList as $key=>$val)
                        {
                            if($val['id'] == $last_id)
                            {
                                $res['defaultAddress'] = $addressList[$key];
                            }
                        }
                    }
                    else
                    {
                        $res['defaultAddress'] = $addressList[0];
                    }
                }
            }
            $res['addressList'] = array_reverse($addressList);
            $out = $this->returnMsg($res);
            return $out;
        }

        //GPS
        $par_gps = explode(',',$params['lonlat']);
        $address = $this->userAddrList($params['uid']);

        //search
        if(count($address) >0)
        {
            foreach($address as $key=>$val)
            {
                if(empty($val['lonlat']))
                {
                    unset($address[$key]);
                }
            }

            foreach($address as $key=>$val)
            {
                $addr_gps = explode(',',$val['lonlat']);
                $address[$key]['range'] = & getDistance($par_gps[1],$par_gps[0],$addr_gps[1],$addr_gps[0]);
            }

            $arr_range = array_column($address,'range');
            array_multisort($arr_range, SORT_ASC,$address);

//            if($address[0]['range'] < 3000) //精度单位-m
//            {
//                $res['defaultAddress'] = $address[0];
//            }
            if(count($address) >= 1)
            {
                $this->ci->load->model('order_model');
                $last_id = $this->ci->order_model->getLastAddr($params['uid']);

                if(!empty($last_id))
                {
                    foreach($address as $key=>$val)
                    {
                        if($val['id'] == $last_id)
                        {
                            $res['defaultAddress'] = $address[$key];
                        }
                    }
                }
                else
                {
                    $res['defaultAddress'] = $address[0];
                }
            }
            $res['addressList'] = $address;
        }

        $out = $this->returnMsg($res);
        return $out;
    }

    /*
     * 用户地址列表
     */
    private function userAddrList($uid)
    {
        $params['source'] = 'app';
        $this->ci->load->model('user_model');
        $address = $this->ci->user_model->geta_user_address($uid, '','', $params['source']);
        return $address;
    }

    /*
     * 格式化输出 - app
     */
    private function returnMsg($data)
    {
        return array('code'=>200,'msg'=>'succ','data'=>$data);
    }

    /*
     * 支付日志
     */
    private function addLog($data) {
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, 'gps_error', json_encode($data));
    }

    /*
     * 根据坐标,地址匹配
     * @params lonlat
     * @params address
     */
    public function checkGpsAddr($params)
    {
        $require_fields = array(
            'lonlat' => array('required' => array('code' => '500', 'msg' => '定位坐标不能空')),
            'address' => array('required' => array('code' => '500', 'msg' => '详细地址不能为空')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'method' => 'address.checkAddressInSameArea',
            'lonlat' => $params['lonlat'],
            'address'=> $params['address'],
        );

        $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);
        $res = array('code'=>'200','msg'=>'succ');
        if ($response['success'] == 1)
        {
            if($response['isSame'] == 0)
            {
                $res['code'] = '315';
                //$res['msg'] = '您填写的详细地址：'.$params['address'].'与您选择的收货地点坐标差异较大，可能会影响配送，是否确认该地址无误并继续保存？';
                $res['msg'] = '您的收货详细地址与选择的定位地址差异较大，我们将尽快为您核实确认，如超出配送区域将会在30分钟内与您取得联系。';
            }
        }

        return $res;
    }


    /*
     * 根据2个地址，返回距离
     * @params store_address
     * @params user_address
     */
    public function checkRange($params)
    {
        $require_fields = array(
            'store_address' => array('required' => array('code' => '500', 'msg' => '门店不能空')),
            'user_address' => array('required' => array('code' => '500', 'msg' => '用户地址不能为空')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'method' => 'address.distance',
            'address1' => $params['store_address'],
            'address2'=> $params['user_address'],
        );

        $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);
        $res = array('code'=>'200','msg'=>'succ');
        if ($response['success'] == 1)
        {
            if($response['addrDistance'] > 3000)
            {
                $res['code'] = '300';
                $res['msg'] = '您填写的详细地址超出配送范围';
            }
        }
        return $res;
    }

    /*
     * 根据地址，获取tmscode active
     * @params address
     */
    public function getActiveTmsCode($params)
    {
        $require_fields = array(
            'address' => array('required' => array('code' => '500', 'msg' => 'address can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $res = array('tmscode'=>'','lonlat'=>'','storename'=>'');

        $this->ci->load->bll('rpc/o2o/request');

        $mmId = 33;
        $adr[0] = array(
            'address' => $params['address'],
            'mmId' => $mmId,
        );
        $params = array(
            'method' => 'address.area',
            'request_address_list_json' => json_encode($adr, true)
        );
        $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);

        if ($response['success'] == 1) {
            $res['tmscode'] = $response['addressAreaList'][0]['o2oArea'];
            if(!empty($res['tmscode']))
            {
                $this->ci->load->model('b2o_store_model');
                $store = $this->ci->b2o_store_model->dump(array('code'=>$res['tmscode']));
                if(!empty($store))
                {
                    $res['storename'] = $store['name'];
                }
            }
            else
            {
                $res['tmscode'] = $response['addressAreaList'][0]['fullArea'];
            }
            $res['lonlat'] = $response['addressAreaList'][0]['lonlat'];
        }
        return $res;
    }

    /*
     * 根据地址获取门店信息
     * @params address
     */
    public function getStoreByAddress($params)
    {
        $require_fields = array(
            'address' => array('required' => array('code' => '500', 'msg' => 'address can not be null')),
        );
        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'method' => 'address.store.query.byAddress',
            'address' => $params['address'],
            'shopType' => '',
        );
        $response = $this->ci->bll_rpc_o2o_request->tmsware_call($params);

        return $response;
    }

    /*
     * TMS city box
     * @params address
     */
    public function getCityBoxTms($params)
    {
        $require_fields = array(
            'address' => array('required' => array('code' => '500', 'msg' => 'address can not be null')),
            'area' => array('required' => array('code' => '500', 'msg' => 'area can not be null')),
            'boxCode' => array('required' => array('code' => '500', 'msg' => 'boxCode can not be null')),
            'city' => array('required' => array('code' => '500', 'msg' => 'city can not be null')),
            'isSelfType' => array('required' => array('code' => '500', 'msg' => 'isSelfType can not be null')),
            'province' => array('required' => array('code' => '500', 'msg' => 'province can not be null')),
            'sendType' => array('required' => array('code' => '500', 'msg' => 'sendType can not be null')),
            'siteCode' => array('required' => array('code' => '500', 'msg' => 'siteCode can not be null')),
            'status' => array('required' => array('code' => '500', 'msg' => 'status can not be null')),

        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->bll('rpc/o2o/request');
        $params = array(
            'method' => 'tmscar.box.saveBox',
            'address' => $params['address'],
            'area' => $params['area'],
            'boxCode' => $params['boxCode'],
            'lonlat' => $params['lonlat'],
            'city' => $params['city'],
            'isSelfType' => $params['isSelfType'],
            'province' => $params['province'],
            'sendType' => $params['sendType'],
            'siteCode' => $params['siteCode'],
            'status' => $params['status'],
        );

        $response = $this->ci->bll_rpc_o2o_request->tmscitybox_call($params);

        return $response;
    }
}
