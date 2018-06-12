<?php
namespace Home\Controller;
use Think\Controller;

class LoginController extends Controller
{
	//生成随机数,用于生成salt
    public function random_str($length){
        //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
        $arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        $str = '';
        $arr_len = count($arr);
        for ($i = 0; $i < $length; $i++){
            $rand = mt_rand(0, $arr_len-1);
            $str.=$arr[$rand];
        }
        return $str;
    }
	public function saveRemember($userid,$identifier,$token,$timeout){
        $auth = M("sys_cookie");
        $data['identifier'] = $identifier;
        $data['token'] = $token;
        $data['timeout'] = $timeout;
        $data['userid'] = $userid;
        if($auth->where(array('userid'=>$userid))->find()){ 
			$auth->where(array('userid'=>$userid))->save($data);
		}else{
			$auth->add($data);
		}
    }
    /**
     * 登录
     */
	  public function phone(){
		 //判断是否有cookie
		$denglu = $this->checkRemember();
		if($denglu){
			session("userid",$denglu['id']);
			session("phone",$denglu['phone']);
			redirect('/');
		}
		 $this->display();
	 }
	 public function password(){
		 //判断是否有cookie
		$denglu = $this->checkRemember();
		if($denglu){
			session("userid",$denglu['id']);
			session("phone",$denglu['phone']);
			redirect('/');
		}
		 $this->display();
	 }
	 //验证用户是否永久登录（记住我）
    public function checkRemember(){
        $arr = array();
        $now = time();

        list($identifier,$token) = explode(':',$_COOKIE['auth']);
        if (ctype_alnum($identifier) && ctype_alnum($token)){
            $arr['identifier'] = $identifier;
            $arr['token'] = $token;
        }else{
            return false;
        }

        $auth = M("sys_cookie");
        $info = $auth->where(array('identifier'=>$arr['identifier']))->find();
        if($info != null){
            if($arr['token'] != $info['token']){
                return false;
            }else if($now > $info['timeout']){
                return false;
            }else{
				$res = M('user')->find($info['userid']);
                return $res;
            }
        }else{
            return false;
        }
    }

    /**
     * 登录发送验证码
     */
    public function ajax_send_sms_login()
    {
        $phone = I('post.phone');
        //判断手机号是否存在
        $id = M('user')->where(array('phone' => $phone))->getField('id');
        if (!$id) {
            echo ajax_return(0, '手机号未注册');
            exit;
        }
        $code = mt_rand(10000, 99999);
        $result = send_sms('78771', $phone, $code);
        if ($result['info'] == 'success') {
            session($phone . 'login', $code);
            echo ajax_return(1, '短信验证码发送成功');
        } else {
            echo ajax_return(0, $result['msg']);
        }
    }

    /**
     * 手机号验证码登录
     */
    public function ajax_login_phone()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');

