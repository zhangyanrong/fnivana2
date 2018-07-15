<?php
// CI七牛云存储调用
// 蔡昀辰 2015
// 使用:
// $params['accessKey'] = 'eQjRZFLFzK8Q031o5SYXsTtxO5anOGD3W7oQp0d3';
// $params['secretKey'] = 'JHoVnaeZ-wL1b7qQtJUL-OGkOWMMpBtI9RHzcHy1';
// $params['bucket']    = 'test';
// $this->load->library('Qiniu/qiniu', $params);
// $this->qiniu->put('my_first_pic', '/tmp/girl.jpg');
require 'Auth.php';
require 'Config.php';
require 'Etag.php';
require 'functions.php';
require 'Http/Client.php';
require 'Http/Error.php';
require 'Http/Request.php';
require 'Http/Response.php';
require 'Processing/Operation.php';
require 'Processing/PersistentFop.php';
require 'Storage/BucketManager.php';
require 'Storage/FormUploader.php';
require 'Storage/ResumeUploader.php';
require 'Storage/UploadManager.php';

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class qiniu {

	var $token;
	
	// params:
	// accessKey: 	AK
	// secretKey: 	SK
	// bucket: 		空间名称
	function __construct ($params) {
		if (!$params['accessKey'])
			throw new Exception("需要七牛accessKey参数");
		if (!$params['secretKey'])
			throw new Exception("需要七牛secretKey参数");	
		
		$auth = new Auth($params['accessKey'], $params['secretKey']);

		if(!$auth)
			throw new Exception("验证失败");
		if (!$params['bucket'])
			throw new Exception("需要七牛bucket参数");

		$this->token = $auth->uploadToken($params['bucket']);

		if (!$this->token)
			throw new Exception("七牛Token获取失败");
	}

	// $filePath 	要上传文件的本地路径(包括文件名)
	// $key      	上传到七牛后保存的文件名
	function put($key, $filePath) {

		// log
		$CI = & get_instance();
		$CI->load->library('fdaylog'); 
		$db_log = $CI->load->database('db_log', TRUE); 		

		// upload
		$uploadMgr = new UploadManager();
		list($res, $err) = $uploadMgr->putFile($this->token, $key, $filePath);

		// result
		if ($err !== null) {
			$CI->fdaylog->add($db_log,'qiniu_err_log:'.$this->token, $err);
			throw $err;
		} else {
			// $CI->fdaylog->add($db_log,'qiniu_succ_log:'.$this->token, $res);
			return true;
		}
	}

    public function downurlAction($url)
    {
        $baseUrl = 'http://7xip11.com2.z0.glb.qiniucdn.com/'.$url;
        if(!empty($baseUrl))
        {
            $auth = new \Qiniu\Auth('oJPhc7RkX3h84zn4Vq9IB0_X_dgiNs-wMaOdLUEQ', 'lS-Qto3Yta_50QD8FhZeDK_2mAzbNethvyK3bzqI');
            $authUrl = $auth->privateDownloadUrl($baseUrl);

            echo $authUrl;
        }else{
            echo "url null";
        }

        return;
    }

}