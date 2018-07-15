<?php
namespace bll\app;
include_once("app.php");
/**
* 邀请好友接口
*/
class Userinvite extends app{

	function __construct(){
		$this->ci = &get_instance();
	}
	
}