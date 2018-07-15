<?php
namespace bll;

class O2o
{
  private $_orderinit = array();
  private $_cartCache = false;

  private $_error = '';

  private $_code = 300;

  private $cache_prefix = 'o2o_page_';
    /**
     * 地球半径
     *
     * @var string
     **/
    private $_earth_radius = 6371;

    /**
     * 查找半径
     *
     * @var string
     **/

    private $_zero_product_id = array(4713, 4715);
    private  $_radius = 2;

    private $_o2oTuan_productids = array(8324, 8647, 8613, 8614, 8615, 8846);

    private $_send_type = array();

    /**
     * 保存手机设备相关数据
     *
     * @author luke_lu add 2016/03/25
     */
    private $_parameters = array();

    public function __construct($params = array())
    {
        $this->ci = &get_instance();
        $this->_source = $params['source'];
        $this->_version = $params['version'];
        $this->_parameters = $params; //luke_lu:从上一层获取所需参数
    }

    public function get_error()
    {
      return $this->_error;
    }

    public function get_code()
    {
      return $this->_code;
    }

    /**
     * 定位最近的楼宇
     *
     * @return void
     * @author
     **/
    public function nearbyBuilding($area_id,$longitude,$latitude)
    {
      $data = array();

        // first level filtering
        $max_latitude = $latitude + rad2deg($this->_radius / $this->_earth_radius);
        $min_latitude = $latitude - rad2deg($this->_radius / $this->_earth_radius);

        $max_longitude = $longitude + rad2deg($this->_radius/$this->_earth_radius/cos(deg2rad($latitude)));
        $min_longitude = $longitude - rad2deg($this->_radius/$this->_earth_radius/cos(deg2rad($latitude)));

        // convert location to radians
        $latitude = deg2rad($latitude);
        $longitude = deg2rad($longitude);

        $sql = "SELECT id, pid, name, latitude, longitude, acos(sin({$latitude}) * sin(radians(latitude)) + cos({$latitude}) * cos(radians(latitude)) * cos(radians(longitude)-{$longitude})) * {$this->_earth_radius} AS distance
            FROM (
              SELECT id,pid,name, latitude, longitude
              FROM ttgy_o2o_region
              WHERE latitude > {$min_latitude} AND latitude < {$max_latitude} AND longitude > {$min_longitude} AND longitude < {$max_longitude}
              ) AS filtered_results
            WHERE acos(sin({$latitude})*sin(radians(latitude)) + cos({$latitude}) * cos(radians(latitude)) * cos(radians(longitude) - {$longitude})) * {$this->_earth_radius} < {$this->_radius}
            ORDER BY distance";

        $rows = $this->ci->db->query($sql)->result_array();

        $this->ci->load->model('o2o_store_building_model');
        $this->ci->load->model('o2o_region_model');
        $attr = $this->ci->o2o_region_model->attr;
        foreach ($rows as $key => $value) {
            $store_building = $this->ci->o2o_store_building_model->dump(array('building_id' => $value['id']));

            if (!$store_building) continue;

            $d = array(
              'id'        => $value['id'],
              'pid'       => $value['pid'],
              'name'      => $value['name'],
              'latitude'  => $value['latitude'],
              'longitude' => $value['longitude'],
              'store'     => array('id'=>$store_building['store_id']),
            );

            $parents = $this->ci->o2o_region_model->getParents($value['pid']);
            foreach ($parents as $p) {
              $d[$attr[$p['attr']]]['id']   = $p['id'];
              $d[$attr[$p['attr']]]['name'] = $p['name'];
            }

            $data[] = $d;
        }

        return $data;
    }

    /**
     * 定位最近的商圈
     *
     * @param Int $area_id 行政区ID
     * @param String $longitude 经度
     * @param String $latitude 纬度
     * @return void
     * @author
     **/
    public function nearbyBszone($area_id,$longitude,$latitude)
    {
        $nearbyBuilding = $this->nearbyBuilding($area_id,$longitude,$latitude);

        $this->ci->load->model('o2o_region_model');

        $data = array();
        if ($nearbyBuilding) {
            $pid = array();
            foreach ($nearbyBuilding as $key => $value) {
                $pid[] = $value['pid'];
            }

            $data = $this->ci->o2o_region_model->getList('id,name,latitude,longitude',array('id'=>$pid));
        }

        return $data;
    }

    /**
     * 获取城市门店
     *
     * @param Int $city_id
     * @return void
     * @author
     **/
    public function citystores($city_id)
    {
        $this->ci->load->model('o2o_store_model');

        $stores = $this->ci->o2o_store_model->getList('*',array('city_id'=>$city_id),0,-1,'order desc');

        $data = array();

        foreach ($stores as $key => $value) {
          $open = $this->_check_store($value);
          if (!$open) {
            continue;
          }

            $data[] = array(
                'store_id'        => $value['id'],
                'store_name'      => $value['name'],
                'store_open_time' => $this->_format_opentime($value['opentime'],$value['opentimesetting']),
                'store_phone'     => $value['phone'],
                'store_address'   => $value['address'],
                'store_longitude' => $value['longitude'],
                'store_latitude'  => $value['latitude'],
            );
        }

        return $data;
    }

    function formateDateCopy($date){
        $str = "";
        $year = substr($date,0,4);
        $dat  = substr($date,4,2);
        $day  = substr($date,6,2);
        return $dat."-".$day;
    }

    function week($w,$c=''){
        if($c==""){
            $d=date("w")+$w;
            if($d>7){
                $d=$d-7;
            }
        }else{
            $d=$c;
        }
        if($d==0){
            return "周日";
        }else if($d==1){
            return "周一";
        }else if($d==2){
            return "周二";
        }else if($d==3){
            return "周三";
        }else if($d==4){
            return "周四";
        }else if($d==5){
            return "周五";
        }else if($d==6){
            return "周六";
        }else if($d==7){
            return "周日";
        }

    }
    /*
    *配送时间计算
    */
    public function get_o2o_send_time($store,$is_8324_tuan=false){
      $now = date('Y-m-d H:i:s');
      if($now >= '2016-02-06 08:00:00' && $now < '2016-02-14 00:00:00'){
          $send_result = array();
          $send_result['shtime'] = '20160214';
          $send_result['skey'] = 'ampm';
          $send_result['stime'] = "02-14(周日)配送";
          return $send_result;
      }
      if($is_8324_tuan == true){
          $send_result = array();
          for ($i=1; $i < 7; $i++) {
              if(date('w',strtotime("+{$i} day")) != 0 && date('w',strtotime("+{$i} day")) != 6){
                  $send_result['shtime'] = date('Ymd',strtotime("+{$i} day"));
                  break;
              }
          }
          // if(0<date('G') && date('G')<12){
          //     $send_result['skey'] = 'am';
          //     $send_result['stime'] = $this->formateDateCopy($send_result['shtime'])."上午配送";
          // }else{
          $send_result['skey'] = 'ampm';
          $send_result['stime'] = $this->formateDateCopy($send_result['shtime'])."(".$this->week($i).")"."配送";
          //}
          return $send_result;
      }
      if ($opentime=@unserialize($store['opentime'])) {
        $date = getdate();
        // echo '<pre>';print_r($opentime);exit;
        $now = time();
        if ($store['opentimesetting'] == 1) {
          $am = mktime($opentime['AM']['h'],$opentime['AM']['m']);
          $pm = mktime($opentime['PM']['h'],$opentime['PM']['m']);


          if ($now < $am) {
              $send_result = array("shtime"=>date("Ymd"),"stime"=>'今天上午配送','skey'=>'am');
              return $send_result;
          }
          if ($now > $pm) {
              $send_date = date("Ymd",strtotime("+1 day"));
              $send_date_str = $this->formateDateCopy($send_date)."(".$this->week(1).")"."配送";
              $send_result = array("shtime"=>$send_date,"stime"=>$send_date_str,'skey'=>'ampm');
              return $send_result;
          }

        } elseif ($store['opentimesetting'] == 2) {
          // echo '<pre>';print_r($opentime);exit;
          // if (!$opentime[$date['wday']]) {
          //   for($i=1;$i<7;$i++){
          //     $new_day = $date['wday']+$i;
          //     if($new_day>7){
          //       $new_day=$new_day-7;
          //     }
          //     if($opentime[$new_day]){
          //       $send_date = date("Ymd",strtotime("+".$i." day"));
          //       $send_date_str = $this->formateDateCopy($send_date)."(".$this->week($i).")"."配送";
          //       $send_result = array("shtime"=>$send_date,"stime"=>$send_date_str,'skey'=>'am');
          //       return $send_result;
          //     }
          //   }
          //   $this->_error = '谢谢光临!门店今日不营业';
          //   return false;
          // }


            $am = mktime($opentime[$date['wday']]['AM']['h'],$opentime[$date['wday']]['AM']['m']);
            $pm = mktime($opentime[$date['wday']]['PM']['h'],$opentime[$date['wday']]['PM']['m']);

            if ($now < $am) {
              $send_result = array("shtime"=>date("Ymd"),"stime"=>'今天上午配送','skey'=>'am');
              return $send_result;
            }
            if ($now > $pm) {
              for($i=1;$i<7;$i++){
                $new_day = $date['wday']+$i;
                if($new_day>=7){
                  $new_day=$new_day-7;
                }
                if($opentime[$new_day]){
                  $send_date = date("Ymd",strtotime("+".$i." day"));
                  $send_date_str = $this->formateDateCopy($send_date)."(".$this->week($i).")"."配送";
                  $send_result = array("shtime"=>$send_date,"stime"=>$send_date_str,'skey'=>'ampm');
                  return $send_result;
                }
              }
            }

        }
      }
      $send_result = array("shtime"=>date("Ymd"),"stime"=> $this->getDefaultSendTime($store['id'], 'time') . '小时之内配送');
      return $send_result;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function _check_store($store)
    {
      // if($store['id']==89 || $store['id']==25){
      //   return true;
      // }
      // $this->ci->load->library('fdaylog');
      // $db_log = $this->ci->load->database('db_log', TRUE);
      // $this->ci->fdaylog->add($db_log,'check_store',$store);
      if ($store['isopen'] != 1) {
          $this->_error = '谢谢光临!门店尚未营业';
          return false;
      }

      // $bj_store = array(5,15,24,25,32,33,88,89,102,107,110,111,114,65,70,11,20,62,64,66,104,109,18,23,9,16,99,21,22,61,67,100,105,26,113,31,55,58,29,69,101,106,60,72,103,108,59,17,71,98,92,112,37,51,34,35,36,48,49,53,54,93,94,38,50,52,105,106,107,108,109,115,119,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,116);

      // if ($opentime=@unserialize($store['opentime'])) {
      //   $date = getdate();
      //   $now = time();
      //   if ($store['opentimesetting'] == 1) {
      //     $am = mktime($opentime['AM']['h'],$opentime['AM']['m']);
      //     $pm = mktime($opentime['PM']['h'],$opentime['PM']['m']);

      //     if(!in_array($store['id'], $bj_store)){
      //     if ($now < $am) {
      //         $this->_error = '商品'.date('H:00',$am).'点开卖';
      //         return false;
      //     }
      //     if ($now > $pm) {
      //         $this->_error = '谢谢光临!今日已打烊,明日请早';
      //         return false;
      //     }
      //     }

      //   } elseif ($store['opentimesetting'] == 2) {
      //     if(!in_array($store['id'], $bj_store)){
      //       if (!$opentime[$date['wday']]) {
      //         $this->_error = '谢谢光临!门店今日不营业';
      //         return false;
      //       }
      //     }


      //       $am = mktime($opentime[$date['wday']]['AM']['h'],$opentime[$date['wday']]['AM']['m']);
      //       $pm = mktime($opentime[$date['wday']]['PM']['h'],$opentime[$date['wday']]['PM']['m']);
      //       if(!in_array($store['id'], $bj_store)){
      //       if ($now < $am) {
      //         $this->_error = '商品'.date('H:00',$am).'点开卖';
      //         return false;
      //       }
      //       if ($now > $pm) {
      //         $this->_error = '谢谢光临!今日已打烊,明日请早';
      //         return false;
      //       }
      //     }

      //   }
      // }

      return true;
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    private function _format_opentime($opentime,$setting)
    {
      $ot = '';
      if ($opentime=@unserialize($opentime)) {

        if ($setting == 1) {
          $ot = $opentime['AM']['h'].':'.$opentime['AM']['m'].'~'.$opentime['PM']['h'].':'.$opentime['PM']['m'];
        } elseif($setting == 2) {
          foreach ( $opentime as $w => $v) {
              switch ($w) {
                  case '1':
                      $ot .= '周一 '.$v['AM']['h'].':'.$v['AM']['m'].' AM 至 '.$v['PM']['h'].':'.$v['PM']['m'].' PM；';
                      break;
                  case '2':
                      $ot .= '周二 '.$v['AM']['h'].':'.$v['AM']['m'].' AM 至 '.$v['PM']['h'].':'.$v['PM']['m'].' PM；';
                      break;
                  case '3':
                      $ot .= '周三 '.$v['AM']['h'].':'.$v['AM']['m'].' AM 至 '.$v['PM']['h'].':'.$v['PM']['m'].' PM；';
                      break;
                  case '4':
                      $ot .= '周四 '.$v['AM']['h'].':'.$v['AM']['m'].' AM 至 '.$v['PM']['h'].':'.$v['PM']['m'].' PM；';
                      break;
                  case '5':
                      $ot .= '周五 '.$v['AM']['h'].':'.$v['AM']['m'].' AM 至 '.$v['PM']['h'].':'.$v['PM']['m'].' PM；';
                      break;
                  case '6':
                      $ot .= '周六 '.$v['AM']['h'].':'.$v['AM']['m'].' AM 至 '.$v['PM']['h'].':'.$v['PM']['m'].' PM；';
                      break;
                  case '0':
                      $ot .= '周日 '.$v['AM']['h'].':'.$v['AM']['m'].' AM 至 '.$v['PM']['h'].':'.$v['PM']['m'].' PM；';
                      break;
              }
          }
        }


        return $ot;
      }

      return '';
    }

    /**
     * 获取常用自提门店
     *
     * @param Int $city_id
     * @return void
     * @author
     **/
    public function commonstores($city_id)
    {
        $this->ci->load->library('login');

        $data = array();

        if ($this->ci->login->is_login()) {
            $uid = $this->ci->login->get_uid();

            $this->ci->load->model('o2o_order_extra_model');

            $rows = $this->ci->o2o_order_extra_model->commonstores($uid,$city_id,0);

            foreach ($rows as $key => $value) {
                $open = $this->_check_store($value);
                if (!$open) {
                  continue;
                }

                $data[] = array(
                    'store_id'        => $value['id'],
                    'store_name'      => $value['name'],
                    'store_open_time' => $this->_format_opentime($value['opentime'],$value['opentimesetting']),
                    'store_phone'     => $value['phone'],
                    'store_address'   => $value['address'],
                    'store_longitude' => $value['longitude'],
                    'store_latitude'  => $value['latitude'],
                );
            }
        }

        return $data;
    }



    /**
     * 获取历史地址
     *
     * @return void
     * @author
     **/
    public function addresslist()
    {
        $data = array();

        $this->ci->load->library('login');

        $uid = $this->ci->login->get_uid();

        $this->ci->load->model('o2o_order_extra_model');
        $o2o_orders = $this->ci->o2o_order_extra_model->addresslist($uid);

        if (!$o2o_orders) return array();

        $this->ci->load->model('o2o_region_model');
        $this->ci->load->model('order_address_model');
        $this->ci->load->model('o2o_store_building_model');
        $this->ci->load->model('o2o_store_model');
        foreach ($o2o_orders as $o2o_order) {
            $d = array();

            $parents = $this->ci->o2o_region_model->getParents($o2o_order['building_id']);

            foreach ((array) $parents as $key => $value) {
                $value['name'] = preg_replace('/（.*）/', '', $value['name']);
                $regionkey = $this->ci->o2o_region_model->attr[$value['attr']];

                $d[$regionkey]['id'] = $value['id'];
                $d[$regionkey]['name'] = $value['name'];
            }

            // $order_address = $this->ci->order_address_model->dump(array('order_id'=>$o2o_order['order_id']));

            $d['address'] = $o2o_order['address'];
            $d['mobile']  = $o2o_order['mobile'];
            $d['name']    = $o2o_order['name'];

            if ($d['building']['id']) {
              $store_building = $this->ci->o2o_store_building_model->dump(array('building_id'=>$d['building']['id']));
              $store = $this->ci->o2o_store_model->dump(array('id'=>$store_building['store_id']));
              $store_buildings = $this->ci->o2o_store_building_model->getList('distinct(store_id)',array('building_id'=>$d['building']['id']));
              $store_ids = array();
              foreach ($store_buildings as $s_id) {
                  $store_ids[] = $s_id['store_id'];
              }
              $d['store']['id']        = $store['id'];
              $d['store']['name']      = $store['name'];
              $d['store']['opentime']  = $this->_format_opentime($store['opentime'],$store['opentimesetting']);
              $d['store']['phone']     = $store['phone'];
              $d['store']['address']   = $store['address'];
              $d['store']['longitude'] = $store['longitude'];
              $d['store']['latitude']  = $store['latitude'];
              $d['stores']  = $store_ids;
            }

            $data[] = $d;
        }

        return $data;
    }

    public function addresslist_v2(){
        $data = array();
        $this->ci->load->library('login');
        $uid = $this->ci->login->get_uid();
        if(empty($uid)) return $data;
        $this->ci->load->model('o2o_order_extra_model');
        $o2o_orders = $this->ci->o2o_order_extra_model->getOpenStoreOrders($uid);
        if (!$o2o_orders) return $data;
        $this->ci->load->model('order_address_model');
        $this->ci->load->model('o2o_store_model');
        $this->ci->load->model('area_model');
        foreach ($o2o_orders as $o2o_order) {
            $d = array();
            $d['name']    = $o2o_order['name'];
            $d['mobile']  = $o2o_order['mobile'];
            $d['addressId'] = $o2o_order['id'];
            $d['addressName'] = $o2o_order['address_name'];
            $d['address'] = $o2o_order['pre_address'];
            $d['addressRemark'] = $o2o_order['pro_address'];
            $d['lonlatForO2O'] = $o2o_order['lonlat']?$o2o_order['lonlat']:'';

            $province = $this->ci->area_model->dump(array('id'=>$o2o_order['province']));
            $city = $this->ci->area_model->dump(array('id'=>$o2o_order['city']));
            $area = $this->ci->area_model->dump(array('id'=>$o2o_order['area']));
            $d['province'] = $province['name'];
            $d['city'] = $city['name'];
            $d['district'] = $area['name'];


            // $d['province']['id'] = $value['province'];
            // $d['province']['name'] = $value['province_name'];
            // $d['city']['id'] = $value['city'];
            // $d['city']['name'] = $value['city_name'];
            // $d['area']['id'] = $value['area'];
            // $d['area']['name'] = $value['area_name'];
            //$store = $this->ci->o2o_store_model->dump(array('id'=>$o2o_order['store_id']));
            $d['storeId']        = $o2o_order['store_id'];
            // $d['store']['name']      = $store['name'];
            // $d['store']['opentime']  = $this->_format_opentime($store['opentime'],$store['opentimesetting']);
            // $d['store']['phone']     = $store['phone'];
            // $d['store']['address']   = $store['address'];
            // $d['store']['longitude'] = $store['longitude'];
            // $d['store']['latitude']  = $store['latitude'];
            // $d['stores']  = array($store['id']);
            $data[] = $d;
        }
        return $data;
    }

    /**
     * 门店商品
     *
     * @return void
     * @author
     **/
    public function storeproducts($store_id,$product_type = 0,$product_ids=array())
    {


        $data = array();
        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
        //     if(!$this->ci->memcached){
        //         $this->ci->load->library('memcached');
        //     }
        //     $mem_key = $this->cache_prefix."store_p_".$store_id."_".$product_type;
        //     $data = $this->ci->memcached->get($mem_key);
        //     if($data){
        //         return $data;
        //     }
        // }
        $this->ci->load->model('o2o_store_goods_model');

        $where = '';
        $order_by = " ORDER BY `stock`=0,`order` desc";
        if($product_ids && is_array($product_ids)){
            $p_ids = implode(',', $product_ids);
            if($p_ids && $p_ids != ','){
               $where = ' and product_id in('.$p_ids.') ';
               $order_by = " ORDER BY `stock`=0 desc,find_in_set(product_id,'".$p_ids."') asc";
            }
        }
        //$store_goods = $this->ci->o2o_store_goods_model->getList('*',array('store_id'=>$store_id),0,-1,'stock=0 , order desc');
        $sql = "SELECT * FROM `ttgy_o2o_store_goods` WHERE `store_id` =  ".$store_id.$where.$order_by;
        $store_goods = $this->ci->db->query($sql)->result_array();

        $this->ci->load->library('login');
        $uid = $this->ci->login->get_uid();
        $store_goods = $this->_initStoreGoods($store_goods, $uid);
        if (!$store_goods) return array();

        $this->ci->load->model('product_model');
        // $pro_sale_first = $this->getProSaleRules();
        // $active_pro_id = array();
        // if(!empty($pro_sale_first)){
        //   foreach ($pro_sale_first as $key => $value) {
        //     $product_id_arr = explode(',', $value['product_id']);
        //     $content = unserialize($value['content']);
        //     foreach ($product_id_arr as $k => $v) {
        //       $active_pro_id[$v] = $content['cut_money'];
        //     }
        //   }
        // }

        foreach ($store_goods as $key => $value) {
            $product = $this->ci->product_model->getProductSkus($value['product_id'],0,$product_type);
            if(empty($product) || $product['is_tuan'] == 1) continue;

            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $product['photo'] = $templateImages['main']['image'];
                    $product['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

            foreach ((array) $product['skus'] as $k => $v) {
              // if(!empty($active_pro_id)){
              //   if(isset($active_pro_id[$v['product_id']])){
              //     $v['price'] -= $active_pro_id[$v['product_id']];
              //   }
              // }
                $data[] = array(
                    'id'           => $v['id'],
                    'product_name' => $product['product_name'],
                    'thum_photo'   => PIC_URL.$product['thum_photo'],
                    'photo'        => PIC_URL.$product['photo'],
                    'price'        => $v['price'],
                    'stock'        => $value['stock'],
                    'volume'       => $v['volume'],
                    'price_id'     => $v['id'],
                    'product_no'   => $v['product_no'],
                    'product_id'   => $v['product_id'],
                    'old_price'    => ($v['old_price']>0)?$v['old_price']:'',
                    'ptype'        => 1, // 产品类型 1:正常产品 2:券卡产品 3:特价商品
                    'buy_limit'    => $value['qtylimit'], // 限购数据
                    'store_id'     => $store_id,
                );
            }
        }
        // if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
        //     if(!$this->ci->memcached){
        //         $this->ci->load->library('memcached');
        //     }
        //     $mem_key = $this->cache_prefix."store_p_".$store_id."_".$product_type;
        //     $this->ci->memcached->set($mem_key,$data,600);
        // }
        return $data;
    }

    private function _initStoreGoods($store_goods, $uid){
      if(!empty($store_goods)){
        $this->ci->load->model('o2o_ainvate_model');
         $ainvate_res = $this->ci->o2o_ainvate_model->dump(array('uid'=>$uid),'id');
          foreach($store_goods as $key=>$val){
              if( (empty($uid) || empty($ainvate_res)) &&in_array($val['product_id'], $this->_zero_product_id)){
                  unset($store_goods[$key]);
              }
          }
      }
      return $store_goods;
    }


    public function lastAddress()
    {
      $data = array();

      // 获取最近一次的收货地址
      $this->ci->load->library('login');
      $uid = $this->ci->login->get_uid();

      if (!$uid) {return $data;}

      $this->ci->load->model('o2o_order_extra_model');
      $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid),0,1,'id desc');

      if (!$o2o_order) return $data;

      $o2o_order = array_pop($o2o_order);
      $this->ci->load->model('o2o_region_model');
      $attr = $this->ci->o2o_region_model->attr;
      $parents = $this->ci->o2o_region_model->getParents($o2o_order['building_id']);

      foreach ($parents as $p) {
        $k = $attr[$p['attr']];

        $data[$k]['id']   = $p['id'];
        $data[$k]['name'] = $p['name'];
      }

      $data['address'] = $o2o_order['address'];

      $this->ci->load->model('order_address_model');
      $order_address = $this->ci->order_address_model->dump(array('order_id'=>$o2o_order['order_id']));

      $data['name']      = $order_address['name'];
      $data['mobile']    = $order_address['mobile'];
      $data['telephone'] = $order_address['telephone'];

      $this->ci->load->model('o2o_store_model');
      $store = $this->ci->o2o_store_model->dump(array('id'=>$o2o_order['store_id']));
      $data['store']['id']        = $store['id'];
      $data['store']['name']      = $store['name'];
      // $data['store']['opentime']  = $this->_format_opentime($store['opentime'],$store['opentimesetting']);
      // $data['store']['phone']     = $store['phone'];
      // $data['store']['address']   = $store['address'];
      // $data['store']['longitude'] = $store['longitude'];
      // $data['store']['latitude']  = $store['latitude'];

      return $data;
    }

    /**
     * 获取最后一次的收货地址
     *
     * @return void
     * @author
     **/
    private function getLastOrder()
    {
        $data = array();

        // 获取最近一次的收货地址
        $this->ci->load->library('login');
        $uid = $this->ci->login->get_uid();

        if (!$uid) {return $data;}

        $this->ci->load->model('o2o_order_extra_model');
        $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid),0,1,'id desc');

