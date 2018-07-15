<?php
/*
 * @author marares.liu
 * session operation model
 */
class Session_model extends CI_model {

    function Session_model()
    {
        parent::__construct();
    }

    function fastSend($DESTMOBS,$CONTENT, $priority = 9){
        $url = 'http://sms.fruitday.com:8080/fday/sms/send/single';
        $data['mobile'] = $DESTMOBS;
        $data['message'] = $CONTENT;
        $data['priority'] = $priority;
        $data['via'] = "fruitday";
        $data_string = json_encode($data);
        $ch=curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array('Content-Type:application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = json_decode(curl_exec($ch),1);
        curl_close($ch);
    }

    function data_merge($session, $data ,$type)
    {
        $keys = array_keys($data);
        foreach($keys as $k) {
            $session[$type][$k] = $data[$k];
        }
        return $session;
    }

    function get_session($session_id)
    {
        if(!$session_id)
            return;

        $sql = "select * from ttgy_ci_sessions where session_id = '".$session_id."'";
        $query = $this->db->query($sql);
        return $query->row_array();
    }

    function update_session_userdata($session_id,$session)
    {
        $this->db->where("session_id",$session_id);
        $this->db->update("ci_sessions",array("user_data"=>serialize($session)));
    }

    function remove_session($session_id)
    {
        if(!$session_id)
            return;
        $this->db->delete('ci_sessions', array('session_id' => $session_id)); 
    }

    function unset_session($session_id, $to_be_unset)
    {
        if(!$session_id || !$to_be_unset)
            return;

        $session = $this->get_session($session_id);
        unset($session[$to_be_unset]);
        $this->update_session_userdata($session_id, $session);

    }

}
