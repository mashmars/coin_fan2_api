<?php
namespace Admin\Controller;
use Think\Controller;
use Admin\Controller\BaseController;

class DeviceController extends BaseController {
	public function device(){
	    $p = I('param.p',1);
	    $list = 10;
	    $res = M('device')->page($p.','.$list)->select();
	    $count = M('device')->count();
	    $page = new \Think\Page($count,$list);
	    $show = $page->show();
	    $this->assign('res',$res);
	    $this->assign('count',$count);
	    $this->assign('page',$show);
	    $this->display();
	}
	public function ajax_device_add()
    {
        $data = I('post.');
        if($data['name'] == ''){
            echo ajax_return(0,'设备名称不能为空');exit;
        }
        $res = M('device')->add($data);
        if($res){
            echo ajax_return(1,'添加成功');
        }else{
            echo ajax_return(0,'添加失败');
        }
    }
    public function device_edit()
    {
        $id = I('param.id');
        $info = M('device')->where(array('id'=>$id))->find();
        $this->assign('info',$info);
        $this->display();
    }
    public function ajax_device_edit()
    {
        $data = I('post.');
        if($data['name'] == ''){
            echo ajax_return(0,'设备名称不能为空');exit;
        }
        $res = M('device')->save($data);
        if($res){
            echo ajax_return(1,'编辑成功');
        }else{
            echo ajax_return(0,'编辑失败');
        }
    }
    public function ajax_device_del()
    {
        $id = I('post.id');
        echo ajax_return(0,'不支持删除，否则对程序会造成很大影响，在下面验证消费记录那');exit;
        $res = M('device')->delete($id);
        if($res){
            echo ajax_return(1,'删除成功');
        }else{
            echo ajax_return(0,'删除失败');
        }
    }

    public function sn(){
        $p = I('param.p',1);
        $list = 10;
		$status = I('param.status');
		$name = I('param.name');
		$keyword = I('param.keyword');
		if($status !==''){
			$map['a.status'] = $status;
		}
		if($name !==''){
			$map['name'] = $name;
		}
		if($keyword !==''){
			$map['a.sn'] = $keyword;
		}
        //$res = M('device_sn')->alias('a')->join('left join device b on a.device_id=b.id')->join('left join user_device c on a.sn=c.sn')->join('left join user d on c.userid=d.id')->where($map)->field('a.*,b.name,d.realname,d.phone')->page($p.','.$list)->select();
        $res = M('device_sn')->alias('a')->join('left join device b on a.device_id=b.id')->where($map)->field('a.*,b.name')->page($p.','.$list)->order('id desc')->select();
		foreach($res as &$v){
			if(strlen($v['sn']) == 10){
				$sn = $v['sn'];
			}else{
				$sn = substr($v['sn'],0,strlen($v['sn'])-1); //15位
			}
			
			$userid = M('user_device')->where(array('sn'=>$sn))->getField('userid');
			if($userid){
				$user = M('user')->field('phone,realname')->find($userid);
				$v['phone'] = $user['phone'];
				$v['realname'] = $user['realname'];
			}
		}
        $count = M('device_sn')->alias('a')->join('left join device b on a.device_id=b.id')->where($map)->count();
        $page = new \Think\Page($count,$list);
		//分页跳转的时候保证查询条件
		foreach($map as $key=>$val) {
			$page->parameter[$key]   = $val;
		}
        $show = $page->show();
        $this->assign('res',$res);
        $this->assign('count',$count);
        $this->assign('page',$show);
        $this->assign('status',$status);
        $this->assign('keyword',$keyword);
        $this->assign('name',$name);
        $this->display();
    }
	/**
     * 消费记录导入 先上传 再导入
     */
    public function import_sn(){
        if(IS_POST){
            $up = new \Think\Upload();
            $up->exts = array('xls','xlsx');

            $up->subName  =''; // 子目录创建方式
            $up->rootPath  = '.' . UP_INFO;

            $info = $up->uploadOne($_FILES['info']);
            if(!$info){
                $this->error($up->getError());
            }else{
                $filename =  $info['savepath'] . $info['savename'];

                //导入数据库
                $this->import_info_sn($filename);

            }
        }
    }
   
