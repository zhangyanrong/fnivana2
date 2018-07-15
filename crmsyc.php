<?php
header("Content-type: text/html; charset=utf-8");
defined('CRM_SECRET') or define('CRM_SECRET','56b44d6cd9b7f902ef36f1f0c1dac79f');

$url = "http://nirvana.fruitday.com/crmApiSyn";
 
$data = array();

$data['service'] = 'crm.orderComplaints';
$data['timestamp'] = time();
$data['sign'] = create_sign($data);
$ddd['data'] = json_encode($data);


$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_HEADER,0);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
//curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"POST");
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$ddd);
//curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type:application/json','Content-Length: ' . strlen($data_string)));
$result = curl_exec($ch);
curl_close($ch);

print_r(json_decode($result,true));
exit;
 
 function create_sign($params){
	unset($params['sign']);
	ksort($params);
	$query = '';
	foreach($params as $k=>$v){
		$query .= $k.'='.$v.'&';
	}
	$validate_sign = md5(substr(md5($query.CRM_SECRET), 0,-1).'w');
	return $validate_sign;
}
?>
