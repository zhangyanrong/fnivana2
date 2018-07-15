<?php


class CronAlipass extends CI_Controller
{
    private $queue_name = 'AliPassVer';
    
    public function __construct()
    {
		parent::__construct ();
        
        $this->ci = &get_instance();
		$this->load->helper('public');
        $this->load->library('phpredis');
        $this->ci->load->bll('alipay/pass');
        $this->redis = $this->phpredis->getConn();
    }
    
    public function run()
    {
        // $this->ci->load->library('fdaylog');
        // $db_log = $this->ci->load->database('db_log', TRUE);

        $this->ci->load->model('alipay_model');

		while( TRUE ){
            
			if( $data = $this->redis->rpop( 'AliPassVer' ) ){
                
                $ver_data = json_decode( $data, true );
                
                $params = array(
                    'serial_number' => $ver_data['card_number'],
                    'status' => 'USED'
                );
                
                $result = $this->ci->bll_alipay_pass->update($params);
                $response = $result['alipay_pass_instance_update_response'];
                
                if( $response && $response['code'] == '10000' ){
                    $ver_status = 'T';
                    echo $ver_data['card_number'] . " Success\r\n";
                }else{
                    $ver_status = 'F';
                    echo $ver_data['card_number'] . " Fail\r\n";
                }
                
                $update_data = array(
                    'status' => 1,
                    'ver_status' => $ver_status,
                    'used_time' => date("Y-m-d H:i:s")
                );
                $where = array(
                    'card_number' => $ver_data['card_number']
                );
                
                $update_result = $this->ci->alipay_model->update_alipay_card( $update_data, $where );
                
                // $log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------cronRun\r\n";
                // $log .= var_export($params,true)."\r\n";
                // $log .= var_export($response,true)."\r\n";
                // $log .= var_export($update_data,true)."\r\n";
                // $log .= var_export($where,true)."\r\n";
                // $log .= var_export($update_result,true)."\r\n";
                // $this->ci->fdaylog->add($db_log, 'alipay_pass', $log );
                
            }else{
                sleep(60);
            }
        }
    }
    
}
