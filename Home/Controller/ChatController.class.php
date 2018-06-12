<?php
namespace Home\Controller;
use Think\Controller;
use Home\Controller\CommonController;

class ChatController extends CommonController {
	public function index(){

		$phone = session('phone');
		$this->assign('phone',$phone);
		$this->display();
	}
	
}