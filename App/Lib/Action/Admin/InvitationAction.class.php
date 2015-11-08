<?php

class InvitationAction extends AdminAction {
    
  
    
    public function invite_list() {
    	$club_id = $this->getClubId();
    	$pageId1;
        $pageId2;
        $condition = "";
        $sqlCondition = "";
        $place = $_GET['place'];
        $ntrp = $_GET['ntrp'];
        $userId = $_GET['userId'];
        $pageId = $_GET['pageId'];
        $list = array();
        $num = 0;
        $s = new SaeStorage(); 
        $topList = array();  
        $hasSql = 0;
        
        if($place!=null && $place!="") {
            $sqlCondition .= "place like '%".$place."%' ";
        	$condition = "&place=".$place;
            $hasSql = 1;
        }
        if($ntrp!=null && $ntrp!=""){
            if($hasSql == 1)
                $sqlCondition .= "and ntrp=".$ntrp;
            else
                $sqlCondition .= "ntrp=".$ntrp;
            
            $hasSql = 1;
            $condition = $condition."&ntrp=".$ntrp;
        }
        if($userId!=null && $userId!="") {
            if($hasSql == 1)
                $sqlCondition .= "and update_user=".$userId." ";
            else
                $sqlCondition .= " update_user=".$userId." ";
            $hasSql == 1;
        	$condition = $condition."&userId=".$userId;
        }
       
        

        if($_GET['type'] == 1){
            $tem_invitation = M('tem_invitation');
            if($sqlCondition == ""){
            	$list = $tem_invitation->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, time, place, update_user,update_date, ntrp, type');  
            	$num = $tem_invitation->where('club_id='.$club_id)->count('id');
            }else{
                $list = $tem_invitation->where($sqlCondition.' and club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, time, place, update_user, update_date, ntrp, type');  
            	$num = $tem_invitation->where($sqlCondition.' and club_id='.$club_id)->count('id');
            }
            
        } else{
            $friend_invitation = M('friend_invitation');
            if($sqlCondition == ""){
            	$list = $friend_invitation->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, place, update_user, update_date, ntrp');  
            	$num = $friend_invitation->where('club_id='.$club_id)->count('id');
            }else{
                $list = $friend_invitation->where($sqlCondition.' and club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, place, update_user, update_date, ntrp'); 
            	$num = $friend_invitation->where($sqlCondition.' and club_id='.$club_id)->count('id');
            }
         
        }
        
        
        $maxPageId = 0;
        // 显示更新人和评论数目
        $User = M('User');   
        $comment = M('invitation_comment');
        if($list != null){
            foreach($list as $key=>$value){
                $id = $value['update_user'];
                $list[$key]['ntrp'] =  number_format($value['ntrp'], 1);
                if($id!=null && $id!=0){
                    $username = $User->where('id='.$id)->getField('username');
                    
                    $list[$key]['user'] = $username;
                } else{
                    $list[$key]['user'] = '匿名';    
                } 
                // 获取评论数目
        		$list[$key]['numOfComment'] = $comment->where('type='.$_GET['type']." and invitation_id=".$value['id'])->count('id');
                               
            }
         
        	// 设置分页Id
            $offset = $num % 24;
            if($offset != 0){
                $maxPageId = ($num-$offset) / 24;
                $maxPageId++;
            } else
                $maxPageId = $num / 24;
            
            if($_GET['pageId'] == 0)
                $pageId1 = 0;
            else 
                $pageId1 = $_GET['pageId'] - 1;
                
            if($_GET['pageId'] < $maxPageId-1)
                $pageId2 = $_GET['pageId'] + 1;
            else
                $pageId2 = $maxPageId-1; 
        }else{
             $pageId=-1;
            $pageId1=-1;
            $pageId2=-1;
        }
         
        $this->assign("page", ($pageId+1).'/'.$maxPageId);               
        $this->assign("pageId", $pageId);
        $this->assign("list", $list); 
        $this->assign("topList", $topList); 
        $this->assign("type", $_GET['type']);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
         
        $this->assign('condition', $condition); 
        $this->assign('place', $place); 
        $this->assign('ntrp', $ntrp);
        $this->assign('userId', $userId); 
        $this->display();
    }
    
