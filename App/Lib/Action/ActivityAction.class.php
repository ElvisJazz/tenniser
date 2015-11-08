<?php

class ActivityAction extends CommonAction {
    
    public function activity_list() {
    
    	$pageId1;
        $pageId2;
        $list = array();
        $num = 0;
        $tip = "";
        $pageId = $_GET['pageId'];
        $s = new SaeStorage();        
        $activity = M('activity');
        $activity_user = M('activity_user');
        $exist = 1;
        $club_id = $this->getClubId();
        
        //进行原生的SQL查询
        
        if($_GET['type'] == 0){            
       		$num = $activity->count("id");
            $list = $activity->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 16)->getField('id, title, introduction, type, start_time, end_time, headcount, goodcount, need_sign, cover,state');  
        } else{
            $num = $activity->where('club_id='.$club_id.' and type='.$_GET['type'])->count("id");
        	$list = $activity->where('club_id='.$club_id.' and type='.$_GET['type'])->order('update_date desc')->page($_GET['pageId']+1, 16)->getField('id, title, introduction, type, start_time, end_time, headcount, goodcount, need_sign, cover, state');           
        }
        $topList = $activity->where('club_id='.$club_id.' and push_top=1')->order('update_date desc')->getField('id, title, introduction, cover'); 
        
        if($list != null){
            foreach($list as $key=>$value){
                // 设置封面
                if($list[$key]['cover']!=1 || $s->fileExists("imgdomain", 'post/'.$value[id].'.jpg')==FALSE)
                    $list[$key]['cover'] = "__IMG__/activity.jpg";
                else
                    $list[$key]['cover'] = $s->getUrl("imgdomain", 'post/'.$value[id].'.jpg');
                // 设置时间提醒
                $date_1 = date("Y-m-d H:i:s");   
                $date_2 = $value[start_time]; 
                $date_3 = $value[end_time]; 
                $d1 = strtotime( $date_1);   
                $d2 = strtotime($date_2);   
                $d3 = strtotime( $date_3);
                
                $endSign = 0;
                if($d2 > $d1){
                    $days = round(($d2-$d1)/3600/24); 
                    $list[$key]['tip'] = "还有".$days."天开始";
                } else if($d3 > $d1) {
                     $days = round(($d3-$d1)/3600/24); 
                     $list[$key]['tip'] = "还有".$days."天结束";
                } else {
                    $list[$key]['tip'] = "已结束";
                    $list[$key]['disable1'] = 'disabled';
                    $endSign = 1;
                }  
                 if($list[$key]['state'] > 0)
                    $list[$key]['tip'] = "已结束报名，".$list[$key]['tip'];
                else if($list[$key]['need_sign'] == 1 &&  $endSign == 0)
               		$list[$key]['tip'] = "火热报名中，".$list[$key]['tip'];
                
                
                if($list[$key]['state'] > 0)
                    $list[$key]['disable1'] = 'disabled';
                
                // 查询$activity_user表，是否参与过     
                $list[$key]['activityType'] = $value['type'];
                $list[$key]['sign'] = '报名('.$value['headcount'].')';
                $list[$key]['good'] = '赞('.$value['goodcount'].')';
                
                if($_SESSION['tennis-user'] != null) {
                    $auList = $activity_user->where('activity_id='.$list[$key]['id'].' and user_id='.$_SESSION['tennis-user']['id'])->limit(1)->getField('id, good_comment, sign');
                    
                    if($auList != null) {
                        $element = current($auList);
                        if($element[sign] == 1) {
                            $list[$key]['sign'] = '已报名('.$value['headcount'].')';
                            $list[$key]['disable1'] = 'disabled';
                        }
                        if($element[good_comment] == 1){                        
                            $list[$key]['good'] = '已赞('.$value['goodcount'].')';
                            $list[$key]['disable2'] = 'disabled';
                        }
                    }
                }
            }
        }
        
