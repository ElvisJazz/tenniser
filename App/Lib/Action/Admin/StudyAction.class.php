<?php

class StudyAction extends AdminAction {
    
    public function study_list() {
        $theme = $_GET['theme'];
        $type = $_GET['type'];
        $pageId = $_GET['pageId'];        
        $pageId1;$pageId2;$num;
        $class1;
        $class2;
        $class[''.$theme.''] = 'active';
        $list = array();
         
        $condition = "";
        $sqlCondition = "";
        $study_id = $_GET['study_id'];
        
        if($study_id!=null && $study_id!="") {
            $sqlCondition .= "id =".$study_id;
        	$condition = "&study_id=".$study_id;
        }
        
        if($type == 1){
            $study_text = M('study_text');
            
            if($sqlCondition == ""){
                $num = $study_text->where('theme='.$theme)->count("id");
                $list = $study_text->where('theme='.$theme)->order('update_date desc')->page($pageId+1, 15)->getField('id, title, theme, update_user, update_date');           
            }else{
                $num = $study_text->where($sqlCondition)->count("id");
                $list = $study_text->where($sqlCondition)->order('update_date desc')->page($pageId+1, 15)->getField('id, title, theme, update_user, update_date'); 
            }
        	
           
        }else{
            $study_video = M('study_video');
            
            if($sqlCondition == ""){
                $num = $study_video->where('theme='.$theme)->count("id");
                $list = $study_video->where('theme='.$theme)->order('update_date desc')->page($pageId+1, 15)->getField('id, title, theme, update_user, update_date');           
            }else{
                $num = $study_video->where($sqlCondition)->count("id");
                $list = $study_video->where($sqlCondition)->order('update_date desc')->page($pageId+1, 15)->getField('id, title, theme, update_user, update_date'); 
            }
        }
       
            
        // 显示更新人和评论数目
        $user = M('User');   
        $comment = M('study_comment');
        $maxPageId = 0;
        if($list != null) {
            foreach($list as $key=>$value) {
                if($value[update_user]!=null)
                    $list[$key][update_username] = $user->where('id='.$value['update_user'])->getfield(username);
                 
                // 获取评论数目
        		$list[$key]['numOfComment'] = $comment->where('type='.$_GET['type']." and study_id=".$value['id'])->count('id');
            }
        
            // 设置分页Id            
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
        }
        else {
            $pageId=-1;
            $pageId1=-1;
            $pageId2=-1;
        }
        
        if($type == 1)
            $class1 = "active";
        if($type == 2)
            $class2 = "active";
        
        $this->assign('page', ($pageId+1).'/'.$maxPageId);
        $this->assign('pageId', $pageId);
        $this->assign('class', $class);
        $this->assign('class1', $class1);
        $this->assign('class2', $class2);
        $this->assign('list', $list);
        $this->assign('theme', $theme);
        $this->assign('type', $type);
        $this->assign('pageId1', $pageId1);
        $this->assign('pageId2', $pageId2);
        
        $this->assign('condition', $condition); 
        $this->assign('study_id', $study_id);
        
        $this->display();
    }
    
    public function study_detail() {
        
        $type = $_GET['type'];
        $id = $_GET['id'];
        $theme= $_GET['theme'];
        $class[''.$theme.''] = 'active';
        $item;
        $user = M('User'); 
        
        if($_GET['add'] == 0) {
            if($type == 1){
                $study_text = M('study_text');
                $item = $study_text->find($id);           
                
            }else{
                $study_video = M('study_video');
                $item = $study_video->find($id);
            }
            
            if($_SESSION['tennis-user']['role']==1 && $_SESSION['tennis-user']['id']!=$item['update_user'])
                $this->error('您无此修改权限！');
                
            $item[update_user0] = $user->where('id='.$item[update_user])->getfield(username);
        } else {
            $item[update_user] = $_SESSION['tennis-user']['id'];
        }
        
        $this->assign('add', $_GET['add']);
        $this->assign('class', $class);
        $this->assign('item', $item); 
        $this->assign('theme', $theme); 
        $this->assign('type', $type); 
        $this->display();
    }
    
    public function delete_record() {
        
        $type = $_GET['type'];
        $theme = $_GET['theme'];
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        $result;
        
        if($type == 1){
            $study_text = M('study_text');
            
            if($_SESSION['tennis-user']['role'] != 0)
            	$result = $study_text->where('id='.$id.' and update_user='.$_SESSION['tennis-user']['id'])->delete();  
            else         
                $result = $study_text->where('id='.$id)->delete();
            
        }else{
            $study_video = M('study_video');
            
            if($_SESSION['tennis-user']['role'] != 0)
            	$result = $study_video->where('id='.$id.' and update_user='.$_SESSION['tennis-user']['id'])->delete();  
            else         
                $result = $study_video->where('id='.$id)->delete();    
        }
        
        if($result === FALSE)
            $this->error('删除失败！该记录不存在或您无此删除权限！');
        else{
             // 更新日志
            //=================
            $data['module'] = '后台管理-教学';
            $data['operation'] = '删除记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Study/study_list?type='.$type.'&theme='.$theme.'&pageId='.$pageId);
        }
    }
    
    public function study_submit() {
        
        $type= $_POST['type'];
        $id = $_POST['id'];
        $theme = $_POST['theme'];
        $result;
        
        if($_POST['add'] == 0){
            if($type == 1){
                $text = M('study_text');
                $text->create();
                $result = $text->save();
            } else{
                $video = M('study_video');
                $video->create();
                $result = $video->save();
            }
        }else{
             if($type == 1){
                $text = M('study_text');
                $text->create();
                $result = $text->add();
            } else{
                $video = M('study_video');
                $video->create();
                $result = $video->add();
            }
         	$id = $result;           
        }    
        
        if($type == 2){ 
            if($_POST['img']==1 && F('tennis_cover_data') != null){
                $s = new SaeStorage();
                $uploadResult = $s->write( 'imgdomain' , 'study_video_img/'.$id.'.jpg' , F('tennis_cover_data'));
               
                if($uploadResult == false)
                {
                    F('tennis_cover_data', NULL);
                    $this->error('封面图片存储失败！');
                }
                
            }
            F('tennis_cover_data', NULL);
        }
        
        if($result === FALSE)
            $this->error('提交失败！');
        else{
             // 更新日志
            //=================
            $data['module'] = '后台管理-教学';
            $data['operation'] = '提交记录：id='.$id." type=".$type;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('提交成功！', '__APP__/Admin/Study/study_list?theme='.$theme.'&type='.$type);
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
    
    public function study_comment_list() {
        $pageId = $_GET['pageId'];
        $pageId1 = 0;
        $pageId2 = 0;
        $s = new SaeStorage();
        $User = M('User');
        
        // 获取评论
        $comment = M('study_comment');
        $commentList = $comment->where('study_id='.$_GET['id']." and type=".$_GET['type'])->order('time desc')->page($_GET['pageId']+1, 30)->select(); 
        $num = $comment->where('study_id='.$_GET['id']." and type=".$_GET['type'])->count("id");       
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
        $study_comment = M('study_comment');
       	
        $result = $study_comment->where('id='.$id)->delete();  
        
        
        if($result === FALSE)
            $this->error('删除失败！该记录不存在！');
        else{
             // 更新日志
            //=================
            $data['module'] = '后台管理-教学';
            $data['operation'] = '删除评论：id='.$id."type=".$type;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Study/study_comment_list?id='.$_GET['study_id'].'&type='.$type.'&pageId='.$pageId);
        }
    }
}
?>