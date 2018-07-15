<?php
class User_action_model extends MY_Model {


    function __construct() {
        parent::__construct();
    }

    public function visitor($param){
        if(empty($param['uid'])){
            return false;           
        }        
        $time = time();
        $visitor = array(
                    'uid' => $param['uid'],
                    'last_view_time' => $time,
                    'ctime' => $time,
        );
       
        $this->db->select('id');
        $this->db->from('user_action');
        $this->db->where('uid', $param['uid']);
        $exist = $this->db->get()->row_array(); 
        if(!$exist['id']){ 
	    $insert_query = $this->db->insert_string('user_action', $visitor);
            $insert_query = str_replace('INSERT INTO','INSERT IGNORE INTO',$insert_query);
  	    $rs = $this->db->query($insert_query);
        }else{
            unset($visitor['ctime']);
            $this->db->where('uid', $visitor['uid']);
            unset($visitor['uid']); 
            $rs = $this->db->update('user_action', $visitor);
        }
        return $rs;
    }
}
