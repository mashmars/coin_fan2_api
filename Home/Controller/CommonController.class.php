<?php
namespace Home\Controller;
use Think\Controller;

class CommonController extends Controller {
    /**
     * 必须登录
     */
    public function _initialize()
    {	
        $userid = session('userid');
        if(!$userid){
            //判断是否有cookie
			$denglu = $this->checkRemember();
			if($denglu){
				session("userid",$denglu['id']);
				session("phone",$denglu['phone']);
			}else{
				if(IS_AJAX){
					echo ajax_return(0,'请先登录系统');exit;
				}else{
					redirect(U('login/password'),0,'no msg');
				}
			}
        }elseif(session('refer') && !IS_AJAX){
			header('Location: https://www.pgyer.com/Nwzd');
		}
        //用户基本信息
        $userinfo = M('user')->where(array('id'=>$userid))->find();
        $this->assign('userinfo',$userinfo);
        //获取用户资产
        $usercoin = M('user_coin')->where(array('userid'=>$userid))->find();
		
        $this->assign('usercoin',$usercoin);
        
        //获取网站基本配置
        $config = M('config')->where('id=1')->find();
        $this->assign('config',$config);
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
}