    public function invite_detail() {
        $type = $_GET['type'];
        $id = $_GET['id'];
        $item;
        $user = M('User'); 
        
        if($_GET['add'] == 0) {
            if($type == 1){
                $tem_invitation = M('tem_invitation');
                $item = $tem_invitation->find($id);           
                
            }else{
                $friend_invitation = M('friend_invitation');
                $item = $friend_invitation->find($id);
            }
               
            $item[update_user0] = $user->where('id='.$item[update_user])->getfield(username);
        } else {
            $item[update_user] = $_SESSION['tennis-user']['id'];
        }
        $item[ntrp] = number_format($item['ntrp'], 1);
        
        $now1 = date('Y-m-d');
        $now2 = date('H:i:s');
        $this->assign('now', $now1.'T'.$now1.'Z');
        $this->assign('add', $_GET['add']);
        $this->assign('item', $item); 
        $this->assign('type', $type); 
        $this->display();
    }
    
    public function delete_record() {
        $type = $_GET['type'];
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        $result;
        
        if($type == 1){
            $tem_invitation = M('tem_invitation');
            $result = $tem_invitation->where('id='.$id)->delete();           
            
        }else{
            $friend_invitation = M('friend_invitation');
            $result = $friend_invitation->where('id='.$id)->delete();   
        }
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '后台管理-约球';
            $data['operation'] = '删除记录：id='.$id." type=".$type;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Invitation/invite_list?type='.$type.'&pageId='.$pageId);
        }
        else
            $this->error('删除失败！');
    }
    
    public function invite_submit() {
        
        
        $type= $_POST['invitation_type'];
        $id = $_POST['id'];
        
        if($_POST['add'] == 0){
            if($type == 1){
                $d = date('Y-m-d H:i:s');
                $today = strtotime($d);
                $date = strtotime($_POST['time']);
                if($today > $date)
                    $this->error('管理员第一素养：时间观念！');
                        
                $tem_invitation = M('tem_invitation');
                $tem_invitation->create();
                $result = $tem_invitation->save();
            } else{
                $friend_invitation = M('friend_invitation');
                $friend_invitation->create();
                $result = $friend_invitation->save();
            }
        }else{
             if($type == 1){
                $tem_invitation = M('tem_invitation');
                $tem_invitation->create();
                $result = $tem_invitation->add();
            } else{
                $friend_invitation = M('friend_invitation');
                $friend_invitation->create();
                $result = $friend_invitation->add();
            }
            $id = $result;
        }
            
        
        if($result === FALSE)
            $this->error('提交失败！');
        else{
            // 更新日志
            //=================
            $data['module'] = '后台管理-约球';
            $data['operation'] = '提交记录：id='.$id." type=".$type;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('提交成功！', '__APP__/Invitation/invite_list');
        }
    }
    
    public function invite_comment_list() {
        $pageId = $_GET['pageId'];
        $pageId1 = 0;
        $pageId2 = 0;
        $s = new SaeStorage();
        $User = M('User');
        
        // 获取评论
        $comment = M('invitation_comment');
        $commentList = $comment->where('invitation_id='.$_GET['id']." and type=".$_GET['type'])->order('time desc')->page($_GET['pageId']+1, 30)->select(); 
        $num = $comment->where('invitation_id='.$_GET['id']." and type=".$_GET['type'])->count("id");       
        $list = array();
        
        // 设置分页Id
        $maxPageId = 0;
        if($commentList != null){
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
        } else{
             $pageId=-1;
            $pageId1=-1;
            $pageId2=-1;
        }
        
        foreach($commentList as $key=>$value){
            $list[$key]['info'] = "id:".$value['user_id']." ".$User->where('id='.$value['user_id'])->getField('username')." ".$value['time'];
            $list[$key]['comment'] = $value['comment'];
            $list[$key]['id'] = $value['id'];
        }
        
       
        $this->assign('commentList', $list);
        $this->assign('num', $num);
        $this->assign("page", ($pageId+1).'/'.$maxPageId);
        $this->assign("theme", $_GET['theme']);
        $this->assign("title", $_GET['title']);
        $this->assign("type", $_GET['type']);   
        $this->assign("id", $_GET['id']);    
        $this->assign("pageId", $pageId);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        
        $this->display();
    }
    
    public function delete_comment() {
        
        $type = $_GET['type'];
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        $result;
        $study_comment = M('invitation_comment');
       	
        $result = $study_comment->where('id='.$id)->delete();  
        
        
        if($result === FALSE)
            $this->error('删除失败！该记录不存在！');
        else{
             // 更新日志
            //=================
            $data['module'] = '后台管理-约球';
            $data['operation'] = '删除评论：id='.$id."type=".$type;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Invitation/invite_comment_list?id='.$_GET['invitation_id'].'&type='.$type.'&pageId='.$pageId);
        }
    }
   
}

?>