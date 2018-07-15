<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* @author marares liu
 * ssl core
 */
class Ssl {
    
    public function sign() {
       $appkey = $this->uri->segment(3); 
       $PATH = BASEPATH."../ssl/";
       // 测试数据
       $data = 'If you are still new to things, we’ve provided a few walkthroughs to get you started.';
       // 私钥及密码
       $privatekeyFile = $PATH.'private.key';
       $passphrase = 'fda';
        
       // 摘要及签名的算法
       $digestAlgo = 'sha512';
       $algo = OPENSSL_ALGO_SHA1;
        
       // 加载私钥
       $privatekey = openssl_pkey_get_private(file_get_contents($privatekeyFile), $passphrase);
        
       // 生成摘要
       $digest = openssl_digest($data, $digestAlgo);
        
       // 签名
       $signature = '';
       @openssl_sign($digest, $signature, $privatekey, $algo);
       $signature = base64_encode($signature); 
var_dump($signature);
       return $signature;
    }

    protected function digest() {
        if (function_exists('hash')) {
            $digest = hash($digestAlgo, $data, TRUE);
        } elseif (function_exists('mhash')) {
            $digest =mhash(constant("MHASH_" . strtoupper($digestAlgo)), $data);
        }
        $digest = bin2hex($digest);
        return $digest;
    }

    public function verfiysign() {

        // 测试数据，同上面一致
         $data = 'If you are still new to things, we’ve provided a few walkthroughs to get you started.';
       $PATH = BASEPATH."../ssl/";
          
         // 公钥
         $publickeyFile = $PATH.'public.key';
          
         // 摘要及签名的算法，同上面一致
         $digestAlgo = 'sha512';
         $algo = OPENSSL_ALGO_SHA1;
          
         // 加载公钥
         $publickey = openssl_pkey_get_public(file_get_contents($publickeyFile));
          
         // 生成摘要
         $digest = openssl_digest($data, $digestAlgo);
          
         // 验签
         $verify = openssl_verify($digest, base64_decode($this->sign()), $publickey, $algo);
         var_dump($verify); // int(1)表示验签成功

    }

    protected function encrypt() {
        // 测试数据
        $data = 'If you are still new to things, we’ve provided a few walkthroughs to get you started.';
         
        $PATH = BASEPATH."../ssl/";
        // 公钥
        $publickeyFile = $PATH.'public.key';
         
        // 加载公钥
        $publickey = openssl_pkey_get_public(file_get_contents($publickeyFile));
         
        // 使用公钥进行加密
        $encryptedData = '';
        openssl_public_encrypt($data, $encryptedData, $publickey);
         
        $encryptedData = base64_encode($encryptedData);
        //var_dump(base64_encode($encryptedData));

        // 私钥及密码
        $privatekeyFile = $PATH.'private.key';
        $passphrase = 'fday';
         
        // 加载私钥
        $privatekey = openssl_pkey_get_private(file_get_contents($privatekeyFile), $passphrase);
         
        // 使用公钥进行加密
        $sensitiveData = '';
        openssl_private_decrypt(base64_decode($encryptedData), $sensitiveData, $privatekey);
         
        var_dump($sensitiveData); // 应该跟$data一致

    }
    
} 
?>
