<?php

class AnnouncementAction extends AdminAction {
    
    public function announcement_list() {
        $pageId1;
        $pageId2;
        $num = 0;
        $club_id = $this->getClubId();
        $pageId = $_GET['pageId'];
        
        $announcement = M('announcement'); 
        $num = $announcement->where('club_id='.$club_id)->count("id");
        $list = $announcement->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 15)->select();    
        
        $maxPageId = 0;
        if($list != null){
            
            $user = M('User');
            
            foreach($list as $key=>$value) {
            	$list[$key]['update_username'] = $user->where('id='.$value['update_user'])->getField('username');
                    
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
        $this->display();
    }
    
    public function announcement_detail() { 
        $id = $_GET['id'];
        $item;
        $user = M('User'); 
        $item[club_id] = $_SESSION['tennis-user']['club_id'];
        
        
        if($_GET['add'] == 0) {
            $announcement = M('announcement');
            $item = $announcement->find($id);
            
            if($_SESSION['tennis-user']['role']==1 && $_SESSION['tennis-user']['id']!=$item['update_user'])
                $this->error('您无此修改权限！');
            
            $item[update_user0] = $user->where('id='.$item[update_user])->getfield(username);
        } else {
            $item[update_user] = $_SESSION['tennis-user']['id'];
        }
        
        $this->assign('add', $_GET['add']);
        $this->assign('item', $item); 
        $this->display();
        
        
    }
    
    public function delete_record() {
        
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        $result;
        
        $announcement = M('announcement');
        
        if($_SESSION['tennis-user']['role'] != 0)
            $result = $announcement->where('id='.$id.' and update_user='.$_SESSION['tennis-user']['id'])->delete();   
        else
        	$result = $announcement->where('id='.$id)->delete();   
       
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '后台管理-公告';
            $data['operation'] = '删除记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Announcement/announcement_list?pageId='.$pageId);
        }
        else
            $this->error('删除失败！该记录不存在或您无此删除权限！');
    }
    
    public function announcement_submit() {
        
        $id = $_POST['id'];
        $announcement;
        $result;
        
        if($_POST['add'] == 0){
            
            $announcement = M('announcement');
            $announcement->create();
            $result = $announcement->save();
            
        }else{
            
            $announcement = M('announcement');
            $announcement->create();
            $result = $announcement->add();
            $id = $result;
        }
        
       
        
        if($result === FALSE)
            $this->error('提交失败！');
        else{
             // 更新日志
            //=================
            $data['module'] = '后台管理-公告';
            $data['operation'] = '提交记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            
            $this->success('提交成功！', '__APP__/Admin/Announcement/announcement_list?pageId=0');
        }
    }
   
    
 
}

?>