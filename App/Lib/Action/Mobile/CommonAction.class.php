<?php
class CommonAction extends Action {
    protected function _initialize() {
        header("Content-Type:text/html; charset=utf-8");
        $logon;
        $sign;
        $pvCounter = new SaeCounter();
        
        $User = M("User"); // 实例化User对象
        $pv;
        
        if($this->PVCounter())
        	$pv = $pvCounter->incr('PVCount');
        else
            $pv = $pvCounter->get('PVCount');
        
        if(session('tennis-user') == null){
                $condition['mailbox'] = $_COOKIE['tennis-user'];
                $user0 = $User->where($condition)->select();
            if($user != null){
				$user = current($user0);
                
                // 更新日志
                //=================
                $data['module'] = '用户登录(手机)';
                $data['operation'] = '登录';
                $data['update_user'] = $user['id'];
                $data['ip'] = $this->getIP();
                $log = M('operation_log');
                $log->add($data);            
                //=================
                // 上次登录日期比较，计算连续登录日期
                $date = date('Y-m-d H:i:s');
                $last_logon_date = $user['last_logon_date'];
                $info = array();
                $is_first_visit = false;
                    
                if(date('d') != date('d', strtotime($last_logon_date)) || date('m')!=date('m', strtotime($last_logon_date)) || date('Y')!=date('Y', strtotime($last_logon_date)))
                {
                    $user['logon_days']++;
                    $is_first_visit = true;
                    $info['is_sign'] = 0;
                    $user['is_sign'] = 0;
                }
                
                $info['last_logon_date'] = $date;
                $info['logon_days'] = $user['logon_days']; 
                
                // 是否解禁
                if($user['is_forbidden'] == 1){
                    $d1 = strtotime($date);   
                    $d2 = strtotime($user['end_forbid_date']); 
                    if($d1 >= $d2){
                        $info['is_forbidden'] = 0;
                        $user['is_forbidden'] = 0;
                    }
                }
                
                $User->where('id='.$user['id'])->setField($info);            
                
                $_SESSION['tennis-user'] = $user;
                
                if($is_first_visit)                
                    $this->updateScoreAndExp(1);
                
            }
        }
            
        if(session('tennis-user') != null){
            $user = session('tennis-user');
            $logon = ' <li class="dropdown">
                        <a href="#" class="dropdown-toggle" style="text-align:center"  data-toggle="dropdown">'.$user['username'].'<b class="caret"></b></a>
                        <ul class="dropdown-menu">
                        
                          <li><a class="btn" style="color:white;" href="__APP__/Mobile/User/userInfo"> 用户中心</a> </li>
                          <li><a class="btn" style="color:white;" href="__APP__/Mobile/Public/logout">退出</a></li>';
                
                if($user['is_sign'] == 0)
                    $sign = "<li><a style='color:white;' id='signStr' class='btn' onclick='sign0();' >签到</a></li>";
                else
                    $sign = "<li><a style='color:white;'id='signStr' class='btn' onclick='sign0();' disabled >已签到</a></li>";
                
                if($user['role'] == 0)
                    $logon .= '<li class="divider"></li> <li><a class="btn" href="__APP__/Admin/Index/index">后台管理</a></li></ul></li>';
            	else if($user['role'] == 1 && !(strpos($user['manage_club_id'], ';'.$club_id.';')===false))
                    $logon .= '<li class="divider"></li> <li><a class="btn" href="__APP__/Admin/Index/index">后台管理</a></li></ul></li>';
                else
                    $logon .= ' </ul></li>';
                
                
        }
        else{
            $logon = '<li><a class="btn" data-toggle="modal" data-target="#myModal">登录</a></li>';
        }
        // 获取俱乐部id变量
        $club_id = $this->getClubId();
        
        $this->assign('pagePostfix', '_'.$club_id);
        $this->assign('logon',$logon);  
        $this->assign('sign',$sign);
        $this->assign('PV',$pv);
        $this->assign('clubName', $this->getClubName());
        
    }
    
   // 获取俱乐部id变量
    function getClubId(){
        $club_id = $_COOKIE['tennis-club'];
        if($club_id==null || $club_id=='')
            $club_id = 1;
        
        return $club_id;
    }
    
    // 获取俱乐部名称变量
    function getClubName(){
        $club_id = $_COOKIE['tennis-club'];
        if($club_id==null || $club_id=='')
            $club_id = 1;
        
        $CLUB = M('club');
        $club_name = $CLUB->where('id='.$club_id)->getField('name');
        return $club_name;
    }
    