        // 获取封面url        
        foreach($topList as $key=>$value){
            if($topList[$key]['cover']!=1 || $s->fileExists("imgdomain", 'post/'.$value[id].'.jpg')==FALSE)
                $topList[$key]['cover'] = "__IMG__/activity.jpg";
            else
            	$topList[$key]['cover'] = $s->getUrl("imgdomain", 'post/'.$value[id].'.jpg');
           
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
        if($list != null){
            $offset = $num % 16;
            if($offset != 0){
                $maxPageId = ($num-$offset) / 16;
                $maxPageId++;
            } else
                $maxPageId = $num / 16;
            
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
        $this->assign("good", $topList); 
        $this->assign("list", $list); 
        $this->assign("topList", $topList); 
        $this->assign("type", $_GET['type']);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        $this->assign("exist", $exist);
        $this->display();
    }
    
    public function activity_sign()   {     
        if($_SESSION['tennis-user'] == null)
            $this->ajaxReturn(-1,"尚未登录！", 0);
        
        $user = $_SESSION['tennis-user'];
        // 如果是赛事活动报名
        $type = $_GET['type'];
        if($type == 2){
            $truename = $user['truename'];
            $telephone = $user['telephone'];
       		 // 错误后返回错误的操作状态和提示信息
            if($truename==null || $truename=='' || $telephone==null || $telephone=='')
            {
                $this->ajaxReturn(-2,"报名失败,请在用户中心补充真实姓名或手机号码！",0);
                return;
            }
        }
            
        $activity_user = M('activity_user');
        $data['user_id'] = $_SESSION['tennis-user']['id'];
        $data['activity_id'] = $_GET['id'];
        $data['sign'] = 1;
        $num = $activity_user->where('user_id='.$_SESSION['tennis-user']['id'].' and activity_id='.$_GET['id'])->count('id');
        if($num == 0)
            $result = $activity_user->add($data);
        else
            $result = $activity_user-> where('user_id='.$_SESSION['tennis-user']['id'].' and activity_id='.$_GET['id'])->setField('sign', 1);
        
        if ($result){
            $activity = M('activity');
            $num = $activity->where('id='.$_GET['id'])->getField('headcount');
            $result = $activity->where('id='.$_GET['id'])->setInc('headcount'); // 报名人数加1
            // 成功后返回客户端新增的用户ID，并返回提示信息和操作状态
            if($result){
            	$this->ajaxReturn($num+1, "报名成功！",1);
            }
        }else{
            // 错误后返回错误的操作状态和提示信息
            $this->ajaxReturn(0,"报名失败！",0);
        }
        $this->ajaxReturn(-2,"报名失败,请在用户中心补充真实姓名或手机号码！",0);
    }
    
    public function activity_comment()   {  
        if($_SESSION['tennis-user'] == null)
            $this->ajaxReturn(-1,"尚未登录！", 0);
        
        $activity_user = M('activity_user');
        
        $data['user_id'] = $_SESSION['tennis-user']['id'];
        $data['activity_id'] = $_GET['id'];
        $data['good_comment'] = 1;
        $num = $activity_user->where('user_id='.$_SESSION['tennis-user']['id'].' and activity_id='.$_GET['id'])->count('id');
        if($num == 0)
            $result = $activity_user->add($data);
        else
        	$result = $activity_user-> where('user_id='.$_SESSION['tennis-user']['id'].' and activity_id='.$_GET['id'])->setField('good_comment', 1);
        
        if ($result){
            $activity = M('activity');            
            $num = $activity->where('id='.$_GET['id'])->getField('goodcount');
            $result = $activity->where('id='.$_GET['id'])->setInc('goodcount'); // 报名人数加1
            // 成功后返回客户端新增的用户ID，并返回提示信息和操作状态
            if($result){
            	$this->ajaxReturn($num+1,"大赞成功！",1);
            }
        }else{
            // 错误后返回错误的操作状态和提示信息
            $this->ajaxReturn(0,"小赞失败！",0);
        }
    }
    
    public function activity_detail() {
    	
        $s = new SaeStorage();
        $activity = M('activity');
        $activity_user = M('activity_user');
        
        //进行SQL查询
        $list = $activity->where('id='.$_GET['id'])->select();
        $item;
        
        if($list != null) {
            $item['id'] = $list[0]['id'];
            $item['title'] = $list[0]['title'];
            $item['type'] = $list[0]['type'];
            $item['introduction'] = $list[0]['introduction'];
            $item['organizer'] = $list[0]['organizer'];
            $item['participant'] = $list[0]['participant'];
            $item['start_time'] = $list[0]['start_time'];
            $item['end_time'] = $list[0]['end_time'];
            $item['location'] = $list[0]['location'];
            $item['content'] = $list[0]['content'];
            $item['update_date'] = $list[0]['update_date'];
            $item['need_sign'] = $list[0]['need_sign'];
            $item['state'] = $list[0]['state'];
            $value = $list[0];
           
            // 设置时间提醒
            $date_1 = date("Y-m-d H:i:s");   
            $date_2 = $item['start_time'];
            $date_3 = $item['end_time'];
            $d1 = strtotime($date_1);   
            $d2 = strtotime($date_2);   
            $d3 = strtotime($date_3);
            
            $endSign = 0;
            if($d2 > $d1){
                $days = round(($d2-$d1)/3600/24); 
				$item['tip'] = "还有".$days."天开始";
            } else if($d3 > $d1) {
                 $days = round(($d3-$d1)/3600/24); 
				 $item['tip'] = "还有".$days."天结束";
            } else {
                 $item['tip'] = "已结束";
                 $item['disable1'] = 'disabled';
                 $endSign = 1;    
            }  
            if($list[0]['state'] > 0)
               $item['tip'] = "已结束报名，".$item['tip'];
            else if($list[0]['need_sign'] == 1 &&  $endSign == 0)
               $item['tip'] = "火热报名中，".$item['tip'];
            
            
            if($list[0]['state'] > 0)
                $item['disable1'] = 'disabled';
            
            // 查询$activity_user表，是否参与过            
            $item['sign'] = '报名('.$value['headcount'].')';
            $item['good'] = '赞('.$value['goodcount'].')';
            
            if($_SESSION['tennis-user'] != null) {
                $auList = $activity_user->where('activity_id='.$list[0]['id'].' and user_id='.$_SESSION['tennis-user']['id'])->limit(1)->getField('id, good_comment, sign');
                
                if($auList != null) {
                    $element = current($auList);
                    if($element['sign'] == 1) {
                        $item['sign'] = '已报名('.$value['headcount'].')';
                        $item['disable1'] = 'disabled';
                    }
                    if($element['good_comment'] == 1){                        
                        $item['good'] = '已赞('.$value['goodcount'].')';
                        $item['disable2'] = 'disabled';
                    }
                }
            }
        }
       
        $this->assign("item", $item); 
        $this->display();
    }
}
?>