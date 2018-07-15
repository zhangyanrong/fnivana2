<?php
namespace bll\risk;



/**
* @支付宝蚁盾风控接口
* @技术对接：@虚怀
* @商户对接：@萨纱 18521781814
* @auth cuiyang
*/
class Ebuckler 
{

	public function __construct()
    {
        
	}
    
    
    /**
     * 获取hmac_sha1签名的值
     *
     * @param $str 源串
     * @param $key 密钥
     *
     * @return 签名值
     */
    public function signature($str, $key) 
    {
        $signature = "";
        
        if(function_exists('hash_hmac')){
            $signature = base64_encode(hash_hmac("sha1", $str, $key, true));
        }else{
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack(
                    'H*', $hashfunc(
                            ($key ^ $opad) . pack(
                                    'H*', $hashfunc(
                                            ($key ^ $ipad) . $str
                                    )
                            )
                    )
            );
            $signature =base64_encode($hmac);
        }
        
        return str_replace(array('+', '/', '='), array('-', '_', ''), $signature);
    }
    
    
	/**
	 * 执行一个 HTTP GET请求
	 *
	 * @param string $url 执行请求的url
	 * @return array 返回网页内容
	 */
	public function request($url, $post_data = '')
    {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		
		if( $post_data ){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		}
        
		$res = curl_exec($curl);
		$err = curl_error($curl);
		
		curl_close($curl);
		return $res;
	}
}