    function updateScoreAndExp($type){
        $id = $_SESSION['tennis-user']['id'];
        $user = M('User');
        $list = $user->where("id=".$id)->getField('id,score,experience,value_exp');
        
        if($list != null){
            $currentScore = $list[$id]['score'];
            $currentExp = $list[$id]['experience'];
            $currentValueExp = $list[$id]['value_exp']; 
            
            $score = 10;
            $exp  = 10;
            switch($type){
                case 0: $score = 2000; $exp=100; break; // 注册
                case 1: $score = 50; $exp=15; break; // 登陆
                case 2: $score = 20; $exp=15; break; // 签到
                case 3: $score = -500; $exp=50; break; // 长期约球发帖
                case 4: $score = -100; $exp=25; break; // 临时约球发帖
                case 5: $score = 40; $exp=20; break; // 资源评论
                case 6: $score = 20; $exp=20; break; // 约球评论
                default: break;
            }
            
            
            $currentScore += $score;
            $currentExp += $exp;
            $currentValueExp += $exp;
            
            if($currentScore < 0)
                return false;
            
            $data['score'] = $currentScore;
            $data['experience'] = $currentExp;
            $data['value_exp'] = $currentValueExp;
            
            $index = ($currentExp-$currentExp%500) / 500;
            
            $level = "神秘人";
            switch($index){
                case 0: $level = "网球新人"; break;
                case 1: $level = "巧遇名师"; break;
                case 2: $level = "勤学苦练"; break;
                case 3: $level = "致命正手"; break;
                case 4: $level = "无敌反手"; break;
                case 5: $level = "底线称王"; break;
                case 6: $level = "截杀网前"; break;
                case 7: $level = "一鸣惊人"; break;
                case 8: $level = "初涉网坛"; break;
                case 9: $level = "力克强敌"; break;
                case 10: $level = "千锤百炼之极限"; break;
                case 11: $level = "才气焕发之极限"; break;
                case 12: $level = "天衣无缝之极限"; break;
                case 13: $level = "无我境界"; break;
                case 14: $level = "网坛巅峰"; break;
                case 15: $level = "网坛传奇"; break;
                default: $level = "神秘人"; break;
                
            }
            $data['level'] = $level;
            
            $result = $user->where("id=".$id)->save($data);
            
            if($result){
                $_SESSION['tennis-user']['score'] = $currentScore; 
                $_SESSION['tennis-user']['experience'] = $currentExp;
                $_SESSION['tennis-user']['level'] = $level;
                $_SESSION['tennis-user']['value_exp'] = $currentValueExp;
            }
        }
        return true;
	}
    
    function PVCounter(){
        if(session('tennis-pv') == null){
            $pv['ip'] = $this->getIP();
            $pv['time'] = date('Y-m-d H:i:s');
            $_SESSION['tennis-pv'] = $pv;
            return true;
            
        }else{
            $ip = $this->getIP();
            $now = date('Y-m-d H:i:s');
            $time = $_SESSION['tennis-pv']['time'];
            
            $t1 = strtotime('Y-m-d H:i:s', $now);
            $t2 = strtotime('Y-m-d H:i:s', $time);
            if($t1 - $t2 > 1800) {
                $_SESSION['tennis-pv']['time'] = $t1;
                 return true;
            }
            else if($ip != $_SESSION['tennis-pv']['ip']){
                $_SESSION['tennis-pv']['ip'] = $ip;
                return true;
            }
            else 
                return false;
        }
        
        
    }
    
    
    function checkScore($type){
        $id = $_SESSION['tennis-user']['id'];
        $user = M('User');
        $list = $user->where("id=".$id)->getField('id,score,experience');
        
        if($list != null){
            $currentScore = $list[$id]['score'];
            
            switch($type){
                case 3: if($currentScore < 500) return false;  // 长期约球发帖
                case 4: if($currentScore < 100) return false; // 临时约球发帖
                default:break;
                
            }
        }
        
        return true;
        
    }
     
    function getIP(){
        //php获取ip的算法
        if ($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"]) 
        { 
        $ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"]; 
        } 
        elseif ($HTTP_SERVER_VARS["HTTP_CLIENT_IP"]) 
        { 
        $ip = $HTTP_SERVER_VARS["HTTP_CLIENT_IP"]; 
        }
        elseif ($HTTP_SERVER_VARS["REMOTE_ADDR"]) 
        { 
        $ip = $HTTP_SERVER_VARS["REMOTE_ADDR"]; 
        } 
        elseif (getenv("HTTP_X_FORWARDED_FOR")) 
        { 
        $ip = getenv("HTTP_X_FORWARDED_FOR"); 
        } 
        elseif (getenv("HTTP_CLIENT_IP")) 
        { 
        $ip = getenv("HTTP_CLIENT_IP"); 
        } 
        elseif (getenv("REMOTE_ADDR"))
        { 
        $ip = getenv("REMOTE_ADDR"); 
        } 
        else 
        { 
        $ip = "Unknown"; 
        } 
        
        $pos = strpos($ip, ',');
        if($pos === false)
        	return $ip;
        else{
        	$ip = substr($ip, 0, $pos);    
            return $ip;
        }
    }
    
    function getIpCity(){
        $ip = $this->getIP();
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if(empty($res))
        { 
            return false; 
        }
        
        $jsonMatches = array();
        preg_match('#\{.+?\}#', $res, $jsonMatches);
        
        if(!isset($jsonMatches[0])){
            return false; 
        }
        
        $json = json_decode($jsonMatches[0], true);
        if(isset($json['ret']) && $json['ret'] == 1){
            $json['ip'] = $ip;
            unset($json['ret']);
        }else{
            return false;
        }
        return $json['city'];
    }
          
}
?>