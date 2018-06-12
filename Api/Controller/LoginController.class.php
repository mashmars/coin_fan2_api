<?php
namespace Api\Controller;
use Think\Controller;

class LoginController extends Controller
{
	

    /**
     * 手机号密码登录
     */
    public function login_password()
    {
        $phone = I('post.phone');
        $password = I('post.password');
       
        if ($password == '') {
            echo ajax_return(0, '登录密码不能为空');
            exit;
        }
        //判断手机号是否存在
        $info = M('user')->where(array('phone' => $phone))->find();
        if (!$info) {
            echo ajax_return(0, '手机号未注册');
            exit;
        }
        if ($info['password'] != md5($password)) {
            echo ajax_return(0, '登录密码不正确');
            exit;
        }       
        
        session('userid', $info['id']);
        session('phone', $phone);
		
		echo ajax_return(1,'',$info);
    }

    /**
     * 找回密码发送短信验证码
     */
    public function send_sms_find()
    {
        $phone = I('post.phone');
        //判断手机号是否存在
        $id = M('user')->where(array('phone' => $phone))->getField('id');
        if (!$id) {
            echo ajax_return(0, '手机号不存在');
            exit;
        }
        $code = mt_rand(10000, 99999);
        $result = send_sms('78771', $phone, $code);
        if ($result['info'] == 'success') {
            session($phone . 'find', $code);
            echo ajax_return(1, '短信验证码发送成功');
        } else {
            echo ajax_return(0, $result['msg']);
        }
    }

    /**
     * 找回密码提交
     */
    public function find_password()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');
        $newpassword = I('post.newpassword');
        $newpassword2 = I('post.newpassword2');
		
		if($sms == ''){
            echo ajax_return(0,'短信验证码不能为空');exit;
        }
		
