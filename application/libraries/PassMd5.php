<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class CI_PassMd5 {

    public static function md5Pass($password) {

        if(strlen($password) == 16) {
            return md5(md5(substr($password,0,4)).md5(substr($password,4,4)).md5(substr($password,8,4)).md5(substr($password,12,16)));
        }

    }  

    public static function userPwd($password){
    	return md5(substr(md5($password.USER_PWD_SECRET), 0,-2).'Wj');
    }  
}



