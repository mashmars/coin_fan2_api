<?php
namespace Admin\Controller;

use Think\Controller;
use Admin\Controller\BaseController;
use Think\Upload;
class UserController extends BaseController
{	
	

    public function member_list()
    {	

        if(IS_POST){
            $data = I('post.');
            $field = $data['field'];
            $value = $data['keyword'];
            $res = M('user')->where(array($field=>$value))->select();
            $this->assign('res',$res);
            $this->assign('field',$field);
            $this->assign('keyword',$value);
            $this->display();
            exit;
		}else{
			$p = I('param.p', 1);
			$list = 10;
			$res = M('user')->page($p . ',' . $list)->order('id desc')->select();
			$count = M('user')->count();
			$page = new \Think\Page($count, $list);
			$show = $page->show();
			$this->assign('res', $res);
			$this->assign('page', $show);
			$this->assign('count', $count);
			$this->display();
		}
       
    }
	
	/*用户信息*/
	public function ajax_member_add(){
		$data = I('post.');
        //密码加密
        if($data['password'] == '' || $data['paypassword'] == '' || $data['phone'] == ''){
            echo ajax_return(0,'登录密码和支付密码,手机号不能为空');exit;
        }
        $data['password'] = md5($data['password']);
        $data['paypassword'] = md5($data['paypassword']);

		//判断上级用户名 手机号 不存在
        $phone = $data['phone'];
        $exist = M('user')->where(array('phone'=>$phone))->find();
        if($exist){
            echo ajax_return(0,'手机号已存在');exit;
        }
        //判断上级是否存在
        $upname = $data['refer'];
        if($upname){
            $id = M('user')->where(array('phone'=>$upname))->getField('id');
            if($id){
                $data['pid'] = $id;
            }else{
                echo ajax_return(0,'指定的推荐人手机号不存在');exit;
            }
        }else{
            $data['pid'] = 0;
        }
        //当前时间
        $data['createdate'] = $data['createdate'] ? strtotime($data['createdate']) : time();
        $data['username'] = $data['phone'];

        //去掉
        unset($data['refer']);
        unset($data['zone']);
		
		$config = M('config')->find(1);
		
        $mo = M();
        $mo->startTrans();
        $rs = array();
        //注册成功
        $rs[] = $mo->table('user')->add($data);
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



	public function member_edit(){
		$id = I('param.id');
		$info = M('user')->where("id=$id")->find();
		$this->assign('info',$info);
		$this->display();
	}
	public function ajax_member_edit(){
		$data = I('post.');
		$id = $data['id'];
		unset($data['id']);
		if(!$data['realname']){
			echo ajax_return(0,'姓名不能为空');exit;
		}
		if($data['password']){
		    $data['password'] = md5($data['password']);
        }else{
		    unset($data['password']);
        }
        if($data['paypassword']){
            $data['paypassword'] = md5($data['paypassword']);
        }else{
            unset($data['paypassword']);
        }
        $data['createdate'] = $data['createdate'] ? strtotime($data['createdate']) : time();
		$res = M('user')->where("id=$id")->save($data);
		if($res){
			echo ajax_return(1,'编辑成功');
		}else{
			echo ajax_return(0,'编辑失败');
		}
	}
	//TODO:是否删除资产
	public function ajax_user_delete(){
		$id = I('post.id');
		$res = M('users')->where("id=$id")->delete();
		if($res){
			echo ajax_return(1,'删除成功');
		}else{
			echo ajax_return(0,'删除失败');
		}
	}

	//资产管理
    public function member_coin(){
        if(IS_POST){
            $data = I('post.');
            $field = $data['field'];
            $value = $data['keyword'];
            $res = M('user')->where(array($field=>$value))->find();
            if(!$res){
                $this->error('手机号不存在');exit;
            }
            $res = M('user_coin')->alias('a')->join('left join user b on a.userid=b.id')->where(array('b.id'=>$res['id']))->field('a.*,b.username,b.phone')->select();
            $this->assign('res',$res);
            $this->assign('field',$field);
            $this->assign('keyword',$value);
            $this->display();
            exit;
        }else{
            $list = 10;
            $p = I('param.p',1);

            $res = M('user_coin')->alias('a')->join('left join user b on a.userid=b.id')->field('a.*,b.username,b.phone')->page($p . ',' .$list)->select();
            $count = M('user_coin')->count();
            $page = new \Think\Page($count,$list);
            $show = $page->show();

            $this->assign('res',$res);
            $this->assign('page',$show);
            $this->assign('count',$count);

            $this->display();
        }
    }

    //资产编辑
    public function member_coin_edit(){
	    $id = I('param.id');
	    $info = M('user_coin')->alias('a')->join('left join user b on a.userid=b.id')->where(array('a.id'=>$id))->field('a.*,b.username,b.phone')->find();
	    $this->assign('info',$info);
	    $this->display();
    }
    //资产编辑提交
    public function ajax_member_coin_edit(){
	    $data = I('post.');
	    $res = M('user_coin')->save($data);
	    if($res){
            echo ajax_return(1,'更新成功');
        }else{
	        echo ajax_return(0,'更新失败');
        }
    }


    //充值
    public function member_chongzhi(){
        if(IS_POST){
            $data = I('post.');
            $field = $data['field'];
            $value = $data['keyword'];
            $res = M('user')->where(array($field=>$value))->find();
            if(!$res){
                $this->error('手机号不存在');exit;
            }
            $res = M('mycz')->alias('a')->join('left join user b on a.userid=b.id')->where(array('b.id'=>$res['id']))->field('a.*,b.username,b.phone')->select();
            $this->assign('res',$res);
            $this->assign('field',$field);
            $this->assign('keyword',$value);
            $this->display();
            exit;
        }else{
            $list = 10;
            $p = I('param.p',1);

            $res = M('mycz')->alias('a')->join('left join user b on a.userid=b.id')->field('a.*,b.username,b.phone')->page($p . ',' .$list)->select();
            $count = M('mycz')->count();
            $page = new \Think\Page($count,$list);
            $show = $page->show();

            $this->assign('res',$res);
            $this->assign('page',$show);
            $this->assign('count',$count);

            $this->display();
        }
    }
    public function ajax_member_chongzhi()
    {
        $phone = I('post.phone');
        $num = I('post.num');
        if($num <= 0 || !is_numeric($num)){
            echo ajax_return(0,'充值数量不正确');exit;
        }
        $id = M('user')->where(array('phone'=>$phone))->getField('id');
        if(!$id){
            echo ajax_return(0,'手机号不正确');exit;
        }
        $mo = M();
        $mo->startTrans();
        $rs = array();

        $rs[] = $mo->table('user_coin')->where(array('userid'=>$id))->setInc('lth',$num);
        $rs[] = $mo->table('mycz')->add(array(
            'userid'    => $id,
            'num'   =>$num,
            'createdate'    =>time()
        ));

        if(check_arr($rs)){
            $mo->commit();
            echo ajax_return(1,'充值成功');exit;
        }else{
            $mo->rollback();
            echo ajax_return(0,'充值失败');exit;
        }
    }


    /**
     * 我的团队
     */
    public function team()
    {
        $users = M('user_zone')->alias('a')->join('left join user b on a.userid=b.id')->field('a.*,b.phone')->where('a.pid=0')->select();
        $data = '';
        foreach($users as $user){
            $data .= $this->get_team($user['userid'],$user['phone']);
        }

       // $data = $this->get_team($userid);
        $data = '{"name":"我的团队","children":[' . $data;
        $data .= ']}';
        $this->assign('data',$data);
        $this->display();
    }
    public function get_team($userid,$phone='',$new=true)
    {
        static $data = '';
        if($new){
            $data = '';
        }
        $users = M('user_zone')->alias('a')->join('left join user b on a.userid=b.id')->where(array('a.pid'=>$userid))->field('a.*,b.phone')->select();
        if($users[0]) {
            foreach ($users as $user) {
                if ($user) {
                    //有下级
                    $data .= '{"name":"' . $user['phone'] . '","children":[';
                    $this->get_team($user['userid'],$user['phone'],false);
                    $data .= ']},';
                }/*else{
                //没有下级
                $data .= '{"name":"'.$user['phone'].'"},';
            }*/
            }
        }else{
            //没有下级
            if($new){
                $data .= '{"name":"'. $phone .'"},';
            }
        }
        return $data;
    }

	/**
     * 实名认证管理
     */
    public function member_certification()
    {

        if(IS_POST){
            $data = I('post.');
            $field = $data['field'];
            $value = $data['keyword'];
            $userid = M('user')->where(array($field=>$value))->getField('id');
            $res = M('user_certification')->where(array('userid'=>$userid))->select();
            $this->assign('res',$res);
            $this->assign('field',$field);
            $this->assign('keyword',$value);
            $this->display();
            exit;
        }else{
            $p = I('param.p', 1);
            $list = 10;
            $res = M('user_certification')->alias('a')->join('left join user b on a.userid=b.id')->field('a.*,b.phone')->page($p . ',' . $list)->order('a.status asc,a.id desc')->select();
            $count = M('user_certification')->count();
            $page = new \Think\Page($count, $list);
            $show = $page->show();
            $this->assign('res', $res);
            $this->assign('page', $show);
            $this->assign('count', $count);
            $this->display();
        }
    }

    /**
     * 审核
     */
    public function ajax_member_certification_shenhe()
    {
        $id = I('post.id');
        $info = M('user_certification')->where(array('id'=>$id))->find();
        if(!$info){
            echo ajax_return(0,'请求有误');exit;
        }
        if($info['status'] ==1){
            echo ajax_return(0,'此认证已通过审核，无需重复审核');exit;
        }
        $res = M('user_certification')->where(array('id'=>$id))->setField('status',1);

        if($res){
            echo ajax_return(1,'审核成功');
            M('user')->where(array('id'=>$info['userid']))->save(array('is_cert'=>1,'realname'=>$info['realname'],'idcard'=>$info['idcard']));
        }else{
            echo ajax_return(0,'审核失败');exit;
        }
    }
	public function ajax_member_certification_nopass()
    {
        $id = I('post.id');
        $info = M('user_certification')->where(array('id'=>$id))->find();
        if(!$info){
            echo ajax_return(0,'请求有误');exit;
        }
        if($info['status'] ==1){
            echo ajax_return(0,'此认证已通过审核，无需重复审核');exit;
        }
        $res = M('user_certification')->where(array('id'=>$id))->setField('status',2);

        if($res){
            echo ajax_return(1,'操作成功');            
        }else{
            echo ajax_return(0,'操作失败');exit;
        }
    }
    /**
     * 删除 同时删除相关图片
     */
    public function ajax_member_certification_del()
    {
        $id = I('post.id');
        $info = M('user_certification')->where(array('id'=>$id))->find();
        if(!$info){
            echo ajax_return(0,'请求有误');exit;
        }
        if($info['status'] ==1){
            echo ajax_return(0,'此认证已通过审核，不能删除');exit;
        }
        $res = M('user_certification')->delete($id);
        if($res){
            echo ajax_return(1,'删除成功');
            unlink('.' . UP_USER . $info['zheng']);
            unlink('.' . UP_USER . $info['fan']);
            unlink('.' . UP_USER . $info['shou']);
        }else{
            echo ajax_return(0,'删除失败');exit;
        }
    }


}