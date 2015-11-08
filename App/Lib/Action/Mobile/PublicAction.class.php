<?php

class PublicAction extends CommonAction{
    Public function verify(){
        import('ORG.Util.Image');
        Image::buildImageVerify();
    }
    
    public function registerSubmit(){
       $User = M("User"); // 实例化User对象
        $condition['mailbox'] = $_POST['mailbox'];
        $list = $User->where($condition)->select();
        if($list[0]['mailbox'] == $_POST['mailbox'])
            $this->error('该邮箱已被注册！');
        
        if($this->isPost()){
            if(session('verify') != md5($_POST['verify'])) {
                $code1 = session('verify');
                $code = md5($_POST['verify']);
                $this->error('验证码错误！');
			}
            else{
                $user = M("User"); // 实例化User对象  
                $data[username] = $_POST['username'];
                $data[password] = $_POST['password'];
                $data[sex] = $_POST['sex'];
                $data[mailbox] = $_POST['mailbox'];
                
                
                $result =   $user->add($data);
                if($result) {
                    // 更新日志
                    //=================
                    $data['module'] = '用户注册(手机)';
                    $data['operation'] = '注册：id='.$result;
                    $data['update_user'] = $result;
                    $data['ip'] = $this->getIP();
                    $log = M('operation_log');
                    $log->add($data);            
                    //=================
                    
                    unset($_SESSION['tennis-user']);   
                    //$this->updateScoreAndExp(0);
                    $this->success('注册成功，请重新登录！', '__APP__/Mobile/Index/index?displayInstruction=1');                    
                }else {
                    $this->error('注册失败！');
                }
            }
            
        }
    }
    
