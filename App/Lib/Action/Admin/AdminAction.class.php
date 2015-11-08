<?php
class AdminAction extends Action {
    protected function _initialize() {
        header("Content-Type:text/html; charset=utf-8");
        
        if(session('tennis-user') == null){
                $User = M("User"); // 实例化User对象
                $condition['mailbox'] = $_COOKIE['tennis-user']['mailbox'];
                $user = $User->where($condition)->select();
                if(array_count_values($user) > 0)
                    $_SESSION['tennis-user'] = $user[0];
                
        }
        
        $user = null;
        // 获得club_id
        $club_id = $this->getClubId();
        cookie("tennis-club", $club_id, time()+3600000);
        
        if(session('tennis-user') != null){
            $user = session('tennis-user');
            if($user['role']==2) {
                $this->error("您暂时还无法进入这个世界！");                
            }
            
            if($user['is_forbidden'] == 1) {
                $this->error("很遗憾，您被封印了！这个世界还是由我接管吧！"); 
            }
            
            $logon = ' <li class="dropdown">
                        <a href="#" class="dropdown-toggle"  style="text-align:center" data-toggle="dropdown">'.$user['username'].'<b class="caret"></b></a>
                        <ul class="dropdown-menu">
                        
                          <li><a class="btn" href="__APP__/User/userInfo"> 用户中心</a> </li>
                          <li><a class="btn" href="__APP__/Public/logout">退出</a></li>';
            
            if($user['role'] != 2)
                $logon .= '<li class="divider"></li> <li><a class="btn" href="/">用户界面</a></li></ul></li>';
            else
                $logon .= ' </ul></li>';
        }
        else{
            $this->error("想入此门，请先登录！",'__APP__/Index/index_'.$club_id);
        }
        
        
        $this->assign('clubName', $this->getClubName());
        $this->assign('pagePostfix', '_'.$club_id);
        $this->assign('logon',$logon);   
        $this->assign('role',$user['role']);
        $this->assign('userId',$user['id']);
        
    }
    
     // 获取俱乐部id变量
    function getClubId(){
        $user = session('tennis-user');
        
        return $user['manage_club_id'];
    }
    
    // 获取俱乐部名称变量
    function getClubName(){
        $club_id = $this->getClubId();
        
        $CLUB = M('club');
        $club_name = $CLUB->where('id='.$club_id)->getField('name');
        return $club_name;
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
        
        return $ip;
    }
    
     
    public function cloudPush($msg){

        $appid = 22492;
        $token = 'BYfkQcUYULUB';
        $title = 'title';
        $msg = 'hello wolrd';
        $acts = "[\"2,sina.Apns,sina.Apns.MainActivity\"]";
        $extra = array(
            'handle_by_app'=>'0'
        );
        
        $adpns = new SaeADPNS();
        //appid 是应用的标识，从SAE的推送服务页面申请
        //token 是SDK通道标识，从SDK的onPush中获取
        $result = $adpns->push($appid, $token, $title, $msg, $acts, $extra);
        if ($result && is_array($result)) {            
            var_dump($result);
            return true;
        } else {            
            var_dump($apns->errno(), $apns->errmsg());
            return false;
        }

    }
          
}
?>