    //导入数据库操作
    private function import_info_sn($filename){
        vendor('PHPExcel');
        Vendor("PHPExcel.IOFactory");
        Vendor("PHPExcel.Reader.Excel5");
        Vendor("PHPExcel.Reader.Excel2007");

        $filename = '.' . UP_INFO . $filename; //文件位置
        if(!is_file($filename)){
            $this->error('excel文件有误,请检查!','',2);
        }
        /*$objReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objPHPExcel = $objReader->load($filename,$encode='utf-8');
        */
        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objPHPExcel = $objReader ->load($filename);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader ->load($filename);
        } else if ($extension=='csv') {
            $PHPReader = new \PHPExcel_Reader_CSV();
            //默认输入字符集
            $PHPReader->setInputEncoding('GBK');
            //默认的分隔符
            $PHPReader->setDelimiter(',');
            //载入文件
            $objPHPExcel = $PHPReader->load($filename);
        }


        $sheet = $objPHPExcel->getSheet(0);
        $columns = $sheet->getHighestColumn(); //获取最大列数

        if($columns != 'C'){
            $this->error('excel表格式列数不正确!'.$columns,'',1);
        }
        $rows = $sheet->getHighestRow();             //获取最大行数

        //要导入的字段名
        $fields = array('device_id','sn','mima');

        $column =array('A','B','C');

        for($i=2;$i<=$rows;$i++){

            $data = array();
            for($j=0;$j<count($column);$j++){
                if($fields[$j] == 'sn'){
                    $d = $objPHPExcel->getActiveSheet()->getCell($column[$j].$i)->getValue();
                    if(M('device_sn')->where(array('sn'=>$d))->getField('id')){
                        $data['no_insert'] = true;
                    }
                }
                $data[$fields[$j]] = $objPHPExcel->getActiveSheet()->getCell($column[$j].$i)->getValue();

            }

            if($data['no_insert']){
                continue;
            }

            $data['status'] = 1;
            M('device_sn')->add($data);
        }