        if ($o2o_order) {
            $o2o_order = array_pop($o2o_order);
            $this->ci->load->model('order_model');
            $order = $this->ci->order_model->dump(array('id'=>$o2o_order['order_id']),'address_id');

            $this->ci->load->model('order_address_model');
            $order_address = $this->ci->order_address_model->dump(array('order_id'=>$o2o_order['order_id']));

            $this->ci->load->model('area_model');
            $province = $this->ci->area_model->dump(array('id'=>$order_address['province']));
            $city = $this->ci->area_model->dump(array('id'=>$order_address['city']));
            $area = $this->ci->area_model->dump(array('id'=>$order_address['area']));

            $this->ci->load->model('o2o_region_model');
            $building = $this->ci->o2o_region_model->dump(array('id'=>$o2o_order['building_id']));
            $bszone = $this->ci->o2o_region_model->dump(array('id'=>$building['pid']));

            $data['order_address'] = array(
                'id' => $order['address_id'],
                'name' => $order_address['name'],
                'province' => array(
                  'id' => $province['id'],
                  'name' => $province['name'],
                ),
                'city' => array(
                  'id' => $city['id'],
                  'name' => $city['name'],
                ),
                'area' => array(
                  'id' => $area['id'],
                  'name' => $area['name'],
                ),
                'bszone' => array(
                  'id' => $bszone['id'],
                  'name' => $bszone['name'],
                ),
                'building' => array(
                  'id' => $building['id'],
                  'name' => $building['name'],
                ),
                'address' => $o2o_order['address'],
                'telephone' => $order_address['telephone'],
                'mobile' => $order_address['mobile'],
            );

            $data['pay_parent_id'] = '';
            $data['pay_id']        = '';
            $data['pay_name']      = '';

            // 除东方支付和线下支付外
            if ($order['pay_parent_id'] != 10) {
              $data['pay_parent_id'] = $order['pay_parent_id'];
              $data['pay_id']        = $order['pay_id'];
              $data['pay_name']      = $order['pay_name'];
            }

            $data['has_invoice'] = 0;
        }

