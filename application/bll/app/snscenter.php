<?php
namespace bll\app;
include_once("app.php");
/**
* 商品相关接口
*/
class Snscenter extends app{

	function __construct(){
		$this->ci = &get_instance();
	}
	
}