        $this->success('导入数据库成功!','',1);

    }
    public function sn_add()
    {
        $device = M('device')->select();

        $this->assign('device',$device);
        $this->display();
    }
    public function ajax_sn_add()
    {
        $data = I('post.');
        if($data['device_id'] == ''){
            echo ajax_return(0,'设备不能为空');exit;
        }
        $res = M('device_sn')->add($data);
        if($res){
            echo ajax_return(1,'添加成功');
        }else{
            echo ajax_return(0,'添加失败');
        }
    }
    public function sn_edit()
    {
        $device = M('device')->select();
        $id = I('param.id');
        $info = M('device_sn')->where(array('id'=>$id))->find();
        $this->assign('info',$info);
        $this->assign('device',$device);
        $this->display();
    }
    public function ajax_sn_edit()
    {
        $data = I('post.');
        if($data['device_id'] == ''){
            echo ajax_return(0,'设备不能为空');exit;
        }
        $res = M('device_sn')->save($data);
        if($res){
            echo ajax_return(1,'编辑成功');
        }else{
            echo ajax_return(0,'编辑失败');
        }
    }
    public function ajax_sn_del()
    {
        $id = I('post.id');

        $res = M('device_sn')->delete($id);
        if($res){
            echo ajax_return(1,'删除成功');
        }else{
            echo ajax_return(0,'删除失败');
        }
    }


    /**
     * 消费记录导入 先上传 再导入
     */
    public function import(){
        if(IS_POST){
            $up = new \Think\Upload();
            $up->exts = array('xls','xlsx','csv');

            $up->subName  =''; // 子目录创建方式
            $up->rootPath  = '.' . UP_INFO;

            $info = $up->uploadOne($_FILES['info']);
            if(!$info){
                $this->error($up->getError());
            }else{
                $filename =  $info['savepath'] . $info['savename'];

                //导入数据库
                $this->import_info($filename);

            }
        }
    }
	/**
	 消费记录的搜索功能 搜索某个设备号的交易记录 同时再加上设备对应的姓名手机号，金额的升降序排列  总金额和手续费的统计
	**/
    public function device_log(){
        $p = I('param.p',1);
        $list = 10;
		$sn1 = I('post.sn');
		if($sn1){
			$map['a.device_sn'] = $sn1;
		}
        //$res = M('device_xiaofei_log')->alias('a')->join('left join user_device b on a.device_sn=b.sn')->join('left join user c on b.userid=c.id')->where($map)->page($p.','.$list)->field('a.*,c.realname,c.phone')->order('a.money desc')->select();
        $res = M('device_xiaofei_log')->alias('a')->where($map)->page($p.','.$list)->field('a.*')->order('a.id desc')->select();
        foreach($res as &$v){
			
			if(strlen($v['device_sn']) == 10){
				$sn = $v['device_sn'];
			}else{
				$sn = substr($v['device_sn'],0,strlen($v['device_sn'])-1);
			}
			$userid = M('user_device')->where(array('sn'=>$sn))->getField('userid');
			if($userid){
				$user = M('user')->field('phone,realname')->find($userid);
				$v['phone'] = $user['phone'];
				$v['realname'] = $user['realname'];
			}
		}
		
		$count = M('device_xiaofei_log')->alias('a')->where($map)->count();
		//总金额和手续费的统
		$money = M('device_xiaofei_log')->alias('a')->where($map)->sum('money');
		$fee = M('device_xiaofei_log')->alias('a')->where($map)->sum('fee');
        $page = new \Think\Page($count,$list);
        $show = $page->show();
        $this->assign('res',$res);
        $this->assign('count',$count);
        $this->assign('page',$show);
        $this->assign('sn',$sn1);
        $this->assign('money',$money);
        $this->assign('fee',$fee);
        $this->display();
    }
    //导入数据库操作
    private function import_info($filename){
        vendor('PHPExcel');
        Vendor("PHPExcel.IOFactory");
        Vendor("PHPExcel.Reader.Excel5");
        Vendor("PHPExcel.Reader.Excel2007");

        $filename = '.' . UP_INFO . $filename; //文件位置
        if(!is_file($filename)){
            $this->error('excel文件有误,请检查!','',2);
        }
        /*$objReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objPHPExcel = $objReader->load($filename,$encode='utf-8');
        */
        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objPHPExcel = $objReader ->load($filename);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objPHPExcel = $objReader ->load($filename);
        } else if ($extension=='csv') {
            $PHPReader = new \PHPExcel_Reader_CSV();
            //默认输入字符集
            $PHPReader->setInputEncoding('GBK');
            //默认的分隔符
            $PHPReader->setDelimiter(',');
            //载入文件
            $objPHPExcel = $PHPReader->load($filename);
        }


        $sheet = $objPHPExcel->getSheet(0);
        $columns = $sheet->getHighestColumn(); //获取最大列数

        if($columns != 'N'){
            $this->error('excel表格式列数不正确!','',1);
        }
        $rows = $sheet->getHighestRow();             //获取最大行数

        //要导入的字段名
        $fields = array('jgmc','device_sn','zdpc','jylx','order_sn','day','time','money','fee','yhkh','sjhm','khxm','sxfl','gdsxf');

        $column =array('A','B','C','D','E','F','G','H','I','J','K','L','M','N');

        for($i=2;$i<=$rows;$i++){

            $data = array();
            for($j=0;$j<count($column);$j++){
                if($fields[$j] == 'order_sn'){
                    $d = $objPHPExcel->getActiveSheet()->getCell($column[$j].$i)->getValue();
                    if(M('device_xiaofei_log')->where(array('order_sn'=>$d))->getField('id')){
                        $data['no_insert'] = true;
                    }
                }
                $data[$fields[$j]] = trim($objPHPExcel->getActiveSheet()->getCell($column[$j].$i)->getValue(),"'");

            }

            if($data['no_insert']){
                continue;
            }

            $data['createdate'] = time();
            M('device_xiaofei_log')->add($data);
        }

        $this->success('导入数据库成功!','',1);

    }

    /**
     * 验证消费记录
     * 规则 都是pos验证， 如果sn码不在发布的里面 直接作废。 如果对应的设备未激活 先去激活 激活后的消费记录返算力
     */
    public function ajax_verify_xiaofei()
    {
        $device_sns = M('device_xiaofei_log')->distinct(true)->where(array('status'=>0))->getField('device_sn',true);
        if(!$device_sns){
            echo ajax_return(0,'没有可用的消费记录');exit;
        }
        $xiaofei = M('device_xiaofei_log')->where(array('device_sn'=>array('in',$device_sns),'status'=>0))->field('device_sn,sum(fee) as fee')->group('device_sn')->lock(true)->select();
        //找到pos机的设置
        $device = M('device')->where('id=1')->find();
        foreach($xiaofei as $v){
            $suanli = 0;//增加的算力
            //先找到这个sn被绑定没 ，如果没绑定直接作废 ， 如果有绑定 先判断该设备的状态是激活还是未激活
            // 如果激活只给自己返算力 没激活则判断激活条件 达到后给上级的冻结原力币去掉进可用 同时给你返算力
			//因pos机导出的比实际多出最后一位 所以最后一位不验证
			$len = strlen($v['device_sn']);
			if($len == 16){
				$sn_tmp = substr($v['device_sn'],0,$len-1);
			}elseif($len == 10){
				$sn_tmp = $v['device_sn'];
			}
			
            $user_device = M('user_device')->where(array('sn'=>$sn_tmp))->find();
            if(!$user_device){
                M('device_xiaofei_log')->where(array('device_sn'=>$v['device_sn']))->setField('status',2);//无效状态
                continue;
            }

            //已激活
            if($user_device['status'] == 1){
                $suanli = $v['fee']/$device['charge_bl'];
				$suanli = intval($suanli);
                if($suanli>0){
					$mo = M();
                    $mo->startTrans();
                    $rs = array();
					$myself = M('user_coin')->where(array('userid'=>$user_device['userid']))->lock(true)->find();
					$rs[] = $mo->table('user_coin')->where(array('userid'=>$user_device['userid']))->setInc('lthz',$suanli);
					$rs[] = $mo->table('device_xiaofei_log')->where(array('device_sn'=>$v['device_sn']))->setField('status',1);
					$rs[] = $mo->table('myinvite')->add(array('userid'=>$user_device['userid'],'device_id'=>$user_device['id'],'type'=>2,'num'=>$suanli,'status'=>1,'createdate'=>time(),'channel'=>3));
					if(check_arr($rs)){
						$mo->commit();
					}else{
						$mo->rollback();
					}
				}
                
            }else{
                //没哟激活
                if($v['fee'] >= $device['charge']){ 
                    //给上级返原力币 解冻 给自己返算力
                    $suanli = ($v['fee'] - $device['charge'])/$device['charge_bl'];
					$suanli = intval($suanli);
                    $myself = M('user_coin')->where(array('userid'=>$user_device['userid']))->lock(true)->find();

                    $mo = M();
                    $mo->startTrans();
                    $rs = array();
					
					$rs[] = $mo->table('user_device')->where(array('id'=>$user_device['id']))->setField('status',1);                    
                    $rs[] = $mo->table('device_xiaofei_log')->where(array('device_sn'=>$v['device_sn']))->setField('status',1); 
					
                    //给自己返算力
                    if($suanli > 0){
                        $rs[] =$mo->table('user_coin')->where(array('userid'=>$myself['userid']))->setInc('lthz',$suanli);
                        $rs[] = $mo->table('myinvite')->add(array('userid'=>$user_device['userid'],'device_id'=>$user_device['id'],'type'=>2,'num'=>$suanli,'status'=>1,'createdate'=>time(),'channel'=>3));
                    }
					//消费记录验证通过后给自己激活 或给上级 上上级激活
                    $invites = M('myinvite')->where(array('from_id'=>$user_device['userid'],'status'=>0))->select(); //注册的记录
					foreach($invites as $invite){
						$rs[] = $mo->table('myinvite')->where(array('id'=>$invite['id']))->setField('status',1);
						$rs[] = $mo->table('user_coin')->where(array('userid'=>$invite['userid']))->setDec('lthd',$invite['num']);
						$rs[] = $mo->table('user_coin')->where(array('userid'=>$invite['userid']))->setInc('lth',$invite['num']);
					}
					
					//消费够了，给自己的设备激活 解冻原力币
					$myinvite = M('myinvite')->where(array('device_id'=>$user_device['id'],'type'=>1,'status'=>0))->lock(true)->find();
					
                    if($myinvite){
						$rs[] = $mo->table('myinvite')->where(array('id'=>$myinvite['id']))->setField('status',1);
						$rs[] = $mo->table('user_coin')->where(array('userid'=>$myinvite['userid']))->setDec('lthd',$myinvite['num']);
						$rs[] = $mo->table('user_coin')->where(array('userid'=>$myinvite['userid']))->setInc('lth',$myinvite['num']);
					}
					
                    if(check_arr($rs)){
                        $mo->commit();
                    }else{
                        $mo->rollback();
                    }
                }else{
                    continue;
                }
            }
        }
        echo ajax_return(1,'验证完成');exit;
    }

	public function ajax_verify_xiaofei_del()
    {
        $id = I('post.id');
		$info = M('device_xiaofei_log')->find($id);
		if(!$info || $info['status'] == 1){
			echo ajax_return(0,'请求有误');exit;
		}
        $res = M('device_xiaofei_log')->delete($id);
        if($res){
            echo ajax_return(1,'删除成功');
        }else{
            echo ajax_return(0,'删除失败');
        }
    }
	public function ajax_verify_xiaofei_piliang_del()
    {
        $ids = I('post.ids');
		foreach($ids as $id){
			$info = M('device_xiaofei_log')->find($id);
			if(!$info || $info['status'] == 1){
				continue;
			}
			$res = M('device_xiaofei_log')->delete($id);
		}
        if($res){
            echo ajax_return(1,'删除成功');
        }else{
            echo ajax_return(0,'删除失败');
        }
    }
    /**
     * 验证返利记录
     */
    public function xiaofei_verify_log()
    {
        $p = I('param.p',1);
        $list = 10;
		$type = I('post.type');
		$status = I('post.status');
		$channel = I('post.channel');
		if($type != ''){
			$map['a.type'] = $type;
		}
		if($status != ''){
			$map['a.status'] = $status;
		}
		if($channel != ''){
			$map['a.channel'] = $channel;
		}
        $res = M('myinvite')->alias('a')->join('left join user c on a.userid=c.id')->where($map)->field('a.*,c.phone')->page($p.','.$list)->order('a.id desc')->select();
		foreach($res as &$v){
			if($v['from_id']){
				$v['sn'] = M('user_device')->where(array('userid'=>$v['from_id']))->getField('sn');
			}
			if($v['device_id']){
				$v['sn'] = M('user_device')->where(array('id'=>$v['device_id']))->getField('sn');
			}
		}
        $count = M('myinvite')->alias('a')->where($map)->count();
        $page = new \Think\Page($count,$list);
		foreach($map as $key=>$val){
			$page->parameter[$key] = $val;
		}
        $show = $page->show();
        $this->assign('res',$res);
        $this->assign('count',$count);
        $this->assign('page',$show);
		
		$this->assign('type',$type);
		$this->assign('status',$status);
		$this->assign('channel',$channel);
        $this->display();
    }
	
	
	
	
	
	/**
	*申请管理
	*/
	public function device_shenqing()
    {	
		$type = I('post.type');
		$status = I('post.status');
		$p = I('param.p', 1);
		$list = 10;
		if($type != ''){
			$map['type'] = $type;
		}
		if($status != ''){
			$map['status'] = $status;
		}
		$res = M('user_shenqing')->where($map)->page($p . ',' . $list)->order('id desc')->select();
		$count = M('user_shenqing')->where($map)->count();
		$page = new \Think\Page($count, $list);
		//分页跳转的时候保证查询条件
		foreach($map as $key=>$val) {
			$page->parameter[$key]   = $val;
		}
		$show = $page->show();
		$this->assign('res', $res);
		$this->assign('page', $show);
		$this->assign('count', $count);
		
		$this->assign('type',$type);
		$this->assign('status',$status);
	   
		$this->display();
		
    }
	public function device_shenqing_edit(){
		$id = I('param.id');
		$info = M('user_shenqing')->where(array('id'=>$id))->find();
		$this->assign('info',$info);
		$this->display();
	}
	public function ajax_shenqing_edit(){
		$data = I('post.');
		$res = M('user_shenqing')->save($data);
		if($res){
			echo ajax_return(1,'操作成功');
		}else{
			echo ajax_return(0,'操作失败');
		}
	}
	public function ajax_shenqing_del(){
		$id = I('post.id');
		$info = M('user_shenqing')->where(array('id'=>$id))->find();
		if($info['status'] == 1){
			echo ajax_return(1,'已处理的单子不能删除');exit;
		}
		$res = M('user_shenqing')->delete($id);
		if($res){
			echo ajax_return(1,'删除成功');
		}else{
			echo ajax_return(0,'删除失败');
		}
	}
}