        return $data;
    }


    /**
     * 获取上一次的收货信息
     *
     * @return void
     * @author
     **/
    public function getLastOrderInfo($building_id)
    {
        $data = array();

        $this->ci->load->library('login');
        $uid = $this->ci->login->get_uid();
        if(empty($uid)) return $data;
        $this->ci->load->model('o2o_order_extra_model');
        $o2o_order = array();
        if($building_id){
            $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid,'building_id'=>$building_id),0,1,'id desc');
            $o2o_order = $o2o_order[0];
            $no_building = 0;
        }else{
            $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid),0,1,'id desc');
            $o2o_order = $o2o_order[0];
            $no_building = 1;
        }
        // if(!$o2o_order){
        //     $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid),0,1,'id desc');
        //     $o2o_order = $o2o_order[0];
        // }
        if($o2o_order){
            $this->ci->load->model('order_address_model');
            $order_address = $this->ci->order_address_model->dump(array('order_id'=>$o2o_order['order_id']));

            //$data['order_address']['address'] = $o2o_order['address'];
            //$data['order_address']['receiver_name']    = $order_address['name'];
            //$data['order_address']['receiver_mobile']  = $order_address['mobile'];

            $this->ci->load->model('area_model');
            $province = $this->ci->area_model->dump(array('id'=>$order_address['province']));
            //$data['order_address']['province'] = $province['name'];
            //$data['order_address']['provinceId'] = $province['id'];
            $city = $this->ci->area_model->dump(array('id'=>$order_address['city']));
            //$data['order_address']['city'] = $city['name'];
            //$data['order_address']['cityId'] = $city['id'];
            $area = $this->ci->area_model->dump(array('id'=>$order_address['area']));
            //$data['order_address']['area'] = $area['name'];
            //$data['order_address']['areaId'] = $area['id'];

            $this->ci->load->model('o2o_region_model');
            $building = $this->ci->o2o_region_model->dump(array('id'=>$o2o_order['building_id']));
            //$data['order_address']['building'] = $building['name'];
            //$data['order_address']['building_id'] = $building['id'];

            $this->ci->load->model('o2o_store_model');
            $store = $this->ci->o2o_store_model->dump(array('id'=>$o2o_order['store_id']));
            //$data['order_address']['store'] = $store['name'];
            //$data['order_address']['store_id'] = $o2o_order['store_id'];

            $this->ci->load->model('o2o_store_building_model');
            $store_ids = $this->ci->o2o_store_building_model->getList('store_id',array('building_id'=>$o2o_order['building_id']));
            if (!$store_ids) {
                return $data;
            }
            $s_id = array();
            foreach ($store_ids as $value) {
                $s_id[] = $value['store_id'];
            }

            $data['order_address'] = array(
                'name' => $order_address['name'],
                'mobile' => $order_address['mobile'],
                'address' => $o2o_order['address'],
                'province' => array(
                  'id' => $province['id'],
                  'name' => $province['name'],
                ),
                'city' => array(
                  'id' => $city['id'],
                  'name' => $city['name'],
                ),
                'area' => array(
                  'id' => $area['id'],
                  'name' => $area['name'],
                ),
                'building' => array(
                  'id' => $building['id'],
                  'name' => $building['name'],
                ),
                'store' => array(
                  'id' => $store['id'],
                  'name' => $store['name'],
                  ),
                'stores' => $s_id,
            );
            $data['no_building'] = $no_building;
        }
        return $data;
    }

    public function getLastOrderInfo_v2($uid,$store_id=0){
        $data = array();
        $this->ci->load->model('o2o_order_extra_model');
        $o2o_order = $this->ci->o2o_order_extra_model->getLastOpenStoreOrder($uid,$store_id);
        if($o2o_order){
            $this->ci->load->model('order_address_model');
            //$order_address = $this->ci->order_address_model->dump(array('order_id'=>$o2o_order['order_id']));
            $this->ci->load->model('area_model');
            $province = $this->ci->area_model->dump(array('id'=>$o2o_order['province']));
            $city = $this->ci->area_model->dump(array('id'=>$o2o_order['city']));
            $area = $this->ci->area_model->dump(array('id'=>$o2o_order['area']));
            $this->ci->load->model('o2o_store_model');
            $store = $this->ci->o2o_store_model->dump(array('id'=>$o2o_order['store_id']));
            $s_id = array($o2o_order['store_id']);
            $data['order_address'] = array(
                'addressId' => $o2o_order['id'],
                'name' => $o2o_order['name'],
                'mobile' => $o2o_order['mobile'],
                'addressName' => $o2o_order['address_name'],
                'address' => $o2o_order['pre_address'],
                'addressRemark' => $o2o_order['pro_address'],
                'lonlatForO2O' => $o2o_order['lonlat']?$o2o_order['lonlat']:'',
                'province' => $province['name'],
                'city' => $city['name'],
                'district' => $area['name'],
                // 'province' => array(
                //   'id' => $province['id'],
                //   'name' => $province['name'],
                // ),
                // 'city' => array(
                //   'id' => $city['id'],
                //   'name' => $city['name'],
                // ),
                // 'area' => array(
                //   'id' => $area['id'],
                //   'name' => $area['name'],
                // ),
                // 'building' => array(
                //   'id' => 0,
                //   'name' => '',
                // ),
                'storeId' => $store['id'],
                //'stores' => $s_id,
            );
        }
        return $data;
    }

    private function _order_address_init($building_id,$order_address)
    {
      if($building_id){
          $this->ci->load->model('o2o_region_model');
          $parents = $this->ci->o2o_region_model->getParents($building_id);

          $attr = $this->ci->o2o_region_model->attr;

          $needArea = array('province','city','area','bszone','building');
          foreach ($parents as $value) {
            $k = $this->ci->o2o_region_model->attr[$value['attr']];

            $this->_orderinit['order_address'][$k]['id'] = $value['id'];
            $this->_orderinit['order_address'][$k]['name'] = $value['name'];

            if (false !== $nk=array_search($k, $needArea)) {
              unset($needArea[$nk]);
            }
          }

          if ($needArea) {
            $this->_error = '无法定位到所在楼宇，请联系客服人员';
            return false;
          }
      }


      if ($order_address) {

        $this->_orderinit['order_address']['name']    = $order_address['name'];
        $this->_orderinit['order_address']['mobile']  = $order_address['mobile'];
        $this->_orderinit['order_address']['address'] = $order_address['address'];
        $this->_orderinit['order_address']['address_name'] = $order_address['address_name'];
        $this->_orderinit['order_address']['pre_address'] = $order_address['pre_address'];
        $this->_orderinit['order_address']['pro_address'] = $order_address['pro_address'];
        $this->_orderinit['order_address']['lonlat'] = $order_address['lonlat'];
        $this->_orderinit['order_address']['address_id'] = $order_address['address_id'];
        $o2o_region = $this->ci->config->item('o2o_region_to_ttgy');
        if($order_address['province_name'] && $order_address['city_name'] && $order_address['area_name']){
            if(isset($o2o_region[$order_address['province_name']]['son'][$order_address['city_name']]['son'][$order_address['area_name']])){
                $this->_orderinit['order_address']['area'] = $o2o_region[$order_address['province_name']]['son'][$order_address['city_name']]['son'][$order_address['area_name']]['area_id'];
                $this->_orderinit['order_address']['area_name'] = $o2o_region[$order_address['province_name']]['son'][$order_address['city_name']]['son'][$order_address['area_name']]['name'];
                $this->_orderinit['order_address']['city'] = $o2o_region[$order_address['province_name']]['son'][$order_address['city_name']]['area_id'];
                $this->_orderinit['order_address']['city_name'] = $o2o_region[$order_address['province_name']]['son'][$order_address['city_name']]['name'];
                $this->_orderinit['order_address']['province'] = $o2o_region[$order_address['province_name']]['area_id'];
                $this->_orderinit['order_address']['province_name'] = $o2o_region[$order_address['province_name']]['name'];
            }
        }

      } elseif(version_compare($this->_version, '4.1.0') < 0) {
        $this->ci->load->library('login');
        $uid = $this->ci->login->get_uid();

        $this->ci->load->model('o2o_order_extra_model');
        if($building_id){
            $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid,'building_id'=>$building_id),0,1,'id desc');
        }else{
            $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid),0,1,'id desc');
        }

        if ($o2o_order) {
            $o2o_order = $o2o_order[0];
            $this->ci->load->model('order_address_model');
            $order_address = $this->ci->order_address_model->dump(array('order_id'=>$o2o_order['order_id']));

            $this->_orderinit['order_address']['name']    = $order_address['name'];
            $this->_orderinit['order_address']['mobile']  = $order_address['mobile'];
            $this->_orderinit['order_address']['address'] = $o2o_order['address'];
        }
      }

      return true;
    }

    private function _order_pay_init($building_id,$payway,$api_version='1',$store_id=0)
    {
      $pay_array  =  $this->ci->config->item("pay_array");
      if ($payway) {


        if (!$pay_array[$payway['pay_parent_id']]) {
          $this->_error = '所选支付方式暂不支持';
          return false;
        }

        $sons = $pay_array[$payway['pay_parent_id']]['son'];
        if ($sons) {
          if ($payway['pay_parent_id']==3) $payway['pay_id'] = str_pad($payway['pay_id'],5,'0',STR_PAD_LEFT);

          if (!$sons[$payway['pay_id']]) {
            $this->_error = '所选支付方式暂不支持';
            return false;
          }
        }

        if ($payway['pay_parent_id'] == 4) {
          $this->_error = '所选支付方式暂不支持';
          return false;
        }

        // $pay_name = array();

        if ($pay_array[$payway['pay_parent_id']]) {
          $pay_name = $pay_array[$payway['pay_parent_id']]['name'];
        }

        if ($pay_array[$payway['pay_parent_id']]['son'][$payway['pay_id']]) {
          $pay_name = $pay_array[$payway['pay_parent_id']]['son'][$payway['pay_id']];
        }

        $payway['pay_id'] = $payway['pay_id'] ? $payway['pay_id'] : 0;

        $this->_orderinit['pay_parent_id'] = $payway['pay_parent_id'];
        $this->_orderinit['pay_id']        = $payway['pay_id'];
        $this->_orderinit['pay_name']      = $pay_name;
        $this->_orderinit['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$payway['pay_parent_id'].'_'.$payway['pay_id'].'.png';
      } else {
        $cart = $this->getCartCache();
        $cart['items'] = array_values($cart['items']);
        $is_8324_tuan = false;
        foreach ($cart['items'] as $item) {
            if(in_array($item['product_id'], $this->_o2oTuan_productids)){
                $is_8324_tuan = true;
                break;
            }
        }
        if($is_8324_tuan == true){
            $this->_orderinit['pay_parent_id'] = 7;
            $this->_orderinit['pay_id']        = 0;
            $this->_orderinit['pay_name']      = '微信支付';
            $this->_orderinit['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/7_0.png';
        }else{
            $this->ci->load->library('login');
            $uid = $this->ci->login->get_uid();

            $this->ci->load->model('o2o_order_extra_model');
            if($building_id){
                $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid,'building_id'=>$building_id),0,1,'id desc');
            }elseif($store_id){
                $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid,'store_id'=>$store_id),0,1,'id desc');
            }else{
                $o2o_order = $this->ci->o2o_order_extra_model->getList('*',array('uid'=>$uid),0,1,'id desc');
            }

            if ($o2o_order) {
              $o2o_order = array_pop($o2o_order);
              $this->ci->load->model('order_model');

              $order = $this->ci->order_model->dump(array('id'=>$o2o_order['order_id']),'pay_parent_id,pay_id,pay_name');
              $reset = false;
              $sons = $pay_array[$order['pay_parent_id']]['son'];
              if ($order['pay_parent_id']==3) $order['pay_id'] = str_pad($order['pay_id'],5,'0',STR_PAD_LEFT);

              if (!$pay_array[$order['pay_parent_id']]) {
                  $reset = true;
              }
              if($sons && !$sons[$order['pay_id']]){
                  $reset = true;
              }
              if($reset){
                  $this->_orderinit['pay_parent_id'] = 7;
                  $this->_orderinit['pay_id']        = 0;
                  $this->_orderinit['pay_name']      = '微信支付';
                  $this->_orderinit['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/7_0.png';
              }else{
                  $order['pay_id'] = $order['pay_id'] ? $order['pay_id'] : 0;
                  $this->_orderinit['pay_parent_id'] = $order['pay_parent_id'];
                  $this->_orderinit['pay_id'] = $order['pay_id'];
                  $this->_orderinit['pay_name'] = $order['pay_name'];
                  $this->_orderinit['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$order['pay_parent_id'].'_'.$order['pay_id'].'.png';
              }
            }
        }
      }

      if ($this->_orderinit['pay_parent_id'] == 5) {
        if($this->_version>='3.4.0'){
          $this->_orderinit['pay_parent_id'] = 7;
          $this->_orderinit['pay_id']        = 0;
          $this->_orderinit['pay_name']      = '微信支付';
          $this->_orderinit['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/7_0.png';
        }else{
          $this->_orderinit['need_authen_code'] = 1;
          // $this->_orderinit['need_send_code'] = 1;
        }
      }

      //new user
      if(empty($this->_orderinit['pay_parent_id']))
      {
          $this->_orderinit['pay_parent_id'] = 7;
          $this->_orderinit['pay_id']        = 0;
          $this->_orderinit['pay_name']      = '微信支付';
          $this->_orderinit['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/7_0.png';
      }

      return true;
    }

    /**
     * 初始化订单总价
     *
     * @return void
     * @author
     **/
    private function _order_total_init()
    {
      $this->_orderinit['money'] = $this->_orderinit['goods_money']
                                    + $this->_orderinit['method_money']
                                    - $this->_orderinit['jf_money']
                                    - $this->_orderinit['card_money']
                                    - $this->_orderinit['pay_discount'];

      if ($this->_orderinit['money'] < 0) {
        $this->_error = '订单金额异常，请重新挑选商品试试';
        return false;
      }

      return true;
    }

    /**
     * 初始化会员
     *
     * @return void
     * @author
     **/
    private function _user_init()
    {
      $this->ci->load->library('login');
      $uid = $this->ci->login->get_uid();

      $this->ci->load->model('user_model');
      $user = $this->ci->user_model->getUser($uid);

      if (!$user) {
        $this->_error = '帐号异常，请重新登录试试';
        return false;
      }

      $this->_orderinit['user_mobile'] = $user['mobile'] ? $user['mobile'] : '';
      $this->_orderinit['user_money']  = $user['money'] ? $user['money'] : '0';
      $this->_orderinit['user_coupon_num'] = $this->ci->user_model->getCouponNum($uid);

      return true;
    }

    /**
     * 积分初始化
     *
     * @return void
     * @author
     **/
    private function _order_jfmoney_init($jf_money)
    {
      $this->_orderinit['jf_money'] = 0;
      $this->_orderinit['use_jf'] = 0;

      if ($jf_money>0) {
        if ($this->_orderinit['can_use_jf'] != 1) {
          $this->_error = '所选商品包含'.$this->_orderinit['jf_limit_pro'].'将不能使用积分';
          return false;
        }

        if ($jf_money > $this->_orderinit['order_jf_limit']) {
          $this->_error = '您最多可使用'.$this->_orderinit['order_jf_limit'].'元积分';
          return false;
        }

        $this->_orderinit['jf_money'] = $jf_money;
        $this->_orderinit['use_jf'] = $jf_money * 100;

      }
      return true;
    }

    /**
     * 优惠券初始化
     *
     * @return void
     * @author
     **/
    private function _order_card_init($card_number)
    {
      $this->_orderinit['use_card'] = '';
      $this->_orderinit['card_money'] = '';

      if ($card_number) {
        $this->ci->load->library('login');
        $uid = $this->ci->login->get_uid();

        $this->ci->load->library('terminal');
        $source = $this->ci->terminal->get_source();

        $this->ci->load->model('card_model');
        $card_info = $this->ci->card_model->get_card_info($card_number);

        $can_use = $this->ci->card_model->card_can_use($card_info,$uid,$this->_orderinit['cart_info']['goods_amount'],$source,$this->_orderinit['jf_money'],$this->_orderinit['pay_discount'],1);

        if ($can_use[0] == 0) {
          $this->_error = $can_use[1];
          return false;
        }

        $product_list=explode(",",$card_info['product_id']);
        $productnum=$salenum=$card_sales_money=0;
        if($card_info['product_id']){
            foreach ($this->_orderinit['cart_info']['items'] as $val){

                for($i=0;$i<count($product_list);$i++){
                    if(trim($product_list[$i])==$val['product_id']){
                        if($card_info['can_use_onemore_time']=='true'){//多次劵
                            if($card_info['maketing']=='5'){//商品减免
                                if($card_info['can_sales_more_times'] =='true'){//多买多减
                                    $card_sales_money += $card_info['card_money']*$val['qty'];//商品种类*每种商品的数量*抵扣金额
                                }else{
                                    $card_sales_money = $card_info['card_money'];
                                }
                            }elseif($card_info['maketing']=='6'){//商品打折
                                if($card_info['can_sales_more_times'] =='true'){//多买多减
                                    $card_sales_money += round((1-$card_info['card_discount'])*$val['qty']*$val['price'],2);
                                }else{
                                    $card_sales_money += round((1-$card_info['card_discount'])*$val['price'],2);
                                }
                            }
                        }else{//单次劵
                            if($card_info['can_sales_more_times'] == 'true'){//TODO
                                $card_sales_money += $card_info['card_money']*$val['qty'];//商品种类*每种商品的数量*抵扣金额
                            }else{
                                $card_sales_money = $card_info['card_money'];
                            }
                        }
                        $salenum=$salenum+1;
                    }
                }
                $productnum=$productnum+1;
            }

            if ($salenum == 0) {
              $this->_error = '购物车中没有可以使用抵扣码的产品。';
              return false;
            }

        }else{
            $productnum = count($this->_orderinit['cart_info']['items']);

            if($card_info['can_use_onemore_time']=='true'){//多次劵
                if($card_info['maketing']=='5'){//商品减免
                    $card_sales_money = $card_info['card_money'];
                }
            }else{//单次劵
                $card_sales_money = $card_info['card_money'];
            }
        }

        if($card_info['black_list']){
            $black_list_arr = explode(',', $card_info['black_list']);
            $i = 0;
            foreach ($this->_orderinit['cart_info']['items'] as $key => $value) {
                if(in_array($value['product_id'], $black_list_arr)){
                    $i++;
                }
            }

            if($i == count($this->_orderinit['cart_info']['items'])){
              $this->_error = '该优惠券需要购买其他商品才能使用';
              return false;
            }
        }


        if($salenum==$productnum && $card_info['restr_good'] == 0){
          $this->_error = '购物篮中都是活动商品不能抵扣，你可以添加非特价商品';
          return false;
        }else if($salenum == 0 && $card_info['restr_good']==1) {
          $this->_error = '购物车中没有可以使用抵扣码的产品。';
          return false;
        }


        $this->_orderinit['use_card'] = $card_number;
        $this->_orderinit['card_money'] = $card_sales_money;
      }

      return true;
    }

    /**
     * 总价计算
     *
     * @return void
     * @author
     **/
    public function orderInit($building_id,$payway=array(),$order_address=array(),$jf_money=0,$card_number='',$store=0,$is_first=0,$api_version='1.0.0')
    {
      // 平台
      $this->ci->load->library('terminal');
      $source = $this->ci->terminal->get_source();

      $this->_orderinit = array();

      $this->ci->load->bll('cart');
      $cart = $this->ci->bll_cart->get_cart_info();
      if (!$cart['items']) {
        $error = $this->ci->bll_cart->get_error();

        $this->_error = implode(';',$error);
        return false;
      }

      $cart['items'] = array_values($cart['items']);

      $this->ci->load->library('login');
      $uid = $this->_orderinit['uid'] = $this->ci->login->get_uid();

      $is_8324_tuan = false;
        foreach ($cart['items'] as $item) {
          if(in_array($item['product_id'], $this->_o2oTuan_productids)){
              if($item['qty']>1){
                  $this->_error = '团购商品每单限购买1件';
                  return false;
              }
              if(count($cart['items']) > 1){
                  $this->_error = '团购商品只能单独购买';
                  return false;
              }
              $result = $this->_check_o2o_tuan($uid, $msg, $item['product_id']);
              if($result == false){
                $this->_error = $msg;
                return false;
              }
              $is_8324_tuan = true;
          }
      }
      //O2O满赠start
      //$cart['items'] = $this->O2oMbGift($cart['items'],$uid,$building_id);
      //o2o满赠END

      $this->_orderinit['cart_info'] = $cart;

      /*单品促销start*/
      $pay_discount = 0;
      $pro_sale_result = $this->checkProSale($cart,$this->_orderinit['uid']);
      if($pro_sale_result){
        $pay_discount = $pro_sale_result['cut_money'];
        $this->_orderinit['pro_sale_result'] = $pro_sale_result;
      }
      /*单品促销end*/
      /*banana huan kele*/
      $banana_money = $this->checkBanana($cart);
      $pay_discount += $banana_money;
      /*banana huan kele*/


      // 初始化价格

      $this->_orderinit['goods_money']  = number_format($cart['total_amount'],2,'.','');
      $method_money = $this->post_fee($cart,$this->_orderinit['goods_money']);
      $this->_orderinit['method_money'] = number_format($method_money,2,'.','');
      $this->_orderinit['pay_discount'] = number_format($pay_discount,2,'.','');
      $this->_orderinit['jf_money']     = 0;
      $this->_orderinit['card_money']   = 0;
      $this->_orderinit['pmoney']       = number_format($cart['goods_cost'],2,'.','');
      $this->_orderinit['msg']          = false;

      // 初始化订单金额
      $rs = $this->_order_total_init();

      if (!$rs) return false;

      $this->_orderinit['need_authen_code'] = 0;

      $o2o_send_result = $this->get_o2o_send_time($store,$is_8324_tuan);
      $this->_orderinit['shtime']           = $o2o_send_result['shtime'];
      $this->_orderinit['stime']            = $o2o_send_result['stime'];
      switch ($o2o_send_result['skey']) {
        case 'am':
          $deliver_option = array(array('stime_key'=>'am','stime'=>'上午'));
          $default_stime_key = 'am';
          break;
        case 'ampm':
          $deliver_option = array(array('stime_key'=>'am','stime'=>'上午'),array('stime_key'=>'pm','stime'=>'下午'));
          $default_stime_key = 'am';
          break;
        default:
          $deliver_option = array(array('stime_key'=>'2hours','stime'=>'两小时之内配送'));
          $default_stime_key = '2hours';
          break;
      }
      $this->_orderinit['deliver_option']   = $deliver_option;
      $this->_orderinit['stime_key']   = $default_stime_key;

      // 积分/优惠券限制
      $this->ci->load->model('order_model');
      $uselimit = $this->ci->order_model->check_cart_pro_status($cart);
      $this->_orderinit['can_use_card'] = $uselimit['card_limit'] == '1' ? 0 : 1;
      $this->_orderinit['can_use_jf']   = $uselimit['jf_limit'] == '1' ? 0 : 1;
      $this->_orderinit['jf_limit_pro'] = $uselimit['jf_limit_pro'];

      // 初始化会员
      $rs = $this->_user_init();
      if (!$rs) return false;



      // 初始化收货地址
      $rs = $this->_order_address_init($building_id,$order_address);
      if (!$rs) return false;

      // 初始化支付方式
      $rs = $this->_order_pay_init($building_id,$payway);
      if (!$rs) return false;

      // 初始化卡券
      $this->ci->load->model('card_model');
      if($api_version>='3.1.0'){
          if(empty($card_number) && $is_first==1){
              $card_info = $this->ci->card_model->get_orderinit_card($uid,$this->_orderinit['goods_money'],$source,$this->_orderinit['jf_money'],$pay_discount,1,$cart,$uselimit['card_limit']);
            $card_number = $card_info['card_number'];
          }
      }
      $can_use_card_number = $this->ci->card_model->can_use_card_number($uid,$this->_orderinit['goods_money'],$source,$this->_orderinit['jf_money'],$pay_discount,1,$cart,$uselimit['card_limit']);
      $this->_orderinit['can_use_card_number'] = $can_use_card_number;
      $rs = $this->_order_card_init($card_number);
      if (!$rs) return false;

      $rs = $this->_order_total_init();
      if (!$rs) return false;

      $this->ci->load->model('user_model');
      $user_jf = $this->ci->user_model->getUserScore($this->_orderinit['uid']);

      $this->_orderinit['order_jf_limit'] = $this->max_use_jf($this->_orderinit['money'],$user_jf['jf']);
      // 初始化积分
      $rs = $this->_order_jfmoney_init($jf_money);
      if (!$rs) return false;

      $rs = $this->_order_total_init();
      if (!$rs) return false;

      if($payway['pay_parent_id'] == 5 && $this->_orderinit['user_money'] < $this->_orderinit['money']){
        $this->_error = '帐户余额不足，当前余额为¥'.$this->_orderinit['user_money'].'，请充值';
        $this->_code = 600;
        return false;
      }



      /* 提交订单短信验证  start */
        $this->_orderinit['need_send_code']  = 0;
        $need_send_code = $this->ci->order_model->checkSendCode($this->_orderinit['cart_info']['items'],$this->_orderinit['uid'],$this->_orderinit['pay_parent_id'],$this->_orderinit['order_address']);
        if($need_send_code){
            $this->_orderinit['need_send_code']  = 1;
        }
        if($this->_orderinit['uid']==2034153 || $this->_orderinit['uid']==4433149 || $this->_orderinit['uid']==504884 || $this->_orderinit['uid']==332208 || $this->_orderinit['uid']==387671){
          $this->_orderinit['need_send_code']  = 0;
        }
       /* 提交订单短信验证  start */

      $this->_orderinit['card_number'] = $card_number;
      return $this->_orderinit;
    }


    /**
     * 总价计算
     *
     * @return void
     * @author
     **/
    public function orderInit_new($building_id=0,$payway=array(),$order_address=array(),$jf_money=0,$card_number='',$store_id=0,$is_first=0,$api_version='1.0.0',$region_id,$need_last_address=0)
    {
      // 平台
      $this->ci->load->library('terminal');
      $source = $this->ci->terminal->get_source();

      $this->_orderinit = array();

      $this->ci->load->bll('o2ocart');
      // $store_id = 0;
      $this->ci->bll_o2ocart->set_province($region_id,$building_id,$store_id);
      $cart = $this->getCartCache();
      if (!$cart['items']) {
        $error = $this->ci->bll_o2ocart->get_error();

        $this->_error = implode(';',$error);
        return false;
      }

      $cart['items'] = array_values($cart['items']);
      if(version_compare($this->_version, '4.1.0') < 0){
          $store_id or $store_id = $this->get_cart_store($cart['items']);
          if(!$store_id){
              $this->_error = '您所选的商品没有门店支持';
              return false;
          }
          if($building_id){
              $check_building_goods = $this->check_building_goods($cart['items'],$building_id);
              if($check_building_goods === false){
                  $this->_error = '您选购的部分商品无法配送至您的地址';
                  $this->_code = 430;
                  return false;
              }
          }
      }else{
          if(!$store_id){
              $this->_error = '您所在地址无门店供货';
              $this->_code = 431;
              return false;
          }
          $cart_store = $this->get_cart_store($cart['items']);
          if(!$cart_store){
              $this->_error = '您所选的商品没有门店支持';
              return false;
          }
          $check_store_goods = $this->check_store_goods($cart['items'],$store_id);
          if($check_store_goods === false){
              $this->_error = '您选购的部分商品无法配送至您的地址';
              $this->_code = 430;
              return false;
          }
      }

      $this->ci->load->model('o2o_store_model');
      $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));


      $this->ci->load->library('login');
      $uid = $this->_orderinit['uid'] = $this->ci->login->get_uid();

      $this->ci->load->model('o2o_order_extra_model');
      /* 限制新鲜点的新用户  start */
      $newO2oUser = $this->ci->o2o_order_extra_model->checkNewO2oUser($cart['items'], $uid);
      if ($newO2oUser) {
        $this->_error = $newO2oUser;
        return false;
      }
      /* 限制新鲜点的新用户  end */

      //团购活动判断begin
      $xsh_check_result = $this->ci->o2o_order_extra_model->check_tuan_pro($cart['items'],$uid);
      if($xsh_check_result){
          $this->_error = $xsh_check_result;
          return false;
      }
      //团购活动判断end

      //O2O满赠start
      //$cart['items'] = $this->O2oMbGift($cart['items'],$uid,$building_id);
      //o2o满赠END

      $this->_orderinit['cart_info'] = $cart;

      $is_8324_tuan = false;
      foreach ($cart['items'] as $item) {
          if(in_array($item['product_id'], $this->_o2oTuan_productids)){
              $is_8324_tuan = true;
          }
      }
      /*单品促销start*/
      // $pay_discount = 0;
      // $pro_sale_result = $this->checkProSale($cart,$this->_orderinit['uid']);
      // if($pro_sale_result){
      //   $pay_discount = $pro_sale_result['cut_money'];
      //   $this->_orderinit['pro_sale_result'] = $pro_sale_result;
      // }
      /*单品促销end*/
      /*banana huan kele*/
      //$banana_money = $this->checkBanana($cart);
      //$pay_discount += $banana_money;
      /*banana huan kele*/


      // 初始化价格
      $this->_orderinit['goods_money']  = number_format($cart['goods_amount'],2,'.','');
      $method_money = $this->post_fee($cart,$cart['total_amount']);
      $this->_orderinit['method_money'] = number_format($method_money,2,'.','');
      $this->_orderinit['pay_discount'] = number_format(($cart['pmt_total']?$cart['pmt_total']:0),2,'.','');
      $this->_orderinit['jf_money']     = 0;
      $this->_orderinit['card_money']   = 0;
      $this->_orderinit['pmoney']       = number_format($cart['goods_cost'],2,'.','');
      $this->_orderinit['msg']          = false;

      // 初始化订单金额
      $rs = $this->_order_total_init();

      if (!$rs) return false;

      $this->_orderinit['need_authen_code'] = 0;

      $o2o_send_result = $this->get_o2o_send_time($store,$is_8324_tuan);
      $this->_orderinit['shtime']           = $o2o_send_result['shtime'];
      $this->_orderinit['stime']            = $o2o_send_result['stime'];
      switch ($o2o_send_result['skey']) {
        case 'am':
          $deliver_option = array(array('stime_key'=>'am','stime'=>'上午'));
          $default_stime_key = 'am';
          break;
        case 'ampm':
          $deliver_option = array(array('stime_key'=>'am','stime'=>'上午'),array('stime_key'=>'pm','stime'=>'下午'));
          $default_stime_key = 'am';
          break;
        default:
          $defaultSendTime = $this->getDefaultSendTime($store['id']);
          $deliver_option = array(array('stime_key'=>$defaultSendTime['key'],'stime'=>$defaultSendTime['time'] . '小时之内配送'));
          $default_stime_key = $defaultSendTime['key'];
          break;
      }
      $this->_orderinit['deliver_option']   = $deliver_option;
      $this->_orderinit['stime_key']   = $default_stime_key;

      // 积分/优惠券限制
      $this->ci->load->model('order_model');
      $uselimit = $this->ci->order_model->check_cart_pro_status($cart);
      $this->_orderinit['can_use_card'] = $uselimit['card_limit'] == '1' ? 0 : 1;
      $this->_orderinit['can_use_jf']   = $uselimit['jf_limit'] == '1' ? 0 : 1;
      $this->_orderinit['jf_limit_pro'] = $uselimit['jf_limit_pro'];

      // 初始化会员
      $rs = $this->_user_init();
      if (!$rs) return false;



      // 初始化收货地址
      $rs = $this->_order_address_init($building_id,$order_address);
      if (!$rs) return false;

      // 初始化支付方式
      $rs = $this->_order_pay_init($building_id,$payway,$api_version,$store_id);
      if (!$rs) return false;

      // 初始化卡券
      $this->ci->load->model('card_model');
      if($api_version>='3.1.0'){
          if(empty($card_number) && $is_first==1){
              $card_info = $this->ci->card_model->get_orderinit_card($uid,$this->_orderinit['goods_money'],$source,$this->_orderinit['jf_money'],$pay_discount,1,$cart,$uselimit['card_limit']);
            $card_number = $card_info['card_number'];
          }
      }
      $can_use_card_number = $this->ci->card_model->can_use_card_number($uid,$this->_orderinit['goods_money'],$source,$this->_orderinit['jf_money'],$pay_discount,1,$cart,$uselimit['card_limit']);
      $this->_orderinit['can_use_card_number'] = $can_use_card_number;
      $rs = $this->_order_card_init($card_number);
      if (!$rs) return false;

      $rs = $this->_order_total_init();
      if (!$rs) return false;
      $this->_orderinit['need_pay_money'] = number_format($this->_orderinit['goods_money'] + $this->_orderinit['method_money'],2,'.','');
      $this->ci->load->model('user_model');

        //o2o 不能使用积分
      //$user_jf = $this->ci->user_model->getUserScore($this->_orderinit['uid']);

      $this->_orderinit['order_jf_limit'] = 0;//$this->max_use_jf($this->_orderinit['money']- $this->_orderinit['method_money'],$user_jf['jf']);
      // 初始化积分
      $rs = $this->_order_jfmoney_init($jf_money);
      if (!$rs) return false;

      $rs = $this->_order_total_init();
      if (!$rs) return false;

      if($payway['pay_parent_id'] == 5 && $this->_orderinit['user_money'] < $this->_orderinit['money']){
        $this->_error = '帐户余额不足，当前余额为¥'.$this->_orderinit['user_money'].'，请充值';
        $this->_code = 600;
        return false;
      }



      /* 提交订单短信验证  start */
        $this->_orderinit['need_send_code']  = 0;
        $need_send_code = $this->ci->order_model->checkSendCode($this->_orderinit['cart_info']['items'],$this->_orderinit['uid'],$this->_orderinit['pay_parent_id'],$this->_orderinit['order_address']);
        if($need_send_code){
            $this->_orderinit['need_send_code']  = 1;
        }
        if($this->_orderinit['uid']==2034153 || $this->_orderinit['uid']==4433149 || $this->_orderinit['uid']==332208 || $this->_orderinit['uid']==387671){
          $this->_orderinit['need_send_code']  = 0;
        }
       /* 提交订单短信验证  start */

      $this->_orderinit['card_number'] = $card_number;

        //优惠券改造
        if(!empty($card_number))
        {
            $this->ci->load->model('card_model');
            $cardInfo = $this->ci->card_model->get_card_info($card_number);
            $cards[] =$cardInfo;
            $card = $this->getCouponUseRange($cards);
            $this->_orderinit['use_range'] = $card[0]['use_range'];
            $this->_orderinit['remarks'] = $card[0]['remarks'];
        }
        else
        {
            $this->_orderinit['use_range'] = '';
            $this->_orderinit['remarks'] = '';
        }
        if(version_compare($this->_version, '4.1.0') >= 0){
            if($need_last_address){
                $last_order_info = $this->getLastOrderInfo_v2($uid,$store_id);
                if($last_order_info['order_address']){
                    $this->_orderinit['last_address'] = $last_order_info['order_address'];
                }
            }
        }


      return $this->_orderinit;
    }

    /*
    *单品促销活动
    */
    function checkProSale($cart_info,$uid){
      $cut_money = 0;
      $active_rules = array();
      $pro_sale_first = $this->getProSaleRules();
      if(empty($pro_sale_first)){
        return false;
      }

      $product_ids = array();
      foreach ($cart_info['items'] as $key => $value) {
        $product_ids[$value['product_id']] = $value['product_id'];
      }
      foreach ($pro_sale_first as $key => $value) {
        $product_arr = explode(',', $value['product_id']);
        foreach ($product_arr as $k => $v) {
          if(isset($product_ids[$v])){
            $rule = unserialize($value['content']);
            if($value['account_limit']=='1'){
              $this->ci->db->from('active_limit');
              $this->ci->db->where(array('uid'=>$uid,'active_tag'=>$value['active_tag']));
              if($this->ci->db->get()->num_rows()==0){
                $cut_money += $rule['cut_money'];
                $active_rules[$key]['account_limit'] = $value['account_limit'];
                $active_rules[$key]['device_limit'] = $value['device_limit'];
                $active_rules[$key]['active_tag'] = $value['active_tag'];
              }
            }else{
              $cut_money += $rule['cut_money'];
              $active_rules[$key]['account_limit'] = $value['account_limit'];
              $active_rules[$key]['device_limit'] = $value['device_limit'];
              $active_rules[$key]['active_tag'] = $value['active_tag'];
            }
          }
        }
      }
      return array('cut_money'=>$cut_money,'active_rules'=>$active_rules);
    }

    private function checkBanana($cart_info){
      $money = 0;
      foreach ($cart_info['items'] as $key => $value) {
        if(in_array($value['product_id'], $this->_zero_product_id)){
          $money += $value['amount'] ;
        }
      }
      return $money;
    }

    /*
    *获取所有进行中的促销规则
    */
    function getProSaleRules(){
      $this->ci->load->model("promotion_model");
      $pro_sale_first = $this->ci->promotion_model->get_single_promotion(3);
      return $pro_sale_first;
    }


    /**
     * 积分限制
     *
     * @return void
     * @author
     **/
    private function max_use_jf($money,$userjf)
    {
        $pay_money = $money;
        $jf_limit = floor($pay_money/2);

        /*用户积分start*/
        $user_jf_money = number_format(floor($userjf/100),0,'.','');
        if($user_jf_money<0){
            $user_jf_money = 0;
        }
        /*用户积分end*/

        /*积分使用计算start*/
        if($user_jf_money<$jf_limit){
            $order_jf_limit = $user_jf_money;
        }else{
            $order_jf_limit = $jf_limit;
        }
        /*积分使用计算end*/

        return $order_jf_limit;
    }

    /**
     * 获取支付方式
     *
     * @param Int $province_id 省份
     * @return void
     * @author
     **/
    public function getPay($region_id)
    {
      // $this->ci->load->model('o2o_region_model');
      // $parents = $this->ci->o2o_region_model->getParents($region_id);

      // foreach ($parents as $p) {
      //   if ($p['attr'] == 1) {
      //     $province_id = $p['area_id'];
      //   }
      // }
      $this->ci->load->bll('o2ocart');
      $this->ci->bll_o2ocart->set_province($region_id);
      $cart = $this->getCartCache();
      $cart['items'] = array_values($cart['items']);
      $is_8324_tuan = false;
      foreach ($cart['items'] as $item) {
          if(in_array($item['product_id'], $this->_o2oTuan_productids)){
              $is_8324_tuan = true;
              break;
          }
      }

      $this->ci->load->bll('payments');

      $payments = $this->ci->bll_payments->getMethods($region_id);

      // 过滤掉线下支付
      foreach ($payments as $key => $payment) {
        if ($payment['pay_parent_id'] == 4  && $payment['pay_parent_name'] == '线下支付') {
          unset($payments[$key]);
        }
        if($is_8324_tuan === true){
          if(!in_array($payment['pay_parent_id'], array(5,7,9))){
            unset($payments[$key]);
          }
        }
      }


    $this->ci->load->library('login');
    $uid = $this->ci->login->get_uid();
    $this->ci->load->model('user_model');
    $user = $this->ci->user_model->selectUser("money", array('id'=>$uid));

    $pay_arr = array();
    foreach ($payments as $value) {
        if($this->_version >= '3.4.0' && $this->_source == 'app' && $value['pay_parent_id'] == 5){
            continue;
        }
        switch ($value['pay_parent_id']) {
            case '1':
                $pay_key = 'online';
                $pay_arr['online']=array(
                            'name'=>'在线支付',
                            'pays'=>array(),
                        );
                break;
            case '3':
                $pay_key = 'bank';
                $pay_arr['bank']=array(
                            'name'=>'网上银行支付',
                            'pays'=>array(),
                        );
                break;
            case '5':
                $pay_key = 'fday';
                $pay_arr['fday']=array(
                            'name'=>'帐户余额支付',
                            'pays'=>array(),
                        );
                break;
            case '4':
                $pay_key = 'offline';
                $pay_arr['offline']=array(
                            'name'=>'线下支付',
                            'pays'=>array(),
                        );
                break;
            case '7':
                $pay_key = 'online';
                // $pay_arr['online']=array(
                //             'name'=>'在线支付',
                //             'pays'=>array(),
                //         );
                break;
            default:
                $pay_key = '';
                break;
        }

        if($pay_key){
            $pay_tmp = array();
            foreach ($value['son'] as $v) {
                $pay_tmp['pay_parent_id'] = $value['pay_parent_id'];
                $pay_tmp['pay_id']        = $v['pay_id'];
                $pay_tmp['pay_name']      = $v['pay_name'];
                $pay_tmp['has_invoice']   = $v['has_invoice'];
                $pay_tmp['icon']          = $v['icon'];
                $pay_tmp['discount_rule'] = $v['discount_rule'];
                $pay_tmp['user_money'] = $user['money'];
                $pay_arr[$pay_key]['pays'][] = $pay_tmp;
            }
        }
    }

      return $pay_arr;
    }

    private function validPayCode($paycode,$need_send_code=false)
    {
      //用户余额判断
      if($this->_orderinit['pay_parent_id']=='5'){
          if($this->_orderinit['user_money'] < $this->_orderinit['money']){
            $this->_error = '帐户余额不足，当前余额为¥'.$this->_orderinit['user_money'].'，请充值';
            $this->_code = 600;
            return false;
          }

          if($need_send_code){
              if (!$paycode['verification_code']) {
                $this->_error = '验证码验证失败，请重新输入验证码';
                $this->_code = 601;
                return false;
              }

              if (!$paycode['ver_code_connect_id']) {
                $this->_error = '验证码不能为空';
                $this->_code = 602;
                return false;
              }

              $this->ci->load->library('session');

              $this->ci->session->sess_id = $paycode['ver_code_connect_id'];
              $this->ci->session->sess_read();
              $ver_code_session = $this->ci->session->userdata;

              $userdata = unserialize($ver_code_session['user_data']);
              if(!isset($userdata['verification_code'])){
                $this->_error = '验证码已过期，请输入最新收到的验证码';
                $this->_code = 601;
                return false;
              }

              if($userdata['verification_code'] != md5($this->_orderinit['user_mobile'].$paycode['verification_code']) ){
                  $this->_code = 602;
                  $this->_error = '验证码错误';
                  return false;
              }

              unset($userdata['verification_code']);

              $this->ci->session->userdata['user_data'] = serialize($userdata);
          }
      }

      return true;
    }

    public function createOrder($building_id,$order_type,$paycode=array(),$payway=array(),$order_address=array(),$jf_money=0,$card_number='',$ordermsg=array(),$device_code='',$api_version,$stime_key='2hours')
    {
      $this->ci->load->library('login');
      $uid = $this->ci->login->get_uid();

      //黑名单验证
      $this->ci->load->model('user_model');
      // if($user_black = $this->ci->user_model->check_user_black($uid)){
      //   if($user_black['type']==1){
      //     $this->_error = '果园君发现您的账号为无效手机号，为保证您的购物体验请用有效手机号注册，敬请谅解。';
      //   }else{
      //     $this->_error = '您的帐号可能存在安全隐患，暂时冻结，请联系客服处理';
      //   }
      //   return false;
      // }

    $this->ci->load->model('o2o_store_building_model');
    $store_building = $this->ci->o2o_store_building_model->dump(array('building_id'=>$building_id));

    if (!$store_building) return array('code'=>300,'msg'=>'所在楼暂无门店供货');

    $store_id = $store_building['store_id'];
    $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));

    $data = $this->orderInit($building_id,$payway,$order_address,$jf_money,$card_number,$store);


      if (!$data) return false;

    /*赠品订单金额限制判断start*/
    $user_gift_check_result = $this->check_gift_money_limit($data['cart_info'],$data['money']-$data['method_money']);
    if($user_gift_check_result!==false){
        $this->_error = $user_gift_check_result;
        return false;
    }
    /*赠品订单金额限制判断end*/

      $this->ci->load->model('o2o_order_extra_model');

      //营销活动判断start
      $xsh_check_result = $this->ci->o2o_order_extra_model->_check_xsh($data['cart_info']['items'],$uid);
      if($xsh_check_result){
        $this->_error = $xsh_check_result;
        return false;
      }
      //营销活动判断end

      //设备限制
      $device_products = array();
      $device_check_result = $this->ci->o2o_order_extra_model->check_device($data['cart_info']['items'],$device_code);
      if($device_check_result['code'] == '300'){
          $this->_error = $device_check_result['msg'];
          return false;
      }elseif($device_check_result['code'] == '200'){
          $device_products = unserialize($device_check_result['msg']);
      }

    /* 限制新鲜点的新用户  start */
    $newO2oUser = $this->ci->o2o_order_extra_model->checkNewO2oUser($data['cart_info']['items'], $uid);
    if ($newO2oUser) {
      $this->_error = $newO2oUser;
      return false;
    }
    /* 限制新鲜点的新用户  start */

        //团购活动判断begin
        $xsh_check_result = $this->ci->o2o_order_extra_model->check_tuan_pro($data['cart_info']['items'],$uid);
        if($xsh_check_result){
            $this->_error = $xsh_check_result;
            return false;
        }
        //团购活动判断end

    if(strcmp($api_version,'2.3.0')>=0){
        $need_send_code = $data['need_send_code'];
    }
    if($uid!=2034153 && $uid!=4433149 && $uid!=332208 && $uid!=387671){


    $rs = $this->validPayCode($paycode,$need_send_code);
      if (!$rs) return false;
    }
      // 收货地址验证
      if (!$data['order_address']['name']) {
        $this->_error = '请完善收货人信息';
        return false;
      }

      if (!$data['order_address']['address']) {
        $this->_error = '请完善收货人地址';
        return false;
      }

      if (!$data['order_address']['mobile']) {
        $this->_error = '请完善收货人手机信息';
        return false;
      }

      if (!is_numeric($data['order_address']['mobile']) || strlen(strval($data['order_address']['mobile'])) != 11) {
        $this->_error = '请正确填写手机信息';
        return false;
      }

      $pay_array  =  $this->ci->config->item("pay_array");
      // 支付方式验证
      if (!$data['pay_parent_id']) {
        $this->_error = '请选择支付方式';
        return false;
      }

      if (!$pay_array[$data['pay_parent_id']]) {
        $this->_error = '所选支付方式暂不支持';
        return false;
      }

      if ($data['pay_parent_id'] == 4) {
          $this->_error = '所选支付方式暂不支持';
          return false;
      }

      $pay_son = $pay_array[$data['pay_parent_id']]['son'];
      if ($pay_son) {
        if (!$data['pay_id']) {
          $this->_error = '请选择支付方式';
          return false;
        }

        if (!$pay_son[$data['pay_id']]) {
          $this->_error = '所选支付方式暂不支持';
          return false;
        }
      } else {
        $data['pay_id'] = 0;
      }


      $this->ci->load->model('o2o_region_model');

      $province = $this->ci->o2o_region_model->dump(array('id'=>$data['order_address']['province']['id']),'area_id');

      $order_region = 1;
      $area_refelect = $this->ci->config->item("area_refelect");
      foreach ($area_refelect as $key => $value) {
          if(in_array($province['area_id'], $value)){
              $order_region = $key;
              break;
          }
      }

      $this->ci->db->trans_begin();



      $score = $this->_order_score($data);

      $this->ci->load->library('terminal');

      // $enter_tag     = '';
      // $sales_channel = 1;
      // $version       = 0;

      //容错代码
      if(isset($data['deliver_option']) && !empty($data['deliver_option']) && is_array($data['deliver_option'])){
        $stime_option = array();
        foreach ($data['deliver_option'] as $key => $value) {
          $stime_option[] = $value['stime_key'];
        }
        if(!in_array($stime_key, $stime_option)){
          $stime_key = $stime_option[0];
        }
      }

      $order = array(
          'order_name'    => '',
          'pay_status'    => $data['money'] == 0 ? 1 : 0,
          'money'         => $data['money'],
          'pmoney'        => $data['goods_money'],
          'goods_money'   => $data['goods_money'],
          'score'         => $score,
          // 'msg'           => '',
          // 'hk'            => '',
          'method_money'  => $data['method_money'],
          'order_status'  => '1',
          'time'          => date('Y-m-d H:i:s'),
          // 'order_region'  => $data['order_region'],
          'channel'       => $this->ci->terminal->get_channel(),
          // 'is_enterprise' => $enter_tag,
          // 'sales_channel' => $data['sales_channel'],
          'pay_discount'  => $data['pay_discount'],
          'version'       => 1,
          'pay_parent_id' => $data['pay_parent_id'],
          'pay_id'        => $data['pay_id'],
          'pay_name'      => $data['pay_name'],
          'jf_money'      => $data['jf_money'],
          'use_jf'        => $data['use_jf'],
          'use_card'      => $data['use_card'],
          'card_money'    => $data['card_money'],
          'uid'           => $uid,
          'shtime'        => $data['shtime'],
          'stime'         => $stime_key,
          'order_type'    => $order_type == 4 ? 4 : 3,
          'address_id'    => 0,
          'last_modify'   => time(),
      );

      if ($ordermsg['msg']) {
        $order['msg'] = $ordermsg['msg'];
      }

      foreach ($data['cart_info']['items'] as $value) {
          if(in_array($value['product_id'], $this->_o2oTuan_productids)){
              $order['channel'] = 99;
          }
      }

      $this->ci->load->model('order_model');

      $order_id = $this->ci->order_model->generate_order('order',$order);

      if (!$order_id) {
        $this->ci->db->trans_rollback();

        $this->_error = '出错啦,请重新提交1';
        return false;
      }

      $order = $this->ci->order_model->dump(array('id' => $order_id ));
      $c_orders = array();
      if($api_version >= '2.2.0'){
          $this->getChildRate($data['cart_info']['items'],$data['goods_money'],$order['stime']);
          $c_orders = $this->o2oSingleOrder($order,$order_address['address'],$building_id);
      }

        /*单品促销验证start*/
        $check_result = $this->checkUserProSale($uid,$data['pro_sale_result'],$order_id);
        /*单品促销验证end*/
      $store_filter = array('building_id'=>$building_id);

      // if($api_version >= '2.2.0'){
      //     $store_id = key($this->o2oChildRate);
      //     $store_filter = array('building_id'=>$building_id,'store_id'=>$store_id);
      // }

      //验证楼宇门店关系
      if($api_version >= '2.2.0'){
          $check_building_goods = $this->check_building_goods($data['cart_info']['items'],$building_id);
          if($check_building_goods === false){
              $this->ci->db->trans_rollback();
              $this->_error = '出错啦,请重新选择商品购买';
              return false;
          }
      }
      // 保存扩展
      $this->ci->load->model('o2o_store_building_model');
      $store_building = $this->ci->o2o_store_building_model->dump($store_filter);
      if (!$store_building){
          $this->ci->db->trans_rollback();
          $this->_error = '出错啦,请重新选择商品购买';
          return false;
      }
      $order_extra = array(
          'order_id'    => $order_id,
          'type'        => $order_type,
          'store_id'    => $store_building['store_id'],
          'building_id' => $building_id,
          'uid'         => $uid,
          'address'     => $order_address['address'],
      );

      if($api_version >= '2.2.0'){
          $data['cart_info']['items'][0]['store_id'] and $order_extra['store_id'] = $data['cart_info']['items'][0]['store_id'];
      }

      $store_id = $order_extra['store_id'];
      $rs = $this->ci->o2o_order_extra_model->insert($order_extra);
      if (!$rs) {
        $this->ci->db->trans_rollback();
        $this->_error = '出错啦,请重新提交4';
        return false;
      }

      // //O2O随单赠start
      // $this->ci->load->model('o2o_region_model');
      // $parents = $this->ci->o2o_region_model->getParents($building_id);
      // $province=end($parents);
      // $region_id = $province['area_id'];
      // $GiftSend = array();
      // if($region_id == 106092){
      //     // if(date('Y-m-d',time())>='2015-09-14'){
      //     //     if($data['money']>=10){
      //     //         $GiftSend = $this->O2oGiftSend($uid);
      //     //     }
      //     // }else{
      //         $GiftSend = $this->O2oGiftSend($uid);
      //     // }
      // }
      // if($GiftSend){
      //     $data['cart_info']['items'][] = $GiftSend['items'];
      //     $GiftSend_id = $GiftSend['id'];
      // }
      // //o2o随单赠END

      // //O2O满5赠1start
      // if(date('Y-m-d H:i:s',time())<='2015-09-30 17:00:00'){
      //     $Gift5to1Send = $this->Gift5to1Send($data['cart_info']['items']);
      //     $Gift5to1Send and $data['cart_info']['items'][] = $Gift5to1Send['items'];
      // }
      // //O2O满5赠1END

      // 生成明细
      $rs = $this->orderAddPro($uid,$order_id,$data['cart_info'],$api_version,$c_orders,$store_id);
      if (!$rs) {
        $this->ci->db->trans_rollback();
        $this->_error = '出错啦,请重新提交2';
        return false;
      }
      //O2O随单增赠品状态start
      if($GiftSend){
          $GiftSend_id = $GiftSend['id'];
          $this->O2oGiftSended($GiftSend_id,$order_id);
      }
      //O2O随单增赠品状态end

      // 生成地址
      $rs = $this->orderAddAddr($order_id,$building_id,$data['order_address']);
      if (!$rs) {
        $this->ci->db->trans_rollback();
        $this->_error = '出错啦,请重新提交3';
        return false;
      }

      // 帐户余额扣款
      if($order['pay_parent_id'] == 5) {
          $check = $this->ci->user_model->check_money_identical($uid);
          if($check === false){
              $this->ci->db->trans_rollback();
              $this->ci->user_model->freeze_user($uid);
              $this->_error = '您的账户余额异常';
              return false;
          }
          if(!$this->ci->user_model->cut_user_money($uid,$order['money'],$order['order_name'])){
            $this->ci->db->trans_rollback();
            $this->_error = '出错啦,请重新提交5';
            return false;
          }
          $this->ci->order_model->update(array('pay_status'=>'1'),array('id'=>$order['id']));
      }

      //扣积分
      if( $order['jf_money'] > 0 ){
          $use_jf = $order['jf_money']*100;
          if(!$this->ci->user_model->cut_uses_jf($uid,$use_jf,$order['order_name'])){
              $this->ci->db->trans_rollback();

              $this->_error = '积分扣除失败，请重新提交订单';
              return false;
          }
      }

      // 卡券
      if ($order['use_card']) {
          if($data['can_use_card'] === 0){
              $this->_error = '优惠券使用错误，请取消使用重新提交';
              return false;
          }

          $this->ci->load->model('card_model');
          $card_info = $this->ci->card_model->get_card_info($order['use_card']);

          $content = "订单".$order["order_name"]."抵扣".$order['card_money'];

          if($card_info['can_use_onemore_time']=='false'){
              $card_data = array(
                  'is_used'=>'1',
                  'content'=>$content
              );
              if(!$this->ci->card_model->update_card($card_data,array('card_number'=>$order['use_card']))){
                  $this->ci->db->trans_rollback();

                  $this->_error = '优惠券使用错误，请取消使用重新提交';
                  return false;
              }
          }
      }

      if($device_products){
          $this->ci->o2o_order_extra_model->add_device_limit($device_products,$device_code,$order_id);
      }

      $this->ci->db->trans_commit();

      $this->ci->session->sess_write();

      $user_info = $this->ci->user_model->dump(array('id' =>$uid),'msgsetting,mobile,email');
      $this->_afterCreateOrder($order['id'],$user_info);

      return array('code'=>200,'msg'=>$order['order_name'],'pay_parent_id'=>$order['pay_parent_id'],'money'=>$order['money']);
    }


    public function createOrder_new($building_id = 0,$order_type,$paycode=array(),$payway=array(),$order_address=array(),$jf_money=0,$card_number='',$ordermsg=array(),$device_code='',$api_version,$stime_key='2hours',$store_id=0)
    {
      $this->ci->load->library('login');
      $uid = $this->ci->login->get_uid();

      //黑名单验证
      $this->ci->load->model('user_model');
      // if($user_black = $this->ci->user_model->check_user_black($uid)){
      //   if($user_black['type']==1){
      //     $this->_error = '果园君发现您的账号为无效手机号，为保证您的购物体验请用有效手机号注册，敬请谅解。';
      //   }else{
      //     $this->_error = '您的帐号可能存在安全隐患，暂时冻结，请联系客服处理';
      //   }
      //   return false;
      // }
    if(version_compare($this->_version, '4.1.0') < 0){
        $this->ci->load->model('o2o_store_building_model');
        $store_building = $this->ci->o2o_store_building_model->dump(array('building_id'=>$building_id));

        if (!$store_building) return array('code'=>300,'msg'=>'所在楼暂无门店供货');

        $store_id = $store_building['store_id'];
    }else{
        if(empty($store_id))  return array('code'=>431,'msg'=>'您所在地址无门店供货');
    }
    $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));

		$data = $this->orderInit_new($building_id,$payway,$order_address,$jf_money,$card_number,$store_id);


      if (!$data) return false;

    /*赠品订单金额限制判断start*/
    $user_gift_check_result = $this->check_gift_money_limit($data['cart_info'],$data['money']-$data['method_money']);
    if($user_gift_check_result!==false){
        $this->_error = $user_gift_check_result;
        return false;
    }
    /*赠品订单金额限制判断end*/

      $this->ci->load->model('o2o_order_extra_model');

      //营销活动判断start
      $xsh_check_result = $this->ci->o2o_order_extra_model->_check_xsh($data['cart_info']['items'],$uid);
      if($xsh_check_result){
        $this->_error = $xsh_check_result;
        return false;
      }
      //营销活动判断end

      //设备限制
      $device_products = array();
      $device_check_result = $this->ci->o2o_order_extra_model->check_device($data['cart_info']['items'],$device_code);
      if($device_check_result['code'] == '300'){
          $this->_error = $device_check_result['msg'];
          return false;
      }elseif($device_check_result['code'] == '200'){
          $device_products = unserialize($device_check_result['msg']);
      }

		// /* 限制新鲜点的新用户  start */
		// $newO2oUser = $this->ci->o2o_order_extra_model->checkNewO2oUser($data['cart_info']['items'], $uid);
		// if ($newO2oUser) {
		// 	$this->_error = $newO2oUser;
		// 	return false;
		// }
		// /* 限制新鲜点的新用户  start */

  //       //团购活动判断begin
  //       $xsh_check_result = $this->ci->o2o_order_extra_model->check_tuan_pro($data['cart_info']['items'],$uid);
  //       if($xsh_check_result){
  //           $this->_error = $xsh_check_result;
  //           return false;
  //       }
  //       //团购活动判断end

    if(strcmp($api_version,'2.3.0')>=0){
        $need_send_code = $data['need_send_code'];
    }
    if($uid!=2034153 && $uid!=4433149 && $uid!=332208 && $uid!=387671){


		$rs = $this->validPayCode($paycode,$need_send_code);
      if (!$rs) return false;
    }
      // 收货地址验证
      if (!$data['order_address']['name']) {
        $this->_error = '请完善收货人信息';
        return false;
      }

      if (!$data['order_address']['address']) {
        $this->_error = '请完善收货人地址';
        return false;
      }

      if (!$data['order_address']['mobile']) {
        $this->_error = '请完善收货人手机信息';
        return false;
      }

      if (!is_numeric($data['order_address']['mobile']) || strlen(strval($data['order_address']['mobile'])) != 11) {
        $this->_error = '请正确填写手机信息';
        return false;
      }

      $pay_array  =  $this->ci->config->item("pay_array");
      // 支付方式验证
      if (!$data['pay_parent_id']) {
        $this->_error = '请选择支付方式';
        return false;
      }

      if (!$pay_array[$data['pay_parent_id']]) {
        $this->_error = '所选支付方式暂不支持';
        return false;
      }

      if ($data['pay_parent_id'] == 4) {
          $this->_error = '所选支付方式暂不支持';
          return false;
      }

      $pay_son = $pay_array[$data['pay_parent_id']]['son'];
      if ($pay_son) {
        if (!$data['pay_id']) {
          $this->_error = '请选择支付方式';
          return false;
        }

        if (!$pay_son[$data['pay_id']]) {
          $this->_error = '所选支付方式暂不支持';
          return false;
        }
      } else {
        $data['pay_id'] = 0;
      }


      $this->ci->load->model('o2o_region_model');

      $province = $this->ci->o2o_region_model->dump(array('id'=>$data['order_address']['province']['id']),'area_id');

      // $order_region = 1;
      // $area_refelect = $this->ci->config->item("area_refelect");
      // foreach ($area_refelect as $key => $value) {
      //     if(in_array($province['area_id'], $value)){
      //         $order_region = $key;
      //         break;
      //     }
      // }

      $this->ci->db->trans_begin();



      $score = $this->_order_score($data);

      $this->ci->load->library('terminal');

      // $enter_tag     = '';
      // $sales_channel = 1;
      // $version       = 0;
      $stime_key = !empty($stime_key) ? $stime_key : $this->getDefaultSendTime($store['id'], 'key');
      //容错代码
      if(isset($data['deliver_option']) && !empty($data['deliver_option']) && is_array($data['deliver_option'])){
        $stime_option = array();
        foreach ($data['deliver_option'] as $key => $value) {
          $stime_option[] = $value['stime_key'];
        }
        if(!in_array($stime_key, $stime_option)){
          $stime_key = $stime_option[0];
        }
      }

      $order = array(
          'order_name'    => '',
          'pay_status'    => $data['money'] == 0 ? 1 : 0,
          'money'         => $data['money'],
          'pmoney'        => $data['goods_money'],
          'goods_money'   => $data['goods_money'],
          'score'         => $score,
          // 'msg'           => '',
          // 'hk'            => '',
          'method_money'  => $data['method_money'],
          'order_status'  => '1',
          'time'          => date('Y-m-d H:i:s'),
          // 'order_region'  => $data['order_region'],
          'channel'       => $this->ci->terminal->get_channel(),
          // 'is_enterprise' => $enter_tag,
          // 'sales_channel' => $data['sales_channel'],
          'pay_discount'  => $data['pay_discount'],
          'version'       => 1,
          'pay_parent_id' => $data['pay_parent_id'],
          'pay_id'        => $data['pay_id'],
          'pay_name'      => $data['pay_name'],
          'jf_money'      => $data['jf_money'],
          'use_jf'        => $data['use_jf'],
          'use_card'      => $data['use_card'],
          'card_money'    => $data['card_money'],
          'uid'           => $uid,
          'shtime'        => $data['shtime'],
          'stime'         => $stime_key,
          'order_type'    => $order_type == 4 ? 4 : 3,
          'address_id'    => 0,
          'last_modify'   => time(),
      );

      if($order['pay_status'] == 1){
          $order['update_pay_time'] = date('Y-m-d H:i:s');
          $order['pay_time'] = date('Y-m-d H:i:s');
      }

      if ($ordermsg['msg']) {
        $order['msg'] = $ordermsg['msg'];
      }

      foreach ($data['cart_info']['items'] as $value) {
          if(in_array($value['product_id'], $this->_o2oTuan_productids)){
              $order['channel'] = 99;
          }
      }

      $this->ci->load->model('order_model');

      $order_id = $this->ci->order_model->generate_order('order',$order);

      if (!$order_id) {
        $this->ci->db->trans_rollback();

        $this->_error = '出错啦,请重新提交1';
        return false;
      }

      $order = $this->ci->order_model->dump(array('id' => $order_id ));
      $c_orders = array();
      if($api_version >= '2.2.0'){
          $this->getChildRate($data['cart_info']['items'],$data['goods_money'],$order['stime']);
          $c_orders = $this->o2oSingleOrder($order,$order_address['address'],$building_id);
      }

        /*单品促销验证start*/
        $check_result = $this->checkUserProSale($uid,$data['pro_sale_result'],$order_id);
        /*单品促销验证end*/
      $store_filter = array('building_id'=>$building_id);

      // if($api_version >= '2.2.0'){
      //     $store_id = key($this->o2oChildRate);
      //     $store_filter = array('building_id'=>$building_id,'store_id'=>$store_id);
      // }

      //验证楼宇门店关系
      if(version_compare($this->_version, '4.1.0') >= 0){
          $check_store_goods = $this->check_store_goods($cart['items'],$store_id);
          if($check_store_goods === false){
              $this->ci->db->trans_rollback();
              $this->_error = '您选购的部分商品无法配送至您的地址';
              return false;
          }
      }else{
          $check_building_goods = $this->check_building_goods($data['cart_info']['items'],$building_id);
          if($check_building_goods === false){
              $this->ci->db->trans_rollback();
              $this->_error = '您选购的部分商品无法配送至您的地址';
              return false;
          }
          $this->ci->load->model('o2o_store_building_model');
          $store_building = $this->ci->o2o_store_building_model->dump($store_filter);
          if (!$store_building){
              $this->ci->db->trans_rollback();
              $this->_error = '出错啦,请重新选择商品购买';
              return false;
          }
          $store_id = $this->get_cart_store($data['cart_info']['items']);
          $store_id or $store_id = $store_building['store_id'];
      }
      // 保存扩展
      $order_extra = array(
          'order_id'    => $order_id,
          'type'        => $order_type,
          'store_id'    => $store_id,
          'building_id' => $building_id,
          'uid'         => $uid,
          'address'     => $order_address['address'],
      );
      if(version_compare($this->_version, '4.1.0') >= 0){
          $order_extra['address'] = $order_address['pre_address'].$order_address['pro_address'];
      }
      $rs = $this->ci->o2o_order_extra_model->insert($order_extra);
      if (!$rs) {
        $this->ci->db->trans_rollback();
        $this->_error = '出错啦,请重新提交4';
        return false;
      }

      if(version_compare($this->_version, '4.1.0') >= 0){
          $address_data = array();
          $address_data['uid'] = $uid;
          if($order_address['address_id']){
              $address_data['id'] = $order_address['address_id'];
          }
          $address_data['store_id'] = $store_id;
          $address_data['mobile'] = $order_address['mobile'];
          $address_data['name'] = $order_address['name'];
          $address_data['lonlat'] = $order_address['lonlat'];
          $address_data['pre_address'] = $order_address['pre_address'];
          $address_data['pro_address'] = $order_address['pro_address'];
          $address_data['address_name'] = $order_address['address_name'];
          $rs = $this->ci->o2o_order_extra_model->addUserAddress($address_data);
          if (!$rs) {
            $this->ci->db->trans_rollback();
            $this->_error = '出错啦,请重新提交6';
            return false;
          }
      }

      //O2O随单赠start
      // $this->ci->load->model('o2o_region_model');
      // $parents = $this->ci->o2o_region_model->getParents($building_id);
      // $province=end($parents);
      // $region_id = $province['area_id'];
      // $GiftSend = array();
      // if($region_id == 106092){
      //     // if(date('Y-m-d',time())>='2015-09-14'){
      //     //     if($data['money']>=10){
      //     //         $GiftSend = $this->O2oGiftSend($uid);
      //     //     }
      //     // }else{
      //         $GiftSend = $this->O2oGiftSend($uid);
      //     // }
      // }
      // if($GiftSend){
      //     $data['cart_info']['items'][] = $GiftSend['items'];
      //     $GiftSend_id = $GiftSend['id'];
      // }
      //o2o随单赠END

      //O2O满5赠1start
      // if(date('Y-m-d H:i:s',time())<='2015-09-30 17:00:00'){
      //     $Gift5to1Send = $this->Gift5to1Send($data['cart_info']['items']);
      //     $Gift5to1Send and $data['cart_info']['items'][] = $Gift5to1Send['items'];
      // }
      //O2O满5赠1END

      // 生成明细
      $rs = $this->orderAddPro($uid,$order_id,$data['cart_info'],$api_version,$c_orders,$store_id);
      if (!$rs) {
        $this->ci->db->trans_rollback();
        $this->_error = '出错啦,请重新提交2';
        return false;
      }
      //O2O随单增赠品状态start
      // if($GiftSend){
      //     $GiftSend_id = $GiftSend['id'];
      //     $this->O2oGiftSended($GiftSend_id,$order_id);
      // }
      //O2O随单增赠品状态end

      // 生成地址
      $rs = $this->orderAddAddr($order_id,$building_id,$data['order_address']);
      if (!$rs) {
        $this->ci->db->trans_rollback();
        $this->_error = '出错啦,请重新提交3';
        return false;
      }

      // 帐户余额扣款
      if($order['pay_parent_id'] == 5) {
          $check = $this->ci->user_model->check_money_identical($uid);
          if($check === false){
              $this->ci->db->trans_rollback();
              $this->ci->user_model->freeze_user($uid);
              $this->_error = '您的账户余额异常';
              return false;
          }
          if(!$this->ci->user_model->cut_user_money($uid,$order['money'],$order['order_name'])){
            $this->ci->db->trans_rollback();
            $this->_error = '出错啦,请重新提交5';
            return false;
          }
          $update_data = array();
          $update_data['update_pay_time'] = date('Y-m-d H:i:s');
          $update_data['pay_time'] = date('Y-m-d H:i:s');
          $update_data['pay_status'] = '1';
          $this->ci->order_model->update($update_data,array('id'=>$order['id']));
      }

      //扣积分
      if( $order['jf_money'] > 0 ){
          $use_jf = $order['jf_money']*100;
          if(!$this->ci->user_model->cut_uses_jf($uid,$use_jf,$order['order_name'])){
              $this->ci->db->trans_rollback();

              $this->_error = '积分扣除失败，请重新提交订单';
              return false;
          }
      }

      // 卡券
      if ($order['use_card']) {
          if($data['can_use_card'] === 0){
              $this->_error = '优惠券使用错误，请取消使用重新提交';
              return false;
          }

          $this->ci->load->model('card_model');
          $card_info = $this->ci->card_model->get_card_info($order['use_card']);

          $content = "订单".$order["order_name"]."抵扣".$order['card_money'];

          if($card_info['can_use_onemore_time']=='false'){
              $card_data = array(
                  'is_used'=>'1',
                  'content'=>$content
              );
              if(!$this->ci->card_model->update_card($card_data,array('card_number'=>$order['use_card']))){
                  $this->ci->db->trans_rollback();

                  $this->_error = '优惠券使用错误，请取消使用重新提交';
                  return false;
              }
          }
      }

      if($device_products){
          $this->ci->o2o_order_extra_model->add_device_limit($device_products,$device_code,$order_id);
      }

      $this->ci->db->trans_commit();

      $this->ci->session->sess_write();

      $user_info = $this->ci->user_model->dump(array('id' =>$uid),'msgsetting,mobile,email');
      $this->_afterCreateOrder($order['id'],$user_info);

      return array('code'=>200,'msg'=>$order['order_name'],'pay_parent_id'=>$order['pay_parent_id'],'money'=>$order['money']);
    }



    /*
    *单品促销验证
    */
    private function checkUserProSale($uid,$pro_sale_result,$order_id){
      if(!empty($pro_sale_result) && isset($pro_sale_result['active_rules'])){
          foreach ($pro_sale_result['active_rules'] as $key => $value) {
            $insert_data = array(
              'uid'=>$uid,
              'device_code'=>'',
              'active_tag'=>$value['active_tag'],
                'order_id' => $order_id
            );
            $this->ci->db->insert('active_limit',$insert_data);
          }
      }
      return false;
    }

    private function _afterCreateOrder($order_id, $user_info){
        $this->ci->load->bll('o2ocart');
        $this->ci->bll_o2ocart->emptyCart();
        // 发短信 AND 邮件
        // $this->ci->load->driver('push_msg');
        // $this->ci->push_msg->set_msgsetting($user_info['msgsetting']);
        // $this->ci->push_msg->order_create->set_order($order_id);
        // $this->ci->push_msg->order_create->send_sms($user_info['mobile']); // 发短信
        // $this->ci->push_msg->order_create->send_email($user_info['email']); // 发邮件
        $this->ci->load->model('active_model');
        $this->ci->active_model->join_tuan_by_order_id($order_id);//8324团购
    }


    private function orderAddAddr($order_id,$building_id,$order_address){
      $name    = $order_address['name'];
      $mobile  = $order_address['mobile'];
      $address = $order_address['address'];
      $this->ci->load->model('order_model');
      $province_id = 0;
      $city_id = 0;
      $area_id = 0;
      if(version_compare($this->_version, '4.1.0') < 0){
          $this->ci->load->model('area_model');
          $this->ci->load->model('o2o_region_model');
          $parents = $this->ci->o2o_region_model->getParents($building_id);

          foreach ($parents as $p) {
            if ($p['attr'] == 1) $province_id = $p['area_id'];
            if ($p['attr'] == 2) $city_id = $p['area_id'];
            if ($p['attr'] == 3) $area_id = $p['area_id'];

            if ($p['attr'] == 4) $bszone = $p;
            if ($p['attr'] == 5) $building = $p;
          }

          $province = $this->ci->area_model->dump(array('id'=>$province_id));
          $city = $this->ci->area_model->dump(array('id'=>$city_id));
          $area = $this->ci->area_model->dump(array('id'=>$area_id));

          $region = $province['name'].$city['name'].$area['name'];
          $order_address = $region.$building['name'].$address;
      }else{
          $region = '';
          if($order_address['province'] && $order_address['city'] && $order_address['area']){
              $province_id = $order_address['province'];
              $city_id = $order_address['city'];
              $area_id = $order_address['area'];
              $region = $order_address['province_name'].$order_address['city_name'].$order_address['area_name'];
          }
          $order_address = $order_address['pre_address'].$order_address['address_name'].$order_address['pro_address'];
      }
      $order_address = array(
        'order_id'  => $order_id,
        'position'  => $region,
        'address'   => $order_address,
        'name'      => $name,
        'email'     => '',
        'telephone' => '',
        'mobile'    => $mobile,
        'province'  => $province_id,
        'city'      => $city_id,
        'area'      => $area_id,
      );

      $insert = $this->ci->order_model->addOrderAddr($order_address);

      return $insert;
    }

    private function _order_score($order)
    {
      $uid = $order['uid'];
      $this->ci->load->model('user_model');
      $order_score = $this->ci->user_model->dill_score_new($order['money'],$uid);
      if($order['pay_parent_id'] == 5){
          $order_score = 0;
      }
      if($order['pay_parent_id'] == 4 && ($order['pay_id']==7 || $order['pay_id']==8 || $order['pay_id']==9)){
          $order_score = 0;
      }

      return $order_score;
    }

    /*
    *订单商品插入
    */
    private function orderAddPro($uid,$order_id,$cart_info,$api_version,$c_orders = array(),$store_id){
        $this->ci->load->model('product_model');
        $this->ci->load->model('order_model');
        $this->ci->load->model('o2o_store_goods_model');

        $opt = $this->ci->config->item('order_product_type');


        $insert = true;

        foreach ($cart_info['items'] as $key => $item) {
          $order_product_type = $opt[$item['item_type']] ? $opt[$item['item_type']] : '1';

          $order_pro_type = 1;
          if ($item['amount'] == 0) $order_pro_type = 3;

          // 判断是否为赠品
          $is_gift = in_array($item['item_type'],array('gift','mb_gift','user_gift','coupon_gift')) ? true : false;
          $score  = $is_gift ? 0 : $this->ci->order_model->get_order_product_score($uid,$item);
            $order_product_id = array();
          if ($item['group_pro'] && $group_pro = explode(',',$item['group_pro'])) { // 组合商品
            $rows = $this->ci->product_model->getGroupProducts($group_pro);

            foreach ($rows as $row) {
              $order_product_data = array(
                'order_id'     => $order_id,
                'product_name' => addslashes($row['product_name']),
                'product_id'   => $row['id'],
                'product_no'   => $row['product_no'],
                'gg_name'      => $row['volume'] . '/' . $row['unit'],
                'price'        => $is_gift ? 0 : $row['price'],
                'qty'          => $item['qty'],
                'score'        => $score,
                'type'         => $order_pro_type,
                'total_money'  => $is_gift ? 0 : $item['qty'] * $row['price'],
                'group_pro_id' => $item['product_id'],
              );

              $insert = $this->ci->order_model->addOrderProduct($order_product_data);
                array_push($order_product_id, $insert);
              if (!$insert) return false;
            }
          } else {
            $order_product_data = array(
              'order_id'     => $order_id,
              'product_name' => addslashes($item['name']),
              'product_id'   => $item['product_id'],
              'product_no'   => $item['product_no'],
              'gg_name'      => $item['spec'].'/'.$item['unit'],
              'price'        => $item['price'],
              'qty'          => $item['qty'],
              'score'        => $score,
              'type'         => $order_pro_type,
              'total_money'  => $item['amount'],
            );
            $insert = $this->ci->order_model->addOrderProduct($order_product_data);
              array_push($order_product_id, $insert);
            if (!$insert) return false;
          }

          if(in_array($item['item_type'], array('mb_gift','gift','user_gift')) && !$item['store_id']){
              $item['store_id'] = $store_id;
          }
          // 扣减库存
          $this->ci->load->model('o2o_store_goods_model');
          if($item['store_id'] && $item['product_id']){
            $this->ci->o2o_store_goods_model->stockSub($item['store_id'],$item['product_id'],$item['qty']);
          }

          // 赠品置状态
          if ($is_gift && $item['user_gift_id']) $this->ci->order_model->receive_user_gift($uid,$order_id,$item['user_gift_id']);
          if($api_version >= '2.2.0'){
              $PhysicalStore_id = $this->formatPhysicalStore($item['store_id']);
              //$this->o2oBranchProduct($order_id , $item , addslashes($item['name']) , $item['store_id'] , $order_pro_type , $cart_info['items']);
              foreach($order_product_id as $opid){
                  $this->o2oSingleOrderProduct($opid,$item['store_id'],$c_orders[$PhysicalStore_id]);
              }

          }
        }

        return $insert;
    }

    /**
     * 取消兑换券
     *
     * @return void
     * @author
     **/
    public function cancelcoupon($coupon)
    {
    }

    /**
     * 使用兑换券
     *
     * @return void
     * @author
     **/
    public function exchgcoupon($coupon)
    {
    }

    //搜索楼宇
    public function searchBuilding($building_name,$region_id=0){
        $where = '';
        if($region_id){
            $site_list = $this->ci->config->item("site_list");
            $province_id = isset($site_list[$region_id]) ? $site_list[$region_id] : $region_id;
            $sql = "SELECT id,pid FROM ttgy_o2o_region where area_id = ".intval($province_id);
            $row = $this->ci->db->query($sql)->row_array();
            if($row['id']){
                $where = " and path like ',".$row['id'].",%'";
            }
        }
        $sql = "SELECT id,name,attr,pid,latitude,longitude,addr FROM ttgy_o2o_region WHERE attr=5 and name like '%".$building_name."%'".$where;
        $rows = $this->ci->db->query($sql)->result_array();
        $data = array();
        $this->ci->load->model('o2o_store_building_model');
        $this->ci->load->model('o2o_region_model');
        $this->ci->load->model('o2o_store_model');
        $attr = $this->ci->o2o_region_model->attr;
        foreach ($rows as $key => $row) {
            $a_data = array();
            $store_ids = $this->ci->o2o_store_building_model->getList('store_id',array('building_id'=>$row['id']));
            if (!$store_ids) {
                continue;
            }
            $s_id = array();
            foreach ($store_ids as $value) {
                $s_id[] = $value['store_id'];
            }
            $store = $this->ci->o2o_store_model->dump(array('id'=>$s_id,'isopen'=>1));
            if(empty($store)){
                continue;
            }

            $a_data['name'] = preg_replace('/（.*）/', '', $row['name']);
            $a_data['id'] = $row['id'];
            $a_data['store'] = $store['id'] ? array('id'=>$store['id']) : '{}';
            $a_data['latitude'] = $row['latitude'];
            $a_data['longitude'] = $row['longitude'];
            $a_data['addr']      = $row['addr'];
            $a_data['stores']      = $s_id;

            // 获取行政区
            $parents = $this->ci->o2o_region_model->getParents($row['pid']);
            foreach ($parents as $p) {
              $a_data[$attr[$p['attr']]]['id']   = $p['id'];
              $a_data[$attr[$p['attr']]]['name'] = $p['name'];
            }
            $data[] = $a_data;
        }
        return $data;
    }

    public function getStroeInfo($store_id){
        $info = array();

        $this->ci->load->model('o2o_store_model');


        $data = array();
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $this->cache_prefix."sinfo_store_".$store_id;
            $data = $this->ci->memcached->get($mem_key);
        }
        if(empty($data)){
            $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
            if($store){
                $data['id'] = $store['id'];
                $data['name'] = $store['name'];
                $opentime = unserialize($store['opentime']);
                $s_opentime = $opentime['AM']['h'].":".$opentime['AM']['m'];
                $e_opentime = $opentime['PM']['h'].":".$opentime['PM']['m'];
                $data['opentime'] = $s_opentime."-".$e_opentime;
                $data['phone'] = $store['phone'];
                $data['address'] = $store['address'];
                $data['post'] = '免费送';
                $cutofftime = $store['cutofftime'];
                $cutofftime = strpos($cutofftime,"：") ? $cutofftime : $cutofftime."：00";
                $data['send_time'] = $cutofftime . '前下单，' . $this->getDefaultSendTime($store['id'], 'time') . '小时内送达；' . $cutofftime . '后下单，第二天配送';
                $data['isopen'] = $store['isopen'];
                if(date('H:i') >= $s_opentime  && date('H:i') <= $e_opentime){
                    $data['in_opentime'] = 1;
                }else{
                    $data['in_opentime'] = 0;
                }
                $data['logo'] = $store['logo']?PIC_URL.$store['logo']:'';
                $data['order'] = $store['order'];
                if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                    if(!$this->ci->memcached){
                        $this->ci->load->library('memcached');
                    }
                    $mem_key = $this->cache_prefix."sinfo_store_".$store_id;
                    $this->ci->memcached->set($mem_key,$data,600);
                }
            }
        }
        $banner = array();
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $this->cache_prefix."sinfo_banner_".$store_id;
            $banner = $this->ci->memcached->get($mem_key);
        }
        if(empty($banner)){
            $this->ci->load->library('terminal');
            $channel = $this->ci->terminal->get_channel();
            $banner = $this->ci->o2o_store_model->get_o2o_store_banner($store_id, null, null, $channel, 0);
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                if(!$this->ci->memcached){
                    $this->ci->load->library('memcached');
                }
                $mem_key = $this->cache_prefix."sinfo_banner_".$store_id;
                $this->ci->memcached->set($mem_key,$banner,600);
            }
        }
        foreach($banner as $k => $v){
            //门店配送提示banner
            if($v['type'] == 18){
                if(!isset($info['delivery_banner'])){
                    $v['type'] = 0;
                    $info['delivery_banner'] = $v;
                }
                unset($banner[$k]);
            }
        }
        $info['store_info'] = $data;
        $info['store_banner'] = array_values($banner);
        $info['delivery_banner'] = !empty($info['delivery_banner']) ? $info['delivery_banner'] : array();

        return $info;
    }

    /**
     * 新定位最近的楼宇
     *
     * @return void
     * @author
     **/
    public function nearbyBuilding_new($longitude,$latitude,$type)
    {
      $data = array();
      $this->ci->load->model('o2o_store_model');
      $stores = $this->ci->o2o_store_model->getList('id,range',array('isopen' => 1),0,-1,'order desc');
      $s_ids = array();
      $this->ci->load->model('o2o_store_building_model');
      foreach ($stores as $store) {
         $range = $store['range'];
         $range = unserialize($range);
         if($longitude<=$range['max_longitude'] && $longitude>=$range['min_longitude'] && $latitude<=$range['max_latitude'] && $latitude>=$range['min_latitude']){
            $s_ids[] = $store['id'];
            if($this->ci->o2o_store_building_model->dump(array('store_id'=>$store['id']))){
               break;
            }
         }
      }
      if(empty($s_ids)) return $data;

      $store_buildings = $this->ci->o2o_store_building_model->getList('building_id',array('store_id'=>$s_ids));
      $b_ids = array();
      foreach ($store_buildings as $s_building) {
          $b_ids[] = $s_building['building_id'];
      }
      if(empty($b_ids)) return $data;
      $this->ci->load->model('o2o_region_model');
      $buildings = $this->ci->o2o_region_model->getList('*',array('id'=>$b_ids,'attr'=>5,'is_delete'=>0));
      $attr = $this->ci->o2o_region_model->attr;
      foreach ($buildings as $key => $value) {
          $store_building = $this->ci->o2o_store_building_model->getList('distinct(store_id)',array('building_id' => $value['id']));
          if (!$store_building) continue;
          $store_ids = array();
          foreach ($store_building as $s_id) {
              $store_ids[] = $s_id['store_id'];
          }
          $d = array(
            'id'        => $value['id'],
            'pid'       => $value['pid'],
            'name'      => $value['name'],
            'latitude'  => $value['latitude'],
            'longitude' => $value['longitude'],
            'store'     => array('id'=>$store_ids[0]),
            'addr'      => $value['addr'],
            'stores'    => $store_ids,
          );
          $parents = $this->ci->o2o_region_model->getParents($value['pid']);
          foreach ($parents as $p) {
            $d[$attr[$p['attr']]]['id']   = $p['id'];
            $d[$attr[$p['attr']]]['name'] = $p['name'];
          }
          $distance = & getDistance($latitude, $longitude, $value['latitude'], $value['longitude']);
          $d['distance'] = $distance;
          $data[] = $d;
      }

        foreach ($data as $key => $value) {
           $sort_array[] = $value['distance'];
        }
        array_multisort($sort_array,SORT_ASC,$data);
        $data = array_slice($data,0,50);
      if($type == 2){
        $data = array_slice($data,0,20);
      }
      foreach ($data as $key => $value) {
          if($value['distance']>1000){
            $data[$key]['distance'] = number_format($value['distance']/1000,1)."千米";
          }else{
            $data[$key]['distance'] .=  "米";
          }
      }
      return $data;
    }

    public function getStoreList($building_id){
        $this->ci->load->model('o2o_store_building_model');
        $store_ids = $this->ci->o2o_store_building_model->getList('distinct(store_id)',array('building_id'=>$building_id));
        $data = array();
        foreach ($store_ids as $value) {
            $store_info = array();
            $store_id = $value['store_id'];
            $store_info = $this->getStroeInfo($store_id);
            if($store_info['store_info']['isopen'] == 0){
                continue;
            }
            $store_info['store_info']['sales'] = '';//销量  留空
            $products = $this->storeproducts($store_id);
            $store_info['products'] = $products;
            $data['store'][] = $store_info;
        }
        $sort_array = array();
        $sort_data = $data['store'];
        foreach($sort_data as $key=>$value){
          $sort_array[] = $value['store_info']['order'];
        }
        array_multisort($sort_array,SORT_DESC,SORT_NUMERIC,$sort_data);
        $data['store'] = $sort_data;
        return $data;
    }

    public function getStoreListByIDs($store_ids){
        $data = array();
        foreach ($store_ids as $store_id) {
            $store_info = array();
            $store_info = $this->getStroeInfo($store_id);
            if($store_info['store_info']['isopen'] == 0){
                continue;
            }
            $store_info['store_info']['sales'] = '';//销量  留空
            $products = $this->storeproducts($store_id);
            $store_info['products'] = $products;
            $data['store'][] = $store_info;
        }
        return $data;
    }

    public function getStoreGoodsInfo($store_id){
        $store_info = array();
        $store_info = $this->getStroeInfo($store_id);
        $products = $this->storeproducts($store_id);
        $store_info['products'] = $products;
        return $store_info;
    }

    public function getStoreOtherGoods($store_id,$product_id,$nums){
        $products = $this->storeproducts($store_id);
        foreach ($products as $key => $value) {
            if($value['product_id'] == $product_id){
                unset($products[$key]);
            }
        }
        $data = array_slice($products,0,$nums);
        return $data;
    }

    function getChildRate($items,$goods_money,$stime='am'){
        $part_goods_money = array();
        foreach ($items as $value) {
             if(empty($value['store_id'])){
                continue;
             }
             $PhysicalStore_id = $this->formatPhysicalStore($value['store_id']);
             $part_goods_money[$PhysicalStore_id] += $value['price'] * $value['qty'];
             $this->o2oChildGoodsMoney[$PhysicalStore_id] = $part_goods_money[$PhysicalStore_id];
             if($this->checkBreakfast($value['product_id'],$stime)){
                $this->_send_type[$PhysicalStore_id] = 2;
             }
        }
        foreach ($part_goods_money as $key => $value) {
            if($goods_money == 0) $this->o2oChildRate[$key] = 0;
            else $this->o2oChildRate[$key] = $value / $goods_money;
        }
    }

    function o2oSingleOrder($order,$order_address,$building_id=0){
        $c_nums = count($this->o2oChildRate);
        $i = 1;
        $check_card = false;
        $last_jf_money = 0;
        $last_use_money_deduction = 0;
        $last_method_money = 0;
        $last_score = 0;
        $last_money = 0;
        $c_orders = array();
        $base_money = $order['money'];
        if($order['card_money']>0 && $order['use_card']){
            $order['money'] = $order['money']+$order['card_money'];
        }
        foreach ($this->o2oChildRate as $key=>$value) {
            $insert_data =  array();
            $insert_data['p_order_id'] = $order['id'];
            $insert_data['uid'] = $order['uid'];
            if($c_nums > 1){
                $insert_data['order_name'] = $order['order_name']."-".$i;
            }else{
                $insert_data['order_name'] = $order['order_name'];
            }
            $insert_data['store_id'] = $key;
            $insert_data['building_id'] = $building_id;
            $insert_data['goods_money'] = $this->o2oChildGoodsMoney[$key];

            if($check_card === false && $this->check_TTGY_Order()){
                if(round($order['money'] * $value)-$order['card_money']>=0){
                    $insert_data['card_money'] = $order['card_money'];
                    $insert_data['use_card'] = $order['use_card'];
                    $check_card = true;
                }else{
                    $insert_data['card_money'] = 0;
                    $insert_data['use_card'] = '';
                }
            }else{
                $insert_data['card_money'] = 0;
                $insert_data['use_card'] = '';
            }
            $insert_data['pmoney'] = $this->o2oChildGoodsMoney[$key];
            $insert_data['operation_id'] = 0;

            if($i == $c_nums){
                $insert_data['jf_money'] = $order['jf_money'] - $last_jf_money;
                $insert_data['use_money_deduction'] = $order['use_money_deduction'] - $last_use_money_deduction;
                $insert_data['pay_discount'] = $order['pay_discount'] - $last_pay_discount;
                $insert_data['method_money'] = $order['method_money'] - $last_method_money;
                $insert_data['score'] = $order['score'] - $last_score;
                $insert_data['money'] = $base_money - $last_money;

            }else{
                $insert_data['jf_money'] = round($order['jf_money'] * $value);
                $insert_data['use_money_deduction'] = round($order['use_money_deduction'] * $value);
                $insert_data['pay_discount'] = round($order['pay_discount'] * $value);
                $insert_data['method_money'] = round($order['method_money'] * $value);
                $insert_data['score'] = round($order['score'] * $value);
                $insert_data['money'] = round($order['money'] * $value)-$insert_data['card_money'];

                $last_jf_money += $insert_data['jf_money'];
                $last_use_money_deduction += $insert_data['use_money_deduction'];
                $last_pay_discount += $insert_data['pay_discount'];
                $last_method_money += $insert_data['method_money'];
                $last_score += $insert_data['score'];
                $last_money += $insert_data['money'];
            }

            $insert_data['pay_status'] = ($insert_data['money']==0)?1:0;
            if($order['pay_parent_id'] ==5){
                $insert_data['pay_status'] = 1;
            }
            $insert_data['address'] = $order_address;
            $insert_data['send_type'] = $this->_send_type[$key]?$this->_send_type[$key]:1;
            $this->ci->load->model('o2o_child_order_model');
            $c_order_id = $this->ci->o2o_child_order_model->insert($insert_data);
            $i++;
            $c_orders[$key] = $c_order_id;
        }
        return $c_orders;
    }

    function o2oSingleOrderProduct($order_product_id,$store_id,$c_order_id){
        $insert_data = array();
        $insert_data['order_product_id'] = $order_product_id;
        $insert_data['store_id'] = $store_id;
        $insert_data['c_order_id'] = $c_order_id;
        $this->ci->load->model('o2o_order_product_extra_model');
        $rs = $this->ci->o2o_order_product_extra_model->insert($insert_data);
    }

    function check_TTGY_Order(){
        return true;
    }

    function O2oMbGift($items,$uid,$building_id){
        $new_items = array();
        foreach ($items as $key => $value) {
            if($value['item_type'] == 'mb_gift'  &&  in_array($value['product_id'],array(5168,5262,5251,5168,5291,5397,5357))){
                $sql = "select o.id from ttgy_order o join ttgy_order_product p on o.id=p.order_id where o.order_status=1 and o.operation_id<>5 and date_format(o.time,'%Y-%m-%d')=CURDATE() and p.product_id=".$value['product_id']." and o.uid=".$uid;
                $res = $this->ci->db->query($sql)->row_array();
                if($res){
                    continue;
                }
            }
            $new_items[] = $value;
        }
        return $new_items;
	}

  function O2oGiftSend($uid){
      $data = array();
      $now = date("Y-m-d H:i:s",time());
      //$today_end = date('Y-m-d 23:59:59',time());
      $sql = "select * from ttgy_blow_gifts where begin<='".$now."' and end>='".$now."' and uid=".$uid." and is_used=0 order by id desc limit 1";
      $result = $this->ci->db->query($sql)->row_array();
      if(empty($result)){
          $this->ci->load->model('user_model');
          $user = $this->ci->user_model->selectUser('mobile', array('id'=>$uid));
          $sql = "select * from ttgy_blow_gifts where begin<='".$now."' and end>='".$now."' and mobile='".$user['mobile']."' and is_used=0 order by id desc limit 1";
          $result = $this->ci->db->query($sql)->row_array();
      }
      if($result['product_id']){
          $this->ci->load->model('product_price_model');
          $product_price = $this->ci->product_price_model->dump(array('product_id'=>$result['product_id']));
          $product = $this->ci->db->query("select * from ttgy_product where id=".$result['product_id'])->row_array();
          $add_items = array(
              'name' => $product['product_name'],
              'product_id'   => $result['product_id'],
              'product_no'   => $product_price['product_no'],
              'spec'         => $product_price['volume'],
              'unit'         => $product_price['unit'],
              'price'        => 0,
              'qty'          => 1,
              'amount'       => 0,
              'item_type'    => 'gift',
            );
          $data = array('id'=>$result['id'],'items'=>$add_items);
      }
      return $data;
  }

  function O2oGiftSended($id,$order_id){
      $sql = "update ttgy_blow_gifts set is_used=1 ,order_id=".$order_id." where id=".$id;
      $this->ci->db->query($sql);
  }

  function Gift5to1Send($items){
      $data = array();
      foreach ($items as $key => $value) {
          if($value['product_id'] == 6026){
              $gift_id = 6027;
              $gift_qty = floor($value['qty']/1);
              if($gift_qty>0){
                  $this->ci->load->model('product_price_model');
                  $product_price = $this->ci->product_price_model->dump(array('product_id'=>$gift_id));
                  $product = $this->ci->db->query("select * from ttgy_product where id=".$gift_id)->row_array();
                  $add_items = array(
                    'name' => $product['product_name'],
                    'product_id'   => $gift_id,
                    'product_no'   => $product_price['product_no'],
                    'spec'         => $product_price['volume'],
                    'unit'         => $product_price['unit'],
                    'price'        => 0,
                    'qty'          => $gift_qty,
                    'amount'       => 0,
                    'item_type'    => 'gift',
                  );
                  $data = array('id'=>$gift_id,'items'=>$add_items);
              }
          }
      }
      return $data;
  }

    function getHomePageInfo($building_id=0,$latitude='',$longitude='',$version,$region_id){
        $data = array();
        $this->ci->load->model('o2o_store_building_model');
        $building_ids = array();
        $s_ids = array();
        if($building_id == 0){
            $this->ci->load->model('o2o_store_model');
            $this->ci->load->library('geohash');
            $this->ci->load->model('o2o_store_building_model');
            $this->ci->load->model('o2o_region_model');
            $hash = $this->ci->geohash->encode($latitude,$longitude);
            $hash = substr($hash,0,6);
            $neighbors = $this->ci->geohash->neighbors($hash);
            $neighbors['self'] = $hash;
            $result = array();
            foreach ($neighbors as $value) {
                $sql = "select * from ttgy_o2o_region where attr=5 and geohash like '".$value."%'";
                $region = $this->ci->db->query($sql)->result_array();
                $result = array_merge($result,$region);
            }
            foreach ($result as $key => $value) {
                $sql="select distinct(sb.store_id) from ttgy_o2o_store_building sb join ttgy_o2o_store s on s.id=sb.store_id where s.isopen=1 and sb.building_id=".$value['id'];
                $store_building = $this->ci->db->query($sql)->result_array();
                if (!$store_building) continue;
                $building_ids[] = $value['id'];
                foreach ($store_building as $s_building) {
                    $s_ids[] = $s_building['store_id'];
                }
            }
            // $stores = $this->ci->o2o_store_model->getList('id,range',array('isopen' => 1),0,-1,'order desc');
            // foreach ($stores as $store) {
            //     $range = $store['range'];
            //     $range = unserialize($range);
            //     if($range && $latitude && $longitude){
            //         if($longitude<=$range['max_longitude'] && $longitude>=$range['min_longitude'] && $latitude<=$range['max_latitude'] && $latitude>=$range['min_latitude']){
            //             $s_ids[] = $store['id'];
            //         }
            //     }
            // }
            // if($s_ids){
            //     $store_building = $this->ci->o2o_store_building_model->getList('building_id',array('store_id'=>$s_ids));
            //     foreach ($store_building as $key => $value) {
            //         $building_ids[] = $value['building_id'];
            //     }
            // }
            $building_ids = array_unique($building_ids);
            $s_ids = array_unique($s_ids);
            $this->ci->load->library('login');
            if($this->ci->login->is_login()){
                $last_order_info = $this->getLastOrderInfo($building_ids);
                if($last_order_info['order_address']){
                    $data['last_address'] = $last_order_info['order_address'];
                    $data['no_building'] = $last_order_info['no_building'];
                    $building_id = $data['last_address']['building']['id']?array($data['last_address']['building']['id']):array();
                }
            }
        }

        $store_info = array();
        if($building_id){
            $store_info = $this->getStoreList($building_id);
        }elseif($s_ids){
            $store_info = $this->getStoreListByIDs($s_ids);
        }

        if($store_info){
            $banner = array();
            $has_much = 0;
            foreach ($store_info['store'] as $key => $value) {
                $store_info['store'][$key]['products'] = array_slice($value['products'],0,20);
                $banner = array_merge($value['store_banner'],$banner);
                unset($store_info['store'][$key]['store_banner']);
                if(mb_substr($value['store_info']['name'], 0,4,'UTF-8') == '天天果园'){
                    $has_much ++;
                }
            }
            if($has_much>=2){
                $data['has_much'] = '1';
            }
            $a_banner = array();
            $data_banner = array();
            foreach ($banner as $key => $value) {
                $arr = array('type'=>$value['type'],'target_id'=>$value['target_id'],'page_url'=>$value['page_url'],'title'=>$value['title']);
                if(in_array($arr, $a_banner)){
                    continue;
                }
                $a_banner[] = $arr;
                $data_banner[] = $value;
            }
            $data['banner'] = $data_banner;
            $data['store'] = $store_info['store'];

            // $product_type[] = array('id'=>0,'name'=>'所有类型');
            $data['product_type'] = $this->getProductType($store_info['store'][0]['store_info']['id']);
        }
        $base_banner = array();
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $this->cache_prefix."base_banner";
            $base_banner = $this->ci->memcached->get($mem_key);
        }
        if(empty($base_banner)){
            $this->ci->load->model('o2o_base_banner_model');
            $base_banner = $this->ci->o2o_base_banner_model->getList('*',array('is_show' => 1),0,-1,'sort asc,id desc');
            foreach ($base_banner as &$value) {
                $value['photo'] = $value['photo']?PIC_URL.$value['photo']:'';
            }
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                if(!$this->ci->memcached){
                    $this->ci->load->library('memcached');
                }
                $mem_key = $this->cache_prefix."base_banner";
                $this->ci->memcached->set($mem_key,$base_banner,600);
            }
        }
        $data['base_banner'] =  $base_banner;
        if($this->ci->login->is_login() && $version>='3.2.0'){
            $this->ci->load->bll('o2ocart');
            $this->ci->bll_o2ocart->set_province($region_id,$building_id,$store_info['store'][0]['store_info']['id']);
            $this->ci->bll_o2ocart->checkCartInit($latitude,$longitude,$building_id,$store_info,true);
        }
        return $data;
    }

    function getHomePageInfo_V2($region_id,$store_id = 0, $lonlatForO2O){
        $data = array();
        $data['no_building'] = 0;
        $data['supportDelivery'] = 1;
        if(empty($store_id)){
            $data['no_building'] = 1;
            $data['supportDelivery'] = 0;
        }
        $this->ci->load->library('login');
        if($this->ci->login->is_login()){
            $uid = $this->ci->login->get_uid();
            if($uid){
                $last_order_info = $this->getLastOrderInfo_v2($uid,$store_id);
                if($last_order_info['order_address'] && !$lonlatForO2O && (!$store_id || $last_order_info['order_address']['storeId'] == $store_id)){
                    $data['last_address'] = $last_order_info['order_address'];
                    $store_id = $last_order_info['order_address']['storeId'];
                }
            }
        }
        if($store_id){
            $data['product_type'] = $this->getProductType($store_id);
            $store_info = $this->getStoreListByIDs(array($store_id));
        }
        if($store_info){
            $banner = array();
            $has_much = 0;
            foreach ($store_info['store'] as $key => $value) {
                $store_info['store'][$key]['products'] = array_slice($value['products'],0,20);
                $banner = array_merge($value['store_banner'],$banner);
                unset($store_info['store'][$key]['store_banner']);
                if(mb_substr($value['store_info']['name'], 0,4,'UTF-8') == '天天果园'){
                    $has_much ++;
                }
            }
            if($has_much>=2){
                $data['has_much'] = '1';
            }
            $a_banner = array();
            $data_banner = array();
            foreach ($banner as $key => $value) {
                $arr = array('type'=>$value['type'],'target_id'=>$value['target_id'],'page_url'=>$value['page_url'],'title'=>$value['title']);
                if(in_array($arr, $a_banner)){
                    continue;
                }
                $a_banner[] = $arr;
                $data_banner[] = $value;
            }
            $data['banner'] = $data_banner;
            $data['store'] = $store_info['store'];

            // $product_type[] = array('id'=>0,'name'=>'所有类型');
            $data['product_type'] = $this->getProductType($store_info['store'][0]['store_info']['id']);
        }
        if(empty($base_banner)){
            $this->ci->load->model('o2o_base_banner_model');
            $base_banner = $this->ci->o2o_base_banner_model->getList('*',array('is_show' => 1),0,-1,'sort asc,id desc');
            foreach ($base_banner as &$value) {
                $value['photo'] = $value['photo']?PIC_URL.$value['photo']:'';
            }
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                if(!$this->ci->memcached){
                    $this->ci->load->library('memcached');
                }
                $mem_key = $this->cache_prefix."base_banner";
                $this->ci->memcached->set($mem_key,$base_banner,600);
            }
        }
        $data['base_banner'] =  $base_banner;
        if($this->ci->login->is_login()){
            $this->ci->load->bll('o2ocart');
            $this->ci->bll_o2ocart->set_province($region_id,0,$store_id);
            $this->ci->bll_o2ocart->checkCartInit_v2($store_id,true);
        }
        return $data;
    }

    public function o2oProductList($building_id=0,$latitude='',$longitude='',$store_id=0,$product_type=0){
        $store_ids = array();
        $this->ci->load->model('o2o_store_building_model');
        $this->ci->load->model('o2o_store_model');
        if($store_id == 0){
            $stores = $this->ci->o2o_store_model->getList('id,range',array('isopen' => 1),0,-1,'order desc');
            if($building_id == 0){
                $this->ci->load->library('geohash');
                $hash = $this->ci->geohash->encode($latitude,$longitude);
                $hash = substr($hash,0,6);
                $neighbors = $this->ci->geohash->neighbors($hash);
                $neighbors['self'] = $hash;
                $result = array();
                foreach ($neighbors as $value) {
                    $sql = "select * from ttgy_o2o_region where attr=5 and geohash like '".$value."%'";
                    $region = $this->ci->db->query($sql)->result_array();
                    $result = array_merge($result,$region);
                }
                $building_id = array();
                foreach ($result as $key => $value) {
                    $building_id[] = $value['id'];
                }
                // foreach ($stores as $store) {
                //     $range = $store['range'];
                //     $range = unserialize($range);
                //     if($longitude<=$range['max_longitude'] && $longitude>=$range['min_longitude'] && $latitude<=$range['max_latitude'] && $latitude>=$range['min_latitude']){
                //         $store_ids[] = $store['id'];
                //     }
                // }
            }
            $store_building = $this->ci->o2o_store_building_model->getList('distinct(store_id)',array('building_id'=>$building_id));
            $s_ids = array();
            foreach ($store_building as $key => $value) {
                $s_ids[] = $value['store_id'];
            }
            $open_stores = array();
            foreach ($stores as $key => $value) {
                $open_stores[] = $value['id'];
            }
            $store_ids = array_intersect($open_stores,$s_ids);
        }else{
            $store_ids[] = $store_id;
        }
        $data = array();
        foreach ($store_ids as $store_id) {
            $info = array();
            //$info['store'] = $this->ci->o2o_store_model->dump(array('id'=>$store_id),'id,name');
            $store_info = $this->getStroeInfo($store_id);
            if($store_info['store_info']['isopen'] == 0){
                continue;
            }
            $store_info['store_info']['sales'] = '';//销量  留空
            $info = $store_info;
            $info['products'] = $this->storeproducts($store_id,$product_type);
            $data[] = $info;
        }
        return $data;
    }

    public function o2oProductList_v2($store_id = 0,$product_type = 0){
        $data = array();
        if(empty($store_id)) return $data;
        $info = array();
        $store_info = $this->getStroeInfo($store_id);
        if($store_info['store_info']['isopen'] == 0){
            return $data;
        }
        $store_info['store_info']['sales'] = '';//销量  留空
        $info = $store_info;
        $info['products'] = $this->storeproducts($store_id,$product_type);
        $data[] = $info;
        return $data;
    }

    function productInfo($id,$store_id) {
        $this->ci->load->model('o2o_store_goods_model');
        $result = $this->ci->o2o_store_goods_model->getO2oProduct($id, $store_id);
        return $result;
    }

    function formatPhysicalStore($store_id){
        $this->ci->load->model('o2o_store_model');
        $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id),'physical_store_id');
        return $store['physical_store_id']?$store['physical_store_id']:0;
    }

    function checkBreakfast($pid,$stime){
        $sql = "SELECT product_type FROM ttgy_product where id = ".intval($pid);
        $row = $this->ci->db->query($sql)->row_array();
        if($row && $row['product_type'] == 6 && $stime == 'am'){
            return true;
        }
        return false;
    }

    function check_building_goods($cart_items,$building_id){
        $this->ci->load->model('o2o_store_building_model');
        foreach ($cart_items as $item) {
            if($item['store_id'] && $item['item_type'] == 'o2o'){
                $store_filter = array('building_id'=>$building_id,'store_id'=>$item['store_id']);
                $store_building = $this->ci->o2o_store_building_model->dump($store_filter);
                if(!$store_building) return false;
            }
        }
        return true;
    }

    function check_store_goods($cart_items,$store_id){
        foreach ($cart_items as $item) {
            if($item['store_id'] && $item['item_type'] == 'o2o'){
                if($item['store_id'] != $store_id) return false;
            }
        }
        return true;
    }

    function nearbyBuilding_geohash($longitude,$latitude,$type){
        $data = array();
        $this->ci->load->library('geohash');
        $this->ci->load->model('o2o_store_building_model');
        $this->ci->load->model('o2o_region_model');
        $hash = $this->ci->geohash->encode($latitude,$longitude);
        $hash = substr($hash,0,6);
        $neighbors = $this->ci->geohash->neighbors($hash);
        $neighbors['self'] = $hash;
        $result = array();
        foreach ($neighbors as $value) {
            $sql = "select * from ttgy_o2o_region where attr=5 and geohash like '".$value."%'";
            $region = $this->ci->db->query($sql)->result_array();
            $result = array_merge($result,$region);
        }
        foreach ($result as $key => $value) {
            ///$store_building = $this->ci->o2o_store_building_model->getList('distinct(store_id)',array('building_id' => $value['id']));
            $sql="select distinct(sb.store_id) from ttgy_o2o_store_building sb join ttgy_o2o_store s on s.id=sb.store_id where s.isopen=1 and sb.building_id=".$value['id'];
            $store_building = $this->ci->db->query($sql)->result_array();
            if (!$store_building) continue;
            $store_ids = array();
            foreach ($store_building as $s_id) {
                $store_ids[] = $s_id['store_id'];
            }
            $d = array(
              'id'        => $value['id'],
              'pid'       => $value['pid'],
              'name'      => $value['name'],
              'latitude'  => $value['latitude'],
              'longitude' => $value['longitude'],
              'store'     => array('id'=>$store_ids[0]),
              'addr'      => $value['addr'],
              'stores'    => $store_ids,
            );
            $parents = $this->ci->o2o_region_model->getParents($value['pid']);
            foreach ($parents as $p) {
              $d[$attr[$p['attr']]]['id']   = $p['id'];
              $d[$attr[$p['attr']]]['name'] = $p['name'];
            }
            $distance = & getDistance($latitude, $longitude, $value['latitude'], $value['longitude']);
            $d['distance'] = $distance;
            $data[] = $d;
        }
        foreach ($data as $key => $value) {
           $sort_array[] = $value['distance'];
        }
        array_multisort($sort_array,SORT_ASC,$data);
        $data = array_slice($data,0,50);
        if($type == 2){
          $data = array_slice($data,0,20);
        }
        foreach ($data as $key => $value) {
            if($value['distance']>1000){
              $data[$key]['distance'] = number_format($value['distance']/1000,1)."千米";
            }else{
              $data[$key]['distance'] .=  "米";
            }
        }
        return $data;
    }

    private function get_cart_store($items){
        foreach ($items as $value) {
            if($value['store_id']){
                return $value['store_id'];
            }
        }
        return false;
    }


    public function post_fee($cart,$goods_money){
        $method_money = 0;
        $this->ci->load->model('order_model');
        $check_result = $this->ci->order_model->check_cart_pro_status($cart);
        if($check_result['free_post']=='1'){
            $method_money = 0;
        }else{
            $postFee = $this->ci->order_model->getO2oPostFee();
            $free_post_money_limit = $postFee['limit'];//o2o包邮金额
            if($goods_money>=$free_post_money_limit){
                $method_money = 0;
            }else{
                $method_money = $postFee['money'];
            }
        }
        return $method_money;
    }

    public function pageListProducts($page_type,$target_id,$store_id){
        $this->ci->load->model('banner_model');
        $field = 'title,product_id,photo,recommend_id,rotation_id,flash_sale,advance';
        $where = array(
          'id'=>$target_id,
          'page_type'=>$page_type,
        );
        $result_list = $this->ci->banner_model->selectPage($field,$where);
        $result = $result_list[0];
        $product_id_arr = explode(',', $result['product_id']);
        $return_result = array();
        switch ($page_type){
            case '17':                   #列表页
                $product_arr = $this->storeproducts($store_id,0,$product_id_arr);
                $return_result['products'] = $product_arr;
                $return_result['title'] = $result['title'];
                if(isset($result['photo']) && !empty($result['photo'])){
                  $return_result['page_photo'] = PIC_URL.$result['photo'];
                }
                //推荐位
                if(!empty($result['recommend_id'])){
                  $rec_data = array(
                      'region_id'=>$region,
                      'source'=>$source,
                      'channel'=>$channel,
                      'ids'=>array($result['recommend_id']),
                    );
                  $return_result['recommend']  = $this->ci->banner_model->get_banner_list($rec_data);
                }
                //轮播位
                if(!empty($result['rotation_id'])){
                  $rot_data = array(
                      'region_id'=>$region,
                      'source'=>$source,
                      'channel'=>$channel,
                      'ids'=>explode(",", $result['rotation_id']),
                    );
                  $return_result['rotation']  = $this->ci->banner_model->get_banner_list($rot_data);
                }
                break;
            default:
                # code...
                break;
        }
        return $return_result;
    }

    public function getGoodsDetailBanner($store_id,$banner_id = 0,$nums=1){
        $banner = array();
        $this->ci->load->model('o2o_store_model');
        $filter = array();
        if($banner_id){
            $filter['b.id <>']=$banner_id;
        }
        $this->ci->load->library('terminal');
        $channel = $this->ci->terminal->get_channel();
        $banners = $this->ci->o2o_store_model->get_o2o_store_banner($store_id,$filter,null, $channel);
        if($banners){
            shuffle($banners);
            $rand_banners = array_chunk($banners,$nums);
            $banner = $rand_banners[0];
        }
        return $banner;
    }

    public function getProductType($store_id = 0){
        $this->ci->load->model('product_type_model');
        $this->ci->load->model('o2o_store_model');
        $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
        $city_id = $store['city_id'];
        $product_type = array();
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if(!$this->ci->memcached){
                $this->ci->load->library('memcached');
            }
            $mem_key = $this->cache_prefix."product_type_".$city_id;
            $product_type = $this->ci->memcached->get($mem_key);
        }
        if(empty($product_type)){
            $product_type = $this->ci->product_type_model->o2OGetDataList($city_id);
            if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
                if(!$this->ci->memcached){
                    $this->ci->load->library('memcached');
                }
                $mem_key = $this->cache_prefix."product_type_".$city_id;
                $this->ci->memcached->set($mem_key,$product_type,600);
            }
        }
        return $product_type;
    }

    function _check_o2o_tuan($uid,&$msg, $product_id){
        $today_time = date("Y-m-d 00:00:00");
        if(!$uid){
            return false;
        }
        $tuan_tag = $product_id.date("md");
        $this->ci->load->model('active_model');
        $is_full = $this->ci->active_model->check_tuan_member($tuan_tag);
        if(!$is_full){
            $msg = "本团今日已满，请明天继续";
            return false;
        }
        $is_join = $this->ci->active_model->check_tuan_is_join($uid, $tuan_tag);
        if(!$is_join){
            $msg = "您已经参加，明天再来";
            return false;
        }
        $is_order = $this->ci->active_model->check_order_is_exists($uid, $product_id);
        if(!empty($is_order)){//曾经购买过
            if($is_order['time'] > $today_time){//今天购买了
                $msg = "您已经参加，明天再来";
                return false;
            }
            $is_share = $this->ci->active_model->check_user_share($uid, $today_time);//判断今天是否有分享
            if(!$is_share){//未分享
                $msg = "活动期间仅限成功参团购买一次哦，分享成功后可再获得再次购买资格";
                return false;
            }
        }
        return true;
    }

    /*赠品订单金额限制验证*/
    private function check_gift_money_limit($cart_info,$order_money){
        foreach ($cart_info['items'] as $key => $value) {
            if($value['item_type']=='user_gift' && $value['order_money_limit']>0 && $order_money<$value['order_money_limit']){
                return "实付金额必须大于".$value['order_money_limit']."才能领取您的赠品(不包含运费和券卡)";
            }
        }
        return false;
    }

    /*
    * 优惠券使用范围
    */
    private function getCouponUseRange($result) {
        if (!empty($result)) {
            foreach ($result as $val) {
                if (strpos($val['product_id'], ",") === false) {
                    $productids[] = $val['product_id'];
                } else {
                    $productids = isset($productids) ? array_merge(explode(',', $val['product_id']), $productids) : explode(',', $val['product_id']);
                }
            }
            if (!empty($productids)) {
                $this->ci->load->model("product_model");
                $where_in[] = array('key' => 'id', 'value' => $productids);
                $results = $this->ci->product_model->selectProducts('product_name,id', '', $where_in);
                foreach ($results as $key => $val) {
                    $products[$val['id']] = $val['product_name'];
                }
            }
            foreach ($result as &$val) {
                if (empty($val['product_id'])) {
                    $val['use_range'] = "全站通用(个别商品除外)";
                } elseif (strpos($val['product_id'], ",") === false) {
                    $val['use_range'] = "仅限" . $products[$val['product_id']] . "使用";
                    $val['card_product_id'] = $val['product_id'];
                } else {
                    $currids = explode(',', $val['product_id']);
                    $curr_range = array();
                    foreach ($currids as $curr_val) {
                        $curr_range[] = $products[$curr_val];
                        // $val['card_product_id'] = $curr_val;
                    }
                    $val['card_product_id'] = $val['product_id'];
                    $val['use_range'] = "仅限" . join(",", $curr_range) . "使用";
                }
                if ($val['order_money_limit'] > 0)
                    $val['use_range'] .="满" . $val['order_money_limit'] . "使用";
//                $val['to_date'] = date("Y-m-d",strtotime("{$val['to_date']} -1 day"));

                if ($val['maketing'] == 1) {
                    if ($val['remarks'] == '仅限18元正价果汁抵用') {
                        $val['use_range'] = '';
                    }
                    unset($val['card_product_id']);
                    $val['card_o2o_only'] = 1;
                }

                if('仅限app购买美国加州樱桃一斤装使用'==$val['remarks']){
                    if('4435' == $val['card_product_id'])
                        $val['remarks'] = '仅限app购买美国红宝石（Ruby）樱桃一斤装使用';
                    else
                        $val['remarks'] = '仅限app购买美国西北樱桃一斤装使用';
                }
                if(!empty($val['direction'])){
                    $val['use_range'] = $val['direction'];
                }
                unset($val['product_id']);
                unset($val['direction']);
            }
        }
        return $result;
    }
    private function getCartCache($refresh = false)
    {
        if($this->_cartCache === false || $refresh){
            $this->ci->load->bll('o2ocart');
            $this->_cartCache = $this->ci->bll_o2ocart->get_cart_info();
        }
        return $this->_cartCache;
    }

    /**
     * 新版首页详情
     */
    public function getHomePageDetail($building_id=0,$latitude='',$longitude='',$version = 0, $region_id = 0)
    {
        $data = array(
            'code' => 200,
            'msg' => '',
            'banner' => array(),
            'product' => array(),
            'no_building' => 0, //是否设置楼宇
            'has_much' => 0, //是否匹配到多个门店;
        );
        $building_id = (int) $building_id;

        $storeBuildings = $this->getStoreBuildingIDs($building_id, $latitude, $longitude);
        $store_ids = $storeBuildings['store_ids'];
        $this->ci->load->library('login');
        if(empty($building_id)){
            $building_ids = $storeBuildings['building_ids'];
            if($this->ci->login->is_login()){
                $last_order_info = $this->getLastOrderInfo($building_ids);
                if($last_order_info['order_address']){
                    $data['last_address'] = $last_order_info['order_address'];
                    $data['no_building'] = $last_order_info['no_building'];
                    $building_id = $data['last_address']['building']['id']?array($data['last_address']['building']['id']):array();
                }
            }
        }

        if(count($store_ids) > 1){
            $data['has_much'] = 1;
        }
        $store_id = array_shift($store_ids);
        $storeDetail = $this->getStoreDetail($store_id);
        $data = array_merge($data, $storeDetail);

        if($this->ci->login->is_login()){
            $this->ci->load->bll('o2ocart');
            $this->ci->bll_o2ocart->set_province($region_id,$building_id,$data['store']['id']);
            $store_info['store'][0]['store_info'] = $data['store'];
            $this->ci->bll_o2ocart->checkCartInit($latitude,$longitude,$building_id,$store_info,true);
        }

        return $data;
    }

    public function getHomePageDetail_v2($region_id,$store_id,$lonlatForO2O){
        $data = array(
            'code' => 200,
            'msg' => '',
            'banner' => array(),
            'product' => array(),
            'no_building' => 0, //是否设置楼宇
            'has_much' => 0, //是否匹配到多个门店;
            'supportDelivery'=>1,
        );
        if(empty($store_id)){
            $data['no_building'] = 1;
            $data['supportDelivery'] = 0;
        }
        $this->ci->load->library('login');
        if($this->ci->login->is_login()){
            $uid = $this->ci->login->get_uid();
            if($uid){
                $last_order_info = $this->getLastOrderInfo_v2($uid,$store_id);
                if($last_order_info['order_address'] && !$lonlatForO2O && (!$store_id || $last_order_info['order_address']['storeId'] == $store_id)){
                    $data['last_address'] = $last_order_info['order_address'];
                    $store_id = $last_order_info['order_address']['storeId'];
                }
            }
        }

        if($store_id){
            $storeDetail = $this->getStoreDetail($store_id);
            $data = array_merge($data, $storeDetail);
        }
        if($this->ci->login->is_login()){
            $this->ci->load->bll('o2ocart');
            $store_info['store'][0]['store_info'] = $data['store'];
            $this->ci->bll_o2ocart->set_province($region_id,0,$store_id);
            $this->ci->bll_o2ocart->checkCartInit_v2($store_id,true);
        }
        return $data;
    }

    /**
     * 获取门店详情, banner, 商品
     * @param int $store_id
     * @return array
     */
    public function getStoreDetail($store_id = 0){
        if(empty($store_id)){
            return array();
        }
        $store_info = $this->getStroeInfo($store_id);
        $products = $this->getStoreProducts($store_id);
        $a_banner = array();
        $data_banner = array();
        foreach ($store_info['store_banner'] as $key => $value) {
            $arr = array('type'=>$value['type'],'target_id'=>$value['target_id'],'page_url'=>$value['page_url'],'title'=>$value['title']);
            if(in_array($arr, $a_banner)){
                continue;
            }
            $a_banner[] = $arr;
            $data_banner[] = $value;
        }

        $data = array(
            'banner'=> $data_banner,
            'product' => $products
        );
        if(!empty($store_info['store_info'])){
            $data['store'] = $store_info['store_info'];
        }
        if(!empty($store_info['delivery_banner'])){
            $data['delivery_banner'] = $store_info['delivery_banner'];
        }
        return $data;
    }

    /**
     * 获取有效的门店ID,和楼宇ID
     * @param int $building_id
     * @param int $latitude
     * @param int $longitude
     * @return array
     */
    public function getStoreBuildingIDs($building_id = 0, $latitude = 0, $longitude = 0){
        $this->ci->load->helper('public');
        $buildings = array();
        if(empty($building_id)){
            $this->ci->load->library('geohash');
            $hash = $this->ci->geohash->encode($latitude,$longitude);
            $hash = substr($hash,0,6);
            $neighbors = $this->ci->geohash->neighbors($hash);
            $neighbors['self'] = $hash;

            foreach ($neighbors as $value) {
                $sql = "select id from ttgy_o2o_region where attr=5 and geohash like '".$value."%'";
                $region = $this->ci->db->query($sql)->result_array();
                $buildings = array_merge($buildings, $region);
            }
        }else{
            $buildings = array_merge($buildings, array(array('id'=> $building_id)));
        }

        $buildingIDsTemp = array_chunk(array_column($buildings,'id'), 30, true);
        $buildingIDs = array();
        $storeIDs = array();
        foreach($buildingIDsTemp as $value){
            $sql="select sb.store_id, sb.building_id from ttgy_o2o_store_building sb join ttgy_o2o_store s on s.id=sb.store_id where s.isopen=1 and sb.building_id in (".implode(',', $value).")";
            $storeBuilding = $this->ci->db->query($sql)->result_array();
            foreach($storeBuilding as $v){
                if(!in_array($v['store_id'], $storeIDs)){
                    $storeIDs[] = $v['store_id'];
                }
                if(!in_array($v['building_id'], $buildingIDs)){
                    $buildingIDs[] = $v['building_id'];
                }
            }
        }
        return array('store_ids'=> $storeIDs, 'building_ids' => $buildingIDs);
    }

    /**
     * 获取门店商品列表
     * @param $store_id
     * @return array
     */
    public function getStoreProducts($store_id)
    {
        $data = array();
        $this->ci->load->model('o2o_store_goods_model');

        $sql = "SELECT sg.product_id,sg.stock,sg.qtylimit,pt.product_type_id FROM `ttgy_o2o_store_goods` sg inner join `ttgy_o2o_product_types` pt on sg.product_id = pt.product_id WHERE `store_id` = ? ORDER BY `stock`=0,`order` desc";
        $store_goods = $this->ci->db->query($sql, array($store_id))->result_array();

        $product_types = $this->getProductType($store_id);

        if (!$store_goods) return array();
        $this->ci->load->helper('public');
        $ids = array_chunk(array_column($store_goods, 'product_id'), 30, true);

        $productsSKU = array();
        foreach ($ids as $v){
            if(empty($v)) {
                continue;
            }
            $sql = "select pp.id,pp.price,pp.volume,pp.product_no,pp.product_id,pp.old_price,p.product_name,p.product_desc,p.cart_tag,p.thum_photo,p.photo,p.is_tuan,p.template_id from ttgy_product_price pp inner join ttgy_product p on p.id=pp.product_id where p.id in (" . implode(',', $v) . ")";
            $productsSKU = array_merge($productsSKU, $this->ci->db->query($sql)->result_array());
        }
        $products = array();
        foreach ($productsSKU as $v){
            $products[$v['product_id']][$v['id']] = $v;
        }

        foreach ($store_goods as $key => $value) {
            $product = isset($products[$value['product_id']]) ? $products[$value['product_id']] : array();
            if(empty($product) || empty($value['product_type_id'])) {
                continue;
            }

            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $product['photo'] = $templateImages['main']['image'];
                    $product['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

            foreach ($product as $k => $v) {
                if($product['is_tuan'] == 1) {
                    continue;
                }
                $data[$value['stock'] ? $value['product_type_id'] : 0][] = array(
                    'id'           => $v['id'],
                    'product_name' => $v['product_name'],
                    'product_desc' => $v['product_desc'],
                    'cart_tag'     => !empty($v['cart_tag']) ? $v['cart_tag'] : '',
                    'thum_photo'   => PIC_URL.$v['thum_photo'],
                    'photo'        => PIC_URL.$v['photo'],
                    'price'        => $v['price'],
                    'stock'        => $value['stock'],
                    'volume'       => $v['volume'],
                    'price_id'     => $v['id'],
                    'product_no'   => $v['product_no'],
                    'product_id'   => $v['product_id'],
                    'old_price'    => ($v['old_price']>0)?$v['old_price']:'',
                    'ptype'        => 1, // 产品类型 1:正常产品 2:券卡产品 3:特价商品
                    'buy_limit'    => $value['qtylimit'], // 限购数据
                    'store_id'     => $store_id,
                );
            }
        }

        foreach ($product_types as $k => $v){
            $product_types[$k]['products'] = !empty($data[$v['id']]) ? $data[$v['id']] : array();
            unset($data[$v['id']]);
        }

        $zero_stocks = array();
        $zero_stocks_ids = array();
        foreach ($data[0] as $k => $v){
            if(in_array($v['id'], $zero_stocks_ids)){
                continue;
            }
            $zero_stocks_ids[] = $v['id'];
            $zero_stocks[] = $v;
        }
        //库存为:0 的商品列表, 放在列表最后
        array_push($product_types, array('products' => $zero_stocks));
        return $product_types;
    }

    /**
     * 默认配送key, time
     * @param int $store_id
     * @param string $key
     * @return mixed
     */
    public function getDefaultSendTime($store_id, $key = ''){
        //1小时内配送的虚拟门店ID
        $oneHourStore = array(99,315,69,282,283,284,285,288,289,290,292,313,320,322,324,325,328,329,327);
        $rs = in_array($store_id, $oneHourStore)
            ? array( 'key' => '1hour', 'time' => '1')
            : array('key' => '2hours', 'time' => '两');
        return isset($rs[$key]) ? $rs[$key] : $rs;
    }
}
