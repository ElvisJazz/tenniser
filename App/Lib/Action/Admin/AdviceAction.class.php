<?php

class AdviceAction extends AdminAction {
    
    public function advice_list(){
    	$pageId1;
        $pageId2;
        $num = 0;
        $pageId = $_GET['pageId'];
        
        $advice = M('advice'); 
        $num = $advice->count("id");
        $list = $advice->order('updateDate desc')->page($_GET['pageId']+1, 15)->select();    
        
        $maxPageId = 0;
        if($list != null){
            
            $user = M('User');
            
            foreach($list as $key=>$value) {
                if($value['userId']==null || $value['userId']==0)
            		$list[$key]['update_user'] = '匿名';
                else{
                    $userList = $user->where('id='.$value['userId'])->getField('id, mailbox, username');
                    $list[$key]['update_user'] = $userList[$value['userId']][username];
                    $list[$key]['mailbox'] = $userList[$value['userId']][mailbox];
                }
                
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
    
     public function advice_detail() {  
        $id = $_GET['id'];
        $item;
        $user = M('User'); 
        
         $advice = M('advice');
         $item = $advice->find($id);
         
         $item[update_user] = $user->where('id='.$item[userId])->getfield(username);
        
        $this->assign('item', $item); 
        $this->display();
        
        
    }
    
    public function delete_record() {
        $pageId = $_GET['pageId'];
        $id = $_GET['id'];
        
        $advice = M('advice');
        $result = $advice->where('id='.$id)->delete();   
       
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '后台管理-反馈';
            $data['operation'] = '删除记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Advice/advice_list?pageId='.$pageId);
        }
        else
            $this->error('删除失败！');
    }
}
?>