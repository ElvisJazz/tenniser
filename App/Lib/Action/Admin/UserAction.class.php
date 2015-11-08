<?php

class UserAction extends AdminAction {
    
    protected function _initialize() {
        if(session('tennis-user') == null){
                $User = M("User"); // 实例化User对象
                $condition['mailbox'] = $_COOKIE['tennis-user']['mailbox'];
                $user = $User->where($condition)->select();
                if(array_count_values($user) > 0)
                    $_SESSION['tennis-user'] = $user[0];
                
        }
        
        $user;
        if(session('tennis-user') != null){
            $user = session('tennis-user');
            if($user['role'] == 2) {
                $this->error("您暂时还无法进入这个世界！"); 
            }
            
            $logon = ' <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">'.$user['username'].'<b class="caret"></b></a>
                        <ul class="dropdown-menu">
                        
                          <li><a class="btn" href="__APP__/User/userInfo"> 用户中心</a> </li>
                          <li><a class="btn" href="__APP__/Public/logout">退出</a></li>';
            
            if($user['role'] != 2)
                $logon .= '<li class="divider"></li> <li><a class="btn" href="/">用户界面</a></li></ul></li>';
            else
                $logon .= ' </ul></li>';
        }
        else{
            $this->error("想入此门，清先登录！");
        }
        $this->assign('logon',$logon);   
        $this->assign('role',$user['role']);
        
        if($_SESSION['tennis-user']['role'] >= 2){
        	$this->error('您还没有进入这个房间的钥匙！');
        }
    }
    
    public function user_list() {
        $pageId1;
        $pageId2;
        $num = 0;
        $pageId = $_GET['pageId'];
        
        $userId = $_GET['userId'];
        $username = $_GET['username'];
        $sqlCondition = "";
        $condition = "";
        $hasSql = 0;
        
        $admin = $_SESSION['tennis-user'];
        $user = M('User'); 
        
        if($userId!=null && $userId!="") {
            $sqlCondition .= "id=".$userId;
        	$condition = "&id=".$userId;
            $hasSql = 1;
        }
        if($username!=null && $username!=""){
            if($hasSql == 1)
                $sqlCondition .= " and username like '%".$username."%' ";
            else
                $sqlCondition .= "username like '%".$username."%' ";
            
            $hasSql = 1;
            $condition = $condition."&username=".$username;
        }
        
           
        
        if($sqlCondition == ""){
            $list = $user->order('id desc')->page($_GET['pageId']+1, 25)->select();  
            $num = $user->count('id');
        }
        else{
            $list = $user->where($sqlCondition)->order('id desc')->page($_GET['pageId']+1, 25)->select();  
        	$num = $user->where($sqlCondition)->count('id');
        }
        
        $maxPageId = 0;
        if($list != null){
                        
            // 设置分页Id
            
            $offset = $num % 25;
            if($offset != 0){
                $maxPageId = ($num-$offset) / 25;
                $maxPageId++;
            } else
                $maxPageId = $num / 25;
            
            foreach($list as $key=>$value){
                switch($value[role]){
                    case 0: $list[$key][role] = "管理员";break;    
                    case 1: $list[$key][role] = "高级用户";break;
                    case 2: $list[$key][role] = "普通用户";break;
                }
                $list[$key][ntrp] = number_format($value['ntrp'], 1);
                    
            }
            
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
        $this->assign('userId', $userId); 
        $this->assign('numOfUser', $num);
        
        $this->assign('adminRole', $admin['role']);
        $this->display();
    }
    
    public function clearValueExp(){
        
        $user = M('User');
        $list = $user->select();
        if($list != null){
            foreach($list as $key=>$value){
                    $user->where('id='.$value['id'])->setField('value_exp', 100);
            }                        
        }
        
        $this->success("操作成功！", "__APP__/Admin/User/user_list?pageId=0");
    }
    
    public function user_detail() {  
        $admin = $_SESSION['tennis-user'];
        if($admim['role'] > 0)
            $this->error("您暂时没有查看用户信息的权限哦！");
        
        $id = $_GET['id'];
        $item;
        $user = M('User'); 
        
        if($_GET['add'] == 0) {
            $user = M('User');
            $item = $user->find($id); 
            $item['ntrp'] = number_format($item['ntrp'], 1);
        } 
        
        $this->assign('add', $_GET['add']);
        $this->assign('item', $item); 
        $this->display();
        
        
    }
    
    public function delete_record() {
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        
        $user = M('User');
        $result = $user->where('id='.$id)->delete();   
       
        
        if($result === FALSE)
            $this->error('删除失败！');
        else{
             // 更新日志
            //=================
            $data['module'] = '后台管理-用户';
            $data['operation'] = '删除记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            $this->success('删除成功！', '__APP__/Admin/User/user_list?pageId='.$pageId);
        }
    }
    
    public function user_submit() {
        $id = $_POST['id'];
        $result;
        
        if($_POST['add'] == 0){
            
            $user = M('user');
            $user->create();
            $result = $user->save();
            
        }else{
            
            $user = M('user');
            $user->create();
            $result = $user->add();
            $id = $result;
        }
            
        
        if($result === FALSE)
            $this->error('提交失败！');
        else
            $this->success('提交成功！', '__APP__/Admin/User/user_list?pageId=0');
    }
    
    public function lock_user() {
        
        if($_GET['id']==null || $_GET['id']=='')
            $this->error('操作失败，id无效！');
        
        $days = $_GET['days'];
        if($_GET['days']==null || $_GET['days']=='')
            $days = 3;
        
        $User = M("User"); // 实例化User对象
        $date = date("Y-m-d H:i:s");
        $data = array('is_forbidden'=>1, 'start_forbid_date'=>$date,'end_forbid_date'=>date('Y-m-d H:i:s', strtotime($date.'+'.$days.' day')));
        // 更改用户的name值
        $result = $User-> where('id='.$_GET['id'])->setField($data);
        
        if($result){
             // 更新日志
            //=================
            $data['module'] = '后台管理-用户';
            $data['operation'] = '封禁用户：id='.$_GET['id'];
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            $this->success('操作成功！', '__APP__/Admin/User/user_list?pageId='.$GET['pageId']);
        }
        else
            $this->error('操作失败！');
    }
    
    public function release_user() {
        
        if($_GET['id']==null || $_GET['id']=='')
            $this->error('操作失败，id无效！');
        
       
        $User = M("User"); // 实例化User对象
        $data = array('is_forbidden'=>0, 'start_forbid_date'=>0,'end_forbid_date'=>0);
        // 更改用户的name值
        $result = $User-> where('id='.$_GET['id'])->setField($data);
        
        if($result){
             // 更新日志
            //=================
            $data['module'] = '后台管理-教学';
            $data['operation'] = '解封用户：id='.$_GET['id'];
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            $this->success('操作成功！', '__APP__/Admin/User/user_list?pageId='.$GET['pageId']);
        }
        else
            $this->error('操作失败，请稍后重试！');
    }
}

?>