<?php
namespace Home\Controller;
use Think\Controller;

class QueueController extends Controller
{

    /**
     * 钱包检查
     */
    public function qianbao()
    {
        Vendor("Move.ext.client");
        $client = new \client('...','...', '..', 29416, 5, [], 1);
        $json = $client->getinfo();

        if (!isset($json['version']) || !$json['version']) {
            echo '###ERR#####*****  connect fail  ***** ####ERR####>' . "\n";exit;
        }
        $listtransactions = $client->listtransactions('*', 100, 0);
        echo 'listtransactions:' . count($listtransactions) . "\n";
        krsort($listtransactions);
        echo '<pre>';
        //var_dump($listtransactions);exit;
        foreach ($listtransactions as $trans) {

            if (M('Myzr')->where(array('txid' => $trans['txid'], 'status' => '1'))->find()) {
                echo 'txid had found continue' . "\n";
                continue;
            }

            echo 'all check ok ' . "\n";

            if ($trans['category'] == 'receive') {
                //如果是接收的 通过账户获取用户信息
               /* if (!($user = M('user')->where(array('phone' => $trans['account']))->find())) {
                    echo 'no account find continue' . "\n";
                    continue;
                }*/
                if (!($user = M('user_coin')->where(array('lthb' => $trans['address']))->find())) {
                    echo 'no account find continue' . "\n";
                    continue;
                }
               // print_r($trans);
                echo 'start receive do:' . "\n";
                $sfee = 0;
                $true_amount = $trans['amount'];

                //经过多少次确认
                if ($trans['confirmations'] < 5) {

                    echo 'confirmations <  c_zr_dz continue' . "\n";

                    if ($res = M('myzr')->where(array('txid' => $trans['txid']))->find()) {
                        M('myzr')->save(array('id' => $res['id'], 'createtime' => time(), 'status' => intval($trans['confirmations'] - 5)));
                    }else {
                        M('myzr')->add(array('userid' => $user['userid'], 'address' => $trans['address'] , 'txid' => $trans['txid'], 'num' => $true_amount, 'createdate' => time(), 'status' => intval($trans['confirmations'] - 5)));
                    }

                    continue;
                }
                else {
                    echo 'confirmations full' . "\n";
                }

                $mo = M();
                $mo->startTrans();
                $rs = array();
                $rs[] = $mo->table('user_coin')->where(array('userid' => $user['userid']))->setInc('lth', $trans['amount']);

                if ($res = $mo->table('myzr')->where(array('txid' => $trans['txid']))->find()) {
                    echo 'myzr find and set status 1';
                    $rs[] = $mo->table('myzr')->save(array('id' => $res['id'], 'createdate' => time(), 'status' => 1));
                }
                else {
                    echo 'myzr not find and add a new myzr' . "\n";
                    $rs[] = $mo->table('myzr')->add(array('userid' => $user['userid'], 'address' => $trans['address'], 'txid' => $trans['txid'], 'num' => $true_amount, 'createdate' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->commit();
                    echo $trans['address'] . ' receive ok '  . $trans['address'];

                    echo 'commit ok' . "\n";
                }else {
                    echo $trans['address'] . 'receive fail ' . $trans['address'];

                    $mo->rollback();
                   // print_r($rs);
                    echo 'rollback ok' . "\n";
                }
            }
            ///////转出
            if ($trans['category'] == 'send') {
                echo 'start send do:' . "\n";

                if (3 <= $trans['confirmations']) {
                    $myzc = M('Myzc')->where(array('address' => $trans['address']))->find();

                    if ($myzc) {
                        if ($myzc['status'] == 0) {
                            M('Myzc')->where(array('id' => $myzc['id']))->save(array('status' => 1,'txid'=>$trans['txid']));
                            echo $trans['amount'] . '成功转出币确定';
                        }
                    }
                }
            }
        }
    }
	
	/**
     * 每天算力挖矿
     * 算力/难度=每日挖出来的可用原力币数量
       算力=消费手续费+各种设备增加的算力
       难度=预设数量*预设天数*人数
     * 每3小时执行 数量为=》 算力/难度/8
     */
	public function miner123_no_use()
    {
        $config = M('config')->where('id=1')->find();
		$count = M('user')->count(); //总人数
        $nandu = $config['total'] * $config['days'] * $count * $config['xishu'];
        //当前算力大于0的会员
        $user_coin = M('user_coin')->where(array('lthz'=>array('gt',0)))->select();
        $mo = M();
        foreach($user_coin as $coin){
            $num = $coin['lthz']/$nandu;
            //$num = round($num/8,8);
			//保留四位小数 不四舍五入
			$num = $num/8;
			$num = substr(sprintf("%.5f",$num),0,-1);
			
            if($num < 0.0001){
                continue;
            }
            $mo->startTrans();
            $rs = array();
           // $rs[] = $mo->table('user_coin')->where(array('userid'=>$coin['userid']))->setInc('lth',$num);
            $rs[] = $mo->table('sys_fl_log')->add(array('userid'=>$coin['userid'],'nandu'=>$nandu,'suanli'=>$coin['lthz'],'num'=>$num,'createdate'=>time())) ;
            if(check_arr($rs)){
                $mo->commit();
            }else{
                $mo->rollback();
            }
        }
        echo 'successful';
    }
	
	/*
	*判断是否在线同时一个ip上最近半个小时登录的最多有50个设备，如果超过50个设备，要按最先绑定的这50个sn有效，多余的视为无效
	思路是：找到该用户的所有路由器设备（1或多个），（暂不考虑最近半小时内一个sn在两个ip上登录，一般不会出现）
	通过这些sn找ip（1或多个），判断每个ip是否超过了50，如果超过了，排除掉
	
	*/
	public function online($devices)
	{
		$offline = 0; //不在线数
		if($devices){
			$remote = M('console_log','tfc_','mysql://console_tfc_kim:FiAdXkHEFxByNMcD@47.91.242.68/console_tfc_kim#utf8');
			//最近半个小时时间戳
			$end = time();
			$start = $end - 30*60;	
			foreach($devices as $k=>$d){				
				$is_on = $remote->where(array('sn'=>$d['sn'],'addtime'=>array('between',array($start,$end))))->find();
				if($is_on){
					//在线 判断当前ip的sn是否大于50，大于则要看当前sn所以在devices的索引是否大于49 大于则排除 小于50则通过验证
					$sns = $remote->where(array('ip'=>$is_on['ip'],'addtime'=>array('between',array($start,$end))))->select();
					if(count($sns) > 50 && $k > 49){
						$offline += 1;
					}
				}else{
					//不在线数
					$offline += 1;
				}			
			}
		}
		return $offline;
	}
	/*
	*公式 发行数/总算力 * 个人的算力 / 8  分8次分完,如果是派送币大于0的话就不用除以8了
	*/
	public function miner()
    {
        $config = M('config')->where('id=1')->find();
		$device = M('device')->find(2);//路由器的设置
		$total = 0;
		if($config['total']>0){
			$total = $config['total']/8;
		}else{
			$total = $config['total1'];
		}
		//总算力
		$suanli = M('user_coin')->sum('lthz');
		
        //当前算力大于0的会员
        $user_coin = M('user_coin')->where(array('lthz'=>array('gt',0)))->select();
        $mo = M();
        foreach($user_coin as $coin){
			//找到该会员的所有路由器
			$devices = M('user_device')->where(array('userid'=>$coin['userid'],'device_id'=>2))->order('id asc')->select();
			$offline = $this->online($devices);
			$gr_suanli = $coin['lthz'] - $offline*$device['suanli'];
            $num = $total /$suanli * $gr_suanli;
            
			//保留四位小数 不四舍五入
			//$num = $num/8; //放到上面判断里面了
			$num = substr(sprintf("%.5f",$num),0,-1);
			
            if($num < 0.0001){
                continue;
            }
            $mo->startTrans();
            $rs = array();
           // $rs[] = $mo->table('user_coin')->where(array('userid'=>$coin['userid']))->setInc('lth',$num);
            $rs[] = $mo->table('sys_fl_log')->add(array('userid'=>$coin['userid'],'nandu'=>'','suanli'=>$gr_suanli,'num'=>$num,'createdate'=>time())) ;
            if(check_arr($rs)){
                $mo->commit();
            }else{
                $mo->rollback();
            }
        }
		M('config')->where('id=1')->setField('total1',0);
        echo 'successful';
    }
	public function miner_bak()
    {
        $config = M('config')->where('id=1')->find();
		
		$total = 0;
		if($config['total']>0){
			$total = $config['total']/8;
		}else{
			$total = $config['total1'];
		}
		//总算力
		$suanli = M('user_coin')->sum('lthz');
		
        //当前算力大于0的会员
        $user_coin = M('user_coin')->where(array('lthz'=>array('gt',0)))->select();
        $mo = M();
        foreach($user_coin as $coin){
            $num = $total /$suanli * $coin['lthz'];
            
			//保留四位小数 不四舍五入
			//$num = $num/8; //放到上面判断里面了
			$num = substr(sprintf("%.5f",$num),0,-1);
			
            if($num < 0.0001){
                continue;
            }
            $mo->startTrans();
            $rs = array();
           // $rs[] = $mo->table('user_coin')->where(array('userid'=>$coin['userid']))->setInc('lth',$num);
            $rs[] = $mo->table('sys_fl_log')->add(array('userid'=>$coin['userid'],'nandu'=>'','suanli'=>$coin['lthz'],'num'=>$num,'createdate'=>time())) ;
            if(check_arr($rs)){
                $mo->commit();
            }else{
                $mo->rollback();
            }
        }
		M('config')->where('id=1')->setField('total1',0);
        echo 'successful';
    }
	/**
	每小时派送 规则是 总人数/2 * 6.25
	*/
	public function exce_hour(){
		$config = M('config')->where('id=1')->find();
		$count = M('user')->count(); //总人数
		
		$count = intval($count/$config['block_renshu']);
		$num =  $count* $config['block_num'] ;
		
		M('config')->where(array('id'=>1))->setInc('total1',$num);
		echo 'successful';
	}
	public function exce_hour_clear(){
		M('config')->where('id=1')->setField('total1',0);
		echo 'successful';
	}
	
}