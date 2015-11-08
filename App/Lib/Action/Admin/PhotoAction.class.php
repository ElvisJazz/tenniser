<?php

class PhotoAction extends AdminAction {
    
    public function photo_list() {        
    	$club_id = $this->getClubId();
        $pageId1;
        $pageId2;
        $num = 0;
        $pageId = $_GET['pageId'];
        
        $photo = M('photo'); 
        $num = $photo->where('club_id='.$club_id)->count("id");
        $list = $photo->where('club_id='.$club_id)->order('update_date desc')->page($_GET['pageId']+1, 15)->select();    
        
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
    
    public function photo_detail() { 
        $id = $_GET['id'];
        $item;
        $user = M('User'); 
        $item[club_id] = $_SESSION['tennis-user']['club_id'];
        
        if($_GET['add'] == 0) {
            $photo = M('photo');
            $item = $photo->find($id);
            
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
        
        $photo = M('photo');
        
        if($_SESSION['tennis-user']['role'] != 0)
            $result = $photo->where('id='.$id.' and update_user='.$_SESSION['tennis-user']['id'])->delete();   
        else
        	$result = $photo->where('id='.$id)->delete();   
       
        
        if($result){
            // 更新日志
            //=================
            $data['module'] = '后台管理-相册';
            $data['operation'] = '删除记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('删除成功！', '__APP__/Admin/Photo/photo_list?pageId='.$pageId);
        }
        else
            $this->error('删除失败！该记录不存在或您无此删除权限！');
    }
    
    public function photo_submit() {
        
        $id = $_POST['id'];
        
        
        $result;
        
        if($_POST['add'] == 0){
               
            $photo = M('photo');
            $photo->create();
            $result = $photo->save();
            
        }else{
            $data['title'] = $_POST['title'];
            $data['description'] = $_POST['description'];
            $data['coverImg'] = $_POST['coverImg'];
            $data['update_user'] = $_POST['update_user'];
            $data['club_id'] = $_POST['club_id'];
            
            $data['path'] = "photo/club".$_POST['club_id']."/".$_POST['title'].date('y-m-d h:i:s',time());
             
            $photo = M('photo');
            $result = $photo->add($data);
            $id = $result;
        }
        
        $user=session('tennis-user');
        if($_POST['coverImg']==1 && F($user.'tennis_cover_data') != null){
        	$s = new SaeStorage();
            $uploadResult = $s->write( 'imgdomain' , 'photo/'.$id.'.jpg' , F($user.'tennis_cover_data'));
			if($uploadResult == false)
            {
                F($user.'tennis_cover_data', NULL);
                $this->error('因为封面图片存储失败，提交失败！');
            }
			
        }
        F('tennis_cover_data', NULL);
        
        if($result === FALSE)
            $this->error('提交失败！');
        else{
             // 更新日志
            //=================
            $data['module'] = '后台管理-相册';
            $data['operation'] = '提交记录：id='.$id;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('提交成功！', '__APP__/Photo/photo_list?pageId=0');
        }
    }
    
    //处理封面修改方法
    public function do_avatar_edit(){
        
        $user=session('tennis-user');
        if($user != null) {
            
            $src=base64_decode($_POST['pic']);
            F($user.'tennis_cover_data',$src);            
			$rs['status'] = 1;
            echo json_encode($rs);
            
        }else{
            //$this->error('登录信息过期，请重新登录！');
            $rs['status'] = 0;
            echo json_encode($rs);
        }
    }
    
    // 进入上传照片页面
    public function photo_upload() {
        $clubId = $this->getClubId();
        $photo = M('photo');
        $path = $photo->where('id='.$_GET['id'])->getfield(path);
        F($clubId.'-photoPath', $path);
        $this->display();
        
    }
    
    // 批量上传照片
    public function many_photos_submit() {
    	// 注意：使用组件上传，不可以使用 $_FILES["Filedata"]["type"] 来判断文件类型
        mb_http_input("utf-8");
        mb_http_output("utf-8");
        
        //---------------------------------------------------------------------------------------------
        //组件设置a.MD5File为2，3时 的实例代码
        
        if($this->getGet('access2008_cmd')=='2'){ // 提交MD5验证后的文件信息进行验证
            //getGet("access2008_File_name") 	'文件名
            //getGet("access2008_File_size")	'文件大小，单位字节
            //getGet("access2008_File_type")	'文件类型 例如.gif .png
            //getGet("access2008_File_md5")		'文件的MD5签名
            
            die('0'); //返回命令  0 = 开始上传文件， 2 = 不上传文件，前台直接显示上传完成
        }
        if($this->getGet('access2008_cmd')=='3'){ //提交文件信息进行验证
            //getGet("access2008_File_name") 	'文件名
            //getGet("access2008_File_size")	'文件大小，单位字节
            //getGet("access2008_File_type")	'文件类型 例如.gif .png
            
            die('1'); //返回命令 0 = 开始上传文件,1 = 提交MD5验证后的文件信息进行验证, 2 = 不上传文件，前台直接显示上传完成
        }
        //---------------------------------------------------------------------------------------------
        $php_path = dirname(__FILE__) . '/';
        $php_url = dirname($_SERVER['PHP_SELF']) . '/';
        
        //文件保存目录路径
        $save_path = $php_path . './';//默认为 update.php所在目录
        //文件保存目录URL
        $save_url = $php_url . './';//默认为 update.php所在目录
        //定义允许上传的文件扩展名
        $ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
        //最大文件大小
        $max_size = 1024*5000;//(默认500K)
        
        $save_path = realpath($save_path) . '/';
        
        //有上传文件时
        if (empty($_FILES) === false) {
            //原文件名
            $file_name = $_FILES['Filedata']['name'];
            //服务器上临时文件名
            $tmp_name = $_FILES['Filedata']['tmp_name'];
            //文件大小
            $file_size = $_FILES['Filedata']['size'];
            
            
            // 获取缓存中的操作相册Id
            $s = new SaeStorage();
            $clubId = $this->getClubId();
        	$photoPath = F($clubId.'-photoPath');
            
            $uploadResult = $s->write( 'imgdomain' , $photoPath.'/'.$file_name , file_get_contents($tmp_name));
			if($uploadResult == false)
            {
                exit($file_name."存储失败！");
            }
            
            
            //检查文件名
            if (!$file_name) {
                exit("返回错误: 请选择文件。");
            }
            
            //检查文件大小
            if ($file_size > $max_size) {
                exit("返回错误: 上传文件($file_name)大小超过限制。最大".($max_size/1024)."KB");
            }
            $temp_arr = explode(".", $file_name);
            $file_ext = array_pop($temp_arr);
            $file_ext = trim($file_ext);
            $file_ext = strtolower($file_ext);
            
            if (in_array($file_ext, $ext_arr) === false) {
                    exit("返回错误: 上传文件扩展名是不允许的扩展名。");
            }
                
           
            $file_url = $save_url . $new_file_name;
            $fileName = uniqid('image',true).$type;
            echo "Stored in: " . $file_name."<br />";
            echo "MD5效验:".$this->getGet("access2008_File_md5")."<br />";
            echo "<br />上传成功！";
            if($this->getPost("access2008_box_info_max")!=""){
                echo "<br />组件文件列表统计,总数量：".$this->getPost("access2008_box_info_max").",剩余：".((int)$this->getPost("access2008_box_info_upload")-1).",完成：".((int)$this->getPost("access2008_box_info_over")+1);
            }
        }        
        
    }
    
    
    function filekzm($a)
    {
        $c=strrchr($a,'.');
        if($c)
        {
            return $c;
        }else{
            return '';
        }
    }
    
    function getGet($v)// 获取GET
    {
      if(isset($_GET[$v]))
      {
         return $_GET[$v];
      }else{
         return '';
      }
    }
    
    function getPost($v)// 获取POST
    {
      if(isset($_POST[$v]))
      {
          return $_POST[$v];
      }else{
          return '';
      }
    }
}

?>