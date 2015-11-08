<?php

class InvitationAction extends CommonAction {
    
    public function invite_list() {
    	$club_id = $this->getClubId();
    	$pageId1;
        $pageId2;
        $condition = "";
        $sqlCondition = "";
        $place = $_GET['place'];
        $ntrp = $_GET['ntrp'];
        $pageId = $_GET['pageId'];
        $list = array();
        $num = 0;
        $s = new SaeStorage(); 
        $topList = array();  
        $exist = 1;
        
        if($place!=null && $place!="") {
            $sqlCondition .= "place like '%".$place."%'";
        	$condition = "&place=".$place;
        }
        
        if($ntrp!=null && $ntrp!=""){
            if($place == null)
                $sqlCondition .= "ntrp=".$ntrp;
            else
                $sqlCondition .= "and ntrp=".$ntrp;
            $condition = $condition."&ntrp=".$ntrp;
        }
        

        if($_GET['type'] == 1){
            $tem_invitation = M('tem_invitation');
            if($sqlCondition == "")
            	$list = $tem_invitation->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, time, place, update_user, ntrp, type');  
            else
                $list = $tem_invitation->where($sqlCondition.' and club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, time, place, update_user, ntrp, type');  
            
            $num = $tem_invitation->count("id");
            
            $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
			$topList = $Model->query("select t.id, t.title, t.ntrp, t.update_user, u.username, u.portrait from tem_invitation t , user u where t.update_user=u.id and t.club_id=".$club_id." order by u.frequency desc, t.update_date desc limit 3 ");
            
        } else{
            $friend_invitation = M('friend_invitation');
          	if($sqlCondition == "")
            	$list = $friend_invitation->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, place, update_user, ntrp');  
            else
                $list = $friend_invitation->where($sqlCondition.' and club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 24)->getField('id, title, place, update_user, ntrp'); 
            
            $num = $friend_invitation->count("id");
            
            $Model = new Model();
            $topList = $Model->query("select t.id, t.title, t.ntrp, t.update_user, u.username, u.portrait from friend_invitation t , user u where t.update_user=u.id and t.club_id=".$club_id." order by u.frequency desc, t.update_date desc limit 3 "); 
        }
          
        $User = M('user');
        $comment = M('invitation_comment');  
        if($list != null){
            foreach($list as $key=>$value){
                // 设置封面
                $list[$key]['portrait'] = '__IMG__/portrait.jpg';
                $id = $value['update_user'];
                if($id!=null && $id!=0){
                    $user = $User->where('id='.$id)->getField('id, portrait, username');
                    if($user[$id][portrait] == 1)
                        $list[$key]['portrait'] = $s->getUrl("imgdomain", 'portrait/'.$value['update_user'].'.jpg');
                    $list[$key]['user'] = $user[$id][username];
                } else{
                    $list[$key]['user'] = '匿名';    
                }
                
                if($_GET['type'] == 1){
                    if($value['type'] == 1)
                       $list[$key]['type'] = '个人';
                    else
                       $list[$key]['type'] = '团体';
                    
                    // 约球时间小提示
                    $date_1 = date("Y-m-d H:i:s");   
                    $date_2 = $value[time];
                    $d1 = strtotime( $date_1);   
                    $d2 = strtotime( $date_2);  
                    
                    if($d2 < $d1){
                        $list[$key]['tip'] = "已经结束";
                        $list[$key]['disable'] = 'disabled';
                    }
                    else
                        $list[$key]['tip'] = "未开始";
                    
                    
                }
                // 设置评论数
                $list[$key]['numOfComment'] = $comment->where('invitation_id='.$value['id']." and type=".$_GET['type'])->count("id");
                $list[$key][ntrp] = number_format($value[ntrp], 1);
                
                // 查询$activity_user表，是否参与过    
                $invitation_user = M('invitation_user');
                $number = 0;
                $number = $invitation_user->where('invitation_id='.$list[$key]['id'].' and type='.$_GET['type'])->count('id');
                
                if($_GET['type'] == 1)
                	$list[$key]['good'] = '响应('.$number.')';
                else
                    $list[$key]['good'] = '赞('.$number.')';
                    
                if($_SESSION['tennis-user'] != null) {
                    $auList = $invitation_user->where('invitation_id='.$list[$key]['id'].' and user_id='.$_SESSION['tennis-user']['id'].' and type='.$_GET['type'])->limit(1)->select();
                    
                    if($auList != null) {
                        $element = current($auList);
                        
                        if($_GET['type'] == 1){
                            if($element[id]){                        
                                $list[$key]['good'] = '已响应('.$number.')';
                                $list[$key]['disable'] = 'disabled';
                            }else{
                                $list[$key]['good'] = '响应('.$number.')';
                            }
                        }else{
                            if($element[id]){                        
                                $list[$key]['good'] = '已赞('.$number.')';
                                $list[$key]['disable'] = 'disabled';
                            }else{
                                $list[$key]['good'] = '赞('.$number.')';
                            }
                        }
                
                    }
                }
                
            }
         }
        
        // 获取封面url        
        foreach($topList as $key=>$value){
            $topList[$key]['ntrp'] = number_format($value[ntrp], 1);
             // 设置封面
            //$User = M('user');
            //$portrait = $User->where('id='.$value['update_user'])->getField('portrait');
            if($value[portrait] == 1)
                $topList[$key]['portrait'] = $s->getUrl("imgdomain", 'portrait/'.$value['update_user'].'.jpg');
            else
                $topList[$key]['portrait'] = '__IMG__/portrait.jpg';     
            
         }

        // 设置分页Id
        $maxPageId = 0;
        if($list != null){
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
            $exist = 0;
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
        
        $this->assign("exist", $exist);
        $this->display();
    }
    
    public function invite_detail() {
    	
        $s = new SaeStorage(); 
        $list = array();
       
        
        if($_GET['type'] == 1){
            $tem_invitation = M('tem_invitation');
            $list = $tem_invitation->find($_GET['id']);  
            
        } else{
            $friend_invitation = M('friend_invitation');
          	$list = $friend_invitation->find($_GET['id']);
        }
        
        // 设置封面等参数
        $User = M('user');
        $id = $list['update_user'];
        $user = $User->find($id);
        $ntrp = $user[ntrp];
		$user[ntrp] = number_format($ntrp, 1);
        $ntrp0 = $list['ntrp'];
		$list['ntrp'] = number_format($ntrp0, 1);
        
        // 查询$activity_user表，是否参与过    
        $invitation_user = M('invitation_user');
        $number = 0;
        $number = $invitation_user->where('invitation_id='.$list['id'].' and type='.$_GET['type'])->count('id');
        
        if($_GET['type'] == 1)
            $list['good'] = '响应('.$number.')';
        else
            $list['good'] = '赞('.$number.')';
        
        if($_SESSION['tennis-user'] != null) {
            $auList = $invitation_user->where('invitation_id='.$list['id'].' and user_id='.$_SESSION['tennis-user']['id'].' and type='.$_GET['type'])->limit(1)->select();
            
            if($auList != null) {
                $element = current($auList);
                
                if($_GET['type'] == 1){
                    if($element[id]){                        
                        $list['good'] = '已响应('.$number.')';
                        $list['disable'] = 'disabled';
                    }else{
                        $list['good'] = '响应('.$number.')';
                    }
                }else{
                    if($element[id]){                        
                        $list['good'] = '已赞('.$number.')';
                        $list['disable'] = 'disabled';
                    }else{
                        $list['good'] = '赞('.$number.')';
                    }
                }
                
            }
        }
        
        if($user != null){
            if($user[portrait] == 1)
                $list['portrait'] = $s->getUrl("imgdomain", 'portrait/'.$list['update_user'].'.jpg');
            else
                $list['portrait'] = '__IMG__/portrait.jpg';   
            
            if($_GET['type'] == 1){
                if($list['type'] == 1)
                    $list['type'] = '个人';
                else
                    $list['type'] = '团体';
                
                // 约球时间小提示
                $date_1 = date("Y-m-d H:i:s");   
                $date_2 = $list['time'];
                $d1 = strtotime($date_1);   
                $d2 = strtotime($date_2);  
                
                if($d2 < $d1){
                    $list['tip'] = "已经结束";
                    $list['disable'] = 'disabled';
                }
                else
                    $list['tip'] = "未开始";
            }
            
            
            
            $list['user'] = $user['username'];
            
            if($user['sex'] == 0)
                $user['sex'] = '男';
            else
                $user['sex'] = '女';
            
            if($user['truename'] == NULL || $user['truename'] == "")
                $user['truename'] ='暂无';
        } else{
            $list['user'] = '匿名';    
        }
        
        // 获取评论
        $comment = M('invitation_comment');
        $commentList = $comment->where('invitation_id='.$_GET['id']." and type=".$_GET['type'])->order('time desc')->page(1, 10)->getField('id, user_id, comment, time'); 
        $num = $comment->where('invitation_id='.$_GET['id']." and type=".$_GET['type'])->count("id");       
        $list1 = array();
        
        foreach($commentList as $key=>$value){
            $userList = $User->where('id='.$value['user_id'])->getField('id,username,level, portrait');
            if($userList != null){
                    if($userList[$value['user_id']]['portrait'] == 1)
                        $list1[$key]['img'] =  $s->getUrl( 'imgdomain' , 'portrait/'.$value['user_id'].'.jpg'); 
                    else
                        $list1[$key]['img'] =  "__IMG__/portrait.jpg";
                    $list1[$key]['info'] = $userList[$value['user_id']]['username']."(".$userList[$value['user_id']]['level'].") ".$value['time'];
                    $list1[$key]['comment'] = $value['comment'];
                    $list1[$key]['user_id'] = $value['user_id'];
                    $list1[$key]['reply_id'] = $value['id'];
               
            }
        }
        
        $this->assign('commentList', $list1);
        $this->assign('num', $num);
        $this->assign('id', $_GET['id']);
        $this->assign('user_id', $list['update_user']);
        $this->assign("item", $list); 
        $this->assign("user", $user); 
        $this->assign("type", $_GET['type']);
        $this->display();
    }
    
    public function invite_comment()   {  
        if($_SESSION['tennis-user'] == null)
            $this->ajaxReturn(-1,"尚未登录！", 0);
        
        $invitation_user = M('invitation_user');
        
        $data['user_id'] = $_SESSION['tennis-user']['id'];
        $data['invitation_id'] = $_GET['id'];
        $data['type'] = $_GET['type'];
        $num = $invitation_user->where('user_id='.$_SESSION['tennis-user']['id'].' and invitation_id='.$_GET['id'])->count('id');
        
        $result = $invitation_user->add($data);        
        
        if ($result){
            
            if($result){
            	$this->ajaxReturn($num+1,"大赞成功！",1);
            }
        }else{
            // 错误后返回错误的操作状态和提示信息
            $this->ajaxReturn(0,"小赞失败！",0);
        }
    }
    
    public function invite_submit() {
        $type;
        if($_GET['type'] == 1)
            $type = 4;
        else
            $type = 3;
        
        if($this->checkScore($type) === false)
            $this->error('很遗憾，您的积分不够了哟！~');
        
       
        $user =  $_SESSION['tennis-user'];
        
        if($user != null) {
            
            if($user['is_forbidden'] == 1)
                $this->error('很抱歉，言多必失，您已被封印！
                解封日期请查看用户中心，如有必要请联系管理员！');
            
            $now1 = date('Y-m-d');
            $now2 = date('H:i:s');
            $this->assign('now', $now1.'T'.$now1.'Z');
            $this->assign("update_user",$user['id']);
            $this->assign("edit", 1);
            $this->assign("club_id", $this->getClubId());
            $this->assign("type", $_GET['type']);
        	$this->display();
        } else {
            $this->error('讨厌，没登录就想来一发？！', 'invite_list?type='.$_GET['type'].'&pageId=0');
        }
    }
    
    public function invite_submit_edit1() {
        $d = date('Y-m-d H:i:s');
    	$today = strtotime($d);
        $date = strtotime($_POST['time']);
        if($today > $date)
            $this->error('约球时间表明您已经穿越了！');
        
        $tem_invitation = M("tem_invitation"); // 实例化tem_invitation对象
        $tem_invitation->create();
        $result = $tem_invitation->save(); // 根据条件保存修改的数据
       
        if($result === FALSE)
            $this->error('发球失误~修改失败！');
        else{
            // 更新日志
            //=================
            $data['module'] = '临时约球(手机)';
            $data['operation'] = '修改内容：id='.$_POST['id'];
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('修改成功！', 'invite_list?type=1&pageId=0');
        }
           
    }
    
     public function invite_submit_edit2() {
    	
        $friend_invitation = M("friend_invitation"); // 实例化tem_invitation对象
        $friend_invitation->create();
        $result = $friend_invitation->save(); // 根据条件保存修改的数据
       
        if($result === FALSE)
            $this->error('发球失误~修改失败！', '__APP__/Mobile/User/editActivity?type=2&id='.$_POST['id']);
         else{
             // 更新日志
            //=================
            $data['module'] = '长期求友(手机)';
            $data['operation'] = '修改内容：id='.$_POST['id'];
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('修改成功！', 'invite_list?type=2&pageId=0');
         }
    }
    
    public function invite_submit_1() {
         $d = date('Y-m-d H:i:s');
        $today = strtotime($d);
        $date = strtotime($_POST['time']);
        if($today > $date)
            $this->error('约球时间表明您已经穿越了！');
    	
        /*if(!checkScore(4))
            $this->error('很遗憾，您的积分不够了哟！~');*/
        
        $tem_invitation = M("tem_invitation"); // 实例化tem_invitation对象
        $tem_invitation->create();    
        
        $result = $tem_invitation->add(); // 根据条件保存修改的数据
       
        if($result === FALSE)
            $this->error('发球失误~提交失败！', 'invite_submit_1');
        else{
            // 更新日志
            //=================
            $data['module'] = '临时约球(手机)';
            $data['operation'] = '发布内容：id='.$result;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->updateScoreAndExp(4);
            $this->success('ACE球哟~提交成功！', 'invite_list?type=1&pageId=0');
        }
           
    }
    
     public function invite_submit_2() {
         /*if(!checkScore(3))
             $this->error('很遗憾，您的积分不够了哟！~');*/
    	
        $friend_invitation = M("friend_invitation"); // 实例化tem_invitation对象
        $friend_invitation->create();
        $result = $friend_invitation->add(); // 根据条件保存修改的数据
        
        if($result === FALSE)
            $this->error('发球失误~提交失败！', 'invite_submit_2');
        else{
            // 更新日志
            //=================
            $data['module'] = '长期求友(手机)';
            $data['operation'] = '发布内容：id='.$result;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            $this->updateScoreAndExp(3);
            $this->success('ACE球哟~提交成功！', 'invite_list?type=2&pageId=0');
         }
    }
    
    public function comment() {
        $user =  $_SESSION['tennis-user'];
        
        if($user != null) {
            
            if($user['is_forbidden'] == 1)
                $this->error('很抱歉，言多必失，您已被封印！
                解封日期请查看用户中心，如有必要请联系管理员！');
        }else {
            $this->error('很遗憾，您还未登录哟？！');
        }
        
        $data['invitation_id'] = $_POST['id'];
        $data['type'] = $_POST['type'];
        $data['user_id'] = $_SESSION['tennis-user']['id'];
        $data['comment'] = $_POST['comment'];
        
        $invitation_comment = M('invitation_comment');
        $result = $invitation_comment->add($data);
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '约球(手机)';
            $data['operation'] = '评论球友：id='.$_POST['id'];
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->updateScoreAndExp(6);
            
            // 发送邮件
            // ==========================================================
            $User = M("User");
        	$mailbox= $User->where('id='.$_POST['user_id'])->getField('mailbox');
            $link = "http://tenniser.sinaapp.com/index.php/Invitation/invite_detail?type=".$_POST['type']."&id=".$_POST['id']."#".$result;
            $mail = new SaeMail();
            $ret = $mail->quickSend( 
                $mailbox,
                "网动青春约球评论" ,
                "尊敬的用户：
                您好！球友 ".$_SESSION['tennis-user']['username']." 在您的约球贴中评论了您哟~ 
                内容如下：".$data['comment'].
                "
                访问此链接查看详情：".$link."
                
                
                			爱网球，爱青春，你我同行。——网动青春" ,
                "tenniser2014@gmail.com" ,
                "jjaazz901222" 
            );
        
            $mail->clean();	
            // ==========================================================
            $this->success('评论成功！');
        }
        else
            $this->success('评论失败！');
    }
    
   public function invite_more_comment() {
        
        $pageId = $_GET['pageId'];
        $user_id = $_GET['user_id'];
        $pageId1 = 0;
        $pageId2 = 0;
        $s = new SaeStorage();
        $User = M('User');
        
        // 获取评论
        $comment = M('invitation_comment');
        $commentList = $comment->where('invitation_id='.$_GET['id'])->order('time desc')->page($_GET['pageId']+1, 30)->getField('id, user_id, comment, time'); 
        $num = $comment->where('invitation_id='.$_GET['id'])->count("id");       
        $list1 = array();
        
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
        	
            $userList = $User->where('id='.$value['user_id'])->getField('id,username,level, portrait');
            if($userList != null){
                if($userList[$value['user_id']]['portrait'] == 1)
                	$list1[$key]['img'] =  $s->getUrl( 'imgdomain' , 'portrait/'.$value['user_id'].'.jpg'); 
                else
                    $list1[$key]['img'] = "__IMG__/portrait.jpg";
                $list1[$key]['info'] = $userList[$value['user_id']]['username']."(".$userList[$value['user_id']]['level'].") ".$value['time'];
                $list1[$key]['comment'] = $value['comment'];
                $list1[$key]['user_id'] = $value['user_id'];
                $list1[$key]['reply_id'] = $value['id'];
            }
        }
       
        
        $this->assign('commentList', $list1);
        $this->assign('num', $num);
        $this->assign("page", ($pageId+1).'/'.$maxPageId);        
        $this->assign('user_id', $user_id);
        $this->assign("title", $_GET['title']);
        $this->assign("type", $_GET['type']);        
        $this->assign("id", $_GET['id']); 
        $this->assign("pageId", $pageId);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        
        $this->display();
    }
   
    public function reply(){
        $user =  $_SESSION['tennis-user'];
        
        if($user != null) {
            
            if($user['is_forbidden'] == 1)
                $this->error('很抱歉，言多必失，您已被封印！
                解封日期请查看用户中心，如有必要请联系管理员！');
        }else {
            $this->error('很遗憾，您还未登录哟？！');
        }
        
        $User = M("User");
        $userList= $User->where('id='.$_POST['user_id'])->getField('id, username, mailbox');
                
        $data['invitation_id'] = $_POST['id'];
        $data['type'] = $_POST['type'];
        $data['user_id'] = $_SESSION['tennis-user']['id'];
        $data['comment'] = "回复".$userList[$_POST['user_id']]['username']."：".$_POST['comment'];   
        
        $invitation_comment = M('invitation_comment');
        $result = $invitation_comment->add($data);
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '约球(手机)';
            $data['operation'] = '回复球友：id='.$_POST['id']."user_id:".$_POST['user_id'];
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->updateScoreAndExp(6);
            
            // 发送邮件
            // ==========================================================
            $link = "http://tenniser.sinaapp.com/index.php/Invitation/invite_detail?type=".$_POST['type']."&id=".$_POST['id']."#".$result;
            $mail = new SaeMail();
            //$mail->setAttach( array("my_photo.jpg" => "照片的二进制数据" ));
            $ret = $mail->quickSend( 
                $userList[$_POST['user_id']]['mailbox'] ,
                "网动青春约球回复" ,
                "尊敬的用户：
                您好！球友 ".$_SESSION['tennis-user']['username']." 在您的约球贴中回复了您哟~
                内容如下：".$data['comment'].
                "
                访问此链接查看详情：".$link."
                
                
                			爱网球，爱青春，你我同行。——网动青春" ,
                "tenniser2014@gmail.com" ,
                "jjaazz901222" 
            );
        
            $mail->clean();	
            // ==========================================================
            
            $this->success('回复成功！');
        }
        else
            $this->success('回复失败！');
        
    }
}

?>