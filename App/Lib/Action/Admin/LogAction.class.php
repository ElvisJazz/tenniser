<?php

class LogAction extends AdminAction {
    
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
        
        if($_SESSION['tennis-user']['role'] != 0){
        	$this->error('您还没有进入这个房间的钥匙！');
        }
    }
    
    public function log_list(){
    	$pageId1;
        $pageId2;
        $num = 0;
        $pageId = $_GET['pageId'];
        
        $condition = "";
        $hasSql = 0;
        $sqlCondition = "";
        $module = $_GET['module'];
        $userId = $_GET['userId'];
        
        $log = M('operation_log'); 
        
        // 搜索条件
        if($userId!=null && $userId!="") {
            $sqlCondition .= "update_user=".$userId;
        	$condition = "&update_user=".$userId;
            $hasSql = 1;
        }
        if($module!=null && $module!=""){
            if($hasSql == 1)
                $sqlCondition .= " and module like '%".$module."%' ";
            else
                $sqlCondition .= "module like '%".$module."%' ";
            
            $hasSql = 1;
            $condition = $condition."&module=".$module;
        }
        
        // 查询数据库
        if($sqlCondition == ""){
            $list = $log->order('update_date desc')->page($_GET['pageId']+1, 30)->select();  
            $num = $log->count('id');
        }
        else{
            $list = $log->where($sqlCondition)->order('update_date desc')->page($_GET['pageId']+1, 30)->select();  
        	$num = $log->where($sqlCondition)->count('id');
        }
      
        
        // 显示用户名 
        $maxPageId = 0;
        
        
        if($list != null){
            
            foreach($list as $key=>$value){
                $User = M('user');
                $id = $value['update_user'];
                $list[$key]['ntrp'] =  number_format($value['ntrp'], 1);
                if($id!=null && $id!=0){
                    $username = $User->where('id='.$id)->getField('username');
                    
                    $list[$key]['update_user'] = $username.'('.$id.')';
                } else{
                    $list[$key]['update_user'] = '匿名';    
                }   
            }
              
            // 设置分页Id
            
            $offset = $num % 30;
            if($offset != 0){
                $maxPageId = ($num-$offset) / 30;
                $maxPageId++;
            } else
                $maxPageId = $num / 30;
            
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
        $this->assign('module', $module); 
        $this->assign('userId', $userId); 
        $this->display();    
    }
    
    public function delete_record() {
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        
        $log = M('operation_log');
        $result = $log->where('id='.$id)->delete();   
       
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '后台管理-日志';
            $data['operation'] = '删除记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Log/log_list?pageId='.$pageId);
        }
        else
            $this->error('删除失败！');
    }
}
?>