        if ($sms != session($phone . 'login')) {
            echo ajax_return(0, '短信验证码不正确');
            exit;
        }
        //判断手机号是否存在
        $id = M('user')->where(array('phone' => $phone))->getField('id');
        if (!$id) {
            echo ajax_return(0, '手机号未注册');
            exit;
        }
        //可以登录
        echo ajax_return(1, '登录成功');
        session('userid', $id);
        session('phone', $phone);
        session($phone . 'login', null);
    }

    /**
     * 手机号密码登录
     */
    public function ajax_login_password()
    {
        $phone = I('post.phone');
        $password = I('post.password');
        $remember = I('post.remember');

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
        //可以登录
		if($remember){			
			//设置cookie 3天
			$salt = $this->random_str(16);
			//第二分身标识
			$identifier = md5($salt . md5($phone . $salt));
			//永久登录标识
			$token = md5(uniqid(rand(), true));
			//永久登录超时时间(3)
			$timeout = time()+3600*24*3;
			//存入cookie
			cookie('auth',"$identifier:$token",$timeout,"/");
			
			$this->saveRemember($info['id'],$identifier,$token,$timeout);
		}
        echo ajax_return(1, '登录成功');
        session('userid', $info['id']);
        session('phone', $phone);
    }

    /**
     * 找回密码发送短信验证码
     */
    public function ajax_send_sms_find()
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
    public function ajax_find_password()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');
        $newpassword = I('post.newpassword');
        $newpassword2 = I('post.newpassword2');
        if ($sms != session($phone . 'find')) {
            echo ajax_return(0, '短信验证码不正确');
            exit;
        }
        if($newpassword != $newpassword2 || $newpassword == ''){
            echo ajax_return(0,'两次密码输入不一致');exit;
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

	public function register(){
        $mobile = I('mobile');
        $this->assign('mobile',$mobile);
		if($mobile){
			session('refer',$mobile);
		}
		$this->display();
	}
	//发送注册短信验证
    public function ajax_send_sms()
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
	//注册前获取邀请人信息ajax获取到当前邀请人下面有人，就返回状态，让一区二区显示出来就可以了 //暂时不用
	public function ajax_getrefer()
	{
		$refer = I('post.refer');
		$id = M('user')->where(array('phone'=>$refer))->getField('id');
		if(!$id){
			echo json_encode(array('info'=>'other','msg'=>'邀请人手机号不正确'));exit;
		}else{
			//判断当前用户下面有人没 有的话 返回success 否则返回erroe
			$zone = M('user_zone')->where(array('pid'=>$id))->find();
			if($zone){
				echo ajax_return(1,'没人');
			}else{
				echo ajax_return(0,'有人');
			}
		}
	}
    //注册
    public function ajax_register()
    {
        $phone = I('post.phone');
        $sms = I('post.sms');
        $realname = I('post.realname');
        $password = I('post.password');
        $paypassword = I('post.paypassword');
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
        if($password == $paypassword){
            echo ajax_return(0,'登录密码和支付密码不能一样');exit;
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
            echo ajax_return(1,'注册成功');
        }else{
            $mo->rollback();
            echo ajax_return(0,'注册失败');
        }

    }
    /**
     * 注册分区 规则如下
     * 注册的时候选择A区或B区，如果是A区，则放到下级的A区上，
     */
    private  function user_zone($userid,$ownid,$pid,$zone)
    {
        if($pid == 0){
            $res = M('user_zone')->add(array('userid'=>$userid,'ownid'=>0,'pid'=>0,'zone'=>0));

        }else{
            $res = $this->add_zone($userid,$ownid,$zone,$pid,$zone);

        }
        if(!$res){
            return false;
        }
        return true;
    }
    /**
     *判断上级的该区是否有人，如果没有人，满足条件直接分配上  如果有人，则遍历下级的一区是否有人 没人则满足条件分配上
     * （考虑个特殊情况，如果分配的是2区，要先判断同级的1区是否有安排人，没有则直接安排上不用再遍历下级了）
     * 如果同级的1区有人，则遍历属于这个人下级且是2区的下级1区是否有人，没人则分配安排上
     * 加行锁 防止同时两个人挂一个人下面的情况
     * $userid 注册会员id $ownid 推荐人id $init_zone注册选择的分区 $pid 节点人的id $zone 要找的哪个区 遍历用
     */
    private function add_zone($userid,$ownid,$init_zone,$pid,$zone)
    {

        $info = M('user_zone')->where(array('pid'=>$pid,'zone'=>$zone))->lock(true)->find();
        if(!$info){
            $res = M('user_zone')->add(array('userid'=>$userid,'ownid'=>$ownid,'pid'=>$pid,'zone'=>$zone));
            if(!$res){
                return false;
            }
            return true;

        }else{

            if($init_zone == 2){
                $init_zone =1 ; //保证只找最初的一次
                //同级的一区是否有人 特殊情况
                $yiqu = M('user_zone')->where(array('pid'=>$pid,'zone'=>1))->lock(true)->find();
                if(!$yiqu){
                    $res = M('user_zone')->add(array('userid'=>$userid,'ownid'=>$ownid,'pid'=>$pid,'zone'=>1));
                    if(!$res){
                        return false;
                    }
                    return true;
                }
            }
            //遍历
           return $this->add_zone($userid,$ownid,$init_zone,$info['userid'],1);
        }
    }
}