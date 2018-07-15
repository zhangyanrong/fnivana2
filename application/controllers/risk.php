<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$sapi_name = php_sapi_name();
if( $sapi_name != 'cli' ){
    //exit("No direct script access allowed");
}

class Risk extends CI_Controller {
    
    private $score_table = 'mobile_score';
    
    //起始日期
    private $start_date = '20160115';
    
    
    public function __construct()
    {
        set_time_limit(0);
        parent::__construct();
    }
    
    
    public function test()
    {
        /* $ci = & get_instance();
         $ci->load->library('ebuckler');

         $result = $ci->ebuckler->get_score( '18606028163' );
         var_dump($result);*/
    }
    
    
    public function batch()
    {
        $ids = '1007404,1011158,1016418,1160631,1174189,1526321,1630850,1856358,1952953,2066637,2066749,2207497,2219984,2269879,2324462,233211,241463,2425226,2448729,2455581,2471585,2481339,2562338,2577361,2584484,2611304,2671254,2677194,2706056,2731113,2736426,2756149,2797573,2798532,2819315,2828938,2902769,2935850,2968660,2978985,2988255,3014747,3023810,3046814,3077692,3078130,3102877,3103007,3107875,3108583,314941,3154944,3156000,3157114,3162283,3165539,3177900,3178045,3178445,3198018,3213060,3223557,3254156,3285467,3319396,3344723,3373258,3441118,3445789,3445832,3455153,3458937,3509942,3618049,3642156,3691963,3736054,3749425,3749855,3757555,3789735,3807915,3832825,3836777,3842317,385681,3884981,3891466,3891489,3892512,3913218,3914994,3921508,3961394,3983975,4012521,4042272,4051817,4068980,4188186,4215622,4220205,4232705,4317205,4336202,436031,4402666,4402848,4571912,4586716,4592353,4605347,4663609,4717063,4752955,4804746,4833656,4833678,4863766,4876303,4916389,4923643,4939390,4952516,4980936,4988915,4989014,5013272,5019046,5021782,5032565,5099352,5199636,5203496,5265482,5280412,5297949,5329803,5341447,5345592,5423711,542816,5440881,5458927,5470713,5480327,5486659,5526807,5530757,5535469,5550382,5554865,5576849,5611813,5652992,5671140,5683122,5741163,5759307,5826996,5834190,5861456,5948953,5951131,5974898,6032140,6066307,6086618,6111855,6139401,6155365,6155492,6161620,6179629,6212957,6224774,6231678,6275094,6285047,6405129,6432912,6477568,686899,824449,953084,953703,1045887,1465599,2195982,2223841,255260,2712178,2763374,2809690,2832586,2847789,2969316,3047976,3063287,3078671,3078714,3095273,3107619,3108393,3108573,3108575,3108638,3136209,3138569,3139431,3140235,3157988,3160862,3162223,3177998,3178041,3178100,3179466,3179557,3186274,3197320,3197350,3198041,3339093,3407105,3433261,3437330,3440216,3441092,3458799,3478099,3487695,3488339,3608001,3618447,3733520,3749275,3749589,3751601,3770863,3852540,3853939,3855501,3876531,3904663,3908317,3918795,396173,3963732,3964580,3980092,4006651,4012337,4012419,4029687,4034525,4034551,4041757,4048487,4053025,4054151,4059478,4069423,4073570,4079634,4279796,4280159,4281009,4311194,4313908,4364387,4402438,4427067,4438280,4463667,4463835,4494328,4590875,4662133,4689080,4689081,4694371,4698209,4698335,4698451,4698493,4699271,4699439,4699559,4699736,4702913,4708361,4725915,4727890,4798805,4853793,4883110,4902905,4914476,4916369,4916372,4916381,4916382,4916392,4916395,4916400,491874,4919538,4926181,4936437,4968696,4968705,4972061,4979270,5005969,5005970,5005971,5005972,5005973,5005974,5005976,5005977,5005979,5005980,5005981,5005983,5005984,5005985,5005986,5005988,5005991,5005992,5005994,5005995,5006012,5006020,5006021,5006024,5006037,5006061,5006086,5006093,5009512,5013256,5013257,5013258,5013259,5013260,5013261,5013262,5013264,5013265,5013266,5013267,5013270,5013273,5013275,5013276,5013279,5015320,5016815,5016816,5016819,5016823,5016824,5016832,5016836,5016838,5016845,5016849,5016850,5016851,5016861,5016863,5016864,5016865,5016873,5016875,5016898,5016918,5018034,5018036,5018037,5018039,5018040,5018043,5018048,5018054,5018056,5018060,5018061,5018065,5018067,5018082,5018106,5018929,5019949,5023282,5043651,509485,5158607,5257096,527515,5289325,5295662,5301250,5328189,5354710,5354775,5354776,5354777,5354778,5354779,5354780,5354782,5359618,5363968,5364318,5364329,5384615,5390297,5391972,5401133,5420422,5420444,5420449,5432582,5438430,5441767,5469770,5491826,5495063,5521699,5528065,5539860,5549028,5549404,5553721,5624164,5630920,5634143,5670084,5680794,5719668,5727127,5736196,5741147,5791968,5852817,5882114,5884516,5930247,5931042,6015612,6024898,6025252,6056184,6056326,6056916,6087145,6094384,6098245,6128928,6141206,6155792,6171106,6172956,6218763,6226994,6304678,6356192,6366402,6389681,6419989,6428338,6432034,6445115,6447322,6453146,6462613,6465488,6468702,6472593,6472626,6515364,6523367,6524494,6534734,6542656,6542688,6556156,6556248,6556257,6557484,6560385,6560614,6566573,6566701,6566958,6567075,6567257,6567501,6568054,6568958,6576740,6577310,6577895,6579458,6581012,6583657,781666,824058,861994,865225,865599';
        
        $ci = & get_instance();
        $ci->load->library('ebuckler');
        
        $params = array();
        $i = 0;
        foreach( explode(',', $ids) as $uid ){
            
            $row = $this->db->from( 'user' )->select( 'mobile' )->where( "`id` = " . $uid )->get()->row_array();
            
            if( $row['mobile'] ){
                $params['mobile'] = $row['mobile'];
                $result_json = $ci->ebuckler->score( $params, false );
                #var_dump($params, $result_json);
            }
        }
    }
    
    
    public function check()
    {
        
        $sapi_name = php_sapi_name();
        if( $sapi_name != 'cli' ){
            exit("No direct script access allowed");
        }

        $sql = "SELECT count(*) AS c FROM ttgy_order WHERE `time` >= '2016-11-19 00:00:00' AND `time` < '2016-11-21 00:00:00' AND pay_status = 1";
        
		$result = $this->db->query($sql)->row_array();
        
        $pageSize = 100;
        $pageStart = 0;
        $pages = ceil( $result['c'] / $pageSize );
        
        $ci = & get_instance();
        $ci->load->library('ebuckler');
        
        for( $i = 0; $i < $pages; $i++ ){
            $pageStart = $i * $pageSize;
            $sql = "SELECT o.`order_name`,o.`time`,u.`mobile`,o.`pay_name`,a.`mobile` AS `mobile_2`,a.`name` AS `username`,a.`address`,o.`money` FROM ttgy_order AS o LEFT JOIN ttgy_user AS u ON o.uid = u.id LEFT JOIN ttgy_order_address AS a ON o.`id` = a.`order_id` WHERE o.`time` >= '2016-11-19 00:00:00' AND o.`time` < '2016-11-21 00:00:00' AND o.`pay_status` = 1 ORDER BY o.`id` ASC LIMIT $pageStart,$pageSize;";
            
            $orderList = $this->db->query($sql)->result_array();
            
            echo "订单号,下单时间,订购人手机,支付方式,收货人手机,收货人姓名,收货人地址,订单金额,订购人手机,收货人手机\n";
            
            foreach( $orderList as $row ){
                
                $params = array(
                    'source' => 'batch',
                    'mobile' => $row['mobile']
                );
                $result_json = $ci->ebuckler->score( $params, true );
                
                if($result_json['code'] == 1){
                    $mobile_score = $result_json['data'][$row['mobile']]['score'];
                }else{
                    $mobile_score = 0;
                }
                
                if( $row['mobile'] != $row['mobile_2'] ){
                    $params = array(
                        'source' => 'batch',
                        'mobile' => $row['mobile_2']
                    );
                    $result_json = $ci->ebuckler->score( $params, true );
                    
                    if($result_json['code'] == 1){
                        $mobile2_score = $result_json['data'][$row['mobile_2']]['score'];
                    }else{
                        $mobile2_score = 0;
                    }
                }else{
                    $mobile2_score = $mobile_score;
                }
                
                echo $row['order_name'] . "," . $row['time'] . "," . $row['mobile'] . "," . $row['pay_name'] . "," . $row['mobile_2'] . "," . $row['username'] . "," . $row['address'] . "," . $row['money'] . "," . $mobile_score . "," . $mobile2_score . "\r\n";
            }
            
        }
    }
    
    
    public function run()
    {
        $currDate = date("Ymd");
        $currTime = strtotime($currDate);
        $startTime = strtotime($this->start_date);
        $diffDays = ($currTime - $startTime) / 86400;
        $diffDays = $diffDays < 0 ? 0 : $diffDays;
        
        $dayMax = 100000;
        $pageMax = 100;
        
        $start_id = $diffDays * $dayMax;
        
        $ci = & get_instance();
        $ci->load->library('ebuckler');
    
        $params = array();
        $params['from'] = 'job';
        
        for($i = 0; $i < $dayMax / $pageMax; $i++){
            
            $begin_id = $start_id + $i * $pageMax;
            $end_id = $begin_id + $pageMax;
            
            $sql = "SELECT `id`,`mobile` FROM ttgy_user WHERE `id` > {$begin_id} AND `id` <= {$end_id} AND `mobile` IS NOT NULL ORDER BY id ASC LIMIT $pageMax;";
            
            $mobileList = $this->db->query($sql)->result_array();
        
            foreach( $mobileList as $data ){
            
                $_mobile = $data['mobile'];
                
                if( !preg_match("/^1[34578]\d{9}$/", $_mobile, $match ) ){
                    continue;
                }
                
                $params['mobile'] = $_mobile;
                $result_json = $ci->ebuckler->score( $params, false );
                
                if($result_json['code'] == 1){
                    $rating = $result_json['data'][$_mobile]['rating'];
                }else{
                    $rating = 'NULL';
                }
                
                $this->response($_mobile, $rating);
            }
            
            #continue;
        }
    }

    
    public function init()
    {
        $history_score = 0;
        
        $sql = "SELECT count(*) AS c FROM ttgy_mobile_score WHERE history_score = {$history_score};";
        
		$result = $this->db->query($sql)->row_array();
        
        $pageSize = 100;
        $pageStart = 0;
        $pages = ceil( $result['c'] / $pageSize );
        
        $ci = & get_instance();
        $ci->load->library('ebuckler');
        
        for( $i = 0; $i < $pages; $i++ ){
            $pageStart = $i * $pageSize;
            $sql = "SELECT `id`,`mobile` FROM ttgy_mobile_score WHERE history_score = {$history_score} ORDER BY id ASC LIMIT $pageStart,$pageSize;";
            
            $mobileList = $this->db->query($sql)->result_array();
            
            foreach( $mobileList as $row ){
                $history_data = $ci->ebuckler->history_score( $​​row['mobile'] );
                
                $this->db->where( 'id', $row['id'] );
                $this->db->update( 'ttgy_mobile_score', $history_data );
            }
            
        }
    }
    
