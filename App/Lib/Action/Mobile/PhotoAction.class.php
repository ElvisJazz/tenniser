<?php

class PhotoAction extends CommonAction {
    
    public function photo_list() {
        $club_id = $this->getClubId();
        $pageId1;
        $pageId2;
        $num = 0;
        $pageId = $_GET['pageId'];
        $exist = 1;
        
        $photo = M('photo'); 
        $s = new SaeStorage();
        $num = $photo->where('club_id='.$club_id)->count("id");
        $list = $photo->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 15)->select();    
        
        $maxPageId = 0;
        if($list != null){
            foreach($list as $key=>$value){
                // 设置封面
                if($list[$key]['coverImg']!=1 || $s->fileExists("imgdomain", 'photo/'.$value[id].'.jpg')==FALSE)
                    $list[$key]['url'] = "__IMG__/activity.jpg";
                else
                    $list[$key]['url'] = $s->getUrl("imgdomain", 'photo/'.$value[id].'.jpg');
            }
            $this->assign("list", $list); 
              
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
            $exist = 0;
        }
       
        $this->assign("page", ($pageId+1).'/'.$maxPageId);               
        $this->assign("pageId", $pageId);
        $this->assign("pageId1", $pageId1);
        $this->assign("pageId2", $pageId2);
        $this->assign("exist", $exist);
        $this->display();
    }
    
    public function photo() {    
       
        $photo = M('photo'); 
        $s = new SaeStorage();
        $path = $photo->where('id='.$_GET['id'])->getField('path');  
        $list = $s->getListByPath('imgdomain', $path);
        $files = $list["files"];
        $imgUrlList = array();
        foreach($files as $imageFile){
            $imgUrl = $s->getUrl("imgdomain", $path."/".$imageFile['Name']);
            array_push($imgUrlList, $imgUrl);
        }
        $this->assign('list', $imgUrlList);
        $this->display();
        
    }
}

?>