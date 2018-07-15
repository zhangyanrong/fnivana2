<?php
// usage:
// $this->ci->load->library('fdaylog'); 
// $db_log = $this->ci->load->database('db_log', TRUE); 
// $this->ci->fdaylog->add($db_log,'pool_order_cancel_0727',$filter);
class CI_Fdaylog{
 


	function add($db_log,$tag,$data){
		
	    $data = array(
	      'time'=>date("Y-m-d H:i:s"),
	      'tag'=>$tag,
	      'log_data'=>serialize($data)
	    );
	    $db_log->insert('error_log',$data);
	}

    function addTms($db_log,$tag,$data){

        $data = array(
            'time'=>date("Y-m-d H:i:s"),
            'tag'=>$tag,
            'log_data'=>serialize($data)
        );
        $db_log->insert('tms_log',$data);
    }

}
