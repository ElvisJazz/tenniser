<?php

class StudyAction extends CommonAction {

   
    public function study() {
    	$studyContent1 = M('study_text');          
        $studyContent2 = M('study_video'); 
        $s = new SaeStorage();
        $img;
        $id;
        $txtList = array();
        $videoList = array();
        
        $i=0;
        while($i < 11){
        	$numOfPic = 0;
       		$txtList[$i] = $studyContent1->where('theme='.$i)->order('update_date desc')->getField('id, title, update_date', 6);
            foreach($txtList[$i] as $key => $value){
               	$date = $value['update_date'];
                $txtList[$i][$key]['update_date'] = date("Y-m-d", strtotime($date));
            }
            
            $videoList[$i] = $studyContent2->where('theme='.$i)->order('update_date desc')->getField('id, title, img, update_date', 6);
            foreach($videoList[$i] as $key => $value){
               	$date = $value['update_date'];
                $videoList[$i][$key]['update_date'] = date("Y-m-d", strtotime($date));
                if($numOfPic==0 &&$value[img]==1 && $s->fileExists("imgdomain", 'study_video_img/'.$value[id].'.jpg')){
                    $img[$i] = $s->getUrl("imgdomain", 'study_video_img/'.$value[id].'.jpg');
                    $id[$i] = $value[id];
                    $numOfPic = 1;
                }
                
            }
        	$i = $i + 1;
        }
        
        
        $this->assign('img', $img);
        $this->assign('id', $id);
        $this->assign('txtList', $txtList);
        $this->assign('videoList', $videoList);
        
        
        $this->display();
        
    }
    
    public function study_detail() {    
        $resource; 
        $s = new SaeStorage();
        
        if($_GET['type'] == 1) {        
            $studyContent0 = M('study_text');   
            $txt = $studyContent0->find($_GET['id']);
           
            $content = $txt[content];
            $i = 1;
            if($txt[img]==1)//<div class='col-xs-offset-1 col-md-offset-1 col-xs-10 col-md-10'>
            {
                $content = str_replace("[center]", "<center>", $content);                
                $content = str_replace("[/center]", "</center>", $content);
                
                while(strpos($content, "<IMG".$i.">") != false){
                    $value = $s->getUrl('imgdomain', "study_text_img/".$txt[id].'_'.$i.'.jpg');                     
                    $content = str_replace("<IMG".$i.">", "<a href=".$value." data-lightbox='roadtrip' data-title='轮播'><img src='".$value."'/></a>", $content);
                    $i++;
                    
                }
            }
            
            $this->assign('type', 1);            
        	$this->assign('content', $content);
            $resource = &$txt;
           
        }
        else if($_GET['type'] == 2) {
            $studyContent1 = M('study_video');   
            $video = $studyContent1->find($_GET['id']);
            $this->assign('type', 2);            
        	$this->assign('content', $video[url]);
            $resource = &$video;
        }
        else {
            $this->error("呀，小的没找到该页面！", 'study_list');
        }
        $User = M("User");
        $condition = 'id='.$resource[update_user];
        $username = $User->where($condition)->getField("username");
        $this->assign('theme', $resource[theme]);
        $this->assign('title', $resource[title]);
        $this->assign('updateUser', $username);
        $this->assign('updateDate', $resource[update_date]);
        $this->assign('id', $_GET['id']);
        
        switch($resource[theme]){
            case 0: $this->assign('module', '入门须知'); break; 
            case 1: $this->assign('module', '正手技术'); break;
            case 2: $this->assign('module', '反手技术'); break;
            case 3: $this->assign('module', '截击技术'); break;
            case 4: $this->assign('module', '发球技术'); break;
            case 5: $this->assign('module', '组合技术'); break; 
            case 6: $this->assign('module', '组合战术'); break;
            case 8: $this->assign('module', '网球网事'); break;
            case 9: $this->assign('module', '专家解惑'); break;
            case 10: $this->assign('module', '网球小辞典'); break;
            default: $this->error("呀，小的没找到该页面！", 'study_list'); break;            
        }
        
        // 获取评论
        $comment = M('study_comment');
        $commentList = $comment->where('study_id='.$_GET['id']." and type=".$_GET['type'])->order('time desc')->page(1, 10)->getField('id, user_id, comment, time'); 
        $num = $comment->where('study_id='.$_GET['id']." and type=".$_GET['type'])->count("id");       
        $list = array();
        
        foreach($commentList as $key=>$value){
            $userList = $User->where('id='.$value['user_id'])->getField('id,username,level');
            if($userList != null){
                if($userList[$value['user_id']]['portrait'] == 1){
                    $list[$key]['img'] =  $s->getUrl( 'imgdomain' , 'portrait/'.$value['user_id'].'.jpg');     
                } else{
                    $list[$key]['img'] =  '__IMG__/portrait.jpg';
                }
                $list[$key]['info'] = $userList[$value['user_id']]['username']."(".$userList[$value['user_id']]['level'].") ".$value['time'];
                $list[$key]['comment'] = $value['comment'];
            }
        }
        
        $this->assign('commentList', $list);
        $this->assign('num', $num);
        $this->display();
        
    }
    
