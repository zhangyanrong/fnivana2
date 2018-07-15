<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * sms log model
 *
 * @author marares
 */
  Class Sms_log_model extends CI_Model{
      public $ip;
      public $mobile;
      public $uid;

      public function __construct()
      {
          $this->ip = $ip;
          parent::__construct();
      }

      private function addRecord()
      {
          $this->db->insert("sms_log", array(
            "ip"    =>  $this->ip,
            "mobile"    =>  $this->mobile,
            "create_time"   => date("Y-m-d H:i:s"),
          ));
      }

      public function checkSend($times=3)
      {
          if($this->uid > 0)
              return true;


        //  $now = microtime(true);
          $date = date("Y-m-d");

        //  $sql = "select * from ttgy_sms_log where ip = '{$this->ip}' and create_time like '%".$date."%' and ip!='58.247.128.206' order by id desc";
         // $query = $this->db->query($sql);
         // $result = $query->result();

         // if(!empty($result))
         // {
           //  $space = $now - strtotime($result[0]->create_time);
             //$id = $result[0]->id;
/*
             if($space < 240)
             {
                 $this->limitTimes($id);
                 return false;
             }
*/
              $this->db->from("sms_log");
              $this->db->where(array(
                "mobile"=>$this->mobile
              ));
              $this->db->like("create_time",$date);
              $count = $this->db->count_all_results();

              if( $count>=$times )
              {
                 //$this->limitTimes($id);
                 return false;
              }
           //   $this->addRecord();
         // }
          $this->addRecord();
          return true;
      }

      private function limitTimes($id)
      {
          $sql = "update ttgy_sms_log set limit_times = limit_times + 1 where id = $id";
          $this->db->query($sql);
      }

  }

