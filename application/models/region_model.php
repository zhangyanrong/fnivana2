<?php
  Class Region_model extends CI_Model{
  var $region = '';
  var $province_id = 0;
  var $province_name = '';
  var $default_area_id = 0;

	function Region_model(){
	     parent::__construct();

        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
	}

	function get_child_region($pid,$source){
		$this->db->select('id,pid,name');
		$this->db->from('area');
		$this->db->where(array('pid'=>$pid,'active'=>'1'));
        if($source=='app'){
            $this->db->order_by('order','asc');
        }else{
            $this->db->order_by('order','desc');
        }
		$query = $this->db->get();
		$result = $query->result_array();
		return $result;
	}

	function get_area_info($area_id){
      $this->db->select('id,pid,name,identify_code,first_weight,first_weight_money,follow_weight_money,cut_off_time,cut_off_time_m,cut_off_time_n,free_post_money_limit,send_time,can_night_send,can_ampm_send,send_role');
      $this->db->from('area');
      $this->db->where('id',$area_id);
      $query = $this->db->get();
      $result = $query->row_array();
      return $result;
    }

    function get_province_id($addr_id,$uid=''){
        $this->db->select("name,province,city,area");
        $this->db->from("user_address");
        $this->db->where(array("id"=>$addr_id));
        if($uid){
            $this->db->where(array("uid"=>$uid));
        }
        $query=$this->db->get();
        $result=$query->row_array();
        return $result;
    }

    function is_send_wd($province){
        $area_refelect = $this->config->item("area_refelect");
        if(in_array($province, $area_refelect['1']) || in_array($province, $area_refelect['5'])){
            return false;
        }else{
            return true;
        }
    }

    function get_user_address_info($fields,$address_id){
        $this->db->select($fields);
        $this->db->from("user_address");
        $this->db->where(array("id"=>$address_id));
        $query=$this->db->get();
        $result=$query->row_array();
        return $result;
    }

    function get_region($area_id){
        if($area_id==0){
            return '';
        }
        $this->db->select("name,pid");
        $this->db->from("area");
        $this->db->where(array("id"=>$area_id));
        $query=$this->db->get();
        $result=$query->row_array();
        if($result['pid']!='0'){
            $this->region = $result['name'].$this->region;
            $this->get_region($result['pid']);

        }else{
            $this->region = $result['name'].$this->region;
        }
        return $this->region;
    }

    function get_province($area_id){
        if($area_id==0){
            return '';
        }
        $this->db->select("id,pid");
        $this->db->from("area");
        $this->db->where(array("id"=>$area_id));
        $query=$this->db->get();
        $result=$query->row_array();
        if($result['pid']!='0'){
            $this->get_province($result['pid']);
        }else{
            $this->province_id = $result['id'];
        }
        return $this->province_id;
    }

    function get_send_region($send_region,$view=''){
        $this->db->select("name,id,send_info");
        $this->db->from("area");
        $this->db->where_in("id",$send_region);
        $this->db->order_by("order","asc");
        $query = $this->db->get();
        $result = $query->result_array();
        $result_arr = array();
        foreach ($result as $key => $value) {
            $result_arr[] = $value['name'];
        }
        return $result_arr;
    }

    function get_area_id($province_id){
        $this->db->select("id");
        $this->db->from("area");
        $this->db->where(array("pid"=>$province_id));
        $this->db->order_by('order','asc');
        $query=$this->db->get();
        $result=$query->row_array();
        if(!empty($result)){
            $this->get_area_id($result['id']);
        }else{
            $this->default_area_id = $province_id;
        }
        return $this->default_area_id;
    }

    function get_parent_region_id($area_id){

        if($area_id==0){
            return '';
        }
        $this->db->select("id,pid");
        $this->db->from("area");
        $this->db->where(array("id"=>$area_id));
        $query=$this->db->get();
        $result1=$query->row_array();

        $this->db->select("id,pid,name");
        $this->db->from("area");
        $this->db->where(array("id"=>$result1['pid']));
        $query=$this->db->get();
        $result=$query->row_array();

        return array('id'=>$result['id'],'name'=>$result['name']);
    }

    function get_province_info($area_id){

        if($area_id==0){
            return '';
        }
        $this->db->select("id,pid,name");
        $this->db->from("area");
        $this->db->where(array("id"=>$area_id));
        $query=$this->db->get();
        $result=$query->row_array();
        if($result['pid']!='0'){
            $this->get_province_info($result['pid']);
        }else{
            $this->province_id = $result['id'];
            $this->province_name = $result['name'];

        }

        return array('id'=>$this->province_id,'name'=>$this->province_name);
    }

    function checkResetShtime($area,$send_date,$stime,$province,$must_zj=false,$addr_id=''){
        $area_info = $this->get_area_info($area);
        $cut_off_time = $area_info['cut_off_time'];
        $cut_off_time_m = $area_info['cut_off_time_m'];
        $cut_off_time_n = $area_info['cut_off_time_n'];
        
        $this->load->model('warehouse_model');
        if($must_zj){
            $warehouse_info = $this->warehouse_model->getZijianWarehouse($area);
        }else{

            $ware_id ='';
            if(!empty($addr_id))
            {
                $this->load->model('user_address_model');
                $user_add = $this->user_address_model->dump(array('id' => $addr_id));
                if(!empty($user_add) && !empty($user_add['tmscode']))
                {
                    $arr_tmsCode = explode('-',$user_add['tmscode']);
                    $tmsCode =$arr_tmsCode[0];
                    $ware = $this->warehouse_model->dump(array('tmscode' => $tmsCode));
                    if(!empty($ware))
                    {
                        $ware_id = $ware['id'];
                    }
                }
            }

            if(!empty($ware_id))
            {
                $warehouse_info = $this->warehouse_model->getWarehouseByID($ware_id);
            }

            if(empty($warehouse_info))
            {
                $warehouse_info = $this->warehouse_model->get_warehouse_by_region($area);
            }
        }
        $warehouse_info['special'] = unserialize($warehouse_info['special']);
        $h = date('H');
        $reset_shtime = false;
        if(in_array($stime,array('weekday','weekend','all')))  $stime = '0918';
        if($area_info['can_ampm_send'] && $area_info['can_night_send'] && $warehouse_info['limit_type']==1){   //支持一日三单
            if($warehouse_info['is_next_day']){               //次日达
                if($h>=$cut_off_time_m && $send_date==date('Ymd') && in_array($stime, array('0918','0914','1418'))){
                    $reset_shtime = true;
                }elseif($h>=$cut_off_time_n && $send_date == date('Ymd')){
                    $reset_shtime = true;
                }elseif($h>=$cut_off_time && $send_date==date('Ymd',strtotime("+1 day")) && in_array($stime, array('0918','0914','1418'))){
                    $reset_shtime = true;
                }
                if(strcmp($send_date, date('Ymd')) < 0){
                    $reset_shtime = true;
                }
                if($send_date==date('Ymd') && in_array($stime, array('0918','0914','1418'))){
                    $reset_shtime = true;
                }
            }else{
                if($h>=$cut_off_time_m && $send_date==date('Ymd') && in_array($stime, array('0918','0914','1418'))){
                    $reset_shtime = true;
                }elseif($h>=$cut_off_time_n && $send_date == date('Ymd')){
                    $reset_shtime = true;
                }elseif($h>=$cut_off_time && $send_date==date('Ymd',strtotime("+1 day")) && in_array($stime, array('0918','0914'))){
                    $reset_shtime = true;
                }
                if(strcmp($send_date, date('Ymd')) < 0){
                    $reset_shtime = true;
                }
                if($send_date==date('Ymd') && in_array($stime, array('0918','0914'))){
                    $reset_shtime = true;
                }
            }
        }elseif($area_info['can_ampm_send'] && $warehouse_info['limit_type']==1){  //支持上下午单，不支持晚单
            if(in_array($stime, array('1822'))){
                $reset_shtime = true;
            }
            if($warehouse_info['is_next_day']){
                if($h>=$cut_off_time && $send_date<=date('Ymd',strtotime("+1 day"))){
                    $reset_shtime = true;
                }
                if(strcmp($send_date, date('Ymd')) < 0){
                    $reset_shtime = true;
                }
                if($send_date==date('Ymd')){
                    $reset_shtime = true;
                }
            }else{
                if($h>=$cut_off_time_m && $send_date==date('Ymd') && in_array($stime, array('0918','0914','1418'))){
                    $reset_shtime = true;
                }elseif($h>=$cut_off_time_n && $send_date == date('Ymd')){
                    $reset_shtime = true;
                }elseif($h>=$cut_off_time && $send_date==date('Ymd',strtotime("+1 day")) && in_array($stime, array('0918','0914'))){
                    $reset_shtime = true;
                }
                if(strcmp($send_date, date('Ymd')) < 0){
                    $reset_shtime = true;
                }
                if($send_date==date('Ymd') && in_array($stime, array('0918','0914'))){
                    $reset_shtime = true;
                }
            }
        }elseif($area_info['can_night_send']){ //支持早晚单
            if($h>=$cut_off_time_m && $send_date==date('Ymd')){
                $reset_shtime = true;
            }elseif($h>=$cut_off_time && $send_date==date('Ymd',strtotime("+1 day")) && in_array($stime, array('0918','0914','1418'))){
                $reset_shtime = true;
            }
            if(strcmp($send_date, date('Ymd')) < 0){
                $reset_shtime = true;
            }
            if($send_date==date('Ymd') && in_array($stime, array('0918','0914','1418'))){
                $reset_shtime = true;
            }
            if(in_array($stime, array('0914','1418'))){
                $reset_shtime = true;
            }
        }else{
            if(in_array($stime, array('1822','0914','1418'))){
                $reset_shtime = true;
            }
            //$send_free = $this->is_send_wd($province);
            //if($send_free === true){
            if($area_info['send_time'] == 'chose_days'){
                if($h>=$cut_off_time && strcmp($send_date, date('Ymd',strtotime('+1 day'))) <= 0){
                    $reset_shtime = true;
                }elseif(strcmp($send_date, date('Ymd')) <= 0){
                    $reset_shtime = true;
                }
            }else{
                if($h>=$cut_off_time && strcmp($send_date, date('Ymd')) <= 0){
                    $reset_shtime = true;
                }
            }
        }

        //单量限制
        $limit_arr = $this->getSendLimit($warehouse_info);
        if(!empty($limit_arr) && !empty($send_date))
        {
            $limit_key = date('Ymd',strtotime($send_date));
            if($limit_arr[$limit_key][$stime] == 'limit')
            {
                $reset_shtime = true;
            }
            else if($limit_arr['all'][$stime] == 'limit')
            {
                $reset_shtime = true;
            }
        }

        if(strcmp($send_date, date('Ymd')) < 0){
            $reset_shtime = true;
        }
        if(empty($stime)){
            $reset_shtime = true;
        }
        return $reset_shtime;
	}

    public function getIdByName($name = '', $pid = 0)
    {
        if (empty($name)) {
            return 0;
        }

        $res = $this->db->select('id')
                 ->from('area')
                 ->where(array('pid' => $pid))
                 ->like('name', $name)
                 ->get()->row_array();

        return $res ? $res['id'] : 0;
    }

    public function getId($name=''){
        if (empty($name)) {
            return 0;
        }

        $sql = "SELECT id FROM ttgy_area WHERE name='".$name."'";
        $res = $this->db->query($sql)->row_array();
        return $res ? $res['id'] : 0;
    }

    public function checkUserAddr($address_id){
        if(empty($address_id)) return false;
        $address_info = $this->db->select('province,city,area')->from('ttgy_user_address')->where(array('id'=>$address_id))->get()->row_array();
        if(empty($address_info)) return false;
        $sql = "select a.id,b.id,c.id from ttgy_area a join ttgy_area b on a.id=b.pid join ttgy_area c on b.id=c.pid where a.active=1 and b.active=1 and c.active=1 and a.id=".$address_info['province']." and b.id=".$address_info['city']." and c.id=".$address_info['area'];
        $res = $this->db->query($sql)->row_array();
        if(empty($res)) return false;
        return true;
    }


    /*
     * 单量限制
     */
    public  function getSendLimit($warehouse_info = array()){
          $limit_arr = array();

          $this->load->model('warehouse_model');
          /*每天发货数量限制start*/
          /*获取订单数量start*/
          $order_time_limit_result = array();
          if($warehouse_info){
              $order_time_limit_result = $this->warehouse_model->getWarehouseSendCount($warehouse_info['id']);
              if($order_time_limit_result){
                  foreach ($order_time_limit_result as $sh_stime => $nums) {
                      $time_key = explode('_', $sh_stime);
                      $sh_time = $time_key[0];
                      $s_time = $time_key[1];
                      switch ($s_time) {                                      //普通设置
                          case '0918':
                              if($nums >= $warehouse_info['day_limit']){
                                  $limit_arr[$sh_time]['0918'] = 'limit';
                              }
                              break;
                          case '0914':
                              if($nums >= $warehouse_info['am_limit']){
                                  $limit_arr[$sh_time]['0914'] = 'limit';
                                  //$limit_arr[$sh_time]['0918'] = 'limit';
                              }
                              break;
                          case '1418':
                              if($nums >= $warehouse_info['pm_limit']){
                                  $limit_arr[$sh_time]['1418'] = 'limit';
                              }
                              break;
                          case '1822':
                              if($nums >= $warehouse_info['night_limit']){
                                  $limit_arr[$sh_time]['1822'] = 'limit';
                              }
                              break;
                          default:
                              if($nums >= $warehouse_info['day_limit']){
                                  $limit_arr[$sh_time]['0918'] = 'limit';
                              }
                              break;
                      }

                      //有特殊设置
                      if($warehouse_info['special'] && is_array($warehouse_info['special']) && $warehouse_info['special'][0]){
                          foreach ($warehouse_info['special'] as $key => $value) {
                              $s_start_time = date('Ymd',strtotime($value['s_start_time']));
                              $s_end_time = date('Ymd',strtotime($value['s_end_time']));
                              if($sh_time>=$s_start_time && $sh_time<=$s_end_time){
                                  switch ($s_time) {
                                      case '0918':
                                          $check_nums = $value['s_day_limit'];
                                          break;
                                      case '0914':
                                          $check_nums = $value['s_am_limit'];
                                          break;
                                      case '1418':
                                          $check_nums = $value['s_pm_limit'];
                                          break;
                                      case '1822':
                                          $check_nums = $value['s_night_limit'];
                                          break;
                                      default:
                                          $check_nums = $value['s_day_limit'];
                                          break;
                                  }
                                  if($nums >= $check_nums){
                                      $limit_arr[$sh_time][$s_time] = 'limit';
                                  }else{
                                      $limit_arr[$sh_time][$s_time] = 'unlimit';
                                  }
                                  // if($warehouse_info['limit_type'] == 1){
                                  //     $limit_arr[$sh_time]['0918'] = $limit_arr[$sh_time]['0914'];
                                  // }
                              }
                          }
                      }
                  }

              }
              if($warehouse_info['day_limit'] == 0){
                  $limit_arr['all']['0918'] = 'limit';
              }
              if($warehouse_info['am_limit'] == 0){
                  $limit_arr['all']['0914'] = 'limit';
              }
              if($warehouse_info['pm_limit'] == 0){
                  $limit_arr['all']['1418'] = 'limit';
              }
              if($warehouse_info['night_limit'] == 0){
                  $limit_arr['all']['1822'] = 'limit';
              }
              // if($warehouse_info['am_limit'] == 0 && $warehouse_info['pm_limit'] == 0 && $warehouse_info['limit_type'] ==1){
              //     $limit_arr['all']['0918'] = 'limit';
              // }
              if($warehouse_info['special'] && is_array($warehouse_info['special']) && $warehouse_info['special'][0]){
                  foreach ($warehouse_info['special'] as $key => $value) {
                      $s_start_time = date('Ymd',strtotime($value['s_start_time']));
                      $s_end_time = date('Ymd',strtotime($value['s_end_time']));
                      $sh_time = date('Ymd',strtotime($s_start_time));
                      while ($sh_time <= $s_end_time) {
                          if($value['s_day_limit'] == 0){
                              $limit_arr[$sh_time]['0918'] = 'limit';
                          }
                          if($value['s_am_limit'] == 0){
                              $limit_arr[$sh_time]['0914'] = 'limit';
                          }
                          if($value['s_pm_limit'] == 0){
                              $limit_arr[$sh_time]['1418'] = 'limit';
                          }
                          if($value['s_night_limit'] == 0){
                              $limit_arr[$sh_time]['1822'] = 'limit';
                          }
                          // if($value['s_am_limit'] == 0 && $value['s_pm_limit'] == 0 && $warehouse_info['limit_type'] ==1){
                          //     $limit_arr[$sh_time]['0918'] = 'limit';
                          // }
                          $sh_time = date('Ymd',strtotime($sh_time)+86400);
                      }
                  }
              }
          }
          return $limit_arr;
      }
}