    public function study_list() {        
        
        $pageId1;
        $pageId2;
        $pageId = $_GET['pageId'];
        $num = 0;
        $list;
        $s = new SaeStorage();
        $exist = 1;
        
        $condition = "";
        $sqlCondition = "";
        $keyword = $_GET['keyword'];
        
        
        if($keyword!=null && $keyword!="") {
            $sqlCondition .= " and title like '%".$keyword."%' ";
        	$condition = "&keyword=".$keyword;
        }
        
        if($_GET['type'] == 1) {
            $studyContent1 = M('study_text'); 
            //进行原生的SQL查询
            $txtList = array();
            
 
            if($sqlCondition == ""){
            	$txtList = $studyContent1->where('theme='.$_GET['theme'])->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, update_date'); 
                $num = $studyContent1->where('theme='.$_GET['theme'])->count("id");
            }else{
                $txtList = $studyContent1->where('theme='.$_GET['theme'].$sqlCondition)->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, update_date'); 
                $num = $studyContent1->where('theme='.$_GET['theme'].$sqlCondition)->count("id");              
            }
            
           
            $this->assign("txtList", $txtList); 
            $list = $txtList;
           
        }
        else if($_GET['type'] == 2) {         
       		$studyContent2 = M('study_video');
            $txtList = array();
            
            //$num = $studyContent2->where('theme='.$_GET['theme'])->count("id");
 
            if($sqlCondition == ""){
            	$videoList = $studyContent2->where('theme='.$_GET['theme'])->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, img, update_date');
            	$num = $studyContent2->where('theme='.$_GET['theme'])->count("id");  
            }else{
                $videoList = $studyContent2->where('theme='.$_GET['theme'].$sqlCondition)->order('update_date desc')->page($_GET['pageId']+1, 15)->getField('id, title, img, update_date');
            	$num = $studyContent2->where('theme='.$_GET['theme'].$sqlCondition)->count("id");  
            }
               
                foreach($videoList as $key=>$value){
                if($s->fileExists("imgdomain", 'study_video_img/'.$value[id].'.jpg'))
                	$videoList[$key][imgName] = $s->getUrl("imgdomain", 'study_video_img/'.$value[id].'.jpg');
                else
                    $videoList[$key][imgName] = '__IMG__/video.jpg';
            }
            
            $this->assign("videoList", $videoList);
            $list = $videoList;
        }
        else {
            $this->error("呀，小的没找到该页面！");
        }
        
        
        // 设置分页Id
        $maxPageId = 0;
        if($list != null){
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
        } else{
             $pageId=-1;
            $pageId1=-1;
            $pageId2=-1;
            $exist = 0;
        }
                
        
        switch($_GET['theme']){
            case 0: $this->assign('module', '入门须知'); break; 
            case 1: $this->assign('module', '正手技术'); break;
            case 2: $this->assign('module', '反手技术'); break;
            case 3: $this->assign('module', '截击技术'); break;
            case 4: $this->assign('module', '发球技术'); break;
            case 5: $this->assign('module', '组合技术'); break; 
            case 6: $this->assign('module', '组合战术'); break;
            case 7: $this->assign('module', '网球装备'); break;
            case 8: $this->assign('module', '网球网事'); break;
            case 9: $this->assign('module', '专家解惑'); break;
            default: $this->error("呀，小的没找到该页面！"); break;            
        }
                
        $this->assign("page", ($pageId+1).'/'.$maxPageId);
        $this->assign("theme", $_GET['theme']);
        $this->assign("type", $_GET['type']);        
        $this->assign("pageId", $pageId);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        
        $this->assign('condition', $condition); 
        $this->assign('keyword', $keyword);
        
        $this->assign("exist", $exist);
        $this->display();
        
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
        
        $data['study_id'] = $_POST['id'];
        $data['user_id'] = $_SESSION['tennis-user']['id'];
        $data['type'] = $_POST['type'];
        $data['comment'] = $_POST['comment'];
        
        $study_comment = M('study_comment');
        $result = $study_comment->add($data);
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '教学(手机)';
            $data['operation'] = '评论资源：id='.$_POST['id'].",type=".$_POST['type'];
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->updateScoreAndExp(5);
            $this->success('评论成功！');
        }
        else
            $this->success('评论失败！');
    }
    
    public function study_more_comment() {
        
        $pageId = $_GET['pageId'];
        $pageId1 = 0;
        $pageId2 = 0;
        $s = new SaeStorage();
        $User = M('User');
        
        // 获取评论
        $comment = M('study_comment');
        $commentList = $comment->where('study_id='.$_GET['id']." and type=".$_GET['type'])->order('time desc')->page($_GET['pageId']+1, 30)->getField('id, user_id, comment, time'); 
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
            $userList = $User->where('id='.$value['user_id'])->getField('id,username,level');
            if($userList != null){
               if($userList[$value['user_id']]['portrait'] == 1)
                	$list[$key]['img'] =  $s->getUrl( 'imgdomain' , 'portrait/'.$value['user_id'].'.jpg'); 
                else
                    $list[$key]['img'] = "__IMG__/portrait.jpg";              
                $list[$key]['info'] = $userList[$value['user_id']]['username']."(".$userList[$value['user_id']]['level'].") ".$value['time'];
                $list[$key]['comment'] = $value['comment'];
            }
        }
        
        switch($resource[theme]){
            case 0: $this->assign('module', '入门须知'); break; 
            case 1: $this->assign('module', '正手技术'); break;
            case 2: $this->assign('module', '反手技术'); break;
            case 3: $this->assign('module', '截击技术'); break;
            case 4: $this->assign('module', '发球技术'); break;
            case 5: $this->assign('module', '组合技术'); break; 
            case 6: $this->assign('module', '组合战术'); break;
            case 8: $this->assign('module', '网球网事'); break;
            case 9: $this->assign('module', '专家解惑'); break;
            case 10: $this->assign('module', '网球小辞典'); break;
            default: $this->error("呀，小的没找到该页面！", 'study_list'); break;            
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
}

?>