    public function logOnSubmit() {
        $User = M("User"); // 实例化User对象
        $condition['mailbox'] = $_POST['mailbox'];
        $user0 = $User->where($condition)->select();
        $user = current($user0);
        if($user!= null && $user[password] == $_POST['password'])
        {
              $ntrp = $user[ntrp];
			  $user[ntrp] = number_format($ntrp, 1);
        
            if($_POST['remember'] == 1) // 是否记住，对cookie进行操作
            {   	
                cookie("tennis-user", $user['mailbox'], time()+3600000);
            }
            else{
                cookie("tennis-user", null);
            }
            
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
                
            if(date('d') != date('d', strtotime("Y-m-d H:i:s", $last_logon_date)) || date('m')!=date('m', strtotime("Y-m-d H:i:s", $last_logon_date)) || date('Y')!=date('Y', strtotime("Y-m-d H:i:s", $last_logon_date)))
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
                $d1 = strtotime("Y-m-d H:i:s", $date);   
                $d2 = strtotime("Y-m-d H:i:s", $user['end_forbid_date']); 
                if($d1 >= $d2){
            		$info['is_forbidden'] = 0;
                    $user['is_forbidden'] = 0;
                }
            }
            
            $User->where('id='.$user['id'])->setField($info);            
            
            $_SESSION['tennis-user'] = $user;
            
            if($is_first_visit)                
                $this->updateScoreAndExp(1);
            
            $this->success('登录成功！', '__APP__/Mobile/Index/index_'.$this->getClubId()); 
        } else {
            $this->error('用户名或密码不正确，登录失败！',  '__APP__/Mobile/Index/index_'.$this->getClubId());
        }
        
    }
    
    public function userInfoSubmit() {
        $User = M("User"); // 实例化User对象
        $User->create();
        $result = $User->save(); // 根据条件保存修改的数据
        if($result === FALSE) 
            $this->error('修改失败！');
        else{
            // 更新日志
            //=================
            $data['module'] = '用户中心(手机)';
            $data['operation'] = '信息修改';
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $_SESSION['tennis-user'] = null;
            cookie("tennis-user", null);
            $this->success('修改成功，请重新登录！', '__APP__/Mobile/Index/index');              
        }
    }
    
    public function logout() {
        // 更新日志
        //=================
        $data['module'] = '用户注销(手机)';
        $data['operation'] = '注销';
        $data['update_user'] = $_SESSION['tennis-user']['id'];
        $data['ip'] = $this->getIP();
        $log = M('operation_log');
        $log->add($data);            
        //=================
        
        unset($_SESSION['tennis-user']);
        cookie("tennis-user", null);
        
        $this->success('注销成功！', '__APP__/Mobile/Index/index_'.$this->getClubId());
    }
    
    public function getBackPasswordSubmit() {
        $mailbox = $_POST['mailbox'];
        $condition['mailbox'] = $mailbox;
        $User = M('User');
        $result = $User->where($condition)->getField("mailbox, password");
        
        if($result){
            $mail = new SaeMail();
            //$mail->setAttach( array("my_photo.jpg" => "照片的二进制数据" ));
            $ret = $mail->quickSend( 
                $mailbox ,
                "网动青春密码找回" ,
                "尊敬的用户：
                您好！这是您网动青春官方网站的账户密码(".$result[$mailbox].")，请妥善保管，如有必要可在用户中心进行修改。感谢您的支持与关注！
                
                			爱网球，爱青春，你我同行。——网动青春" ,
                "tenniser2014@gmail.com" ,
                "jjaazz901222" 
            );
        
            $mail->clean();	
            
            if ($ret === false)
        		var_dump($mail->errno(), $mail->errmsg());
            else{
               
                $this->success('发送成功，请注意查收！', '__APP__/Mobile/Index/index');
            }
        }else{
            $this->error('该邮箱未注册！');
        }
    }
    
    public function getBackPassword() {
        $this->display('Register:getBackPassword');    
    }
    
    public function adviceSubmit() {
        $advice = D("Advice");
        $data['title'] = $_POST['title'];
        $data['content'] = $_POST['content'];
        $data['userId'] = $_SESSION['tennis-user'][id];
        
        $time=time(); 
        $now = date('Y-m-d H:i:s',$time);
        $data['updateDate'] = $now;
        
        $result= $advice->add($data);
        
        if($result > 0){
             // 更新日志
            //=================
            $data['module'] = '反馈(手机)';
            $data['operation'] = '信息反馈';
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('提交成功！'); 
        }
        else
            $this->error('提交失败！');
    }
    
    public function registerAttendance(){
        $user = $_SESSION['tennis-user'];
        if($user['is_sign'] == 0){
            $User = M('user');
            $result = $User->where('id='.$user['id'])->setField('is_sign', 1);
            
            if($result === false){
            	$this->error('管理员跑去打球了，系统出问题了！');    
            }else{
                $this->updateScoreAndExp(2);
            	$_SESSION['tennis-user']['is_sign'] = 1; 
                $this->ajaxReturn(1, "签到成功！",1);
            }
        }
        $this->ajaxReturn(1, "签到成功！",1);
            
    }
  
    public function score_exp(){
       $pageId1;
        $pageId2;
        $num = 0;
        $pageId = $_GET['pageId'];
        
        $username = $_GET['username'];
        $sqlCondition = "";
        $condition = "";
        $hasSql = 0;
        
        $user = M('User'); 
        
       
        if($username!=null && $username!=""){
            if($hasSql == 1)
                $sqlCondition .= " username like '%".$username."%' ";
            else
                $sqlCondition .= "username like '%".$username."%' ";
            
            $hasSql = 1;
            $condition = $condition."username=".$username;
        }
        
           
        
        if($sqlCondition == ""){
            $list = $user->order('value_exp desc')->page($_GET['pageId']+1, 25)->select();  
            $num = $user->count('id');
        }
        else{
            $list = $user->where($sqlCondition)->order('value_exp desc')->page($_GET['pageId']+1, 25)->select();  
        	$num = $user->where($sqlCondition)->count('id');
        }
        
        
        $maxPageId = 0;
        if($list != null){
            $index = 1;        
            foreach($list as $key=>$value){
            	$list[$key]['index'] = $_GET['pageId']*25+$index;   
                $index++;                
            }
            // 设置分页Id
            
            $offset = $num % 25;
            if($offset != 0){
                $maxPageId = ($num-$offset) / 25;
                $maxPageId++;
            } else
                $maxPageId = $num / 25;
            
           
            if($_GET['pageId'] == 0)
                $pageId1 = 0;
            else 
                $pageId1 = $_GET['pageId'] - 1;
                
            if($_GET['pageId'] < $maxPageId-1)
                $pageId2 = $_GET['pageId'] + 1;
            else
                  $pageId2 = $maxPageId-1;
        }else {
             $pageId=-1;
            $pageId1=-1;
            $pageId2=-1;
        }
       
        $this->assign("page", ($pageId+1).'/'.$maxPageId);               
        $this->assign("pageId", $pageId);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        $this->assign("list", $list);   
        
        $this->assign('condition', $condition); 
        $this->assign('username', $username); 
        $this->assign('numOfUser', $num);
        $this->display('Common:score_exp'); 
        
    }
    
    // 手机客户端获取最新公告
    public function getInform(){
        
        $announcement = M('announcement');
        $now = date("Y-m-d H:i:s");
        $list = $announcement->where("time>'".$now."'")->select();
        
        $content = "";
        $index = 1;
        foreach($list as $key=>$value){
            $content .= '【公告'.$index.'】 |  时间：'.$value['time'].' |  内容：'.$value['content'].'||';
            $index++;
        }
        
        echo $content;
        //echo $announcement->getLastSql();
        $this->assign("Recruit:sign");
        
    }
}

?>