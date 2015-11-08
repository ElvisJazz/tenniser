<?php

class UserAction extends CommonAction {

  
    
    public function userInfo() {
        $pageId00 = 0;
        $pageId0 = 0;
        $pageId1;
        $pageId2;
        $pageId01;
        $pageId02;
        $list = array();
        $list0 = array();
        $num = 0;
        $tip = "";
        $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
        $activity_user = M('activity_user');
        $tem_invitation = M('tem_invitation');
        $friend_invitation = M('friend_invitation');
        $exist1 = 1;
        $exist2 = 1;
        
        // ==============================获取用户信息===================================
        // 显示头像
        $user=session('tennis-user');
        $portrait = "__IMG__/portrait.jpg";
        $user_id;
        if($user != null) {
            $user_id = $user['id'];
            if($user['portrait'] == 1) {
                $s = new SaeStorage();
            	$portrait = $s->getUrl( 'imgdomain' , 'portrait/'.$user_id.'.jpg');
                
            }
            $user['ntrp'] = number_format($user[ntrp], 1);
            
        }else{
        	$this->error("您还未登录",'Index:index');    
        }
        // =============================获取活动信息======================================
       
        $num = $activity_user->where('user_id='.$user_id)->count("user_id");
         
		$list = $Model->query("select t.id, t.title, t.introduction, t.start_time, t.end_time, t.headcount, t.goodcount, t.need_sign, u.good_comment from activity t , activity_user u where t.id=u.activity_id and u.user_id=".$user_id." and u.sign=1 order by t.update_date desc limit 15 ");
        
        
        $maxPageId = 0;
       
        if($list != null){
        foreach($list as $key=>$value){
            // 设置封面
            if($list[$key]['cover']==null || $list[$key]['cover'] == "")
                $list[$key]['cover'] = "__IMG__/activity.jpg";
            else
            	$list[$key]['cover'] = $s->getUrl("imgdomain", $list[$key]['cover']);
            // 设置时间提醒
            $date_1 = date("Y-m-d H:i:s");   
            $date_2 = $value[start_time]; 
            $date_3 = $value[end_time]; 
            $d1 = strtotime($date_1);   
            $d2 = strtotime($date_2);   
            $d3 = strtotime($date_3);
            
            if($d2 > $d1){
                $days = round(($d2-$d1)/3600/24); 
				$list[$key]['tip'] = "还有".$days."天开始";
            } else if($d3 > $d1) {
                 $days = round(($d3-$d1)/3600/24); 
				 $list[$key]['tip'] = "还有".$days."天结束";
            } else {
                 $list[$key]['tip'] = "已结束";
            }  
            
            
            // 查询$activity_user表，是否参与过            
            $list[$key]['sign'] = '报名('.$value['headcount'].')';
            $list[$key]['good'] = '赞('.$value['goodcount'].')';
            
           
            $list[$key]['sign'] = '已报名('.$value['headcount'].')';
            $list[$key]['disable1'] = 'disabled';
            
            if($value[good_comment] == 1)  {                 
                $list[$key]['good'] = '已赞('.$value['goodcount'].')';
                $list[$key]['disable2'] = 'disabled';
            }
            
           
        
              
        }
      	// 设置分页Id
        $offset = $num % 15;
        if($offset != 0){
            $maxPageId = ($num-$offset) / 15;
            $maxPageId++;
        } else
            $maxPageId = $num / 15;
        
        if($pageId0 == 0)
            $pageId1 = 0;
        else 
            $pageId1 = $pageId0 - 1;
            
        if($pageId0 < $maxPageId-1)
            $pageId2 = $pageId0 + 1;
        else
            $pageId2 = $maxPageId-1;    
        } else
        {
            $pageId0=-1;
            $pageId1=-1;
            $pageId2=-1;
            $exist2 = 0;
        }   
        $maxPageId0 = $maxPageId;
        // ======================设置约球信息============================
        
        $num1 = $tem_invitation->where("update_user=".$user_id)->count();
        $num2 = $friend_invitation->where("update_user=".$user_id)->count();
        $num = $num1 + $num2;
        
		$list0 = $Model->query("(select t1.id, t1.title, t1.update_date, t1.invitation_type from tem_invitation t1 where t1.update_user=".$user_id." ) union all
        (select t2.id, t2.title, t2.update_date, t2.invitation_type from friend_invitation t2 where t2.update_user=".$user_id.") order by invitation_type desc, update_date desc limit 15");
    
        $maxPageId = 0;   
    if($list0 != null){
        
        $invitation_comment = M('invitation_comment');
        foreach($list0 as $key=>$value){
            $list0[$key]['numOfComment'] = $invitation_comment->where('invitation_id='.$value['id'].' and type='.$value['invitation_type'])->count('id');
           
        }
        
        // 设置分页Id
        
        $offset = $num % 15;
        if($offset != 0){
            $maxPageId = ($num-$offset) / 15;
            $maxPageId++;
        } else
            $maxPageId = $num / 15;
        
        if($pageId00 == 0)
            $pageId01 = 0;
        else 
            $pageId01 = $pageId00 - 1;
            
        if($pageId00 < $maxPageId-1)
            $pageId02 = $pageId00 + 1;
        else
            $pageId02 = $maxPageId-1;   
    }else {
            $pageId00=-1;
            $pageId01=-1;
            $pageId02=-1;
        	$exist1 = 0;
        }
        //=============================================
        $this->assign("page00", ($pageId00+1).'/'.$maxPageId);
        $this->assign("page0", ($pageId0+1).'/'.$maxPageId0);
        $this->assign("pageId00", $pageId00);
        $this->assign("pageId01", $pageId01);
        $this->assign("pageId02", $pageId02);
        $this->assign("pageId0", $pageId0);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        $this->assign('class0', 'active');
        // 显示用户信息
        $this->assign("activityList", $list);
        $this->assign("invitationList", $list0);
        $this->assign("portrait", $portrait); 
		$this->assign("user", $user);
        
        $this->assign("exist1", $exist1);
        $this->assign("exist2", $exist2);
        $this->display('UserCenter:userInfo');
        
        
    }
    
    public function myActivity() {
        $pageId00 = $_GET['pageId00'];
        $pageId0 = $_GET['pageId0'];
        $class = $_GET['class'];
        $pageId1;
        $pageId2;
        $pageId01;
        $pageId02;
        $list = array();
        $list0 = array();
        $num = 0;
        $tip = "";
        $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
        $activity_user = M('activity_user');
        $tem_invitation = M('tem_invitation');
        $friend_invitation = M('friend_invitation');
      
        $exist1 = 1;
        $exist2 = 1;
        
        $s = new SaeStorage();        
       	$user_id;
        
        $Model = new Model(); // 实例化一个model对象 没有对应任何数据表
      
        // ========================================获取用户信息===========================================================
        // 显示头像
        $user=session('tennis-user');
        $portrait = "__IMG__/portrait.jpg";
        $user_id;
        if($user != null) {
            $user_id = $user['id'];
            if($user['portrait'] == 1) {
                $s = new SaeStorage();
            	$portrait = $s->getUrl( 'imgdomain' , 'portrait/'.$user_id.'.jpg');
                
            }
            
        } else{
        	$this->error("您还未登录",'Index:index');    
        } 
        
        
        $num = $activity_user->where('user_id='.$user_id)->count("user_id");
        $startRow = $pageId0*15;
            
        // ======================================获取活动信息===========================================================
		$list = $Model->query("select t.id, t.title, t.introduction, t.start_time, t.end_time, t.headcount, t.goodcount, t.need_sign, u.good_comment from activity t , activity_user u where t.id=u.activity_id and u.user_id=".$user_id." order by t.update_date desc limit ".$startRow.",15");
        
        
        $maxPageId = 0;
        if($list != null){
        foreach($list as $key=>$value){
            // 设置封面
            if($list[$key]['cover']==null || $list[$key]['cover'] == "")
                $list[$key]['cover'] = "__IMG__/activity.jpg";
            else
            	$list[$key]['cover'] = $s->getUrl("imgdomain", $list[$key]['cover']);
            // 设置时间提醒
            $date_1 = date("Y-m-d H:i:s");   
            $date_2 = $value[start_time]; 
            $date_3 = $value[end_time]; 
            $d1 = strtotime($date_1);   
            $d2 = strtotime($date_2);   
            $d3 = strtotime($date_3);
            
            if($d2 > $d1){
                $days = round(($d2-$d1)/3600/24); 
				$list[$key]['tip'] = "还有".$days."天开始";
            } else if($d3 > $d1) {
                 $days = round(($d3-$d1)/3600/24); 
				 $list[$key]['tip'] = "还有".$days."天结束";
            } else {
                 $list[$key]['tip'] = "已结束";
            }  
            
            
            // 查询$activity_user表，是否参与过            
            $list[$key]['sign'] = '报名('.$value['headcount'].')';
            $list[$key]['good'] = '赞('.$value['goodcount'].')';
            
           
            $list[$key]['sign'] = '已报名('.$value['headcount'].')';
            $list[$key]['disable1'] = 'disabled';
            
            if($value[good_comment] == 1)  {                 
                $list[$key]['good'] = '已赞('.$value['goodcount'].')';
                $list[$key]['disable2'] = 'disabled';
            }
              
        }
      
         
        // 设置分页Id
        $offset = $num % 15;
        if($offset != 0){
            $maxPageId = ($num-$offset) / 15;
            $maxPageId++;
        } else
            $maxPageId = $num / 15;
        
        if($pageId0 == 0)
            $pageId1 = 0;
        else 
            $pageId1 = $pageId0 - 1;
            
        if($pageId0 < $maxPageId-1)
            $pageId2 = $pageId0 + 1;
        else
            $pageId2 = $maxPageId-1;    
        } else{
             $pageId0=-1;
            $pageId1=-1;
            $pageId2=-1;
            $exist2 = 0;
        }
        $maxPageId0 = $maxPageId;
        // =================================================设置邀请信息===========================================================
       $num1 = $tem_invitation->where("update_user=".$user_id)->count();
        $num2 = $friend_invitation->where("update_user=".$user_id)->count();
        $num = $num1 + $num2;
        $startRow = $pageId00*15;
         
		$list0 = $Model->query("(select t1.id, t1.title, t1.update_date, t1.invitation_type from tem_invitation t1 where t1.update_user=".$user_id." ) union all
        (select t2.id, t2.title, t2.update_date, t2.invitation_type from friend_invitation t2 where t2.update_user=".$user_id.") order by invitation_type desc, update_date desc limit ".$startRow.",15");
        
      	$maxPageId = 0;
        if($list0 != null){
             $invitation_comment = M('invitation_comment');
            foreach($list0 as $key=>$value){
                $list0[$key]['numOfComment'] = $invitation_comment->where('invitation_id='.$value['id'].' and type='.$value['invitation_type'])->count('id');
            }
                
            // 设置分页Id
            
            $offset = $num % 15;
            if($offset != 0){
                $maxPageId = ($num-$offset) / 15;
                $maxPageId++;
            } else
                $maxPageId = $num / 15;
            
            if($pageId00 == 0)
                $pageId01 = 0;
            else 
                $pageId01 = $pageId00 - 1;
                
            if($pageId00 < $maxPageId-1)
                $pageId02 = $pageId00 + 1;
            else
                $pageId02 = $maxPageId-1; 
        }else {
             $pageId00=-1;
            $pageId01=-1;
            $pageId02=-1;
            $exist1 = 0;
        }
        //=============================================
        $this->assign("page00", ($pageId00+1).'/'.$maxPageId);
        $this->assign("page0", ($pageId0+1).'/'.$maxPageId0);
        $this->assign("pageId00", $pageId00);
        $this->assign("pageId01", $pageId01);
        $this->assign("pageId02", $pageId02);
        $this->assign("pageId0", $pageId0);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        $this->assign($class, 'active');
        // 显示用户信息
        $this->assign("activityList", $list);
         $this->assign("invitationList", $list0);
        $this->assign("portrait", $portrait); 
		$this->assign("user", $user);
        $this->assign("exist1", $exist1);
        $this->assign("exist2", $exist2);
        
        $this->display('UserCenter:userInfo');
        
        
    }
    
    public function deleteActivity() {
    	$type = $_GET['type'];
        $id = $_GET['id'];
        $pageId = $_GET['pageId'];
        $result = 0;
        
        if($_SESSION['tennis-user']==null){
        	$this->error("您还未登录",'Index:index');    
        }
        //$invitation_user = M('invitation_user');
        
        if($type == 1){
            $tem_invitation = M('tem_invitation');
            //$invitation_user->where('invitation_id='.$id." and type=1")->delete();
            $result = $tem_invitation->where('id='.$id." and update_user=".$_SESSION['tennis-user']['id'])->delete();
        }else{
            $friend_invitation = M('friend_invitation');
            //$invitation_user->where('invitation_id='.$id." and type=2")->delete();
            $result = $friend_invitation->where('id='.$id." and update_user=".$_SESSION['tennis-user']['id'])->delete();
        }
            
        if($result){
            $this->success('删除成功！', 'myActivity?pageId0='.$pageId0.'&pageId00='.$pageId.'&class=class1');
            
            // 更新日志
            //=================
            $data['module'] = '用户中心(手机)';
            $data['operation'] = '删除活动：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->assign($class,'active');
        }
        else
            $this->error("删除失败！该记录不存在或您无此删除权限！", 'userInfo');
        
    }
    
    public function editActivity() {
        $id = $_GET['id'];
        $type = $_GET['type'];
        $item;
        if($_SESSION['tennis-user']==null){
        	$this->error("您还未登录",'Index:index');    
        }
        
        if($type == 1){
            $tem_invitation = M('tem_invitation');
            $item = $tem_invitation->find($id);
            
            if($item != null){
                $today = strtotime(date('Y-m-d H:m:s'));
                $date = strtotime($item['time']);
                if($today > $date)
                    $this->error('该约球贴已经过期了哦，不可以修改了，请另外发布吧~','/');
            }else{
             	$this->error('无此记录，系统惊呆了！');   
            }
            
        }else{
            $friend_invitation = M('friend_invitation');
            $item = $friend_invitation->find($id);
        }
        
         
        
        $user =  $_SESSION['tennis-user'];
        
        if($user['id'] != $item['update_user'])
            $this->error("您无此修改权限！", 'userInfo');
        
        if($user != null) {
            $this->assign("update_user",$user['id']);
      
        }
        
        $ntrp = $item['ntrp'];
		$item['ntrp'] = number_format($ntrp, 1);
        
        $this->assign("editStr", "edit"); 
        $this->assign("type", $type);        
        $this->assign("item", $item);
        $this->assign("edit", 2);
        $this->display("Invitation:invite_submit");
    }
    
    public function userEdit() {
        $user=session('tennis-user');
        $user['ntrp'] = number_format($user[ntrp], 1);
        $this->assign("user", $user);
        $this->display('UserCenter:userEdit');
    }
    
    //处理头像修改方法
    public function do_avatar_edit(){
        $user=session('tennis-user');
        if($user != null) {
            
            $src=base64_decode($_POST['pic1']);
			$s = new SaeStorage();
            $uploadResult = $s->write( 'imgdomain' , 'portrait/'.$user['id'].'.jpg' , $src);
			if($uploadResult == false)
            {
                
                $rs['status'] = 0;
                echo json_encode($rs);
            }
			else 
            {               
                $user_id = $user['id'];
                
                $user_info=M('User');
                $data['portrait']=1;
                
                $result=$user_info->where("id=$user_id")->save($data);
                if($result === FALSE){
                    // $this->error('头像修改失败！');
                    $rs['status'] = 0;
                    echo json_encode($rs);
                }else{
                    // 更新日志
                    //=================
                    $data['module'] = '用户中心(手机)';
                    $data['operation'] = '修改头像';
                    $data['update_user'] = $_SESSION['tennis-user']['id'];
                    $data['ip'] = $this->getIP();
                    $log = M('operation_log');
                    $log->add($data);            
                    //=================
                    
                    $_SESSION['tennis-user']['portrait']=1;   
                    $rs['status'] = 1;
                    echo json_encode($rs);
                }
            }
        }else{
            //$this->error('登录信息过期，请重新登录！');
            $rs['status'] = 0;
            echo json_encode($rs);
        }
    }
}

?> 
