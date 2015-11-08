<?php

class ActivityAction extends AdminAction {
    
    public function activity_list() {
        $club_id = $_SESSION['tennis-user']['club_id'];
    	$pageId = $_GET['pageId'];
    	$pageId1;
        $pageId2;
        $list = array();
        $num = 0;
        $tip = "";      
        $activity = M('activity');
        
        //进行原生的SQL查询
        if($_SESSION['tennis-user']['role'] == 0){
            if($_GET['type'] == 0){
                $num = $activity->count("id");        
                $list = $activity->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, update_user, update_date');  
            }else{
                $num = $activity->where('type='.$_GET['type'])->count("id");        
                $list = $activity->where('type='.$_GET['type'])->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, update_user, update_date');           
            }
        }else{
            if($_GET['type'] == 0){
                $num = $activity->count("id");        
                $list = $activity->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, update_user, update_date');  
            }else{
                $num = $activity->where('club_id='.$club_id.' and type='.$_GET['type'])->count("id");        
                $list = $activity->where('club_id='.$club_id.' and type='.$_GET['type'])->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, update_user, update_date');           
            }
        }
        
        switch($_GET['type']){
            case 0: $this->assign('class0', 'active'); break; 
            case 1: $this->assign('class1', 'active'); break;
            case 2: $this->assign('class2', 'active'); break;
            case 3: $this->assign('class3', 'active'); break;
            case 4: $this->assign('class4', 'active'); break;
            default: break;            
        }

        // 设置分页Id
        $maxPageId = 0;
        $user = M('User');
        if($list!=null){
            foreach($list as $key=>$value){
                $list[$key]['update_username'] = $user->where('id='.$value[update_user])->getfield('username');
            }
            
            $offset = $num % 15;
            if($offset != 0){
                $maxPageId = ($num-$offset) / 15;
                $maxPageId++;
            } else
                $maxPageId = $num / 15;
            
            if($_GET['pageId'] == 0)
                $pageId1 = 0;
            else 
                $pageId1 = $_GET['pageId'] - 1;
                
            if($_GET['pageId'] < $maxPageId-1)
                $pageId2 = $_GET['pageId'] + 1;
            else
                $pageId2 = $maxPageId-1;    
        }else{
            $pageId = -1;
            $pageId1 = -1;
            $pageId2 = -1;
        }
      
         
        $this->assign("page", ($pageId+1).'/'.$maxPageId);               
        $this->assign("pageId", $pageId);
        $this->assign("list", $list);
        $this->assign("type", $_GET['type']);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        $this->display();
    }
   
    public function activity_detail() {
        
        $s = new SaeStorage();
        $activity = M('activity');
        $item[club_id] = $_SESSION['tennis-user']['club_id'];
        
        if($_GET['add'] == 0){
            //进行SQL查询
           $item = $activity->find($_GET['id']);
            
           if($_SESSION['tennis-user']['role']==1 && $_SESSION['tennis-user']['id']!=$item['update_user'])
                $this->error('您无此修改权限！');
            
            $user = M('User');
            $item['update_user0'] = $user->where('id='.$item['update_user'])->getField('username');
            
            switch($_GET['type']){
                case 0: $this->assign('class0', 'active'); break; 
                case 1: $this->assign('class1', 'active'); break;
                case 2: $this->assign('class2', 'active'); break;
                case 3: $this->assign('class3', 'active'); break;
                case 4: $this->assign('class4', 'active'); break;
                default: break;   
            }
            
        }else {
            $item[update_user] = $_SESSION['tennis-user']['id'];
        }
        
        $now1 = date('Y-m-d');
        $now2 = date('H:i:s');
        $this->assign('now', $now1.'T'.$now1.'Z');
        
        $this->assign('class0', 'active');
        $this->assign('add', $_GET['add']);
        $this->assign("item", $item); 
        $this->display();
    }
    
    public function delete_record() {
        
        $type = $_GET['type'];
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        $result;
        
        $activity = M('activity');
        
        if($_SESSION['tennis-user']['role'] != 0)
        	$result = $activity->where('id='.$id.' and update_user='.$_SESSION['tennis-user']['id'])->delete();   
        else
        	$result = $activity->where('id='.$id)->delete();  
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '后台管理-活动';
            $data['operation'] = '删除记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Activity/activity_list?type='.$type.'&pageId='.$pageId);
        }
        else
            $this->error('删除失败！该记录不存在或您无此删除权限！');
    }
    
    
    public function activity_submit() {
        
        $d = date('Y-m-d H:i:s');
        $today = strtotime($d);
        $date1 = strtotime($_POST['start_time']);
        $date2 = strtotime($_POST['end_time']);
        if($date1 > $date2)
            $this->error('管理员第一素养：时间观念！');
        
        $type= $_POST['type'];
        $id = $_POST['id'];
        $activity = M('activity');
        $activity->create();
        $result;
        
        if($_POST['add'] == 0)
        	$result = $activity->save();
        else{
            $result = $activity->add();
            $id = $result;
        }
        
        if($_POST['cover']==1 && F('tennis_cover_data') != null){
        	$s = new SaeStorage();
            $uploadResult = $s->write( 'imgdomain' , 'post/'.$id.'.jpg' , F('tennis_cover_data'));
			if($uploadResult == false)
            {
                F('tennis_cover_data', NULL);
                $this->error('因为封面图片存储失败，提交失败！');
            }
			
        }
        F('tennis_cover_data', NULL);
        if($result === FALSE)
            $this->error('提交失败！');
        else{
            // 更新日志
            //=================
            $data['module'] = '后台管理-活动';
            $data['operation'] = '提交记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('提交成功！', '__APP__/Activity/activity_list');
        }
    }
    
    //处理封面修改方法
    public function do_avatar_edit(){
        $user=session('tennis-user');
        if($user != null) {
            
            $src=base64_decode($_POST['pic']);
            F('tennis_cover_data',$src);            
			$rs['status'] = 1;
            echo json_encode($rs);
            
        }else{
            //$this->error('登录信息过期，请重新登录！');
            $rs['status'] = 0;
            echo json_encode($rs);
        }
    }
}
?>