        if ($sms != session($phone . 'find')) {
            echo ajax_return(0, '短信验证码不正确');
            exit;
        }
        if($newpassword != $newpassword2 || $newpassword == ''){
            echo ajax_return(0,'两次密码输入不一致或密码不能为空');exit;
        }
        //判断手机号是否存在
        $info = M('user')->where(array('phone' => $phone))->find();
        if (!$info) {
            echo ajax_return(0, '手机号未注册');
            exit;
        }
        //可以更改
        $res = M('user')->where(array('id'=>$info['id']))->setField(array('password'=>md5($newpassword)));
        if($res){
            echo ajax_return(1, '修改成功');
            session($phone . 'find',null);
        }else{
            echo ajax_return(0, '修改失败');
        }
    }

	
	//发送注册短信验证
    public function send_sms()
    {
        $phone = I('post.phone');
        //判断手机号是否存在
        $id = M('user')->where(array('phone'=>$phone))->getField('id');
        if($id){
            echo ajax_return(0,'手机号已注册');exit;
        }
        $code = mt_rand(10000,99999);
        $result = send_sms('78771',$phone,$code);
        if($result['info'] == 'success'){
            session($phone.'reg',$code);
            echo ajax_return(1,'短信验证码发送成功');
        }else{
            echo ajax_return(0,$result['msg']);
        }
    }
	
	public function register()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');
        $realname = I('post.realname');
        $password = I('post.password');
        $password = I('post.password2');
        

        /**
         * 注册流程，
         * 1，必要条件验证
         * 2，判断短信验证码是否存在
         * 3，判断手机号是否存在 以及上级手机号是否存在
         * 4，注册成功 同时建立资产表
         */
        //1
        if(!$phone){
            echo ajax_return(0,'手机号不正确');exit;
        }
        if($sms == ''){
            echo ajax_return(0,'短信验证码不能为空');exit;
        }
        if(!$password){
            echo ajax_return(0,'登录密码设置不正确');exit;
        }
        
        if($password != $password2){
            echo ajax_return(0,'登录密码和确认密码不一样');exit;
        }

        //2
        if($sms != session($phone . 'reg')){
            echo ajax_return(0,'短信验证码不正确');exit;
        }
        //3
        $id = M('user')->where(array('phone'=>$phone))->getField('id');
        if($id){
            echo ajax_return(0,'手机号已注册');exit;
        }
        
		session('phone',$phone);
		session('sms',$sms);
		session('password',$password);
		session('realname',$realname);
		
		echo ajax_return(1,'验证通过');

    }
    //注册
    public function register_finish()
    {
        $phone = session('phone');
        $sms = session('sms');
        $realname = session('realname');
        $password = session('password');
        
		$paypassword = I('post.paypassword');
        $paypassword2 = I('post.paypassword2');
        $refer = I('post.refer');

        /**
         * 注册流程，
         * 1，必要条件验证
         * 2，判断短信验证码是否存在
         * 3，判断手机号是否存在 以及上级手机号是否存在
         * 4，注册成功 同时建立资产表
         */
        //1
        if(!$phone){
            echo ajax_return(0,'手机号不正确');exit;
        }
        if($sms == ''){
            echo ajax_return(0,'短信验证码不能为空');exit;
        }
        if(!$password){
            echo ajax_return(0,'登录密码设置不正确');exit;
        }
        if(!$paypassword){
            echo ajax_return(0,'支付密码设置不正确');exit;
        }
        if($paypassword != $paypassword2){
            echo ajax_return(0,'支付密码和确认密码不能一样');exit;
        }

        //2
        if($sms != session($phone . 'reg')){
            echo ajax_return(0,'短信验证码不正确');exit;
        }
        //3
        $id = M('user')->where(array('phone'=>$phone))->getField('id');
        if($id){
            echo ajax_return(0,'手机号已注册');exit;
        }
        if($refer){
            $id = M('user')->where(array('phone'=>$refer))->getField('id');
            if(!$id){
                echo ajax_return(0,'推荐人手机号不存在');exit;
            }else{
                $pid = $id;
            }
        }else{
            $pid = 0;
        }
		$config = M('config')->find(1);
		
        //4
        $mo = M();
        $mo->startTrans();
        $rs = array();
        //注册成功
        $rs[] = $mo->table('user')->add(array('phone'=>$phone,'username'=>$phone,'password'=>md5($password),'paypassword'=>md5($paypassword),'pid'=>$pid,'realname'=>$realname,'createdate'=>time()));
        //插入资产表
		//给自己返
		$rs[] = $mo->table('user_coin')->add(array('userid'=>$rs[0],'lth'=>$config['invite'],'lthd'=>$config['invite_dongjie'],'lthz'=>$config['register_suanli']));
		if($config['invite']>0){ //直接返币
			$rs[] = $mo->table('myinvite')->add(array('userid'=>$rs[0],'from_id'=>$rs[0],'type'=>1,'num'=>$config['invite'],'status'=>1,'createdate'=>time(),'channel'=>1));
		}
		if($config['invite_dongjie']>0){ //返币 冻结状态
			$rs[] = $mo->table('myinvite')->add(array('userid'=>$rs[0],'from_id'=>$rs[0],'type'=>1,'num'=>$config['invite_dongjie'],'status'=>0,'createdate'=>time(),'channel'=>1));
		}
		if($config['register_suanli']>0){
			$rs[] = $mo->table('myinvite')->add(array('userid'=>$rs[0],'from_id'=>'','type'=>2,'num'=>$config['register_suanli'],'status'=>1,'createdate'=>time(),'channel'=>1));//注册送算力 from_id为空
		}
		
		//给上级返和上上级返
		$pid = M('user')->where(array('id'=>$rs[0]))->getField('pid');//上级
		if($pid){
			$ppid = M('user')->where(array('id'=>$pid))->getField('pid');//上上级
		}
		if($pid){
            if($config['invite1']){
                //给推荐人返原力币
                $rs[] = $mo->table('myinvite')->add(array('userid'=>$pid,'from_id'=>$rs[0],'type'=>1,'num'=>$config['invite1'],'status'=>0,'createdate'=>time(),'channel'=>2));
                $rs[] = $mo->table('user_coin')->where(array('userid'=>$pid))->setInc('lthd',$config['invite1']);
            }
			if($config['invite1_suanli']){
                //给推荐人返算力
                $rs[] = $mo->table('myinvite')->add(array('userid'=>$pid,'from_id'=>$rs[0],'type'=>2,'num'=>$config['invite1_suanli'],'status'=>1,'createdate'=>time(),'channel'=>2));
                $rs[] = $mo->table('user_coin')->where(array('userid'=>$pid))->setInc('lthz',$config['invite1_suanli']);
            }
        }
		if($ppid){
            if($config['invite2']){
                //给推荐人的推荐人返原力币
                $rs[] = $mo->table('myinvite')->add(array('userid'=>$ppid,'from_id'=>$rs[0],'type'=>1,'num'=>$config['invite2'],'status'=>0,'createdate'=>time(),'channel'=>2));
                
                $rs[] = $mo->table('user_coin')->where(array('userid'=>$ppid))->setInc('lthd',$config['invite2']);
            }
			if($config['invite2_suanli']){
                //给推荐人的推荐人返算力
                $rs[] = $mo->table('myinvite')->add(array('userid'=>$ppid,'from_id'=>$rs[0],'type'=>2,'num'=>$config['invite2_suanli'],'status'=>1,'createdate'=>time(),'channel'=>2));
                $rs[] = $mo->table('user_coin')->where(array('userid'=>$ppid))->setInc('lthz',$config['invite2_suanli']);
            }
        }

        if(check_arr($rs)){
            $mo->commit();
            session($phone . 'reg' ,null);
			session('phone',null);
			session('sms',null);
			session('password',null);
			session('realname',null);
            echo ajax_return(1,'注册成功');
        }else{
            $mo->rollback();
            echo ajax_return(0,'注册失败');
        }

    }
   
}