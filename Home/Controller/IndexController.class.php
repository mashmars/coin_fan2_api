<?php
namespace Home\Controller;
use Think\Controller;
use Home\Controller\CommonController;

class IndexController extends CommonController {
	public function ddddd(){
		$ad = cookie('ad');
      var_dump($ad);
	}
	//检查是否实名认证
	private function check_certification(){
		$userid = session('userid');
		$is_cert = M('user')->where(array('id'=>$userid))->getField('is_cert');
		if($is_cert){
			return true;
		}
		return false;
	}
	public function index(){

	    $userid = session('userid');
	    
		//24小时内待收取的  超过24小时作废
		$guoqi = time() - 24*3600;
		M('sys_fl_log')->where(array('userid'=>$userid,'status'=>2,'createdate'=>array('lt',$guoqi)))->setField('status',0);
		//待收取的
		$wait = M('sys_fl_log')->where(array('userid'=>$userid,'status'=>2))->select();
		$style = array('one','two','three','four','five','six','seven','eight');
		$this->assign('style',$style);
		
		//我的收取记录4条
		$shouqu = M('sys_fl_log')->where(array('userid'=>$userid,'status'=>1))->limit(4)->order('updatedate desc')->select();
		if($shouqu[0]){
			foreach($shouqu as &$v){
				$v['createdate'] = format_date($v['updatedate']);
			}
		}
		//我获取的算力 4条
		$suanli = M('myinvite')->where(array('userid'=>$userid,'type'=>2))->limit(4)->order('id desc')->select();
		
		//前三名算理最多的
		$paihang = M('user_coin')->alias('a')->join('left join user b on a.userid=b.id')->field('b.phone,a.lthz')->order('lthz desc,a.id desc')->limit(10)->select();
		
		$this->assign('wait',$wait);
		$this->assign('shouqu',$shouqu);
		$this->assign('suanli',$suanli);
		$this->assign('paihang',$paihang);
		
		//新增每天首次登录返算力		
		$start = mktime(0,0,0,date('m'),date('d'),date('Y'));
		$end = mktime(23,59,59,date('m'),date('d'),date('Y'));
		$is = M('myinvite')->where(array('userid'=>$userid,'createdate'=>array('between',array($start,$end)),'channel'=>4))->find();
		if(!$is){
			$config = M('config')->find(1);
			if($config['login_suanli']){
				$mo = M();
				$rs = array();
				$mo->startTrans();
				//给推荐人返算力
				$rs[] = $mo->table('myinvite')->add(array('userid'=>$userid,'type'=>2,'num'=>$config['login_suanli'],'status'=>1,'createdate'=>time(),'channel'=>4));
				$rs[] = $mo->table('user_coin')->where(array('userid'=>$userid))->setInc('lthz',$config['login_suanli']);
				if(check_arr($rs)){
					$mo->commit();
				}else{
					$mo->rollback();
				}
			}
		}
		
		//查找不在线的路由器
		$mydevice = M('user_device')->where(array('userid'=>$userid,'device_id'=>2))->select(); //2是路由器
		$jianqu = 0; //需要减去的算力
		if($mydevice){
			$device = M('device')->find(2);//路由器设置
			$remote = M('console_log','tfc_','mysql://console_tfc_kim:FiAdXkHEFxByNMcD@47.91.242.68/console_tfc_kim#utf8');
			//最近半个小时时间戳
			$end = time();
			$start = $end - 30*60;			
			foreach($mydevice as $d){				
				$is_on = $remote->where(array('sn'=>$d['sn'],'addtime'=>array('between',array($start,$end))))->find();
				if(!$is_on){
					$jianqu += $device['suanli'];
				}			
			}
		}
		$this->assign('jianqu',$jianqu);
		
		$this->display();
		exit;

		Vendor("Move.ext.client");

		$client = new \client('...','...', '..', 29416, 5, [], 1);
		if (!$client) {
			var_dump('aaa');
		}else{
			var_dump('a');
			echo '<pre>';
			//var_dump($client);
			//$res = $client->execute("listtransactions", ["*", 20, 0]);
			//$res = $client->execute("getinfo");
			$res = $client->getnewaddress();//生成新地址			
			//var_dump($res);
			//$res = $client->getaddressesbyaccount('15890143123');//获取新地址
				var_dump($res);
		}
		var_dump('dd');exit;
		$this->display();
	}
	//收取能量
	public function ajax_shouqu(){
		$id = I('post.id');
		if(!$id){
			echo ajax_return(0,'请求有误');exit;
		}
		$userid = session('userid');
		$info = M('sys_fl_log')->where(array('id'=>$id,'userid'=>$userid,'status'=>2))->lock(true)->find();
		if(!$info){
			echo ajax_return(0,'请求有误');exit;
		}
		$mo = M();
		$rs = array();
		$mo->startTrans();
		$rs[] = $mo->table('sys_fl_log')->where(array('id'=>$id,'userid'=>$userid,'status'=>2))->setField(array('status'=>1,'updatedate'=>time()));
		$rs[] = $mo->table('user_coin')->where(array('userid'=>$userid))->setInc('lth',$info['num']);
		
		if(check_arr($rs)){
			$mo->commit();
			echo json_encode(array('info'=>'success','msg'=>'收取成功','data'=>format_date(time()-1)));
		}else{
			$mo->rollback();
			echo ajax_return(0,'收取失败');
		}
	}
	
	//申请pos 路由
	public function ajax_add_shenqing(){
		$userid = session('userid');
		$shr = I('post.xm');
		$lxfs = I('post.lxfs');
		$address = I('post.address');
		$area = I('post.area');
		$type = I('post.type');
		$num = I('post.num');
		if(!$this->check_certification()){
			echo ajax_return(0,'请先进行实名认证');exit;
		}
		if($type !=1 && $type !=2){
			echo ajax_return(0,'请求参数不正确');exit;
		}
		if(!$shr || !$lxfs || !$address || !$area){
			echo ajax_return(0,'请求参数不正确');exit;
		}
		//
		if($type == 1){
			$info = M('user_shenqing')->where(array('userid'=>$userid,'type'=>$type))->find();
			if($info){
				echo ajax_return(0,'已经提交申请，无需重复申请，请等待管理人员与您联系');exit;
			}
		}
		$num = $num ? $num :1;
		$res = M('user_shenqing')->add(array('userid'=>$userid,'shr'=>$shr,'lxfs'=>$lxfs,'address'=>$address,'area'=>$area,'type'=>$type,'num'=>$num,'createdate'=>time()));
		if($res){
			echo ajax_return(1,'申请提交成功，请耐心等待管理人员与您联系');
		}else{
			echo ajax_return(0,'申请提交失败');
		}
	}
	
	//
	/**
     * 邀请记录
     */
    public function frozen()
    {
        $userid = session('userid');
        $p = I('param.p',1);
        $list = 5;
        $res = M('myinvite')->where(array('userid'=>$userid,'type'=>1))->order('id desc')->page($p.','.$list)->select();
        $this->assign('res',$res);
        $this->display();
    }
    /**
     * 转出记录
     */
    public function ajax_frozen()
    {
        $userid = session('userid');
        $p = I('param.p',1);
        $list = 5;
        $res = M('myinvite')->where(array('userid'=>$userid,'type'=>1))->order('id desc')->page($p.','.$list)->select();
        foreach($res as &$v){
            $v['date'] = date('m月d日');
            $v['time'] = date('H:i');
        }
        echo json_encode($res);
    }
}