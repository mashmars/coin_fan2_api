<?php
namespace Api\Controller;
use Think\Controller;

class CommonController extends Controller {
    /**
     * 必须登录
     */
    public function _initialize()
    {	
        $userid = session('userid');
		$userid=7;
        if(!$userid){
            //判断是否有cookie
			$denglu = $this->checkRemember();
			if($denglu){
				session("userid",$denglu['id']);
				session("phone",$denglu['phone']);
			}else{
				echo ajax_return(0,'请先登录系统');exit;
			}
        }
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