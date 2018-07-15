<?php
class Ver_error_model extends MY_Model {
	public $filter = array();

	public function table_name(){
		return "ver_error";
	}

	public function setFilter($connect_id, $mobile){
		if(!empty($connect_id)  && !empty($mobile)){
			$this->filter = array(
				'connect_id'=>$connect_id,
				'mobile'=>$mobile,
			);
		}
	}
	//创建
	public function creVer(){
		if(empty($this->filter)){
			return false;
		}
		$this->insert($this->filter);
	}
	//清除
	public function cleVer(){
		if(empty($this->filter)){
			return false;
		}
		$this->delete($this->filter);
	}
	//更新
	public function setVer($reset=0){
		if(empty($this->filter)){
			return false;
		}
		if($reset==1){
			$this->delete($this->filter);
		}else{
			$res = $this->dump($this->filter,'num');
			$num = $res['num'];
			if($num >= 3){
				return false;
			}else{
				$curr_num = $num+1;
				$this->update(array('num'=>$curr_num),$this->filter);
				return $curr_num;
			}
		}
	}
}