    public function update()
    {
        $start_id = $_REQUEST['start_id'] ? $_REQUEST['start_id'] : 0;
        $end_id = $_REQUEST['end_id'] ? $_REQUEST['end_id'] : 830000;
        
        $sql = "SELECT count(*) AS c FROM ttgy_mobile_score WHERE id > $start_id AND id < $end_id;";
        $result = $this->db->query($sql)->row_array();
        
        $pageSize = 100;
        $pageStart = 0;
        $pages = ceil( $result['c'] / $pageSize );
        
        $ci = & get_instance();
        $ci->load->library('ebuckler');
        
        for( $i = 0; $i < $pages; $i++ ){
            $pageStart = $i * $pageSize;
            $sql = "SELECT `id`,`mobile`,`rating`,`score` FROM ttgy_mobile_score WHERE id > $start_id AND id < $end_id ORDER BY id ASC LIMIT $pageStart,$pageSize;";
            $mobileList = $this->db->query($sql)->result_array();
            
            foreach( $mobileList as $row ){
                
                $history_data = $ci->ebuckler->history_score( $​​row['mobile'] );
                $history_data['rating'] = $ci->ebuckler->reset_rating( $​​row['mobile'], $​​row['score'], $history_data );
                
                $this->db->where( 'id', $row['id'] );
                $this->db->update( 'ttgy_mobile_score', $history_data );
            }
        }
    }
    
    
    public function response( $mobile, $rating )
    {
		echo $log = "#### Run Time：". date("Y-m-d H:i:s") ."，mobile：". $mobile ."，rating：". $rating ."####\r\n";
		
    }
}
